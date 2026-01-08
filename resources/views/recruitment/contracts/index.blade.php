@extends('layouts.app')

@section('title', 'Manajemen Dokumen Kontrak')

@section('content')
@php
    $me = auth()->user();
    $meUnit = $me?->unit_id;
    $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
    $statusOptions = config('recruitment.contract_statuses', []);
    $currentUnitId = $selectedUnitId ?? $meUnit;
@endphp

{{-- External Libs for Map --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

{{-- Custom Style Overrides --}}
<style>
    /* Utility Classes */
    .is-hidden { display: none !important; }
    
    /* Modal Styling - Center & Glass */
    .u-modal {
        display: flex !important; align-items: center; justify-content: center;
        padding: 1rem; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px);
        z-index: 99999 !important; /* Super High Z-Index to cover sidebar */
        position: fixed; inset: 0;
    }
    .u-modal[hidden] { display: none !important; }

    .u-modal__card {
        margin: auto; width: 100%; max-width: 750px; max-height: 90vh;
        display: flex; flex-direction: column;
        background: var(--surface-0); border: 1px solid var(--border);
        border-radius: var(--radius-xl); box-shadow: var(--shadow-xl);
        overflow: hidden; animation: modalFloatIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .u-modal__card--xl { max-width: 900px; }
    .u-modal__card--md { max-width: 500px; }

    @keyframes modalFloatIn {
        from { opacity: 0; transform: scale(0.96) translateY(12px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .u-modal__body { overflow-y: auto; padding: 1.5rem; }
    .u-modal__foot { padding: 1.25rem 1.5rem; background: var(--surface-1); border-top: 1px solid var(--border); }
    .u-modal__head { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--surface-1); }

    /* Section & Form Styling */
    .u-bg-section {
        background-color: var(--surface-1); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem;
    }
    .section-divider {
        display: flex; align-items: center; gap: 0.75rem;
        padding-bottom: 0.75rem; margin-bottom: 1rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase; 
        letter-spacing: 0.05em; color: var(--muted);
    }
    .section-divider i { color: var(--accent); font-size: 1rem; }

    /* Currency Input */
    .has-prefix { position: relative; }
    .currency-prefix {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        color: var(--muted); font-size: 0.875rem; font-weight: 600; pointer-events: none;
    }
    .has-prefix .u-input {
        padding-left: 38px; text-align: right; 
        font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; font-weight: 500;
    }
    .map-container { height: 250px; width: 100%; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); z-index: 1; }
    .float-in { animation: modalFloatIn 0.4s ease forwards; }
</style>

{{-- PAGE HEADER --}}
<div class="u-card u-card--glass u-mb-xl">
    <div class="u-flex u-justify-between u-items-center u-stack-mobile u-gap-md">
        <div>
            <h2 class="u-title u-text-lg">Dokumen Kontrak</h2>
            <p class="u-text-sm u-muted u-mt-xs">Manajemen SPK, PKWT, dan Perjanjian Bersama.</p>
        </div>
        @can('contract.create')
        <button type="button" class="u-btn u-btn--brand u-shadow-lg u-hover-lift" id="btnOpenCreate" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;">
            <i class="fas fa-plus"></i> <span>Buat Dokumen</span>
        </button>
        @endcan
    </div>
</div>

{{-- ERROR ALERT --}}
@if ($errors->any())
    <div class="u-card u-p-md u-mb-lg u-error u-flex u-gap-md u-items-start">
        <div class="u-text-danger u-text-xl"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="u-font-bold u-mb-xs">Gagal Menyimpan Data</div>
            <ul class="u-text-sm u-ml-md" style="list-style-type: disc;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- FILTER --}}
<div class="u-card u-p-md u-mb-lg" style="background: var(--surface-1);">
    <form method="get" class="u-grid-2 u-gap-lg">
        <div>
            <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs u-block">Unit Kerja</label>
            @if ($canSeeAll)
                <div class="u-search">
                    <span class="u-search__icon"><i class="fas fa-building"></i></span>
                    <select name="unit_id" class="u-search__input" onchange="this.form.submit()" style="background: transparent;">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u) <option value="{{ $u->id }}" @selected((string)$currentUnitId === (string)$u->id)>{{ $u->name }}</option> @endforeach
                    </select>
                </div>
            @else
                <div class="u-input u-input--sm u-bg-light u-text-muted u-flex u-items-center u-gap-sm">
                    <i class="fas fa-lock u-text-xs"></i> {{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}
                </div>
                <input type="hidden" name="unit_id" value="{{ $meUnit }}">
            @endif
        </div>
        <div>
            <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs u-block">Status Dokumen</label>
            <div class="u-search">
                <span class="u-search__icon"><i class="fas fa-filter"></i></span>
                <select name="status" class="u-search__input" onchange="this.form.submit()" style="background: transparent;">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $code => $label) <option value="{{ $code }}" @selected($statusFilter == $code)>{{ $label }}</option> @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- DATA TABLE --}}
