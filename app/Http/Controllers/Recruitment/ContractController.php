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
            ->when($selectedUnitId, fn($q) => $q->where('unit_id', $selectedUnitId))
            ->when(
                $isDhc && !$isSuperadmin,
                fn($q) => $q->whereHas('unit', fn($u) => $u->where('category', 'ENABLER'))
            )
            ->when(
                $isSuperadmin,
                fn($q) => $q->whereHas('unit', fn($u) => $u->whereIn('category', ['ENABLER', 'CABANG', 'OPERASI']))
            )
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($searchFilter, function ($q) use ($searchFilter) {
                $q->where(function ($qq) use ($searchFilter) {
                    $qq->where('contract_no', 'like', '%' . $searchFilter . '%')
                        ->orWhere('employment_type', 'like', '%' . $searchFilter . '%');
                });
            })
            ->orderByDesc('created_at');

        $contracts = $contractsQuery->paginate(25)->withQueryString();

        /**
         * CONTRACT TYPES (pakai config mentah + mapping label)
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
         * EMPLOYMENT TYPES — FULL DINAMIS
         */
        $employmentTypes = collect();

        if (Schema::hasTable('employee_status')) {
            $employmentRows = DB::table('employee_status')
                ->orderBy('name')
                ->get();

            $employmentTypes = $employmentRows
                ->map(function ($row) {
                    // misal "Kontrak Organik" → "kontrak-organik"
                    $code = strtolower(str_replace([' ', '_'], '-', $row->name));
                    return [
                        'value' => $code,
                        'label' => $row->name,
                    ];
                })
                ->filter(fn($i) => in_array($i['value'], [
                    'kontrak-organik',
                    'kontrak-project-based',
                ]))
                ->values();
        }

        // Fallback config kalau tabel belum ada / kosong
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
         * BUDGET SOURCE – sementara tidak dipakai
         */
        $budgetSourceTypes       = [];
        $budgetSourceTypeConfigs = [];

        /**
         * APPLICANTS (pelamar yang eligible jadi kontrak)
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
         * KONTRAK / PORTOFOLIO AKAN HABIS 30 HARI
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
        if ($isDhc && !$isSuperadmin) {
            $expiringQuery->where('u.category', 'ENABLER');
        }
        if ($isSuperadmin) {
            $expiringQuery->whereIn('u.category', ['ENABLER', 'CABANG', 'OPERASI']);
        }

        $expiringContracts = $expiringQuery->orderBy('ph.end_date')->get();

        return view('recruitment.contracts.index', [
            'contracts'               => $contracts,
            'list'                    => $contracts,
            'units'                   => $units,
            'selectedUnitId'          => $selectedUnitId,
            'statusFilter'            => $statusFilter,
            'statusOptions'           => config('recruitment.contract_statuses'),
            'contractTypes'           => $contractTypes,          // simple: code => label
            'contractTypeConfigs'     => $contractTypeConfigs,    // full config array
            'employmentTypes'         => $employmentTypes,        // collection of [value,label]
            'employmentTypeConfigs'   => config('recruitment.employment_types', []),
            'budgetSourceTypeConfigs' => $budgetSourceTypeConfigs,
            'budgetSourceTypes'       => $budgetSourceTypes,
            'requestTypes'            => config('recruitment.request_types', []),
            'applicants'              => $applicants,
            'expiringContracts'       => $expiringContracts,
            'canSeeAll'               => $canSeeAll,
        ]);
    }

    /**
     * STORE (CREATE DRAFT)
     */
    public function store(Request $request)
    {
        $me = $request->user();

        $validated = $request->validate([
            'contract_type'  => 'required|string',
            'unit_id'        => 'required|integer|exists:units,id',
            'applicant_id'   => 'nullable|string|exists:applicants,id',
            'position_name'  => 'nullable|string|max:191',
            'employment_type'=> 'nullable|string|max:60',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'remarks'        => 'nullable|string|max:2000',

            'source_contract_id' => 'nullable|integer',

            'salary_amount'                 => 'nullable|string',
            'salary_amount_words'           => 'nullable|string',
            'lunch_allowance_daily'         => 'nullable|string',
            'lunch_allowance_words'         => 'nullable|string',
            'allowance_special_amount'      => 'nullable|string',
            'allowance_special_words'       => 'nullable|string',
            'allowance_position_amount'     => 'nullable|string',
            'allowance_position_words'      => 'nullable|string',
            'allowance_communication_amount'=> 'nullable|string',
            'allowance_communication_words' => 'nullable|string',
            'allowance_other_amount'        => 'nullable|string',
            'allowance_other_words'         => 'nullable|string',
            'allowance_other_desc'          => 'nullable|string',
            'other_benefits_desc'           => 'nullable|string',

            'base_contract_start' => 'nullable|date',
            'base_contract_end'   => 'nullable|date',

            'pb_effective_end'           => 'nullable|date',
            'pb_compensation_amount'     => 'nullable|string',
            'pb_compensation_amount_words'=> 'nullable|string',

            'submit_action' => 'required|in:draft,submit'
        ]);

        if (!$me->hasRole('Superadmin') && !$me->hasRole('DHC')) {
            if ((int) $validated['unit_id'] !== (int) $me->unit_id) {
                abort(403, 'Tidak boleh membuat kontrak di luar unit Anda.');
            }
        }

        $ctCode    = $validated['contract_type'];
        $typeConfig = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $ctCode);

        if (!$typeConfig) {
            return back()->withErrors(['contract_type' => 'Jenis kontrak tidak dikenali.']);
        }

        $mode             = $typeConfig['mode'] ?? 'new';
        $requiresApplicant= $typeConfig['requires_applicant'] ?? false;
        $requiresExisting = $typeConfig['requires_existing_contract'] ?? false;

        if ($requiresApplicant && empty($validated['applicant_id'])) {
            return back()->withErrors(['applicant_id' => 'Pelamar wajib dipilih.']);
        }

        if ($requiresExisting && empty($validated['source_contract_id'])) {
            return back()->withErrors(['source_contract_id' => 'Pilih kontrak dasar (perpanjangan/pengakhiran).']);
        }

        if ($mode === 'new' && empty($validated['position_name'])) {
            return back()->withErrors(['position_name' => 'Nama jabatan wajib diisi.']);
        }

        $applicant    = null;
        $baseContract = null;

        if ($requiresApplicant && $validated['applicant_id']) {
            $applicant = Applicant::find($validated['applicant_id']);
        }

        if ($requiresExisting && !empty($validated['source_contract_id'])) {
            $baseContract = Contract::find($validated['source_contract_id']);
        }

        $contract                        = new Contract();
        $contract->contract_no           = null;
        $contract->contract_type         = $ctCode;
        $contract->unit_id               = $validated['unit_id'];
        $contract->employment_type       = $validated['employment_type'] ?? null;

        if ($applicant) {
            $contract->person_id   = $applicant->person_id;
            $contract->employee_id = null;
        } elseif ($baseContract) {
            $contract->person_id   = $baseContract->person_id;
            $contract->employee_id = $baseContract->employee_id;
        }

        $contract->start_date = $validated['start_date'] ?? null;
        $contract->end_date   = $validated['end_date'] ?? null;

        $contract->remuneration_json = $this->collectMeta($validated);

        $contract->requires_draw_signature = true;
        $contract->requires_camera         = true;
        $contract->requires_geolocation    = true;

        // FIX: Determine status based on submit action
        $contract->status = $validated['submit_action'] === 'submit' ? 'review' : 'draft';
        
        $contract->document_id         = null;
        $contract->created_by_user_id  = $me->id;
        $contract->created_by_person_id= $me->person_id ?? null;

        $contract->save();

        // FIX: If submitted, generate contract number and forward to head of unit
        if ($validated['submit_action'] === 'submit') {
            $contract->contract_no = $this->generateContractNumber($contract);
            $contract->save();

            // TODO: Add notification to head of unit here
            return redirect()->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
                ->with('ok', 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.');
        }

        return redirect()->route('recruitment.contracts.index', ['unit_id' => $contract->unit_id])
            ->with('ok', 'Draft kontrak berhasil dibuat.');
    }

    /**
     * UPDATE DRAFT
     */
    public function update(Request $request, Contract $contract)
    {
        $me = $request->user();

        if (!$me->hasRole('Superadmin') && !$me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        if ($contract->status !== 'draft') {
            return back()->withErrors('Hanya draft yang bisa diedit.');
        }

        $validated = $request->validate([
            'contract_type'   => 'nullable|string',
            'unit_id'         => 'required|integer|exists:units,id',
            'position_name'   => 'nullable|string|max:191',
            'employment_type' => 'nullable|string|max:60',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'remarks'         => 'nullable|string|max:2000',
            'note'            => 'nullable|string|max:2000',

            'salary_amount'                 => 'nullable|string',
            'salary_amount_words'           => 'nullable|string',
            'lunch_allowance_daily'         => 'nullable|string',
            'lunch_allowance_words'         => 'nullable|string',
            'allowance_special_amount'      => 'nullable|string',
            'allowance_special_words'       => 'nullable|string',
            'allowance_position_amount'     => 'nullable|string',
            'allowance_position_words'      => 'nullable|string',
            'allowance_communication_amount'=> 'nullable|string',
            'allowance_communication_words' => 'nullable|string',
            'allowance_other_amount'        => 'nullable|string',
            'allowance_other_words'         => 'nullable|string',
            'allowance_other_desc'          => 'nullable|string',
            'other_benefits_desc'           => 'nullable|string',

            'base_contract_start' => 'nullable|date',
            'base_contract_end'   => 'nullable|date',

            'pb_effective_end'            => 'nullable|date',
            'pb_compensation_amount'      => 'nullable|string',
            'pb_compensation_amount_words'=> 'nullable|string',

            'submit_action' => 'required|in:draft,submit'
        ]);

        // FIX: Handle submit action properly
        if ($validated['submit_action'] === 'submit') {
            $contract->status = 'review';
            $contract->contract_no = $this->generateContractNumber($contract);
            
            // TODO: Add notification to head of unit here
        }

        if (!empty($validated['contract_type'])) {
            $contract->contract_type = $validated['contract_type'];
        }

        $contract->unit_id         = $validated['unit_id'];
        $contract->employment_type = $validated['employment_type'] ?? $contract->employment_type;
        $contract->start_date      = $validated['start_date'] ?? null;
        $contract->end_date        = $validated['end_date'] ?? null;

        $metaOld = is_array($contract->remuneration_json) ? $contract->remuneration_json : [];
        $metaNew = $this->collectMeta($validated, true, $metaOld);
        $contract->remuneration_json = array_merge($metaOld, $metaNew);

        $contract->save();

        if ($validated['submit_action'] === 'submit') {
            return back()->with('ok', 'Kontrak berhasil disubmit dan dikirim ke Kepala Unit untuk review.');
        }

        return back()->with('ok', 'Draft kontrak diperbarui.');
    }

    /**
     * SHOW JSON FOR MODAL
     */
    public function show(Contract $contract)
    {
        $me = auth()->user();

        if (!$me->hasRole('Superadmin') && !$me->hasRole('DHC')) {
            if ((int) $contract->unit_id !== (int) $me->unit_id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $contract->load('unit');
        $meta = $contract->remuneration_json ?? [];

        $person = null;
        if ($contract->person_id) {
            $person = DB::table('persons')
                ->select('id', 'full_name', 'id_number')
                ->where('id', $contract->person_id)
                ->first();
        }

        $employee = null;
        if ($contract->employee_id) {
            $employee = DB::table('employees')
                ->select('id', 'employee_id', 'full_name', 'nik', 'employee_status')
                ->where('id', $contract->employee_id)
                ->first();
        }

        $typeCfg = collect(config('recruitment.contract_types', []))
            ->firstWhere('code', $contract->contract_type) ?? [];

        $signatures = [];
        if ($contract->document_id) {
            $signatures = Signature::where('document_id', $contract->document_id)
                ->orderBy('signed_at')
                ->get()
                ->map(fn($sig) => [
                    'id'             => $sig->id,
                    'signer_role'    => $sig->signer_role,
                    'signed_at'      => optional($sig->signed_at)->toDateTimeString(),
                    'geo_lat'        => $sig->geo_lat,
                    'geo_lng'        => $sig->geo_lng,
                    'geo_accuracy'   => $sig->geo_accuracy_m,
                ]);
        }

        return response()->json([
            'data' => [
                'id'               => $contract->id,
                'contract_no'      => $contract->contract_no,
                'contract_type'    => $contract->contract_type,
                'status'           => $contract->status,
                'employment_type'  => $contract->employment_type,
                'start_date'       => optional($contract->start_date)->format('Y-m-d'),
                'end_date'         => optional($contract->end_date)->format('Y-m-d'),
                'unit'             => $contract->unit?->only(['id', 'name', 'code']),
                'requires_draw'    => true,
                'requires_camera'  => true,
                'requires_geo'     => true,
                'has_document'     => !!$contract->document_id,
                'document_id'      => $contract->document_id,

                'meta'             => $meta,
                'position_name'    => $meta['position_name'] ?? null,
                'remarks'          => $meta['remarks'] ?? null,

                'person' => $person ? [
                    'id'        => $person->id,
                    'full_name' => $person->full_name,
                    'id_number' => $person->id_number,
                ] : null,
                'employee' => $employee ? [
                    'id'              => $employee->id,
                    'employee_id'     => $employee->employee_id,
                    'full_name'       => $employee->full_name,
                    'nik'             => $employee->nik,
                    'employee_status' => $employee->employee_status,
                ] : null,

                'type_config' => $typeCfg,
                'signatures'  => $signatures,
            ]
        ]);
    }

    /**
     * GENERATE CONTRACT NUMBER
     * Format: (TYPE)-xxx/UNITCODE-mm/INITIALS/YYYY
     */
    protected function generateContractNumber(Contract $contract): string
    {
        $contract->loadMissing('unit');

        $type    = $contract->contract_type;
        $cfg     = config('recruitment.numbering', []);
        $docCodes= $cfg['doc_codes'] ?? [];
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
     * HEAD CODE RESOLVER: inisial nama Kepala Unit (dua huruf)
     */
    protected function resolveHeadCodeForUnit(?Unit $unit): string
    {
        $default = config('recruitment.numbering.default_head_code', 'XX');
        if (!$unit) {
            return $default;
        }

        $headRoles = config('recruitment.numbering.head_role_names', ['Kepala Unit']);

        $user = User::query()
            ->where('unit_id', $unit->id)
            ->whereHas('roles', function ($q) use ($headRoles, $unit) {
                $q->whereIn('name', $headRoles)
                    ->where('team_id', $unit->id);
            })
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

        if (!$full) {
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
     * COLLECT META
     */
    protected function collectMeta(array $v, bool $edit = false, array $old = []): array
    {
        $get = fn($key) => $v[$key] ?? ($edit ? ($old[$key] ?? null) : null);

        return [
            'remarks'                        => $get('remarks') ?? $get('note'),
            'position_name'                  => $get('position') ?? $get('position_name'),

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
     * BASIC HELPERS
     */
    protected function formatTanggalIndo(Carbon $d): string
    {
        $b = [
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
        return $d->format('j') . ' ' . $b[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    protected function getHariIndo(Carbon $d): string
    {
        $m = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];
        return $m[$d->format('l')] ?? $d->format('l');
    }

    protected function normalizeMoney($raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }
        return (int) $digits;
    }

    protected function terbilangIndonesia(int $n): string
    {
        $n = abs($n);
        $h = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];
        if ($n < 12) {
            return $h[$n];
        }
        if ($n < 20) {
            return $this->terbilangIndonesia($n - 10) . ' belas';
        }
        if ($n < 100) {
            return $this->terbilangIndonesia((int) ($n / 10)) . ' puluh ' . $this->terbilangIndonesia($n % 10);
        }
        if ($n < 200) {
            return 'seratus ' . $this->terbilangIndonesia($n - 100);
        }
        if ($n < 1000) {
            return $this->terbilangIndonesia((int) ($n / 100)) . ' ratus ' . $this->terbilangIndonesia($n % 100);
        }
        if ($n < 2000) {
            return 'seribu ' . $this->terbilangIndonesia($n - 1000);
        }
        if ($n < 1000000) {
            return $this->terbilangIndonesia((int) ($n / 1000)) . ' ribu ' . $this->terbilangIndonesia($n % 1000);
        }
        if ($n < 1000000000) {
            return $this->terbilangIndonesia((int) ($n / 1000000)) . ' juta ' . $this->terbilangIndonesia($n % 1000000);
        }
        if ($n < 1000000000000) {
            return $this->terbilangIndonesia((int) ($n / 1000000000)) . ' miliar ' . $this->terbilangIndonesia($n % 1000000000);
        }
        return (string) $n;
    }

    protected function storeBase64Image(?string $dataUrl, string $disk, string $dir): array
    {
        if (!$dataUrl) {
            return [null, null];
        }

        $ext = 'png';
        if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
            $ext     = strtolower($m[1]);
            $dataUrl = substr($dataUrl, strpos($dataUrl, ',') + 1);                                    
        }

        $binary = base64_decode($dataUrl);
        if ($binary === false) {
            return [null, null];
        }

        $filename = $dir . '/' . uniqid('img_', true) . '.' . $ext;
        Storage::disk($disk)->put($filename, $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $binary);
        $hash = hash_file('sha256', $tmp);
        @unlink($tmp);

        return [$filename, $hash];
    }
}