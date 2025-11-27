<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Applicant;
use App\Models\Unit;
use App\Models\Document;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $isSuperadmin = $me?->hasRole('Superadmin');
        $isDhc        = $me?->hasRole('DHC');

        $canSeeAll = $isSuperadmin || $isDhc;
        $meUnit    = (int) ($me->unit_id ?? 0);

        if ($canSeeAll) {
            $selectedUnitId = $request->filled('unit_id')
                ? (int) $request->integer('unit_id')
                : null;
        } else {
            $selectedUnitId = $meUnit;
        }

        $unitsQuery = Unit::query();

        if ($isSuperadmin) {
            $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        } elseif ($isDhc) {
            $unitsQuery->where('category', 'ENABLER');
        }

        $units = $canSeeAll
            ? $unitsQuery->orderBy('name')->get(['id', 'name', 'code', 'category'])
            : Unit::where('id', $selectedUnitId)->get(['id', 'name', 'code', 'category']);

        $statusFilter = $request->input('status');
        $searchFilter = $request->input('q');

        $contractsQuery = Contract::query()
            ->with(['unit'])
            ->when($selectedUnitId, function ($q) use ($selectedUnitId) {
                $q->where('unit_id', $selectedUnitId);
            })
            ->when($isDhc && ! $isSuperadmin, function ($q) {
                $q->whereHas('unit', function ($uq) {
                    $uq->where('category', 'ENABLER');
                });
            })
            ->when($isSuperadmin, function ($q) {
                $q->whereHas('unit', function ($uq) {
                    $uq->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
                });
            })
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            })
            ->when($searchFilter, function ($q) use ($searchFilter) {
                $q->where(function ($qq) use ($searchFilter) {
                    $qq->where('contract_no', 'like', '%'.$searchFilter.'%')
                       ->orWhere('employment_type', 'like', '%'.$searchFilter.'%');
                });
            })
            ->orderByDesc('created_at');

        $contracts = $contractsQuery->paginate(25)->withQueryString();

        $contractTypeConfigs = config('recruitment.contract_types', []);
        $contractTypes = collect($contractTypeConfigs)
            ->mapWithKeys(function (array $row) {
                $code  = $row['code']  ?? '';
                $label = $row['label'] ?? $code;
                return $code ? [$code => $label] : [];
            })
            ->all();

        $employmentTypeConfigs = config('recruitment.employment_types', []);
        $employmentTypes = collect($employmentTypeConfigs)
            ->mapWithKeys(function (array $row) {
                $code  = $row['code']  ?? '';
                $label = $row['label'] ?? $code;
                return $code ? [$code => $label] : [];
            })
            ->all();

        $budgetSourceTypeConfigs = config('recruitment.budget_source_types', []);
        $budgetSourceTypes = collect($budgetSourceTypeConfigs)
            ->mapWithKeys(function (array $row) {
                $code  = $row['code']  ?? '';
                $label = $row['label'] ?? $code;
                return $code ? [$code => $label] : [];
            })
            ->all();

        $requestTypeConfigs = config('recruitment.request_types', []);
        $statusOptions      = config('recruitment.contract_statuses', []);

        $eligibleStatuses = config('recruitment.contract_applicant_statuses', ['APPROVED']);

        $applicantsQuery = Applicant::query()
            ->leftJoin('units as u', 'u.id', '=', 'applicants.unit_id')
            ->select('applicants.*', 'u.name as unit_name')
            ->whereIn('status', $eligibleStatuses);

        if ($selectedUnitId) {
            $applicantsQuery->where('applicants.unit_id', $selectedUnitId);
        } elseif (! $canSeeAll && $meUnit) {
            $applicantsQuery->where('applicants.unit_id', $meUnit);
        }

        if ($isDhc && ! $isSuperadmin) {
            $applicantsQuery->where('u.category', 'ENABLER');
        }

        if ($isSuperadmin) {
            $applicantsQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        }

        $applicants = $applicantsQuery
            ->orderBy('full_name')
            ->get();

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
            ->whereDate('ph.end_date', '>=', $today->toDateString())
            ->whereDate('ph.end_date', '<=', $until->toDateString());

        if (! $canSeeAll && $meUnit) {
            $expiringQuery->where('e.unit_id', $meUnit);
        }

        if ($isDhc && ! $isSuperadmin) {
            $expiringQuery->where('u.category', 'ENABLER');
        }

        if ($isSuperadmin) {
            $expiringQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        }

        $expiringContracts = $expiringQuery
            ->orderBy('ph.end_date')
            ->get();

        return view('recruitment.contracts.index', [
            'contracts'         => $contracts,
            'list'              => $contracts,
            'units'             => $units,
            'selectedUnitId'    => $selectedUnitId,
            'statusFilter'      => $statusFilter,
            'statusOptions'     => $statusOptions,
            'contractTypes'           => $contractTypes,
            'employmentTypes'         => $employmentTypes,
            'budgetSourceTypes'       => $budgetSourceTypes,
            'contractTypeConfigs'     => $contractTypeConfigs,
            'employmentTypeConfigs'   => $employmentTypeConfigs,
            'budgetSourceTypeConfigs' => $budgetSourceTypeConfigs,
            'requestTypes'            => $requestTypeConfigs,
            'applicants'        => $applicants,
            'expiringContracts' => $expiringContracts,
            'canSeeAll'         => $canSeeAll,
        ]);
    }

    public function store(Request $request)
    {
        $me = $request->user();

        $validated = $request->validate([
            'contract_type'      => 'required|string|max:40',
            'unit_id'            => 'required|integer|exists:units,id',
            'applicant_id'       => 'nullable|string|exists:applicants,id',
            'position_name'      => 'nullable|string|max:191',
            'employment_type'    => 'nullable|string|max:40',
            'budget_source_type' => 'nullable|string|max:40',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'remarks'            => 'nullable|string|max:2000',
            'source_contract_id' => 'nullable|integer',
            'salary_amount'                     => 'nullable|string|max:50',
            'salary_amount_words'               => 'nullable|string|max:255',
            'lunch_allowance_daily'             => 'nullable|string|max:50',
            'lunch_allowance_words'             => 'nullable|string|max:255',
            'allowance_special_amount'          => 'nullable|string|max:50',
            'allowance_special_words'           => 'nullable|string|max:255',
            'allowance_position_amount'         => 'nullable|string|max:50',
            'allowance_position_words'          => 'nullable|string|max:255',
            'allowance_communication_amount'    => 'nullable|string|max:50',
            'allowance_communication_words'     => 'nullable|string|max:255',
            'allowance_other_amount'            => 'nullable|string|max:50',
            'allowance_other_words'             => 'nullable|string|max:255',
            'allowance_other_desc'              => 'nullable|string|max:500',
            'other_benefits_desc'               => 'nullable|string|max:500',
            'base_contract_start'               => 'nullable|date',
            'base_contract_end'                 => 'nullable|date',
            'pb_effective_end'                  => 'nullable|date',
            'pb_compensation_amount'            => 'nullable|string|max:50',
            'pb_compensation_amount_words'      => 'nullable|string|max:255',
        ]);

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                abort(403, 'Tidak boleh membuat kontrak di luar unit Anda.');
            }
        }

        $ctCode     = $validated['contract_type'];
        $typeConfig = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $ctCode);

        if (! $typeConfig) {
            return back()
                ->withErrors(['contract_type' => 'Jenis kontrak tidak dikenali.'])
                ->withInput();
        }

        $mode              = (string) ($typeConfig['mode'] ?? 'new');
        $requiresApplicant = (bool) ($typeConfig['requires_applicant'] ?? false);
        $requiresExisting  = (bool) ($typeConfig['requires_existing_contract'] ?? false);

        if ($requiresApplicant && empty($validated['applicant_id'])) {
            return back()
                ->withErrors(['applicant_id' => 'Pelamar / kandidat wajib dipilih untuk jenis kontrak ini.'])
                ->withInput();
        }

        if ($requiresExisting && empty($validated['source_contract_id'])) {
            return back()
                ->withErrors(['source_contract_id' => 'Pilih kontrak / portfolio yang menjadi dasar perpanjangan / pengakhiran.'])
                ->withInput();
        }

        if ($mode === 'new' && empty($validated['position_name'])) {
            return back()
                ->withErrors(['position_name' => 'Nama jabatan wajib diisi untuk kontrak baru.'])
                ->withInput();
        }

        $applicant = null;
        if (! empty($validated['applicant_id']) && $requiresApplicant) {
            $applicant = Applicant::query()
                ->where('id', $validated['applicant_id'])
                ->firstOrFail();
        }

        $requiresDraw = (bool) ($typeConfig['requires_draw_signature'] ?? true);
        $requiresCam  = (bool) ($typeConfig['requires_camera'] ?? false);
        $requiresGeo  = (bool) ($typeConfig['requires_geolocation'] ?? false);

        $contract = new Contract();
        $contract->contract_no   = null;
        $contract->contract_type = $ctCode;

        if ($applicant) {
            $contract->person_id       = $applicant->person_id ?? null;
            $contract->employee_id     = null;
            $contract->unit_id         = $validated['unit_id'];
            $contract->employment_type = $validated['employment_type'] ?? null;
            $contract->start_date      = $validated['start_date'] ?? null;
            $contract->end_date        = $validated['end_date'] ?? null;
        } else {
            $contract->unit_id         = $validated['unit_id'];
            $contract->employment_type = $validated['employment_type'] ?? null;
            $contract->start_date      = $validated['start_date'] ?? null;
            $contract->end_date        = $validated['end_date'] ?? null;
        }

        $contract->budget_source_type      = $validated['budget_source_type'] ?? null;
        $contract->status                  = 'draft';
        $contract->requires_draw_signature = $requiresDraw;
        $contract->requires_camera         = $requiresCam;
        $contract->requires_geolocation    = $requiresGeo;
        $contract->document_id             = null;
        $contract->created_by_person_id    = $me->person_id ?? null;
        $contract->created_by_user_id      = $me->id;

        $meta = [
            'remarks'                       => $validated['remarks'] ?? null,
            'position_name'                 => $validated['position_name'] ?? null,
            'salary_amount'                 => $validated['salary_amount'] ?? null,
            'salary_amount_words'           => $validated['salary_amount_words'] ?? null,
            'lunch_allowance_daily'         => $validated['lunch_allowance_daily'] ?? null,
            'lunch_allowance_words'         => $validated['lunch_allowance_words'] ?? null,
            'allowance_special_amount'       => $validated['allowance_special_amount'] ?? null,
            'allowance_special_words'        => $validated['allowance_special_words'] ?? null,
            'allowance_position_amount'      => $validated['allowance_position_amount'] ?? null,
            'allowance_position_words'       => $validated['allowance_position_words'] ?? null,
            'allowance_communication_amount' => $validated['allowance_communication_amount'] ?? null,
            'allowance_communication_words'  => $validated['allowance_communication_words'] ?? null,
            'allowance_other_amount'         => $validated['allowance_other_amount'] ?? null,
            'allowance_other_words'          => $validated['allowance_other_words'] ?? null,
            'allowance_other_desc'           => $validated['allowance_other_desc'] ?? null,
            'other_benefits_desc'            => $validated['other_benefits_desc'] ?? null,
            'base_contract_start'           => $validated['base_contract_start'] ?? null,
            'base_contract_end'             => $validated['base_contract_end'] ?? null,
            'pb_effective_end'              => $validated['pb_effective_end'] ?? null,
            'pb_compensation_amount'        => $validated['pb_compensation_amount'] ?? null,
            'pb_compensation_amount_words'  => $validated['pb_compensation_amount_words'] ?? null,
        ];

        $contract->remuneration_json = $meta;

        $contract->save();

        return redirect()
            ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Draft kontrak berhasil dibuat.');
    }

    public function update(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        if ($contract->status !== 'draft') {
            return back()->withErrors('Kontrak yang bukan draft tidak dapat diedit.');
        }

        $validated = $request->validate([
            'type'                    => 'required|string|max:40',
            'unit_id'                 => 'required|integer|exists:units,id',
            'applicant_id'            => 'nullable|string|exists:applicants,id',
            'position'                => 'required|string|max:191',
            'employment_type'         => 'nullable|string|max:40',
            'start_date'              => 'nullable|date',
            'end_date'                => 'nullable|date|after_or_equal:start_date',
            'note'                    => 'nullable|string|max:2000',
            'salary_amount'                     => 'nullable|string|max:50',
            'salary_amount_words'               => 'nullable|string|max:255',
            'lunch_allowance_daily'             => 'nullable|string|max:50',
            'lunch_allowance_words'             => 'nullable|string|max:255',
            'allowance_special_amount'          => 'nullable|string|max:50',
            'allowance_special_words'           => 'nullable|string|max:255',
            'allowance_position_amount'         => 'nullable|string|max:50',
            'allowance_position_words'          => 'nullable|string|max:255',
            'allowance_communication_amount'    => 'nullable|string|max:50',
            'allowance_communication_words'     => 'nullable|string|max:255',
            'allowance_other_amount'            => 'nullable|string|max:50',
            'allowance_other_words'             => 'nullable|string|max:255',
            'allowance_other_desc'              => 'nullable|string|max:500',
            'other_benefits_desc'               => 'nullable|string|max:500',
            'base_contract_start'               => 'nullable|date',
            'base_contract_end'                 => 'nullable|date',
            'pb_effective_end'                  => 'nullable|date',
            'pb_compensation_amount'            => 'nullable|string|max:50',
            'pb_compensation_amount_words'      => 'nullable|string|max:255',
        ]);

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                abort(403, 'Tidak boleh memindahkan kontrak ke unit lain.');
            }
        }

        $applicant = null;
        if (! empty($validated['applicant_id'])) {
            $applicant = Applicant::query()
                ->where('id', $validated['applicant_id'])
                ->firstOrFail();
        }

        $contract->contract_type   = $validated['type'];
        $contract->unit_id         = $validated['unit_id'];
        $contract->person_id       = $applicant?->person_id ?? $contract->person_id;
        $contract->employment_type = $validated['employment_type'] ?? null;
        $contract->start_date      = $validated['start_date'] ?? null;
        $contract->end_date        = $validated['end_date'] ?? null;

        $meta = $contract->remuneration_json ?? [];

        $meta['remarks']           = $validated['note'] ?? null;
        $meta['position_name']     = $validated['position'] ?? ($meta['position_name'] ?? null);

        $meta['salary_amount']                 = $validated['salary_amount'] ?? ($meta['salary_amount'] ?? null);
        $meta['salary_amount_words']           = $validated['salary_amount_words'] ?? ($meta['salary_amount_words'] ?? null);
        $meta['lunch_allowance_daily']         = $validated['lunch_allowance_daily'] ?? ($meta['lunch_allowance_daily'] ?? null);
        $meta['lunch_allowance_words']         = $validated['lunch_allowance_words'] ?? ($meta['lunch_allowance_words'] ?? null);
        $meta['allowance_special_amount']       = $validated['allowance_special_amount'] ?? ($meta['allowance_special_amount'] ?? null);
        $meta['allowance_special_words']        = $validated['allowance_special_words'] ?? ($meta['allowance_special_words'] ?? null);
        $meta['allowance_position_amount']      = $validated['allowance_position_amount'] ?? ($meta['allowance_position_amount'] ?? null);
        $meta['allowance_position_words']       = $validated['allowance_position_words'] ?? ($meta['allowance_position_words'] ?? null);
        $meta['allowance_communication_amount'] = $validated['allowance_communication_amount'] ?? ($meta['allowance_communication_amount'] ?? null);
        $meta['allowance_communication_words']  = $validated['allowance_communication_words'] ?? ($meta['allowance_communication_words'] ?? null);
        $meta['allowance_other_amount']         = $validated['allowance_other_amount'] ?? ($meta['allowance_other_amount'] ?? null);
        $meta['allowance_other_words']          = $validated['allowance_other_words'] ?? ($meta['allowance_other_words'] ?? null);
        $meta['allowance_other_desc']           = $validated['allowance_other_desc'] ?? ($meta['allowance_other_desc'] ?? null);
        $meta['other_benefits_desc']            = $validated['other_benefits_desc'] ?? ($meta['other_benefits_desc'] ?? null);
        $meta['base_contract_start']            = $validated['base_contract_start'] ?? ($meta['base_contract_start'] ?? null);
        $meta['base_contract_end']              = $validated['base_contract_end'] ?? ($meta['base_contract_end'] ?? null);
        $meta['pb_effective_end']               = $validated['pb_effective_end'] ?? ($meta['pb_effective_end'] ?? null);
        $meta['pb_compensation_amount']         = $validated['pb_compensation_amount'] ?? ($meta['pb_compensation_amount'] ?? null);
        $meta['pb_compensation_amount_words']   = $validated['pb_compensation_amount_words'] ?? ($meta['pb_compensation_amount_words'] ?? null);

        $contract->remuneration_json = $meta;

        $contract->save();

        return redirect()
            ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Draft kontrak berhasil diperbarui.');
    }

    public function submit(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya kontrak berstatus draft yang bisa di-submit.');
        }

        $contract->status = 'review';
        $contract->save();

        return back()->with('ok', 'Kontrak dikirim untuk review.');
    }

    public function approve(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            abort(403, 'Akses ditolak.');
        }

        if (! in_array($contract->status, ['review', 'draft'], true)) {
            return back()->withErrors('Hanya kontrak draft/review yang bisa di-approve.');
        }

        DB::transaction(function () use ($contract, $me) {
            if (! $contract->contract_no) {
                $contract->contract_no = $this->generateContractNumber($contract);
            }

            if (! $contract->document_id) {
                $document = $this->ensureContractDocumentExists(
                    $contract,
                    $me->id,
                    $me->person_id ?? null
                );
                $contract->document_id = $document->id;
            }

            $contract->status = 'approved';
            $contract->save();
        });

        return back()->with('ok', 'Kontrak disetujui dan siap untuk e-sign.');
    }

    public function show(Contract $contract)
    {
        $me = auth()->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $contract->load('unit');

        $signatures = [];
        if ($contract->document_id) {
            $signatures = Signature::query()
                ->where('document_id', $contract->document_id)
                ->orderBy('signed_at')
                ->get()
                ->map(function (Signature $sig) {
                    return [
                        'id'           => $sig->id,
                        'signer_role'  => $sig->signer_role,
                        'signed_at'    => optional($sig->signed_at)->toDateTimeString(),
                        'geo_lat'      => $sig->geo_lat,
                        'geo_lng'      => $sig->geo_lng,
                        'geo_accuracy' => $sig->geo_accuracy_m,
                    ];
                });
        }

        $meta = $contract->remuneration_json ?? [];

        return response()->json([
            'data' => [
                'id'              => $contract->id,
                'contract_no'     => $contract->contract_no,
                'contract_type'   => $contract->contract_type,
                'status'          => $contract->status,
                'position_name'   => $meta['position_name'] ?? null,
                'remarks'         => $meta['remarks'] ?? null,
                'employment_type' => $contract->employment_type,
                'start_date'      => optional($contract->start_date)->format('Y-m-d'),
                'end_date'        => optional($contract->end_date)->format('Y-m-d'),
                'unit'            => $contract->unit?->only(['id', 'name', 'code']),
                'requires_draw'   => (bool) $contract->requires_draw_signature,
                'requires_camera' => (bool) $contract->requires_camera,
                'requires_geo'    => (bool) $contract->requires_geolocation,
                'has_document'    => (bool) $contract->document_id,
                'document_id'     => $contract->document_id,
                'signatures'      => $signatures,
                'meta'            => $meta,
            ],
        ]);
    }

    public function sign(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        if (! $contract->document_id) {
            return back()->withErrors('Dokumen kontrak belum tersedia untuk ditandatangani.');
        }

        if (! in_array($contract->status, ['approved', 'signed'], true)) {
            return back()->withErrors('Kontrak belum dalam status yang dapat ditandatangani.');
        }

        $validated = $request->validate([
            'signature_image'      => 'nullable|string',
            'signature_draw_data'  => 'nullable|string',
            'camera_photo'         => 'nullable|image|max:4096',
            'selfie_image'         => 'nullable|string',
            'geo_lat'              => 'nullable|numeric',
            'geo_lng'              => 'nullable|numeric',
            'geo_acc'              => 'nullable|numeric|min:0',
            'geo_accuracy_m'       => 'nullable|numeric|min:0',
            'signer_role'          => 'nullable|string|max:40',
        ]);

        $signerRole = $validated['signer_role'] ?? 'candidate';

        $drawData = $validated['signature_image']
            ?? $validated['signature_draw_data']
            ?? null;

        if (! $drawData) {
            return back()->withErrors('Tanda tangan (canvas) belum diisi.');
        }

        $drawHash = hash('sha256', $drawData);

        $photoPath = null;
        $photoHash = null;

        if ($request->hasFile('camera_photo')) {
            $file = $request->file('camera_photo');
            $photoPath = $file->store('contract-signatures', 'public');
            $photoHash = hash_file('sha256', $file->getRealPath());
        } elseif (! empty($validated['selfie_image'])) {
            [$photoPath, $photoHash] = $this->storeBase64Image(
                $validated['selfie_image'],
                'public',
                'contract-signatures'
            );
        }

        $geoAcc = $validated['geo_acc'] ?? $validated['geo_accuracy_m'] ?? null;

        Signature::updateOrCreate(
            [
                'document_id'      => $contract->document_id,
                'signer_role'      => $signerRole,
                'signer_person_id' => $me->person_id ?? $contract->person_id,
            ],
            [
                'signer_user_id'        => $me->id,
                'signature_draw_data'   => $drawData,
                'signature_draw_hash'   => $drawHash,
                'camera_photo_path'     => $photoPath,
                'camera_photo_hash'     => $photoHash,
                'geo_lat'               => $validated['geo_lat'] ?? null,
                'geo_lng'               => $validated['geo_lng'] ?? null,
                'geo_accuracy_m'        => $geoAcc,
                'signed_at'             => now(),
            ]
        );

        $contract->status = 'signed';
        $contract->save();

        return redirect()
            ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Kontrak berhasil ditandatangani.');
    }

    protected function generateContractNumber(Contract $contract): string
    {
        $contract->loadMissing('unit');

        $type = (string) $contract->contract_type;

        $numberingCfg = config('recruitment.numbering', []);
        $docCodes     = $numberingCfg['doc_codes'] ?? [];
        $defaultHead  = $numberingCfg['default_head_code'] ?? 'XX';

        $docCode  = $docCodes[$type] ?? strtoupper($type);

        $unit     = $contract->unit;
        $unitCode = $unit?->code ?: 'UNIT';

        $now   = now();
        $year  = $now->format('Y');
        $month = $now->format('m');

        $headCode = $this->resolveHeadCodeForUnit($unit) ?: $defaultHead;

        $likePattern = sprintf(
            '%s-%%/%s-%s/%s/%s',
            $docCode,
            $unitCode,
            $month,
            $headCode,
            $year
        );

        $lastNo = Contract::query()
            ->where('contract_type', $contract->contract_type)
            ->where('unit_id', $contract->unit_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('contract_no')
            ->where('contract_no', 'like', $likePattern)
            ->orderByDesc('contract_no')
            ->value('contract_no');

        $seq = 1;
        if ($lastNo) {
            $segments = explode('/', $lastNo);
            $first    = $segments[0] ?? '';
            $parts    = explode('-', $first);
            $lastSeq  = isset($parts[1]) ? (int) $parts[1] : 0;

            if ($lastSeq > 0) {
                $seq = $lastSeq + 1;
            }
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return sprintf(
            '%s-%s/%s-%s/%s/%s',
            $docCode,
            $seqStr,
            $unitCode,
            $month,
            $headCode,
            $year
        );
    }

    protected function resolveHeadCodeForUnit(?Unit $unit): string
    {
        $default = config('recruitment.numbering.default_head_code', 'XX');
        if (! $unit) {
            return $default;
        }

        $headRoleNames = config('recruitment.numbering.head_role_names', ['GM/VP Unit']);

        $user = User::query()
            ->where('unit_id', $unit->id)
            ->whereHas('roles', function ($q) use ($headRoleNames, $unit) {
                $q->whereIn('name', $headRoleNames)
                    ->where('team_id', $unit->id);
            })
            ->orderBy('id')
            ->first();

        $fullName = null;
        if ($user) {
            if ($user->person_id) {
                $person = DB::table('persons')->where('id', $user->person_id)->first();
                $fullName = $person->full_name ?? null;
            }
            if (! $fullName) {
                $fullName = $user->name;
            }
        }

        if (! $fullName) {
            return $default;
        }

        $parts = preg_split('/\s+/', trim($fullName));
        if (! $parts || count($parts) === 0) {
            return $default;
        }

        $first = $parts[0];
        $last  = $parts[count($parts) - 1];

        $initials = mb_substr($first, 0, 1, 'UTF-8') . mb_substr($last, 0, 1, 'UTF-8');

        return mb_strtoupper($initials, 'UTF-8');
    }

    protected function ensureContractDocumentExists(Contract $contract, int $userId, ?string $personId = null): Document
    {
        if ($contract->document_id) {
            $existing = Document::find($contract->document_id);
            if ($existing) {
                return $existing;
            }
        }

        $contract->loadMissing('unit');

        $typeConfig = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $contract->contract_type) ?: [];

        $templates = config('recruitment.templates', []);

        $templateKey = $typeConfig['template_key'] ?? null;
        $tpl         = ($templateKey && isset($templates[$templateKey]))
            ? $templates[$templateKey]
            : (reset($templates) ?: []);

        $disk         = $tpl['disk'] ?? 'public';
        $templatePath = $tpl['path'] ?? 'templates/contract-default.docx';
        $docType      = $typeConfig['document_type']
            ?? ($tpl['doc_type'] ?? 'CONTRACT');

        $path = "contracts/{$docType}/{$contract->id}-{$contract->contract_no}.pdf";

        $payload = $this->buildContractTemplatePayload($contract, $personId);

        return Document::create([
            'person_id'    => $contract->person_id,
            'employee_id'  => $contract->employee_id,
            'doc_type'     => $docType,
            'storage_disk' => $disk,
            'path'         => $path,
            'mime'         => 'application/pdf',
            'meta'         => [
                'source'           => 'rekrutmen.contract',
                'contract_id'      => $contract->id,
                'contract_no'      => $contract->contract_no,
                'unit_id'          => $contract->unit_id,
                'unit_name'        => $contract->unit?->name,
                'template_key'     => $templateKey,
                'template_path'    => $templatePath,
                'generated_by'     => $userId,
                'generated_at'     => now()->toDateTimeString(),
                'template_payload' => $payload,
                'sign_person_id'   => $personId,
            ],
        ]);
    }

    protected function buildContractTemplatePayload(Contract $contract, ?string $overrideSignPersonId = null): array
    {
        $contract->loadMissing('unit');

        $meta = $contract->remuneration_json ?? [];
        $unit = $contract->unit;

        $person = null;
        if ($contract->person_id) {
            $person = DB::table('persons')
                ->where('id', $contract->person_id)
                ->first();
        }

        $employee = null;
        if ($contract->employee_id) {
            $employee = DB::table('employees')
                ->where('id', $contract->employee_id)
                ->first();
        }

        $signDate = $contract->start_date ?: now();
        if (! $signDate instanceof Carbon) {
            $signDate = Carbon::parse($signDate);
        }

        $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
        $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

        $companyName        = config('recruitment.company_name', 'PT Surveyor Indonesia');
        $companyAddress     = config('recruitment.company_address', 'Gedung Surveyor Indonesia, Jakarta');
        $companyRepName     = config('recruitment.company_rep_name', 'Lussy Ariani Seba');
        $companyRepPosition = config('recruitment.company_rep_title', 'Direktur Sumber Daya Manusia');
        $companyCity        = config('recruitment.company_city', 'Jakarta');

        $personName  = $person->full_name ?? ($employee->full_name ?? null);
        $personNik   = $person->id_number ?? ($employee->nik ?? null);
        $birthPlace  = $person->birth_place ?? null;
        $birthDate   = isset($person->birth_date) ? Carbon::parse($person->birth_date) : null;
        $address     = $employee->home_base_address ?? ($person->address ?? null);
        $city        = $employee->home_base_city ?? null;

        $rawBaseSalary    = $meta['salary_amount']           ?? null;
        $rawLunchDaily    = $meta['lunch_allowance_daily']   ?? null;
        $baseSalaryWords  = $meta['salary_amount_words']     ?? null;
        $lunchDailyWords  = $meta['lunch_allowance_words']   ?? null;
        $otherBenefitDesc = $meta['other_benefits_desc']     ?? null;

        $rawSpecial       = $meta['allowance_special_amount']       ?? null;
        $rawPosition      = $meta['allowance_position_amount']      ?? null;
        $rawComm          = $meta['allowance_communication_amount'] ?? null;
        $rawOther         = $meta['allowance_other_amount']         ?? null;

        $specialWords     = $meta['allowance_special_words']        ?? null;
        $positionWords    = $meta['allowance_position_words']       ?? null;
        $commWords        = $meta['allowance_communication_words']  ?? null;
        $otherWords       = $meta['allowance_other_words']          ?? null;
        $otherDesc        = $meta['allowance_other_desc']           ?? null;

        $baseSalary = $this->normalizeMoney($rawBaseSalary);
        $lunchDaily = $this->normalizeMoney($rawLunchDaily);
        $special    = $this->normalizeMoney($rawSpecial);
        $position   = $this->normalizeMoney($rawPosition);
        $comm       = $this->normalizeMoney($rawComm);
        $other      = $this->normalizeMoney($rawOther);

        if ($baseSalary !== null && ! $baseSalaryWords) {
            $baseSalaryWords = $this->terbilangIndonesia((int) $baseSalary).' rupiah';
        }

        if ($lunchDaily !== null && ! $lunchDailyWords) {
            $lunchDailyWords = $this->terbilangIndonesia((int) $lunchDaily).' rupiah';
        }

        if ($special !== null && ! $specialWords) {
            $specialWords = $this->terbilangIndonesia((int) $special).' rupiah';
        }

        if ($position !== null && ! $positionWords) {
            $positionWords = $this->terbilangIndonesia((int) $position).' rupiah';
        }

        if ($comm !== null && ! $commWords) {
            $commWords = $this->terbilangIndonesia((int) $comm).' rupiah';
        }

        if ($other !== null && ! $otherWords) {
            $otherWords = $this->terbilangIndonesia((int) $other).' rupiah';
        }

        $baseContractFrom = isset($meta['base_contract_start'])
            ? Carbon::parse($meta['base_contract_start'])
            : null;
        $baseContractTo   = isset($meta['base_contract_end'])
            ? Carbon::parse($meta['base_contract_end'])
            : null;

        $pbEndDate     = isset($meta['pb_effective_end']) ? Carbon::parse($meta['pb_effective_end']) : $end;
        $rawPbComp     = $meta['pb_compensation_amount'] ?? null;
        $pbCompAmount  = $this->normalizeMoney($rawPbComp);
        $pbCompAmountW = $meta['pb_compensation_amount_words'] ?? null;

        if ($pbCompAmount !== null && ! $pbCompAmountW) {
            $pbCompAmountW = $this->terbilangIndonesia((int) $pbCompAmount).' rupiah';
        }

        $durationMonths      = null;
        $durationMonthsWords = null;

        if ($baseContractFrom && $baseContractTo) {
            $durationMonths = (($baseContractTo->year - $baseContractFrom->year) * 12)
                + ($baseContractTo->month - $baseContractFrom->month);
            if ($durationMonths <= 0) {
                $durationMonths = null;
            }
        }

        if ($durationMonths !== null) {
            $durationMonthsWords = strtoupper($this->terbilangIndonesia($durationMonths));
        }

        $signPersonId = $overrideSignPersonId ?: $contract->person_id;

        $baseSalaryRp   = $this->formatMoney($baseSalary);
        $lunchDailyRp   = $this->formatMoney($lunchDaily);
        $specialRp      = $this->formatMoney($special);
        $positionRp     = $this->formatMoney($position);
        $commRp         = $this->formatMoney($comm);
        $otherRp        = $this->formatMoney($other);
        $pbCompAmountRp = $this->formatMoney($pbCompAmount);

        $baseSalaryWordsUpper   = $baseSalaryWords ? mb_strtoupper($baseSalaryWords, 'UTF-8') : null;
        $lunchDailyWordsUpper   = $lunchDailyWords ? mb_strtoupper($lunchDailyWords, 'UTF-8') : null;
        $specialWordsUpper      = $specialWords ? mb_strtoupper($specialWords, 'UTF-8') : null;
        $positionWordsUpper     = $positionWords ? mb_strtoupper($positionWords, 'UTF-8') : null;
        $commWordsUpper         = $commWords ? mb_strtoupper($commWords, 'UTF-8') : null;
        $otherWordsUpper        = $otherWords ? mb_strtoupper($otherWords, 'UTF-8') : null;
        $pbCompAmountWordsUpper = $pbCompAmountW ? mb_strtoupper($pbCompAmountW, 'UTF-8') : null;

        return [
            'contract_type'       => $contract->contract_type,
            'contract_no'         => $contract->contract_no,
            'unit_name'           => $unit?->name,
            'unit_code'           => $unit?->code,
            'unit_category'       => $unit?->category,
            'sign_date_dmy'       => $signDate->format('d-m-Y'),
            'sign_date_long'      => $this->formatTanggalIndo($signDate),
            'sign_day_name'       => $this->getHariIndo($signDate),
            'sign_place_city'     => $companyCity,
            'start_date_dmy'      => $start?->format('d-m-Y'),
            'start_date_long'     => $start ? $this->formatTanggalIndo($start) : null,
            'end_date_dmy'        => $end?->format('d-m-Y'),
            'end_date_long'       => $end ? $this->formatTanggalIndo($end) : null,
            'company_name'        => $companyName,
            'company_address'     => $companyAddress,
            'company_city'        => $companyCity,
            'company_rep_name'    => $companyRepName,
            'company_rep_title'   => $companyRepPosition,
            'person_id'           => $contract->person_id,
            'sign_person_id'      => $signPersonId,
            'person_name'         => $personName,
            'person_nik'          => $personNik,
            'person_birth_place'  => $birthPlace,
            'person_birth_date_dmy'  => $birthDate?->format('d-m-Y'),
            'person_birth_date_long' => $birthDate ? $this->formatTanggalIndo($birthDate) : null,
            'person_address'      => $address,
            'person_city'         => $city,
            'position_name'       => $meta['position_name'] ?? null,
            'placement_unit_name' => $unit?->name,
            'salary_amount'              => $baseSalary,
            'salary_amount_raw'          => $rawBaseSalary,
            'salary_amount_rp'           => $baseSalaryRp,
            'salary_amount_words'        => $baseSalaryWords,
            'salary_amount_words_upper'  => $baseSalaryWordsUpper,
            'lunch_daily_amount'         => $lunchDaily,
            'lunch_daily_amount_raw'     => $rawLunchDaily,
            'lunch_daily_amount_rp'      => $lunchDailyRp,
            'lunch_daily_amount_words'   => $lunchDailyWords,
            'lunch_daily_amount_words_upper' => $lunchDailyWordsUpper,
            'allowance_special_amount'            => $special,
            'allowance_special_amount_raw'        => $rawSpecial,
            'allowance_special_amount_rp'         => $specialRp,
            'allowance_special_amount_words'      => $specialWords,
            'allowance_special_amount_words_upper'=> $specialWordsUpper,
            'allowance_position_amount'           => $position,
            'allowance_position_amount_raw'       => $rawPosition,
            'allowance_position_amount_rp'        => $positionRp,
            'allowance_position_amount_words'     => $positionWords,
            'allowance_position_amount_words_upper'=> $positionWordsUpper,
            'allowance_communication_amount'           => $comm,
            'allowance_communication_amount_raw'       => $rawComm,
            'allowance_communication_amount_rp'        => $commRp,
            'allowance_communication_amount_words'     => $commWords,
            'allowance_communication_amount_words_upper'=> $commWordsUpper,
            'allowance_other_amount'           => $other,
            'allowance_other_amount_raw'       => $rawOther,
            'allowance_other_amount_rp'        => $otherRp,
            'allowance_other_amount_words'     => $otherWords,
            'allowance_other_amount_words_upper'=> $otherWordsUpper,
            'allowance_other_desc'             => $otherDesc,
            'other_benefits_desc'             => $otherBenefitDesc,
            'base_contract_start_dmy' => $baseContractFrom?->format('d-m-Y'),
            'base_contract_start_long'=> $baseContractFrom ? $this->formatTanggalIndo($baseContractFrom) : null,
            'base_contract_end_dmy'   => $baseContractTo?->format('d-m-Y'),
            'base_contract_end_long'  => $baseContractTo ? $this->formatTanggalIndo($baseContractTo) : null,
            'duration_months'         => $durationMonths,
            'duration_months_words'   => $durationMonthsWords,
            'pb_effective_end_dmy'         => $pbEndDate?->format('d-m-Y'),
            'pb_effective_end_long'        => $pbEndDate ? $this->formatTanggalIndo($pbEndDate) : null,
            'pb_compensation_amount'       => $pbCompAmount,
            'pb_compensation_amount_raw'   => $rawPbComp,
            'pb_compensation_amount_rp'    => $pbCompAmountRp,
            'pb_compensation_amount_words' => $pbCompAmountW,
            'pb_compensation_amount_words_upper' => $pbCompAmountWordsUpper,
        ];
    }

    protected function formatTanggalIndo(Carbon $date): string
    {
        $bulan = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $d = (int) $date->format('j');
        $m = (int) $date->format('n');
        $y = $date->format('Y');

        return $d.' '.$bulan[$m].' '.$y;
    }

    protected function getHariIndo(Carbon $date): string
    {
        $map = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];

        $eng = $date->format('l');

        return $map[$eng] ?? $eng;
    }

    protected function normalizeMoney($raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', (string) $raw);
        if ($digits === '' || ! ctype_digit($digits)) {
            return null;
        }

        return (int) $digits;
    }

    protected function formatMoney(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    protected function terbilangIndonesia(int $nilai): string
    {
        $nilai = abs($nilai);
        $huruf = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'
        ];

        if ($nilai < 12) {
            return $huruf[$nilai];
        } elseif ($nilai < 20) {
            return $this->terbilangIndonesia($nilai - 10).' belas';
        } elseif ($nilai < 100) {
            return $this->terbilangIndonesia(intval($nilai / 10)).' puluh '.$this->terbilangIndonesia($nilai % 10);
        } elseif ($nilai < 200) {
            return 'seratus '.$this->terbilangIndonesia($nilai - 100);
        } elseif ($nilai < 1000) {
            return $this->terbilangIndonesia(intval($nilai / 100)).' ratus '.$this->terbilangIndonesia($nilai % 100);
        } elseif ($nilai < 2000) {
            return 'seribu '.$this->terbilangIndonesia($nilai - 1000);
        } elseif ($nilai < 1000000) {
            return $this->terbilangIndonesia(intval($nilai / 1000)).' ribu '.$this->terbilangIndonesia($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            return $this->terbilangIndonesia(intval($nilai / 1000000)).' juta '.$this->terbilangIndonesia($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            return $this->terbilangIndonesia(intval($nilai / 1000000000)).' miliar '.$this->terbilangIndonesia($nilai % 1000000000);
        }

        return (string) $nilai;
    }

    protected function storeBase64Image(?string $dataUrl, string $disk, string $dir): array
    {
        if (empty($dataUrl)) {
            return [null, null];
        }

        $data = $dataUrl;
        $ext  = 'png';

        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
            $ext  = strtolower($m[1]);
            $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
        }

        $binary = base64_decode($data);
        if ($binary === false) {
            return [null, null];
        }

        $filename = $dir.'/'.uniqid('img_', true).'.'.$ext;
        Storage::disk($disk)->put($filename, $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $binary);
        $hash = hash_file('sha256', $tmp);
        @unlink($tmp);

        return [$filename, $hash];
    }
}
