<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Applicant;
use App\Models\Unit;
use App\Models\Document;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    /**
     * LIST + FILTER KONTRAK
     */
    public function index(Request $request)
    {
        $me = $request->user();

        // Hanya Superadmin & DHC bisa lihat semua unit
        $canSeeAll = $me?->hasRole('Superadmin') || $me?->hasRole('DHC');
        $meUnit = (int) ($me->unit_id ?? 0);


        $selectedUnitId = $canSeeAll
            ? ($request->filled('unit_id') ? (int) $request->integer('unit_id') : null)
            : (int) ($me?->unit_id);

        $units = $canSeeAll
            ? Unit::orderBy('name')->get(['id', 'name', 'code'])
            : Unit::where('id', $selectedUnitId)->get(['id', 'name', 'code']);

        $statusFilter = $request->input('status');
        $searchFilter = $request->input('q');

        // ===========================
        // LIST KONTRAK (monitoring)
        // ===========================
        $contractsQuery = Contract::query()
            ->with(['unit'])
            ->when($selectedUnitId, function ($q) use ($selectedUnitId) {
                if ($selectedUnitId) {
                    $q->where('unit_id', $selectedUnitId);
                }
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

        // ===========================
        // Master data dari config
        // ===========================
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

        // ===========================
        // Pelamar untuk SPK / PKWT Baru
        // ===========================
        $eligibleStatuses = config('recruitment.contract_applicant_statuses', ['APPROVED']);

        $applicants = Applicant::query()
            ->with(['unit'])
            ->when($selectedUnitId, function ($q) use ($selectedUnitId) {
                if ($selectedUnitId) {
                    $q->where('unit_id', $selectedUnitId);
                }
            })
            ->whereIn('status', $eligibleStatuses)
            ->orderBy('full_name')
            ->get();

        // ===========================
        // KONTRAK PKWT YANG AKAN BERAKHIR
        // basis: tabel contracts sendiri (PKWT aktif)
        // ===========================
// ===========================
// KONTRAK PKWT YANG AKAN BERAKHIR (via portfolio_histories)
// ===========================
$today  = now()->toDateString();
$meUnit = (int) ($me->unit_id ?? 0);

$expiringContracts = DB::table('portfolio_histories AS ph')
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
    ->when(!$canSeeAll, function ($q) use ($meUnit) {
        $q->where('e.unit_id', $meUnit);
    })
    ->orderBy('ph.end_date')
    ->limit(300)
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

    /**
     * SIMPAN DRAFT KONTRAK
     */
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
            'source_contract_id' => 'nullable|integer|exists:contracts,id',
        ]);

        // Scope unit
        if (! $me->hasRole('Superadmin') && ! $me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                abort(403, 'Tidak boleh membuat kontrak di luar unit Anda.');
            }
        }

        // Konfigurasi jenis kontrak
        $ctCode     = $validated['contract_type'];
        $typeConfig = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $ctCode);

        if (! $typeConfig) {
            return back()
                ->withErrors(['contract_type' => 'Jenis kontrak tidak dikenali.'])
                ->withInput();
        }

        $mode              = (string) ($typeConfig['mode'] ?? 'new'); // new / extend
        $requiresApplicant = (bool) ($typeConfig['requires_applicant'] ?? false);
        $requiresExisting  = (bool) ($typeConfig['requires_existing_contract'] ?? false);

        if ($requiresApplicant && empty($validated['applicant_id'])) {
            return back()
                ->withErrors(['applicant_id' => 'Pelamar / kandidat wajib dipilih untuk jenis kontrak ini.'])
                ->withInput();
        }

        if ($requiresExisting && empty($validated['source_contract_id'])) {
            return back()
                ->withErrors(['source_contract_id' => 'Pilih kontrak yang menjadi dasar perpanjangan / pengakhiran.'])
                ->withInput();
        }

        if ($mode === 'new' && empty($validated['position_name'])) {
            return back()
                ->withErrors(['position_name' => 'Nama jabatan wajib diisi untuk kontrak baru.'])
                ->withInput();
        }

        // Ambil data pelamar (jika perlu)
        $applicant = null;
        if (! empty($validated['applicant_id']) && $requiresApplicant) {
            $applicant = Applicant::query()
                ->where('id', $validated['applicant_id'])
                ->firstOrFail();
        }

        // Ambil kontrak existing (untuk extend / PB)
        $baseContract = null;
        if ($requiresExisting && ! empty($validated['source_contract_id'])) {
            $baseContract = Contract::query()
                ->where('id', $validated['source_contract_id'])
                ->firstOrFail();
        }

        $requiresDraw = (bool) ($typeConfig['requires_draw_signature'] ?? true);
        $requiresCam  = (bool) ($typeConfig['requires_camera'] ?? false);
        $requiresGeo  = (bool) ($typeConfig['requires_geolocation'] ?? false);

        $contract = new Contract();
        $contract->contract_no   = null;        // diisi saat approve
        $contract->contract_type = $ctCode;

        // === RULE DATA SOURCE ===
        // 1) Jika extend → dari kontrak existing
        if ($baseContract) {
            $contract->person_id         = $baseContract->person_id;
            $contract->employee_id       = $baseContract->employee_id;
            $contract->unit_id           = $baseContract->unit_id;
            $contract->position_id       = $baseContract->position_id;
            $contract->position_level_id = $baseContract->position_level_id;
            $contract->employment_type   = $baseContract->employment_type;
            $contract->start_date        = $validated['start_date'] ?? null;
            $contract->end_date          = $validated['end_date'] ?? null;
        }
        // 2) Jika kontrak baru (SPK / PKWT Baru) → dari Applicant
        elseif ($applicant) {
            $contract->person_id       = $applicant->person_id ?? null;
            $contract->employee_id     = null;
            $contract->unit_id         = $validated['unit_id'];
            $contract->employment_type = $validated['employment_type'] ?? null;
            $contract->start_date      = $validated['start_date'] ?? null;
            $contract->end_date        = $validated['end_date'] ?? null;
        }
        // 3) Fallback
        else {
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

        // Simpan catatan/remarks ke meta.remark
        $meta = [
            'remarks'       => $validated['remarks'] ?? null,
            'position_name' => $validated['position_name'] ?? null,
        ];
        $contract->remuneration_json = $meta;

        $contract->save();

        return redirect()
            ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Draft kontrak berhasil dibuat.');
    }

    /**
     * UPDATE KONTRAK DRAFT
     */
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
            'type'               => 'required|string|max:40',
            'unit_id'            => 'required|integer|exists:units,id',
            'applicant_id'       => 'nullable|string|exists:applicants,id',
            'position'           => 'required|string|max:191',
            'employment_type'    => 'nullable|string|max:40',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'note'               => 'nullable|string|max:2000',
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
        $meta['remarks']       = $validated['note'] ?? null;
        $meta['position_name'] = $validated['position'] ?? ($meta['position_name'] ?? null);
        $contract->remuneration_json = $meta;

        $contract->save();

        return redirect()
            ->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Draft kontrak berhasil diperbarui.');
    }

    /**
     * SUBMIT DRAFT → REVIEW
     */
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

    /**
     * APPROVE KONTRAK → nomor + dokumen
     */
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
                $document = $this->ensureContractDocumentExists($contract, $me->id, $me->person_id ?? null);
                $contract->document_id = $document->id;
            }

            $contract->status = 'approved';
            $contract->save();
        });

        return back()->with('ok', 'Kontrak disetujui dan siap untuk e-sign.');
    }

    /**
     * DETAIL KONTRAK (JSON)
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
                'id'                => $contract->id,
                'contract_no'       => $contract->contract_no,
                'contract_type'     => $contract->contract_type,
                'status'            => $contract->status,
                'position_name'     => $meta['position_name'] ?? null,
                'remarks'           => $meta['remarks'] ?? null,
                'employment_type'   => $contract->employment_type,
                'start_date'        => optional($contract->start_date)->format('Y-m-d'),
                'end_date'          => optional($contract->end_date)->format('Y-m-d'),
                'unit'              => $contract->unit?->only(['id', 'name', 'code']),
                'requires_draw'     => (bool) $contract->requires_draw_signature,
                'requires_camera'   => (bool) $contract->requires_camera,
                'requires_geo'      => (bool) $contract->requires_geolocation,
                'has_document'      => (bool) $contract->document_id,
                'document_id'       => $contract->document_id,
                'signatures'        => $signatures,
            ],
        ]);
    }

    /**
     * E-SIGN KONTRAK
     */
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

    /* =======================================================================
     *  HELPER: NOMOR KONTRAK & DOKUMEN
     * ======================================================================= */

    protected function generateContractNumber(Contract $contract): string
    {
        $contract->loadMissing('unit');

        $type = strtoupper((string) $contract->contract_type);
        if (str_contains($type, 'SPK')) {
            $prefix = 'SPK';
        } elseif (str_contains($type, 'PKWT')) {
            $prefix = 'PKWT';
        } elseif (str_contains($type, 'PB')) {
            $prefix = 'PB';
        } else {
            $prefix = 'CTR';
        }

        $unitCode  = $contract->unit?->code ?: 'UNIT';
        $now       = now();
        $year      = $now->format('Y');
        $yearMonth = $now->format('Ym');

        $base = "{$prefix}/{$unitCode}/{$yearMonth}";

        $lastNo = Contract::query()
            ->where('contract_type', $contract->contract_type)
            ->where('unit_id', $contract->unit_id)
            ->whereYear('created_at', $year)
            ->whereNotNull('contract_no')
            ->where('contract_no', 'like', $base.'/%')
            ->orderByDesc('contract_no')
            ->value('contract_no');

        $seq = 1;
        if ($lastNo) {
            $parts   = explode('/', $lastNo);
            $lastSeq = (int) end($parts);
            $seq     = $lastSeq + 1;
        }

        return $base.'/'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
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

        return Document::create([
            'person_id'    => $contract->person_id,
            'employee_id'  => $contract->employee_id,
            'doc_type'     => $docType,
            'storage_disk' => $disk,
            'path'         => $path,
            'mime'         => 'application/pdf',
            'meta'         => [
                'source'        => 'rekrutmen.contract',
                'contract_id'   => $contract->id,
                'contract_no'   => $contract->contract_no,
                'unit_id'       => $contract->unit_id,
                'unit_name'     => $contract->unit?->name,
                'template_key'  => $templateKey,
                'template_path' => $templatePath,
                'generated_by'  => $userId,
                'generated_at'  => now()->toDateTimeString(),
            ],
        ]);
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
