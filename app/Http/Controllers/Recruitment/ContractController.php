<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\{Contract, ContractTemplate, Applicant, Unit, Document, Signature, Approval, User, Employee, Person};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('Superadmin');
        $isDhc = $user->hasRole('DHC');
        $isEmployee = $user->hasRole('Karyawan');
        $canSeeAll = $isSuperadmin || $isDhc;
        $userUnitId = (int)($user->unit_id ?? 0);
        $userEmployeeId = $user->employee_id;
        
        $isApproverOnly = $user->can('contract.approve') && !$user->can('contract.update');
        $selectedUnitId = $canSeeAll ? ($request->filled('unit_id') ? (int)$request->integer('unit_id') : null) : $userUnitId;

        $query = Contract::with(['unit', 'employee', 'document', 'person', 'applicant'])->orderByDesc('created_at');

        if ($isEmployee && !$isSuperadmin && !$isDhc && !$isApproverOnly) {
             $query->where('employee_id', $userEmployeeId)
                   ->whereIn('status', ['approved', 'signed']); 
        } else {
            if ($selectedUnitId) {
                $query->where('unit_id', $selectedUnitId);
            } elseif ($isDhc && !$isSuperadmin) {
                $query->whereHas('unit', fn($q) => $q->where('category', 'ENABLER'));
            } elseif ($isSuperadmin) {
                $query->whereHas('unit', fn($q) => $q->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']));
            }
    
            if ($isApproverOnly) {
                $query->where('status', '!=', 'draft');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $contracts = $query->paginate(25)->withQueryString();

        $unitsQuery = Unit::query();
        if ($isSuperadmin) {
            $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        } elseif ($isDhc) {
            $unitsQuery->where('category', 'ENABLER');
        }
        $units = $canSeeAll ? $unitsQuery->orderBy('name')->get(['id', 'name']) : Unit::where('id', $selectedUnitId)->get(['id', 'name']);

        $expiringContracts = collect();
        if (!$isEmployee) {
            $expiringQuery = DB::table('portfolio_histories AS ph')
                ->leftJoin('employees AS e', 'e.person_id', '=', 'ph.person_id')
                ->leftJoin('units AS u', 'u.id', '=', 'e.unit_id')
                ->select('ph.id', 'ph.person_id', 'e.employee_id', 'ph.title AS position_name', 'ph.start_date', 'ph.end_date', 'e.employee_status', 'e.unit_id', 'u.name AS unit_name', DB::raw("(SELECT full_name FROM persons WHERE persons.id = ph.person_id) AS person_name"))
                ->where('ph.category', 'job')->whereNotNull('ph.end_date')->whereDate('ph.end_date', '>=', now())->whereDate('ph.end_date', '<=', now()->addDays(30));

            if (!$canSeeAll && $userUnitId) {
                $expiringQuery->where('e.unit_id', $userUnitId);
            }
            $expiringContracts = $expiringQuery->orderBy('ph.end_date', 'asc')->get();
        }

        return view('recruitment.contracts.index', [
            'contracts' => $contracts,
            'units' => $units,
            'positions' => DB::table('positions')->select('id', 'name')->orderBy('name')->get(),
            'selectedUnitId' => $selectedUnitId,
            'statusFilter' => $request->input('status'),
            'statusOptions' => config('recruitment.contract_statuses'),
            'contractTypes' => collect(config('recruitment.contract_types'))->pluck('label', 'code'),
            'contractTypeConfigs' => config('recruitment.contract_types'),
            'employmentTypes' => collect(config('recruitment.employment_types'))->map(fn($v, $k) => ['value' => $k, 'label' => $v])->values(),
            'applicants' => Applicant::whereIn('status', config('recruitment.contract_applicant_statuses'))->orderBy('full_name')->get(),
            'expiringContracts' => $expiringContracts,
            'canSeeAll' => $canSeeAll,
            'currentUser' => $user,
        ]);
    }

    public function baseOptions(Request $request)
    {
        abort_unless($request->user() && $request->user()->can('contract.view'), 403);
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        $v = $request->validate([
            'contract_type' => 'required|string', 'unit_id' => 'required|integer', 'new_unit_id' => 'nullable|integer', 'applicant_id' => 'nullable|string', 'employee_id' => 'nullable|string', 'person_id' => 'nullable|string',
            'position_name' => 'nullable|string', 'employment_type' => 'nullable|string', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date', 'remarks' => 'nullable|string',
            'salary_amount' => 'nullable|string', 'salary_amount_words' => 'nullable|string', 'lunch_allowance_daily' => 'nullable|string', 'lunch_allowance_words' => 'nullable|string',
            'allowance_special_amount' => 'nullable|string', 'allowance_special_words' => 'nullable|string', 'allowance_position_amount' => 'nullable|string', 'allowance_position_words' => 'nullable|string',
            'allowance_communication_amount' => 'nullable|string', 'allowance_communication_words' => 'nullable|string', 'allowance_other_amount' => 'nullable|string', 'allowance_other_words' => 'nullable|string',
            'allowance_other_desc' => 'nullable|string', 'other_benefits_desc' => 'nullable|string', 'pb_effective_end' => 'nullable|date', 'pb_compensation_amount' => 'nullable|string', 'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit', 'source_contract_id' => 'nullable|integer', 'requires_draw_signature' => 'nullable', 'requires_camera' => 'nullable', 'requires_geolocation' => 'nullable',
        ]);
        if (!$request->user()->hasRole(['Superadmin', 'DHC']) && (int)$v['unit_id'] !== (int)$request->user()->unit_id) {
            return back()->withErrors(['unit_id' => 'Unit tidak valid.'])->withInput();
        }
        DB::beginTransaction();
        try {
            $c = new Contract();
            $c->fill($v);
            $c->contract_no = null;
            $c->unit_id = $v['new_unit_id'] ?: $v['unit_id'];
            if (in_array($v['contract_type'], ['SPK', 'PKWT_BARU']) && $v['applicant_id']) {
                $a = Applicant::find($v['applicant_id']);
                $c->applicant_id = $a->id;
                $c->person_id = $a->person_id;
                if (empty($v['position_name'])) {
                    $c->position_name = $a->position_applied;
                }
            } else {
                $c->person_id = $v['person_id'] ?: Employee::where('employee_id', $v['employee_id'])->value('person_id');
                if ($v['source_contract_id'] && Contract::find($v['source_contract_id'])) {
                    $c->parent_contract_id = $v['source_contract_id'];
                }
            }
            if (!$c->person_id) {
                $c->person_id = $request->input('person_id');
            }
            $c->requires_draw_signature = $request->has('requires_draw_signature');
            $c->requires_camera = $request->has('requires_camera');
            $c->requires_geolocation = $request->has('requires_geolocation');
            $meta = $this->collectMeta($v);
            $cand = $this->resolveCandidate($c);
            $meta['person_name'] = $cand['name'];
            if ($v['new_unit_id']) {
                $meta['new_unit_name'] = Unit::find($v['new_unit_id'])?->name;
                $meta['new_unit_id'] = (int)$v['new_unit_id'];
            }
            $c->remuneration_json = $meta;
            $c->status = ($v['submit_action'] === 'submit') ? 'review' : 'draft';
            
            if ($c->status === 'review') {
                $c->contract_no = $this->generateContractNumber($c);
            }
            
            $c->created_by_user_id = $request->user()->id;
            $c->created_by_person_id = $request->user()->person_id;
            $c->save();
            
            $this->ensureDocumentRecord($c);
            if ($c->status === 'review') {
                $this->createApproval($c, $request->user());
                $this->generatePdfFile($c);
            }
            DB::commit();
            return redirect()->route('recruitment.contracts.index', ['unit_id' => $c->unit_id])->with('success', 'Berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya draft yang bisa diedit.');
        }
        $v = $request->validate([
            'contract_type' => 'required', 'unit_id' => 'required', 'new_unit_id' => 'nullable', 'applicant_id' => 'nullable', 'employee_id' => 'nullable', 'person_id' => 'nullable',
            'position_name' => 'nullable', 'employment_type' => 'nullable', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date', 'remarks' => 'nullable',
            'salary_amount' => 'nullable', 'salary_amount_words' => 'nullable', 'lunch_allowance_daily' => 'nullable', 'lunch_allowance_words' => 'nullable',
            'allowance_special_amount' => 'nullable', 'allowance_special_words' => 'nullable', 'allowance_position_amount' => 'nullable', 'allowance_position_words' => 'nullable',
            'allowance_communication_amount' => 'nullable', 'allowance_communication_words' => 'nullable', 'allowance_other_amount' => 'nullable', 'allowance_other_words' => 'nullable',
            'allowance_other_desc' => 'nullable', 'other_benefits_desc' => 'nullable', 'pb_effective_end' => 'nullable', 'pb_compensation_amount' => 'nullable', 'pb_compensation_amount_words' => 'nullable',
            'submit_action' => 'required', 'source_contract_id' => 'nullable', 'requires_draw_signature' => 'nullable', 'requires_camera' => 'nullable', 'requires_geolocation' => 'nullable'
        ]);
        DB::beginTransaction();
        try {
            $contract->fill($v);
            $contract->unit_id = $v['new_unit_id'] ?: $v['unit_id'];
            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');
            if ($v['source_contract_id'] && Contract::find($v['source_contract_id'])) {
                $contract->parent_contract_id = $v['source_contract_id'];
            }
            $meta = $this->collectMeta($v);
            $cand = $this->resolveCandidate($contract);
            $meta['person_name'] = $cand['name'];
            if ($v['new_unit_id']) {
                $meta['new_unit_name'] = Unit::find($v['new_unit_id'])?->name;
                $meta['new_unit_id'] = (int)$v['new_unit_id'];
            }
            $contract->remuneration_json = $meta;
            if ($v['submit_action'] === 'submit') {
                $contract->status = 'review';
                if (!$contract->contract_no) {
                    $contract->contract_no = $this->generateContractNumber($contract);
                }
                $this->createApproval($contract, $request->user());
                $this->generatePdfFile($contract);
            }
            $contract->save();
            DB::commit();
            return back()->with('success', 'Update berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya dokumen status Draft yang dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            if ($contract->document_id) {
                $doc = Document::find($contract->document_id);
                if ($doc) {
                    if (Storage::disk('local')->exists($doc->path)) {
                        Storage::disk('local')->delete($doc->path);
                    }
                    $doc->delete();
                }
            }

            Approval::where('approvable_id', $contract->id)->where('approvable_type', 'contract')->delete();
            
            $contract->delete();
            DB::commit();
            return back()->with('success', 'Dokumen berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    public function approve(Request $request, Contract $contract)
    {
        return $this->processSignAction($request, $contract, 'approved', 'Kepala Unit');
    }
    public function sign(Request $request, Contract $contract)
    {
        return $this->processSignAction($request, $contract, 'signed', 'Kandidat');
    }

    private function processSignAction($request, $contract, $status, $role)
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        $needsDraw = ($contract->requires_draw_signature == 1 || $contract->requires_draw_signature === true);
        $data = $request->validate([
            'note' => 'nullable',
            'signature_image' => ($needsDraw ? 'required' : 'nullable'),
            'geo_lat' => 'nullable|string',
            'geo_lng' => 'nullable|string',
            'snapshot_image' => 'nullable|string',
        ]);
        DB::beginTransaction();
        try {
            if ($role === 'Kepala Unit') {
                Approval::where('approvable_id', $contract->id)->where('status', 'pending')->update([
                    'status' => 'approved', 'approver_person_id' => $request->user()->person_id, 'approver_user_id' => $request->user()->id, 'decided_at' => now(), 'note' => $data['note'] ?? null
                ]);
                if (!$contract->contract_no) {
                    $contract->contract_no = $this->generateContractNumber($contract);
                }
            } else {
                Approval::where('approvable_id', $contract->id)->where('status', 'approved')->update(['status' => 'completed', 'note' => $data['note'] ?? 'Signed']);
            }
            $this->ensureDocumentRecord($contract);
            Signature::create([
                'document_id' => $contract->document_id, 'signer_person_id' => $request->user()->person_id, 'signer_user_id' => $request->user()->id,
                'signer_role' => $role, 'signed_at' => now(),
                'signature_draw_data' => $data['signature_image'] ?? null,
                'geo_lat' => $data['geo_lat'] ?? null,
                'geo_lng' => $data['geo_lng'] ?? null,
                'snapshot_data' => $data['snapshot_image'] ?? null,
                'ip_address' => $request->ip()
            ]);
            $contract->update(['status' => $status]);
            $contract->loadMissing('document');
            if ($contract->document && Storage::disk('local')->exists($contract->document->path)) {
                Storage::disk('local')->delete($contract->document->path);
            }
            $this->generatePdfFile($contract);
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Berhasil ditandatangani.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, Contract $contract)
    {
        $contract->update(['status' => 'draft']);
        Approval::where('approvable_id', $contract->id)->where('status', 'pending')->update(['status' => 'rejected', 'note' => $request->note ?? $request->rejection_note, 'decided_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function submit(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return response()->json(['success' => false], 422);
        }
        $contract->update(['status' => 'review', 'contract_no' => $contract->contract_no ?: $this->generateContractNumber($contract)]);
        $this->createApproval($contract, $request->user());
        return response()->json(['success' => true]);
    }

    public function document(Request $request, Contract $contract)
    {
        set_time_limit(300);
        $this->ensureDocumentRecord($contract);
        $contract->refresh();
        $contract->loadMissing('document');
        if (!Storage::disk('local')->exists($contract->document->path)) {
            $this->generatePdfFile($contract);
            $contract->refresh();
            $contract->loadMissing('document');
        }
        $filename = basename($contract->document->path);
        return Storage::disk('local')->response($contract->document->path, $filename, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function show(Contract $contract)
    {
        $contract->load(['unit', 'document', 'person', 'applicant']);
        $meta = $contract->remuneration_json ?? [];
        $cand = $this->resolveCandidate($contract);
        $docUrl = ($contract->document_id || in_array($contract->status, ['approved', 'signed'])) ? route('recruitment.contracts.document', $contract) : null;
        $typeCfg = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];
        
        $kaUnitStatus = 'Waiting';
        $candStatus = 'Waiting';

        if ($contract->status === 'approved') {
            $kaUnitStatus = 'Approved';
            $candStatus = 'Pending';
        } elseif ($contract->status === 'signed') {
            $kaUnitStatus = 'Approved';
            $candStatus = 'Signed';
        } elseif ($contract->status === 'rejected') {
            $kaUnitStatus = 'Rejected';
            $candStatus = '-';
        }
        
        $me = auth()->user();
        $canSign = $me->can('contract.sign') && $contract->status === 'approved';
        if ($canSign && $me->hasRole('Karyawan') && $contract->employee_id !== $me->employee_id) {
            $canSign = false;
        }

        $targetRole = (in_array($contract->contract_type, ['PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN'])) ? 'Pegawai' : 'Kandidat';

        $geoData = $this->getGeoData($contract, $me);

        return response()->json(['success' => true, 'data' => array_merge($contract->toArray(), [
            'contract_type_label' => $typeCfg['label'] ?? $contract->contract_type, 'person_name' => $cand['name'],
            'start_date' => $contract->start_date?->format('d M Y'), 'end_date' => $contract->end_date?->format('d M Y'),
            'remuneration_json' => $meta, 'can_approve' => auth()->user()->can('contract.approve') && $contract->status === 'review',
            'can_sign' => $canSign,
            'doc_url' => $docUrl, 'approve_url' => route('recruitment.contracts.approve', $contract), 'sign_url' => route('recruitment.contracts.sign', $contract),
            'reject_url' => route('recruitment.contracts.reject', $contract),
            'candidate_nik' => $cand['nik'] ?? '-',
            'candidate_nik_real' => $cand['nik_real'] ?? '-',
            'target_role_label' => $targetRole,
            'geolocation' => $geoData,
            'progress' => [
                'ka_unit' => $kaUnitStatus,
                'candidate' => $candStatus
            ]
        ])]);
    }

    protected function getGeoData(Contract $contract, User $user)
    {
        $signatures = Signature::where('document_id', $contract->document_id)->get();
        $headSig = $signatures->where('signer_role', 'Kepala Unit')->last();
        $candSig = $signatures->whereIn('signer_role', ['Kandidat', 'Pegawai'])->last();

        $geo = ['head' => null, 'candidate' => null];
        
        $isPowerUser = $user->hasRole(['Superadmin', 'SDM Unit', 'DHC']);
        
        if ($headSig && ($isPowerUser || $headSig->signer_person_id == $user->person_id)) {
             $geo['head'] = [
                 'lat' => $headSig->geo_lat, 
                 'lng' => $headSig->geo_lng, 
                 'ts' => $headSig->signed_at->format('d M H:i'),
                 'name' => 'Kepala Unit'
            ];
        }

        if ($candSig && ($isPowerUser || $candSig->signer_person_id == $user->person_id)) {
             $geo['candidate'] = [
                 'lat' => $candSig->geo_lat, 
                 'lng' => $candSig->geo_lng, 
                 'ts' => $candSig->signed_at->format('d M H:i'),
                 'name' => 'Kandidat/Pegawai'
            ];
        }

        return $geo;
    }

    protected function resolveCandidate(Contract $c)
    {
        $p = $c->person ?? ($c->person_id ? Person::find($c->person_id) : ($c->applicant ?? ($c->applicant_id ? Applicant::find($c->applicant_id) : null)));
        $d = ['name' => 'Kandidat', 'address' => '-', 'nik' => '-', 'nik_real' => '-', 'pob' => '-', 'dob' => '-', 'gender' => '-'];
        if ($p) {
            $d['name'] = $p->full_name ?? $p->name ?? 'Kandidat';
            $d['address'] = $p->address ?? $p->domicile_address ?? '-';
            $realNik = DB::table('identities')->where('person_id', $p->id)->whereIn('system', ['nik', 'ktp', 'e_ktp'])->value('external_id');
            $d['nik'] = $c->employee_id ?? '-'; 
            $d['nik_real'] = $realNik ?? $p->nik_hash ?? $p->nik ?? '-'; 
            $d['pob'] = $p->place_of_birth ?? $p->pob ?? '-';
            $d['dob'] = $p->date_of_birth ? Carbon::parse($p->date_of_birth)->translatedFormat('d F Y') : '-';
            $g = strtoupper($p->gender ?? '');
            if ($g === 'L' || $g === 'MALE' || $g === 'PRIA') $d['gender'] = 'Laki-laki';
            elseif ($g === 'P' || $g === 'FEMALE' || $g === 'WANITA') $d['gender'] = 'Perempuan';
        }
        return $d;
    }

    protected function resolveSigner(Contract $c, $meta)
    {
        if (in_array($c->status, ['approved', 'signed'])) {
            $ap = Approval::where('approvable_id', $c->id)->whereIn('status', ['approved', 'completed'])->orderByDesc('decided_at')->first();
            if ($ap && $ap->approver_person_id) {
                $p = Person::find($ap->approver_person_id);
                if ($p) {
                    $u = User::where('person_id', $p->id)->first();
                    $title = $u ? $this->getUserJobTitle($u) : 'Kepala Unit';
                    return ['name' => $p->full_name, 'position' => $title];
                }
            }
        }
        $uId = (int)($meta['new_unit_id'] ?? $c->unit_id);
        $unit = $uId ? Unit::find($uId) : null;
        $headUnit = $this->resolveHeadUnit($unit);
        $head = $this->getUnitHeadUser($headUnit);
        if ($head && $head->person && ($head->person->full_name ?? null)) {
            return ['name' => $head->person->full_name, 'position' => $this->getUserJobTitle($head)];
        }
        return ['name' => 'Nama Kepala Unit', 'position' => 'Kepala Unit'];
    }

    protected function getUserJobTitle(User $u)
    {
        if ($u->employee_id) {
            $emp = Employee::where('employee_id', $u->employee_id)->first();
            if ($emp && $emp->latest_jobs_title) return $emp->latest_jobs_title;
            if ($emp && $emp->position_id) {
                $pos = DB::table('positions')->where('id', $emp->position_id)->value('name');
                if ($pos) return $pos;
            }
        }
        return 'Kepala Unit';
    }

    protected function generateContractNumber(Contract $c): string
    {
        $c->loadMissing('unit');
        $code = config("recruitment.numbering.doc_codes.{$c->contract_type}") ?? 'PERJ';
        $uCode = $c->unit?->code ?? 'UNIT';
        $romanMonth = $this->getRomanMonth(now()->month);
        $year = now()->year;
        
        $defaultHead = (string)(config('recruitment.numbering.default_head_code') ?? 'XX');
        $headCode = $defaultHead;
        
        $headUnit = $this->resolveHeadUnit($c->unit);
        $head = $this->getUnitHeadUser($headUnit);
        
        if ($head && $head->person && ($head->person->full_name ?? null)) {
            $ini = $this->initialsFromName($head->person->full_name);
            if ($ini) $headCode = $ini;
        }

        $base = "/{$uCode}-{$romanMonth}/{$headCode}/{$year}";
        
        $last = DB::table('contracts')
            ->where('contract_no', 'like', "%{$base}")
            ->orderByRaw('LENGTH(contract_no) DESC')
            ->orderBy('contract_no', 'desc')
            ->value('contract_no');

        $seq = 1;
        if ($last) {
            $parts = explode('/', $last);
            $nums = explode('-', $parts[0] ?? '');
            $seq = ((int)end($nums)) + 1;
        }

        do {
            $candidate = sprintf("%s-%03d%s", $code, $seq, $base);
            $exists = DB::table('contracts')->where('contract_no', $candidate)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $candidate;
    }

    protected function initialsFromName($name)
    {
        $n = trim(preg_replace('/\s+/', ' ', (string)$name));
        if ($n === '') return null;
        $parts = explode(' ', $n);
        $first = $parts[0] ?? '';
        $last = end($parts) ?: '';
        $a = mb_strtoupper(mb_substr($first, 0, 1));
        $b = mb_strtoupper(mb_substr($last, 0, 1));
        $ini = trim($a . $b);
        return $ini !== '' ? $ini : null;
    }

    protected function resolveHeadUnit(?Unit $unit)
    {
        if (!$unit) return null;
        $rules = config('recruitment.numbering.head_unit_rules', []);
        $cat = strtoupper((string)($unit->category ?? ''));
        $rule = $rules[$cat] ?? ['mode' => 'self_unit'];
        $mode = strtolower((string)($rule['mode'] ?? 'self_unit'));
        if ($mode === 'fixed_unit') {
            $m = $rule['unit_match'] ?? [];
            $q = Unit::query();
            if (!empty($m['code'])) $q->where('code', $m['code']);
            if (!empty($m['name_contains'])) $q->where('name', 'like', '%' . $m['name_contains'] . '%');
            $t = $q->first();
            return $t ?: $unit;
        }
        return $unit;
    }

    protected function generatePdfFile($contract)
    {
        $contract->loadMissing(['unit', 'document']);
        $doc = config("recruitment.contract_types.{$contract->contract_type}.document_type");
        if (!$doc) throw new \RuntimeException("document_type missing: {$contract->contract_type}");
        $template = ContractTemplate::where('code', $doc)->firstOrFail();
        $vars = $this->getTemplateVars($contract);
        $html = $this->renderPdfHtml($contract, $template, $vars);
        $cfg = (array)(config('recruitment.pdf', []) ?? []);
        $page = (array)($cfg['page'] ?? []);
        $paper = (string)($page['paper'] ?? 'a4');
        $orientation = (string)($page['orientation'] ?? 'portrait');
        $dompdf = (array)($cfg['dompdf'] ?? []);
        $dpi = (int)($dompdf['dpi'] ?? 96);
        $pdf = Pdf::loadHTML($html)->setPaper($paper, $orientation);
        $dom = $pdf->getDomPDF();
        $dom->set_option('dpi', $dpi);
        $dom->set_option('isRemoteEnabled', (bool)($dompdf['isRemoteEnabled'] ?? true));
        $dom->set_option('isHtml5ParserEnabled', (bool)($dompdf['isHtml5ParserEnabled'] ?? true));
        $out = $pdf->output();
        Storage::disk('local')->put($contract->document->path, $out);
        $contract->document->update(['size_bytes' => strlen($out)]);
    }

    protected function renderPdfHtml($contract, $template = null, $vars = [])
    {
        if (!$template) {
            $doc = config("recruitment.contract_types.{$contract->contract_type}.document_type");
            $template = ContractTemplate::where('code', $doc)->firstOrFail();
        }
        if (empty($vars)) $vars = $this->getTemplateVars($contract);
        $body = (string)($template->body ?? '');
        foreach ($vars as $k => $v) {
            $body = str_replace("{{{$k}}}", (string)$v, $body);
        }
        $body = preg_replace('~<!doctype[^>]*>~i', '', $body);
        $body = preg_replace('~</?(html|head|body)[^>]*>~i', '', $body);
        $body = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $body);

        $cfg = (array)(config('recruitment.pdf', []) ?? []);
        $tplKey = (string)($template->code ?? '');
        $m = (array)(data_get($cfg, "templates.{$tplKey}.margin_cm") ?? data_get($cfg, 'margin_cm') ?? []);

        $m['top'] = 3.5;
        $m['bottom'] = 3.5;

        $mt = (float)($m['top']);
        $mr = (float)($m['right'] ?? 2.54);
        $mb = (float)($m['bottom']);
        $ml = (float)($m['left'] ?? 2.54);

        $page = (array)($cfg['page'] ?? []);
        $pw = (float)($page['width_cm'] ?? 21);
        $ph = (float)($page['height_cm'] ?? 29.7);

        $font = (array)(data_get($cfg, "templates.{$tplKey}.font") ?? data_get($cfg, 'font') ?? []);
        $ff = (string)($font['family'] ?? 'Tahoma');
        $fs = (float)($font['size_pt'] ?? 11);
        $lh = (float)($font['line_height'] ?? 1.3); 
        $titleSize = (float)($font['title_size_pt'] ?? 14);
        $pa = (float)($font['paragraph_after_pt'] ?? 6);

        $disk = (string)($cfg['letterhead_disk'] ?? 'public');
        $path = (string)($cfg['letterhead_path'] ?? '');
        $lhImg = $this->pdfDataUri($disk, $path);

        $pathRegular = storage_path((string)($font['regular_file'] ?? 'app/fonts/tahoma.ttf'));
        $pathBold = storage_path((string)($font['bold_file'] ?? 'app/fonts/tahomabd.ttf'));
        $fontFaceCss = '';
        $finalFamily = 'sans-serif';
        if (file_exists($pathRegular)) {
            $finalFamily = 'Tahoma';
            $fr64 = base64_encode(file_get_contents($pathRegular));
            $fb64 = file_exists($pathBold) ? base64_encode(file_get_contents($pathBold)) : $fr64;
            $fontFaceCss = "@font-face{font-family:'Tahoma';font-style:normal;font-weight:400;src:url(data:font/truetype;base64,{$fr64}) format('truetype');}@font-face{font-family:'Tahoma';font-style:normal;font-weight:700;src:url(data:font/truetype;base64,{$fb64}) format('truetype');}";
        }

        $tplCss = (string)($template->css ?? '');
        $tplCss = preg_replace('~@page\s*[^{]*\{.*?\}~is', '', $tplCss);
        $tplCss = preg_replace('~\b(html|body)\b\s*\{.*?\}~is', '', $tplCss);

        $css = "@page{margin:{$mt}cm {$mr}cm {$mb}cm {$ml}cm;}{$fontFaceCss}
        body{margin:0;padding:0;font-family:'{$finalFamily}',{$ff},sans-serif;font-size:{$fs}pt;line-height:{$lh};color:#000;background-color:rgba(255,255,255,0.88);}
        .letterhead-img{position:fixed;top:-{$mt}cm;left:-{$ml}cm;width:{$pw}cm;height:{$ph}cm;z-index:-9999;opacity:1.0;}
        .content{margin:0!important;padding:0!important;}
        p{margin:0 0 {$pa}pt 0;text-align:justify;text-justify:inter-word;}
        .justify{text-align:justify;text-justify:inter-word;}
        strong,b{font-weight:700;}
        table{width:100%;border-collapse:collapse;}
        table.info{width:100%;border-collapse:collapse;margin:0 0 {$pa}pt 0;}
        table.info td{vertical-align:top;padding:2px 0;}
        ol{margin:0 0 {$pa}pt 0;padding-left:30px;}
        ol li{text-align:justify;padding-left:5px;margin-bottom:5px;}
        ol ol {list-style-type: lower-alpha; padding-left:25px;}
        ul{margin:0 0 {$pa}pt 0;padding-left:30px;}
        ul li{text-align:justify;padding-left:5px;margin-bottom:5px;}
        .title{text-align:center;font-weight:700;text-transform:uppercase;font-size:{$titleSize}pt;margin:0 0 5pt 0;text-decoration:underline;}
        .subtitle{text-align:center;font-weight:700;font-size:{$fs}pt;margin:0 0 12pt 0;}
        .pasal-title{text-align:center!important;font-weight:700!important;text-transform:uppercase;line-height:{$lh};margin:14pt 0 6pt 0;font-size:{$fs}pt;page-break-after:avoid;}
        table.ttd{width:100%;margin-top:24pt;page-break-inside:avoid;table-layout:fixed;}
        table.ttd td{text-align:center;vertical-align:top;}
        .sig-box{height:70px;}
        {$tplCss}";

        $bg = $lhImg ? ("<img class='letterhead-img' src='{$lhImg}'>") : '';
        return "<html><head><meta charset='utf-8'><style>{$css}</style></head><body>{$bg}{$body}</body></html>";
    }

    protected function pdfDataUri($disk, $path)
    {
        if (!$path || !Storage::disk($disk)->exists($path)) return null;
        $bin = Storage::disk($disk)->get($path);
        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/jpeg';
        return "data:{$mime};base64," . base64_encode($bin);
    }

    protected function getTemplateVars(Contract $c)
    {
        Carbon::setLocale('id');
        $meta = $c->remuneration_json ?? [];
        $signer = $this->resolveSigner($c, $meta);
        $cand = $this->resolveCandidate($c);
        $fmt = fn($n) => 'Rp ' . number_format((float)preg_replace('/\D/', '', $n ?? 0), 0, ',', '.');
        $sigs = Signature::where('document_id', $c->document_id)->get();
        $signerImg = $sigs->where('signer_role', 'Kepala Unit')->last()?->signature_draw_data;
        $candImg = $sigs->where('signer_role', 'Kandidat')->last()?->signature_draw_data;
        $signerTag = $signerImg ? "<img src='{$signerImg}' style='height:70px;'>" : "<div style='height:70px'></div>";
        $candTag = $candImg ? "<img src='{$candImg}' style='height:70px;'>" : "<div style='height:70px'></div>";
        $duration = '-';
        if ($c->start_date && $c->end_date) {
            $diff = $c->start_date->diff($c->end_date);
            $duration = ($diff->y ? $diff->y . " Tahun " : "") . ($diff->m ? $diff->m . " Bulan" : "");
            if (!$diff->y && !$diff->m) $duration = $diff->days . " Hari";
        }
        return [
            'contract_no' => $c->contract_no ?? 'DRAFT',
            'today_date' => now()->translatedFormat('d F Y'),
            'day_name' => now()->translatedFormat('l'),
            'day_number' => ucwords($this->terbilang(now()->day)),
            'month_name' => now()->translatedFormat('F'),
            'year_name' => ucwords($this->terbilang(now()->year)),
            'signer_name' => $signer['name'],
            'signer_position' => $signer['position'],
            'signer_signature' => $signerTag,
            'candidate_name' => $cand['name'],
            'candidate_address' => $cand['address'],
            'candidate_nik' => $cand['nik'],
            'pob' => $cand['pob'],
            'dob' => $cand['dob'],
            'gender' => $cand['gender'],
            'candidate_signature' => $candTag,
            'position_name' => $c->position_name,
            'unit_name' => $meta['new_unit_name'] ?? ($c->unit->name ?? '-'),
            'employment_type' => $c->employment_type,
            'duration' => $duration,
            'start_date' => $c->start_date?->translatedFormat('d F Y'),
            'end_date' => $c->end_date?->translatedFormat('d F Y'),
            'salary' => $fmt($meta['salary_amount'] ?? 0),
            'salary_words' => ucwords($meta['salary_amount_words'] ?? ''),
            'meal_allowance' => $fmt($meta['lunch_allowance_daily'] ?? 0),
            'allowance_list' => $this->formatAllowances($meta, $fmt),
            'other_benefits' => $meta['other_benefits_desc'] ?? '-',
            'pb_date' => isset($meta['pb_effective_end']) ? Carbon::parse($meta['pb_effective_end'])->translatedFormat('d F Y') : '-',
            'pb_amount' => $fmt($meta['pb_compensation_amount'] ?? 0),
            'pb_words' => ucwords($meta['pb_compensation_amount_words'] ?? '')
        ];
    }

    private function ensureDocumentRecord($c)
    {
        if (!$c->document_id) {
            $path = "contracts/{$c->contract_type}-{$c->id}-" . time() . ".pdf";
            $doc = Document::create(['doc_type' => $c->contract_type, 'storage_disk' => 'local', 'path' => $path, 'mime' => 'application/pdf', 'size_bytes' => 0]);
            $c->update(['document_id' => $doc->id]);
            $c->refresh();
        }
        if (!Storage::disk('local')->exists('contracts')) {
            Storage::disk('local')->makeDirectory('contracts');
        }
    }

    private function formatAllowances($m, $fmt)
    {
        $list = [];
        if (($v = $m['allowance_position_amount'] ?? 0)) $list[] = "T.Jabatan " . $fmt($v);
        if (($v = $m['allowance_communication_amount'] ?? 0)) $list[] = "T.Komunikasi " . $fmt($v);
        if (($v = $m['allowance_special_amount'] ?? 0)) $list[] = "T.Khusus " . $fmt($v);
        if (($v = $m['allowance_other_amount'] ?? 0)) $list[] = "Lainnya " . $fmt($v);
        return implode(', ', $list) ?: '-';
    }

    private function collectMeta($v)
    {
        return collect($v)->except(['contract_type', 'unit_id', '_token'])->toArray();
    }

    private function createApproval($c, $u)
    {
        if (!Approval::where('approvable_id', $c->id)->where('status', 'pending')->exists()) {
            Approval::create(['approvable_type' => 'contract', 'approvable_id' => $c->id, 'requester_person_id' => $u->person_id, 'requester_user_id' => $u->id, 'status' => 'pending', 'note' => 'Review Kepala Unit']);
        }
    }

    private function terbilang($x)
    {
        $a = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        if ($x < 12) return $a[$x];
        if ($x < 20) return $this->terbilang($x - 10) . " belas";
        if ($x < 100) return $this->terbilang($x / 10) . " puluh " . $this->terbilang($x % 10);
        if ($x < 200) return "seratus " . $this->terbilang($x - 100);
        if ($x < 1000) return $this->terbilang($x / 100) . " ratus " . $this->terbilang($x % 100);
        if ($x < 2000) return "seribu " . $this->terbilang($x - 1000);
        if ($x < 1000000) return $this->terbilang($x / 1000) . " ribu " . $this->terbilang($x % 1000);
        return $this->terbilang($x / 1000000) . " juta " . $this->terbilang($x % 1000000);
    }

    public function terbilangApi(Request $request)
    {
        return response()->json(['success' => true, 'data' => ['words' => strtoupper($this->terbilang((int)$request->amount) . ' RUPIAH')]]);
    }
    private function getRomanMonth($m)
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$m] ?? '';
    }

    protected function getUnitHeadUser(?Unit $unit)
    {
        if (!$unit) return null;
        $roleNames = (array)config('recruitment.numbering.head_role_names', []);
        $teamKey = (string)(config('permission.team_foreign_key') ?? 'unit_id');
        $hasTeamCol = false;
        try {
            $hasTeamCol = DB::getSchemaBuilder()->hasColumn('model_has_roles', $teamKey);
        } catch (\Exception $e) {
            $hasTeamCol = false;
        }
        $ids = null;
        try {
            $q = DB::table('model_has_roles as mhr')->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_type', User::class)->whereIn('r.name', $roleNames);
            if ($hasTeamCol) $q->where("mhr.{$teamKey}", $unit->id);
            $ids = $q->pluck('mhr.model_id')->unique()->values()->all();
        } catch (\Exception $e) {
            $ids = null;
        }
        $uq = User::where('unit_id', $unit->id);
        if (is_array($ids)) $uq->whereIn('id', $ids);
        else $uq->whereHas('roles', fn($q) => $q->whereIn('name', $roleNames));
        return $uq->with('person', 'roles')->first();
    }
}