<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\{Contract, ContractTemplate, RecruitmentApplicant, Unit, Document, Signature, Approval, User, Employee, Person};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('Superadmin');
        $isDhc = $user->hasRole('DHC');
        $isEmployee = $user->hasRole('Karyawan');
        $canSeeAll = $isSuperadmin || $isDhc;
        $userUnitId = (int) ($user->unit_id ?? 0);
        $isApproverOnly = $user->can('contract.approve') && !$user->can('contract.update');
        $selectedUnitId = $canSeeAll ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null) : $userUnitId;

        if ($request->ajax()) {
            $query = Contract::with(['unit:id,name', 'person:id,full_name', 'applicant.user.person'])
                ->select('contracts.*');

            if ($isEmployee && !$isSuperadmin && !$isDhc && !$isApproverOnly) {
                $query->where('employee_id', $user->employee_id)->whereIn('status', ['approved', 'signed']);
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

            $totalRecords = $query->count();

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('contract_no', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%")
                      ->orWhere('position_name', 'like', "%{$search}%")
                      ->orWhereHas('person', fn($sq) => $sq->where('full_name', 'like', "%{$search}%"));
                });
            }

            $recordsFiltered = $query->count();

            if ($request->has('order')) {
                $colIdx = $request->input('order.0.column');
                $dir = $request->input('order.0.dir');
                $cols = [
                    0 => 'contract_no', 
                    1 => 'ticket_number', 
                    3 => 'position_name', 
                    4 => 'start_date', 
                    5 => 'status'
                ];
                
                if (isset($cols[$colIdx])) {
                    $query->orderBy($cols[$colIdx], $dir);
                } elseif ($colIdx === 2) {
                     $query->orderBy('created_at', 'desc'); 
                }
            } else {
                $query->orderByDesc('created_at');
            }

            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);
            $data = $query->skip($start)->take($limit)->get();

            $formatted = $data->map(function ($c) use ($user) {
                $cand = $this->resolveCandidate($c);
                $personName = $cand['name'];

                $bg = match($c->status) {
                    'draft' => 'u-badge--warn', 'review' => 'u-badge--primary',
                    'approved' => 'u-badge--info', 'signed' => 'u-badge--success',
                    'rejected' => 'u-badge--danger', default => 'u-badge--glass'
                };
                $statusLabel = config('recruitment.contract_statuses')[$c->status] ?? $c->status;
                $statusHtml = "<span class='u-badge {$bg}'>{$statusLabel}</span>";

                $typeLabel = collect(config('recruitment.contract_types'))->firstWhere('code', $c->contract_type)['label'] ?? $c->contract_type;
                $docHtml = "<div class='u-font-mono u-font-bold u-text-sm'>" . ($c->contract_no ?: '(Draft)') . "</div>";
                $docHtml .= "<span class='u-badge u-badge--glass u-mt-xs u-text-xs'>" . $typeLabel . "</span>";

                $ticketHtml = $c->ticket_number ? "<span class='u-badge u-badge--info u-text-2xs'>{$c->ticket_number}</span>" : "<span class='u-text-muted u-text-xs'>-</span>";

                $personHtml = "<div><div class='u-font-bold u-text-sm'>{$personName}</div><div class='u-text-xs u-muted u-mt-xxs'>";
                if ($c->applicant_id) $personHtml .= "<span class='u-text-accent'><i class='fas fa-user-check u-mr-xxs'></i> Pelamar</span>";
                elseif ($c->employee_id) $personHtml .= "<i class='fas fa-id-badge u-mr-xxs'></i> {$c->employee_id}";
                else $personHtml .= "-";
                $personHtml .= "</div></div>";

                $posHtml = "<div class='u-text-sm u-font-medium' style='white-space: normal;'>" . ($c->position_name ?? '-') . "</div>";
                $posHtml .= "<div class='u-text-xs u-muted' style='white-space: normal;'><i class='fas fa-building u-mr-xxs'></i> " . ($c->unit?->name ?? '-') . "</div>";

                if ($c->contract_type === 'PB_PENGAKHIRAN') {
                    $effEnd = isset($c->remuneration_json['pb_effective_end']) ? Carbon::parse($c->remuneration_json['pb_effective_end'])->format('d M Y') : '-';
                    $periodHtml = "<span class='u-text-danger u-font-bold u-text-xs'><i class='fas fa-stop-circle u-mr-xxs'></i> End: {$effEnd}</span>";
                } else {
                    $s = $c->start_date ? Carbon::parse($c->start_date)->format('d/m/Y') : '-';
                    $e = $c->end_date ? Carbon::parse($c->end_date)->format('d/m/Y') : '-';
                    $periodHtml = "<div class='u-text-sm'>{$s}</div><div class='u-text-xs u-muted'>s/d {$e}</div>";
                }

                $actions = "<div class='cell-actions__group'>";
                $urlShow = route('recruitment.contracts.show', $c->id);
                $actions .= "<button type='button' class='u-btn u-btn--ghost u-btn--icon u-btn--sm js-btn-detail' data-show-url='{$urlShow}' title='Lihat Detail'><i class='fas fa-eye'></i></button>";
                
                if ($c->status === 'draft' && $user->can('contract.update', $c)) {
                    $urlUp = route('recruitment.contracts.update', $c->id);
                    $urlDel = route('recruitment.contracts.destroy', $c->id);
                    $actions .= "<button type='button' class='u-btn u-btn--outline u-btn--icon u-btn--sm js-btn-edit' data-show-url='{$urlShow}' data-update-url='{$urlUp}' title='Edit'><i class='fas fa-pencil-alt'></i></button>";
                    $actions .= "<button type='button' class='u-btn u-btn--danger u-btn--icon u-btn--sm js-btn-delete' data-url='{$urlDel}' title='Hapus'><i class='fas fa-trash'></i></button>";
                }
                $actions .= "</div>";

                return [
                    $docHtml,
                    $ticketHtml,
                    $personHtml,
                    $posHtml,
                    $periodHtml,
                    $statusHtml,
                    $actions
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $recordsFiltered,
                'data' => $formatted
            ]);
        }

        $unitsQuery = Unit::query()->select('id', 'name', 'category');
        if ($isSuperadmin) $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        elseif ($isDhc) $unitsQuery->where('category', 'ENABLER');
        
        $units = ($isSuperadmin || $isDhc) ? $unitsQuery->orderBy('name')->get() : Unit::where('id', $selectedUnitId)->get(['id', 'name', 'category']);

        $expiringContracts = collect();
        if ($canSeeAll || $user->can('contract.create')) {
             $expiringQuery = DB::table('portfolio_histories AS ph')
                ->leftJoin('employees AS e', 'e.person_id', '=', 'ph.person_id')
                ->leftJoin('units AS u', 'u.id', '=', 'e.unit_id')
                ->select('ph.id', 'ph.person_id', 'e.employee_id', 'ph.title AS position_name', 'ph.start_date', 'ph.end_date', 'e.employee_status AS employment_type', 'e.unit_id', 'u.name AS unit_name', DB::raw("(SELECT full_name FROM persons WHERE persons.id = ph.person_id) AS person_name"))
                ->where('ph.category', 'job')
                ->whereNotNull('ph.end_date')
                ->whereDate('ph.end_date', '>=', now())
                ->whereDate('ph.end_date', '<=', now()->addDays(30));

            if ($isDhc && !$isSuperadmin) {
                $expiringQuery->where('u.category', 'ENABLER');
            } elseif (!$canSeeAll && $userUnitId) {
                $expiringQuery->where('e.unit_id', $userUnitId);
            }
            $expiringContracts = $expiringQuery->orderBy('ph.end_date', 'asc')->get();
        }

        $applicants = RecruitmentApplicant::with(['user.person', 'recruitmentRequest.unit'])
            ->whereIn('status', config('recruitment.contract_applicant_statuses', ['APPROVED']))
            ->get();
        
        $locations = DB::table('locations')->select('id', DB::raw("CONCAT(name, ', ', city) as location_label"), 'name', 'city')->orderBy('name')->get();

        return view('recruitment.contracts.index', [
            'units' => $units,
            'positions' => DB::table('positions')->select('id', 'name')->orderBy('name')->get(),
            'locations' => $locations,
            'selectedUnitId' => $selectedUnitId,
            'statusFilter' => $request->input('status'),
            'statusOptions' => config('recruitment.contract_statuses'),
            'contractTypes' => collect(config('recruitment.contract_types'))->pluck('label', 'code'),
            'employmentTypes' => collect(config('recruitment.employment_types'))->map(fn($v, $k) => ['value' => $k, 'label' => $v])->values(),
            'applicants' => $applicants,
            'expiringContracts' => $expiringContracts,
            'canSeeAll' => $canSeeAll,
            'currentUser' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validateContract($request);
        if (!$request->user()->hasRole(['Superadmin', 'DHC']) && (int) $v['unit_id'] !== (int) $request->user()->unit_id)
            return back()->withErrors(['unit_id' => 'Unit tidak valid.'])->withInput();
        return $this->saveContract($request, new Contract(), $v);
    }

    public function update(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft')
            return back()->withErrors('Hanya dokumen status Draft yang bisa diedit.');
        $v = $this->validateContract($request);
        return $this->saveContract($request, $contract, $v);
    }

    private function saveContract($request, $c, $v)
    {
        DB::beginTransaction();
        try {
            $isNew = !$c->exists;
            $c->fill($v);
            if ($isNew) $c->contract_no = null;
            $c->unit_id = $v['unit_id'];

            if (in_array($v['contract_type'], ['SPK', 'PKWT_BARU']) && $v['applicant_id']) {
                $a = RecruitmentApplicant::with(['user', 'recruitmentRequest'])->find($v['applicant_id']);
                $c->applicant_id = $a->id;
                $c->person_id = $a->user?->person_id ?? null;
                if ($a->recruitmentRequest && $a->recruitmentRequest->ticket_number) {
                    $c->ticket_number = $a->recruitmentRequest->ticket_number;
                }
                if (empty($v['position_name'])) $c->position_name = $a->position_applied;
            } else {
                $c->person_id = $v['person_id'] ?: Employee::where('employee_id', $v['employee_id'])->value('person_id');
                if ($v['source_contract_id']) {
                    $parent = Contract::find($v['source_contract_id']);
                    if ($parent) {
                        $c->parent_contract_id = $parent->id;
                        $c->ticket_number = $parent->ticket_number;
                    }
                }
            }
            if (!$c->person_id) $c->person_id = $request->input('person_id');

            $c->requires_draw_signature = $request->has('requires_draw_signature');
            $c->requires_camera = $request->has('requires_camera');
            $c->requires_geolocation = $request->has('requires_geolocation');

            $meta = $this->collectMeta($v);
            $cand = $this->resolveCandidate($c);
            $meta['person_name'] = $cand['name'];
            $meta['new_unit_name'] = Unit::find($v['unit_id'])?->name;
            $meta['new_unit_id'] = (int) $v['unit_id'];

            if (!in_array($c->contract_type, ['PKWT_BARU', 'PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN'])) {
                unset($meta['work_location']);
            }

            $c->remuneration_json = $meta;

            if ($v['submit_action'] === 'submit') {
                $c->status = 'review';
                if (!$c->contract_no) $c->contract_no = $this->generateContractNumber($c);
            } elseif ($isNew || $v['submit_action'] === 'draft') {
                $c->status = 'draft';
            }

            if ($isNew) $c->created_by_user_id = $request->user()->id;
            
            $c->save();
            $this->ensureDocumentRecord($c);

            if ($c->status === 'review') {
                $this->createApproval($c, $request->user());
                $this->generatePdfFile($c);
            }

            DB::commit();
            if ($isNew) return redirect()->route('recruitment.contracts.index', ['unit_id' => $c->unit_id])->with('success', 'Dokumen berhasil disimpan.');
            return back()->with('success', 'Dokumen berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    private function validateContract(Request $request)
    {
        $rules = [
            'contract_type' => 'required',
            'unit_id' => 'required',
            'applicant_id' => 'nullable',
            'employee_id' => 'nullable',
            'person_id' => 'nullable',
            'position_name' => 'nullable',
            'employment_type' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'remarks' => 'nullable',
            'work_location' => 'nullable|string',
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
            'work_days' => 'nullable|string',
            'work_hours' => 'nullable|string',
            'break_hours' => 'nullable|string',
            'travel_allowance_stay' => 'nullable',
            'travel_allowance_non_stay' => 'nullable',
            'pb_effective_end' => 'nullable',
            'pb_compensation_amount' => 'nullable',
            'pb_compensation_amount_words' => 'nullable',
            'submit_action' => 'required',
            'source_contract_id' => 'nullable',
            'requires_draw_signature' => 'nullable',
            'requires_camera' => 'nullable',
            'requires_geolocation' => 'nullable'
        ];

        if (!in_array($request->contract_type, ['PKWT_BARU', 'PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN'])) {
            unset($rules['work_location']);
        }

        return $request->validate($rules);
    }

    public function destroy(Contract $contract)
    {
        if ($contract->status !== 'draft')
            return back()->withErrors('Hanya dokumen status Draft yang dapat dihapus.');
        DB::beginTransaction();
        try {
            if ($contract->document_id) {
                $doc = Document::find($contract->document_id);
                if ($doc) {
                    if (Storage::disk('local')->exists($doc->path))
                        Storage::disk('local')->delete($doc->path);
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
        $role = in_array($contract->contract_type, ['PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN']) ? 'Pegawai' : 'Kandidat';
        return $this->processSignAction($request, $contract, 'signed', $role);
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
            'geo_accuracy' => 'nullable|numeric',
            'snapshot_image' => 'nullable|string',
        ]);

        // GPS VALIDATION - Ultra Presisi Check
        $geoLat = !empty($data['geo_lat']) ? floatval($data['geo_lat']) : null;
        $geoLng = !empty($data['geo_lng']) ? floatval($data['geo_lng']) : null;
        $geoAccuracy = !empty($data['geo_accuracy']) ? floatval($data['geo_accuracy']) : 999;
        
        // Validasi: GPS coordinates harus valid
        if ($geoLat && $geoLng) {
            if ($geoLat < -90 || $geoLat > 90 || $geoLng < -180 || $geoLng > 180) {
                return response()->json(['success' => false, 'message' => '❌ Koordinat GPS Invalid - Kemungkinan VPN/Spoofing'], 400);
            }
            
            // Enterprise: Accept all GPS accuracy levels (informative logging only)
            \Log::info("Contract Sign: GPS Locked - Accuracy {$geoAccuracy}m for user {$request->user()->id}");
        }

        DB::beginTransaction();
        try {
            $snapshotPath = null;
            $camHash = null;
            if (!empty($data['snapshot_image']) && str_starts_with($data['snapshot_image'], 'data:image')) {
                $image_parts = explode(";base64,", $data['snapshot_image']);
                if (count($image_parts) >= 2) {
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1] ?? 'jpeg';
                    $image_base64 = base64_decode($image_parts[1]);
                    $fileName = 'snap_' . $contract->id . '_' . time() . '_' . Str::random(8) . '.' . $image_type;
                    $path = 'signatures/snapshots/' . $fileName;
                    Storage::disk('public')->put($path, $image_base64);
                    $snapshotPath = $path;
                    $camHash = hash('sha256', $image_base64);
                }
            }

            if ($role === 'Kepala Unit') {
                Approval::where('approvable_id', $contract->id)->where('approvable_type', 'contract')->where('status', 'pending')->update([
                    'status' => 'approved',
                    'approver_person_id' => $request->user()->person_id,
                    'approver_user_id' => $request->user()->id,
                    'decided_at' => now(),
                    'note' => $data['note'] ?? null
                ]);
                if (!$contract->contract_no)
                    $contract->contract_no = $this->generateContractNumber($contract);
            } else {
                Approval::where('approvable_id', $contract->id)->where('approvable_type', 'contract')->where('status', 'approved')->update([
                    'status' => 'completed',
                    'note' => $data['note'] ?? 'Signed'
                ]);
            }

            $this->ensureDocumentRecord($contract);
            $sigHash = !empty($data['signature_image']) ? hash('sha256', $data['signature_image']) : null;
            $verifCode = strtoupper(Str::random(10));

            Signature::create([
                'document_id' => $contract->document_id,
                'signer_person_id' => $request->user()->person_id,
                'signer_user_id' => $request->user()->id,
                'signer_role' => $role,
                'signed_at' => now(),
                'signature_draw_data' => $data['signature_image'] ?? null,
                'signature_draw_hash' => $sigHash,
                'geo_lat' => $geoLat,
                'geo_lng' => $geoLng,
                'geo_accuracy_m' => $geoAccuracy,
                'camera_photo_path' => $snapshotPath,
                'camera_photo_hash' => $camHash,
                'snapshot_data' => $snapshotPath,
                'verification_code' => $verifCode,
                'ip_address' => $request->ip()
            ]);

            $contract->update(['status' => $status]);
            $contract->loadMissing('document');
            if ($contract->document && Storage::disk('local')->exists($contract->document->path))
                Storage::disk('local')->delete($contract->document->path);
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
        $request->validate(['rejection_note' => 'required|string|min:5']);

        DB::transaction(function () use ($contract, $request) {
            Approval::where('approvable_id', $contract->id)
                ->where('approvable_type', 'contract')
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'note' => $request->rejection_note,
                    'decided_at' => now(),
                    'approver_user_id' => $request->user()->id,
                    'approver_person_id' => $request->user()->person_id
                ]);

            $contract->update(['status' => 'draft']);
        });

        return response()->json(['success' => true]);
    }

    public function submit(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft')
            return response()->json(['success' => false, 'message' => 'Hanya draft yang bisa disubmit.'], 422);

        DB::transaction(function () use ($contract, $request) {
            $contract->update([
                'status' => 'review',
                'contract_no' => $contract->contract_no ?: $this->generateContractNumber($contract)
            ]);
            $this->createApproval($contract, $request->user());
        });

        return response()->json(['success' => true]);
    }

    public function document(Request $request, Contract $contract)
    {
        set_time_limit(300);
        $this->ensureDocumentRecord($contract);
        $contract->refresh()->loadMissing('document');
        if (!Storage::disk('local')->exists($contract->document->path)) {
            $this->generatePdfFile($contract);
            $contract->refresh()->loadMissing('document');
        }
        $filename = basename($contract->document->path);
        return response()->file(Storage::disk('local')->path($contract->document->path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    public function show(Contract $contract)
    {
        $contract->load(['unit', 'document', 'person', 'applicant.user.person']);

        $creatorName = 'System';
        if ($contract->created_by_user_id) {
            $creator = User::find($contract->created_by_user_id);
            if ($creator) {
                $roles = $creator->getRoleNames()->join(', ');
                $realName = $creator->person ? $creator->person->full_name : $creator->name;
                $creatorName = $realName;
                if (!empty($roles))
                    $creatorName .= " ($roles)";
            }
        }

        $meta = $contract->remuneration_json ?? [];
        $cand = $this->resolveCandidate($contract);
        $docUrl = ($contract->document_id || in_array($contract->status, ['approved', 'signed'])) ? route('recruitment.contracts.document', $contract) : null;
        $typeCfg = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];

        $me = auth()->user();
        $isInternal = $me->hasRole(['Superadmin', 'DHC', 'SDM Unit', 'Kepala Unit']);

        $latestRejection = Approval::where('approvable_id', $contract->id)
            ->where('approvable_type', 'contract')
            ->where('status', 'rejected')
            ->latest('decided_at')
            ->first();

        Carbon::setLocale('id');

        $logs = collect();

        if ($contract->document_id) {
            $signatures = Signature::with(['signerPerson', 'signerUser.employee'])
                ->where('document_id', $contract->document_id)
                ->orderByDesc('signed_at')
                ->get()
                ->map(function ($sig) {
                    return [
                        'type' => 'signature',
                        'status' => 'signed',
                        'name' => $sig->signerPerson->full_name ?? $sig->signerUser->name ?? '-',
                        'role' => $sig->signer_role,
                        'date' => Carbon::parse($sig->signed_at)->timezone('Asia/Jakarta'),
                        'note' => 'Signed Digitally'
                    ];
                });
            $logs = $logs->merge($signatures);
        }

        $rejections = Approval::with(['approverUser.person', 'approverPerson', 'approverUser.employee'])
            ->where('approvable_id', $contract->id)
            ->where('approvable_type', 'contract')
            ->where('status', 'rejected')
            ->get()
            ->map(function ($app) {
                $name = $app->approverPerson->full_name ?? $app->approverUser->person->full_name ?? $app->approverUser->name ?? 'System';
                $role = $app->approverUser->employee->latest_jobs_title ?? 'Kepala Unit';
                return [
                    'type' => 'approval',
                    'status' => 'rejected',
                    'name' => $name,
                    'role' => $role,
                    'date' => Carbon::parse($app->decided_at)->timezone('Asia/Jakarta'),
                    'note' => $app->note
                ];
            });

        $logs = $logs->merge($rejections)->sortByDesc('date')->values();

        $approvalLogsFormatted = $logs->map(function ($log) {
            return [
                'status' => $log['status'],
                'name' => $log['name'],
                'role' => $log['role'],
                'note' => $log['note'],
                'date_formatted' => $log['date']->translatedFormat('d M Y, H:i') . ' WIB',
                'time_ago' => $log['date']->diffForHumans()
            ];
        });

        $headSig = $contract->document_id ? Signature::where('document_id', $contract->document_id)->where('signer_role', 'Kepala Unit')->first() : null;

        $unitHeadUser = $this->getUnitHeadUser($this->resolveHeadUnit($contract->unit));
        $headRealName = $unitHeadUser->person->full_name ?? $unitHeadUser->name ?? 'Kepala Unit';
        $headPosition = 'Kepala Unit';
        if ($unitHeadUser && $unitHeadUser->employee) {
            $headPosition = $unitHeadUser->employee->latest_jobs_title ?? 'Kepala Unit';
        }

        $headStatus = 'Waiting';
        $headDate = '-';
        $headCss = 'u-badge--glass';

        if ($headSig) {
            $headStatus = 'Signed';
            $headDate = Carbon::parse($headSig->signed_at)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB';
            $headCss = 'u-badge--success';
        } elseif ($contract->status === 'review') {
            $headStatus = 'Pending';
            $headDate = 'Menunggu Review';
            $headCss = 'u-badge--warn';
        } elseif ($contract->status === 'approved') {
            $headStatus = 'Approved';
            $headDate = 'Menunggu TTD';
            $headCss = 'u-badge--info';
        } elseif ($contract->status === 'rejected') {
            $headStatus = 'Rejected';
            $headCss = 'u-badge--danger';
        }

        $headProgress = [
            'status' => $headStatus,
            'name' => $headRealName,
            'position' => $headPosition,
            'date' => $headDate,
            'css' => $headCss
        ];

        $candSig = $contract->document_id ? Signature::where('document_id', $contract->document_id)->whereIn('signer_role', ['Kandidat', 'Pegawai'])->first() : null;
        $candProgress = ['status' => 'Waiting', 'name' => $cand['name'], 'date' => '-', 'css' => 'u-badge--glass'];

        if ($candSig) {
            $candProgress = [
                'status' => 'Signed',
                'name' => $cand['name'],
                'date' => Carbon::parse($candSig->signed_at)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                'css' => 'u-badge--success'
            ];
        } elseif ($contract->status === 'approved') {
            $candProgress = ['status' => 'Pending', 'name' => $cand['name'], 'date' => 'Menunggu Tanda Tangan', 'css' => 'u-badge--warn'];
        }

        $canSign = $me->can('contract.sign') && $contract->status === 'approved';
        if ($canSign && $me->hasRole('Karyawan') && $contract->employee_id !== $me->employee_id)
            $canSign = false;

        $targetRole = in_array($contract->contract_type, ['PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN']) ? 'Pegawai' : 'Kandidat';
        $geoData = $this->getGeoData($contract, $me);
        $approvalLogs = $isInternal ? $approvalLogsFormatted : [];

        return response()->json([
            'success' => true,
            'data' => array_merge($contract->toArray(), [
                'contract_type_label' => $typeCfg['label'] ?? $contract->contract_type,
                'person_name' => $cand['name'],
                'start_date' => Carbon::parse($contract->start_date)->translatedFormat('d M Y'),
                'end_date' => Carbon::parse($contract->end_date)->translatedFormat('d M Y'),
                'start_date_raw' => Carbon::parse($contract->start_date)->format('Y-m-d'),
                'end_date_raw' => Carbon::parse($contract->end_date)->format('Y-m-d'),
                'remuneration_json' => $meta,
                'can_approve' => auth()->user()->can('contract.approve') && $contract->status === 'review',
                'can_sign' => $canSign,
                'doc_url' => $docUrl,
                'approve_url' => route('recruitment.contracts.approve', $contract),
                'sign_url' => route('recruitment.contracts.sign', $contract),
                'reject_url' => route('recruitment.contracts.reject', $contract),
                'ui_nik_ktp' => $cand['nik_ktp'],
                'ui_employee_id' => $cand['employee_id'],
                'target_role_label' => $targetRole,
                'geolocation' => $geoData,
                'tracker' => [
                    'head' => $headProgress,
                    'candidate' => $candProgress
                ],
                'ticket_number' => $contract->ticket_number,
                'rejection_note' => $latestRejection ? $latestRejection->note : null,
                'can_see_logs' => $isInternal,
                'approval_logs' => $approvalLogs,
                'creator_name' => $creatorName,
                'created_at_human' => $contract->created_at->diffForHumans(),
                'created_at_formatted' => $contract->created_at->translatedFormat('d M Y, H:i') . ' WIB'
            ])
        ]);
    }

    protected function getGeoData(Contract $contract, User $user)
    {
        $geo = ['head' => null, 'candidate' => null];
        if (!$contract->document_id)
            return $geo;

        $headSig = Signature::where('document_id', $contract->document_id)->where('signer_role', 'Kepala Unit')->orderByDesc('signed_at')->orderByDesc('id')->first();
        $candSig = Signature::where('document_id', $contract->document_id)->whereIn('signer_role', ['Kandidat', 'Pegawai'])->orderByDesc('signed_at')->orderByDesc('id')->first();
        $isPowerUser = $user->hasRole(['Superadmin', 'SDM Unit', 'DHC', 'Kepala Unit']);

        if ($headSig && ($isPowerUser || $headSig->signer_person_id == $user->person_id)) {
            $tz = $this->resolveIndonesianTimezone($headSig->geo_lng);
            $geo['head'] = [
                'lat' => $headSig->geo_lat,
                'lng' => $headSig->geo_lng,
                'ts' => Carbon::parse($headSig->signed_at)->timezone($tz['zone'])->format('d M Y H:i') . ' ' . $tz['abbr'],
                'name' => 'Kepala Unit',
                'image_url' => $this->resolveImageUrl($headSig->camera_photo_path ?: $headSig->snapshot_data)
            ];
        }
        if ($candSig && ($isPowerUser || $candSig->signer_person_id == $user->person_id)) {
            $tz = $this->resolveIndonesianTimezone($candSig->geo_lng);
            $geo['candidate'] = [
                'lat' => $candSig->geo_lat,
                'lng' => $candSig->geo_lng,
                'ts' => Carbon::parse($candSig->signed_at)->timezone($tz['zone'])->format('d M Y H:i') . ' ' . $tz['abbr'],
                'name' => 'Kandidat/Pegawai',
                'image_url' => $this->resolveImageUrl($candSig->camera_photo_path ?: $candSig->snapshot_data)
            ];
        }
        return $geo;
    }

    private function resolveImageUrl($pathOrBase64)
    {
        if (!$pathOrBase64)
            return null;
        if (str_starts_with($pathOrBase64, 'data:image'))
            return $pathOrBase64;
        return asset('storage/' . $pathOrBase64);
    }

    protected function resolveIndonesianTimezone($lng)
    {
        $lng = (float) $lng;
        if ($lng > 125.0)
            return ['zone' => 'Asia/Jayapura', 'abbr' => 'WIT'];
        elseif ($lng > 114.5)
            return ['zone' => 'Asia/Makassar', 'abbr' => 'WITA'];
        return ['zone' => 'Asia/Jakarta', 'abbr' => 'WIB'];
    }

    protected function resolveCandidate(Contract $c)
    {
        $p = $c->person;
        if (!$p && $c->applicant_id) {
            $app = RecruitmentApplicant::with('user.person')->find($c->applicant_id);
            $p = $app?->user?->person;
        }
        if (!$p && $c->person_id)
            $p = Person::find($c->person_id);

        $d = ['name' => 'Kandidat', 'address' => '-', 'nik_ktp' => '-', 'employee_id' => '-', 'pob' => '-', 'dob' => '-', 'gender' => '-'];

        if ($p) {
            $d['name'] = $p->full_name ?? $p->name ?? 'Kandidat';
            $d['address'] = $p->address ?? $p->domicile_address ?? '-';
            $ktpDb = DB::table('identities')->where('person_id', $p->id)->whereIn('system', ['nik', 'ktp', 'e_ktp'])->value('external_id');
            $d['nik_ktp'] = $ktpDb ?? $p->nik ?? $p->nik ?? '-';
            $empId = $c->employee_id ?: (Employee::where('person_id', $p->id)->value('employee_id') ?? '-');
            $d['employee_id'] = $empId;
            $d['pob'] = $p->place_of_birth ?? $p->pob ?? '-';
            $d['dob'] = $p->date_of_birth ? Carbon::parse($p->date_of_birth)->translatedFormat('d F Y') : '-';
            $g = strtoupper($p->gender ?? '');
            if (in_array($g, ['L', 'MALE', 'PRIA', 'LAKI-LAKI']))
                $d['gender'] = 'Laki-laki';
            elseif (in_array($g, ['P', 'FEMALE', 'WANITA', 'PEREMPUAN']))
                $d['gender'] = 'Perempuan';
        } elseif ($c->applicant) {
            $d['name'] = $c->applicant->user?->name ?? 'Kandidat';
        }
        return $d;
    }

    protected function resolveSigner(Contract $c, $meta)
    {
        $uId = (int) ($meta['new_unit_id'] ?? $c->unit_id);
        $unit = $uId ? Unit::find($uId) : $c->unit;
        $headUnit = $this->resolveHeadUnit($unit);
        $headUser = $this->getUnitHeadUser($headUnit);
        $headArr = $headUser && $headUser->person && ($headUser->person->full_name ?? null) ? ['name' => $headUser->person->full_name, 'position' => $this->getUserJobTitle($headUser)] : null;

        if (!in_array($c->status, ['approved', 'signed'])) {
            if ($headArr)
                return $headArr;
            return ['name' => 'Nama Kepala Unit', 'position' => 'Kepala Unit'];
        }

        $ap = Approval::where('approvable_id', $c->id)->where('approvable_type', 'contract')->whereIn('status', ['approved', 'completed'])->orderByDesc('decided_at')->first();
        $apUser = $ap && $ap->approver_person_id ? User::where('person_id', $ap->approver_person_id)->with('person', 'roles')->first() : null;
        $apArr = $apUser && $apUser->person && ($apUser->person->full_name ?? null) ? ['name' => $apUser->person->full_name, 'position' => $this->getUserJobTitle($apUser)] : null;

        if ($headArr) {
            if ($apUser && $headUser && $apUser->id === $headUser->id)
                return $headArr;
            return $headArr;
        }
        if ($apArr)
            return $apArr;
        return ['name' => 'Nama Kepala Unit', 'position' => 'Kepala Unit'];
    }

    protected function getUserJobTitle(User $u)
    {
        if ($u->employee_id) {
            $emp = Employee::where('employee_id', $u->employee_id)->first();
            if ($emp && $emp->latest_jobs_title)
                return $emp->latest_jobs_title;
            if ($emp && $emp->position_id) {
                $pos = DB::table('positions')->where('id', $emp->position_id)->value('name');
                if ($pos)
                    return $pos;
            }
        }
        return 'Kepala Unit';
    }

    protected function generateContractNumber(Contract $c): string
    {
        $c->loadMissing('unit');
        $code = config("recruitment.numbering.doc_codes.{$c->contract_type}") ?? 'PERJ';
        
        $cat = strtoupper($c->unit?->category ?? '');
        $uCode = ($cat === 'ENABLER') ? 'DSDM' : ($c->unit?->code ?? 'UNIT');

        $romanMonth = $this->getRomanMonth(now()->month);
        $year = now()->year;
        $defaultHead = (string) (config('recruitment.numbering.default_head_code') ?? 'XX');
        $headCode = $defaultHead;
        $headUnit = $this->resolveHeadUnit($c->unit);
        $head = $this->getUnitHeadUser($headUnit);
        if ($head && $head->person && ($head->person->full_name ?? null)) {
            $ini = $this->initialsFromName($head->person->full_name);
            if ($ini)
                $headCode = $ini;
        }
        $base = "/{$uCode}-{$romanMonth}/{$headCode}/{$year}";
        $last = DB::table('contracts')->where('contract_no', 'like', "%{$base}")->orderByRaw('LENGTH(contract_no) DESC')->orderBy('contract_no', 'desc')->value('contract_no');
        $seq = 1;
        if ($last) {
            $parts = explode('/', $last);
            $nums = explode('-', $parts[0] ?? '');
            $seq = ((int) end($nums)) + 1;
        }
        do {
            $candidate = sprintf("%s-%03d%s", $code, $seq, $base);
            $exists = DB::table('contracts')->where('contract_no', $candidate)->exists();
            if ($exists)
                $seq++;
        } while ($exists);
        return $candidate;
    }

    protected function initialsFromName($name)
    {
        $n = trim(preg_replace('/\s+/', ' ', (string) $name));
        if ($n === '')
            return null;
        $parts = explode(' ', $n);
        $first = $parts[0] ?? '';
        $last = end($parts) ?: '';
        $a = mb_strtoupper(mb_substr($first, 0, 1));
        $b = mb_strtoupper(mb_substr($last, 0, 1));
        $return = trim($a . $b);
        return $return ?: null;
    }

    protected function resolveHeadUnit(?Unit $unit)
    {
        if (!$unit)
            return null;
        $rules = config('recruitment.numbering.head_unit_rules', []);
        $cat = strtoupper((string) ($unit->category ?? ''));
        $rule = $rules[$cat] ?? ['mode' => 'self_unit'];
        $mode = strtolower((string) ($rule['mode'] ?? 'self_unit'));
        if ($mode === 'fixed_unit') {
            $m = $rule['unit_match'] ?? [];
            $q = Unit::query();
            if (!empty($m['code']))
                $q->where('code', $m['code']);
            if (!empty($m['name_contains']))
                $q->where('name', 'like', '%' . $m['name_contains'] . '%');
            $t = $q->first();
            return $t ?: $unit;
        }
        return $unit;
    }

    protected function generatePdfFile($contract)
    {
        $contract->loadMissing(['unit', 'document']);
        $doc = config("recruitment.contract_types.{$contract->contract_type}.document_type");
        if (!$doc)
            throw new \RuntimeException("document_type missing: {$contract->contract_type}");
        $template = ContractTemplate::where('code', $doc)->firstOrFail();
        $vars = $this->getTemplateVars($contract);
        $html = $this->renderPdfHtml($contract, $template, $vars);

        // Debug: Simpan HTML sebelum diubah menjadi PDF
        Storage::disk('local')->put('debug_contract.html', $html);

        $cfg = (array) (config('recruitment.pdf', []) ?? []);
        $page = (array) ($cfg['page'] ?? []);
        $paper = (string) ($page['paper'] ?? 'a4');
        $orientation = (string) ($page['orientation'] ?? 'portrait');
        $dompdf = (array) ($cfg['dompdf'] ?? []);
        $dpi = (int) ($dompdf['dpi'] ?? 96);
        $pdf = Pdf::loadHTML($html)->setPaper($paper, $orientation);
        $dom = $pdf->getDomPDF();
        $dom->set_option('dpi', $dpi);
        $dom->set_option('isRemoteEnabled', (bool) ($dompdf['isRemoteEnabled'] ?? true));
        $dom->set_option('isHtml5ParserEnabled', (bool) ($dompdf['isHtml5ParserEnabled'] ?? true));
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
        if (empty($vars))
            $vars = $this->getTemplateVars($contract);
        $body = (string) ($template->body ?? '');
        foreach ($vars as $k => $v)
            $body = str_replace("{{{$k}}}", (string) $v, $body);
        $body = preg_replace('~<!doctype[^>]*>~i', '', $body);
        $body = preg_replace('~</?(html|head|body)[^>]*>~i', '', $body);
        $body = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $body);

        $cfg = (array) (config('recruitment.pdf', []) ?? []);
        $tplKey = (string) ($template->code ?? '');
        $m = (array) (data_get($cfg, "templates.{$tplKey}.margin_cm") ?? data_get($cfg, 'margin_cm') ?? []);
        $m['top'] = 3.5;
        $m['bottom'] = 3.5;
        $mt = (float) ($m['top']);
        $mr = (float) ($m['right'] ?? 2.54);
        $mb = (float) ($m['bottom']);
        $ml = (float) ($m['left'] ?? 2.54);
        $page = (array) ($cfg['page'] ?? []);
        $pw = (float) ($page['width_cm'] ?? 21);
        $ph = (float) ($page['height_cm'] ?? 29.7);
        $font = (array) (data_get($cfg, "templates.{$tplKey}.font") ?? data_get($cfg, 'font') ?? []);
        $ff = (string) ($font['family'] ?? 'Tahoma');
        $fs = (float) ($font['size_pt'] ?? 11);
        $lh = (float) ($font['line_height'] ?? 1.3);
        $titleSize = (float) ($font['title_size_pt'] ?? 14);
        $pa = (float) ($font['paragraph_after_pt'] ?? 6);

        $disk = 'public';
        $path = $template->header_image_path;
        if (!$path) {
            $disk = (string) ($cfg['letterhead_disk'] ?? 'public');
            $path = (string) ($cfg['letterhead_path'] ?? '');
        }
        $lhImg = $this->pdfDataUri($disk, $path);
        $pathRegular = storage_path((string) ($font['regular_file'] ?? 'app/fonts/tahoma.ttf'));
        $pathBold = storage_path((string) ($font['bold_file'] ?? 'app/fonts/tahomabd.ttf'));
        $fontFaceCss = '';
        $finalFamily = $ff; // Gunakan font dari config atau template
        if (file_exists($pathRegular)) {
            $fr64 = base64_encode(file_get_contents($pathRegular));
            $fb64 = file_exists($pathBold) ? base64_encode(file_get_contents($pathBold)) : $fr64;
            $fontFaceCss = "@font-face{font-family:'{$ff}';font-style:normal;font-weight:400;src:url(data:font/truetype;base64,{$fr64}) format('truetype');}@font-face{font-family:'{$ff}';font-style:normal;font-weight:700;src:url(data:font/truetype;base64,{$fb64}) format('truetype');}";
        } else {
            // Jika font file tidak ada, gunakan web-safe alternative
            if (stripos($ff, 'Tahoma') !== false) {
                $finalFamily = "'Trebuchet MS', 'Lucida Grande', sans-serif";
            }
        }

        // Extract hanya font-family dari template CSS tanpa menghapus @page dan body
        $tplCss = (string) ($template->css ?? '');

        $css = "@page{margin:{$mt}cm {$mr}cm {$mb}cm {$ml}cm;}{$fontFaceCss}
        body{margin:0;padding:0;font-family:{$finalFamily},sans-serif;font-size:{$fs}pt;line-height:{$lh};color:#000;background-color:rgba(255,255,255,0.88);}
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
        ol ol{list-style-type:lower-alpha;padding-left:25px;}
        ul{margin:0 0 {$pa}pt 0;padding-left:30px;}
        ul li{text-align:justify;padding-left:5px;margin-bottom:5px;}
        .title{text-align:center;font-weight:700;text-transform:uppercase;font-size:{$titleSize}pt;margin:0 0 5pt 0;text-decoration:underline;}
        .subtitle{text-align:center;font-weight:700;font-size:{$fs}pt;margin:0 0 12pt 0;}
        .pasal-title{text-align:center!important;font-weight:700!important;text-transform:uppercase;line-height:{$lh};margin:14pt 0 6pt 0;font-size:{$fs}pt;page-break-after:avoid;}
        table.ttd{width:100%;margin-top:24pt;page-break-inside:avoid;table-layout:fixed;}
        table.ttd td{text-align:center;vertical-align:top;}
        .sig-box{height:70px;}
        {$tplCss}";

        // Hapus deklarasi body duplikat untuk memastikan hanya satu font-family yang digunakan
        $css = preg_replace('/body\s*\{[^}]*font-family:[^;]+;[^}]*\}/', '', $css, 1);

        $bg = $lhImg ? ("<img class='letterhead-img' src='{$lhImg}'>") : '';
        return "<html><head><meta charset='utf-8'><style>{$css}</style></head><body>{$bg}{$body}</body></html>";
    }

    protected function pdfDataUri($disk, $path)
    {
        if (!$path || !Storage::disk($disk)->exists($path))
            return null;
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
        $fmt = fn($n) => 'Rp ' . number_format((float) preg_replace('/\D/', '', $n ?? 0), 0, ',', '.');
        $signerImg = Signature::where('document_id', $c->document_id)->where('signer_role', 'Kepala Unit')->orderByDesc('signed_at')->orderByDesc('id')->value('signature_draw_data');
        $candImg = Signature::where('document_id', $c->document_id)->whereIn('signer_role', ['Kandidat', 'Pegawai'])->orderByDesc('signed_at')->orderByDesc('id')->value('signature_draw_data');
        $signerTag = $signerImg ? "<img src='{$signerImg}' style='height:70px;'>" : "<div style='height:70px'></div>";
        $candTag = $candImg ? "<img src='{$candImg}' style='height:70px;'>" : "<div style='height:70px'></div>";

        $duration = '-';
        if ($c->start_date && $c->end_date) {
            $startDate = $c->start_date->copy()->startOfDay();
            $endDatePlusOne = $c->end_date->copy()->startOfDay()->addDay();
            $diff = $startDate->diff($endDatePlusOne);
            
            $parts = [];
            if ($diff->y)
                $parts[] = $diff->y . " Tahun";
            if ($diff->m)
                $parts[] = $diff->m . " Bulan";
            if ($diff->d && $diff->d > 0)
                $parts[] = $diff->d . " Hari";

            if (empty($parts)) {
                $duration = '0 Hari';
            } else {
                $duration = implode(' ', $parts);
            }
        }

        $workDays = $meta['work_days'] ?? 'Senin s/d hari Jumat';
        $workHours = $meta['work_hours'] ?? 'Jam 07.30 WIB s/d 16.30 WIB';
        $breakHours = $meta['break_hours'] ?? 'Jam 12.00 WIB s/d 13.00 WIB';
        $travelStay = $fmt($meta['travel_allowance_stay'] ?? 150000);
        $travelNonStay = $fmt($meta['travel_allowance_non_stay'] ?? 75000);
        $unitHeadTitle = isset($meta['new_unit_name']) ? 'Kepala ' . $meta['new_unit_name'] : ($signer['position'] ?? 'Kepala Unit');

        return [
            'contract_no' => $c->contract_no ?? 'DRAFT',
            'ticket_number' => $c->ticket_number ?? '-',
            'today_date' => now()->translatedFormat('d F Y'),
            'today_date_numeric' => now()->format('d/m/Y'),
            'day_name' => now()->translatedFormat('l'),
            'day_number' => ucwords($this->terbilang(now()->day)),
            'month_name' => now()->translatedFormat('F'),
            'year_name' => ucwords($this->terbilang(now()->year)),
            'signer_name' => $signer['name'],
            'signer_position' => $signer['position'],
            'signer_signature' => $signerTag,
            'candidate_name' => $cand['name'],
            'candidate_address' => $cand['address'],
            'candidate_nik' => $cand['nik_ktp'],
            'candidate_employee_id' => $cand['employee_id'],
            'pob' => $cand['pob'],
            'dob' => $cand['dob'],
            'gender' => $cand['gender'],
            'candidate_signature' => $candTag,
            'position_name' => $c->position_name,
            'unit_name' => $meta['new_unit_name'] ?? ($c->unit->name ?? '-'),
            'unit_head_position' => $unitHeadTitle,
            'employment_type' => $c->employment_type,
            'duration' => $duration,
            'start_date' => $c->start_date?->translatedFormat('d F Y'),
            'end_date' => $c->end_date?->translatedFormat('d F Y'),
            'salary' => $fmt($meta['salary_amount'] ?? 0),
            'salary_words' => ucwords($meta['salary_amount_words'] ?? ''),
            'meal_allowance' => $fmt($meta['lunch_allowance_daily'] ?? 0),
            'allowance_list' => $this->formatAllowances($meta, $fmt),
            'other_benefits' => $meta['other_benefits_desc'] ?? '-',
            'work_days' => $workDays,
            'work_hours' => $workHours,
            'break_hours' => $breakHours,
            'travel_allowance_stay' => $travelStay,
            'travel_allowance_non_stay' => $travelNonStay,
            'pb_date' => isset($meta['pb_effective_end']) ? Carbon::parse($meta['pb_effective_end'])->translatedFormat('d F Y') : '-',
            'pb_amount' => $fmt($meta['pb_compensation_amount'] ?? 0),
            'pb_words' => ucwords($meta['pb_compensation_amount_words'] ?? ''),
            'work_location' => in_array($c->contract_type, ['PKWT_BARU', 'PKWT_PERPANJANGAN', 'PB_PENGAKHIRAN']) ? ($meta['work_location'] ?? '') : ''
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
        if (!Storage::disk('local')->exists('contracts'))
            Storage::disk('local')->makeDirectory('contracts');
    }

    private function formatAllowances($m, $fmt)
    {
        $list = [];
        if (($v = $m['allowance_position_amount'] ?? 0))
            $list[] = "T.Jabatan " . $fmt($v);
        if (($v = $m['allowance_communication_amount'] ?? 0))
            $list[] = "T.Komunikasi " . $fmt($v);
        if (($v = $m['allowance_special_amount'] ?? 0))
            $list[] = "T.Khusus " . $fmt($v);
        if (($v = $m['allowance_other_amount'] ?? 0))
            $list[] = "Lainnya " . $fmt($v);
        return implode(', ', $list) ?: '-';
    }

    private function collectMeta($v)
    {
        return collect($v)->except(['contract_type', 'unit_id', '_token'])->toArray();
    }

    private function createApproval($c, $u)
    {
        if (!Approval::where('approvable_id', $c->id)->where('approvable_type', 'contract')->where('status', 'pending')->exists()) {
            Approval::create(['approvable_type' => 'contract', 'approvable_id' => $c->id, 'requester_person_id' => $u->person_id, 'requester_user_id' => $u->id, 'status' => 'pending', 'note' => 'Review Kepala Unit']);
        }
    }

    private function terbilang($x)
    {
        $a = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        if ($x < 12)
            return $a[$x];
        if ($x < 20)
            return $this->terbilang($x - 10) . " belas";
        if ($x < 100)
            return $this->terbilang($x / 10) . " puluh " . $this->terbilang($x % 10);
        if ($x < 200)
            return "seratus " . $this->terbilang($x - 100);
        if ($x < 1000)
            return $this->terbilang($x / 100) . " ratus " . $this->terbilang($x % 100);
        if ($x < 2000)
            return "seribu " . $this->terbilang($x - 1000);
        if ($x < 1000000)
            return $this->terbilang($x / 1000) . " ribu " . $this->terbilang($x % 1000);
        return $this->terbilang($x / 1000000) . " juta " . $this->terbilang($x % 1000000);
    }

    public function terbilangApi(Request $request)
    {
        return response()->json(['success' => true, 'data' => ['words' => strtoupper($this->terbilang((int) $request->amount) . ' RUPIAH')]]);
    }

    private function getRomanMonth($m)
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$m] ?? '';
    }

    protected function getUnitHeadUser(?Unit $unit)
    {
        if (!$unit)
            return null;
        $roleNames = (array) config('recruitment.numbering.head_role_names', []);
        $teamKey = (string) (config('permission.team_foreign_key') ?? 'unit_id');
        $hasTeamCol = false;
        try {
            $hasTeamCol = DB::getSchemaBuilder()->hasColumn('model_has_roles', $teamKey);
        } catch (\Exception $e) {
            $hasTeamCol = false;
        }
        $ids = null;
        try {
            $q = DB::table('model_has_roles as mhr')->join('roles as r', 'r.id', '=', 'mhr.role_id')->where('mhr.model_type', User::class)->whereIn('r.name', $roleNames);
            if ($hasTeamCol)
                $q->where("mhr.{$teamKey}", $unit->id);
            $ids = $q->pluck('mhr.model_id')->unique()->values()->all();
        } catch (\Exception $e) {
            $ids = null;
        }
        $uq = User::where('unit_id', $unit->id);
        if (is_array($ids))
            $uq->whereIn('id', $ids);
        else
            $uq->whereHas('roles', fn($q) => $q->whereIn('name', $roleNames));
        return $uq->with('person', 'roles')->first();
    }
}