<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Applicant;
use App\Models\Unit;
use App\Models\Document;
use App\Models\Signature;
use App\Models\Approval;
use App\Models\User;
use App\Models\Employee;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('Superadmin');
        $isDhc = $user->hasRole('DHC');
        $canSeeAll = $isSuperadmin || $isDhc;
        $userUnitId = (int) ($user->unit_id ?? 0);
        
        $isApproverOnly = $user->can('contract.approve') && !$user->can('contract.update');
        $selectedUnitId = $canSeeAll ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null) : $userUnitId;

        $query = Contract::with(['unit', 'employee', 'document', 'person', 'applicant'])->orderByDesc('created_at');

        // Filter Unit
        if ($selectedUnitId) {
            $query->where('unit_id', $selectedUnitId);
        } elseif ($isDhc && !$isSuperadmin) {
            $query->whereHas('unit', function ($q) {
                $q->where('category', 'ENABLER');
            });
        } elseif ($isSuperadmin) {
            $query->whereHas('unit', function ($q) {
                $q->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
            });
        }

        // Filter Status & Search
        if ($isApproverOnly) {
            $query->where('status', '!=', 'draft');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($searchTerm = $request->input('q')) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contract_no', 'like', "%{$searchTerm}%")
                  ->orWhere('employment_type', 'like', "%{$searchTerm}%")
                  ->orWhereHas('person', function ($p) use ($searchTerm) {
                      $p->where('full_name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('unit', function ($u) use ($searchTerm) {
                      $u->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $contracts = $query->paginate(25)->withQueryString();

        // Data for Filters
        $unitsQuery = Unit::query();
        if ($isSuperadmin) {
            $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        } elseif ($isDhc) {
            $unitsQuery->where('category', 'ENABLER');
        }
        $units = $canSeeAll ? $unitsQuery->orderBy('name')->get(['id', 'name']) : Unit::where('id', $selectedUnitId)->get(['id', 'name']);

        // Expiring Contracts Logic
        $expiringQuery = DB::table('portfolio_histories AS ph')
            ->leftJoin('employees AS e', 'e.person_id', '=', 'ph.person_id')
            ->leftJoin('units AS u', 'u.id', '=', 'e.unit_id')
            ->select('ph.id', 'ph.person_id', 'e.employee_id', 'ph.title AS position_name', 'ph.start_date', 'ph.end_date', 'e.employee_status', 'e.unit_id', 'u.name AS unit_name', DB::raw("(SELECT full_name FROM persons WHERE persons.id = ph.person_id) AS person_name"))
            ->where('ph.category', 'job')
            ->whereNotNull('ph.end_date')
            ->whereDate('ph.end_date', '>=', now())
            ->whereDate('ph.end_date', '<=', now()->addDays(30));

        if (!$canSeeAll && $userUnitId) {
            $expiringQuery->where('e.unit_id', $userUnitId);
        }
        $expiringContracts = $expiringQuery->orderBy('ph.end_date', 'asc')->get();

        return view('recruitment.contracts.index', [
            'contracts' => $contracts,
            'units' => $units,
            'positions' => DB::table('positions')->select('id', 'name')->orderBy('name')->get(),
            'selectedUnitId' => $selectedUnitId,
            'statusFilter' => $request->input('status'),
            'searchFilter' => $request->input('q'),
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
        // Increase limits for initial PDF generation
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $validatedData = $request->validate([
            'contract_type' => 'required|string',
            'unit_id' => 'required|integer',
            'new_unit_id' => 'nullable|integer',
            'applicant_id' => 'nullable|string',
            'employee_id' => 'nullable|string',
            'person_id' => 'nullable|string',
            'position_name' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'salary_amount' => 'nullable|string',
            'salary_amount_words' => 'nullable|string',
            'lunch_allowance_daily' => 'nullable|string',
            'lunch_allowance_words' => 'nullable|string',
            'allowance_special_amount' => 'nullable|string',
            'allowance_special_words' => 'nullable|string',
            'allowance_position_amount' => 'nullable|string',
            'allowance_position_words' => 'nullable|string',
            'allowance_communication_amount' => 'nullable|string',
            'allowance_communication_words' => 'nullable|string',
            'allowance_other_amount' => 'nullable|string',
            'allowance_other_words' => 'nullable|string',
            'allowance_other_desc' => 'nullable|string',
            'other_benefits_desc' => 'nullable|string',
            'pb_effective_end' => 'nullable|date',
            'pb_compensation_amount' => 'nullable|string',
            'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit',
            'source_contract_id' => 'nullable|integer',
            'requires_draw_signature' => 'nullable',
            'requires_camera' => 'nullable',
            'requires_geolocation' => 'nullable',
        ]);

        if (!$request->user()->hasRole(['Superadmin', 'DHC']) && (int)$validatedData['unit_id'] !== (int)$request->user()->unit_id) {
            return back()->withErrors(['unit_id' => 'Unit tidak valid.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = new Contract();
            $contract->fill($validatedData);
            $contract->contract_no = null;
            $contract->unit_id = $validatedData['new_unit_id'] ?: $validatedData['unit_id'];

            // Resolve Subject (Person/Applicant)
            if (in_array($validatedData['contract_type'], ['SPK', 'PKWT_BARU']) && $validatedData['applicant_id']) {
                $applicant = Applicant::find($validatedData['applicant_id']);
                $contract->applicant_id = $applicant->id;
                $contract->person_id = $applicant->person_id;
                if (empty($validatedData['position_name'])) {
                    $contract->position_name = $applicant->position_applied;
                }
            } else {
                $contract->person_id = $validatedData['person_id'] ?: Employee::where('employee_id', $validatedData['employee_id'])->value('person_id');
                if ($validatedData['source_contract_id'] && Contract::find($validatedData['source_contract_id'])) {
                    $contract->parent_contract_id = $validatedData['source_contract_id'];
                }
            }
            if (!$contract->person_id) {
                $contract->person_id = $request->input('person_id');
            }

            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');

            $meta = $this->collectMeta($validatedData);
            $candidate = $this->resolveCandidate($contract);
            $meta['person_name'] = $candidate['name'];

            if ($validatedData['new_unit_id']) {
                $meta['new_unit_name'] = Unit::find($validatedData['new_unit_id'])?->name;
            }
            $contract->remuneration_json = $meta;

            $contract->status = ($validatedData['submit_action'] === 'submit') ? 'review' : 'draft';
            if ($contract->status === 'review') {
                $contract->contract_no = $this->generateContractNumber($contract);
            }

            $contract->created_by_user_id = $request->user()->id;
            $contract->created_by_person_id = $request->user()->person_id;
            $contract->save();

            // Init Document Record (Without File)
            $this->ensureDocumentRecord($contract);

            if ($contract->status === 'review') {
                $this->createApproval($contract, $request->user());
                // Generate PDF immediately for review
                $this->generatePdfFile($contract);
            }

            DB::commit();
            return redirect()->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])->with('success', 'Berhasil disimpan.');
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
        $validatedData = $request->validate([
            'contract_type' => 'required',
            'unit_id' => 'required',
            'new_unit_id' => 'nullable',
            'applicant_id' => 'nullable',
            'employee_id' => 'nullable',
            'person_id' => 'nullable',
            'position_name' => 'nullable',
            'employment_type' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'remarks' => 'nullable',
            'salary_amount' => 'nullable',
            'salary_amount_words' => 'nullable',
            'lunch_allowance_daily' => 'nullable',
            'lunch_allowance_words' => 'nullable',
            'allowance_special_amount' => 'nullable',
            'allowance_special_words' => 'nullable',
            'allowance_position_amount' => 'nullable',
            'allowance_position_words' => 'nullable',
            'allowance_communication_amount' => 'nullable',
            'allowance_communication_words' => 'nullable',
            'allowance_other_amount' => 'nullable',
            'allowance_other_words' => 'nullable',
            'allowance_other_desc' => 'nullable',
            'other_benefits_desc' => 'nullable',
            'pb_effective_end' => 'nullable',
            'pb_compensation_amount' => 'nullable',
            'pb_compensation_amount_words' => 'nullable',
            'submit_action' => 'required',
            'source_contract_id' => 'nullable',
            'requires_draw_signature' => 'nullable',
            'requires_camera' => 'nullable',
            'requires_geolocation' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            $contract->fill($validatedData);
            $contract->unit_id = $validatedData['new_unit_id'] ?: $validatedData['unit_id'];
            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');
            
            if ($validatedData['source_contract_id'] && Contract::find($validatedData['source_contract_id'])) {
                $contract->parent_contract_id = $validatedData['source_contract_id'];
            }

            $meta = $this->collectMeta($validatedData);
            $candidate = $this->resolveCandidate($contract);
            $meta['person_name'] = $candidate['name'];

            if ($validatedData['new_unit_id']) {
                $meta['new_unit_name'] = Unit::find($validatedData['new_unit_id'])?->name;
            }
            $contract->remuneration_json = $meta;

            if ($validatedData['submit_action'] === 'submit') {
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
        // Optimasi: Tambah waktu eksekusi & limit memori untuk proses berat
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $needsDraw = ($contract->requires_draw_signature == 1 || $contract->requires_draw_signature === true);
        $data = $request->validate([
            'note' => 'nullable',
            'signature_image' => ($needsDraw ? 'required' : 'nullable')
        ]);

        DB::beginTransaction();
        try {
            if ($role === 'Kepala Unit') {
                Approval::where('approvable_id', $contract->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'approved',
                        'approver_person_id' => $request->user()->person_id,
                        'approver_user_id' => $request->user()->id,
                        'decided_at' => now(),
                        'note' => $data['note'] ?? null
                    ]);
                
                if (!$contract->contract_no) {
                    $contract->contract_no = $this->generateContractNumber($contract);
                }
            } else {
                Approval::where('approvable_id', $contract->id)
                    ->where('status', 'approved')
                    ->update(['status' => 'completed', 'note' => $data['note'] ?? 'Signed']);
            }

            // Pastikan record dokumen ada
            $this->ensureDocumentRecord($contract);

            // Simpan TTD
            Signature::create([
                'document_id' => $contract->document_id,
                'signer_person_id' => $request->user()->person_id,
                'signer_user_id' => $request->user()->id,
                'signer_role' => $role,
                'signature_draw_data' => $data['signature_image'] ?? null,
                'signed_at' => now()
            ]);

            // Update Status Kontrak
            $contract->update(['status' => $status]);

            // GENERATE PDF HANYA SEKALI DI SINI
            // Hapus file lama jika ada untuk memaksa generate ulang dengan TTD baru
            if (Storage::disk('local')->exists($contract->document->path)) {
                Storage::disk('local')->delete($contract->document->path);
            }
            
            $this->generatePdfFile($contract);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, Contract $contract)
    {
        $contract->update(['status' => 'draft']);
        Approval::where('approvable_id', $contract->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'note' => $request->note ?? $request->rejection_note,
                'decided_at' => now()
            ]);
        return response()->json(['success' => true]);
    }

    public function submit(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return response()->json(['success' => false], 422);
        }
        $contract->update([
            'status' => 'review',
            'contract_no' => $contract->contract_no ?: $this->generateContractNumber($contract)
        ]);
        $this->createApproval($contract, $request->user());
        return response()->json(['success' => true]);
    }

    public function document(Request $request, Contract $contract)
    {
        set_time_limit(300);
        $this->ensureDocumentRecord($contract);
        $contract->refresh();

        // Safety check: jika file fisik hilang, generate ulang
        if (!Storage::disk('local')->exists($contract->document->path)) {
            $this->generatePdfFile($contract);
            $contract->refresh();
        }

        return Storage::disk('local')->response(
            $contract->document->path,
            basename($contract->document->path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function show(Contract $contract)
    {
        $contract->load(['unit', 'document', 'person', 'applicant']);
        $meta = $contract->remuneration_json ?? [];
        $cand = $this->resolveCandidate($contract);
        $docUrl = ($contract->document_id || in_array($contract->status, ['approved', 'signed'])) ? route('recruitment.contracts.document', $contract) : null;
        
        $typeCfg = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];

        return response()->json(['success' => true, 'data' => array_merge($contract->toArray(), [
            'contract_type_label' => $typeCfg['label'] ?? $contract->contract_type,
            'person_name' => $cand['name'],
            'start_date' => $contract->start_date?->format('d M Y'),
            'end_date' => $contract->end_date?->format('d M Y'),
            'remuneration_json' => $meta,
            'can_approve' => auth()->user()->can('contract.approve') && $contract->status === 'review',
            'can_sign' => auth()->user()->can('contract.sign') && $contract->status === 'approved',
            'doc_url' => $docUrl,
            'approve_url' => route('recruitment.contracts.approve', $contract),
            'sign_url' => route('recruitment.contracts.sign', $contract),
            'reject_url' => route('recruitment.contracts.reject', $contract)
        ])]);
    }

    // --- HELPER LOGIC DATA KANDIDAT ---
    protected function resolveCandidate(Contract $c)
    {
        $p = $c->person ?? ($c->person_id ? Person::find($c->person_id) : ($c->applicant ?? ($c->applicant_id ? Applicant::find($c->applicant_id) : null)));
        
        $d = [
            'name' => 'Kandidat',
            'address' => '-',
            'nik' => '-',
            'pob' => '-',
            'dob' => '-',
            'gender' => '-',
        ];

        if ($p) {
            $d['name'] = $p->full_name ?? $p->name ?? 'Kandidat';
            $d['address'] = $p->address ?? $p->domicile_address ?? '-';
            $d['nik'] = $p->nik ?? $p->nik_e_ktp ?? $p->ktp ?? '-';
            $d['pob'] = $p->place_of_birth ?? $p->pob ?? '-';
            $d['dob'] = $p->date_of_birth ? Carbon::parse($p->date_of_birth)->translatedFormat('d F Y') : '-';
            
            $g = strtoupper($p->gender ?? '');
            if ($g == 'L' || $g == 'MALE' || $g == 'PRIA') $d['gender'] = 'Laki-laki';
            elseif ($g == 'P' || $g == 'FEMALE' || $g == 'WANITA') $d['gender'] = 'Perempuan';
        }
        return $d;
    }

    // --- HELPER LOGIC PENANDATANGAN ---
    protected function resolveSigner(Contract $c, $meta)
    {
        // 1. Cek History Approval (Paling Akurat)
        if (in_array($c->status, ['approved', 'signed'])) {
            $approval = Approval::where('approvable_id', $c->id)
                ->where('status', 'approved')
                ->orderByDesc('decided_at')
                ->first();
            
            if ($approval && $approval->approver_person_id) {
                $p = Person::find($approval->approver_person_id);
                if ($p) return ['name' => $p->full_name, 'position' => 'Kepala Unit'];
            }
        }
        
        // 2. Logic Kode Surat /DN/ => Deddi Nurmal
        if (str_contains($c->contract_no, '/DN/')) {
            return ['name' => 'Deddi Nurmal', 'position' => 'Vice President Human Capital'];
        }
        
        // 3. Logic Cari Kepala Unit
        $uId = $meta['new_unit_id'] ?? $c->unit_id;
        if ($uId) {
            $head = $this->getUnitHeadUser(Unit::find($uId));
            if ($head) return ['name' => $head->person->full_name ?? $head->name, 'position' => 'Kepala Unit'];
        }

        // 4. Default Config
        return ['name' => config('recruitment.company_rep_name'), 'position' => config('recruitment.company_rep_title')];
    }

    protected function generateContractNumber(Contract $c): string
    {
        $c->loadMissing('unit');
        $code = config("recruitment.numbering.doc_codes.{$c->contract_type}") ?? 'PERJ';
        $uCode = $c->unit?->code ?? 'UNIT';
        $romanMonth = $this->getRomanMonth(now()->month);
        $year = now()->year;
        
        $headCode = 'DN'; // Default
        
        $head = $this->getUnitHeadUser($c->unit);
        if ($head) {
             $full = strtoupper($head->person ? $head->person->full_name : $head->name);
             if (!str_contains($full, 'DEDDI')) {
                 $parts = preg_split('/\s+/', trim($full));
                 if (count($parts) > 0) {
                     $headCode = mb_strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                 }
             }
        }

        $baseFormat = "/{$uCode}-{$romanMonth}/{$headCode}/{$year}";
        $lastContract = Contract::where('contract_no', 'like', "%$baseFormat")
            ->orderByDesc('contract_no')
            ->value('contract_no');
            
        $seq = 1;
        if ($lastContract) {
            $parts = explode('/', $lastContract);
            $nums = explode('-', $parts[0] ?? '');
            $seq = intval(end($nums)) + 1;
        }
        
        return sprintf("%s-%03d%s", $code, $seq, $baseFormat);
    }

    // --- PDF GENERATION ENGINE ---
    protected function generatePdfFile($contract)
    {
        $template = ContractTemplate::where('code', config("recruitment.contract_types.{$contract->contract_type}.document_type") ?? 'PKWT')->first();
        
        if (!$template) return;

        $vars = $this->getTemplateVars($contract);
        $htmlContent = $this->renderPdfHtml($contract, $template, $vars);
        
        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'portrait');
        
        Storage::disk('local')->put($contract->document->path, $pdf->output());
        
        $contract->document->update(['size_bytes' => strlen($pdf->output())]);
    }

    protected function renderPdfHtml($contract, $template = null, $vars = [])
    {
        if (!$template) {
            $template = ContractTemplate::where('code', config("recruitment.contract_types.{$contract->contract_type}.document_type") ?? 'PKWT')->first();
        }
        if (empty($vars)) {
            $vars = $this->getTemplateVars($contract);
        }

        $html = $template->body;
        foreach ($vars as $key => $value) {
            $html = str_replace("{{{$key}}}", $value, $html);
        }

        return "<html><head><style>@page { margin: 0px; } body { margin: 0px; padding: 0px; } {$template->css}</style></head><body>{$html}</body></html>";
    }

    protected function getTemplateVars(Contract $c)
    {
        Carbon::setLocale('id');
        $meta = $c->remuneration_json ?? [];
        $signer = $this->resolveSigner($c, $meta);
        $candidate = $this->resolveCandidate($c);
        
        // Format Rupiah
        $fmt = fn($n) => 'Rp ' . number_format((float)preg_replace('/\D/','',$n??0), 0, ',', '.');
        
        // Signatures
        $signatures = Signature::where('document_id', $c->document_id)->get();
        $signerImg = $signatures->where('signer_role', 'Kepala Unit')->last()?->signature_draw_data;
        $candidateImg = $signatures->where('signer_role', 'Kandidat')->last()?->signature_draw_data;
        
        $signerTag = $signerImg ? "<img src='$signerImg' style='height:70px;'>" : '<br><br><br>';
        $candidateTag = $candidateImg ? "<img src='$candidateImg' style='height:70px;'>" : '<br><br><br>';

        // Duration Text
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
            'candidate_name' => $candidate['name'],
            'candidate_address' => $candidate['address'],
            'candidate_nik' => $candidate['nik'],
            'pob' => $candidate['pob'],
            'dob' => $candidate['dob'],
            'gender' => $candidate['gender'],
            'candidate_signature' => $candidateTag,
            'position_name' => $c->position_name,
            'unit_name' => $meta['new_unit_name'] ?? $c->unit->name ?? '-',
            'employment_type' => $c->employment_type,
            'duration' => $duration,
            'start_date' => $c->start_date?->translatedFormat('d F Y'),
            'end_date' => $c->end_date?->translatedFormat('d F Y'),
            'salary' => $fmt($meta['salary_amount']),
            'salary_words' => ucwords($meta['salary_amount_words']),
            'meal_allowance' => $fmt($meta['lunch_allowance_daily']),
            'allowance_list' => $this->formatAllowances($meta, $fmt),
            'other_benefits' => $meta['other_benefits_desc'] ?? '-',
            'pb_date' => isset($meta['pb_effective_end']) ? Carbon::parse($meta['pb_effective_end'])->translatedFormat('d F Y') : '-',
            'pb_amount' => $fmt($meta['pb_compensation_amount']),
            'pb_words' => ucwords($meta['pb_compensation_amount_words'])
        ];
    }

    private function ensureDocumentRecord($c)
    {
        if (!$c->document_id) {
            $path = "contracts/{$c->contract_type}-{$c->id}-" . time() . ".pdf";
            $doc = Document::create([
                'doc_type' => $c->contract_type,
                'storage_disk' => 'local',
                'path' => $path,
                'mime' => 'application/pdf',
                'size_bytes' => 0
            ]);
            $c->update(['document_id' => $doc->id]);
            $c->refresh();
        }
        if (!Storage::disk('local')->exists('contracts')) {
            Storage::disk('local')->makeDirectory('contracts');
        }
    }
    
    private function formatAllowances($m, $fmt) {
        $list = [];
        if ($v = $m['allowance_position_amount'] ?? 0) $list[] = "T.Jabatan " . $fmt($v);
        if ($v = $m['allowance_communication_amount'] ?? 0) $list[] = "T.Komunikasi " . $fmt($v);
        if ($v = $m['allowance_special_amount'] ?? 0) $list[] = "T.Khusus " . $fmt($v);
        if ($v = $m['allowance_other_amount'] ?? 0) $list[] = "Lainnya " . $fmt($v);
        return implode(', ', $list) ?: '-';
    }

    private function collectMeta($v) {
        return collect($v)->except(['contract_type', 'unit_id', '_token'])->toArray();
    }

    private function createApproval($c, $u) {
        if (!Approval::where('approvable_id', $c->id)->where('status', 'pending')->exists()) {
            Approval::create([
                'approvable_type' => 'contract',
                'approvable_id' => $c->id,
                'requester_person_id' => $u->person_id,
                'requester_user_id' => $u->id,
                'status' => 'pending',
                'note' => 'Review Kepala Unit'
            ]);
        }
    }

    private function terbilang($x) {
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

    public function terbilangApi(Request $request) {
        return response()->json(['success' => true, 'data' => ['words' => strtoupper($this->terbilang((int)$request->amount) . ' RUPIAH')]]);
    }

    private function getRomanMonth($m) {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$m];
    }

    protected function getUnitHeadUser(?Unit $unit) {
        if (!$unit) return null;
        return User::where('unit_id', $unit->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', config('recruitment.numbering.head_role_names')))
            ->with('person')
            ->first();
    }
}