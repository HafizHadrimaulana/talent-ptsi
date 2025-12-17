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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();
        $isSuperadmin = $me?->hasRole('Superadmin');
        $isDhc = $me?->hasRole('DHC');
        $canSeeAll = $isSuperadmin || $isDhc;
        $meUnit = (int) ($me->unit_id ?? 0);
        $isApproverOnly = $me && $me->can('contract.approve') && ! $me->can('contract.update');
        $selectedUnitId = $canSeeAll ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null) : $meUnit;

        $unitsQuery = Unit::query();
        if ($isSuperadmin) $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        elseif ($isDhc)   $unitsQuery->where('category', 'ENABLER');
        $units = $canSeeAll
            ? $unitsQuery->orderBy('name')->get(['id', 'name', 'code', 'category'])
            : Unit::where('id', $selectedUnitId)->get(['id', 'name', 'code', 'category']);

        $positions = DB::table('positions')->select('id','name')->orderBy('name')->get();
        $statusFilter = $request->input('status');
        $searchFilter = $request->input('q');

        $contractsQuery = Contract::query()
            ->with(['unit', 'employee', 'document', 'person', 'applicant'])
            ->when($selectedUnitId, fn ($q) => $q->where('unit_id', $selectedUnitId))
            ->when($isDhc && ! $isSuperadmin, fn ($q) => $q->whereHas('unit', fn ($u) => $u->where('category', 'ENABLER')))
            ->when($isSuperadmin, fn ($q) => $q->whereHas('unit', fn ($u) => $u->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI'])))
            ->when($isApproverOnly, fn ($q) => $q->where('status', '!=', 'draft'))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($searchFilter, function ($q) use ($searchFilter) {
                $q->where(function ($qq) use ($searchFilter) {
                    $qq->where('contract_no', 'like', '%' . $searchFilter . '%')
                        ->orWhere('employment_type', 'like', '%' . $searchFilter . '%')
                        ->orWhereHas('unit', function ($u) use ($searchFilter) {
                            $u->where('name', 'like', '%' . $searchFilter . '%');
                        });
                });
            })
            ->orderByDesc('created_at');

        $contracts = $contractsQuery->paginate(25)->withQueryString();
        $contractTypeConfigs = config('recruitment.contract_types', []);
        $contractTypes = collect($contractTypeConfigs)->mapWithKeys(fn($r) => [$r['code'] => $r['label']])->all();
        $employmentTypes = collect(config('recruitment.employment_types', []))
            ->map(fn($val, $key) => ['value' => $key, 'label' => $val])->values();

        $eligibleStatuses = config('recruitment.contract_applicant_statuses', ['APPROVED']);
        $applicantsQuery = Applicant::query()
            ->leftJoin('units as u', 'u.id', '=', 'applicants.unit_id')
            ->select('applicants.*', 'u.name as unit_name')
            ->whereIn('status', $eligibleStatuses);

        if ($selectedUnitId) $applicantsQuery->where('applicants.unit_id', $selectedUnitId);
        elseif (! $canSeeAll && $meUnit) $applicantsQuery->where('applicants.unit_id', $meUnit);
        
        $applicants = $applicantsQuery->orderBy('full_name')->get();
        $today = Carbon::now()->startOfDay();
        $until = (clone $today)->addDays(30);

        $expiringQuery = DB::table('portfolio_histories AS ph')
            ->leftJoin('employees AS e', 'e.person_id', '=', 'ph.person_id')
            ->leftJoin('units AS u', 'u.id', '=', 'e.unit_id')
            ->select('ph.id', 'ph.person_id', 'e.employee_id', 'ph.title AS position_name', 'ph.organization AS unit_name_raw', 'ph.start_date', 'ph.end_date', 'e.employee_status', 'e.unit_id', 'u.name AS unit_name', DB::raw("(SELECT full_name FROM persons WHERE persons.id = ph.person_id) AS person_name"))
            ->where('ph.category', 'job')
            ->whereNotNull('ph.end_date')
            ->whereDate('ph.end_date', '>=', $today)
            ->whereDate('ph.end_date', '<=', $until);

        if (! $canSeeAll && $meUnit) $expiringQuery->where('e.unit_id', $meUnit);
        $expiringContracts = $expiringQuery->orderBy('ph.end_date', 'asc')->get();

        return view('recruitment.contracts.index', [
            'contracts' => $contracts, 'units' => $units, 'positions' => $positions,
            'selectedUnitId' => $selectedUnitId, 'statusFilter' => $statusFilter, 'searchFilter' => $searchFilter,
            'statusOptions' => config('recruitment.contract_statuses'), 'contractTypes' => $contractTypes,
            'contractTypeConfigs' => $contractTypeConfigs, 'employmentTypes' => $employmentTypes,
            'applicants' => $applicants, 'expiringContracts' => $expiringContracts,
            'canSeeAll' => $canSeeAll, 'currentUser' => $me,
        ]);
    }

    public function baseOptions(Request $request) {
        abort_unless($request->user() && $request->user()->can('contract.view'), 403);
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request) {
        $me = $request->user();
        $request->merge([
            'source_contract_id' => $request->input('source_contract_id') ?: null,
            'unit_id' => $request->input('unit_id') ?: null,
            'new_unit_id' => $request->input('new_unit_id') ?: null,
            'applicant_id' => $request->input('applicant_id') ?: null,
            'employee_id' => $request->input('employee_id') ?: null,
            'person_id' => $request->input('person_id') ?: null,
        ]);

        $validated = $request->validate([
            'contract_type' => 'required|string', 'unit_id' => 'required|integer', 'new_unit_id' => 'nullable|integer',
            'applicant_id' => 'nullable|string', 'employee_id' => 'nullable|string', 'person_id' => 'nullable|string',
            'position_name' => 'nullable|string|max:191', 'employment_type' => 'nullable|string|max:60',
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'remarks' => 'nullable|string|max:2000', 'salary_amount' => 'nullable|string', 'salary_amount_words' => 'nullable|string',
            'lunch_allowance_daily' => 'nullable|string', 'lunch_allowance_words' => 'nullable|string',
            'allowance_special_amount' => 'nullable|string', 'allowance_special_words' => 'nullable|string',
            'allowance_position_amount' => 'nullable|string', 'allowance_position_words' => 'nullable|string',
            'allowance_communication_amount' => 'nullable|string', 'allowance_communication_words' => 'nullable|string',
            'allowance_other_amount' => 'nullable|string', 'allowance_other_words' => 'nullable|string',
            'allowance_other_desc' => 'nullable|string', 'other_benefits_desc' => 'nullable|string',
            'pb_effective_end' => 'nullable|date', 'pb_compensation_amount' => 'nullable|string', 'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit', 'source_contract_id' => 'nullable|integer',
            'requires_draw_signature' => 'nullable', 'requires_camera' => 'nullable', 'requires_geolocation' => 'nullable',
        ]);

        if (!$me->hasRole('Superadmin') && !$me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) return back()->withErrors(['unit_id' => 'Unit tidak valid.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = new Contract();
            $contract->contract_no = null;
            $contract->contract_type = $validated['contract_type'];
            $contract->unit_id = !empty($validated['new_unit_id']) ? $validated['new_unit_id'] : $validated['unit_id'];
            $meta = $this->collectMeta($validated);

            if (in_array($validated['contract_type'], ['SPK','PKWT_BARU']) && $validated['applicant_id']) {
                $app = Applicant::find($validated['applicant_id']);
                if($app) {
                    $contract->applicant_id = $app->id;
                    $contract->person_id = $app->person_id;
                    if(empty($validated['position_name'])) $validated['position_name'] = $app->position_applied;
                }
            } else {
                $contract->employee_id = $validated['employee_id'];
                $contract->person_id = $validated['person_id'];
                if(empty($contract->person_id) && $contract->employee_id) {
                     $emp = Employee::where('employee_id', $contract->employee_id)->first();
                     if($emp) $contract->person_id = $emp->person_id;
                }
                if ($validated['source_contract_id']) {
                    if (Contract::where('id', $validated['source_contract_id'])->exists()) $contract->parent_contract_id = $validated['source_contract_id'];
                    else $meta['legacy_source_id'] = $validated['source_contract_id'];
                }
            }
            if(empty($contract->person_id)) $contract->person_id = $request->input('person_id');
            if (empty($contract->employee_id) && !empty($contract->person_id)) {
                $emp = Employee::where('person_id', $contract->person_id)->first();
                if ($emp) $contract->employee_id = $emp->employee_id;
            }
            
            $contract->position_name = $validated['position_name'] ?? null;
            if ($contract->position_name) {
                $posObj = DB::table('positions')->where('name', $contract->position_name)->first();
                if ($posObj) $contract->position_id = $posObj->id;
            }

            $contract->employment_type = $validated['employment_type'] ?? null;
            $contract->start_date = $validated['start_date'] ?? null;
            $contract->end_date = $validated['end_date'] ?? null;
            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');

            $meta['person_name'] = $this->resolvePersonName($contract);
            if (!empty($validated['new_unit_id'])) {
                $meta['new_unit_id'] = $validated['new_unit_id'];
                $uObj = Unit::find($validated['new_unit_id']);
                $meta['new_unit_name'] = $uObj ? $uObj->name : null;
            }
            $contract->remuneration_json = $meta;

            if ($validated['submit_action'] === 'submit') {
                $contract->status = 'review';
                $contract->contract_no = $this->generateContractNumber($contract);
            } else {
                $contract->status = 'draft';
            }

            $contract->created_by_user_id = $me->id;
            $contract->created_by_person_id = $me->person_id ?? null;
            $contract->save();

            if ($validated['submit_action'] === 'submit') {
                $approval = new Approval();
                $approval->approvable_type = 'contract';
                $approval->approvable_id = $contract->id;
                $approval->requester_person_id = $me->person_id;
                $approval->requester_user_id = $me->id;
                $approval->status = 'pending';
                $approval->note = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
                $approval->save();
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
        if ($contract->status !== 'draft') return back()->withErrors('Hanya draft yang bisa diedit.')->withInput();
        $me = $request->user();
        $request->merge([
            'source_contract_id' => $request->input('source_contract_id') ?: null,
            'unit_id' => $request->input('unit_id') ?: null,
            'new_unit_id' => $request->input('new_unit_id') ?: null,
            'applicant_id' => $request->input('applicant_id') ?: null,
            'employee_id' => $request->input('employee_id') ?: null,
            'person_id' => $request->input('person_id') ?: null,
        ]);
        $validated = $request->validate([
            'contract_type' => 'required|string', 'unit_id' => 'required|integer', 'new_unit_id' => 'nullable|integer',
            'applicant_id' => 'nullable|string', 'employee_id' => 'nullable|string', 'person_id' => 'nullable|string',
            'position_name' => 'nullable|string', 'employment_type' => 'nullable|string',
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date',
            'remarks' => 'nullable|string', 'salary_amount' => 'nullable|string', 'salary_amount_words' => 'nullable|string',
            'lunch_allowance_daily' => 'nullable|string', 'lunch_allowance_words' => 'nullable|string',
            'allowance_special_amount' => 'nullable|string', 'allowance_special_words' => 'nullable|string',
            'allowance_position_amount' => 'nullable|string', 'allowance_position_words' => 'nullable|string',
            'allowance_communication_amount' => 'nullable|string', 'allowance_communication_words' => 'nullable|string',
            'allowance_other_amount' => 'nullable|string', 'allowance_other_words' => 'nullable|string',
            'allowance_other_desc' => 'nullable|string', 'other_benefits_desc' => 'nullable|string',
            'pb_effective_end' => 'nullable|date', 'pb_compensation_amount' => 'nullable|string', 'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit', 'source_contract_id' => 'nullable|integer',
            'requires_draw_signature' => 'nullable', 'requires_camera' => 'nullable', 'requires_geolocation' => 'nullable',
        ]);
        
        DB::beginTransaction();
        try {
            $contract->contract_type = $validated['contract_type'];
            $contract->unit_id = !empty($validated['new_unit_id']) ? $validated['new_unit_id'] : $validated['unit_id'];
            $contract->applicant_id = $validated['applicant_id'] ?? null;
            $contract->employee_id = $validated['employee_id'] ?? null;
            $contract->person_id = $validated['person_id'] ?? null;
            
            $sourceId = $validated['source_contract_id'] ?? null;
            if ($sourceId && Contract::where('id', $sourceId)->exists()) $contract->parent_contract_id = $sourceId;
            if (empty($contract->employee_id) && !empty($contract->person_id)) {
                $emp = Employee::where('person_id', $contract->person_id)->first();
                if ($emp) $contract->employee_id = $emp->employee_id;
            }
            $contract->position_name = $validated['position_name'] ?? null;
            if (!empty($contract->position_name)) {
                $posObj = DB::table('positions')->where('name', $contract->position_name)->first();
                if ($posObj) $contract->position_id = $posObj->id;
            }
            $contract->employment_type = $validated['employment_type'] ?? null;
            $contract->start_date = $validated['start_date'] ?? null;
            $contract->end_date = $validated['end_date'] ?? null;
            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');

            $meta = $this->collectMeta($validated);
            $meta['person_name'] = $this->resolvePersonName($contract);
            if ($sourceId && !Contract::where('id', $sourceId)->exists()) $meta['legacy_source_id'] = $sourceId;
            if (!empty($validated['new_unit_id'])) {
                $meta['new_unit_id'] = $validated['new_unit_id'];
                $uObj = Unit::find($validated['new_unit_id']);
                $meta['new_unit_name'] = $uObj ? $uObj->name : null;
            }
            $contract->remuneration_json = $meta;

            if ($validated['submit_action'] === 'submit') {
                $contract->status = 'review';
                if (!$contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);
                $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->where('status', 'pending')->first();
                if (!$approval) {
                    $approval = new Approval();
                    $approval->approvable_type = 'contract';
                    $approval->approvable_id = $contract->id;
                    $approval->requester_person_id = $me->person_id;
                    $approval->requester_user_id = $me->id;
                    $approval->status = 'pending';
                    $approval->note = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
                    $approval->save();
                }
            }
            $contract->save();
            DB::commit();
            return back()->with('success', 'Berhasil update.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
    
    public function approve(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (!$me->can('contract.approve')) abort(403);
        $needsDraw = ($contract->requires_draw_signature === 1 || $contract->requires_draw_signature === '1' || $contract->requires_draw_signature === true);
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
            'signature_image' => ($needsDraw ? 'required' : 'nullable') . '|string',
        ]);
        
        DB::beginTransaction();
        try {
            $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->where('status', 'pending')->first();
            if ($approval) {
                $approval->approver_person_id = $me->person_id;
                $approval->approver_user_id = $me->id;
                $approval->status = 'approved';
                $approval->note = $validated['note'] ?? null;
                $approval->decided_at = now();
                $approval->save();
            }
            if (!$contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);
            $this->ensureDocumentExists($contract);
            
            $sig = new Signature();
            $sig->document_id = $contract->document_id;
            $sig->signer_person_id = $me->person_id;
            $sig->signer_user_id = $me->id;
            $sig->signer_role = 'Kepala Unit';
            $sig->signature_draw_data = $validated['signature_image'] ?? null;
            $sig->signed_at = now();
            $sig->save();
            
            $contract->status = 'approved';
            $contract->save();
            $this->forceRegeneratePdf($contract);
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Kontrak disetujui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function reject(Request $request, Contract $contract) {
        $contract->status = 'draft';
        $contract->save();
        $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->where('status', 'pending')->first();
        if($approval) {
            $approval->status = 'rejected';
            $approval->note = $request->rejection_note ?? $request->note;
            $approval->decided_at = now();
            $approval->save();
        }
        return response()->json(['success'=>true, 'message'=>'Kontrak direject.']);
    }
    
    public function sign(Request $request, Contract $contract) {
        $me = $request->user();
        if (!$me->can('contract.sign')) abort(403);
        if ($contract->status !== 'approved') return response()->json(['success'=>false, 'message'=>'Status tidak valid.'], 422);
        $needsDraw = ($contract->requires_draw_signature === 1 || $contract->requires_draw_signature === '1' || $contract->requires_draw_signature === true);
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
            'signature_image' => ($needsDraw ? 'required' : 'nullable') . '|string',
        ]);

        DB::beginTransaction();
        try {
            $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->where('status', 'approved')->first();
            if ($approval) {
                $approval->status = 'completed';
                $approval->note = $validated['note'] ?? 'Ditandatangani Kandidat';
                $approval->save();
            }
            $this->ensureDocumentExists($contract);
            
            $sig = new Signature();
            $sig->document_id = $contract->document_id;
            $sig->signer_person_id = $me->person_id;
            $sig->signer_user_id = $me->id;
            $sig->signer_role = 'Kandidat';
            $sig->signature_draw_data = $validated['signature_image'] ?? null;
            $sig->signed_at = now();
            $sig->save();
            $contract->status = 'signed';
            $contract->save();
            $this->forceRegeneratePdf($contract);
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Kontrak ditandatangani.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }
    
    public function submit(Request $request, Contract $contract) {
        $me = $request->user();
        abort_unless($me && $me->can('contract.update'), 403);
        if ($contract->status !== 'draft') return response()->json(['success' => false, 'message' => 'Hanya draft.'], 422);
        DB::beginTransaction();
        try {
            $contract->status = 'review';
            if (!$contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);
            $contract->save();
            $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->where('status', 'pending')->first();
            if (!$approval) {
                $approval = new Approval();
                $approval->approvable_type = 'contract';
                $approval->approvable_id = $contract->id;
                $approval->requester_person_id = $me->person_id;
                $approval->requester_user_id = $me->id;
                $approval->status = 'pending';
                $approval->note = 'Review Kepala Unit';
                $approval->save();
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Disubmit ke review.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function document(Request $request, Contract $contract) {
        $me = $request->user();
        abort_unless($me && $me->can('contract.view'), 403);
        $this->ensureDocumentExists($contract);
        $doc = $contract->document;
        if(!Storage::disk('local')->exists($doc->path)) {
             $doc->delete();
             $contract->document_id = null;
             $contract->save();
             $this->ensureDocumentExists($contract);
             $doc = $contract->document;
        }
        $filename = basename($doc->path);
        return Storage::disk('local')->response($doc->path, $filename, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $filename . '"']);
    }

    public function show(Contract $contract) {
        $contract->load(['unit', 'document', 'person', 'applicant']);
        $meta = $contract->remuneration_json;
        if(is_string($meta)) $meta = json_decode($meta, true);
        $personName = $this->resolvePersonName($contract);
        $docUrl = null;
        if ($contract->document_id || in_array($contract->status, ['approved', 'signed'])) $docUrl = route('recruitment.contracts.document', $contract);
        $typeCfg = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];
        $canApprove = auth()->user()->can('contract.approve') && $contract->status === 'review';
        $canSign = auth()->user()->can('contract.sign') && $contract->status === 'approved';

        return response()->json(['success' => true, 'data' => [
                'id' => $contract->id, 'contract_no' => $contract->contract_no, 'contract_type' => $contract->contract_type,
                'contract_type_label' => $typeCfg['label'] ?? $contract->contract_type, 'status' => $contract->status,
                'unit' => $contract->unit, 'unit_id' => $contract->unit_id, 'person_name' => $personName, 
                'person_id' => $contract->person_id, 'employee_id' => $contract->employee_id, 'applicant_id' => $contract->applicant_id,
                'position_name' => $contract->position_name, 'employment_type' => $contract->employment_type,
                'start_date' => $contract->start_date?->format('d M Y'), 'end_date' => $contract->end_date?->format('d M Y'),
                'remuneration_json' => $meta, 'remarks' => $meta['remarks'] ?? null,
                'can_approve' => $canApprove, 'can_sign' => $canSign, 'doc_url' => $docUrl,
                'approve_url' => route('recruitment.contracts.approve', $contract), 'sign_url' => route('recruitment.contracts.sign', $contract),
                'reject_url' => route('recruitment.contracts.reject', $contract),
                'requires_draw_signature' => (bool)$contract->requires_draw_signature, 'requires_camera' => (bool)$contract->requires_camera,
                'requires_geolocation' => (bool)$contract->requires_geolocation,
        ]]);
    }
    
    protected function resolvePersonName(Contract $contract) {
        if ($contract->relationLoaded('person') && $contract->person && $contract->person->full_name) return $contract->person->full_name;
        if ($contract->relationLoaded('applicant') && $contract->applicant && $contract->applicant->full_name) return $contract->applicant->full_name;
        if ($contract->relationLoaded('employee') && $contract->employee && $contract->employee->person && $contract->employee->person->full_name) return $contract->employee->person->full_name;
        if ($contract->person_id) { $name = DB::table('persons')->where('id', $contract->person_id)->value('full_name'); if ($name) return $name; }
        if ($contract->applicant_id) { $name = DB::table('applicants')->where('id', $contract->applicant_id)->value('full_name'); if ($name) return $name; }
        if ($contract->employee_id) { $personId = DB::table('employees')->where('employee_id', $contract->employee_id)->value('person_id'); if ($personId) { $name = DB::table('persons')->where('id', $personId)->value('full_name'); if ($name) return $name; } }
        $meta = $contract->remuneration_json; if(is_string($meta)) $meta = json_decode($meta, true);
        if(is_array($meta) && !empty($meta['person_name'])) return $meta['person_name'];
        return 'Nama Tidak Ditemukan';
    }
    
    public function terbilang(Request $request) {
        $amount = $request->input('amount', 0); $clean = preg_replace('/\D/', '', $amount); $val = (int) $clean;
        $txt = $this->terbilangIndonesia($val); $res = $txt ? strtoupper($txt . ' RUPIAH') : '';
        return response()->json(['success'=>true, 'data'=>['amount'=>$val, 'words'=>$res]]);
    }

    protected function generateContractNumber(Contract $contract): string {
        $contract->loadMissing('unit');
        $type = $contract->contract_type;
        $cfg = config('recruitment.numbering', []);
        $docCodes = $cfg['doc_codes'] ?? [];
        $defaultHead = $cfg['default_head_code'] ?? 'XX';
        $docCode  = $docCodes[$type] ?? strtoupper($type);
        $unit     = $contract->unit;
        $unitCode = $unit?->code ?? 'UNIT';
        $now    = now();
        $year   = $now->format('Y');
        $month = $now->format('m');
        
        // GET HEAD CODE FROM ACTUAL UNIT HEAD
        $headCode = $defaultHead;
        $unitHead = $this->getUnitHeadUser($unit);
        if ($unitHead) {
            $full = $unitHead->person ? $unitHead->person->full_name : $unitHead->name;
            $parts = preg_split('/\s+/', trim($full));
            if (count($parts) >= 1) {
                $first = mb_substr($parts[0], 0, 1, 'UTF-8');
                $last  = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');
                $headCode = mb_strtoupper($first . $last, 'UTF-8');
            }
        }

        $like = sprintf('%s-%%/%s-%s/%s/%s', $docCode, $unitCode, $month, $headCode, $year);
        $last = Contract::where('contract_type', $contract->contract_type)->where('unit_id', $contract->unit_id)->whereYear('created_at', $year)->whereMonth('created_at', $month)->whereNotNull('contract_no')->where('contract_no', 'like', $like)->orderByDesc('contract_no')->value('contract_no');
        $seq = 1;
        if ($last) {
            $segments = explode('/', $last); $first = $segments[0] ?? ''; $parts = explode('-', $first); $num = $parts[1] ?? 0; $seq = ((int) $num) + 1;
        }
        return sprintf('%s-%03d/%s-%s/%s/%s', $docCode, $seq, $unitCode, $month, $headCode, $year);
    }

    protected function getUnitHeadUser(?Unit $unit) {
        if (!$unit) return null;
        $headRoles = config('recruitment.numbering.head_role_names', ['Kepala Unit', 'GM/VP Unit']);
        return User::query()->join('model_has_roles as mhr', function ($join) use ($unit) {
                $join->on('mhr.model_id', '=', 'users.id')->where('mhr.model_type', '=', User::class)->where('mhr.unit_id', '=', $unit->id);
            })->join('roles', 'roles.id', '=', 'mhr.role_id')->whereIn('roles.name', $headRoles)
            ->where(function ($q) use ($unit) { $q->whereNull('roles.unit_id')->orWhere('roles.unit_id', $unit->id); })
            ->where('users.unit_id', $unit->id)->select('users.*')->with('person')->first();
    }

    protected function collectMeta(array $v, bool $edit = false, array $old = []): array {
        $get = fn ($key) => $v[$key] ?? ($edit ? ($old[$key] ?? null) : null);
        return [
            'remarks' => $get('remarks'), 'position_name' => $get('position_name'),
            'salary_amount' => $get('salary_amount'), 'salary_amount_words' => $get('salary_amount_words'),
            'lunch_allowance_daily' => $get('lunch_allowance_daily'), 'lunch_allowance_words' => $get('lunch_allowance_words'),
            'allowance_special_amount' => $get('allowance_special_amount'), 'allowance_special_words' => $get('allowance_special_words'),
            'allowance_position_amount' => $get('allowance_position_amount'), 'allowance_position_words' => $get('allowance_position_words'),
            'allowance_communication_amount' => $get('allowance_communication_amount'), 'allowance_communication_words' => $get('allowance_communication_words'),
            'allowance_other_amount' => $get('allowance_other_amount'), 'allowance_other_words' => $get('allowance_other_words'),
            'allowance_other_desc' => $get('allowance_other_desc'), 'other_benefits_desc' => $get('other_benefits_desc'),
            'base_contract_start' => $get('base_contract_start'), 'base_contract_end' => $get('base_contract_end'),
            'pb_effective_end' => $get('pb_effective_end'), 'pb_compensation_amount' => $get('pb_compensation_amount'), 'pb_compensation_amount_words' => $get('pb_compensation_amount_words'),
        ];
    }
    
    protected function terbilangIndonesia(int $n): string {
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($n < 12) return $huruf[$n];
        if ($n < 20) return $this->terbilangIndonesia($n - 10) . ' belas';
        if ($n < 100) return $this->terbilangIndonesia((int) ($n / 10)) . ' puluh ' . $this->terbilangIndonesia($n % 10);
        if ($n < 200) return 'seratus ' . $this->terbilangIndonesia($n - 100);
        if ($n < 1000) return $this->terbilangIndonesia((int) ($n / 100)) . ' ratus ' . $this->terbilangIndonesia($n % 100);
        if ($n < 2000) return 'seribu ' . $this->terbilangIndonesia($n - 1000);
        if ($n < 1000000) return $this->terbilangIndonesia((int) ($n / 1000)) . ' ribu ' . $this->terbilangIndonesia($n % 1000);
        if ($n < 1000000000) return $this->terbilangIndonesia((int) ($n / 1000000)) . ' juta ' . $this->terbilangIndonesia($n % 1000000);
        if ($n < 1000000000000) return $this->terbilangIndonesia((int) ($n / 1000000)) . ' miliar ' . $this->terbilangIndonesia($n % 1000000000);
        return (string) $n;
    }

    protected function storeBase64Image(?string $dataUrl, string $disk, string $dir): array {
        if (! $dataUrl || ! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) return [null, null];
        $extension = strtolower($matches[1]);
        $imageData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
        if ($imageData === false) return [null, null];
        $filename = uniqid('sig_', true) . '.' . $extension;
        $path = $dir . '/' . $filename;
        Storage::disk($disk)->put($path, $imageData);
        $hash = hash('sha256', $imageData);
        return [$path, $hash];
    }
    
    protected function forceRegeneratePdf(Contract $contract) {
        $contract->loadMissing('document');
        if ($contract->document && Storage::disk('local')->exists($contract->document->path)) Storage::disk('local')->delete($contract->document->path);
        $this->ensureDocumentExists($contract);
    }

    protected function ensureDocumentExists(Contract $contract) {
        $contract->loadMissing(['person', 'applicant', 'unit']);
        $empId = $contract->employee_id ?? 'NA';
        $ymd   = date('Ymd');
        $type  = $contract->contract_type;
        $fileName = "{$type}-{$empId}-{$ymd}-" . $contract->id . "-" . time() . ".pdf";
        $path = 'contracts/' . $fileName;

        if (!$contract->document_id) {
            $doc = new Document();
            $doc->doc_type = $contract->contract_type;
            $doc->storage_disk = 'local';
            $doc->person_id = $contract->person_id;
            $doc->employee_id = $contract->employee_id;
            $doc->meta = json_encode($contract->remuneration_json ?? []);
            $doc->path = $path; 
            $doc->mime = 'application/pdf';
            $doc->size_bytes = 0;
            $doc->save();
            $contract->document_id = $doc->id;
            $contract->save();
            $contract->refresh(); 
        }
        $doc = $contract->document;
        if ($doc && (!Storage::disk('local')->exists($doc->path) || $contract->status === 'draft')) {
            if(!Storage::disk('local')->exists('contracts')) Storage::disk('local')->makeDirectory('contracts');
            $html = $this->renderPdfHtml($contract);
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            $content = $pdf->output();
            Storage::disk('local')->put($path, $content);
            $doc->path = $path;
            $doc->size_bytes = strlen($content);
            $doc->save();
        }
    }

    protected function renderPdfHtml(Contract $contract): string {
        $map = ['SPK' => 'SPK', 'PKWT_BARU' => 'PKWT', 'PKWT_PERPANJANGAN' => 'PKWT', 'PB_PENGAKHIRAN' => 'PB'];
        $code = $map[$contract->contract_type] ?? 'PKWT';
        $tpl = ContractTemplate::where('code', $code)->where('is_active', true)->first();
        if (!$tpl) return "<h1>Error: Template '$code' belum dibuat.</h1>";
        $vars = $this->getTemplateVars($contract);
        $html = $tpl->body;
        foreach ($vars as $k => $v) $html = str_replace('{{'.$k.'}}', $v, $html);
        return "<html><head><style>@page { margin: 0px; } body { margin: 0px; padding: 0px; } {$tpl->css}</style></head><body>{$html}</body></html>";
    }

    protected function getTemplateVars(Contract $c): array {
        Carbon::setLocale('id');
        $meta = is_string($c->remuneration_json) ? json_decode($c->remuneration_json, true) : ($c->remuneration_json ?? []);
        $signer = $this->resolveSigner($c, $meta);
        $cand = $this->resolveCandidate($c, $meta);
        $fmt = fn($v) => 'Rp ' . number_format((float)preg_replace('/\D/','',$v??0),0,',','.');
        $alw = [];
        if($v=$meta['allowance_position_amount']??0) $alw[] = "T.Jabatan ".$fmt($v);
        if($v=$meta['allowance_communication_amount']??0) $alw[] = "T.Komunikasi ".$fmt($v);
        if($v=$meta['allowance_special_amount']??0) $alw[] = "T.Khusus ".$fmt($v);
        if($v=$meta['allowance_other_amount']??0) $alw[] = "Lainnya ".$fmt($v);

        $signerSignature = ''; $candidateSignature = '';
        if ($c->document_id) {
            $sigs = Signature::where('document_id', $c->document_id)->get();
            $bossSig = $sigs->where('signer_role', 'Kepala Unit')->sortByDesc('id')->first();
            if ($bossSig && $bossSig->signature_draw_data) $signerSignature = "<img src='{$bossSig->signature_draw_data}' style='height: 80px; width: auto;' />";
            $candSig = $sigs->where('signer_role', 'Kandidat')->sortByDesc('id')->first();
            if ($candSig && $candSig->signature_draw_data) $candidateSignature = "<img src='{$candSig->signature_draw_data}' style='height: 80px; width: auto;' />";
        }

        // Hitung durasi (Contoh: 1 Tahun)
        $duration = '-';
        if ($c->start_date && $c->end_date) {
            $diff = $c->start_date->diff($c->end_date);
            $duration = ($diff->y > 0 ? $diff->y . " Tahun " : "") . ($diff->m > 0 ? $diff->m . " Bulan" : "");
            if (!$diff->y && !$diff->m) $duration = $diff->days . " Hari";
        }

        return [
            'contract_no' => $c->contract_no ?? 'DRAFT',
            'today_date' => now()->translatedFormat('d F Y'),
            'day_name' => now()->translatedFormat('l'), 'day_number' => $this->terbilangIndonesia(now()->day),
            'month_name' => now()->translatedFormat('F'), 'year_name' => $this->terbilangIndonesia(now()->year),
            'signer_name' => $signer['name'], 'signer_position' => $signer['position'], 'signer_signature' => $signerSignature,
            'candidate_name' => $cand['name'], 'candidate_address' => $cand['address'], 'candidate_nik' => $cand['nik'],
            'candidate_signature' => $candidateSignature,
            'pob' => $cand['pob'], 'dob' => $cand['dob'], 'gender' => $cand['gender'],
            'position_name' => $c->position_name, 'unit_name' => $meta['new_unit_name'] ?? $c->unit->name ?? '-',
            'employment_type' => $c->employment_type,
            'start_date' => $c->start_date?->translatedFormat('d F Y') ?? '-', 'end_date' => $c->end_date?->translatedFormat('d F Y') ?? '-',
            'duration' => $duration,
            'salary' => $fmt($meta['salary_amount']??0), 'salary_words' => ucwords($meta['salary_amount_words']??''),
            'meal_allowance' => $fmt($meta['lunch_allowance_daily']??0), 'other_benefits' => $meta['other_benefits_desc'] ?? '-',
            'allowance_list' => implode(', ', $alw) ?: '-',
            'pb_date' => isset($meta['pb_effective_end']) ? Carbon::parse($meta['pb_effective_end'])->translatedFormat('d F Y') : '-',
            'pb_amount' => $fmt($meta['pb_compensation_amount']??0), 'pb_words' => ucwords($meta['pb_compensation_amount_words']??''),
        ];
    }

    protected function resolveSigner(Contract $c, array $meta): array {
        if (in_array($c->status, ['approved', 'signed'])) {
            $approval = Approval::where('approvable_id', $c->id)->where('approvable_type', 'contract')->where('status', 'approved')->orderByDesc('decided_at')->first();
            if ($approval && $approval->approver_person_id) {
                $p = DB::table('persons')->where('id', $approval->approver_person_id)->first();
                if ($p) return ['name' => $p->full_name ?? $p->name, 'position' => 'Kepala Unit'];
            }
        }
        $uId = $meta['new_unit_id'] ?? $c->unit_id;
        if ($uId) {
            $boss = $this->getUnitHeadUser(Unit::find($uId));
            if ($boss) return ['name' => $boss->person->full_name ?? $boss->name, 'position' => 'Kepala Unit'];
        }
        return ['name' => config('recruitment.company_rep_name', 'Lussy Ariani Seba'), 'position' => config('recruitment.company_rep_title', 'Direktur SDM')];
    }

    protected function resolveCandidate(Contract $c, array $meta): array {
        $name = $this->resolvePersonName($c);
        $addr = '-'; $nik = '-'; $pob = '-'; $dob = '-'; $gender = '-';
        
        $set = function($m) use (&$addr, &$nik, &$pob, &$dob, &$gender) {
            if(!$m) return;
            $addr = $m->address ?? $addr;
            $nik = $m->nik ?? ($m->nik_e_ktp ?? $nik); // Full NIK
            $pob = $m->place_of_birth ?? $pob;
            $dob = $m->date_of_birth ? Carbon::parse($m->date_of_birth)->translatedFormat('d F Y') : $dob;
            $gender = $m->gender === 'L' ? 'Laki-laki' : ($m->gender === 'P' ? 'Perempuan' : $gender);
        };

        if ($c->person) $set($c->person);
        elseif ($c->applicant) $set($c->applicant);
        
        return ['name' => $name, 'address' => $addr, 'nik' => $nik, 'pob' => $pob, 'dob' => $dob, 'gender' => $gender];
    }
}