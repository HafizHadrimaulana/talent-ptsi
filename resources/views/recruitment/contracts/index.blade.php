@extends('layouts.app')

@section('title', 'Kontrak')

@section('content')
    @php
        $me = auth()->user();
        $meUnit = $me?->unit_id;

        // STATUS
        $rawStatusConfig = config('recruitment.contract_statuses', []);
        $statusOptions = [];
        foreach ($rawStatusConfig as $key => $row) {
            if (is_array($row)) {
                $code = $row['code'] ?? $key;
                $label = $row['label'] ?? $code;
            } else {
                $code = $key;
                $label = $row;
            }
            $statusOptions[$code] = $label;
        }

        // JENIS KONTRAK
        $rawTypeConfig = config('recruitment.contract_types', []);
        $contractTypeOptions = [];
        foreach ($rawTypeConfig as $key => $row) {
            if (is_array($row)) {
                $code = $row['code'] ?? $key;
                $label = $row['label'] ?? $code;
            } else {
                $code = $key;
                $label = $row;
            }
            $contractTypeOptions[$code] = $label;
        }

        $spkCfg = $rawTypeConfig['SPK'] ?? null;
        $pkwtNewCfg = $rawTypeConfig['PKWT_BARU'] ?? null;
        $pkwtExtCfg = $rawTypeConfig['PKWT_PERPANJANGAN'] ?? null;
        $pbCfg = $rawTypeConfig['PB_PENGAKHIRAN'] ?? null;

        // EMPLOYMENT TYPE
        $rawEmploymentConfig = config('recruitment.employment_types', []);
        $employmentTypeOptions = [];
        foreach ($rawEmploymentConfig as $key => $row) {
            if (is_array($row)) {
                $code = $row['code'] ?? $key;
                $label = $row['label'] ?? $code;
            } else {
                $code = $key;
                $label = $row;
            }
            $employmentTypeOptions[$code] = $label;
        }

        // UNIT / FILTER
        $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
        $currentUnitId = isset($currentUnitId) ? $currentUnitId : $meUnit;
        $units = $units ?? collect();
        $selectedUnitId = $selectedUnitId ?? request('unit_id', $currentUnitId);
        $searchFilter = $searchFilter ?? request('q', '');
        $statusFilter = request('status');
    @endphp

    <div class="u-card u-card--glass u-hover-lift">
        {{-- HEADER --}}
        <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
            <div>
                <h2 class="u-title u-mb-xs">Penerbitan &amp; Penandatanganan Kontrak</h2>
                <p class="u-text-sm u-muted">
                    Monitoring draft–submit–review–e-sign kontrak kerja (SPK, PKWT, Perjanjian Bersama).
                    <br>
                    <span class="u-text-xxs">
                        Format nomor:
                        <code>(TYPE)-xxx/UNITCODE-mm/INISIAL-KEPALA-UNIT/YYYY</code>
                    </span>
                </p>
            </div>

            @can('contract.create')
                <button type="button" class="u-btn u-btn--brand u-btn--sm u-hover-lift" data-modal-open="createContractModal">
                    <i class="fas fa-plus u-mr-xs"></i> Draft Kontrak
                </button>
            @endcan
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('ok'))
            @push('swal')
                <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script>
            @endpush
        @endif

    @if($errors->any())
        <div class="u-card u-mb-md u-error">
            <div class="u-flex u-items-center u-gap-sm u-mb-sm">
                <i class="u-error-icon fas fa-exclamation-circle"></i>
                <span class="u-font-semibold">Mohon periksa kembali isian berikut:</span>
            </div>
            <ul class="u-list">
                @foreach($errors->all() as $e)
                    <li class="u-item">{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

        {{-- FILTER BAR --}}
        <div class="u-flex u-items-center u-gap-md u-mb-md u-stack-mobile">
            <form method="get" class="u-flex u-items-center u-gap-md u-stack-mobile u-w-full u-flex-wrap">
                {{-- UNIT --}}
                @if ($canSeeAll)
                    <div class="u-flex-1-min">
                        <label class="u-text-xs u-font-medium u-mb-xs d-block">Unit Kerja</label>
                        <select name="unit_id" class="u-input u-input--sm" onchange="this.form.submit()">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" @selected((string) $selectedUnitId === (string) $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    @php $unitNameFilter = ($units[0]->name ?? 'Unit Saya'); @endphp
                    <div class="u-flex-1-min">
                        <label class="u-text-xs u-font-medium u-mb-xs d-block">Unit Kerja</label>
                        <div class="u-badge u-badge--glass u-text-xs">{{ $unitNameFilter }}</div>
                        <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                    </div>
                @endif

                {{-- STATUS --}}
                <div class="u-flex-1-min">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Status Kontrak</label>
                    <select name="status" class="u-input u-input--sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $code => $label)
                            <option value="{{ $code }}" @selected($statusFilter == $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- SEARCH --}}
                <div class="u-flex-1-min u-w-full u-max-w-xs u-ml-auto">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Cari</label>
                    <input type="search" name="q" id="contractsSearch" class="u-input u-input--sm"
                        placeholder="No. Kontrak / Jenis / Unit / Nama" value="{{ $searchFilter }}">
                </div>
            </form>
        </div>

        {{-- TABEL (DATATABLES) --}}
        <div class="dt-wrapper u-mb-xl">
            <div class="u-flex u-items-center u-justify-between u-mb-sm u-stack-mobile">
                <div class="u-font-semibold">Monitoring Kontrak</div>
                <span class="u-badge u-badge--glass u-text-xs u-mt-xs-sm">
                    {{ $canSeeAll && !$selectedUnitId ? 'Semua Unit' : 'Unit ID: ' . ($selectedUnitId ?? $meUnit) }}
                </span>
            </div>

            <div class="u-scroll-x">
                <table id="contracts-table" class="u-table u-table-mobile" data-datatable="contracts"
                    data-datatable-search="#contractsSearch">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Jenis</th>
                            <th>Unit</th>
                            <th>Periode</th>
                            <th>Status &amp; Flow</th>
                            <th class="cell-actions">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $c)
                            @php
                                if (!is_object($c)) {
                                    continue;
                                }

                                $rawType = $c->contract_type ?? '—';
                                $jenis = $contractTypeOptions[$rawType] ?? $rawType;
                                $unitName = optional($c->unit)->name ?? ($c->unit_name ?? '—');

                                $start = $c->start_date ? Carbon::parse($c->start_date) : null;
                                $end = $c->end_date ? Carbon::parse($c->end_date) : null;

                                $periode = ($start && $end)
                                    ? $start->format('d M Y') . ' s/d ' . $end->format('d M Y')
                                    : '—';

                                $st = $c->status ?? 'draft';

                                $statusBadgeClass =
                                    $st === 'signed' ? 'u-badge--success' :
                                    ($st === 'approved' ? 'u-badge--primary' :
                                        ($st === 'review' ? 'u-badge--warn' :
                                            ($st === 'active' ? 'u-badge--primary' :
                                                ($st === 'ended' ? 'u-badge--muted' : 'u-badge--glass'))));

                                $statusLabelRaw = $statusOptions[$st] ?? null;
                                if (is_array($statusLabelRaw)) {
                                    $statusLabel = $statusLabelRaw['label']
                                        ?? $statusLabelRaw['code']
                                        ?? ucfirst(str_replace('_', ' ', $st));
                                } else {
                                    $statusLabel = $statusLabelRaw
                                        ?? ucfirst(str_replace('_', ' ', $st));
                                }

                                // Flow hint by type + status
                                $flowHint = '';
                                if ($rawType === 'SPK') {
                                    if ($st === 'draft') {
                                        $flowHint = 'Draft SPK oleh SDM Unit.';
                                    } elseif ($st === 'review') {
                                        $flowHint = 'Menunggu review + e-sign Kepala Unit.';
                                    } elseif ($st === 'approved') {
                                        $flowHint = 'Menunggu approval + e-sign kandidat.';
                                    } elseif ($st === 'signed') {
                                        $flowHint = 'SPK disetujui dan ditandatangani kandidat.';
                                    }
                                } elseif (in_array($rawType, ['PKWT_BARU', 'PKWT_PERPANJANGAN'])) {
                                    if ($st === 'draft') {
                                        $flowHint = 'Draft PKWT oleh SDM Unit.';
                                    } elseif ($st === 'review') {
                                        $flowHint = 'Menunggu review + e-sign Kepala Unit.';
                                    } elseif ($st === 'approved') {
                                        $flowHint = 'Menunggu e-sign kandidat.';
                                    } elseif ($st === 'signed') {
                                        $flowHint = 'PKWT ditandatangani kandidat.';
                                    }
                                } elseif ($rawType === 'PB_PENGAKHIRAN') {
                                    if ($st === 'draft') {
                                        $flowHint = 'Draft PB oleh SDM Unit.';
                                    } elseif ($st === 'review') {
                                        $flowHint = 'Menunggu review + e-sign Kepala Unit.';
                                    } elseif ($st === 'approved') {
                                        $flowHint = 'Menunggu e-sign pihak terkait.';
                                    } elseif ($st === 'signed') {
                                        $flowHint = 'Perjanjian Bersama ditandatangani.';
                                    }
                                }

                                // Akses dinamis (tidak hardcode role)
                                $canView = Gate::check('contract.view', $c);
                                $canEdit = Gate::check('contract.update', $c) && $st === 'draft';
                            @endphp

                            @if ($canView)
                                <tr>
                                    <td>
                                        <span class="u-badge u-badge--glass u-text-xs font-mono">
                                            {{ $c->contract_no ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="u-chip u-chip--soft">{{ $jenis }}</span>
                                    </td>
                                    <td>{{ $unitName }}</td>
                                    <td class="u-text-sm">{{ $periode }}</td>
                                    <td>
                                        <span class="u-badge {{ $statusBadgeClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                        @if ($flowHint)
                                            <div class="u-text-xxs u-muted u-mt-xxs">{{ $flowHint }}</div>
                                        @endif
                                    </td>
                                    <td class="cell-actions">
                                        <div class="cell-actions__group">
                                            {{-- DETAIL selalu muncul jika bisa view --}}
                                            <button type="button" class="u-btn u-btn--ghost u-btn--xs" data-contract-detail
                                                data-show-url="{{ route('recruitment.contracts.show', $c) }}">
                                                <i class="fas fa-eye u-mr-xxs"></i> Detail
                                            </button>

                                            {{-- EDIT hanya jika boleh update & status draft (SDM Unit) --}}
                                            @if ($canEdit)
                                                <button type="button" class="u-btn u-btn--primary u-btn--xs" data-contract-edit
                                                    data-show-url="{{ route('recruitment.contracts.show', $c) }}"
                                                    data-update-url="{{ route('recruitment.contracts.update', $c) }}">
                                                    <i class="fas fa-edit u-mr-xxs"></i> Edit
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($contracts instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="u-mt-md">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL: CREATE KONTRAK BARU (CANON LAMA – HANYA DIRAPIKAN SEDIKIT) --}}
    @can('contract.create')
        <div id="createContractModal" class="u-modal" hidden>
            <div class="u-modal__card u-modal__card--xl">
                <div class="u-modal__head">
                    <div class="u-flex u-items-center u-gap-md">
                        <div class="u-avatar u-avatar--lg u-avatar--brand">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div>
                            <div class="u-title">Draft Kontrak Baru</div>
                            <div class="u-muted u-text-sm">
                                SPK: SDM Unit → Kepala Unit (review + e-sign) → Kandidat (approval + e-sign).<br>
                                PKWT: SDM Unit → Kepala Unit (review + e-sign) → Kandidat (e-sign).
                            </div>
                        </div>
                    </div>
                    <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('recruitment.contracts.store') }}"
                    class="u-modal__body u-p-md u-space-y-lg" id="createContractForm">
                    @csrf
                    <input type="hidden" name="mode" id="contractModeInput" value="{{ old('mode') }}">
                    <input type="hidden" name="source_contract_id" id="sourceContractInput"
                        value="{{ old('source_contract_id') }}">
                    <input type="hidden" name="contract_type" id="contractTypeInput" value="{{ old('contract_type') }}">

                    {{-- JENIS KONTRAK --}}
                    <div class="u-space-y-sm">
                        <label class="u-text-sm u-font-medium d-block">Pilih Jenis Kontrak</label>

                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div>
                                <select id="contractFamilySelect" class="u-input" required>
                                    <option value="">Pilih jenis kontrak...</option>

                                    {{-- SPK --}}
                                    @if ($spkCfg)
                                        <option value="SPK" data-mode="new"
                                            data-hint="{{ $spkCfg['description'] ?? 'Surat Penawaran Kerja (SPK) untuk kandidat baru.' }}"
                                            @selected(old('contract_type') === 'SPK')>
                                            {{ $spkCfg['label'] ?? 'Surat Penawaran Kerja (SPK)' }}
                                        </option>
                                    @else
                                        <option value="SPK" data-mode="new"
                                            data-hint="Surat Penawaran Kerja (SPK) untuk kandidat baru."
                                            @selected(old('contract_type') === 'SPK')>
                                            Surat Penawaran Kerja (SPK)
                                        </option>
                                    @endif

                                    {{-- PKWT FAMILY --}}
                                    <option value="PKWT" data-mode="" data-hint="Pilih detail PKWT: baru atau perpanjangan."
                                        @selected(in_array(old('contract_type'), ['PKWT_BARU', 'PKWT_PERPANJANGAN']))>
                                        PKWT (Baru / Perpanjangan)
                                    </option>

                                    {{-- PB --}}
                                    @if ($pbCfg)
                                        <option value="PB" data-mode="terminate"
                                            data-hint="{{ $pbCfg['description'] ?? 'Perjanjian Bersama pengakhiran PKWT.' }}"
                                            @selected(old('contract_type') === 'PB_PENGAKHIRAN')>
                                            {{ $pbCfg['label'] ?? 'Perjanjian Bersama (PB)' }}
                                        </option>
                                    @else
                                        <option value="PB" data-mode="terminate" data-hint="Perjanjian Bersama pengakhiran PKWT."
                                            @selected(old('contract_type') === 'PB_PENGAKHIRAN')>
                                            Perjanjian Bersama (PB)
                                        </option>
                                    @endif
                                </select>
                                <p id="contractFamilyHint" class="u-text-xs u-muted u-mt-xs"></p>
                            </div>

                            <div class="u-hidden-mobile">
                                <div class="u-text-xs u-muted">
                                    <span class="u-font-semibold">Catatan:</span><br>
                                    • SPK: setelah submit, Kepala Unit review + e-sign, lalu kandidat approve + e-sign.<br>
                                    • PKWT: setelah submit, Kepala Unit review + e-sign, lalu kandidat e-sign.<br>
                                    • PB: digunakan untuk pengakhiran PKWT dengan Perjanjian Bersama.
                                </div>
                            </div>
                        </div>

                        {{-- SUBTYPE PKWT --}}
                        <div id="contractSubtypeWrapper" class="u-mt-sm" hidden>
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                Detail PKWT
                            </label>
                            <select id="contractSubtypeSelect" class="u-input">
                                <option value="">Pilih detail PKWT...</option>
                                @if ($pkwtNewCfg)
                                    <option value="PKWT_BARU" data-mode="new"
                                        data-hint="{{ $pkwtNewCfg['description'] ?? 'PKWT pertama kali (dari pelamar).' }}"
                                        @selected(old('contract_type') === 'PKWT_BARU')>
                                        {{ $pkwtNewCfg['label'] ?? 'PKWT Baru' }}
                                    </option>
                                @endif
                                @if ($pkwtExtCfg)
                                    <option value="PKWT_PERPANJANGAN" data-mode="extend"
                                        data-hint="{{ $pkwtExtCfg['description'] ?? 'Perpanjangan PKWT dari kontrak aktif yang akan berakhir.' }}"
                                        @selected(old('contract_type') === 'PKWT_PERPANJANGAN')>
                                        {{ $pkwtExtCfg['label'] ?? 'PKWT Perpanjangan' }}
                                    </option>
                                @endif
                            </select>
                            <p id="contractSubtypeHint" class="u-text-xs u-muted u-mt-xxs"></p>
                        </div>
                    </div>

                    {{-- SECTIONS BY MODE (NEW / EXTEND / TERMINATE) --}}
                    <div id="contractFormSections" class="u-space-y-lg" hidden>
                        {{-- UNIT FIELD --}}
                        <div id="unitFieldWrapper" class="u-mt-md" hidden>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>
                            @if ($canSeeAll)
                                <select name="unit_id" id="contractUnitSelect" class="u-input">
                                    <option value="">Pilih Unit</option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u->id }}" @selected(old('unit_id', $selectedUnitId ?? $meUnit) == $u->id)>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                @php $unitNameCreate = ($units[0]->name ?? 'Unit Saya'); @endphp
                                <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                                <div class="u-badge u-badge--glass">{{ $unitNameCreate }}</div>
                            @endif
                        </div>

                        {{-- MODE: NEW (SPK & PKWT BARU) --}}
                        <div data-mode-section="new" hidden>
                            <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar / Kandidat</label>
                                    <select name="applicant_id" id="applicantSelect" class="u-input">
                                        <option value="">Pilih Pelamar</option>
                                        @foreach ($applicants as $a)
                                            @php
                                                $appUnitName = $a->unit_name ?? 'Unit ?';
                                                $appPosition = $a->position_applied ?? ($a->position_name ?? 'Posisi belum diisi');
                                            @endphp
                                            <option value="{{ $a->id }}" data-full-name="{{ $a->full_name }}"
                                                data-position="{{ $appPosition }}" data-unit="{{ $appUnitName }}"
                                                @selected(old('applicant_id') == $a->id)>
                                                {{ $a->full_name }} — {{ $appPosition }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="u-text-xs u-muted u-mt-xxs">
                                        Data kandidat dari Monitoring Rekrutmen (status APPROVED/HIRED).
                                    </p>

                                    <div id="applicantPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                        <div class="u-text-xs u-muted">
                                            <span class="u-font-semibold">Ringkasan Kandidat:</span>
                                            <div class="u-mt-xxs">
                                                <span id="applicantPreviewName">-</span>
                                                <span class="u-muted"> • </span>
                                                <span id="applicantPreviewPosition">-</span>
                                                <span class="u-muted"> • </span>
                                                <span id="applicantPreviewUnit">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Jenis Hubungan Kerja</label>
                                    <select name="employment_type" id="employmentTypeSelect" class="u-input">
                                        <option value="">Pilih Jenis Hubungan Kerja</option>
                                        @foreach ($employmentTypeOptions as $code => $label)
                                            <option value="{{ $code }}" @selected(old('employment_type') == $code)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">Nama Jabatan</label>
                                <input type="text" name="position_name" class="u-input"
                                    placeholder="Mis. Staff HCIS, Analis TKDN, dsb." value="{{ old('position_name') }}">
                            </div>

                            <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai Kontrak</label>
                                    <input type="date" name="start_date" class="u-input" value="{{ old('start_date') }}">
                                </div>
                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Berakhir Kontrak</label>
                                    <input type="date" name="end_date" class="u-input" value="{{ old('end_date') }}">
                                </div>
                            </div>

                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                    Catatan Tambahan <span class="u-muted">(opsional)</span>
                                </label>
                                <textarea name="remarks" class="u-input" rows="3"
                                    placeholder="Catatan khusus benefit / lokasi / klausul lain">{{ old('remarks') }}</textarea>
                            </div>
                        </div>

                        {{-- MODE: EXTEND (PKWT PERPANJANGAN) --}}
                        <div data-mode-section="extend" hidden>
                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                    Kontrak Dasar untuk Perpanjangan
                                </label>
                                <select id="sourceContractSelect" class="u-input">
                                    <option value="">Pilih kontrak aktif yang akan berakhir</option>
                                    @foreach ($expiringContracts as $c)
                                        @php
                                            $startRaw = $c->start_date ?? null;
                                            $endRaw = $c->end_date ?? null;
                                            $startTxt = $startRaw ? Carbon::parse($startRaw)->format('d M Y') : '-';
                                            $endTxt = $endRaw ? Carbon::parse($endRaw)->format('d M Y') : '-';
                                            $unit = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
                                            $person = $c->person_name ?? '-';
                                            $pos = $c->position_name ?? '-';
                                        @endphp
                                        <option value="{{ $c->id }}" data-contract-no="{{ $c->contract_no ?? $c->id }}"
                                            data-person="{{ $person }}" data-position="{{ $pos }}" data-unit="{{ $unit }}"
                                            data-start="{{ $startTxt }}" data-end="{{ $endTxt }}"
                                            data-unit-id="{{ $c->unit_id ?? '' }}" @selected(old('source_contract_id') == $c->id)>
                                            {{ $person }} — {{ $pos }} • {{ $unit }} ({{ $startTxt }} s/d {{ $endTxt }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="u-text-xs u-muted u-mt-xxs">
                                    Diambil dari posisi PKWT (kontrak aktif) yang akan berakhir ≤ 30 hari ke depan.
                                </p>

                                <div id="sourceContractPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                    <div class="u-text-xs u-muted">
                                        <span class="u-font-semibold">Ringkasan Kontrak Dasar:</span>
                                        <div class="u-mt-xxs u-space-y-xxs">
                                            <div>
                                                <span class="u-muted">No. Kontrak:</span>
                                                <span id="sourcePreviewNo">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Pekerja:</span>
                                                <span id="sourcePreviewPerson">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Posisi:</span>
                                                <span id="sourcePreviewPosition">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Unit:</span>
                                                <span id="sourcePreviewUnit">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Periode:</span>
                                                <span id="sourcePreviewPeriod">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai Kontrak Baru</label>
                                    <input type="date" name="start_date" class="u-input" value="{{ old('start_date') }}">
                                </div>
                                <div>
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Berakhir Kontrak Baru</label>
                                    <input type="date" name="end_date" class="u-input" value="{{ old('end_date') }}">
                                </div>
                            </div>

                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                    Catatan / Hasil Evaluasi <span class="u-muted">(opsional)</span>
                                </label>
                                <textarea name="remarks" class="u-input" rows="3"
                                    placeholder="Ringkasan hasil evaluasi kinerja, etika, dan kesepakatan perpanjangan">{{ old('remarks') }}</textarea>
                            </div>
                        </div>

                        {{-- MODE: TERMINATE (PB PENGAKHIRAN) --}}
                        <div data-mode-section="terminate" hidden>
                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                    Kontrak PKWT yang Diakhiri
                                </label>
                                <select id="sourceContractSelectTerminate" class="u-input">
                                    <option value="">Pilih kontrak PKWT yang diakhiri</option>
                                    @foreach ($expiringContracts as $c)
                                        @php
                                            $startRaw = $c->start_date ?? null;
                                            $endRaw = $c->end_date ?? null;
                                            $startTxt = $startRaw ? Carbon::parse($startRaw)->format('d M Y') : '-';
                                            $endTxt = $endRaw ? Carbon::parse($endRaw)->format('d M Y') : '-';
                                            $unit = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
                                            $person = $c->person_name ?? '-';
                                            $pos = $c->position_name ?? '-';
                                        @endphp
                                        <option value="{{ $c->id }}" data-contract-no="{{ $c->contract_no ?? $c->id }}"
                                            data-person="{{ $person }}" data-position="{{ $pos }}" data-unit="{{ $unit }}"
                                            data-start="{{ $startTxt }}" data-end="{{ $endTxt }}"
                                            data-unit-id="{{ $c->unit_id ?? '' }}" @selected(old('source_contract_id') == $c->id)>
                                            {{ $person }} — {{ $pos }} • {{ $unit }} ({{ $startTxt }} s/d {{ $endTxt }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="u-text-xs u-muted u-mt-xxs">
                                    Pilih kontrak PKWT yang menjadi dasar Perjanjian Bersama (PB) pengakhiran.
                                </p>

                                <div id="sourceContractPreviewTerminate" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                    <div class="u-text-xs u-muted">
                                        <span class="u-font-semibold">Ringkasan Kontrak Dasar:</span>
                                        <div class="u-mt-xxs u-space-y-xxs">
                                            <div>
                                                <span class="u-muted">No. Kontrak:</span>
                                                <span id="sourcePreviewNoTerminate">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Pekerja:</span>
                                                <span id="sourcePreviewPersonTerminate">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Posisi:</span>
                                                <span id="sourcePreviewPositionTerminate">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Unit:</span>
                                                <span id="sourcePreviewUnitTerminate">-</span>
                                            </div>
                                            <div>
                                                <span class="u-muted">Periode:</span>
                                                <span id="sourcePreviewPeriodTerminate">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="u-mt-lg">
                                <div class="u-flex u-items-center u-justify-between u-mb-xs">
                                    <label class="u-text-sm u-font-semibold">Detail Perjanjian Bersama (PB)</label>
                                    <span class="u-text-xs u-muted">
                                        Tanggal efektif pengakhiran &amp; kompensasi (jika ada).
                                    </span>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                                    <div>
                                        <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                            Tanggal Efektif Pengakhiran
                                        </label>
                                        <input type="date" name="pb_effective_end" class="u-input"
                                            value="{{ old('pb_effective_end') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                            Kompensasi PB (Rp)
                                        </label>
                                        <input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true"
                                            data-terbilang-target="pb_compensation_amount_words"
                                            placeholder="Mis. Rp. 10.000.000" value="{{ old('pb_compensation_amount') }}">
                                    </div>
                                </div>

                                <div class="u-mt-md">
                                    <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                        Kompensasi PB (Terbilang)
                                    </label>
                                    <input type="text" name="pb_compensation_amount_words" class="u-input" readonly
                                        value="{{ old('pb_compensation_amount_words') }}">
                                </div>

                                <div class="u-mt-md">
                                    <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                        Catatan / Kesepakatan Lain <span class="u-muted">(opsional)</span>
                                    </label>
                                    <textarea name="remarks" class="u-input" rows="3"
                                        placeholder="Ringkasan kesepakatan PB, klausul khusus, dsb.">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- REMUNERATION --}}
                    <div id="remunerationSection" class="u-mt-lg" data-mode-visible="new,extend" hidden>
                        <div class="u-flex u-items-center u-justify-between u-mb-xs">
                            <label class="u-text-sm u-font-semibold">Rincian Upah &amp; Fasilitas</label>
                            <span class="u-text-xs u-muted">
                                Ketik angka, sistem memformat menjadi Rp. ... dan mengisi terbilang otomatis.
                            </span>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Rp)</label>
                                <input type="text" name="salary_amount" class="u-input" data-rupiah="true"
                                    data-terbilang-target="salary_amount_words" placeholder="Mis. Rp. 7.500.000"
                                    value="{{ old('salary_amount') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Terbilang)</label>
                                <input type="text" name="salary_amount_words" class="u-input" readonly
                                    value="{{ old('salary_amount_words') }}">
                            </div>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan per Hari (Rp)</label>
                                <input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true"
                                    data-terbilang-target="lunch_allowance_words" placeholder="Mis. Rp. 40.000"
                                    value="{{ old('lunch_allowance_daily') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan (Terbilang)</label>
                                <input type="text" name="lunch_allowance_words" class="u-input" readonly
                                    value="{{ old('lunch_allowance_words') }}">
                            </div>
                        </div>

                        <details class="u-mt-md">
                            <summary class="u-text-xs u-font-medium u-cursor-pointer">
                                Tunjangan Lain (opsional)
                            </summary>
                            <div class="u-mt-sm u-space-y-sm">
                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Khusus (Rp)
                                        </label>
                                        <input type="text" name="allowance_special_amount" class="u-input" data-rupiah="true"
                                            data-terbilang-target="allowance_special_words"
                                            value="{{ old('allowance_special_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Khusus (Terbilang)
                                        </label>
                                        <input type="text" name="allowance_special_words" class="u-input" readonly
                                            value="{{ old('allowance_special_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Jabatan (Rp)
                                        </label>
                                        <input type="text" name="allowance_position_amount" class="u-input" data-rupiah="true"
                                            data-terbilang-target="allowance_position_words"
                                            value="{{ old('allowance_position_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Jabatan (Terbilang)
                                        </label>
                                        <input type="text" name="allowance_position_words" class="u-input" readonly
                                            value="{{ old('allowance_position_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Komunikasi (Rp)
                                        </label>
                                        <input type="text" name="allowance_communication_amount" class="u-input"
                                            data-rupiah="true" data-terbilang-target="allowance_communication_words"
                                            value="{{ old('allowance_communication_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Komunikasi (Terbilang)
                                        </label>
                                        <input type="text" name="allowance_communication_words" class="u-input" readonly
                                            value="{{ old('allowance_communication_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Lain (Rp)
                                        </label>
                                        <input type="text" name="allowance_other_amount" class="u-input" data-rupiah="true"
                                            data-terbilang-target="allowance_other_words"
                                            value="{{ old('allowance_other_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                            Tunjangan Lain (Terbilang)
                                        </label>
                                        <input type="text" name="allowance_other_words" class="u-input" readonly
                                            value="{{ old('allowance_other_words') }}">
                                    </div>
                                </div>

                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                        Keterangan Tunjangan Lain
                                    </label>
                                    <textarea name="allowance_other_desc" class="u-input" rows="2"
                                        placeholder="Mis. tunjangan shift, tunjangan remote area, dll.">{{ old('allowance_other_desc') }}</textarea>
                                </div>
                            </div>
                        </details>

                        <div class="u-mt-md">
                            <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                Fasilitas / Benefit Lain <span class="u-muted">(opsional)</span>
                            </label>
                            <textarea name="other_benefits_desc" class="u-input" rows="2"
                                placeholder="Mis. BPJS Kesehatan &amp; Ketenagakerjaan, tunjangan transport, dll.">{{ old('other_benefits_desc') }}</textarea>
                        </div>

                        {{-- OPSI VERIFIKASI / E-SIGN --}}
                        <div class="u-mt-md">
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                Opsi Verifikasi &amp; e-Sign
                            </label>
                            <div class="u-flex u-items-center u-gap-md u-flex-wrap">
                                <label class="u-text-xs">
                                    <input type="checkbox" name="requires_camera" value="1" class="u-mr-xs"
                                        @checked(old('requires_camera', 1))>
                                    Kamera (foto saat tanda tangan)
                                </label>
                                <label class="u-text-xs">
                                    <input type="checkbox" name="requires_geolocation" value="1" class="u-mr-xs"
                                        @checked(old('requires_geolocation', 1))>
                                    Geo-location
                                </label>
                                <label class="u-text-xs">
                                    <input type="checkbox" name="requires_draw_signature" value="1" class="u-mr-xs"
                                        @checked(old('requires_draw_signature', 1))>
                                    Tanda tangan digital (drawing)
                                </label>
                            </div>
                        </div>
                    </div>

                <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--soft">Simpan Draft</button>
                    <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand">Submit</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- MODAL: DETAIL KONTRAK (RAPI, CARD SEJENIS DENGAN MODAL TAMBAH) --}}
    <div class="u-modal" id="contractDetailModal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head">
                <div class="u-flex u-items-center u-gap-md">
                    <div class="u-avatar u-avatar--lg u-avatar--brand">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <div class="u-title">Detail Kontrak</div>
                        <div class="u-text-xs u-muted" id="detailFlowHint">
                            {{-- diisi JS dari data.flow_hint --}}
                        </div>
                    </div>
                </div>
                <button type="button" class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="u-modal__body u-p-md u-space-y-md">
                {{-- META UTAMA --}}
                <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div class="u-card u-card--border u-p-sm u-space-y-xs">
                        <div class="u-text-xs u-muted">Nomor Kontrak</div>
                        <div class="u-text-sm u-font-semibold" id="detailContractNo">—</div>
                        <div class="u-text-xs u-muted">Jenis Kontrak</div>
                        <div class="u-text-sm">
                            <span class="u-badge u-badge--glass" id="detailContractType">—</span>
                        </div>
                    </div>
                    <div class="u-card u-card--border u-p-sm u-space-y-xs">
                        <div class="u-text-xs u-muted">Status</div>
                        <div class="u-chip u-chip--soft" id="detailContractStatus">—</div>
                        <div class="u-text-xs u-muted u-mt-xs">Unit Kerja</div>
                        <div class="u-text-sm" id="detailContractUnit">—</div>
                    </div>
                </div>

                <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div class="u-card u-card--border u-p-sm u-space-y-xs">
                        <div class="u-text-xs u-muted">Periode Kontrak</div>
                        <div class="u-text-sm" id="detailContractPeriod">—</div>
                        <div class="u-text-xs u-muted u-mt-xs">Nama Jabatan</div>
                        <div class="u-text-sm" id="detailContractPosition">—</div>
                    </div>
                    <div class="u-card u-card--border u-p-sm u-space-y-xs">
                        <div class="u-text-xs u-muted">Nama Pegawai / Kandidat</div>
                        <div class="u-text-sm" id="detailContractPerson">—</div>
                        <div class="u-text-xs u-muted u-mt-xs">Catatan</div>
                        <div class="u-text-sm" id="detailContractRemarks">—</div>
                    </div>
                </div>

                {{-- RINCIAN UPAH & FASILITAS (DIISI DARI remuneration_json) --}}
                <div class="u-card u-card--glass u-p-sm u-space-y-sm">
                    <div class="u-flex u-items-center u-justify-between">
                        <span class="u-text-xs u-font-semibold">Upah &amp; Fasilitas</span>
                        <span class="u-text-xxs u-muted">
                            Nilai diambil dari draft yang disetujui (salary, uang makan, tunjangan).
                        </span>
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-xs">
                        <div class="u-space-y-xxs">
                            <div class="u-text-xxs u-muted">Gaji Pokok</div>
                            <div class="u-text-sm" id="detailSalaryAmount">-</div>
                            <div class="u-text-xxs u-muted" id="detailSalaryAmountWords">-</div>
                        </div>
                        <div class="u-space-y-xxs">
                            <div class="u-text-xxs u-muted">Uang Makan / Hari</div>
                            <div class="u-text-sm" id="detailLunchDaily">-</div>
                            <div class="u-text-xxs u-muted" id="detailLunchDailyWords">-</div>
                        </div>
                    </div>

                    <details class="u-mt-xs">
                        <summary class="u-text-xxs u-font-medium u-cursor-pointer">
                            Tunjangan Lain &amp; Benefit
                        </summary>
                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-xs">
                            <div class="u-space-y-xxs">
                                <div class="u-text-xxs u-muted">Tunjangan Khusus</div>
                                <div class="u-text-sm" id="detailAllowanceSpecial">-</div>
                                <div class="u-text-xxs u-muted" id="detailAllowanceSpecialWords">-</div>
                            </div>
                            <div class="u-space-y-xxs">
                                <div class="u-text-xxs u-muted">Tunjangan Jabatan</div>
                                <div class="u-text-sm" id="detailAllowancePosition">-</div>
                                <div class="u-text-xxs u-muted" id="detailAllowancePositionWords">-</div>
                            </div>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-xs">
                            <div class="u-space-y-xxs">
                                <div class="u-text-xxs u-muted">Tunjangan Komunikasi</div>
                                <div class="u-text-sm" id="detailAllowanceCommunication">-</div>
                                <div class="u-text-xxs u-muted" id="detailAllowanceCommunicationWords">-</div>
                            </div>
                            <div class="u-space-y-xxs">
                                <div class="u-text-xxs u-muted">Tunjangan Lain</div>
                                <div class="u-text-sm" id="detailAllowanceOther">-</div>
                                <div class="u-text-xxs u-muted" id="detailAllowanceOtherWords">-</div>
                            </div>
                        </div>

                        <div class="u-mt-xs u-space-y-xxs">
                            <div class="u-text-xxs u-muted">Keterangan Tunjangan Lain</div>
                            <div class="u-text-xs" id="detailAllowanceOtherDesc">-</div>
                        </div>

                        <div class="u-mt-xs u-space-y-xxs">
                            <div class="u-text-xxs u-muted">Fasilitas / Benefit Lain</div>
                            <div class="u-text-xs" id="detailOtherBenefitsDesc">-</div>
                        </div>
                    </details>
                </div>

                {{-- APPROVAL STATUS --}}
                <div class="u-card u-card--border u-p-sm u-mt-sm" id="approvalInfo" hidden>
                    <div class="u-text-xs u-font-semibold u-mb-xxs">Status Approval</div>
                    <div class="u-text-xs" id="approvalStatusText">-</div>
                    <div class="u-text-xxs u-muted u-mt-xxs" id="approvalNote">-</div>
                </div>
            </div>

            <div class="u-modal__foot u-flex u-items-center u-justify-between u-gap-md">
                <div class="u-text-xxs u-muted">
                    Aksi hanya muncul bagi pengguna yang memiliki hak akses pada tahap ini
                    (SDM Unit, Kepala Unit, DHC, atau Dir SDM).
                </div>
                <div class="u-flex u-gap-xs">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>
                        Tutup
                    </button>

                    {{-- Tombol Reject (untuk approver di tahap review) --}}
                    <button type="button" class="u-btn u-btn--danger u-btn--sm" id="detailRejectBtn" hidden>
                        <i class="fas fa-times u-mr-xxs"></i> Reject
                    </button>

                    {{-- Tombol Approve + e-Sign (Kepala Unit) --}}
                    <button type="button" class="u-btn u-btn--brand u-btn--sm u-hover-lift" id="detailApproveBtn" hidden>
                        <i class="fas fa-check u-mr-xxs"></i> Approve &amp; e-Sign
                    </button>

                    {{-- Tombol e-Sign untuk kandidat / pegawai --}}
                    <button type="button" class="u-btn u-btn--primary u-btn--sm u-hover-lift" id="detailSignBtn" hidden>
                        <i class="fas fa-pen-nib u-mr-xxs"></i> e-Sign Kontrak
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: REJECT --}}
    <div class="u-modal" id="rejectContractModal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--md">
            <div class="u-modal__head">
                <div class="u-title u-text-base">Reject Kontrak</div>
                <button type="button" class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="rejectContractForm" class="u-modal__body u-space-y-md">
                @csrf
                <p class="u-text-xs u-muted">
                    Kontrak akan dikembalikan ke SDM Unit dengan status <strong>DRAFT</strong>.
                    Berikan catatan alasan reject (opsional).
                </p>

                <div class="u-space-y-sm">
                    <label class="u-text-xs u-font-medium d-block">Catatan Reject</label>
                    <textarea name="note" class="u-input" rows="3"
                        placeholder="Alasan reject / revisi yang diperlukan..."></textarea>
                </div>

                <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--danger u-hover-lift">
                        <i class="fas fa-times u-mr-xxs"></i> Reject ke Draft
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: E-SIGN (APPROVE &/OR KANDIDAT) --}}
    <div class="u-modal" id="signContractModal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--md">
            <div class="u-modal__head">
                <div class="u-title u-text-base">
                    <span id="signModalTitle">Tanda Tangan Digital</span>
                </div>
                <button type="button" class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="signContractForm" class="u-modal__body u-space-y-md">
                @csrf
                <input type="hidden" name="signature_role" id="signRoleInput" value="HEAD">
                <input type="hidden" name="signature_image" id="signImageInput">
                <input type="hidden" name="geo_lat" id="signGeoLat">
                <input type="hidden" name="geo_lng" id="signGeoLng">
                <input type="hidden" name="geo_accuracy" id="signGeoAcc">
                <input type="hidden" name="camera_image" id="signCamInput">

                <p class="u-text-xs u-muted" id="signModalDescription">
                    Silakan tanda tangan pada kotak di bawah ini. Sistem dapat menyimpan
                    lokasi (geo-location) dan foto (kamera) sesuai konfigurasi kontrak.
                </p>

                {{-- Catatan (opsional) --}}
                <div class="u-space-y-sm" id="signNoteSection">
                    <label class="u-text-xs u-font-medium d-block">Catatan (opsional)</label>
                    <textarea name="note" class="u-input" rows="2"
                        placeholder="Catatan approval / persetujuan..."></textarea>
                </div>

                {{-- CANVAS SIGNATURE --}}
                <div class="u-card u-card--glass u-p-sm u-flex u-flex-col u-gap-sm">
                    <canvas id="signCanvas"
                        style="width:100%;height:180px;border:1px dashed rgba(0,0,0,0.15);border-radius:10px;background:#fff;"></canvas>
                    <div class="u-flex u-justify-between u-items-center u-text-xxs u-muted">
                        <button type="button" class="u-btn u-btn--ghost u-btn--xs" id="signClearBtn">
                            Bersihkan
                        </button>
                        <span id="signStatusHint">Belum ada coretan.</span>
                    </div>
                </div>

                {{-- REQUIREMENTS (KAMERA / GEO / DRAW) --}}
                <div class="u-card u-card--soft u-p-xs u-mt-sm" id="verificationRequirements" hidden>
                    <div class="u-text-xxs u-font-semibold">Verifikasi yang diperlukan:</div>
                    <div class="u-text-xxs" id="reqCamera">• Kamera: Tidak diperlukan</div>
                    <div class="u-text-xxs" id="reqGeo">• Geo-location: Tidak diperlukan</div>
                    <div class="u-text-xxs" id="reqDraw">• Tanda tangan digital: Tidak diperlukan</div>
                </div>

                <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--brand u-hover-lift" id="signSubmitBtn">
                        <i class="fas fa-pen-nib u-mr-xxs"></i> Simpan Tanda Tangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT KONTRAK (RAPI, SAMA STYLE-NYA DENGAN CREATE) --}}
    <div id="editContractModal" class="u-modal" hidden>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head">
                <div class="u-flex u-items-center u-gap-md">
                    <div class="u-avatar u-avatar--lg u-avatar--brand">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <div class="u-title">Edit Draft Kontrak</div>
                        <div class="u-muted u-text-sm">
                            Perbarui informasi draft kontrak, lalu submit ke Kepala Unit untuk review dan e-sign.
                        </div>
                    </div>
                </div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="#" class="u-modal__body u-p-md u-space-y-lg" id="editContractForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="contract_id" id="editContractId">

                <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Jenis Kontrak</label>
                        <select name="contract_type" id="editContractTypeSelect" class="u-input">
                            @foreach ($contractTypeOptions as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>
                        @if ($canSeeAll)
                            <select name="unit_id" id="editContractUnitSelect" class="u-input">
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        @else
                            @php $unitNameEdit = ($units[0]->name ?? 'Unit Saya'); @endphp
                            <input type="hidden" name="unit_id" id="editContractUnitHidden" value="{{ $meUnit }}">
                            <div class="u-badge u-badge--glass">{{ $unitNameEdit }}</div>
                        @endif
                    </div>
                </div>

                <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                    <div id="editApplicantWrapper">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar / Kandidat</label>
                        <select name="applicant_id" id="editApplicantSelect" class="u-input">
                            <option value="">Tidak ada (manual / existing)</option>
                            @foreach ($applicants as $a)
                                @php
                                    $appUnitName = $a->unit_name ?? 'Unit ?';
                                    $appPosition = $a->position_applied ?? ($a->position_name ?? 'Posisi belum diisi');
                                @endphp
                                <option value="{{ $a->id }}">
                                    {{ $a->full_name }} — {{ $appPosition }} ({{ $appUnitName }})
                                </option>
                            @endforeach
                        </select>
                        <p class="u-text-xs u-muted u-mt-xxs">
                            Untuk SPK &amp; PKWT Baru, pilih dari daftar pelamar (APPROVED/HIRED).
                        </p>
                    </div>

                    <div id="editExistingWrapper" hidden>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Karyawan Existing</label>
                        <select name="source_contract_id" id="editSourceContractSelect" class="u-input">
                            <option value="">Pilih karyawan / kontrak existing</option>
                            @foreach ($expiringContracts as $c)
                                @php
                                    $startRaw = $c->start_date ?? null;
                                    $endRaw = $c->end_date ?? null;
                                    $startTxt = $startRaw ? Carbon::parse($startRaw)->format('d M Y') : '-';
                                    $endTxt = $endRaw ? Carbon::parse($endRaw)->format('d M Y') : '-';
                                    $unit = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
                                    $person = $c->person_name ?? '-';
                                    $pos = $c->position_name ?? '-';
                                @endphp
                                <option value="{{ $c->id }}">
                                    {{ $person }} — {{ $pos }} • {{ $unit }} ({{ $startTxt }} s/d {{ $endTxt }})
                                </option>
                            @endforeach
                        </select>
                        <p class="u-text-xs u-muted u-mt-xxs">
                            Untuk PKWT Perpanjangan &amp; PB Pengakhiran, pilih karyawan dari kontrak PKWT existing.
                        </p>
                    </div>

                    <div>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Jenis Hubungan Kerja</label>
                        <select name="employment_type" id="editEmploymentTypeSelect" class="u-input">
                            <option value="">Pilih Jenis Hubungan Kerja</option>
                            @foreach ($employmentTypeOptions as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="u-mt-md">
                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Nama Jabatan</label>
                    <input type="text" name="position_name" id="editPositionInput" class="u-input"
                        placeholder="Mis. Staff HCIS, Analis TKDN, dsb.">
                </div>

                <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                    <div>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai Kontrak</label>
                        <input type="date" name="start_date" id="editStartDateInput" class="u-input">
                    </div>
                    <div>
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Berakhir Kontrak</label>
                        <input type="date" name="end_date" id="editEndDateInput" class="u-input">
                    </div>
                </div>

                <div class="u-mt-md">
                    <label class="u-text-sm u-font-medium u-mb-sm d-block">
                        Catatan <span class="u-muted">(opsional)</span>
                    </label>
                    <textarea name="remarks" id="editNoteInput" class="u-input" rows="3"
                        placeholder="Catatan khusus / hasil evaluasi / kesepakatan"></textarea>
                </div>

                {{-- REMUNERATION EDIT --}}
                <div class="u-mt-lg">
                    <div class="u-flex u-items-center u-justify-between u-mb-xs">
                        <label class="u-text-sm u-font-semibold">Rincian Upah &amp; Fasilitas</label>
                        <span class="u-text-xs u-muted">
                            Ketik angka, sistem memformat menjadi Rp. ... dan mengisi terbilang otomatis.
                        </span>
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                        <div>
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Rp)</label>
                            <input type="text" name="salary_amount" id="editSalaryAmountInput" class="u-input"
                                data-rupiah="true" data-terbilang-target="edit_salary_amount_words"
                                placeholder="Mis. Rp. 7.500.000">
                        </div>
                        <div>
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Terbilang)</label>
                            <input type="text" name="salary_amount_words" id="edit_salary_amount_words" class="u-input"
                                readonly>
                        </div>
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan per Hari (Rp)</label>
                            <input type="text" name="lunch_allowance_daily" id="editLunchDailyInput" class="u-input"
                                data-rupiah="true" data-terbilang-target="edit_lunch_allowance_words"
                                placeholder="Mis. Rp. 40.000">
                        </div>
                        <div>
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan (Terbilang)</label>
                            <input type="text" name="lunch_allowance_words" id="edit_lunch_allowance_words" class="u-input"
                                readonly>
                        </div>
                    </div>

                <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--soft">Simpan Draft</button>
                    <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand">Submit</button>
                </div>
            </form>
        </div>
    </div>

                            <div class="u-grid-2 u-stack-mobile u-gap-md">
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Jabatan (Rp)</label>
                                    <input type="text" name="allowance_position_amount" id="editAllowancePositionInput"
                                        class="u-input" data-rupiah="true"
                                        data-terbilang-target="edit_allowance_position_words">
                                </div>
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Jabatan
                                        (Terbilang)</label>
                                    <input type="text" name="allowance_position_words" id="edit_allowance_position_words"
                                        class="u-input" readonly>
                                </div>
                            </div>

                            <div class="u-grid-2 u-stack-mobile u-gap-md">
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                        Tunjangan Komunikasi (Rp)
                                    </label>
                                    <input type="text" name="allowance_communication_amount" id="editAllowanceCommInput"
                                        class="u-input" data-rupiah="true"
                                        data-terbilang-target="edit_allowance_communication_words">
                                </div>
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                        Tunjangan Komunikasi (Terbilang)
                                    </label>
                                    <input type="text" name="allowance_communication_words"
                                        id="edit_allowance_communication_words" class="u-input" readonly>
                                </div>
                            </div>

                            <div class="u-grid-2 u-stack-mobile u-gap-md">
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Lain (Rp)</label>
                                    <input type="text" name="allowance_other_amount" id="editAllowanceOtherInput"
                                        class="u-input" data-rupiah="true"
                                        data-terbilang-target="edit_allowance_other_words">
                                </div>
                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                        Tunjangan Lain (Terbilang)
                                    </label>
                                    <input type="text" name="allowance_other_words" id="edit_allowance_other_words"
                                        class="u-input" readonly>
                                </div>
                            </div>

                            <div>
                                <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                    Keterangan Tunjangan Lain
                                </label>
                                <textarea name="allowance_other_desc" id="editAllowanceOtherDesc" class="u-input" rows="2"
                                    placeholder="Mis. tunjangan shift, tunjangan remote area, dll."></textarea>
                            </div>
                        </div>
                    </details>

                    <div class="u-mt-md">
                        <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                            Fasilitas / Benefit Lain <span class="u-muted">(opsional)</span>
                        </label>
                        <textarea name="other_benefits_desc" id="editOtherBenefitsDesc" class="u-input" rows="2"
                            placeholder="Mis. BPJS Kesehatan &amp; Ketenagakerjaan, tunjangan transport, dll."></textarea>
                    </div>
                    <div class="u-mt-md">
                        <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                            Opsi Verifikasi &amp; e-Sign
                        </label>
                        <div class="u-flex u-items-center u-gap-md u-flex-wrap">
                            <label class="u-text-xs">
                                <input type="checkbox" name="requires_camera" id="editRequiresCamera" value="1"
                                    class="u-mr-xs">
                                Kamera (foto saat tanda tangan)
                            </label>
                            <label class="u-text-xs">
                                <input type="checkbox" name="requires_geolocation" id="editRequiresGeolocation" value="1"
                                    class="u-mr-xs">
                                Geo-location
                            </label>
                            <label class="u-text-xs">
                                <input type="checkbox" name="requires_draw_signature" id="editRequiresDraw" value="1"
                                    class="u-mr-xs">
                                Tanda tangan digital (drawing)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="u-modal__foot">
                    <div class="u-text-sm u-muted">
                        <strong>Simpan Draft</strong> untuk menyimpan di level SDM Unit,
                        <strong>Submit ke Kepala Unit</strong> untuk masuk ke tahap review dan generate nomor.
                    </div>
                    <div class="u-flex u-gap-sm">
                        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                        <button type="submit" class="u-btn u-btn--soft" name="submit_action" value="draft">
                            <i class="fas fa-save u-mr-xs"></i> Simpan Draft
                        </button>
                        <button type="submit" class="u-btn u-btn--brand u-hover-lift" name="submit_action" value="submit">
                            <i class="fas fa-paper-plane u-mr-xs"></i> Submit ke Kepala Unit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const app = {
                init() {
                    this.bindModal();
                    this.bindRupiahFormatter();
                    this.bindTerbilangAutoFill();
                    this.bindFormSubmit();
                    this.bindDetailAndEdit();
                },

                openModal(modal) {
                    if (!modal) return;
                    modal.hidden = false;
                    document.body.classList.add('modal-open');
                },

                closeModal(modal) {
                    if (!modal) return;
                    modal.hidden = true;
                    if (!document.querySelector('.u-modal:not([hidden])')) {
                        document.body.classList.remove('modal-open');
                    }
                },

                bindModal() {
                    const modal = document.getElementById('createContractModal');
                    const form = document.getElementById('createContractForm');
                    const familySelect = document.getElementById('contractFamilySelect');
                    const familyHint = document.getElementById('contractFamilyHint');
                    const subtypeWrapper = document.getElementById('contractSubtypeWrapper');
                    const subtypeSelect = document.getElementById('contractSubtypeSelect');
                    const subtypeHint = document.getElementById('contractSubtypeHint');
                    const typeInput = document.getElementById('contractTypeInput');
                    const modeInput = document.getElementById('contractModeInput');
                    const sectionsWrapper = document.getElementById('contractFormSections');
                    const unitWrapper = document.getElementById('unitFieldWrapper');
                    const unitSelect = document.getElementById('contractUnitSelect');
                    const sourceSelect = document.getElementById('sourceContractSelect');
                    const sourceInput = document.getElementById('sourceContractInput');
                    const sourceSelectTerm = document.getElementById('sourceContractSelectTerminate');
                    const applicantSelect = document.getElementById('applicantSelect');
                    const remunerationSection = document.getElementById('remunerationSection');

                    const allSourceOptions = sourceSelect ? Array.from(sourceSelect.options) : [];
                    const allSourceTermOptions = sourceSelectTerm ? Array.from(sourceSelectTerm.options) : [];

                    const appPreviewBox = document.getElementById('applicantPreview');
                    const appPrevName = document.getElementById('applicantPreviewName');
                    const appPrevPos = document.getElementById('applicantPreviewPosition');
                    const appPrevUnit = document.getElementById('applicantPreviewUnit');

                    const srcPreviewBox = document.getElementById('sourceContractPreview');
                    const srcPrevNo = document.getElementById('sourcePreviewNo');
                    const srcPrevPerson = document.getElementById('sourcePreviewPerson');
                    const srcPrevPosition = document.getElementById('sourcePreviewPosition');
                    const srcPrevUnit = document.getElementById('sourcePreviewUnit');
                    const srcPrevPeriod = document.getElementById('sourcePreviewPeriod');

                    const srcPrevBoxTerm = document.getElementById('sourceContractPreviewTerminate');
                    const srcPrevNoTerm = document.getElementById('sourcePreviewNoTerminate');
                    const srcPrevPersonTerm = document.getElementById('sourcePreviewPersonTerminate');
                    const srcPrevPosTerm = document.getElementById('sourcePreviewPositionTerminate');
                    const srcPrevUnitTerm = document.getElementById('sourcePreviewUnitTerminate');
                    const srcPrevPeriodTerm = document.getElementById('sourcePreviewPeriodTerminate');

                    function hideAllSections() {
                        if (sectionsWrapper) sectionsWrapper.hidden = true;
                        document.querySelectorAll('[data-mode-section]').forEach(el => { el.hidden = true; });
                        if (remunerationSection) remunerationSection.hidden = true;
                    }

                    function showSectionByMode(mode) {
                        if (!sectionsWrapper) return;
                        if (!mode) {
                            hideAllSections();
                            return;
                        }
                        sectionsWrapper.hidden = false;
                        document.querySelectorAll('[data-mode-section]').forEach(el => {
                            const elMode = el.getAttribute('data-mode-section');
                            el.hidden = (elMode !== mode);
                        });

                        updateRemunerationVisibility(mode);
                    }

                    function updateRemunerationVisibility(mode) {
                        if (!remunerationSection) return;
                        const modesAttr = remunerationSection.getAttribute('data-mode-visible') || '';
                        const list = modesAttr.split(',').map(s => s.trim()).filter(Boolean);
                        remunerationSection.hidden = !list.includes(mode);
                    }

                    function hideUnit() {
                        if (unitWrapper) unitWrapper.hidden = true;
                    }

                    function showUnit() {
                        if (unitWrapper) unitWrapper.hidden = false;
                    }

                    function resetPreviews() {
                        if (appPreviewBox) appPreviewBox.hidden = true;
                        if (appPrevName) appPrevName.textContent = '-';
                        if (appPrevPos) appPrevPos.textContent = '-';
                        if (appPrevUnit) appPrevUnit.textContent = '-';

                        if (srcPreviewBox) srcPreviewBox.hidden = true;
                        if (srcPrevNo) srcPrevNo.textContent = '-';
                        if (srcPrevPerson) srcPrevPerson.textContent = '-';
                        if (srcPrevPosition) srcPrevPosition.textContent = '-';
                        if (srcPrevUnit) srcPrevUnit.textContent = '-';
                        if (srcPrevPeriod) srcPrevPeriod.textContent = '-';

                        if (srcPrevBoxTerm) srcPrevBoxTerm.hidden = true;
                        if (srcPrevNoTerm) srcPrevNoTerm.textContent = '-';
                        if (srcPrevPersonTerm) srcPrevPersonTerm.textContent = '-';
                        if (srcPrevPosTerm) srcPrevPosTerm.textContent = '-';
                        if (srcPrevUnitTerm) srcPrevUnitTerm.textContent = '-';
                        if (srcPrevPeriodTerm) srcPrevPeriodTerm.textContent = '-';
                    }

                    function resetFormLayout() {
                        if (!form) return;

                        if (modeInput) modeInput.value = '';
                        if (typeInput) typeInput.value = '';
                        if (familyHint) familyHint.textContent = '';
                        if (subtypeHint) subtypeHint.textContent = '';
                        if (subtypeWrapper) subtypeWrapper.hidden = true;

                        hideAllSections();
                        hideUnit();
                        resetPreviews();
                    }

                    function filterSourceByUnit(unitId) {
                        if (!sourceSelect) return;
                        const val = unitId ? String(unitId) : '';
                        allSourceOptions.forEach((opt, idx) => {
                            if (idx === 0) {
                                opt.hidden = false;
                                opt.disabled = false;
                                return;
                            }
                            const optUnit = opt.dataset.unitId || '';
                            const match = !val || optUnit === val;
                            opt.hidden = !match;
                            opt.disabled = !match;
                        });

                        if (sourceSelect.value) {
                            const current = sourceSelect.options[sourceSelect.selectedIndex];
                            if (current && current.disabled) {
                                sourceSelect.value = '';
                                if (sourceInput) sourceInput.value = '';
                                updateSourcePreview();
                            }
                        }
                    }

                    function filterSourceTermByUnit(unitId) {
                        if (!sourceSelectTerm) return;
                        const val = unitId ? String(unitId) : '';
                        allSourceTermOptions.forEach((opt, idx) => {
                            if (idx === 0) {
                                opt.hidden = false;
                                opt.disabled = false;
                                return;
                            }
                            const optUnit = opt.dataset.unitId || '';
                            const match = !val || optUnit === val;
                            opt.hidden = !match;
                            opt.disabled = !match;
                        });

                        if (sourceSelectTerm.value) {
                            const current = sourceSelectTerm.options[sourceSelectTerm.selectedIndex];
                            if (current && current.disabled) {
                                sourceSelectTerm.value = '';
                                if (sourceInput) sourceInput.value = '';
                                updateSourcePreviewTerminate();
                            }
                        }
                    }

                    function handleFamilyChange() {
                        if (!familySelect) return;
                        const familyVal = familySelect.value || '';

                        if (subtypeSelect) subtypeSelect.value = '';
                        if (subtypeHint) subtypeHint.textContent = '';

                        if (!familyVal) {
                            if (typeInput) typeInput.value = '';
                            if (modeInput) modeInput.value = '';
                            if (familyHint) familyHint.textContent = '';
                            if (subtypeWrapper) subtypeWrapper.hidden = true;
                            hideAllSections();
                            hideUnit();
                            return;
                        }

                        if (familyVal === 'SPK') {
                            if (subtypeWrapper) subtypeWrapper.hidden = true;

                            const opt = familySelect.options[familySelect.selectedIndex];
                            const mode = opt ? (opt.dataset.mode || 'new') : 'new';
                            const hint = opt ? (opt.dataset.hint || '') : '';

                            if (typeInput) typeInput.value = 'SPK';
                            if (modeInput) modeInput.value = mode;
                            if (familyHint) familyHint.textContent = hint;

                            showUnit();
                            showSectionByMode(mode);

                            if (unitSelect) {
                                filterSourceByUnit(unitSelect.value || '');
                                filterSourceTermByUnit(unitSelect.value || '');
                            }
                            return;
                        }

                        if (familyVal === 'PKWT') {
                            if (typeInput) typeInput.value = '';
                            if (modeInput) modeInput.value = '';
                            if (subtypeWrapper) subtypeWrapper.hidden = false;
                            if (familyHint) {
                                const opt = familySelect.options[familySelect.selectedIndex];
                                familyHint.textContent = opt && opt.dataset.hint
                                    ? opt.dataset.hint
                                    : 'Pilih detail PKWT (baru / perpanjangan).';
                            }
                            hideAllSections();
                            showUnit();
                            return;
                        }

                        if (familyVal === 'PB') {
                            if (subtypeWrapper) subtypeWrapper.hidden = true;

                            const opt = familySelect.options[familySelect.selectedIndex];
                            const mode = opt ? (opt.dataset.mode || 'terminate') : 'terminate';
                            const hint = opt ? (opt.dataset.hint || '') : '';

                            if (typeInput) typeInput.value = 'PB_PENGAKHIRAN';
                            if (modeInput) modeInput.value = mode;
                            if (familyHint) familyHint.textContent = hint;

                            showUnit();
                            showSectionByMode(mode);

                            if (unitSelect) {
                                filterSourceByUnit(unitSelect.value || '');
                                filterSourceTermByUnit(unitSelect.value || '');
                            }
                            return;
                        }

                        if (typeInput) typeInput.value = '';
                        if (modeInput) modeInput.value = '';
                        hideAllSections();
                        hideUnit();
                    }

                    function handleSubtypeChange() {
                        if (!subtypeSelect) return;
                        const code = subtypeSelect.value || '';
                        if (!code) {
                            if (typeInput) typeInput.value = '';
                            if (modeInput) modeInput.value = '';
                            if (subtypeHint) subtypeHint.textContent = '';
                            hideAllSections();
                            return;
                        }

                        const opt = subtypeSelect.options[subtypeSelect.selectedIndex];
                        const mode = opt ? (opt.dataset.mode || 'new') : 'new';
                        const hint = opt ? (opt.dataset.hint || '') : '';

                        if (typeInput) typeInput.value = code;
                        if (modeInput) modeInput.value = mode;
                        if (subtypeHint) subtypeHint.textContent = hint;

                        showUnit();
                        showSectionByMode(mode);

                        if (unitSelect) {
                            filterSourceByUnit(unitSelect.value || '');
                            filterSourceTermByUnit(unitSelect.value || '');
                        }
                    }

                    function updateApplicantPreview() {
                        if (!applicantSelect || !appPreviewBox) return;
                        const val = applicantSelect.value || '';
                        const opt = val ? applicantSelect.options[applicantSelect.selectedIndex] : null;
                        if (!opt || !opt.dataset.fullName) {
                            appPreviewBox.hidden = true;
                            if (appPrevName) appPrevName.textContent = '-';
                            if (appPrevPos) appPrevPos.textContent = '-';
                            if (appPrevUnit) appPrevUnit.textContent = '-';
                            return;
                        }
                        if (appPrevName) appPrevName.textContent = opt.dataset.fullName || '-';
                        if (appPrevPos) appPrevPos.textContent = opt.dataset.position || '-';
                        if (appPrevUnit) appPrevUnit.textContent = opt.dataset.unit || '-';
                        appPreviewBox.hidden = false;
                    }

                    function updateSourcePreview() {
                        if (!sourceSelect || !srcPreviewBox) return;
                        const val = sourceSelect.value || '';
                        const opt = val ? sourceSelect.options[sourceSelect.selectedIndex] : null;
                        if (!opt || !opt.dataset.contractNo) {
                            srcPreviewBox.hidden = true;
                            if (srcPrevNo) srcPrevNo.textContent = '-';
                            if (srcPrevPerson) srcPrevPerson.textContent = '-';
                            if (srcPrevPosition) srcPrevPosition.textContent = '-';
                            if (srcPrevUnit) srcPrevUnit.textContent = '-';
                            if (srcPrevPeriod) srcPrevPeriod.textContent = '-';
                            return;
                        }

                        if (srcPrevNo) srcPrevNo.textContent = opt.dataset.contractNo || '-';
                        if (srcPrevPerson) srcPrevPerson.textContent = opt.dataset.person || '-';
                        if (srcPrevPosition) srcPrevPosition.textContent = opt.dataset.position || '-';
                        if (srcPrevUnit) srcPrevUnit.textContent = opt.dataset.unit || '-';
                        if (srcPrevPeriod) srcPrevPeriod.textContent = (opt.dataset.start || '-') + ' s/d ' + (opt.dataset.end || '-');

                        srcPreviewBox.hidden = false;
                    }

                    function updateSourcePreviewTerminate() {
                        if (!sourceSelectTerm || !srcPrevBoxTerm) return;
                        const val = sourceSelectTerm.value || '';
                        const opt = val ? sourceSelectTerm.options[sourceSelectTerm.selectedIndex] : null;
                        if (!opt || !opt.dataset.contractNo) {
                            srcPrevBoxTerm.hidden = true;
                            if (srcPrevNoTerm) srcPrevNoTerm.textContent = '-';
                            if (srcPrevPersonTerm) srcPrevPersonTerm.textContent = '-';
                            if (srcPrevPosTerm) srcPrevPosTerm.textContent = '-';
                            if (srcPrevUnitTerm) srcPrevUnitTerm.textContent = '-';
                            if (srcPrevPeriodTerm) srcPrevPeriodTerm.textContent = '-';
                            return;
                        }

                        if (srcPrevNoTerm) srcPrevNoTerm.textContent = opt.dataset.contractNo || '-';
                        if (srcPrevPersonTerm) srcPrevPersonTerm.textContent = opt.dataset.person || '-';
                        if (srcPrevPosTerm) srcPrevPosTerm.textContent = opt.dataset.position || '-';
                        if (srcPrevUnitTerm) srcPrevUnitTerm.textContent = opt.dataset.unit || '-';
                        if (srcPrevPeriodTerm) srcPrevPeriodTerm.textContent = (opt.dataset.start || '-') + ' s/d ' + (opt.dataset.end || '-');

                        srcPrevBoxTerm.hidden = false;
                    }

                    document.addEventListener('click', e => {
                        const openBtn = e.target.closest && e.target.closest('[data-modal-open]');
                        if (openBtn && openBtn.getAttribute('data-modal-open') === 'createContractModal') {
                            if (modal) {
                                resetFormLayout();
                                app.openModal(modal);

                                const initialType = typeInput ? (typeInput.value || '') : '';
                                const typesPkwt = ['PKWT_BARU', 'PKWT_PERPANJANGAN'];

                                if (initialType === 'SPK') {
                                    if (familySelect) {
                                        familySelect.value = 'SPK';
                                        handleFamilyChange();
                                    }
                                } else if (typesPkwt.includes(initialType)) {
                                    if (familySelect) {
                                        familySelect.value = 'PKWT';
                                        handleFamilyChange();
                                    }
                                    if (subtypeSelect) {
                                        subtypeSelect.value = initialType;
                                        handleSubtypeChange();
                                    }
                                } else if (initialType === 'PB_PENGAKHIRAN') {
                                    if (familySelect) {
                                        familySelect.value = 'PB';
                                        handleFamilyChange();
                                    }
                                }

                                if (sourceSelect && sourceInput && sourceInput.value) {
                                    Array.from(sourceSelect.options).forEach(opt => {
                                        if (opt.value === sourceInput.value) {
                                            sourceSelect.value = opt.value;
                                        }
                                    });
                                    updateSourcePreview();
                                }

                                if (unitSelect) {
                                    filterSourceByUnit(unitSelect.value || '');
                                    filterSourceTermByUnit(unitSelect.value || '');
                                }
                                updateApplicantPreview();
                                updateSourcePreviewTerminate();
                            }
                        }

                        const closeBtn = e.target.matches('[data-modal-close]')
                            ? e.target
                            : (e.target.closest && e.target.closest('[data-modal-close]'));

                        if (closeBtn) {
                            const m = closeBtn.closest('.u-modal');
                            if (m) {
                                app.closeModal(m);
                            }
                        }
                    });

                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') {
                            const open = document.querySelector('.u-modal:not([hidden])');
                            if (open) {
                                app.closeModal(open);
                            }
                        }
                    });

                    if (familySelect) familySelect.addEventListener('change', handleFamilyChange);
                    if (subtypeSelect) subtypeSelect.addEventListener('change', handleSubtypeChange);

                    if (unitSelect) {
                        unitSelect.addEventListener('change', function () {
                            filterSourceByUnit(this.value || '');
                            filterSourceTermByUnit(this.value || '');
                        });
                    }

                    if (sourceSelect && sourceInput) {
                        sourceSelect.addEventListener('change', function () {
                            sourceInput.value = this.value || '';
                            updateSourcePreview();
                        });
                    }

                    if (sourceSelectTerm && sourceInput) {
                        sourceSelectTerm.addEventListener('change', function () {
                            sourceInput.value = this.value || '';
                            updateSourcePreviewTerminate();
                        });
                    }

                    if (applicantSelect) {
                        applicantSelect.addEventListener('change', updateApplicantPreview);
                    }

                    @if($errors->any())
                        if (modal) {
                            app.openModal(modal);

                            const initialType = typeInput ? (typeInput.value || '') : '';
                            const typesPkwt = ['PKWT_BARU', 'PKWT_PERPANJANGAN'];

                            if (initialType === 'SPK') {
                                if (familySelect) {
                                    familySelect.value = 'SPK';
                                    handleFamilyChange();
                                }
                            } else if (typesPkwt.includes(initialType)) {
                                if (familySelect) {
                                    familySelect.value = 'PKWT';
                                    handleFamilyChange();
                                }
                                if (subtypeSelect) {
                                    subtypeSelect.value = initialType;
                                    handleSubtypeChange();
                                }
                            } else if (initialType === 'PB_PENGAKHIRAN') {
                                if (familySelect) {
                                    familySelect.value = 'PB';
                                    handleFamilyChange();
                                }
                            }

                            if (sourceSelect && sourceInput && sourceInput.value) {
                                Array.from(sourceSelect.options).forEach(opt => {
                                    if (opt.value === sourceInput.value) {
                                        sourceSelect.value = opt.value;
                                    }
                                });
                                updateSourcePreview();
                            }

                            if (unitSelect) {
                                filterSourceByUnit(unitSelect.value || '');
                                filterSourceTermByUnit(unitSelect.value || '');
                            }
                            updateApplicantPreview();
                            updateSourcePreviewTerminate();
                        }
                    @endif
                },

                bindRupiahFormatter() {
                    function formatRupiah(value) {
                        const digits = (value || '').replace(/[^\d]/g, '');
                        if (!digits) return '';
                        const withDots = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        return 'Rp. ' + withDots;
                    }

                    document.querySelectorAll('input[data-rupiah="true"]').forEach(input => {
                        if (input._rupiahBound) return;
                        input._rupiahBound = true;

                        input.addEventListener('focus', function () {
                            const digits = this.value.replace(/[^\d]/g, '');
                            this.value = digits;
                        });

                        input.addEventListener('blur', function () {
                            this.value = formatRupiah(this.value);
                        });

                        input.addEventListener('input', function () {
                            const raw = this.value.replace(/[^\d]/g, '');
                            this.value = formatRupiah(raw);
                            this.setSelectionRange(this.value.length, this.value.length);
                        });

                        if (input.value) {
                            input.value = formatRupiah(input.value);
                        }
                    });
                },

                bindTerbilangAutoFill() {
                    function toInt(value) {
                        const digits = (value || '').replace(/[^\d]/g, '');
                        return digits ? parseInt(digits, 10) : 0;
                    }

                    function terbilang(n) {
                        n = Math.floor(Math.abs(n));
                        const huruf = [
                            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
                            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'
                        ];

                        if (n < 12) return huruf[n];
                        if (n < 20) return terbilang(n - 10) + ' belas';
                        if (n < 100) return terbilang(Math.floor(n / 10)) + ' puluh ' + terbilang(n % 10);
                        if (n < 200) return 'seratus ' + terbilang(n - 100);
                        if (n < 1000) return terbilang(Math.floor(n / 100)) + ' ratus ' + terbilang(n % 100);
                        if (n < 2000) return 'seribu ' + terbilang(n - 1000);
                        if (n < 1000000) return terbilang(Math.floor(n / 1000)) + ' ribu ' + terbilang(n % 1000);
                        if (n < 1000000000) return terbilang(Math.floor(n / 1000000)) + ' juta ' + terbilang(n % 1000000);
                        if (n < 1000000000000) return terbilang(Math.floor(n / 1000000000)) + ' miliar ' + terbilang(n % 1000000000);
                        return String(n);
                    }

                    function applyTerbilang(input) {
                        const targetName = input.getAttribute('data-terbilang-target');
                        if (!targetName) return;

                        const form = input.closest('form');
                        if (!form) return;

                        const target = form.querySelector('[name="' + targetName + '"]');
                        if (!target) return;

                        const nilai = toInt(input.value || '');
                        if (!nilai) {
                            target.value = '';
                            return;
                        }

                        const text = terbilang(nilai).trim() + ' rupiah';
                        target.value = text.toUpperCase();
                    }

                    document.querySelectorAll('input[data-rupiah="true"][data-terbilang-target]').forEach(input => {
                        if (input._terbilangBound) return;
                        input._terbilangBound = true;

                        input.addEventListener('input', function () {
                            applyTerbilang(this);
                        });

                        if (input.value) {
                            applyTerbilang(input);
                        }
                    });
                },

                bindFormSubmit() {
                    document.querySelectorAll('form').forEach(form => {
                        if (form._submitBound) return;
                        form._submitBound = true;

                        form.addEventListener('submit', function (e) {
                            const submitBtn = e.submitter;
                            if (submitBtn && submitBtn.name === 'submit_action') {
                                this.submit_action = submitBtn.value;
                            }
                        });
                    });
                },

                bindDetailAndEdit() {
                    const detailModal = document.getElementById('contractDetailModal');
                    const editModal = document.getElementById('editContractModal');
                    const editForm = document.getElementById('editContractForm');

                    const rejectModal = document.getElementById('rejectContractModal');
                    const rejectForm = document.getElementById('rejectContractForm');
                    const signModal = document.getElementById('signContractModal');
                    const signForm = document.getElementById('signContractForm');

                    const detailApproveBtn = document.getElementById('detailApproveBtn');
                    const detailRejectBtn = document.getElementById('detailRejectBtn');
                    const detailSignBtn = document.getElementById('detailSignBtn');
                    const detailFlowHint = document.getElementById('detailFlowHint');

                    const dNo = document.getElementById('detailContractNo');
                    const dType = document.getElementById('detailContractType');
                    const dStatus = document.getElementById('detailContractStatus');
                    const dUnit = document.getElementById('detailContractUnit');
                    const dPeriode = document.getElementById('detailContractPeriod');
                    const dPerson = document.getElementById('detailContractPerson');
                    const dPosition = document.getElementById('detailContractPosition');
                    const dRemarks = document.getElementById('detailContractRemarks');

                    const approvalInfo = document.getElementById('approvalInfo');
                    const approvalStatusText = document.getElementById('approvalStatusText');
                    const approvalNote = document.getElementById('approvalNote');

                    const signCanvas = document.getElementById('signCanvas');
                    const signClearBtn = document.getElementById('signClearBtn');
                    const signImageInput = document.getElementById('signImageInput');
                    const signStatusHint = document.getElementById('signStatusHint');
                    const signModalTitle = document.getElementById('signModalTitle');
                    const signModalDesc = document.getElementById('signModalDescription');
                    const signNoteSection = document.getElementById('signNoteSection');
                    const verificationRequirements = document.getElementById('verificationRequirements');
                    const signSubmitBtn = document.getElementById('signSubmitBtn');

                    const editApplicantWrapper = document.getElementById('editApplicantWrapper');
                    const editExistingWrapper = document.getElementById('editExistingWrapper');
                    const editApplicantSelect = document.getElementById('editApplicantSelect');
                    const editSourceSelect = document.getElementById('editSourceContractSelect');

                    let currentApproveUrl = null;
                    let currentSignUrl = null;
                    let currentRejectUrl = null;
                    let currentContractId = null;
                    let currentContractData = null;
                    let signCtx = null;
                    let signDrawing = false;

                    function normalizeRequirements(data) {
                        if (!data) return { needCamera: false, needGeo: false, needDraw: false };

                        const needCamera = !!data.requires_camera;
                        const needGeo = !!(data.requires_geolocation ?? data.requires_geo);
                        const needDraw = !!(data.requires_draw_signature ?? data.requires_draw);

                        return { needCamera, needGeo, needDraw };
                    }

                    function setDetailLoading(isLoading) {
                        if (!detailModal) return;

                        if (isLoading) {
                            if (dNo) dNo.textContent = 'Memuat...';
                            if (dType) dType.textContent = 'Memuat...';
                            if (dStatus) dStatus.textContent = 'Memuat...';
                            if (dUnit) dUnit.textContent = 'Memuat...';
                            if (dPeriode) dPeriode.textContent = 'Memuat...';
                            if (dPerson) dPerson.textContent = 'Memuat...';
                            if (dPosition) dPosition.textContent = 'Memuat...';
                            if (dRemarks) dRemarks.textContent = '—';
                            if (detailFlowHint) detailFlowHint.textContent = '';

                            if (approvalInfo) approvalInfo.hidden = true;

                            if (detailApproveBtn) detailApproveBtn.hidden = true;
                            if (detailRejectBtn) detailRejectBtn.hidden = true;
                            if (detailSignBtn) detailSignBtn.hidden = true;
                        }
                    }

                    function initSignCanvas() {
                        if (!signCanvas) return;
                        if (!signCtx) {
                            signCanvas.width = signCanvas.offsetWidth || 600;
                            signCanvas.height = 180;
                            signCtx = signCanvas.getContext('2d');
                            signCtx.lineWidth = 2;
                            signCtx.lineCap = 'round';
                            signCtx.strokeStyle = '#222';
                            signCtx.fillStyle = '#222';
                        }

                        function getPos(e) {
                            const rect = signCanvas.getBoundingClientRect();
                            const evt = e.touches ? e.touches[0] : e;
                            return {
                                x: evt.clientX - rect.left,
                                y: evt.clientY - rect.top,
                            };
                        }

                        function startDraw(e) {
                            e.preventDefault();
                            signDrawing = true;
                            const p = getPos(e);
                            signCtx.beginPath();
                            signCtx.moveTo(p.x, p.y);
                            if (signStatusHint) signStatusHint.textContent = 'Sedang menggambar...';
                            signCanvas.style.cursor = 'crosshair';
                        }

                        function moveDraw(e) {
                            if (!signDrawing) return;
                            e.preventDefault();
                            const p = getPos(e);
                            signCtx.lineTo(p.x, p.y);
                            signCtx.stroke();
                        }

                        function endDraw(e) {
                            if (!signDrawing) return;
                            e.preventDefault();
                            signDrawing = false;
                            if (signStatusHint) signStatusHint.textContent = 'Tanda tangan sudah terisi.';
                            signCanvas.style.cursor = 'default';
                        }

                        if (!signCanvas._boundEvents) {
                            signCanvas.addEventListener('mousedown', startDraw);
                            signCanvas.addEventListener('mousemove', moveDraw);
                            signCanvas.addEventListener('mouseup', endDraw);
                            signCanvas.addEventListener('mouseleave', endDraw);

                            signCanvas.addEventListener('touchstart', startDraw, { passive: false });
                            signCanvas.addEventListener('touchmove', moveDraw, { passive: false });
                            signCanvas.addEventListener('touchend', endDraw, { passive: false });

                            signCanvas._boundEvents = true;
                        }
                    }

                    if (signClearBtn && signCanvas) {
                        signClearBtn.addEventListener('click', function () {
                            if (!signCtx) initSignCanvas();
                            if (!signCtx) return;
                            signCtx.clearRect(0, 0, signCanvas.width, signCanvas.height);
                            if (signStatusHint) signStatusHint.textContent = 'Belum ada coretan.';
                        });
                    }

                    function updateEditFormByType(ct) {
                        const type = ct || '';
                        const isNewType = (type === 'SPK' || type === 'PKWT_BARU');
                        const isExistingType = (type === 'PKWT_PERPANJANGAN' || type === 'PB_PENGAKHIRAN');

                        if (!editApplicantWrapper || !editExistingWrapper) return;

                        if (isNewType) {
                            editApplicantWrapper.hidden = false;
                            editExistingWrapper.hidden = true;
                        } else if (isExistingType) {
                            editApplicantWrapper.hidden = true;
                            editExistingWrapper.hidden = false;
                        } else {
                            editApplicantWrapper.hidden = false;
                            editExistingWrapper.hidden = true;
                        }
                    }

                    function simplePost(url, bodyObj, onOk, onError) {
                        const fd = new FormData();
                        Object.keys(bodyObj || {}).forEach(k => fd.append(k, bodyObj[k]));

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: fd,
                        })
                            .then(r => {
                                if (!r.ok) throw new Error('Network response was not ok');
                                return r.json();
                            })
                            .then(data => {
                                if (data.ok) {
                                    onOk && onOk(data);
                                } else {
                                    throw new Error(data.message || 'Proses gagal.');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                onError && onError(err);
                            });
                    }

                    document.addEventListener('click', e => {
                        const detailBtn = e.target.closest && e.target.closest('[data-contract-detail]');
                        if (detailBtn) {
                            const url = detailBtn.getAttribute('data-show-url');
                            if (!url) return;

                            if (detailModal) {
                                setDetailLoading(true);
                                app.openModal(detailModal);
                            }

                            fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                                .then(r => r.json())
                                .then(resp => {
                                    const data = resp.data || {};
                                    const unit = data.unit || {};

                                    const start = data.start_date || null;
                                    const end = data.end_date || null;
                                    let periode = '-';
                                    if (start && end) {
                                        periode = start + ' s/d ' + end;
                                    }

                                    const status = data.status || '-';
                                    let statusLabel = (status || '').replace(/_/g, ' ');
                                    statusLabel = statusLabel.charAt(0).toUpperCase() + statusLabel.slice(1);

                                    const personName =
                                        data.person_name ||
                                        data.employee_name ||
                                        data.display_name ||
                                        '-';

                                    if (dNo) dNo.textContent = data.contract_no || '—';
                                    if (dType) dType.textContent = data.contract_type_label || data.contract_type || '—';
                                    if (dStatus) dStatus.textContent = statusLabel;
                                    if (dUnit) dUnit.textContent = (unit && unit.name) || '—';
                                    if (dPeriode) dPeriode.textContent = periode;
                                    if (dPosition) dPosition.textContent = data.position_name || '—';
                                    if (dRemarks) dRemarks.textContent = data.remarks || '-';
                                    if (dPerson) dPerson.textContent = personName;

                                    const flowHint = data.flow_hint || '';
                                    if (detailFlowHint) detailFlowHint.textContent = flowHint;

                                    if (data.approval && approvalInfo && approvalStatusText && approvalNote) {
                                        const statusMap = {
                                            'pending': 'Menunggu',
                                            'approved': 'Disetujui',
                                            'rejected': 'Ditolak',
                                            'completed': 'Selesai'
                                        };
                                        approvalStatusText.textContent = statusMap[data.approval.status] || data.approval.status;
                                        approvalNote.textContent = data.approval.note || '-';
                                        approvalInfo.hidden = false;
                                    } else if (approvalInfo) {
                                        approvalInfo.hidden = true;
                                    }

                                    currentContractId = data.id || null;
                                    currentContractData = data;
                                    currentApproveUrl = data.approve_url || null;
                                    currentSignUrl = data.sign_url || null;
                                    currentRejectUrl = data.reject_url || null;

                                    const { needCamera, needGeo, needDraw } = normalizeRequirements(data);

                                    if (detailApproveBtn) detailApproveBtn.hidden = !data.can_approve;
                                    if (detailRejectBtn) detailRejectBtn.hidden = !data.can_approve;
                                    if (detailSignBtn) detailSignBtn.hidden = !data.can_sign;

                                    if (detailSignBtn && data.can_sign) {
                                        if (data.status === 'approved') {
                                            detailSignBtn.innerHTML = '<i class="fas fa-pen-nib u-mr-xxs"></i> e-Sign sebagai Kandidat';
                                        } else {
                                            detailSignBtn.innerHTML = '<i class="fas fa-pen-nib u-mr-xxs"></i> e-Sign Kontrak';
                                        }
                                    }

                                    // Setup teks requirements default
                                    if (verificationRequirements) {
                                        const reqCamera = document.getElementById('reqCamera');
                                        const reqGeo = document.getElementById('reqGeo');
                                        const reqDraw = document.getElementById('reqDraw');

                                        if (reqCamera) {
                                            reqCamera.textContent = needCamera
                                                ? '• Kamera: Diperlukan (foto akan diambil)'
                                                : '• Kamera: Tidak diperlukan';
                                            reqCamera.style.color = needCamera ? 'var(--color-brand)' : '';
                                        }
                                        if (reqGeo) {
                                            reqGeo.textContent = needGeo
                                                ? '• Geo-location: Diperlukan'
                                                : '• Geo-location: Tidak diperlukan';
                                            reqGeo.style.color = needGeo ? 'var(--color-brand)' : '';
                                        }
                                        if (reqDraw) {
                                            reqDraw.textContent = needDraw
                                                ? '• Tanda tangan digital: Diperlukan'
                                                : '• Tanda tangan digital: Tidak diperlukan';
                                            reqDraw.style.color = needDraw ? 'var(--color-brand)' : '';
                                        }
                                        verificationRequirements.hidden = !(needCamera || needGeo || needDraw);
                                    }
                                })
                                .catch(() => {
                                    if (dNo) dNo.textContent = 'Gagal memuat detail.';
                                    alert('Gagal memuat detail kontrak.');
                                });

                            return;
                        }

                        const editBtn = e.target.closest && e.target.closest('[data-contract-edit]');
                        if (editBtn) {
                            const url = editBtn.getAttribute('data-show-url');
                            const updateUrl = editBtn.getAttribute('data-update-url');
                            if (!url || !updateUrl || !editForm) return;

                            fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                                .then(r => r.json())
                                .then(resp => {
                                    const data = resp.data || {};
                                    const unit = data.unit || {};
                                    let metaRaw = data.remuneration || data.meta || data.remuneration_json || {};
                                    let meta = {};

                                    if (typeof metaRaw === 'string') {
                                        try { meta = JSON.parse(metaRaw); } catch (e) { meta = {}; }
                                    } else if (metaRaw && typeof metaRaw === 'object') {
                                        meta = metaRaw;
                                    }

                                    const idInput = document.getElementById('editContractId');
                                    const typeSelect = document.getElementById('editContractTypeSelect');
                                    const unitSelect = document.getElementById('editContractUnitSelect');
                                    const unitHidden = document.getElementById('editContractUnitHidden');
                                    const employmentSelect = document.getElementById('editEmploymentTypeSelect');
                                    const positionInput = document.getElementById('editPositionInput');
                                    const noteInput = document.getElementById('editNoteInput');
                                    const startInput = document.getElementById('editStartDateInput');
                                    const endInput = document.getElementById('editEndDateInput');

                                    const salaryInput = document.getElementById('editSalaryAmountInput');
                                    const salaryWordsInput = document.getElementById('edit_salary_amount_words');
                                    const lunchInput = document.getElementById('editLunchDailyInput');
                                    const lunchWordsInput = document.getElementById('edit_lunch_allowance_words');
                                    const allowanceSpecialInput = document.getElementById('editAllowanceSpecialInput');
                                    const allowanceSpecialWords = document.getElementById('edit_allowance_special_words');
                                    const allowancePositionInput = document.getElementById('editAllowancePositionInput');
                                    const allowancePositionWords = document.getElementById('edit_allowance_position_words');
                                    const allowanceCommInput = document.getElementById('editAllowanceCommInput');
                                    const allowanceCommWords = document.getElementById('edit_allowance_communication_words');
                                    const allowanceOtherInput = document.getElementById('editAllowanceOtherInput');
                                    const allowanceOtherWords = document.getElementById('edit_allowance_other_words');

                                    const otherBenefitsDesc = document.getElementById('editOtherBenefitsDesc');
                                    const allowOtherDesc = document.getElementById('editAllowanceOtherDesc');

                                    const editRequiresCamera = document.getElementById('editRequiresCamera');
                                    const editRequiresGeo = document.getElementById('editRequiresGeolocation');
                                    const editRequiresDraw = document.getElementById('editRequiresDraw');

                                    if (editForm) editForm.setAttribute('action', updateUrl);
                                    if (idInput) idInput.value = data.id || '';

                                    if (typeSelect) {
                                        typeSelect.value = data.contract_type || '';
                                        updateEditFormByType(data.contract_type || '');

                                        if (!typeSelect._boundUpdateMode) {
                                            typeSelect.addEventListener('change', function () {
                                                updateEditFormByType(this.value || '');
                                            });
                                            typeSelect._boundUpdateMode = true;
                                        }
                                    }

                                    if (unitSelect) unitSelect.value = unit.id || '';
                                    if (unitHidden && !unitSelect) {
                                        unitHidden.value = unit.id || unitHidden.value || '';
                                    }

                                    if (employmentSelect) employmentSelect.value = data.employment_type || '';

                                    if (editApplicantSelect) {
                                        editApplicantSelect.value = data.applicant_id || '';
                                    }
                                    if (editSourceSelect) {
                                        editSourceSelect.value = data.source_contract_id || '';
                                    }

                                    if (positionInput) positionInput.value = data.position_name || '';
                                    if (noteInput) noteInput.value = data.remarks || '';
                                    if (startInput) startInput.value = data.start_date || '';
                                    if (endInput) endInput.value = data.end_date || '';

                                    const { needCamera, needGeo, needDraw } = normalizeRequirements(data);

                                    if (editRequiresCamera) editRequiresCamera.checked = needCamera;
                                    if (editRequiresGeo) editRequiresGeo.checked = needGeo;
                                    if (editRequiresDraw) editRequiresDraw.checked = needDraw;

                                    if (salaryInput) salaryInput.value = meta.salary_amount || '';
                                    if (salaryWordsInput) salaryWordsInput.value = meta.salary_amount_words || '';
                                    if (lunchInput) lunchInput.value = meta.lunch_allowance_daily || '';
                                    if (lunchWordsInput) lunchWordsInput.value = meta.lunch_allowance_words || '';
                                    if (allowanceSpecialInput) allowanceSpecialInput.value = meta.allowance_special_amount || '';
                                    if (allowanceSpecialWords) allowanceSpecialWords.value = meta.allowance_special_words || '';
                                    if (allowancePositionInput) allowancePositionInput.value = meta.allowance_position_amount || '';
                                    if (allowancePositionWords) allowancePositionWords.value = meta.allowance_position_words || '';
                                    if (allowanceCommInput) allowanceCommInput.value = meta.allowance_communication_amount || '';
                                    if (allowanceCommWords) allowanceCommWords.value = meta.allowance_communication_words || '';
                                    if (allowanceOtherInput) allowanceOtherInput.value = meta.allowance_other_amount || '';
                                    if (allowanceOtherWords) allowanceOtherWords.value = meta.allowance_other_words || '';

                                    if (otherBenefitsDesc) otherBenefitsDesc.value = meta.other_benefits_desc || '';
                                    if (allowOtherDesc) allowOtherDesc.value = meta.allowance_other_desc || '';

                                    [
                                        salaryInput,
                                        lunchInput,
                                        allowanceSpecialInput,
                                        allowancePositionInput,
                                        allowanceCommInput,
                                        allowanceOtherInput
                                    ].forEach(inp => {
                                        if (inp) {
                                            const evt = new Event('input');
                                            inp.dispatchEvent(evt);
                                        }
                                    });

                                    app.openModal(editModal);
                                })
                                .catch(() => { });
                        }
                    });

                    if (detailRejectBtn) {
                        detailRejectBtn.addEventListener('click', function () {
                            if (!currentRejectUrl) {
                                alert('URL reject tidak tersedia.');
                                return;
                            }
                            app.openModal(rejectModal);
                        });
                    }

                    if (rejectForm) {
                        rejectForm.addEventListener('submit', function (e) {
                            e.preventDefault();
                            if (!currentRejectUrl) return;

                            if (!confirm('Reject kontrak ini dan kembalikan ke SDM Unit (status: draft)?')) return;

                            const formData = new FormData(rejectForm);

                            fetch(currentRejectUrl, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: formData,
                            })
                                .then(r => {
                                    if (!r.ok) throw new Error('Network response was not ok');
                                    return r.json();
                                })
                                .then(data => {
                                    if (data.ok) {
                                        app.closeModal(rejectModal);
                                        app.closeModal(detailModal);
                                        window.toastOk('Berhasil', data.message || 'Kontrak berhasil direject.');
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1500);
                                    } else {
                                        throw new Error(data.message || 'Gagal reject kontrak.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Gagal reject kontrak: ' + error.message);
                                });
                        });
                    }

                    if (detailApproveBtn) {
                        detailApproveBtn.addEventListener('click', function () {
                            if (!currentApproveUrl || !currentContractData) {
                                alert('URL approve tidak tersedia.');
                                return;
                            }

                            const { needCamera, needGeo, needDraw } = normalizeRequirements(currentContractData);

                            if (!needCamera && !needGeo && !needDraw) {
                                if (!confirm('Approve kontrak tanpa e-sign & verifikasi tambahan?')) return;

                                simplePost(
                                    currentApproveUrl,
                                    {},
                                    data => {
                                        app.closeModal(detailModal);
                                        window.toastOk('Berhasil', data.message || 'Kontrak berhasil di-approve.');
                                        setTimeout(() => window.location.reload(), 1500);
                                    },
                                    err => {
                                        alert('Gagal approve kontrak: ' + err.message);
                                    }
                                );
                                return;
                            }

                            signModalTitle.textContent = 'Approve & e-Sign Kontrak';
                            signModalDesc.textContent = 'Sebagai Kepala Unit, silakan tanda tangan untuk menyetujui kontrak ini.';
                            signNoteSection.hidden = false;

                            if (verificationRequirements) {
                                const reqCamera = document.getElementById('reqCamera');
                                const reqGeo = document.getElementById('reqGeo');
                                const reqDraw = document.getElementById('reqDraw');

                                if (reqCamera) {
                                    reqCamera.textContent = needCamera
                                        ? '• Kamera: Diperlukan (foto akan diambil)'
                                        : '• Kamera: Tidak diperlukan';
                                    reqCamera.style.color = needCamera ? 'var(--color-brand)' : '';
                                }
                                if (reqGeo) {
                                    reqGeo.textContent = needGeo
                                        ? '• Geo-location: Diperlukan'
                                        : '• Geo-location: Tidak diperlukan';
                                    reqGeo.style.color = needGeo ? 'var(--color-brand)' : '';
                                }
                                if (reqDraw) {
                                    reqDraw.textContent = needDraw
                                        ? '• Tanda tangan digital: Diperlukan'
                                        : '• Tanda tangan digital: Tidak diperlukan';
                                    reqDraw.style.color = needDraw ? 'var(--color-brand)' : '';
                                }
                                verificationRequirements.hidden = !(needCamera || needGeo || needDraw);
                            }

                            signForm.setAttribute('action', currentApproveUrl);
                            signForm.setAttribute('method', 'POST');

                            initSignCanvas();
                            if (signCtx && signCanvas) {
                                signCtx.clearRect(0, 0, signCanvas.width, signCanvas.height);
                            }
                            if (signStatusHint) signStatusHint.textContent = 'Belum ada coretan.';

                            app.openModal(signModal);
                        });
                    }

                    if (detailSignBtn) {
                        detailSignBtn.addEventListener('click', function () {
                            if (!currentSignUrl || !currentContractData) {
                                alert('URL e-sign tidak tersedia.');
                                return;
                            }

                            const { needCamera, needGeo, needDraw } = normalizeRequirements(currentContractData);

                            if (!needCamera && !needGeo && !needDraw) {
                                if (!confirm('Menandatangani kontrak tanpa e-sign canvas & verifikasi tambahan?')) return;

                                simplePost(
                                    currentSignUrl,
                                    {},
                                    data => {
                                        app.closeModal(detailModal);
                                        window.toastOk('Berhasil', data.message || 'Kontrak berhasil ditandatangani.');
                                        setTimeout(() => window.location.reload(), 1500);
                                    },
                                    err => {
                                        alert('Gagal e-sign kontrak: ' + err.message);
                                    }
                                );
                                return;
                            }

                            signModalTitle.textContent = 'e-Sign Kontrak sebagai Kandidat';
                            signModalDesc.textContent = 'Sebagai kandidat/pegawai, silakan tanda tangan untuk menyetujui kontrak ini.';
                            signNoteSection.hidden = false;

                            if (verificationRequirements) {
                                const reqCamera = document.getElementById('reqCamera');
                                const reqGeo = document.getElementById('reqGeo');
                                const reqDraw = document.getElementById('reqDraw');

                                if (reqCamera) {
                                    reqCamera.textContent = needCamera
                                        ? '• Kamera: Diperlukan (foto akan diambil)'
                                        : '• Kamera: Tidak diperlukan';
                                    reqCamera.style.color = needCamera ? 'var(--color-brand)' : '';
                                }
                                if (reqGeo) {
                                    reqGeo.textContent = needGeo
                                        ? '• Geo-location: Diperlukan'
                                        : '• Geo-location: Tidak diperlukan';
                                    reqGeo.style.color = needGeo ? 'var(--color-brand)' : '';
                                }
                                if (reqDraw) {
                                    reqDraw.textContent = needDraw
                                        ? '• Tanda tangan digital: Diperlukan'
                                        : '• Tanda tangan digital: Tidak diperlukan';
                                    reqDraw.style.color = needDraw ? 'var(--color-brand)' : '';
                                }
                                verificationRequirements.hidden = !(needCamera || needGeo || needDraw);
                            }

                            signForm.setAttribute('action', currentSignUrl);
                            signForm.setAttribute('method', 'POST');

                            initSignCanvas();
                            if (signCtx && signCanvas) {
                                signCtx.clearRect(0, 0, signCanvas.width, signCanvas.height);
                            }
                            if (signStatusHint) signStatusHint.textContent = 'Belum ada coretan.';

                            app.openModal(signModal);
                        });
                    }

                    if (signForm) {
                        signForm.addEventListener('submit', function (e) {
                            e.preventDefault();

                            const url = signForm.getAttribute('action');
                            if (!url || !currentContractData) return;

                            if (!signCtx) initSignCanvas();
                            const dataUrl = signCanvas.toDataURL('image/png');

                            const { needGeo, needDraw } = normalizeRequirements(currentContractData);

                            const isEmpty = app.isCanvasEmpty(signCanvas);
                            if (needDraw && isEmpty) {
                                alert('Harap berikan tanda tangan digital terlebih dahulu.');
                                return;
                            }

                            if (signImageInput) signImageInput.value = dataUrl;

                            function submitSignForm() {
                                const formData = new FormData(signForm);

                                if (signSubmitBtn) {
                                    signSubmitBtn.disabled = true;
                                    signSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin u-mr-xxs"></i> Menyimpan...';
                                }

                                fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: formData,
                                })
                                    .then(r => {
                                        if (!r.ok) throw new Error('Network response was not ok');
                                        return r.json();
                                    })
                                    .then(data => {
                                        if (data.ok) {
                                            app.closeModal(signModal);
                                            app.closeModal(detailModal);
                                            window.toastOk('Berhasil', data.message || 'Tanda tangan berhasil disimpan.');
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 1500);
                                        } else {
                                            throw new Error(data.message || 'Gagal menyimpan tanda tangan.');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Gagal menyimpan tanda tangan: ' + error.message);
                                    })
                                    .finally(() => {
                                        if (signSubmitBtn) {
                                            signSubmitBtn.disabled = false;
                                            signSubmitBtn.innerHTML = '<i class="fas fa-pen-nib u-mr-xxs"></i> Simpan Tanda Tangan';
                                        }
                                    });
                            }

                            if (navigator.geolocation && needGeo) {
                                navigator.geolocation.getCurrentPosition(function (pos) {
                                    const latInput = document.getElementById('signGeoLat');
                                    const lngInput = document.getElementById('signGeoLng');
                                    const accInput = document.getElementById('signGeoAcc');
                                    if (latInput) latInput.value = pos.coords.latitude;
                                    if (lngInput) lngInput.value = pos.coords.longitude;
                                    if (accInput) accInput.value = pos.coords.accuracy;

                                    submitSignForm();
                                }, function (error) {
                                    console.warn('Geolocation error:', error);
                                    if (confirm('Gagal mendapatkan lokasi. Lanjutkan tanpa geolocation?')) {
                                        submitSignForm();
                                    }
                                });
                            } else {
                                submitSignForm();
                            }
                        });
                    }
                },

                isCanvasEmpty(canvas) {
                    if (!canvas) return true;
                    const ctx = canvas.getContext('2d');
                    const pixelBuffer = new Uint32Array(
                        ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer
                    );
                    return !pixelBuffer.some(color => color !== 0);
                },
            };

            app.init();
        });
    </script>
@endpush