<div class="dt-wrapper">
    <div class="u-scroll-x">
        <table id="contracts-table" class="u-table u-table-mobile">
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Ticket (Izin Prinsip)</th>
                    <th>Personil</th>
                    <th>Posisi & Unit</th>
                    <th>Periode / Efektif</th>
                    <th>Status</th>
                    <th class="cell-actions" width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $c)
                    <tr class="u-hover-bright">
                        <td>
                            <div class="u-font-mono u-font-bold u-text-sm">{{ $c->contract_no ?: '(Draft)' }}</div>
                            <span class="u-badge u-badge--glass u-mt-xs u-text-xs">{{ $c->contract_type_label ?? $c->contract_type }}</span>
                        </td>
                        <td>
                            @if($c->ticket_number)
                                <span class="u-badge u-badge--info u-text-2xs">{{ $c->ticket_number }}</span>
                            @else
                                <span class="u-text-muted u-text-xs">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="u-flex u-items-center u-gap-sm">
                                <div>{{ substr($c->person_name, 0, 1) }}</div>
                                <div>
                                    <div class="u-font-bold u-text-sm">{{ $c->person_name }}</div>
                                    <div class="u-text-xs u-muted u-mt-xxs">
                                        @if($c->applicant_id) <span class="u-text-accent"><i class="fas fa-user-check u-mr-xxs"></i> Pelamar</span>
                                        @elseif($c->employee_id) <i class="fas fa-id-badge u-mr-xxs"></i> {{ $c->employee_id }}
                                        @else - @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="u-text-sm u-font-medium">{{ $c->position_name ?? '-' }}</div>
                            <div class="u-text-xs u-muted"><i class="fas fa-building u-mr-xxs"></i> {{ $c->unit?->name ?? '-' }}</div>
                        </td>
                        <td>
                            @if($c->contract_type === 'PB_PENGAKHIRAN')
                                <span class="u-text-danger u-font-bold u-text-xs">
                                    <i class="fas fa-stop-circle u-mr-xxs"></i> End: {{ isset($c->remuneration_json['pb_effective_end']) ? \Carbon\Carbon::parse($c->remuneration_json['pb_effective_end'])->format('d M Y') : '-' }}
                                </span>
                            @else
                                <div class="u-text-sm">{{ $c->start_date?->format('d/m/Y') }}</div>
                                <div class="u-text-xs u-muted">s/d {{ $c->end_date?->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $bg = match($c->status) {
                                    'draft' => 'u-badge--warn', 'review' => 'u-badge--primary',
                                    'approved' => 'u-badge--info', 'signed' => 'u-badge--success',
                                    'rejected' => 'u-badge--danger', default => 'u-badge--glass'
                                };
                            @endphp
                            <span class="u-badge {{ $bg }}">{{ $statusOptions[$c->status] ?? $c->status }}</span>
                        </td>
                        <td class="cell-actions">
                            <div class="cell-actions__group">
                                <button type="button" class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-btn-detail" 
                                    data-show-url="{{ route('recruitment.contracts.show', $c) }}" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if ($c->status === 'draft' && auth()->user()->can('contract.update', $c))
                                    <button type="button" class="u-btn u-btn--outline u-btn--icon u-btn--sm js-btn-edit" 
                                        data-show-url="{{ route('recruitment.contracts.show', $c) }}" 
                                        data-update-url="{{ route('recruitment.contracts.update', $c) }}" title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" class="u-btn u-btn--danger u-btn--icon u-btn--sm js-btn-delete"
                                        data-url="{{ route('recruitment.contracts.destroy', $c) }}" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="u-empty"><div class="u-empty__icon"><i class="far fa-folder-open"></i></div>Tidak ada data dokumen ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($contracts instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="u-mt-md">{{ $contracts->links() }}</div>
    @endif
</div>

{{-- ================= MODAL CREATE ================= --}}
@can('contract.create')
<div id="createContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-file-signature"></i></div>
                <div><div class="u-title">Buat Dokumen Baru</div><div class="u-muted u-text-sm">SPK / PKWT / PB</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" action="{{ route('recruitment.contracts.store') }}" class="u-modal__body" id="createContractForm" autocomplete="off">
            @csrf
            {{-- Hidden Fields --}}
            <input type="hidden" name="contract_type" id="createTypeInput" value="{{ old('contract_type') }}">
            <input type="hidden" name="mode" id="createModeInput" value="{{ old('mode') }}">
            <input type="hidden" name="source_contract_id" id="createSourceIdInput" disabled>
            <input type="hidden" name="employee_id" id="createEmployeeIdInput" disabled>
            <input type="hidden" name="person_id" id="createPersonIdInput" disabled>

            {{-- STEP 1: PILIH JENIS --}}
            <div class="u-bg-section">
                <div class="section-divider"><i class="fas fa-layer-group"></i> 1. Jenis Dokumen</div>
                <div class="u-grid-2 u-stack-mobile">
                    <div class="u-form-group">
                        <label>Kategori Dokumen</label>
                        <select id="createFamilySelect" class="u-input" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="SPK" data-mode="new">SPK (Offering Letter)</option>
                            <option value="PKWT" data-mode="">PKWT (Perjanjian Kerja)</option>
                            <option value="PB" data-mode="terminate">PB (Pengakhiran)</option>
                        </select>
                    </div>
                    <div id="createSubtypeWrap" class="u-form-group is-hidden float-in">
                        <label class="u-text-accent u-text-xs u-mb-xxs u-uppercase u-font-bold">Spesifikasi PKWT:</label>
                        <select id="createSubtypeSelect" class="u-input">
                            <option value="">-- Baru / Perpanjangan --</option>
                            <option value="PKWT_BARU" data-mode="new">PKWT Baru</option>
                            <option value="PKWT_PERPANJANGAN" data-mode="extend">PKWT Perpanjangan</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- MAIN FORM CONTAINER (Hidden until type selected) --}}
            <div id="createMainSection" class="is-hidden float-in">
                <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    
                    {{-- LEFT COL: SOURCE --}}
                    <div class="u-space-y-lg">
                        <div class="u-bg-section">
                            <div class="section-divider"><i class="fas fa-database"></i> 2. Sumber Data</div>
                            
                            {{-- Mode NEW --}}
                            <div data-mode-section="new" class="is-hidden u-space-y-md">
                                <div class="u-form-group">
                                    <label>Pilih Pelamar (Status Approved)</label>
                                    <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                        <option value="">-- Cari Pelamar --</option>
                                        @foreach ($applicants as $a)
                                            <option value="{{ $a->id }}" 
                                                data-person-id="{{ $a->person_id ?? '' }}"
                                                data-fullname="{{ $a->full_name }}" 
                                                data-pos="{{ $a->position_applied }}" 
                                                data-unit="{{ $a->unit_name ?? '' }}" 
                                                data-unit-id="{{ $a->unit_id ?? '' }}"
                                                data-ticket="{{ $a->ticket_number ?? '' }}">
                                                {{ $a->full_name }} — {{ $a->position_applied }} [Ticket: {{ $a->ticket_number ?? '-' }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group">
                                    <label>Unit Penempatan</label>
                                    @if ($canSeeAll)
                                        <select name="unit_id" id="createUnitSelectNew" class="u-input"><option value="">-- Pilih Unit --</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                                    @else
                                        <input type="hidden" name="unit_id" value="{{ $meUnit }}" id="createUnitHiddenNew">
                                        <input type="text" class="u-input u-bg-light" value="{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}" readonly>
                                    @endif
                                </div>
                            </div>

                            {{-- Mode EXISTING --}}
                            <div data-mode-section="existing" class="is-hidden u-space-y-md">
                                <div class="u-form-group">
                                    <label id="labelSourceExisting">Pilih Kontrak Dasar</label>
                                    <div class="u-mb-xs">
                                        <select id="filterSourceUnit" class="u-input u-input--sm" style="font-size:0.75rem;"><option value="">Filter Unit (Semua)</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                                    </div>
                                    <select id="createSourceSelect" class="u-input">
                                        <option value="">-- Cari Karyawan (Exp 30 Hari) --</option>
                                        @foreach ($expiringContracts as $c)
                                            <option value="{{ $c->id }}" 
                                                data-unit-id="{{ $c->unit_id }}" 
                                                data-person-id="{{ $c->person_id }}" 
                                                data-employee-id="{{ $c->employee_id }}" 
                                                data-person="{{ $c->person_name }}" 
                                                data-pos="{{ $c->position_name }}" 
                                                data-unit-name="{{ $c->unit_name }}" 
                                                data-start="{{ \Carbon\Carbon::parse($c->start_date)->format('Y-m-d') }}" 
                                                data-end="{{ \Carbon\Carbon::parse($c->end_date)->format('Y-m-d') }}" 
                                                data-end-human="{{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}"
                                                data-nik="{{ $c->employee_id ?? '-' }}"
                                                data-emp-type="{{ $c->employment_type ?? '' }}" 
                                            >
                                                {{ $c->person_name }} — {{ $c->position_name }} (Exp: {{ \Carbon\Carbon::parse($c->end_date)->format('d M Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group" id="unitWrapperForExisting">
                                    <label>Unit Kerja / Penempatan</label>
                                    <select name="unit_id" id="createUnitSelectExisting" class="u-input">
                                        <option value="">-- Pilih Unit --</option>
                                        @foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- DETAILS --}}
                        <div class="u-bg-section">
                            <div class="section-divider"><i class="fas fa-pen-fancy"></i> 3. Detail Posisi</div>
                            <div class="u-grid-2 u-stack-mobile">
                                <div class="u-form-group">
                                    <label>Jabatan</label>
                                    <input type="text" name="position_name" id="createPosName" class="u-input" list="positionList" placeholder="Nama Jabatan">
                                    <datalist id="positionList">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                                </div>
                                <div class="u-form-group">
                                    <label>Hubungan Kerja</label>
                                    <select name="employment_type" id="createEmpType" class="u-input">
                                        @foreach ($employmentTypes as $opt) <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COL: PREVIEW & DATES --}}
                    <div class="u-space-y-lg">
                        <div id="createPersonPreview" class="u-card u-card--glass u-p-lg is-hidden float-in">
                            <div class="u-flex u-items-center u-gap-md u-mb-lg">
                                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-user"></i></div>
                                <div>
                                    <div class="u-font-bold u-text-lg" id="prevName">-</div>
                                    <div class="u-text-sm u-muted u-font-mono" id="prevNik">-</div>
                                    <div class="u-text-xs u-text-info u-mt-xxs" id="prevTicket"></div>
                                </div>
                            </div>
                            <div class="u-grid-2 u-gap-md u-text-sm">
                                <div><span class="u-muted u-text-xs u-uppercase u-font-bold">Posisi</span><div class="u-font-medium u-mt-xxs" id="prevPos">-</div></div>
                                <div><span class="u-muted u-text-xs u-uppercase u-font-bold">Unit</span><div class="u-font-medium u-mt-xxs" id="prevUnit">-</div></div>
                                <div class="u-grid-col-span-2 u-border-t u-pt-md">
                                    <span class="u-muted u-text-xs u-uppercase u-font-bold">Periode Lama</span>
                                    <div class="u-font-medium u-mt-xxs" id="prevDate">-</div>
                                </div>
                            </div>
                        </div>

                        {{-- DATES & OPS (PKWT/SPK) --}}
                        <div id="sectionPkwtSpk" class="is-hidden">
                            <div class="u-bg-section">
                                <div class="section-divider"><i class="far fa-calendar-alt"></i> Durasi & Operasional</div>
                                <div class="u-grid-2 u-stack-mobile u-mb-md">
                                    <div class="u-form-group"><label>Tanggal Mulai</label><input type="date" name="start_date" id="createStartDate" class="u-input"></div>
                                    <div class="u-form-group"><label>Tanggal Selesai</label><input type="date" name="end_date" class="u-input"></div>
                                </div>
                                <div class="u-grid-2 u-stack-mobile">
                                    <div class="u-form-group"><label>Hari Kerja</label><input type="text" name="work_days" class="u-input" value="Senin s/d hari Jumat"></div>
                                    <div class="u-form-group"><label>Jam Kerja</label><input type="text" name="work_hours" class="u-input" value="07.30 - 16.30 WIB"></div>
                                    <div class="u-form-group"><label>Waktu Istirahat</label><input type="text" name="break_hours" class="u-input" value="12.00 - 13.00 WIB"></div>
                                </div>
                                <div class="u-grid-2 u-stack-mobile u-mt-md">
                                    <div class="u-form-group has-prefix"><label>UHPD Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_stay" class="u-input" value="150.000" data-rupiah="true"></div></div>
                                    <div class="u-form-group has-prefix"><label>UHPD Tidak Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_non_stay" class="u-input" value="75.000" data-rupiah="true"></div></div>
                                </div>
                            </div>
                        </div>

                        {{-- PB (TERMINATION) --}}
                        <div id="sectionPb" class="is-hidden u-bg-section" style="border-left: 4px solid #ef4444;">
                            <div class="section-divider u-text-danger"><i class="fas fa-hand-holding-usd"></i> Kompensasi Pengakhiran</div>
                            <div class="u-grid-2 u-stack-mobile">
                                <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" id="createPbEnd" class="u-input"></div>
                                <div class="u-form-group has-prefix">
                                    <label>Nilai Kompensasi</label>
                                    <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true" data-terbilang-target="pb_compensation_amount_words"></div>
                                    <input type="text" name="pb_compensation_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- REMUNERATION --}}
                <div id="sectionRemun" class="u-bg-section u-mt-lg is-hidden">
                    <div class="section-divider u-text-brand"><i class="fas fa-money-check-alt"></i> Rincian Remunerasi</div>
                    <div class="u-grid-2 u-stack-mobile u-gap-lg">
                        <div class="u-space-y-md">
                            <div class="u-form-group has-prefix"><label>Gaji Pokok</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" class="u-input" data-rupiah="true" data-terbilang-target="salary_amount_words"></div><input type="text" name="salary_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                            <div class="u-form-group has-prefix"><label>Uang Makan / Hari</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true" data-terbilang-target="lunch_allowance_words"></div><input type="text" name="lunch_allowance_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                        </div>
                        <div>
                            <label class="u-text-sm u-font-bold u-muted u-uppercase u-mb-sm u-block">Tunjangan Lainnya</label>
                            <div class="u-space-y-md">
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" class="u-input" placeholder="Jabatan" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" class="u-input" placeholder="Komunikasi" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" class="u-input" placeholder="Khusus" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" class="u-input" placeholder="Lainnya" data-rupiah="true"></div>
                            </div>
                            <input type="text" name="other_benefits_desc" class="u-input u-mt-lg" placeholder="Deskripsi Benefit Lain (BPJS, dll)">
                        </div>
                    </div>
                </div>

                <div class="u-border-t u-pt-lg u-mt-lg">
                      <div class="u-form-group"><label>Catatan Tambahan</label><input type="text" name="remarks" class="u-input" placeholder="Opsional..."></div>
                      <div class="u-flex u-gap-lg u-flex-wrap u-mt-lg u-p-md u-bg-section">
                           <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_draw_signature" value="1" checked> Tanda Tangan Digital</label>
                           <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_camera" value="1" checked> Wajib Kamera</label>
                           <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_geolocation" value="1" checked> Wajib Lokasi</label>
                      </div>
                </div>
            </div>

            <div class="u-modal__foot u-flex u-justify-between u-items-center u-stack-mobile">
                <div class="u-text-sm u-muted"><i class="fas fa-info-circle u-mr-xs"></i> Pastikan data valid sebelum submit.</div>
                <div class="u-flex u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                    <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--outline" style="border-radius: 999px;">Simpan Draft</button>
                    <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">Submit Dokumen</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ================= MODAL EDIT ================= --}}
<div id="editContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-title">Edit Dokumen</div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="u-modal__body" id="editContractForm" autocomplete="off">
            @csrf @method('PUT')
            <input type="hidden" name="contract_type" id="editTypeInput">
            <input type="hidden" name="source_contract_id" id="editSourceIdInput">
            <input type="hidden" name="mode" id="editModeInput">
            <input type="hidden" name="employee_id" id="editEmployeeId">
            <input type="hidden" name="person_id" id="editPersonId">
            <input type="hidden" name="applicant_id" id="editApplicantId">

            <div class="u-card u-card--glass u-p-lg u-mb-xl u-grid-2">
                <div>
                    <div class="u-text-xs u-muted u-uppercase u-font-bold u-mb-xs">Personil</div>
                    <div id="editDisplayPerson" class="u-text-xl u-font-bold">-</div>
                </div>
                <div>
                    <div class="u-text-xs u-muted u-uppercase u-font-bold u-mb-xs">Tipe Dokumen</div>
                    <div id="editDisplayType" class="u-badge u-badge--glass u-text-sm">-</div>
                </div>
            </div>

            <div class="u-space-y-lg">
                <div class="u-bg-section">
                    <div class="section-divider"><i class="fas fa-user-tag"></i> Detail Posisi</div>
                    <div class="u-grid-2 u-stack-mobile">
                        <div class="u-form-group">
                            <label>Unit Kerja</label>
                            @if ($canSeeAll)
                                <select name="unit_id" id="editUnitSelect" class="u-input"><option value="">Pilih</option>@foreach($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                            @else
                                <input type="hidden" name="unit_id" id="editUnitIdHidden">
                                <input type="text" id="editUnitDisplay" class="u-input u-bg-light" readonly>
                            @endif
                        </div>
                        <div class="u-form-group"><label>Jabatan</label><input type="text" name="position_name" id="editPos" class="u-input"></div>
                    </div>
                    <div class="u-form-group u-mt-md" id="editNewUnitWrapper" hidden>
                        <label>Unit Kerja Baru (Pindah Unit)</label>
                        <select name="new_unit_id" id="editNewUnitId" class="u-input"><option value="">-- Tidak Berubah --</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                    </div>
                </div>

                {{-- PKWT/SPK EDIT --}}
                <div id="editSectionPkwtSpk" class="is-hidden">
                    <div class="u-bg-section">
                        <div class="section-divider"><i class="far fa-calendar-alt"></i> Durasi & Ops</div>
                        <div class="u-grid-2 u-stack-mobile u-mb-md">
                            <div class="u-form-group"><label>Mulai</label><input type="date" name="start_date" id="editStart" class="u-input"></div>
                            <div class="u-form-group"><label>Selesai</label><input type="date" name="end_date" id="editEnd" class="u-input"></div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile">
                            <div class="u-form-group"><label>Hari Kerja</label><input type="text" name="work_days" id="editWorkDays" class="u-input"></div>
                            <div class="u-form-group"><label>Jam Kerja</label><input type="text" name="work_hours" id="editWorkHours" class="u-input"></div>
                            <div class="u-form-group"><label>Istirahat</label><input type="text" name="break_hours" id="editBreakHours" class="u-input"></div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-mt-md">
                            <div class="u-form-group has-prefix"><label>UHPD Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_stay" id="editTravelStay" class="u-input" data-rupiah="true"></div></div>
                            <div class="u-form-group has-prefix"><label>UHPD Tidak Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_non_stay" id="editTravelNonStay" class="u-input" data-rupiah="true"></div></div>
                        </div>
                    </div>
                    
                    <div class="u-bg-section">
                        <div class="section-divider u-text-brand"><i class="fas fa-money-check-alt"></i> Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg">
                            <div class="u-space-y-md">
                                <div class="u-form-group has-prefix"><label>Gaji Pokok</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" id="editSalary" class="u-input" data-rupiah="true" data-terbilang-target="editSalaryW"></div><input id="editSalaryW" name="salary_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                                <div class="u-form-group has-prefix"><label>Uang Makan</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" id="editLunch" class="u-input" data-rupiah="true" data-terbilang-target="editLunchW"></div><input id="editLunchW" name="lunch_allowance_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                            </div>
                            <div class="u-space-y-md">
                                <label class="u-font-bold u-text-xs u-muted u-uppercase">Tunjangan</label>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" id="editAP" class="u-input" placeholder="Jabatan" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" id="editAC" class="u-input" placeholder="Komunikasi" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" id="editAS" class="u-input" placeholder="Khusus" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" id="editAO" class="u-input" placeholder="Lainnya" data-rupiah="true"></div>
                                <input type="text" name="other_benefits_desc" id="editOB" class="u-input u-mt-lg" placeholder="Benefit Lain">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PB EDIT --}}
                <div id="editSectionPb" class="is-hidden u-bg-section" style="border-left: 4px solid #ef4444;">
                      <div class="section-divider u-text-danger">Kompensasi Pengakhiran</div>
                      <div class="u-grid-2 u-stack-mobile">
                          <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" id="editPbEnd" class="u-input"></div>
                          <div class="u-form-group has-prefix">
                              <label>Kompensasi</label>
                              <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" id="editPbComp" class="u-input" data-rupiah="true" data-terbilang-target="editPbCompW"></div>
                              <input type="text" name="pb_compensation_amount_words" id="editPbCompW" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1">
                          </div>
                      </div>
                </div>

                <div class="u-border-t u-pt-lg">
                    <div class="u-form-group"><label>Catatan</label><input type="text" name="remarks" id="editRemarks" class="u-input"></div>
                    <div class="u-flex u-gap-lg u-flex-wrap u-p-md u-bg-section u-mt-lg">
                        <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_draw_signature" id="editDraw" value="1"> Ttd Digital</label>
                        <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_camera" id="editCam" value="1"> Kamera</label>
                        <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_geolocation" id="editGeo" value="1"> Lokasi</label>
                    </div>
                </div>
            </div>

            <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--outline" style="border-radius: 999px;">Simpan</button>
                <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">Submit</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div id="detailContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-info-circle"></i></div>
                <div class="u-title">Detail Dokumen</div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-space-y-xl">
            <div class="u-grid-2 u-stack-mobile">
                <div class="u-bg-section u-p-lg">
                    <div class="section-divider">Info Dokumen</div>
                    <div class="u-space-y-md">
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Nomor</span><span id="detNo" class="u-font-mono u-font-bold u-text-md">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Tipe</span><span id="detType" class="u-badge u-badge--glass">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Status</span><span id="detStatus" class="u-badge">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Unit</span><span id="detUnit" class="u-font-medium">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs"><span class="u-text-sm u-muted">Ticket (Izin Prinsip)</span><span id="detTicket" class="u-badge u-badge--info u-text-xs">-</span></div>
                    </div>
                </div>
                <div class="u-bg-section u-p-lg">
                      <div class="section-divider">Personil</div>
                      <div class="u-space-y-md">
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Nama</span><span id="detName" class="u-font-bold u-text-xl">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">NIK (Employee ID)</span><span id="detNik" class="u-font-medium">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">NIK (KTP)</span><span id="detNikReal" class="u-font-medium">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Jabatan</span><span id="detPos" class="u-font-medium">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Hubungan</span><span id="detEmpType" class="u-font-medium">-</span></div>
                          <div id="detPeriodRow" class="u-flex u-justify-between u-items-center u-py-xs"><span class="u-text-sm u-muted">Periode</span><span id="detPeriod" class="u-font-medium">-</span></div>
                      </div>
                </div>
            </div>

            <div class="u-card u-card--glass u-p-md" id="detNewUnitBox" hidden>
                  <div class="u-text-sm u-flex u-items-center"><i class="fas fa-exchange-alt u-mr-sm u-text-brand"></i> Pindah ke: <strong id="detNewUnit" class="u-ml-xs u-text-md">-</strong></div>
            </div>

            <div id="detRemunBox" class="u-bg-section u-p-lg is-hidden">
                  <div class="section-divider">Rincian Remunerasi</div>
                  <div class="u-grid-2 u-stack-mobile">
                    <div class="u-space-y-md">
                        <div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-text-sm u-muted">Gaji Pokok</span><strong id="detSalary" class="u-text-md">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-text-sm u-muted">Uang Makan</span><strong id="detLunch" class="u-text-md">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-text-sm u-muted">Hari Kerja</span><strong id="detWorkDays" class="u-text-md">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-text-sm u-muted">Jam Kerja</span><strong id="detWorkHours" class="u-text-md">-</strong></div>
                    </div>
                    <div id="detAllowances" class="u-space-y-sm u-text-sm"></div>
                  </div>
            </div>

            <div id="detPbBox" class="u-bg-section u-p-lg is-hidden" style="border-left: 4px solid #ef4444;">
                  <div class="section-divider u-text-danger">Kompensasi Pengakhiran</div>
                  <div class="u-grid-2">
                      <div><div class="u-text-sm u-muted u-mb-xs">Efektif Berakhir</div><div id="detPbEff" class="u-font-bold u-text-xl">-</div></div>
                      <div><div class="u-text-sm u-muted u-mb-xs">Nilai Kompensasi</div><div id="detPbVal" class="u-font-bold u-text-xl u-text-brand">-</div><div class="u-text-sm u-muted u-mt-xs" id="detPbValW"></div></div>
                  </div>
            </div>
            
            <div id="detMapSection" class="is-hidden">
                <div class="u-bg-section u-p-lg">
                    <div class="section-divider"><i class="fas fa-map-marked-alt u-text-brand"></i> Verifikasi Lokasi & Wajah</div>
                    <div class="u-grid-2 u-stack-mobile">
                        <div id="wrapperMapHead" class="is-hidden">
                            <div class="u-text-xs u-font-bold u-muted u-mb-xs">Lokasi Kepala Unit (Saat Approval)</div>
                            <div id="map-head" class="map-container u-mb-sm"></div>
                            <div class="u-text-xs u-muted u-mb-sm text-right" id="ts-head"></div>
                            <div class="u-text-xs u-font-bold u-muted u-mb-xs">Snapshot Wajah</div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 250px; width: 100%;">
                                <img id="img-head" src="" style="width: 100%; height: 100%; object-fit: contain; display: none;">
                                <span id="no-img-head" class="u-text-xs u-text-muted" style="display:none;">Tidak ada foto</span>
                            </div>
                        </div>
                        <div id="wrapperMapCand" class="is-hidden">
                            <div class="u-text-xs u-font-bold u-muted u-mb-xs">Lokasi Kandidat/Pegawai (Saat Ttd)</div>
                            <div id="map-cand" class="map-container u-mb-sm"></div>
                            <div class="u-text-xs u-muted u-mb-sm text-right" id="ts-cand"></div>
                            <div class="u-text-xs u-font-bold u-muted u-mb-xs">Snapshot Wajah</div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 250px; width: 100%;">
                                <img id="img-cand" src="" style="width: 100%; height: 100%; object-fit: contain; display: none;">
                                <span id="no-img-cand" class="u-text-xs u-text-muted" style="display:none;">Tidak ada foto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="u-bg-section u-p-lg">
                 <div class="section-divider">Approval Progress</div>
                 <div class="u-space-y-md">
                     <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Kepala Unit (Approve & Sign)</span><span id="progKaUnit" class="u-badge u-badge--glass">Waiting</span></div>
                     <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted" id="roleLabel">Kandidat (Sign)</span><span id="progCand" class="u-badge u-badge--glass">Waiting</span></div>
                 </div>
            </div>
        </div>
        <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
            <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Tutup</button>
            <a href="#" id="btnPreviewDoc" target="_blank" class="u-btn u-btn--outline u-text-brand is-hidden" style="border-radius: 999px;"><i class="fas fa-file-pdf u-mr-xs"></i> Dokumen</a>
            <button type="button" id="btnReject" class="u-btn u-btn--danger is-hidden" style="border-radius: 999px;">Reject</button>
            <button type="button" id="btnApprove" class="u-btn u-btn--brand u-shadow-sm is-hidden" style="border-radius: 999px;">Approve</button>
            <button type="button" id="btnSign" class="u-btn u-btn--primary u-shadow-sm is-hidden" style="border-radius: 999px;">Tanda Tangan</button>
        </div>
    </div>
</div>

{{-- MODAL SIGNATURE --}}
<div id="signModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--md">
        <div class="u-modal__head">
            <div class="u-title">Tanda Tangan & Verifikasi</div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="signForm" class="u-modal__body">
            <div id="cameraSection" class="u-mb-md is-hidden">
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Verifikasi Wajah</label>
                <div id="wrapperCamera" class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 360px; position:relative;">
                    <video id="cameraStream" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <img id="snapshotPreview" style="width: 100%; height: 100%; object-fit: cover; display:none;">
                    <div id="cameraPlaceholder" class="u-text-white u-text-sm" style="position:absolute;">Menghubungkan Kamera...</div>
                </div>
                <div class="u-flex u-justify-center u-gap-md u-mt-md">
                    <button type="button" id="btnCapture" class="u-btn u-btn--sm u-btn--primary u-shadow-sm" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;">
                        <i class="fas fa-camera u-mr-xs"></i> Ambil Foto
                    </button>
                    <button type="button" id="btnRetake" class="u-btn u-btn--sm u-btn--outline is-hidden" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;">
                        <i class="fas fa-redo u-mr-xs"></i> Ulangi Foto
                    </button>
                </div>
            </div>
            <div class="u-mb-md">
                <div class="u-flex u-justify-between u-items-end u-mb-xs">
                    <label class="u-text-xs u-font-bold u-muted u-uppercase">Tanda Tangan Digital</label>
                    <button type="button" id="clearSign" class="u-btn u-btn--xs u-btn--ghost u-text-danger u-font-bold" style="border-radius: 999px;">
                        <i class="fas fa-eraser u-mr-xs"></i> Hapus
                    </button>
                </div>
                <div class="u-card u-card--border u-p-xs" style="background: #fff;">
                    <canvas id="signCanvas" style="width: 100%; height: 200px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                </div>
            </div>
            <div class="u-card u-p-sm u-bg-light u-mb-lg u-flex u-items-center u-gap-sm">
                <i class="fas fa-map-marker-alt u-text-muted" id="geoIcon"></i>
                <div class="u-flex-1">
                    <div class="u-text-xs u-font-bold u-muted">Lokasi Saat Ini</div>
                    <div id="geoStatus" class="u-text-sm u-font-medium">Menunggu Izin Lokasi...</div>
                </div>
            </div>
            <input type="hidden" name="signature_image">
            <input type="hidden" name="snapshot_image"> 
            <input type="hidden" name="geo_lat">
            <input type="hidden" name="geo_lng">
            <input type="hidden" name="geo_accuracy"> 
            <div class="u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" id="btnSubmitSign" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;" disabled>Simpan & Tanda Tangan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const doc = document;
    const select = (sel, parent=doc) => parent.querySelector(sel);
    const selectAll = (sel, parent=doc) => [...parent.querySelectorAll(sel)];
    const csrf = select('meta[name="csrf-token"]')?.content;
    const hide = el => { if(el){ el.hidden = true; el.style.display = 'none'; el.classList.add('is-hidden'); } };
    const show = el => { if(el){ el.hidden = false; el.style.display = 'flex'; el.classList.remove('is-hidden'); } };
    const showBlock = el => { if(el){ el.hidden = false; el.style.display = 'block'; el.classList.remove('is-hidden'); } };
    const money = n => (!n || n == 0) ? '-' : n.toString().replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    const safeJSON = (v) => { try{ return JSON.parse(v) || {}; } catch(e){ return v && typeof v === 'object' ? v : {}; } };
    const terbilang = (n) => { const h = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas']; n = Math.abs(parseInt(n)) || 0; if(n<12) return h[n]; if(n<20) return terbilang(n-10)+' belas'; if(n<100) return terbilang(Math.floor(n/10))+' puluh '+terbilang(n%10); if(n<200) return 'seratus '+terbilang(n-100); if(n<1000) return terbilang(Math.floor(n/100))+' ratus '+terbilang(n%100); if(n<2000) return 'seribu '+terbilang(n-1000); if(n<1000000) return terbilang(Math.floor(n/1000))+' ribu '+terbilang(n%1000); return terbilang(Math.floor(n/1000000))+' juta '+terbilang(n%1000000); };
    const addDays = (dateStr, days) => { if (!dateStr) return ''; const d = new Date(dateStr); d.setDate(d.getDate() + parseInt(days)); return d.toISOString().split('T')[0]; };
    let maps = {}; 

    // Helper Functions
    const bindCalc = (root) => { 
        selectAll('input[data-rupiah="true"]', root).forEach(el => { 
            const tgtId = el.dataset.terbilangTarget; 
            el.addEventListener('input', () => { 
                let v = el.value.replace(/\D/g,''); el.value = v ? money(v) : ''; 
                const tgt = tgtId ? (select(`[name="${tgtId}"]`, root) || select(`#${tgtId}`, root)) : null; 
                if(tgt) tgt.value = (v && v != '0') ? (terbilang(v) + ' rupiah').toUpperCase() : ''; 
            }); 
        }); 
    };

    const openModal = (id) => { const m = document.getElementById(id); if(m) { m.hidden = false; m.style.display = 'flex'; document.body.classList.add('modal-open'); } };
    const closeModal = (m) => { if(m) { m.hidden = true; m.style.display = 'none'; document.body.classList.remove('modal-open'); } };
    
    // Global Modal Handlers
    doc.body.addEventListener('click', (e) => { 
        if(e.target.closest('.js-close-modal') || e.target.classList.contains('u-modal__backdrop')) { closeModal(e.target.closest('.u-modal')); } 
        const btn = e.target.closest('[data-modal-open]'); 
        if(btn) { e.preventDefault(); openModal(btn.dataset.modalOpen); } 
    });

    // === CREATE MODAL LOGIC ===
    const initCreateModal = () => {
        const btnCreate = select('#btnOpenCreate');
        const formCreate = select('#createContractForm'); 
        if(!btnCreate || !formCreate) return;

        bindCalc(formCreate);

        const famSel = select('#createFamilySelect'); const subSel = select('#createSubtypeSelect'); 
        const srcSel = select('#createSourceSelect'); const filterUnit = select('#filterSourceUnit'); 
        const appSel = select('#createApplicantSelect');
        const inpType = select('#createTypeInput'); const inpMode = select('#createModeInput');
        
        // Sections
        const secSubtype = select('#createSubtypeWrap');
        const secMain = select('#createMainSection');
        const secPkwtSpk = select('#sectionPkwtSpk');
        const secPb = select('#sectionPb');
        const secRemun = select('#sectionRemun');
        const secNew = select('[data-mode-section="new"]'); 
        const secExist = select('[data-mode-section="existing"]');
        const prevTicket = select('#prevTicket');

        let existingSource = null;

        // Reset UI State on Open
        const resetCreateUI = () => {
            formCreate.reset();
            famSel.value = ""; subSel.value = ""; srcSel.value = ""; appSel.value = "";
            existingSource = null;
            
            // Hide Everything Initially
            hide(secSubtype); hide(secMain); 
            hide(secPkwtSpk); hide(secPb); hide(secRemun);
            hide(secNew); hide(secExist);
            hide(select('#createPersonPreview'));
            if(prevTicket) prevTicket.textContent = '';
        };

        btnCreate.onclick = (e) => { 
            e.preventDefault(); 
            resetCreateUI(); 
            openModal('createContractModal'); 
        };

        // UI Update Logic
        const updateUI = () => {
            const mode = inpMode.value;
            const isNew = (mode === 'new');
            const type = inpType.value;

            // 1. Show Main Section
            showBlock(secMain);

            // 2. Toggle Source Section
            if (isNew) {
                showBlock(secNew); hide(secExist);
                // Enable inputs inside new, disable inside existing
                toggleInputs(secNew, true); toggleInputs(secExist, false);
            } else {
                hide(secNew); showBlock(secExist);
                toggleInputs(secNew, false); toggleInputs(secExist, true);
                select('#labelSourceExisting').textContent = (type === 'PB_PENGAKHIRAN') ? 'Pilih Kontrak yang Diakhiri' : 'Pilih Kontrak Dasar';
            }

            // 3. Toggle Form Sections
            const isPb = (type === 'PB_PENGAKHIRAN');
            if (isPb) {
                hide(secPkwtSpk); hide(secRemun);
                showBlock(secPb);
            } else {
                showBlock(secPkwtSpk); showBlock(secRemun);
                hide(secPb);
            }

            // 4. Apply Auto-fill Rules
            applyAutoFill();
        };

        const applyAutoFill = () => {
            const type = inpType.value;
            if (!existingSource) return;
            const nextDay = addDays(existingSource.end, 1); 

            if (type === 'PB_PENGAKHIRAN') {
                const inpPbEnd = select('#createPbEnd');
                if(inpPbEnd) inpPbEnd.value = nextDay; 
            } else if (type === 'PKWT_PERPANJANGAN') {
                const inpStart = select('#createStartDate');
                if(inpStart) inpStart.value = nextDay; 
            }
            
            // Auto fill fields from source
            const inpPos = select('#createPosName'); 
            const inpEmpType = select('#createEmpType'); 
            const inpUnitExisting = select('#createUnitSelectExisting');

            if(inpPos) inpPos.value = existingSource.pos || '';
            if(inpEmpType && existingSource.empType) inpEmpType.value = existingSource.empType;
            if(inpUnitExisting) inpUnitExisting.value = existingSource.unitId || '';
        };

        const toggleInputs = (container, enable) => {
            if(!container) return;
            container.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !enable);
        };

        // Event Listeners
        famSel.addEventListener('change', () => {
            const val = famSel.value;
            existingSource = null;
            
            // Hide everything first
            hide(secMain); hide(secSubtype);
            
            if (!val) return;

            if (val === 'PKWT') {
                showBlock(secSubtype); 
                // Don't show main section yet, wait for subtype
                inpType.value = ''; inpMode.value = '';
            } else {
                // SPK or PB
                const opt = famSel.options[famSel.selectedIndex];
                inpType.value = (val === 'SPK') ? 'SPK' : ((val === 'PB') ? 'PB_PENGAKHIRAN' : '');
                inpMode.value = opt.dataset.mode || '';
                updateUI();
            }
        });

        subSel.addEventListener('change', () => {
            const val = subSel.value;
            if (!val) { hide(secMain); return; }
            const opt = subSel.options[subSel.selectedIndex];
            inpType.value = val;
            inpMode.value = opt.dataset.mode;
            updateUI();
        });

        // Source: Applicant
        appSel.addEventListener('change', () => {
             const o = appSel.options[appSel.selectedIndex];
             const hidPerson = select('#createPersonIdInput');
             const hidEmp = select('#createEmployeeIdInput');
             
             if (appSel.value) {
                 hidPerson.value = o.dataset.personId || ''; hidPerson.disabled = false; 
                 hidEmp.value = ''; hidEmp.disabled = true;

                 // Preview
                 select('#prevName').textContent = o.dataset.fullname || '-'; 
                 select('#prevPos').textContent = o.dataset.pos || '-'; 
                 select('#prevUnit').textContent = o.dataset.unit || '-'; 
                 select('#prevNik').textContent = '-'; 
                 select('#prevDate').textContent = '-';
                 if(prevTicket) prevTicket.textContent = o.dataset.ticket ? `Ticket: ${o.dataset.ticket}` : '';
                 
                 showBlock(select('#createPersonPreview'));

                 // Auto fill
                 const uSel = select('#createUnitSelectNew'); 
                 if(uSel && o.dataset.unitId) uSel.value = o.dataset.unitId;
                 
                 const inpPos = select('#createPosName');
                 if(inpPos) inpPos.value = o.dataset.pos || '';
             } else { 
                 hide(select('#createPersonPreview')); 
             }
        });

        // Source: Existing Contract
        if(filterUnit && srcSel) {
            filterUnit.addEventListener('change', () => {
                const uId = filterUnit.value;
                [...srcSel.options].forEach(o => { 
                    if(!o.value) return; 
                    if(!uId || o.dataset.unitId == uId) { o.hidden = false; o.disabled = false; } 
                    else { o.hidden = true; o.disabled = true; } 
                });
                srcSel.value = ""; existingSource = null; hide(select('#createPersonPreview'));
            });

            srcSel.addEventListener('change', () => {
                const o = srcSel.options[srcSel.selectedIndex];
                const hidSrc = select('#createSourceIdInput');
                const hidPerson = select('#createPersonIdInput');
                const hidEmp = select('#createEmployeeIdInput');

                if (!srcSel.value) { 
                    existingSource = null; hide(select('#createPersonPreview')); 
                    hidSrc.disabled = true; hidPerson.disabled = true; hidEmp.disabled = true;
                    return; 
                }

                existingSource = { 
                    id: srcSel.value, unitId: o.dataset.unitId, unitName: o.dataset.unitName, 
                    personId: o.dataset.personId, employeeId: o.dataset.employeeId, 
                    person: o.dataset.person, pos: o.dataset.pos, 
                    start: o.dataset.start, end: o.dataset.end, 
                    endHuman: o.dataset.endHuman,
                    nik: o.dataset.nik, empType: o.dataset.empType 
                };

                hidSrc.value = existingSource.id; hidSrc.disabled = false; 
                hidPerson.value = existingSource.personId; hidPerson.disabled = false; 
                hidEmp.value = existingSource.employeeId; hidEmp.disabled = false;
                
                const uExist = select('#createUnitSelectExisting');
                if(uExist) { uExist.value = existingSource.unitId; uExist.disabled = false; }

                select('#prevName').textContent = existingSource.person || '-'; 
                select('#prevPos').textContent = existingSource.pos || '-'; 
                select('#prevUnit').textContent = existingSource.unitName || '-'; 
                select('#prevNik').textContent = existingSource.nik || '-'; 
                select('#prevDate').textContent = 'Exp: ' + existingSource.endHuman;
                if(prevTicket) prevTicket.textContent = ''; // Existing contracts might not have ticket info readily available here unless added to dataset
                showBlock(select('#createPersonPreview'));
                
                applyAutoFill();
            });
        }
        
        // Handle Laravel Validation Errors (Re-open modal & restore state)
        @if($errors->any() && old('contract_type'))
            openModal('createContractModal');
            // Restore hidden inputs
            inpType.value = "{{ old('contract_type') }}";
            inpMode.value = "{{ old('mode') }}";
            
            // Restore UI state
            if(inpType.value) {
                // If PKWT, show subtype wrapper
                if(inpType.value.startsWith('PKWT')) {
                    showBlock(secSubtype);
                    subSel.value = inpType.value;
                }
                // Show main section
                updateUI(); 
            }
        @endif
    };

    // === EDIT MODAL LOGIC ===
    const initEditModal = () => {
        doc.body.addEventListener('click', async (e) => {
            const btnEdit = e.target.closest('.js-btn-edit');
            if(!btnEdit) return;
            e.preventDefault();

            try {
                const res = await fetch(btnEdit.dataset.showUrl).then(r => r.json());
                if(!res.success) throw new Error(res.message);
                
                const d = res.data;
                const m = safeJSON(d.remuneration_json);
                const form = select('#editContractForm');
                bindCalc(form); 
                form.action = btnEdit.dataset.updateUrl;

                // Populate Hidden Fields
                select('#editTypeInput').value = d.contract_type;
                select('#editDisplayPerson').textContent = d.person_name;
                select('#editDisplayType').textContent = d.contract_type_label;
                select('#editPos').value = d.position_name || '';
                select('#editRemarks').value = d.remarks || '';
                
                if(select('#editUnitSelect')) select('#editUnitSelect').value = d.unit_id;
                else if(select('#editUnitIdHidden')) { 
                    select('#editUnitIdHidden').value = d.unit_id; 
                    select('#editUnitDisplay').value = d.unit?.name || ''; 
                }

                // Show/Hide Sections based on Type
                const isPb = (d.contract_type === 'PB_PENGAKHIRAN');
                if(isPb) {
                    hide(select('#editSectionPkwtSpk')); 
                    showBlock(select('#editSectionPb'));
                    select('#editPbEnd').value = m.pb_effective_end || '';
                    const el = select('#editPbComp'); el.value = money(m.pb_compensation_amount); el.dispatchEvent(new Event('input'));
                } else {
                    showBlock(select('#editSectionPkwtSpk')); 
                    hide(select('#editSectionPb'));
                    select('#editStart').value = d.start_date_raw || '';
                    select('#editEnd').value = d.end_date_raw || '';
                    
                    // Fill Remuneration
                    const setM = (sel, val) => { const el = select(sel); if(el){ el.value = money(val); el.dispatchEvent(new Event('input')); } };
                    setM('#editSalary', m.salary_amount);
                    setM('#editLunch', m.lunch_allowance_daily);
                    setM('#editAP', m.allowance_position_amount);
                    setM('#editAC', m.allowance_communication_amount);
                    setM('#editAS', m.allowance_special_amount);
                    setM('#editAO', m.allowance_other_amount);
                    setM('#editTravelStay', m.travel_allowance_stay || 150000);
                    setM('#editTravelNonStay', m.travel_allowance_non_stay || 75000);
                    
                    select('#editOB').value = m.other_benefits_desc || '';
                    select('#editWorkDays').value = m.work_days || 'Senin s/d hari Jumat';
                    select('#editWorkHours').value = m.work_hours || 'Jam 07.30 WIB s/d 16.30 WIB';
                    select('#editBreakHours').value = m.break_hours || 'Jam 12.00 WIB s/d 13.00 WIB';
                }

                // Show New Unit if Extended
                const boxNew = select('#editNewUnitWrapper');
                if(d.contract_type === 'PKWT_PERPANJANGAN') {
                    showBlock(boxNew);
                    if(m.new_unit_id) select('#editNewUnitId').value = m.new_unit_id;
                } else hide(boxNew);

                openModal('editContractModal');
            } catch(err) {
                window.toastErr(err.message);
            }
        });
    };

    // Other Init...
    initCreateModal();
    initEditModal();
    
    const initMap = (divId, lat, lng) => { 
        if (!lat || !lng) return; 
        if (maps[divId]) { maps[divId].remove(); delete maps[divId]; } 
        setTimeout(() => { 
            const el = document.getElementById(divId); if (!el) return; 
            const map = L.map(divId).setView([lat, lng], 15); 
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map); 
            L.marker([lat, lng]).addTo(map); maps[divId] = map; map.invalidateSize(); 
        }, 300); 
    };

    const handleSign = (url, role) => {
        const m = select('#signModal'); const f = select('#signForm'); const cvs = select('#signCanvas'); const vid = select('#cameraStream'); const camSec = select('#cameraSection'); const btnSubmit = select('#btnSubmitSign'); const geoStat = select('#geoStatus'); const btnCap = select('#btnCapture'); const btnRet = select('#btnRetake'); const snapPrev = select('#snapshotPreview'); let captured = false;
        f.reset(); select('[name="signature_image"]').value = ''; select('[name="geo_lat"]').value = ''; select('[name="geo_lng"]').value = ''; select('[name="snapshot_image"]').value = ''; captured = false; snapPrev.style.display = 'none'; vid.style.display = 'block'; hide(btnRet); if(btnCap) { showBlock(btnCap); btnCap.disabled = false; } btnSubmit.disabled = true; openModal('signModal');
        setTimeout(() => { cvs.width = cvs.offsetWidth; cvs.height = 200; const ctx = cvs.getContext('2d'); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#000'; ctx.clearRect(0, 0, cvs.width, cvs.height); }, 150);
        
        const getGeo = () => {
            if (!window.isSecureContext && location.hostname !== 'localhost') { geoStat.innerHTML = '<span class="u-text-danger">Wajib HTTPS!</span>'; return; }
            navigator.geolocation.getCurrentPosition((pos) => {
                select('[name="geo_lat"]').value = pos.coords.latitude; select('[name="geo_lng"]').value = pos.coords.longitude; select('[name="geo_accuracy"]').value = pos.coords.accuracy;
                geoStat.textContent = `Akurasi: ${pos.coords.accuracy.toFixed(0)}m`; geoStat.className = "u-text-sm u-font-medium u-text-success"; select('#geoIcon').className = "fas fa-map-marker-alt u-text-success"; checkReady();
            }, (err) => { geoStat.textContent = "Gagal Deteksi Lokasi"; geoStat.className = "u-text-sm u-font-medium u-text-danger"; }, { enableHighAccuracy: true, timeout: 30000 });
        }; getGeo();

        let streamObj = null;
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && (window.isSecureContext || location.hostname === 'localhost')) {
            showBlock(camSec); select('#cameraPlaceholder').hidden = false;
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } }).then((stream) => { streamObj = stream; vid.srcObject = stream; select('#cameraPlaceholder').hidden = true; }).catch(() => { select('#cameraPlaceholder').textContent = "Izin Kamera Ditolak"; });
        } else { hide(camSec); }

        if(btnCap) btnCap.onclick = () => { if (vid.videoWidth > 0) { const snapCanvas = document.createElement('canvas'); snapCanvas.width = 640; snapCanvas.height = 480; snapCanvas.getContext('2d').drawImage(vid, 0, 0, snapCanvas.width, snapCanvas.height); const dataUrl = snapCanvas.toDataURL('image/jpeg', 0.8); select('[name="snapshot_image"]').value = dataUrl; snapPrev.src = dataUrl; vid.style.display = 'none'; snapPrev.style.display = 'block'; hide(btnCap); showBlock(btnRet); captured = true; checkReady(); } };
        if(btnRet) btnRet.onclick = () => { select('[name="snapshot_image"]').value = ''; snapPrev.style.display = 'none'; vid.style.display = 'block'; showBlock(btnCap); hide(btnRet); captured = false; checkReady(); };

        let isDown = false; let hasSigned = false; const ctx = cvs.getContext('2d'); let rect = cvs.getBoundingClientRect();
        const getXY = (e) => { const clientX = e.touches ? e.touches[0].clientX : e.clientX; const clientY = e.touches ? e.touches[0].clientY : e.clientY; return { x: clientX - rect.left, y: clientY - rect.top }; };
        const drawMove = (e) => { if (!isDown) return; e.preventDefault(); const p = getXY(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
        cvs.onmousedown = (e) => { isDown = true; rect = cvs.getBoundingClientRect(); ctx.beginPath(); ctx.moveTo(getXY(e).x, getXY(e).y); };
        cvs.onmousemove = drawMove; window.addEventListener('mouseup', () => { if(isDown) { isDown = false; hasSigned = true; checkReady(); } });
        cvs.ontouchstart = (e) => { isDown = true; rect = cvs.getBoundingClientRect(); ctx.beginPath(); ctx.moveTo(getXY(e).x, getXY(e).y); };
        cvs.ontouchmove = drawMove; window.addEventListener('touchend', () => { if(isDown) { isDown = false; hasSigned = true; checkReady(); } });
        select('#clearSign').onclick = () => { ctx.clearRect(0, 0, cvs.width, cvs.height); hasSigned = false; btnSubmit.disabled = true; };
        function checkReady() { const locOk = select('[name="geo_lat"]').value !== ""; const camOk = !camSec.classList.contains('is-hidden') ? captured : true; btnSubmit.disabled = !(locOk && hasSigned && camOk); }

        f.onsubmit = async (e) => { e.preventDefault(); select('[name="signature_image"]').value = cvs.toDataURL('image/png'); const fd = new FormData(f); if (streamObj) streamObj.getTracks().forEach(track => track.stop());
            try { const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd }); const j = await r.json(); if (r.ok) { window.toastOk('Berhasil!'); closeModal(m); setTimeout(() => location.reload(), 1000); } else throw new Error(j.message); } catch (err) { window.toastErr(err.message); }
        };
        const cleanup = () => { if (streamObj) streamObj.getTracks().forEach(track => track.stop()); };
        m.querySelectorAll('.js-close-modal').forEach(b => b.addEventListener('click', cleanup));
    };

    doc.body.addEventListener('click', async (e) => {
        const btnDet = e.target.closest('.js-btn-detail');
        if(btnDet) { e.preventDefault(); try { const res = await fetch(btnDet.dataset.showUrl).then(r => r.json()); if(!res.success) throw new Error(res.message); const d = res.data; const m = safeJSON(d.remuneration_json); const isPb = (d.contract_type === 'PB_PENGAKHIRAN'); 
            select('#detNo').textContent = d.contract_no; select('#detType').textContent = d.contract_type_label; select('#detStatus').textContent = d.status; select('#detUnit').textContent = d.unit?.name || '-'; select('#detName').textContent = d.person_name; select('#detNik').textContent = d.ui_employee_id || '-'; select('#detNikReal').textContent = d.ui_nik_ktp || '-'; select('#detPos').textContent = d.position_name || '-'; select('#detEmpType').textContent = d.employment_type || '-'; 
            
            // Populate Ticket Number
            select('#detTicket').textContent = d.ticket_number || '-';

            if(d.progress) { const cMap = {'Waiting':'u-badge--glass', 'Approved':'u-badge--success', 'Signed':'u-badge--success', 'Rejected':'u-badge--danger', 'Pending':'u-badge--warn'}; select('#progKaUnit').textContent = d.progress.ka_unit; select('#progKaUnit').className = `u-badge ${cMap[d.progress.ka_unit]||'u-badge--glass'}`; select('#progCand').textContent = d.progress.candidate; select('#progCand').className = `u-badge ${cMap[d.progress.candidate]||'u-badge--glass'}`; }
            if(d.target_role_label) select('#roleLabel').textContent = `${d.target_role_label} (Sign)`;
            if (isPb) { hide(select('#detRemunBox')); hide(select('#detPeriodRow')); showBlock(select('#detPbBox')); select('#detPbEff').textContent = m.pb_effective_end || '-'; select('#detPbVal').textContent = 'Rp '+money(m.pb_compensation_amount); } 
            else { showBlock(select('#detRemunBox')); showBlock(select('#detPeriodRow')); hide(select('#detPbBox')); select('#detPeriod').textContent = `${d.start_date || '-'} s/d ${d.end_date || '-'}`; select('#detSalary').textContent = 'Rp '+money(m.salary_amount); select('#detLunch').textContent = 'Rp '+money(m.lunch_allowance_daily); select('#detWorkDays').textContent = m.work_days || '-'; select('#detWorkHours').textContent = m.work_hours || '-'; const allws = []; if(m.allowance_position_amount) allws.push(['T. Jabatan', m.allowance_position_amount]); if(m.allowance_communication_amount) allws.push(['T. Komunikasi', m.allowance_communication_amount]); if(m.allowance_special_amount) allws.push(['T. Khusus', m.allowance_special_amount]); if(m.allowance_other_amount) allws.push(['Lainnya', m.allowance_other_amount]); if(m.travel_allowance_stay) allws.push(['UHPD Inap', m.travel_allowance_stay]); if(m.travel_allowance_non_stay) allws.push(['UHPD Non-Inap', m.travel_allowance_non_stay]); select('#detAllowances').innerHTML = allws.map(x => `<div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-muted">${x[0]}</span><strong>Rp ${money(x[1])}</strong></div>`).join(''); }
            const boxNew = select('#detNewUnitBox'); const prevId = (m.prev_unit_id ?? '').toString(); const newId = (m.new_unit_id ?? '').toString(); if(d.contract_type === 'PKWT_PERPANJANGAN' && prevId && newId && prevId !== newId) { showBlock(boxNew); select('#detNewUnit').textContent = m.new_unit_name || '-'; } else { hide(boxNew); }
            const bPrev = select('#btnPreviewDoc'); d.doc_url ? (show(bPrev), bPrev.style.display='inline-flex', bPrev.href=d.doc_url) : hide(bPrev); 
            const bApp = select('#btnApprove'); d.can_approve ? (show(bApp), bApp.onclick=()=>handleSign(d.approve_url, 'Kepala Unit')) : hide(bApp); 
            const bSign = select('#btnSign'); d.can_sign ? (show(bSign), bSign.onclick=()=>handleSign(d.sign_url, 'Kandidat')) : hide(bSign); 
            const bRej = select('#btnReject'); d.can_approve ? (show(bRej), bRej.onclick=()=>{
                Swal.fire({
                    title: 'Tolak Dokumen', input: 'textarea', inputPlaceholder: 'Alasan penolakan...', showCancelButton: true, confirmButtonText: 'Tolak', confirmButtonColor: '#ef4444',
                    preConfirm: (note) => { return fetch(d.reject_url, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify({ rejection_note: note }) })
                    .then(response => { if (!response.ok) throw new Error(response.statusText); return response.json(); }).catch(error => { Swal.showValidationMessage(`Request failed: ${error}`); }); }
                }).then((result) => { if (result.isConfirmed) { window.toastOk('Berhasil Ditolak'); setTimeout(()=>location.reload(),1000); } });
            }) : hide(bRej);
            const geo = d.geolocation || {}; const mapSec = select('#detMapSection'); const wHead = select('#wrapperMapHead'); const wCand = select('#wrapperMapCand');
            if (geo.head || geo.candidate) showBlock(mapSec); else hide(mapSec); select('#img-head').style.display='none'; select('#no-img-head').style.display='none'; select('#img-cand').style.display='none'; select('#no-img-cand').style.display='none';
            if (geo.head) { showBlock(wHead); select('#ts-head').textContent = `Ditandatangani: ${geo.head.ts}`; initMap('map-head', geo.head.lat, geo.head.lng); if(geo.head.image_url) { select('#img-head').src = geo.head.image_url; showBlock(select('#img-head')); } else showBlock(select('#no-img-head')); } else hide(wHead);
            if (geo.candidate) { showBlock(wCand); select('#ts-cand').textContent = `Ditandatangani: ${geo.candidate.ts}`; initMap('map-cand', geo.candidate.lat, geo.candidate.lng); if(geo.candidate.image_url) { select('#img-cand').src = geo.candidate.image_url; showBlock(select('#img-cand')); } else showBlock(select('#no-img-cand')); } else hide(wCand);
            openModal('detailContractModal'); } catch(err) { window.toastErr(err.message); }
        }
        
        const btnDelete = e.target.closest('.js-btn-delete');
        if(btnDelete) {
            e.preventDefault();
            Swal.fire({ title: 'Hapus Dokumen?', text: "Data tidak dapat dikembalikan!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!' }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form'); form.method = 'POST'; form.action = btnDelete.dataset.url;
                    const csrfInput = document.createElement('input'); csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = csrf; form.appendChild(csrfInput);
                    const methodInput = document.createElement('input'); methodInput.type = 'hidden'; methodInput.name = '_method'; methodInput.value = 'DELETE'; form.appendChild(methodInput);
                    document.body.appendChild(form); form.submit();
                }
            });
        }
    });
});
</script>
@endpush