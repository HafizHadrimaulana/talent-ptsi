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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ContractController extends Controller
{
    /**
     * INDEX (LIST + FILTER)
     */
    public function index(Request $request)
    {
        $me = $request->user();

        $isSuperadmin = $me?->hasRole('Superadmin');
        $isDhc        = $me?->hasRole('DHC');
        $canSeeAll    = $isSuperadmin || $isDhc;
        $meUnit       = (int) ($me->unit_id ?? 0);

        // Kepala Unit: punya approve tapi tidak punya update → tidak boleh lihat draft
        $isApproverOnly = $me && $me->can('contract.approve') && ! $me->can('contract.update');

        $selectedUnitId = $canSeeAll
            ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null)
            : $meUnit;

        /**
         * UNITS
         */
        $unitsQuery = Unit::query();
        if ($isSuperadmin) {
            $unitsQuery->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']);
        } elseif ($isDhc) {
            $unitsQuery->where('category', 'ENABLER');
        }

        $units = $canSeeAll
            ? $unitsQuery->orderBy('name')->get(['id', 'name', 'code', 'category'])
            : Unit::where('id', $selectedUnitId)->get(['id', 'name', 'code', 'category']);

        /**
         * CONTRACTS
         */
        $statusFilter = $request->input('status');
        $searchFilter = $request->input('q');

        $contractsQuery = Contract::query()
            ->with(['unit'])
            // Filter unit
            ->when($selectedUnitId, fn ($q) => $q->where('unit_id', $selectedUnitId))
            // DHC khusus ENABLER (kalau bukan Superadmin)
            ->when(
                $isDhc && ! $isSuperadmin,
                fn ($q) => $q->whereHas('unit', fn ($u) => $u->where('category', 'ENABLER'))
            )
            // Superadmin hanya ENABLER / CABANG / OPERASI
            ->when(
                $isSuperadmin,
                fn ($q) => $q->whereHas('unit', fn ($u) => $u->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']))
            )
            // Kepala Unit (approver only) tidak boleh lihat draft
            ->when(
                $isApproverOnly,
                fn ($q) => $q->where('status', '!=', 'draft')
            )
            // Filter status
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            // Search
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

        /**
         * CONTRACT TYPES
         */
        $contractTypeConfigs = config('recruitment.contract_types', []);

        // Untuk dropdown simple: code => label
        $contractTypes = collect($contractTypeConfigs)
            ->mapWithKeys(function ($row) {
                $code  = $row['code'] ?? '';
                $label = $row['label'] ?? $code;
                return [$code => $label];
            })
            ->all();

        /**
         * EMPLOYMENT TYPES — FULL DINAMIS dari employees.employee_status
         */
        $employmentTypes = collect();

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'employee_status')) {
            $employmentRows = DB::table('employees')
                ->select('employee_status')
                ->whereNotNull('employee_status')
                ->distinct()
                ->orderBy('employee_status')
                ->get();

            $employmentTypes = $employmentRows
                ->map(function ($row) {
                    $label = trim($row->employee_status);
                    return [
                        'value' => $label,   // simpan apa adanya (no hardcode)
                        'label' => $label,
                    ];
                })
                ->values();
        }

        // Fallback ke config kalau kosong (jaga-jaga)
        if ($employmentTypes->isEmpty()) {
            $cfg = config('recruitment.employment_types', []);
            $employmentTypes = collect($cfg)
                ->map(function ($label, $code) {
                    return [
                        'value' => $code,
                        'label' => $label,
                    ];
                })
                ->values();
        }

        /**
         * APPLICANTS
         */
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

        if ($isDhc && !$isSuperadmin) {
            $applicantsQuery->where('u.category', 'ENABLER');
        }

        if ($isSuperadmin) {
            $applicantsQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        }

        $applicants = $applicantsQuery->orderBy('full_name')->get();

        /**
         * KONTRAK AKAN HABIS 30 HARI
         */
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

        if (!$canSeeAll && $meUnit) {
            $expiringQuery->where('e.unit_id', $meUnit);
        }
        if ($isDhc && ! $isSuperadmin) {
            $expiringQuery->where('u.category', 'ENABLER');
        }
        if ($isSuperadmin) {
            $expiringQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        }

        $expiringContracts = $expiringQuery->orderBy('ph.end_date')->get();

        return view('recruitment.contracts.index', [
            'contracts'               => $contracts,
            'units'                   => $units,
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

    /**
     * STORE (CREATE DRAFT)
     *
     * Flow:
     * - SDM Unit buat draft (draft / submit langsung → review)
     */
    public function store(Request $request)
    {
        $me = $request->user();

        $validated = $request->validate([
            'contract_type'   => 'required|string',
            'unit_id'         => 'required|integer|exists:units,id',
            'applicant_id'    => 'nullable|string|exists:applicants,id',
            'position_name'   => 'nullable|string|max:191',
            'employment_type' => 'nullable|string|max:60',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'remarks'         => 'nullable|string|max:2000',
            'source_contract_id' => 'nullable|integer',
            'salary_amount'   => 'nullable|string',
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
            'base_contract_start' => 'nullable|date',
            'base_contract_end'   => 'nullable|date',
            'pb_effective_end'    => 'nullable|date',
            'pb_compensation_amount' => 'nullable|string',
            'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit',
        ]);

        // Validasi akses unit
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                return back()->withErrors(['unit_id' => 'Tidak boleh membuat kontrak di luar unit Anda.'])->withInput();
            }
        }

        $ctCode = $validated['contract_type'];
        $typeConfig = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $ctCode);

        if (! $typeConfig) {
            return back()->withErrors(['contract_type' => 'Jenis kontrak tidak dikenali.'])->withInput();
        }

        $mode = $typeConfig['mode'] ?? 'new';
        $requiresApplicant = $typeConfig['requires_applicant'] ?? false;
        $requiresExisting = $typeConfig['requires_existing_contract'] ?? false;

        if ($requiresApplicant && empty($validated['applicant_id'])) {
            return back()->withErrors(['applicant_id' => 'Pelamar wajib dipilih.'])->withInput();
        }

        if ($requiresExisting && empty($validated['source_contract_id'])) {
            return back()->withErrors(['source_contract_id' => 'Pilih kontrak dasar (perpanjangan/pengakhiran).'])->withInput();
        }

        if ($mode === 'new' && empty($validated['position_name'])) {
            return back()->withErrors(['position_name' => 'Nama jabatan wajib diisi.'])->withInput();
        }

        // Ambil data applicant atau kontrak dasar
        $applicant = null;
        $baseContract = null;
        $personId = null;
        $employeeId = null;

        if ($requiresApplicant && $validated['applicant_id']) {
            $applicant = Applicant::find($validated['applicant_id']);
            if ($applicant && $applicant->person_id) {
                $personId = $applicant->person_id;
            }
        }

        if ($requiresExisting && ! empty($validated['source_contract_id'])) {
            $baseContract = Contract::find($validated['source_contract_id']);
            if ($baseContract) {
                $personId   = $baseContract->person_id;
                $employeeId = $baseContract->employee_id;
            }
        }

        DB::beginTransaction();
        try {
            $contract = new Contract();
            $contract->contract_no      = null;
            $contract->contract_type    = $ctCode;
            $contract->unit_id          = $validated['unit_id'];
            $contract->employment_type  = $validated['employment_type'] ?? null;
            $contract->person_id        = $personId;
            $contract->employee_id      = $employeeId;

            // Simpan applicant_id jika kolomnya ada
            if (Schema::hasColumn('contracts', 'applicant_id') && ! empty($validated['applicant_id'])) {
                $contract->applicant_id = $validated['applicant_id'];
            }

            $contract->start_date = $validated['start_date'] ?? null;
            $contract->end_date   = $validated['end_date'] ?? null;

            // Simpan remuneration sebagai JSON
            $remunerationData = $this->collectMeta($validated);
            $remunerationData['position_name'] = $validated['position_name'] ?? null;
            $remunerationData['remarks']       = $validated['remarks'] ?? null;
            $contract->remuneration_json       = $remunerationData;

            // Verification requirements (checkbox)
            $contract->requires_draw_signature = $request->has('requires_draw_signature') ? true : false;
            $contract->requires_camera         = $request->has('requires_camera') ? true : false;
            $contract->requires_geolocation    = $request->has('requires_geolocation') ? true : false;

            // Status berdasarkan submit_action
            if ($validated['submit_action'] === 'submit') {
                $contract->status       = 'review';
                $contract->contract_no  = $this->generateContractNumber($contract);
            } else {
                $contract->status = 'draft';
            }

            $contract->created_by_user_id   = $me->id;
            $contract->created_by_person_id = $me->person_id ?? null;
            $contract->save();

            // Jika submit, buat approval record
            if ($validated['submit_action'] === 'submit') {
                $approval = new Approval();
                $approval->approvable_type      = 'contract';
                $approval->approvable_id        = $contract->id;
                $approval->requester_person_id  = $me->person_id;
                $approval->requester_user_id    = $me->id;
                $approval->status               = 'pending';
                $approval->note                 = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
                $approval->save();
            }

            DB::commit();

            if ($validated['submit_action'] === 'submit') {
                return redirect()
                    ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
                    ->with('success', 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.');
            }

            return redirect()
                ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
                ->with('success', 'Draft kontrak berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating contract: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat kontrak: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * UPDATE DRAFT
     */
    public function update(Request $request, Contract $contract)
    {
        $me = $request->user();

        // Validasi akses
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya draft yang bisa diedit.')->withInput();
        }

        $validated = $request->validate([
            'contract_type'   => 'nullable|string',
            'unit_id'         => 'required|integer|exists:units,id',
            'applicant_id'    => 'nullable|string|exists:applicants,id',
            'position_name'   => 'nullable|string|max:191',
            'employment_type' => 'nullable|string|max:60',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'remarks'         => 'nullable|string|max:2000',
            'salary_amount'   => 'nullable|string',
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
            'base_contract_start' => 'nullable|date',
            'base_contract_end'   => 'nullable|date',
            'pb_effective_end'    => 'nullable|date',
            'pb_compensation_amount' => 'nullable|string',
            'pb_compensation_amount_words' => 'nullable|string',
            'submit_action' => 'required|in:draft,submit',
        ]);

        DB::beginTransaction();
        try {
            // Update basic info
            if (! empty($validated['contract_type'])) {
                $contract->contract_type = $validated['contract_type'];
            }

            $contract->unit_id         = $validated['unit_id'];
            $contract->employment_type = $validated['employment_type'] ?? $contract->employment_type;
            $contract->start_date      = $validated['start_date'] ?? null;
            $contract->end_date        = $validated['end_date'] ?? null;

            // Update applicant_id jika kolomnya ada
            if (Schema::hasColumn('contracts', 'applicant_id') && array_key_exists('applicant_id', $validated)) {
                $contract->applicant_id = $validated['applicant_id'] ?: null;
            }

            // Update remuneration JSON
            $metaOld = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            $metaNew = $this->collectMeta($validated, true, $metaOld);
            $metaNew['position_name'] = $validated['position_name'] ?? ($metaOld['position_name'] ?? null);
            $metaNew['remarks']       = $validated['remarks'] ?? ($metaOld['remarks'] ?? null);
            $contract->remuneration_json = array_merge($metaOld, $metaNew);

            // Update verification requirements
            $contract->requires_draw_signature = $request->has('requires_draw_signature') ? true : false;
            $contract->requires_camera         = $request->has('requires_camera') ? true : false;
            $contract->requires_geolocation    = $request->has('requires_geolocation') ? true : false;

            // Jika submit dari draft
            if ($validated['submit_action'] === 'submit') {
                $contract->status = 'review';
                if (! $contract->contract_no) {
                    $contract->contract_no = $this->generateContractNumber($contract);
                }

                // Buat approval record baru
                $approval = new Approval();
                $approval->approvable_type      = 'contract';
                $approval->approvable_id        = $contract->id;
                $approval->requester_person_id  = $me->person_id;
                $approval->requester_user_id    = $me->id;
                $approval->status               = 'pending';
                $approval->note                 = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
                $approval->save();
            }

            $contract->save();
            DB::commit();

            if ($validated['submit_action'] === 'submit') {
                return back()->with('success', 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.');
            }

            return back()->with('success', 'Draft kontrak diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating contract: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memperbarui kontrak: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * SUBMIT (draft -> review)
     *
     * Alternatif endpoint via AJAX (tanpa lewat update())
     */
    public function submit(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->can('contract.update')) {
            abort(403, 'Akses ditolak.');
        }

        if ($contract->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya draft yang bisa disubmit.',
            ], 422);
        }

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        DB::beginTransaction();
        try {
            $contract->status = 'review';
            if (! $contract->contract_no) {
                $contract->contract_no = $this->generateContractNumber($contract);
            }

            // Buat approval record
            $approval = new Approval();
            $approval->approvable_type      = 'contract';
            $approval->approvable_id        = $contract->id;
            $approval->requester_person_id  = $me->person_id;
            $approval->requester_user_id    = $me->id;
            $approval->status               = 'pending';
            $approval->note                 = 'Kontrak dikirim untuk review dan e-sign Kepala Unit';
            $approval->save();

            $contract->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kontrak disubmit dan menunggu review Kepala Unit.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error submitting contract: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit kontrak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * APPROVE + E-SIGN KEPALA UNIT
     *
     * Flow: status REVIEW → APPROVED (menunggu e-sign kandidat)
     */
    public function approve(Request $request, Contract $contract)
    {
        $me = auth()->user();

        if (! $me->can('contract.approve')) {
            abort(403, 'Akses ditolak.');
        }

        if ($contract->status !== 'review') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya kontrak status REVIEW yang bisa di-approve.',
            ], 422);
        }

        // Validasi unit
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        // CONDITIONAL: signature wajib hanya jika requires_draw_signature = true
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
            // 1. Update approval record
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->where('status', 'pending')
                ->first();

            if (! $approval) {
                throw new \Exception('Approval record tidak ditemukan.');
            }

            $approval->approver_person_id = $me->person_id;
            $approval->approver_user_id   = $me->id;
            $approval->status             = 'approved';
            $approval->note               = $validated['note'] ?? null;
            $approval->decided_at         = now();
            $approval->save();

            // 2. Pastikan nomor kontrak ada
            if (! $contract->contract_no) {
                $contract->contract_no = $this->generateContractNumber($contract);
            }

            // 3. Buat atau update document
            $typeCfg  = collect(config('recruitment.contract_types', []))
                ->firstWhere('code', $contract->contract_type) ?? [];
            $docType  = $typeCfg['document_type'] ?? $contract->contract_type;
            $docTitle = $typeCfg['label'] ?? ('Kontrak ' . $contract->contract_type);

            if ($contract->document_id) {
                $document = Document::find($contract->document_id);
            } else {
                $document = new Document();
                $document->doc_type             = $docType;
                $document->doc_no               = $contract->contract_no;
                $document->title                = $docTitle;
                $document->storage_disk         = 'local';
                $document->path                 = 'contracts/' . $contract->id . '.pdf';
                $document->mime                 = 'application/pdf';
                $document->size_bytes           = 0;
                $document->meta_json            = $contract->remuneration_json ?? [];
                $document->created_by_user_id   = $me->id;
                $document->created_by_person_id = $me->person_id ?? null;
                $document->save();

                $contract->document_id = $document->id;
            }

            // 4. Simpan tanda tangan Kepala Unit (hanya jika ada data)
            $imgPath = null;
            $imgHash = null;
            $camPath = null;
            $camHash = null;

            if (! empty($validated['signature_image'])) {
                [$imgPath, $imgHash] = $this->storeBase64Image(
                    $validated['signature_image'],
                    'public',
                    'signatures'
                );
            }

            if (! empty($validated['camera_image'])) {
                [$camPath, $camHash] = $this->storeBase64Image(
                    $validated['camera_image'],
                    'public',
                    'signatures/camera'
                );
            }

            if ($imgPath || $camPath) {
                $signature = new Signature();
                $signature->document_id           = $document->id;
                $signature->signer_person_id      = $me->person_id ?? null;
                $signature->signer_user_id        = $me->id;
                $signature->signer_role           = 'Kepala Unit';
                $signature->signature_draw_data   = $validated['signature_image'] ?? null;
                $signature->signature_draw_hash   = $imgHash;
                $signature->camera_photo_path     = $camPath;
                $signature->camera_photo_hash     = $camHash;
                $signature->geo_lat               = $validated['geo_lat'] ?? null;
                $signature->geo_lng               = $validated['geo_lng'] ?? null;
                $signature->geo_accuracy_m        = $validated['geo_accuracy'] ?? null;
                $signature->signed_at             = now();
                $signature->save();
            }

            // 5. Update status kontrak
            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) {
                $meta['approval_note'] = $validated['note'];
            }
            $contract->remuneration_json = $meta;
            $contract->status            = 'approved'; // Menunggu tanda tangan kandidat/pegawai
            $contract->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kontrak telah di-approve dan (jika diisi) e-sign Kepala Unit tersimpan.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving contract: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses approval kontrak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * REJECT — kembalikan ke draft SDM Unit
     *
     * Flow: REVIEW → DRAFT (negosiasi ulang SDM Unit dengan kandidat)
     */
    public function reject(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->can('contract.approve')) {
            abort(403, 'Akses ditolak.');
        }

        if ($contract->status !== 'review') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya kontrak status REVIEW yang bisa dikembalikan ke draft.',
            ], 422);
        }

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // Update approval record
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->where('status', 'pending')
                ->first();

            if ($approval) {
                $approval->approver_person_id = $me->person_id;
                $approval->approver_user_id   = $me->id;
                $approval->status             = 'rejected';
                $approval->note               = $validated['note'] ?? 'Dikembalikan ke SDM Unit untuk revisi';
                $approval->decided_at         = now();
                $approval->save();
            }

            // Update kontrak
            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) {
                $meta['reject_note'] = $validated['note'];
            }

            $contract->remuneration_json = $meta;
            $contract->status            = 'draft';
            $contract->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kontrak dikembalikan ke draft SDM Unit.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error rejecting contract: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses reject kontrak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * E-SIGN KANDIDAT / PEGAWAI
     *
     * Flow: APPROVED → SIGNED
     */
    public function sign(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (! $me->can('contract.sign')) {
            abort(403, 'Akses ditolak.');
        }

        if ($contract->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya kontrak status APPROVED yang bisa ditandatangani kandidat.',
            ], 422);
        }

        // Validasi bahwa ini adalah kandidat yang benar
        if ($contract->person_id && $contract->person_id !== $me->person_id) {
            if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya kandidat terkait yang bisa menandatangani kontrak.',
                ], 403);
            }
        }

        // CONDITIONAL: signature wajib hanya jika requires_draw_signature = true
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
            // 1. Update approval record
            $approval = Approval::where('approvable_type', 'contract')
                ->where('approvable_id', $contract->id)
                ->whereIn('status', ['approved', 'pending'])
                ->first();

            if ($approval) {
                $approval->status = 'completed';
                $approval->note   = $validated['note'] ?? 'Ditandatangani oleh kandidat';
                $approval->save();
            }

            // 2. Pastikan document ada
            if (! $contract->document_id) {
                $typeCfg  = collect(config('recruitment.contract_types', []))
                    ->firstWhere('code', $contract->contract_type) ?? [];
                $docType  = $typeCfg['document_type'] ?? $contract->contract_type;
                $docTitle = $typeCfg['label'] ?? ('Kontrak ' . $contract->contract_type);

                $document = new Document();
                $document->doc_type             = $docType;
                $document->doc_no               = $contract->contract_no;
                $document->title                = $docTitle;
                $document->storage_disk         = 'local';
                $document->path                 = 'contracts/' . $contract->id . '.pdf';
                $document->mime                 = 'application/pdf';
                $document->size_bytes           = 0;
                $document->meta_json            = $contract->remuneration_json ?? [];
                $document->created_by_user_id   = $me->id;
                $document->created_by_person_id = $me->person_id ?? null;
                $document->save();

                $contract->document_id = $document->id;
                $contract->save();
            } else {
                $document = Document::find($contract->document_id);
            }

            // 3. Simpan tanda tangan kandidat (jika ada data)
            $imgPath = null;
            $imgHash = null;
            $camPath = null;
            $camHash = null;

            if (! empty($validated['signature_image'])) {
                [$imgPath, $imgHash] = $this->storeBase64Image(
                    $validated['signature_image'],
                    'public',
                    'signatures'
                );
            }

            if (! empty($validated['camera_image'])) {
                [$camPath, $camHash] = $this->storeBase64Image(
                    $validated['camera_image'],
                    'public',
                    'signatures/camera'
                );
            }

            if ($imgPath || $camPath) {
                $signature = new Signature();
                $signature->document_id           = $document->id;
                $signature->signer_person_id      = $me->person_id ?? null;
                $signature->signer_user_id        = $me->id;
                $signature->signer_role           = 'Kandidat';
                $signature->signature_draw_data   = $validated['signature_image'] ?? null;
                $signature->signature_draw_hash   = $imgHash;
                $signature->camera_photo_path     = $camPath;
                $signature->camera_photo_hash     = $camHash;
                $signature->geo_lat               = $validated['geo_lat'] ?? null;
                $signature->geo_lng               = $validated['geo_lng'] ?? null;
                $signature->geo_accuracy_m        = $validated['geo_accuracy'] ?? null;
                $signature->signed_at             = now();
                $signature->save();
            }

            // 4. Update meta dan status kontrak
            $meta = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
            if (! empty($validated['note'])) {
                $meta['candidate_note'] = $validated['note'];
            }
            $contract->remuneration_json = $meta;
            $contract->status            = 'signed';
            $contract->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kontrak telah ditandatangani kandidat.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error signing contract: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan e-sign kandidat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * SHOW JSON FOR MODAL
     *
     * Dipakai modal detail (card rapi, nanti di-Blade):
     * - nama jabatan
     * - nama pegawai / kandidat (person / applicant / employee)
     * - gaji, tunjangan, dsb dari remuneration_json
     */
    public function show(Contract $contract)
    {
        $me = auth()->user();

        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $contract->load('unit');
        $meta = $contract->remuneration_json ?? [];

        // Get person data (canonical nama orang)
        $person = null;
        if ($contract->person_id) {
            $person = DB::table('persons')
                ->select('id', 'full_name', 'nik_last4')
                ->where('id', $contract->person_id)
                ->first();
        }

        // Get employee data (join ke persons untuk nama)
        $employee = null;
        if ($contract->employee_id) {
            $employee = DB::table('employees as e')
                ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
                ->select('e.id', 'e.employee_id', 'e.email', 'e.employee_status', 'p.full_name as person_name')
                ->where('e.employee_id', $contract->employee_id)
                ->first();
        }

        // Get applicant data kalau kolom applicant_id ada
        $applicant = null;
        if (Schema::hasColumn('contracts', 'applicant_id') && ! empty($contract->applicant_id)) {
            $applicant = DB::table('applicants as a')
                ->leftJoin('units as u', 'u.id', '=', 'a.unit_id')
                ->select('a.id', 'a.full_name', 'a.position_name', 'u.name as unit_name')
                ->where('a.id', $contract->applicant_id)
                ->first();
        }

        $personName = $person->full_name ?? null;
        if (! $personName && $applicant) {
            $personName = $applicant->full_name;
        }

        $employeeName = null;
        if ($employee) {
            $employeeName = $employee->person_name ?? $employee->employee_id ?? $employee->email;
        }

        $displayName = $personName ?: $employeeName;

        $typeCfg = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $contract->contract_type) ?? [];

        // Get signatures
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

        // Get approval info
        $approval = Approval::where('approvable_type', 'contract')
            ->where('approvable_id', $contract->id)
            ->first();

        // Flow hint
        $flowHint = '';
        $rawType = $contract->contract_type;
        $status  = $contract->status;

        if ($rawType === 'SPK') {
            if ($status === 'draft') {
                $flowHint = 'Draft SPK: disusun oleh SDM Unit.';
            } elseif ($status === 'review') {
                $flowHint = 'SPK: menunggu review + e-sign Kepala Unit.';
            } elseif ($status === 'approved') {
                $flowHint = 'SPK: menunggu approval + e-sign kandidat.';
            } elseif ($status === 'signed') {
                $flowHint = 'SPK: sudah disetujui dan ditandatangani kandidat.';
            }
        } elseif (in_array($rawType, ['PKWT_BARU', 'PKWT_PERPANJANGAN'])) {
            if ($status === 'draft') {
                $flowHint = 'Draft PKWT: disusun oleh SDM Unit.';
            } elseif ($status === 'review') {
                $flowHint = 'PKWT: menunggu review + e-sign Kepala Unit.';
            } elseif ($status === 'approved') {
                $flowHint = 'PKWT: menunggu e-sign kandidat.';
            } elseif ($status === 'signed') {
                $flowHint = 'PKWT: telah ditandatangani kandidat.';
            }
        } elseif ($rawType === 'PB_PENGAKHIRAN') {
            if ($status === 'draft') {
                $flowHint = 'Draft PB: disusun oleh SDM Unit.';
            } elseif ($status === 'review') {
                $flowHint = 'PB: menunggu review + e-sign Kepala Unit.';
            } elseif ($status === 'approved') {
                $flowHint = 'PB: menunggu e-sign pihak terkait.';
            } elseif ($status === 'signed') {
                $flowHint = 'PB: Perjanjian Bersama telah ditandatangani.';
            }
        }

        // Hak akses sesuai flow
        $canApprove = $me && $me->can('contract.approve') && $contract->status === 'review';
        $canReject  = $canApprove;
        $canSign    = $me && $me->can('contract.sign') && $contract->status === 'approved';

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $contract->id,
                'contract_no'       => $contract->contract_no,
                'contract_type'     => $contract->contract_type,
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

    /**
     * GENERATE CONTRACT NUMBER
     */
    protected function generateContractNumber(Contract $contract): string
    {
        $contract->loadMissing('unit');

        $type    = $contract->contract_type;
        $cfg     = config('recruitment.numbering', []);
        $docCodes   = $cfg['doc_codes'] ?? [];
        $defaultHead = $cfg['default_head_code'] ?? 'XX';

        $docCode  = $docCodes[$type] ?? strtoupper($type);
        $unit     = $contract->unit;
        $unitCode = $unit?->code ?? 'UNIT';

        $now   = now();
        $year  = $now->format('Y');
        $month = $now->format('m');

        $headCode = $this->resolveHeadCodeForUnit($unit) ?: $defaultHead;

        $like = sprintf(
            '%s-%%/%s-%s/%s/%s',
            $docCode,
            $unitCode,
            $month,
            $headCode,
            $year
        );

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

        return sprintf(
            '%s-%03d/%s-%s/%s/%s',
            $docCode,
            $seq,
            $unitCode,
            $month,
            $headCode,
            $year
        );
    }

    /**
     * HEAD CODE RESOLVER
     *
     * Ambil inisial dari full name Kepala Unit (contoh:
     * - "Deddi Nurmal" → DN
     * - "Edwin Pantas" → EP
     */
    protected function resolveHeadCodeForUnit(?Unit $unit): string
    {
        $default = config('recruitment.numbering.default_head_code', 'XX');
        if (!$unit) {
            return $default;
        }

        $headRoles = config('recruitment.numbering.head_role_names', ['Kepala Unit']);

        // Cari user Kepala Unit untuk unit ini (role dinamis, no hardcode id)
        $user = User::query()
            ->join('model_has_roles as mhr', function ($join) use ($unit) {
                $join->on('mhr.model_id', '=', 'users.id')
                    ->where('mhr.model_type', '=', User::class)
                    ->where('mhr.unit_id', '=', $unit->id);
            })
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->whereIn('roles.name', $headRoles)
            ->where(function ($q) use ($unit) {
                $q->whereNull('roles.unit_id')
                    ->orWhere('roles.unit_id', $unit->id);
            })
            ->where('users.unit_id', $unit->id)
            ->select('users.*')
            ->first();

        $full = null;
        if ($user) {
            if ($user->person_id) {
                $p    = DB::table('persons')->where('id', $user->person_id)->first();
                $full = $p->full_name ?? $user->name;
            } else {
                $full = $user->name;
            }
        }

        if (! $full) {
            return $default;
        }

        $parts = preg_split('/\s+/', trim($full));
        if (count($parts) < 1) {
            return $default;
        }

        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last  = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');

        return mb_strtoupper($first . $last, 'UTF-8');
    }

    /**
     * COLLECT META (remuneration_json)
     */
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

    /**
     * STORE BASE64 IMAGE
     */
    protected function storeBase64Image(?string $dataUrl, string $disk, string $dir): array
    {
        if (! $dataUrl || ! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            return [null, null];
        }

        $extension = strtolower($matches[1]);
        $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return [null, null];
        }

        $filename = uniqid('sig_', true) . '.' . $extension;
        $path     = $dir . '/' . $filename;

        Storage::disk($disk)->put($path, $imageData);

        $hash = hash('sha256', $imageData);

        return [$path, $hash];
    }

    /**
     * FORMAT TANGGAL INDONESIA
     */
    protected function formatTanggalIndo(Carbon $d): string
    {
        $bulan = [
            1  => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5  => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9  => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    /**
     * TERBILANG INDONESIA
     */
    protected function terbilangIndonesia(int $n): string
    {
        $n = abs($n);
        $huruf = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'
        ];

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
}