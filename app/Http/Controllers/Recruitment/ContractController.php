<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Contract;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            ->with(['unit'])
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
        $contractTypes = collect($contractTypeConfigs)->mapWithKeys(function ($row) {
            $code  = $row['code'] ?? '';
            $label = $row['label'] ?? $code;
            return [$code => $label];
        })->all();

        $employmentTypes = collect();
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'employee_status')) {
            $employmentRows = DB::table('employees')
                ->select('employee_status')
                ->whereNotNull('employee_status')
                ->distinct()
                ->orderBy('employee_status')
                ->get();

            $employmentTypes = $employmentRows->map(function ($row) {
                $label = trim($row->employee_status);
                return ['value' => $label, 'label' => $label];
            })->values();
        }
        if ($employmentTypes->isEmpty()) {
            $cfg = config('recruitment.employment_types', []);
            $employmentTypes = collect($cfg)->map(function ($label, $code) {
                return ['value' => $code, 'label' => $label];
            })->values();
        }

        $eligibleStatuses = config('recruitment.contract_applicant_statuses', ['APPROVED']);
        $applicantsQuery = Applicant::query()
            ->leftJoin('units as u', 'u.id', '=', 'applicants.unit_id')
            ->select('applicants.*', 'u.name as unit_name')
            ->whereIn('status', $eligibleStatuses);

        if ($selectedUnitId) {
            $applicantsQuery->where('applicants.unit_id', $selectedUnitId);
        } elseif (!$canSeeAll && $meUnit) {
            $applicantsQuery->where('applicants.unit_id', $meUnit);
        }
        if ($isDhc && ! $isSuperadmin) $applicantsQuery->where('u.category', 'ENABLER');
        if ($isSuperadmin) $applicantsQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        $applicants = $applicantsQuery->orderBy('full_name')->get();

        $today = Carbon::now()->startOfDay();
        $until = (clone $today)->addDays(30);

        $expiringQuery = DB::table('portfolio_histories AS ph')
            ->leftJoin('employees AS e', 'e.person_id', '=', 'ph.person_id')
            ->leftJoin('units AS u', 'u.id', '=', 'e.unit_id')
            ->select(
                'ph.id',
                'ph.person_id',
                'ph.employee_id',
                'ph.title AS position_name',
                'ph.organization AS unit_name_raw',
                'ph.start_date',
                'ph.end_date',
                'e.employee_status',
                'e.unit_id',
                'u.name AS unit_name',
                DB::raw("(SELECT full_name FROM persons WHERE persons.id = ph.person_id) AS person_name")
            )
            ->where('ph.category', 'job')
            ->whereNotNull('ph.end_date')
            ->whereDate('ph.end_date', '>=', $today)
            ->whereDate('ph.end_date', '<=', $until);

        if (! $canSeeAll && $meUnit) $expiringQuery->where('e.unit_id', $meUnit);
        if ($isDhc && ! $isSuperadmin) $expiringQuery->where('u.category', 'ENABLER');
        if ($isSuperadmin) $expiringQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        $expiringContracts = $expiringQuery->orderBy('ph.end_date')->get();

        return view('recruitment.contracts.index', [
            'contracts'               => $contracts,
            'units'                   => $units,
            'positions'               => $positions,
            'selectedUnitId'          => $selectedUnitId,
            'statusFilter'            => $statusFilter,
            'statusOptions'           => config('recruitment.contract_statuses'),
            'contractTypes'           => $contractTypes,
            'contractTypeConfigs'     => $contractTypeConfigs,
            'employmentTypes'         => $employmentTypes,
            'applicants'              => $applicants,
            'expiringContracts'       => $expiringContracts,
            'canSeeAll'               => $canSeeAll,
            'currentUser'             => $me,
        ]);
    }

    public function table(Request $request)
    {
        $me = $request->user();
        $isSuperadmin = $me?->hasRole('Superadmin');
        $isDhc = $me?->hasRole('DHC');
        $canSeeAll = $isSuperadmin || $isDhc;
        $selectedUnitId = $canSeeAll ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null) : (int) ($me->unit_id ?? 0);

        $qType   = trim((string) $request->get('type', ''));
        $qStatus = trim((string) $request->get('status', ''));
        $qSearch = trim((string) $request->get('q', ''));

        $rows = Contract::query()
            ->with('unit')
            ->when($selectedUnitId, fn($q)=>$q->where('unit_id',$selectedUnitId))
            ->when($qType !== '', fn($q)=>$q->where('contract_type',$qType))
            ->when($qStatus !== '', fn($q)=>$q->where('status',$qStatus))
            ->when($qSearch !== '', function($q) use($qSearch){
                $q->where(function($w) use($qSearch){
                    $w->where('contract_no','like',"%{$qSearch}%")
                      ->orWhere('position_name','like',"%{$qSearch}%")
                      ->orWhere('employment_type','like',"%{$qSearch}%");
                })->orWhereHas('unit', fn($u)=>$u->where('name','like',"%{$qSearch}%"));
            })
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $typeMap = collect(config('recruitment.contract_types', []))
            ->mapWithKeys(fn($r)=>[($r['code'] ?? '')=>($r['label'] ?? ($r['code'] ?? ''))])
            ->all();

        $data = [];
        foreach ($rows as $c) {
            $personName = null;
            if ($c->person_id) {
                $p = DB::table('persons')->select('full_name')->where('id',$c->person_id)->first();
                $personName = $p?->full_name;
            }
            if (!$personName && $c->employee_id) {
                $e = DB::table('employees as e')->leftJoin('persons as p','p.id','=','e.person_id')
                    ->select('p.full_name')->where('e.employee_id',$c->employee_id)->first();
                $personName = $e?->full_name;
            }
            $amount = (string) data_get($c->remuneration_json,'salary_amount','');
            $amount = is_numeric($amount) ? (int) $amount : (int) preg_replace('/\D+/','',$amount);
            $data[] = [
                'id'          => $c->id,
                'no'          => $c->contract_no ?: '-',
                'type'        => $typeMap[$c->contract_type] ?? $c->contract_type,
                'status'      => Str::upper($c->status),
                'unit'        => $c->unit?->name ?: '-',
                'person'      => $personName ?: '-',
                'position'    => $c->position_name ?: data_get($c->remuneration_json,'position_name','-'),
                'emp_type'    => $c->employment_type ?: '-',
                'emp_status'  => DB::table('employees')->where('employee_id',$c->employee_id)->value('employee_status') ?: '-',
                'start_date'  => optional($c->start_date)->format('Y-m-d') ?: '-',
                'end_date'    => optional($c->end_date)->format('Y-m-d') ?: '-',
                'amount'      => $amount,
                'created_at'  => optional($c->created_at)->format('Y-m-d H:i'),
                'actions'     => [
                    'show'    => route('recruitment.contracts.show', $c),
                    'submit'  => route('recruitment.contracts.submit', $c),
                    'approve' => route('recruitment.contracts.approve', $c),
                    'reject'  => route('recruitment.contracts.reject', $c),
                    'sign'    => route('recruitment.contracts.sign', $c),
                    'update'  => route('recruitment.contracts.update', $c),
                ],
            ];
        }

        return response()->json(['success'=>true,'data'=>$data]);
    }

    public function baseOptions(Request $request)
    {
        $me = $request->user();
        if (! $me->can('contract.view')) abort(403, 'Akses ditolak.');

        $unitId     = $request->integer('unit_id') ?: null;
        $personId   = $request->get('person_id') ?: null;
        $employeeId = $request->get('employee_id') ?: null;
        $q          = trim((string) $request->get('q', ''));
        $limit      = (int) $request->integer('limit', 30);

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            $unitId = $unitId ?: $me->unit_id;
            if ((int)$unitId !== (int)$me->unit_id) abort(403, 'Akses unit tidak sesuai.');
        }

        $today = Carbon::today();
        $until = (clone $today)->addDays(30);

        $rows = Contract::query()
            ->leftJoin('persons as p', 'p.id', '=', 'contracts.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'contracts.unit_id')
            ->select([
                'contracts.id',
                'contracts.start_date',
                'contracts.end_date',
                'contracts.unit_id',
                'contracts.contract_no',
                'contracts.position_name',
                'p.full_name as person_name',
                'u.name as unit_name',
            ])
            ->when($employeeId, fn($qq) => $qq->where('contracts.employee_id', $employeeId))
            ->when(!$employeeId && $personId, fn($qq) => $qq->where('contracts.person_id', $personId))
            ->when($unitId, fn($qq) => $qq->where('contracts.unit_id', $unitId))
            ->whereIn('contracts.status', ['signed','approved','review'])
            ->whereIn('contracts.contract_type', ['SPK','PKWT_BARU','PKWT_PERPANJANGAN'])
            ->whereDate('contracts.end_date', '>=', $today)
            ->whereDate('contracts.end_date', '<=', $until)
            ->when($q !== '', function($qq) use ($q) {
                $qq->where(function($w) use ($q) {
                    $w->where('contracts.contract_no','like',"%{$q}%")
                      ->orWhere('contracts.position_name','like',"%{$q}%")
                      ->orWhere('p.full_name','like',"%{$q}%");
                });
            })
            ->orderBy('contracts.end_date')
            ->orderBy('contracts.start_date')
            ->limit($limit)
            ->get();

        $out = $rows->map(function($c){
            $sd = $c->start_date ? Carbon::parse($c->start_date)->format('d M Y') : '-';
            $ed = $c->end_date   ? Carbon::parse($c->end_date)->format('d M Y') : '-';
            $name = $c->person_name ?: '—';
            $pos  = $c->position_name ?: 'Tanpa Jabatan';
            $no   = $c->contract_no ?: $c->id;
            $unit = $c->unit_name ? (' · '.$c->unit_name) : '';
            $label = "{$name} — {$pos} ({$sd} s/d {$ed}){$unit}";
            return ['id' => $c->id, 'text' => $label, 'no' => $no];
        });

        return response()->json(['results' => $out]);
    }

    public function store(Request $request)
    {
        $me = $request->user();

        $validated = $request->validate([
            'contract_type'                   => 'required|string',
            'unit_id'                         => 'required|integer|exists:units,id',
            'applicant_id'                    => 'nullable|string',
            'employee_id'                     => 'nullable|string',
            'person_id'                       => 'nullable|string',
            'position_id'                     => 'nullable|integer',
            'position_level_id'               => 'nullable|integer',
            'position_name'                   => 'nullable|string|max:191',
            'employment_type'                 => 'nullable|string|max:60',
            'start_date'                      => 'nullable|date',
            'end_date'                        => 'nullable|date|after_or_equal:start_date',
            'remarks'                         => 'nullable|string|max:2000',
            'salary_amount'                   => 'nullable|string',
            'salary_amount_words'             => 'nullable|string',
            'lunch_allowance_daily'           => 'nullable|string',
            'lunch_allowance_words'           => 'nullable|string',
            'allowance_special_amount'        => 'nullable|string',
            'allowance_special_words'         => 'nullable|string',
            'allowance_position_amount'       => 'nullable|string',
            'allowance_position_words'        => 'nullable|string',
            'allowance_communication_amount'  => 'nullable|string',
            'allowance_communication_words'   => 'nullable|string',
            'allowance_other_amount'          => 'nullable|string',
            'allowance_other_words'           => 'nullable|string',
            'allowance_other_desc'            => 'nullable|string',
            'other_benefits_desc'             => 'nullable|string',
            'base_contract_start'             => 'nullable|date',
            'base_contract_end'               => 'nullable|date',
            'pb_effective_end'                => 'nullable|date',
            'pb_compensation_amount'          => 'nullable|string',
            'pb_compensation_amount_words'    => 'nullable|string',
            'submit_action'                   => 'required|in:draft,submit',
            'source_contract_id'              => 'nullable|integer',
            'expiring_id'                     => 'nullable|integer',
            'expiring_end'                    => 'nullable|date',
        ]);

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                return back()->withErrors(['unit_id' => 'Tidak boleh membuat kontrak di luar unit Anda.'])->withInput();
            }
        }

        $ct = $validated['contract_type'];
        $isNewFromApplicant = in_array($ct, ['SPK','PKWT_BARU'], true);
        $isFromEmployee     = in_array($ct, ['PKWT_PERPANJANGAN','PB_PENGAKHIRAN'], true);

        if ($isNewFromApplicant && empty($validated['applicant_id'])) {
            return back()->withErrors(['applicant_id' => 'Pelamar wajib dipilih untuk jenis kontrak ini.'])->withInput();
        }
        if ($isFromEmployee) {
            if (empty($validated['employee_id']) && empty($validated['person_id']) && empty($validated['source_contract_id']) && empty($validated['expiring_id'])) {
                return back()->withErrors(['employee_id' => 'Pegawai existing wajib dipilih atau pilih Kontrak Dasar.'])->withInput();
            }
        }

        $applicant = null;
        if ($isNewFromApplicant) {
            $applicant = Applicant::find($validated['applicant_id']);
            if (! $applicant) {
                return back()->withErrors(['applicant_id' => 'Data pelamar tidak ditemukan.'])->withInput();
            }
        }

        $baseContract = null;
        if ($isFromEmployee) {
            $baseContract = $this->resolveBaseContractForType(
                $validated['source_contract_id'] ?? null,
                $validated['expiring_id'] ?? null,
                $validated['expiring_end'] ?? null,
                $validated['employee_id'] ?? null,
                $validated['person_id'] ?? null,
                (int)$validated['unit_id']
            );
            if (! $baseContract && empty($validated['employee_id']) && empty($validated['person_id'])) {
                return back()->withErrors(['source_contract_id' => 'Kontrak dasar tidak ditemukan untuk pegawai/unit ini.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $contract = new Contract();
            $contract->contract_no = null;
            $contract->contract_type = $ct;
            $contract->unit_id = $validated['unit_id'];

            if ($isNewFromApplicant) {
                $this->fillSubjectFromRequest($contract, ['person_id' => $applicant->person_id ?? null], null, $applicant);
                $contract->applicant_id = $applicant->id;
                if (! empty($validated['position_name'])) {
                    $contract->position_name = $validated['position_name'];
                } elseif (! empty($applicant->position_name)) {
                    $contract->position_name = $applicant->position_name;
                }
                $contract->employment_type = $validated['employment_type'] ?? $contract->employment_type;
            } else {
                $this->fillSubjectFromRequest($contract, $validated, $baseContract, null);
                if (empty($validated['employment_type']) && $contract->employee_id) {
                    $emp = Employee::where('employee_id', $contract->employee_id)->first();
                    $contract->employment_type = $this->mapEmploymentType($emp->employee_status ?? null);
                } else {
                    $contract->employment_type = $validated['employment_type'] ?? $contract->employment_type;
                }
                if (!empty($validated['position_name'])) {
                    $contract->position_name = $validated['position_name'];
                }
                if ($baseContract) {
                    $contract->parent_contract_id = $baseContract->id;
                }
            }

            $contract->start_date = $validated['start_date'] ?? null;
            $contract->end_date   = $validated['end_date'] ?? null;

            $meta = $this->collectMeta($validated);
            if ($isNewFromApplicant && empty($meta['position_name']) && !empty($contract->position_name)) {
                $meta['position_name'] = $contract->position_name;
            }
            if ($baseContract) {
                $meta['base_contract_start'] = $meta['base_contract_start'] ?? optional($baseContract->start_date)->format('Y-m-d');
                $meta['base_contract_end']   = $meta['base_contract_end']   ?? optional($baseContract->end_date)->format('Y-m-d');
            }

            $contract->remuneration_json = $meta;
            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');

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

            return redirect()
                ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
                ->with('success', $validated['submit_action'] === 'submit'
                    ? 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.'
                    : 'Draft kontrak berhasil dibuat.'
                );

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating contract: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat kontrak: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) abort(403, 'Akses ditolak.');
        }
        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya draft yang bisa diedit.')->withInput();
        }

        $validated = $request->validate([
            'contract_type'                   => 'nullable|string',
            'unit_id'                         => 'required|integer|exists:units,id',
            'applicant_id'                    => 'nullable|string',
            'employee_id'                     => 'nullable|string',
            'person_id'                       => 'nullable|string',
            'position_id'                     => 'nullable|integer',
            'position_level_id'               => 'nullable|integer',
            'position_name'                   => 'nullable|string|max:191',
            'employment_type'                 => 'nullable|string|max:60',
            'start_date'                      => 'nullable|date',
            'end_date'                        => 'nullable|date|after_or_equal:start_date',
            'remarks'                         => 'nullable|string|max:2000',
            'salary_amount'                   => 'nullable|string',
            'salary_amount_words'             => 'nullable|string',
            'lunch_allowance_daily'           => 'nullable|string',
            'lunch_allowance_words'           => 'nullable|string',
            'allowance_special_amount'        => 'nullable|string',
            'allowance_special_words'         => 'nullable|string',
            'allowance_position_amount'       => 'nullable|string',
            'allowance_position_words'        => 'nullable|string',
            'allowance_communication_amount'  => 'nullable|string',
            'allowance_communication_words'   => 'nullable|string',
            'allowance_other_amount'          => 'nullable|string',
            'allowance_other_words'           => 'nullable|string',
            'allowance_other_desc'            => 'nullable|string',
            'other_benefits_desc'             => 'nullable|string',
            'base_contract_start'             => 'nullable|date',
            'base_contract_end'               => 'nullable|date',
            'pb_effective_end'                => 'nullable|date',
            'pb_compensation_amount'          => 'nullable|string',
            'pb_compensation_amount_words'    => 'nullable|string',
            'source_contract_id'              => 'nullable|integer',
            'expiring_id'                     => 'nullable|integer',
            'expiring_end'                    => 'nullable|date',
            'submit_action'                   => 'required|in:draft,submit',
            'requires_draw_signature'         => 'nullable',
            'requires_camera'                 => 'nullable',
            'requires_geolocation'            => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            if (! empty($validated['contract_type'])) $contract->contract_type = $validated['contract_type'];
            $contract->unit_id = $validated['unit_id'];

            $ct = $contract->contract_type;
            $isNewFromApplicant = in_array($ct, ['SPK','PKWT_BARU'], true);
            $isFromEmployee     = in_array($ct, ['PKWT_PERPANJANGAN','PB_PENGAKHIRAN'], true);

            if ($isNewFromApplicant) {
                if (empty($validated['applicant_id'])) {
                    DB::rollBack();
                    return back()->withErrors(['applicant_id' => 'Pelamar wajib dipilih untuk jenis kontrak ini.'])->withInput();
                }
                $applicant = Applicant::find($validated['applicant_id']);
                if (! $applicant) {
                    DB::rollBack();
                    return back()->withErrors(['applicant_id' => 'Data pelamar tidak ditemukan.'])->withInput();
                }
                $this->fillSubjectFromRequest($contract, ['person_id' => $applicant->person_id ?? null], null, $applicant);
                $contract->applicant_id = $applicant->id;
                if (array_key_exists('employment_type', $validated)) $contract->employment_type = $validated['employment_type'] ?: null;
                if (array_key_exists('position_name', $validated)) $contract->position_name = $validated['position_name'] ?: ($applicant->position_name ?? null);
            } else {
                $this->fillSubjectFromRequest($contract, $validated);
                if ($isFromEmployee) {
                    $baseContract = $this->resolveBaseContractForType(
                        $validated['source_contract_id'] ?? $contract->parent_contract_id,
                        $validated['expiring_id'] ?? null,
                        $validated['expiring_end'] ?? null,
                        $validated['employee_id'] ?? $contract->employee_id,
                        $validated['person_id'] ?? $contract->person_id,
                        (int)$validated['unit_id']
                    );
                    if (! $baseContract && empty($validated['employee_id']) && empty($validated['person_id']) && empty($contract->employee_id) && empty($contract->person_id)) {
                        DB::rollBack();
                        return back()->withErrors(['source_contract_id' => 'Kontrak dasar tidak ditemukan untuk pegawai/unit ini.'])->withInput();
                    }
                    if ($baseContract) $contract->parent_contract_id = $baseContract->id;
                }
                if (array_key_exists('employment_type', $validated)) {
                    if ($validated['employment_type'] === null || $validated['employment_type'] === '') {
                        if ($contract->employee_id) {
                            $emp = Employee::where('employee_id', $contract->employee_id)->first();
                            $contract->employment_type = $this->mapEmploymentType($emp->employee_status ?? null);
                        }
                    } else {
                        $contract->employment_type = (string) $validated['employment_type'];
                    }
                }
                if (array_key_exists('position_name', $validated)) $contract->position_name = $validated['position_name'] ?: null;
            }

            $contract->start_date = $validated['start_date'] ?? null;
            $contract->end_date   = $validated['end_date'] ?? null;

            $metaOld = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            $typeNow = $contract->contract_type;

            if ($typeNow === 'PB_PENGAKHIRAN') {
                $metaNew = [
                    'remarks'                        => $validated['remarks'] ?? ($metaOld['remarks'] ?? null),
                    'position_name'                  => $validated['position_name'] ?? ($metaOld['position_name'] ?? null),
                    'pb_effective_end'               => $validated['pb_effective_end'] ?? null,
                    'pb_compensation_amount'         => $validated['pb_compensation_amount'] ?? null,
                    'pb_compensation_amount_words'   => $validated['pb_compensation_amount_words'] ?? null,
                    'salary_amount'                  => null,
                    'salary_amount_words'            => null,
                    'lunch_allowance_daily'          => null,
                    'lunch_allowance_words'          => null,
                    'allowance_special_amount'       => null,
                    'allowance_special_words'        => null,
                    'allowance_position_amount'      => null,
                    'allowance_position_words'       => null,
                    'allowance_communication_amount' => null,
                    'allowance_communication_words'  => null,
                    'allowance_other_amount'         => null,
                    'allowance_other_words'          => null,
                    'allowance_other_desc'           => null,
                    'other_benefits_desc'            => null,
                    'base_contract_start'            => null,
                    'base_contract_end'              => null,
                ];
            } else {
                $metaNew = $this->collectMeta($validated, true, $metaOld);
            }

            if ($isNewFromApplicant && empty($metaNew['position_name']) && !empty($contract->position_name)) $metaNew['position_name'] = $contract->position_name;
            if (array_key_exists('remarks', $validated)) $metaNew['remarks'] = $validated['remarks'] ?? ($metaOld['remarks'] ?? null);
            $contract->remuneration_json = array_merge($metaOld, $metaNew);

            $contract->requires_draw_signature = $request->has('requires_draw_signature');
            $contract->requires_camera = $request->has('requires_camera');
            $contract->requires_geolocation = $request->has('requires_geolocation');

            if ($validated['submit_action'] === 'submit') {
                $contract->status = 'review';
                if (! $contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);
                $approval = new Approval();
                $approval->approvable_type = 'contract';
                $approval->approvable_id = $contract->id;
                $approval->requester_person_id = $me->person_id;
                $approval->requester_user_id = $me->id;
                $approval->status = 'pending';
                $approval->note = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
                $approval->save();
            }

            $contract->save();
            DB::commit();

            return back()->with('success', $validated['submit_action'] === 'submit'
                ? 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.'
                : 'Draft kontrak diperbarui.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating contract: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memperbarui kontrak: ' . $e->getMessage()])->withInput();
        }
    }

    public function submit(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (! $me->can('contract.update')) abort(403, 'Akses ditolak.');
        if ($contract->status !== 'draft') return response()->json(['success' => false, 'message' => 'Hanya draft yang bisa disubmit.'], 422);
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) abort(403, 'Akses ditolak.');
        }

        DB::beginTransaction();
        try {
            $contract->status = 'review';
            if (! $contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);
            $approval = new Approval();
            $approval->approvable_type = 'contract';
            $approval->approvable_id = $contract->id;
            $approval->requester_person_id = $me->person_id;
            $approval->requester_user_id = $me->id;
            $approval->status = 'pending';
            $approval->note = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
            $approval->save();

            $contract->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kontrak disubmit dan menunggu review Kepala Unit.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error submitting contract: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal submit kontrak: ' . $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (! $me->can('contract.approve')) abort(403, 'Akses ditolak.');
        if ($contract->status !== 'review') return response()->json(['success' => false, 'message' => 'Hanya kontrak status REVIEW yang bisa di-approve.'], 422);
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) abort(403, 'Akses ditolak.');
        }

        $needsDraw = (bool) $contract->requires_draw_signature;
        $rules = [
            'note'            => 'nullable|string|max:2000',
            'signature_image' => ($needsDraw ? 'required' : 'nullable') . '|string',
            'camera_image'    => 'nullable|string',
            'geo_lat'         => 'nullable|numeric',
            'geo_lng'         => 'nullable|numeric',
            'geo_accuracy'    => 'nullable|numeric',
        ];
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->where('status', 'pending')
                ->first();

            if (! $approval) throw new \Exception('Approval record tidak ditemukan.');

            $approval->approver_person_id = $me->person_id;
            $approval->approver_user_id = $me->id;
            $approval->status = 'approved';
            $approval->note = $validated['note'] ?? null;
            $approval->decided_at = now();
            $approval->save();

            if (! $contract->contract_no) $contract->contract_no = $this->generateContractNumber($contract);

            $typeCfg  = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];
            $docType  = $typeCfg['document_type'] ?? $contract->contract_type;
            $docTitle = $typeCfg['label'] ?? ('Kontrak ' . $contract->contract_type);

            if ($contract->document_id) {
                $document = Document::find($contract->document_id);
            } else {
                $document = new Document();
                $document->doc_type = $docType;
                $document->doc_no = $contract->contract_no;
                $document->title = $docTitle;
                $document->storage_disk = 'local';
                $document->path = 'contracts/' . $contract->id . '.pdf';
                $document->mime = 'application/pdf';
                $document->size_bytes = 0;
                $document->meta_json = $contract->remuneration_json ?? [];
                $document->created_by_user_id = $me->id;
                $document->created_by_person_id = $me->person_id ?? null;
                $document->save();
                $contract->document_id = $document->id;
            }

            $imgPath = null; $imgHash = null;
            $camPath = null; $camHash = null;
            if (! empty($validated['signature_image'])) [$imgPath, $imgHash] = $this->storeBase64Image($validated['signature_image'], 'public', 'signatures');
            if (! empty($validated['camera_image']))    [$camPath, $camHash] = $this->storeBase64Image($validated['camera_image'], 'public', 'signatures/camera');

            if ($imgPath || $camPath) {
                $signature = new Signature();
                $signature->document_id = $document->id;
                $signature->signer_person_id = $me->person_id ?? null;
                $signature->signer_user_id = $me->id;
                $signature->signer_role = 'Kepala Unit';
                $signature->signature_draw_data = $validated['signature_image'] ?? null;
                $signature->signature_draw_hash = $imgHash;
                $signature->camera_photo_path = $camPath;
                $signature->camera_photo_hash = $camHash;
                $signature->geo_lat = $validated['geo_lat'] ?? null;
                $signature->geo_lng = $validated['geo_lng'] ?? null;
                $signature->geo_accuracy_m = $validated['geo_accuracy'] ?? null;
                $signature->signed_at = now();
                $signature->save();
            }

            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) $meta['approval_note'] = $validated['note'];
            $contract->remuneration_json = $meta;
            $contract->status = 'approved';
            $contract->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kontrak telah di-approve dan e-sign tersimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving contract: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memproses approval kontrak: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (! $me->can('contract.approve')) abort(403, 'Akses ditolak.');
        if ($contract->status !== 'review') return response()->json(['success' => false, 'message' => 'Hanya kontrak status REVIEW yang bisa dikembalikan ke draft.'], 422);
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        DB::beginTransaction();
        try {
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->where('status', 'pending')
                ->first();

            if ($approval) {
                $approval->approver_person_id = $me->person_id;
                $approval->approver_user_id = $me->id;
                $approval->status = 'rejected';
                $approval->note = $validated['note'] ?? 'Dikembalikan ke SDM Unit untuk revisi';
                $approval->decided_at = now();
                $approval->save();
            }

            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) $meta['reject_note'] = $validated['note'];
            $contract->remuneration_json = $meta;
            $contract->status = 'draft';
            $contract->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kontrak dikembalikan ke draft SDM Unit.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting contract: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memproses reject kontrak: ' . $e->getMessage()], 500);
        }
    }

    public function sign(Request $request, Contract $contract)
    {
        $me = $request->user();
        if (! $me->can('contract.sign')) abort(403, 'Akses ditolak.');
        if ($contract->status !== 'approved') return response()->json(['success' => false, 'message' => 'Hanya kontrak status APPROVED yang bisa ditandatangani kandidat.'], 422);
        if ($contract->person_id && $contract->person_id !== $me->person_id) {
            if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) return response()->json(['success' => false, 'message' => 'Hanya kandidat terkait yang bisa menandatangani kontrak.'], 403);
        }

        $needsDraw = (bool) $contract->requires_draw_signature;
        $rules = [
            'note'            => 'nullable|string|max:2000',
            'signature_image' => ($needsDraw ? 'required' : 'nullable') . '|string',
            'camera_image'    => 'nullable|string',
            'geo_lat'         => 'nullable|numeric',
            'geo_lng'         => 'nullable|numeric',
            'geo_accuracy'    => 'nullable|numeric',
        ];
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->whereIn('status', ['approved', 'pending'])
                ->first();

            if ($approval) {
                $approval->status = 'completed';
                $approval->note = $validated['note'] ?? 'Ditandatangani oleh kandidat';
                $approval->save();
            }

            if (! $contract->document_id) {
                $typeCfg  = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];
                $docType  = $typeCfg['document_type'] ?? $contract->contract_type;
                $docTitle = $typeCfg['label'] ?? ('Kontrak ' . $contract->contract_type);

                $document = new Document();
                $document->doc_type = $docType;
                $document->doc_no = $contract->contract_no;
                $document->title = $docTitle;
                $document->storage_disk = 'local';
                $document->path = 'contracts/' . $contract->id . '.pdf';
                $document->mime = 'application/pdf';
                $document->size_bytes = 0;
                $document->meta_json = $contract->remuneration_json ?? [];
                $document->created_by_user_id = $me->id;
                $document->created_by_person_id = $me->person_id ?? null;
                $document->save();

                $contract->document_id = $document->id;
                $contract->save();
            } else {
                $document = Document::find($contract->document_id);
            }

            $imgPath = null; $imgHash = null;
            $camPath = null; $camHash = null;

            if (! empty($validated['signature_image'])) [$imgPath, $imgHash] = $this->storeBase64Image($validated['signature_image'], 'public', 'signatures');
            if (! empty($validated['camera_image']))    [$camPath, $camHash] = $this->storeBase64Image($validated['camera_image'], 'public', 'signatures/camera');

            if ($imgPath || $camPath) {
                $signature = new Signature();
                $signature->document_id = $document->id;
                $signature->signer_person_id = $me->person_id ?? null;
                $signature->signer_user_id = $me->id;
                $signature->signer_role = 'Kandidat';
                $signature->signature_draw_data = $validated['signature_image'] ?? null;
                $signature->signature_draw_hash = $imgHash;
                $signature->camera_photo_path = $camPath;
                $signature->camera_photo_hash = $camHash;
                $signature->geo_lat = $validated['geo_lat'] ?? null;
                $signature->geo_lng = $validated['geo_lng'] ?? null;
                $signature->geo_accuracy_m = $validated['geo_accuracy'] ?? null;
                $signature->signed_at = now();
                $signature->save();
            }

            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) $meta['candidate_note'] = $validated['note'];
            $contract->remuneration_json = $meta;
            $contract->status = 'signed';
            $contract->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Kontrak telah ditandatangani kandidat.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error signing contract: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan e-sign kandidat: ' . $e->getMessage()], 500);
        }
    }

    public function show(Contract $contract)
    {
        $me = auth()->user();
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) abort(403, 'Akses ditolak.');
        }

        $contract->load('unit');
        $meta = $contract->remuneration_json ?? [];

        $person = null;
        if ($contract->person_id) $person = DB::table('persons')->select('id', 'full_name', 'nik_last4')->where('id', $contract->person_id)->first();

        $employee = null;
        if ($contract->employee_id) {
            $employee = DB::table('employees as e')
                ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
                ->select('e.id', 'e.employee_id', 'e.email', 'e.employee_status', 'p.full_name as person_name')
                ->where('e.employee_id', $contract->employee_id)
                ->first();
        }

        $applicant = null;
        if (Schema::hasColumn('contracts', 'applicant_id') && ! empty($contract->applicant_id)) {
            $applicant = DB::table('applicants as a')
                ->leftJoin('units as u', 'u.id', '=', 'a.unit_id')
                ->select('a.id', 'a.full_name', 'a.position_name', 'u.name as unit_name')
                ->where('a.id', $contract->applicant_id)
                ->first();
        }

        $personName = $person->full_name ?? null;
        if (! $personName && $applicant) $personName = $applicant->full_name;

        $employeeName = null;
        if ($employee) $employeeName = $employee->person_name ?? $employee->employee_id ?? $employee->email;

        $displayName = $personName ?: $employeeName;

        $typeCfg = collect(config('recruitment.contract_types', []))->firstWhere('code', $contract->contract_type) ?? [];
        $contractMode  = $typeCfg['mode'] ?? 'new';
        $typeLabel     = $typeCfg['label'] ?? $contract->contract_type;

        $baseContractPayload = null;
        if ($contract->parent_contract_id) {
            $base = Contract::with('unit')->find($contract->parent_contract_id);
            if ($base) {
                $baseMeta = $base->remuneration_json ?? [];
                $baseContractPayload = [
                    'id'            => $base->id,
                    'contract_no'   => $base->contract_no,
                    'contract_type' => $base->contract_type,
                    'contract_type_label' => optional(collect(config('recruitment.contract_types', []))->firstWhere('code', $base->contract_type))['label'] ?? $base->contract_type,
                    'start_date'    => optional($base->start_date)->format('Y-m-d'),
                    'end_date'      => optional($base->end_date)->format('Y-m-d'),
                    'unit'          => $base->unit?->only(['id', 'name', 'code']),
                    'meta'          => $baseMeta,
                    'position_name' => $baseMeta['position_name'] ?? null,
                ];
            }
        }

        $signatures = [];
        if ($contract->document_id) {
            $signatures = Signature::where('document_id', $contract->document_id)
                ->orderBy('signed_at')
                ->get()
                ->map(fn ($sig) => [
                    'id'            => $sig->id,
                    'signer_role'   => $sig->signer_role,
                    'signed_at'     => optional($sig->signed_at)->toDateTimeString(),
                    'geo_lat'       => $sig->geo_lat,
                    'geo_lng'       => $sig->geo_lng,
                    'geo_accuracy'  => $sig->geo_accuracy_m,
                ]);
        }

        $approval = Approval::where('approvable_type', 'contract')->where('approvable_id', $contract->id)->first();

        $flowHint = '';
        $rawType = $contract->contract_type;
        $status  = $contract->status;

        if ($rawType === 'SPK') {
            $flowHint = match ($status) {
                'draft'    => 'Draft SPK: disusun oleh SDM Unit.',
                'review'   => 'SPK: menunggu review + e-sign Kepala Unit.',
                'approved' => 'SPK: menunggu approval + e-sign kandidat.',
                'signed'   => 'SPK: sudah ditandatangani kandidat.',
                default    => '',
            };
        } elseif (in_array($rawType, ['PKWT_BARU', 'PKWT_PERPANJANGAN'])) {
            $flowHint = match ($status) {
                'draft'    => 'Draft PKWT: disusun oleh SDM Unit.',
                'review'   => 'PKWT: menunggu review + e-sign Kepala Unit.',
                'approved' => 'PKWT: menunggu e-sign kandidat.',
                'signed'   => 'PKWT: telah ditandatangani kandidat.',
                default    => '',
            };
        } elseif ($rawType === 'PB_PENGAKHIRAN') {
            $flowHint = match ($status) {
                'draft'    => 'Draft PB: disusun oleh SDM Unit.',
                'review'   => 'PB: menunggu review + e-sign Kepala Unit.',
                'approved' => 'PB: menunggu e-sign pihak terkait.',
                'signed'   => 'PB: Perjanjian Bersama telah ditandatangani.',
                default    => '',
            };
        }

        $canApprove = $me && $me->can('contract.approve') && $contract->status === 'review';
        $canReject  = $canApprove;
        $canSign    = $me && $me->can('contract.sign') && $contract->status === 'approved';

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $contract->id,
                'contract_no'       => $contract->contract_no,
                'contract_type'     => $contract->contract_type,
                'contract_type_label' => $typeLabel,
                'contract_mode'     => $contractMode,
                'status'            => $contract->status,
                'employment_type'   => $contract->employment_type,
                'start_date'        => optional($contract->start_date)->format('Y-m-d'),
                'end_date'          => optional($contract->end_date)->format('Y-m-d'),
                'unit'              => $contract->unit?->only(['id', 'name', 'code']),
                'requires_draw'     => $contract->requires_draw_signature,
                'requires_camera'   => $contract->requires_camera,
                'requires_geo'      => $contract->requires_geolocation,
                'has_document'      => !! $contract->document_id,
                'document_id'       => $contract->document_id,
                'meta'              => $meta,
                'remuneration_json' => $meta,
                'position_name'     => $meta['position_name'] ?? null,
                'remarks'           => $meta['remarks'] ?? null,
                'person' => $person ? [
                    'id'        => $person->id,
                    'full_name' => $person->full_name,
                    'id_number' => $person->nik_last4,
                ] : null,
                'employee' => $employee ? [
                    'id'              => $employee->id,
                    'employee_id'     => $employee->employee_id,
                    'email'           => $employee->email,
                    'employee_status' => $employee->employee_status,
                    'person_name'     => $employee->person_name,
                ] : null,
                'applicant' => $applicant ? [
                    'id'            => $applicant->id,
                    'full_name'     => $applicant->full_name,
                    'position_name' => $applicant->position_name,
                    'unit_name'     => $applicant->unit_name,
                ] : null,
                'person_name'   => $personName,
                'employee_name' => $employeeName,
                'display_name'  => $displayName,
                'type_config'   => $typeCfg,
                'base_contract' => $baseContractPayload,
                'signatures'    => $signatures,
                'approval'      => $approval,
                'flow_hint'     => $flowHint,
                'can_approve'   => $canApprove,
                'can_reject'    => $canReject,
                'can_sign'      => $canSign,
                'approve_url'   => $canApprove ? route('recruitment.contracts.approve', $contract) : null,
                'reject_url'    => $canReject ? route('recruitment.contracts.reject', $contract) : null,
                'sign_url'      => $canSign ? route('recruitment.contracts.sign', $contract) : null,
            ],
        ]);
    }

    public function terbilang(Request $request)
    {
        $amount = (string) $request->get('amount', '0');
        $amount = is_numeric($amount) ? (int) $amount : (int) preg_replace('/\D+/','',$amount);
        $words = trim($this->terbilangIndonesia(abs($amount)));
        $words = $words === '' ? 'nol' : $words;
        $suffix = 'rupiah';
        $txt = trim($words.' '.$suffix);
        return response()->json([
            'success'=>true,
            'data'=>[
                'amount'=>$amount,
                'words'=>$txt,
            ],
        ]);
    }

    protected function generateContractNumber(Contract $contract): string
    {
        $contract->loadMissing('unit');
        $type = $contract->contract_type;
        $cfg = config('recruitment.numbering', []);
        $docCodes = $cfg['doc_codes'] ?? [];
        $defaultHead = $cfg['default_head_code'] ?? 'XX';
        $docCode  = $docCodes[$type] ?? strtoupper($type);
        $unit     = $contract->unit;
        $unitCode = $unit?->code ?? 'UNIT';
        $now   = now();
        $year  = $now->format('Y');
        $month = $now->format('m');
        $headCode = $this->resolveHeadCodeForUnit($unit) ?: $defaultHead;

        $like = sprintf('%s-%%/%s-%s/%s/%s', $docCode, $unitCode, $month, $headCode, $year);

        $last = Contract::where('contract_type', $contract->contract_type)
            ->where('unit_id', $contract->unit_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('contract_no')
            ->where('contract_no', 'like', $like)
            ->orderByDesc('contract_no')
            ->value('contract_no');

        $seq = 1;
        if ($last) {
            $segments = explode('/', $last);
            $first    = $segments[0] ?? '';
            $parts    = explode('-', $first);
            $num      = $parts[1] ?? 0;
            $seq      = ((int) $num) + 1;
        }

        return sprintf('%s-%03d/%s-%s/%s/%s', $docCode, $seq, $unitCode, $month, $headCode, $year);
    }

    protected function resolveHeadCodeForUnit(?Unit $unit): string
    {
        $default = config('recruitment.numbering.default_head_code', 'XX');
        if (! $unit) return $default;

        $headRoles = config('recruitment.numbering.head_role_names', ['Kepala Unit']);

        $user = User::query()
            ->join('model_has_roles as mhr', function ($join) use ($unit) {
                $join->on('mhr.model_id', '=', 'users.id')
                    ->where('mhr.model_type', '=', User::class)
                    ->where('mhr.unit_id', '=', $unit->id);
            })
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->whereIn('roles.name', $headRoles)
            ->where(function ($q) use ($unit) {
                $q->whereNull('roles.unit_id')->orWhere('roles.unit_id', $unit->id);
            })
            ->where('users.unit_id', $unit->id)
            ->select('users.*')
            ->first();

        $full = null;
        if ($user) {
            if ($user->person_id) {
                $p = DB::table('persons')->where('id', $user->person_id)->first();
                $full = $p->full_name ?? $user->name;
            } else {
                $full = $user->name;
            }
        }
        if (! $full) return $default;

        $parts = preg_split('/\s+/', trim($full));
        if (count($parts) < 1) return $default;

        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last  = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');

        return mb_strtoupper($first . $last, 'UTF-8');
    }

    protected function collectMeta(array $v, bool $edit = false, array $old = []): array
    {
        $get = fn ($key) => $v[$key] ?? ($edit ? ($old[$key] ?? null) : null);

        return [
            'remarks'                        => $get('remarks'),
            'position_name'                  => $get('position_name'),
            'salary_amount'                  => $get('salary_amount'),
            'salary_amount_words'            => $get('salary_amount_words'),
            'lunch_allowance_daily'          => $get('lunch_allowance_daily'),
            'lunch_allowance_words'          => $get('lunch_allowance_words'),
            'allowance_special_amount'       => $get('allowance_special_amount'),
            'allowance_special_words'        => $get('allowance_special_words'),
            'allowance_position_amount'      => $get('allowance_position_amount'),
            'allowance_position_words'       => $get('allowance_position_words'),
            'allowance_communication_amount' => $get('allowance_communication_amount'),
            'allowance_communication_words'  => $get('allowance_communication_words'),
            'allowance_other_amount'         => $get('allowance_other_amount'),
            'allowance_other_words'          => $get('allowance_other_words'),
            'allowance_other_desc'           => $get('allowance_other_desc'),
            'other_benefits_desc'            => $get('other_benefits_desc'),
            'base_contract_start'            => $get('base_contract_start'),
            'base_contract_end'              => $get('base_contract_end'),
            'pb_effective_end'               => $get('pb_effective_end'),
            'pb_compensation_amount'         => $get('pb_compensation_amount'),
            'pb_compensation_amount_words'   => $get('pb_compensation_amount_words'),
        ];
    }

    protected function storeBase64Image(?string $dataUrl, string $disk, string $dir): array
    {
        if (! $dataUrl || ! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) return [null, null];
        $extension = strtolower($matches[1]);
        $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $imageData = base64_decode($imageData);
        if ($imageData === false) return [null, null];
        $filename = uniqid('sig_', true) . '.' . $extension;
        $path     = $dir . '/' . $filename;
        Storage::disk($disk)->put($path, $imageData);
        $hash = hash('sha256', $imageData);
        return [$path, $hash];
    }

    protected function formatTanggalIndo(Carbon $d): string
    {
        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    protected function terbilangIndonesia(int $n): string
    {
        $n = abs($n);
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($n < 12) return $huruf[$n];
        if ($n < 20) return $this->terbilangIndonesia($n - 10) . ' belas';
        if ($n < 100) return $this->terbilangIndonesia((int) ($n / 10)) . ' puluh ' . $this->terbilangIndonesia($n % 10);
        if ($n < 200) return 'seratus ' . $this->terbilangIndonesia($n - 100);
        if ($n < 1000) return $this->terbilangIndonesia((int) ($n / 100)) . ' ratus ' . $this->terbilangIndonesia($n % 100);
        if ($n < 2000) return 'seribu ' . $this->terbilangIndonesia($n - 1000);
        if ($n < 1000000) return $this->terbilangIndonesia((int) ($n / 1000)) . ' ribu ' . $this->terbilangIndonesia($n % 1000);
        if ($n < 1000000000) return $this->terbilangIndonesia((int) ($n / 1000000)) . ' juta ' . $this->terbilangIndonesia($n % 1000000);
        if ($n < 1000000000000) return $this->terbilangIndonesia((int) ($n / 1000000000)) . ' miliar ' . $this->terbilangIndonesia($n % 1000000000);
        return (string) $n;
    }

    protected function fillSubjectFromRequest(Contract $contract, array $v, ?Contract $base = null, ?Applicant $applicant = null): void
    {
        $reqEmployeeId = trim((string)($v['employee_id'] ?? ''));
        if ($reqEmployeeId !== '') {
            $emp = Employee::where('employee_id', $reqEmployeeId)->first();
            if ($emp) {
                $contract->employee_id = $emp->employee_id;
                $contract->person_id = $emp->person_id ?? $contract->person_id;
                if (Schema::hasColumn('employees', 'position_id')) $contract->position_id = $emp->position_id ?? $contract->position_id;
                if (Schema::hasColumn('employees', 'position_level_id')) $contract->position_level_id = $emp->position_level_id ?? $contract->position_level_id;
                if (empty($contract->unit_id) && !empty($emp->unit_id)) $contract->unit_id = $emp->unit_id;
                return;
            }
        }

        $reqPersonId = trim((string)($v['person_id'] ?? ''));
        if ($reqPersonId !== '') {
            $contract->person_id = $reqPersonId;
            if (empty($contract->employee_id)) {
                $emp = Employee::where('person_id', $reqPersonId)->latest('id')->first();
                if ($emp) {
                    $contract->employee_id = $emp->employee_id;
                    if (Schema::hasColumn('employees', 'position_id')) $contract->position_id = $emp->position_id ?? $contract->position_id;
                    if (Schema::hasColumn('employees', 'position_level_id')) $contract->position_level_id = $emp->position_level_id ?? $contract->position_level_id;
                    if (empty($contract->unit_id) && !empty($emp->unit_id)) $contract->unit_id = $emp->unit_id;
                }
            }
            return;
        }

        if ($base) {
            $contract->employee_id       = $contract->employee_id ?: $base->employee_id;
            $contract->person_id         = $contract->person_id   ?: $base->person_id;
            $contract->position_id       = $contract->position_id ?: $base->position_id;
            $contract->position_level_id = $contract->position_level_id ?: $base->position_level_id;
            return;
        }

        if ($applicant) {
            if (! empty($applicant->person_id)) $contract->person_id = $applicant->person_id;
        }
    }

    protected function mapEmploymentType(?string $employeeStatus): ?string
    {
        if (! $employeeStatus) return null;
        $s = mb_strtolower($employeeStatus, 'UTF-8');
        if (str_contains($s, 'organik')) return 'Kontrak Organik';
        if (str_contains($s, 'project') || str_contains($s, 'proyek') || str_contains($s, 'kso')) return 'Kontrak-Project Based';
        return $employeeStatus;
    }

    protected function resolveBaseContractForType(?int $sourceId, ?int $expiringId, ?string $expiringEnd, ?string $employeeId, ?string $personId, ?int $unitId): ?Contract
    {
        if ($sourceId) {
            $byId = Contract::where('id', $sourceId)->first();
            if ($byId) return $byId;
        }

        $pid = $personId;
        $eid = $employeeId;
        $end = $expiringEnd;

        if ($expiringId && (!$pid || !$eid || !$end)) {
            $row = DB::table('portfolio_histories as ph')
                ->leftJoin('employees as e', 'e.person_id', '=', 'ph.person_id')
                ->select('ph.person_id','e.employee_id','ph.end_date')
                ->where('ph.id', $expiringId)
                ->first();
            if ($row) {
                $pid = $pid ?: ($row->person_id ?? null);
                $eid = $eid ?: ($row->employee_id ?? null);
                $end = $end ?: ($row->end_date ?? null);
            }
        }

        $statusSet = ['signed','approved','review'];

        $q = Contract::query()
            ->when($eid, fn($qq) => $qq->where('employee_id', $eid))
            ->when(!$eid && $pid, fn($qq) => $qq->where('person_id', $pid))
            ->when($unitId, fn($qq) => $qq->where('unit_id', $unitId))
            ->whereIn('status', $statusSet)
            ->whereIn('contract_type', ['SPK','PKWT_BARU','PKWT_PERPANJANGAN'])
            ->orderByDesc('end_date')
            ->orderByDesc('created_at');

        if ($end) $q->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END, ABS(DATEDIFF(COALESCE(end_date, NOW()), ?)) ASC', [$end]);

        $found = $q->first();
        if ($found) return $found;

        $q2 = Contract::query()
            ->when($eid, fn($qq) => $qq->where('employee_id', $eid))
            ->when(!$eid && $pid, fn($qq) => $qq->where('person_id', $pid))
            ->whereIn('status', $statusSet)
            ->whereIn('contract_type', ['SPK','PKWT_BARU','PKWT_PERPANJANGAN'])
            ->orderByDesc('end_date')
            ->orderByDesc('created_at')
            ->limit(1);

        return $q2->first();
    }
}
