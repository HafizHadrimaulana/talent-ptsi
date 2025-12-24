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

<style>
    /* --- MODAL & LAYOUT --- */
    .u-modal {
        z-index: 1050; display: none; position: fixed; inset: 0;
        background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        padding: 1rem;
        align-items: center; justify-content: center;
    }
    .u-modal:not([hidden]) { display: flex !important; }

    .u-modal__card {
        width: min(100%, 650px); max-height: 90vh; display: flex; flex-direction: column;
        background-color: var(--surface-1);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
        border-radius: var(--radius-lg);
        overflow: hidden;
    }
    .u-modal__card--xl { width: min(100%, 1100px); }

    .u-modal__head { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .u-modal__body { overflow-y: auto; flex: 1; padding: 1.5rem; scrollbar-width: thin; }
    .u-modal__foot { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); background-color: var(--surface-2); }

    /* --- SECTIONS & CARD --- */
    .u-bg-section { background-color: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-md); }
    .section-divider { border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.5rem; font-weight: 700; color: var(--muted); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; }

    /* --- FORMS & INPUTS --- */
    /* Memberikan jarak antar form-group lebih lega */
    .u-form-group { margin-bottom: 1.5rem; } 
    .u-form-group label { font-size: 0.8rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.025em; margin-bottom: 0.5rem; display: block; }
    .currency-prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.875rem; pointer-events: none; }
    .has-prefix .u-input { padding-left: 40px; text-align: right; font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; }
    /* Helper untuk jarak bottom manual jika tidak pakai form-group */
    .u-mb-input-gap { margin-bottom: 1.25rem; }

    /* --- TABLE --- */
    .u-table th, .u-table td { padding: 1rem 1.25rem; }
    /* FIX: Warna teks header diubah menjadi terang agar terlihat di background gelap */
    .u-table thead th { 
        border-bottom: 2px solid var(--border); 
        color: rgba(255, 255, 255, 0.9); /* Warna putih transparan */
        font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; 
    }
    .u-table tbody td { border-bottom: 1px solid var(--border); vertical-align: middle; }

    /* --- UTILS --- */
    .is-hidden { display: none !important; }
    .u-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }

    /* --- RESPONSIVE --- */
    @media (max-width: 768px) {
        .u-grid-2 { grid-template-columns: 1fr !important; gap: 1rem; }
        .cell-actions__group { justify-content: flex-start; }
        .u-modal__card { width: 100%; height: 100%; max-height: 100vh; border-radius: 0; }
        .u-stack-mobile { flex-direction: column; align-items: stretch; gap: 1rem; }
    }
</style>

<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-lg u-stack-mobile">
        <div>
            <h2 class="u-title u-mb-xs">Dokumen Kontrak</h2>
            <p class="u-text-sm u-muted">Manajemen SPK, PKWT, dan Perjanjian Bersama.</p>
        </div>
        @can('contract.create')
        <button type="button" class="u-btn u-btn--brand u-hover-lift u-shadow-sm" id="btnOpenCreate" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;">
            <i class="fas fa-plus u-mr-xs"></i> Buat Dokumen
        </button>
        @endcan
    </div>

    @if (session('success'))
        <div class="u-card u-p-sm u-mb-md u-success"><i class="fas fa-check-circle u-mr-sm"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="u-card u-p-sm u-mb-md u-error"><i class="fas fa-exclamation-triangle u-mr-sm"></i> Periksa kembali inputan Anda.</div>
    @endif

    <div class="u-card u-p-md u-mb-lg u-bg-section">
        <form method="get" class="u-grid-2 u-gap-md" style="grid-template-columns: 1fr 1fr 1.5fr;">
            <div>
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Unit Kerja</label>
                @if ($canSeeAll)
                    <select name="unit_id" class="u-input u-input--sm" onchange="this.form.submit()">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u) <option value="{{ $u->id }}" @selected((string)$currentUnitId === (string)$u->id)>{{ $u->name }}</option> @endforeach
                    </select>
                @else
                    <div class="u-input u-input--sm u-bg-light u-text-muted">{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}</div>
                    <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                @endif
            </div>
            <div>
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Status</label>
                <select name="status" class="u-input u-input--sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $code => $label) <option value="{{ $code }}" @selected($statusFilter == $code)>{{ $label }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Pencarian</label>
                <div class="u-search">
                    <i class="fas fa-search u-search__icon"></i>
                    <input type="search" name="q" class="u-search__input" placeholder="No. Dokumen, Nama, NIK..." value="{{ $searchFilter }}">
                </div>
            </div>
        </form>
    </div>

    <div class="dt-wrapper">
        <div class="u-scroll-x">
            <table id="contracts-table" class="u-table u-table-mobile">
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Personil</th>
                        <th>Posisi & Unit</th>
                        <th>Periode / Efektif</th>
                        <th>Status</th>
                        <th class="cell-actions" width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $c)
                        <tr>
                            <td>
                                <div class="u-font-mono u-font-bold u-text-sm">{{ $c->contract_no ?: '(Draft)' }}</div>
                                <span class="u-badge u-badge--glass u-mt-xs">{{ $c->contract_type_label ?? $c->contract_type }}</span>
                            </td>
                            <td>
                                {{-- FIX: Icon Avatar dihapus, hanya menyisakan nama dan detail --}}
                                <div class="u-flex u-items-center u-gap-sm">
                                    <div>
                                        <div class="u-font-bold u-text-sm">{{ $c->person_name }}</div>
                                        <div class="u-text-xs u-muted u-mt-xxs">
                                            @if($c->applicant_id) <span class="u-text-brand"><i class="fas fa-user-check u-mr-xxs"></i> Pelamar</span>
                                            @elseif($c->employee_id) <i class="fas fa-id-badge u-mr-xxs"></i> {{ $c->employee_id }}
                                            @else - @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="u-text-sm u-font-medium">{{ $c->position_name ?? '-' }}</div>
                                <div class="u-text-xs u-muted">{{ $c->unit?->name ?? '-' }}</div>
                            </td>
                            <td>
                                @if($c->contract_type === 'PB_PENGAKHIRAN')
                                    <span class="u-text-danger u-font-bold u-text-xs">
                                        End: {{ isset($c->remuneration_json['pb_effective_end']) ? \Carbon\Carbon::parse($c->remuneration_json['pb_effective_end'])->format('d M Y') : '-' }}
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
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="u-empty"><div class="u-empty__icon"><i class="far fa-folder-open"></i></div>Tidak ada data dokumen ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contracts instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="u-mt-md">{{ $contracts->links() }}</div>
        @endif
    </div>
</div>

{{-- CREATE MODAL --}}
@can('contract.create')
<div id="createContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-file-contract"></i></div>
                <div><div class="u-title">Buat Dokumen Baru</div><div class="u-muted u-text-sm">SPK / PKWT / PB</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" action="{{ route('recruitment.contracts.store') }}" class="u-modal__body" id="createContractForm">
            @csrf
            <input type="hidden" name="contract_type" id="createTypeInput" value="{{ old('contract_type') }}">
            <input type="hidden" name="mode" id="createModeInput" value="{{ old('mode') }}">
            <input type="hidden" name="source_contract_id" id="createSourceIdInput" value="{{ old('source_contract_id') }}" disabled>
            <input type="hidden" name="employee_id" id="createEmployeeIdInput" value="{{ old('employee_id') }}" disabled>
            <input type="hidden" name="person_id" id="createPersonIdInput" value="{{ old('person_id') }}" disabled>

            <div class="u-card u-p-lg u-mb-lg u-bg-section">
                <div class="section-divider"><i class="fas fa-layer-group u-text-brand"></i> 1. Jenis Dokumen</div>
                <div class="u-grid-2">
                    <div class="u-form-group">
                        <label>Pilih Jenis</label>
                        <select id="createFamilySelect" class="u-input" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="SPK" data-mode="new">SPK (Offering Letter)</option>
                            <option value="PKWT" data-mode="">PKWT (Perjanjian Kerja)</option>
                            <option value="PB" data-mode="terminate">PB (Pengakhiran)</option>
                        </select>
                        <div id="createSubtypeWrap" class="u-mt-md is-hidden">
                            <label class="u-text-brand u-text-xs u-mb-xxs">Spesifikasi PKWT:</label>
                            <select id="createSubtypeSelect" class="u-input">
                                <option value="">-- Baru / Perpanjangan --</option>
                                <option value="PKWT_BARU" data-mode="new">PKWT Baru</option>
                                <option value="PKWT_PERPANJANGAN" data-mode="extend">PKWT Perpanjangan</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="createMainSection" class="is-hidden u-space-y-xl">
                <div class="u-grid-2 u-stack-mobile">
                    {{-- LEFT COL: SOURCE --}}
                    <div>
                        <div class="section-divider"><i class="fas fa-database u-text-brand"></i> 2. Sumber Data</div>
                        
                        {{-- NEW RECRUIT --}}
                        <div data-mode-section="new" class="is-hidden u-space-y-lg">
                            <div class="u-form-group">
                                <label>Pilih Pelamar (Approved)</label>
                                <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                    <option value="">-- Cari Pelamar --</option>
                                    @foreach ($applicants as $a)
                                        <option value="{{ $a->id }}" data-person-id="{{ $a->person_id }}" data-fullname="{{ $a->full_name }}" data-pos="{{ $a->position_applied }}" data-unit="{{ $a->unit_name }}" data-unit-id="{{ $a->unit_id }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
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

                        {{-- EXISTING --}}
                        <div data-mode-section="existing" class="is-hidden u-space-y-lg">
                            <div class="u-form-group">
                                <label id="labelSourceExisting">Pilih Kontrak Dasar</label>
                                <div class="u-flex u-gap-xs u-mb-sm">
                                    <select id="filterSourceUnit" class="u-input u-input--sm" style="width:100%"><option value="">Filter Unit (Semua)</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                                </div>
                                <select id="createSourceSelect" class="u-input">
                                    <option value="">-- Cari Karyawan (Exp 30 Hari) --</option>
                                    @foreach ($expiringContracts as $c)
                                        <option value="{{ $c->id }}" data-unit-id="{{ $c->unit_id }}" data-person-id="{{ $c->person_id }}" data-employee-id="{{ $c->employee_id }}" data-person="{{ $c->person_name }}" data-pos="{{ $c->position_name }}" data-unit-name="{{ $c->unit_name }}" data-start="{{ \Carbon\Carbon::parse($c->start_date)->format('d/m/Y') }}" data-end="{{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}" data-nik="{{ $c->employee_id ?? '-' }}">{{ $c->person_name }} — {{ $c->position_name }} (Exp: {{ \Carbon\Carbon::parse($c->end_date)->format('d M Y') }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="u-form-group">
                                <label>Unit Kerja Saat Ini</label>
                                <input type="text" id="createExistingUnitName" class="u-input u-bg-light" readonly placeholder="-">
                                <input type="hidden" name="unit_id" id="createExistingUnitId"> 
                            </div>
                            <div class="u-form-group" id="newUnitSection" style="display:none;">
                                <label>Unit Kerja Baru (Jika Pindah)</label>
                                <select name="new_unit_id" class="u-input"><option value="">-- Tidak Pindah --</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COL: PREVIEW --}}
                    <div>
                        <div class="section-divider"><i class="fas fa-id-card u-text-brand"></i> Preview Personil</div>
                        <div id="createPersonPreview" class="u-card u-card--glass u-p-lg is-hidden">
                            <div class="u-flex u-items-center u-gap-md u-mb-lg">
                                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-user"></i></div>
                                <div><div class="u-font-bold u-text-lg" id="prevName">-</div><div class="u-text-sm u-muted u-font-mono" id="prevNik">-</div></div>
                            </div>
                            <div class="u-grid-2 u-gap-md u-text-sm">
                                <div><span class="u-muted u-text-xs u-uppercase u-font-bold">Posisi</span><div class="u-font-medium u-mt-xxs" id="prevPos">-</div></div>
                                <div><span class="u-muted u-text-xs u-uppercase u-font-bold">Unit</span><div class="u-font-medium u-mt-xxs" id="prevUnit">-</div></div>
                                <div class="u-grid-col-span-2 u-border-t u-pt-md"><span class="u-muted u-text-xs u-uppercase u-font-bold">Periode Lama</span><div class="u-font-medium u-mt-xxs" id="prevDate">-</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="u-border-t u-pt-xl">
                    <div class="section-divider"><i class="fas fa-file-signature u-text-brand"></i> 3. Detail Kontrak</div>
                    <div class="u-grid-2 u-stack-mobile">
                        <div class="u-form-group">
                            <label>Jabatan</label>
                            <input type="text" name="position_name" id="createPosName" class="u-input" list="positionList" placeholder="Nama Jabatan">
                            <datalist id="positionList">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                        </div>
                        <div class="u-form-group">
                            <label>Hubungan Kerja</label>
                            <select name="employment_type" class="u-input">
                                @foreach ($employmentTypes as $opt) <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option> @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- SPK/PKWT FIELDS --}}
                    <div id="sectionPkwtSpk" class="is-hidden u-mt-lg">
                        <div class="u-grid-2 u-stack-mobile u-mb-lg">
                            <div class="u-form-group"><label>Mulai</label><input type="date" name="start_date" class="u-input"></div>
                            <div class="u-form-group"><label>Selesai</label><input type="date" name="end_date" class="u-input"></div>
                        </div>
                        <div class="u-bg-section u-p-lg">
                            <div class="section-divider u-text-brand">Rincian Remunerasi</div>
                            <div class="u-grid-2 u-stack-mobile">
                                {{-- FIX: Jarak antar field diperlebar (u-space-y-lg dan u-mt-md) --}}
                                <div class="u-space-y-lg">
                                    <div class="u-form-group has-prefix">
                                        <label>Gaji Pokok</label>
                                        <div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" class="u-input" data-rupiah="true" data-terbilang-target="salary_amount_words"></div>
                                        <input type="text" name="salary_amount_words" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1">
                                    </div>
                                    <div class="u-form-group has-prefix">
                                        <label>Uang Makan / Hari</label>
                                        <div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true" data-terbilang-target="lunch_allowance_words"></div>
                                        <input type="text" name="lunch_allowance_words" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div>
                                    <label class="u-mb-sm u-block u-text-xs u-font-bold u-muted u-uppercase">Tunjangan Lainnya</label>
                                    {{-- FIX: Jarak antar input tunjangan diperlebar menggunakan u-mb-input-gap --}}
                                    <div class="has-prefix">
                                        <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" class="u-input" placeholder="Jabatan" data-rupiah="true"></div>
                                        <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" class="u-input" placeholder="Komunikasi" data-rupiah="true"></div>
                                        <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" class="u-input" placeholder="Khusus" data-rupiah="true"></div>
                                        <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" class="u-input" placeholder="Lainnya" data-rupiah="true"></div>
                                    </div>
                                    <input type="text" name="other_benefits_desc" class="u-input u-mt-lg" placeholder="Deskripsi Benefit Lain (BPJS, dll)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PB FIELDS --}}
                    <div id="sectionPb" class="is-hidden u-mt-lg u-bg-section u-p-lg" style="border-left: 4px solid var(--danger);">
                        <div class="section-divider u-text-danger">Kompensasi Pengakhiran</div>
                        <div class="u-grid-2 u-stack-mobile">
                            <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" class="u-input"></div>
                            <div class="u-form-group has-prefix">
                                <label>Nilai Kompensasi</label>
                                {{-- FIX: Jarak antara input dan terbilang diperlebar (u-mt-md) --}}
                                <div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true" data-terbilang-target="pb_compensation_amount_words"></div>
                                <input type="text" name="pb_compensation_amount_words" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="u-border-t u-pt-lg">
                     <div class="u-form-group"><label>Catatan Tambahan</label><input type="text" name="remarks" class="u-input" placeholder="Opsional..."></div>
                     <div class="u-flex u-gap-lg u-flex-wrap u-mt-lg u-p-md u-bg-section">
                          <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_draw_signature" value="1" checked> Tanda Tangan Digital</label>
                          <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_camera" value="1" checked> Wajib Kamera</label>
                          <label class="u-flex u-items-center u-gap-sm u-text-sm u-font-bold u-muted u-pointer"><input type="checkbox" name="requires_geolocation" value="1" checked> Wajib Lokasi</label>
                     </div>
                </div>
            </div>

            <div class="u-modal__foot u-flex u-justify-between u-items-center">
                <div class="u-text-sm u-muted"><i class="fas fa-info-circle u-mr-xs"></i> Pastikan data sudah benar sebelum submit.</div>
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

{{-- EDIT MODAL --}}
<div id="editContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-title">Edit Dokumen</div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="u-modal__body" id="editContractForm">
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

            <div class="u-space-y-xl">
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

                <div class="u-form-group" id="editNewUnitWrapper" hidden>
                    <label>Unit Kerja Baru (Pindah Unit)</label>
                    <select name="new_unit_id" id="editNewUnitId" class="u-input"><option value="">-- Tidak Berubah --</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                </div>

                <div id="editSectionPkwtSpk" class="is-hidden u-space-y-lg">
                    <div class="u-grid-2 u-stack-mobile">
                        <div class="u-form-group"><label>Mulai</label><input type="date" name="start_date" id="editStart" class="u-input"></div>
                        <div class="u-form-group"><label>Selesai</label><input type="date" name="end_date" id="editEnd" class="u-input"></div>
                    </div>
                    <div class="u-bg-section u-p-lg">
                        <div class="section-divider u-text-brand">Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile">
                            {{-- FIX: Jarak antar field diperlebar --}}
                            <div class="u-space-y-lg">
                                <div class="u-form-group has-prefix"><label>Gaji Pokok</label><div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" id="editSalary" class="u-input" data-rupiah="true" data-terbilang-target="editSalaryW"></div><input id="editSalaryW" name="salary_amount_words" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1"></div>
                                <div class="u-form-group has-prefix"><label>Uang Makan</label><div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" id="editLunch" class="u-input" data-rupiah="true" data-terbilang-target="editLunchW"></div><input id="editLunchW" name="lunch_allowance_words" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1"></div>
                            </div>
                            <div class="has-prefix">
                                <label class="u-mb-sm u-block u-text-xs u-font-bold u-muted u-uppercase">Tunjangan</label>
                                {{-- FIX: Jarak antar input tunjangan diperlebar menggunakan u-mb-input-gap --}}
                                <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" id="editAP" class="u-input" placeholder="Jabatan" data-rupiah="true"></div>
                                <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" id="editAC" class="u-input" placeholder="Komunikasi" data-rupiah="true"></div>
                                <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" id="editAS" class="u-input" placeholder="Khusus" data-rupiah="true"></div>
                                <div class="u-mb-input-gap" style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" id="editAO" class="u-input" placeholder="Lainnya" data-rupiah="true"></div>
                                <input type="text" name="other_benefits_desc" id="editOB" class="u-input u-mt-lg" placeholder="Benefit Lain">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="editSectionPb" class="is-hidden u-bg-section u-p-lg" style="border-left: 4px solid var(--danger);">
                     <div class="section-divider u-text-danger">Kompensasi Pengakhiran</div>
                     <div class="u-grid-2 u-stack-mobile">
                         <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" id="editPbEnd" class="u-input"></div>
                         <div class="u-form-group has-prefix">
                             <label>Kompensasi</label>
                             {{-- FIX: Jarak antara input dan terbilang diperlebar (u-mt-md) --}}
                             <div style="position:relative"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" id="editPbComp" class="u-input" data-rupiah="true" data-terbilang-target="editPbCompW"></div>
                             <input type="text" name="pb_compensation_amount_words" id="editPbCompW" class="u-input u-input--sm u-mt-md u-bg-light" readonly tabindex="-1">
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

{{-- DETAIL MODAL --}}
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
                         <div class="u-flex u-justify-between u-items-center u-py-xs"><span class="u-text-sm u-muted">Unit</span><span id="detUnit" class="u-font-medium">-</span></div>
                    </div>
                </div>
                <div class="u-bg-section u-p-lg">
                     <div class="section-divider">Personil</div>
                     <div class="u-space-y-md">
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Nama</span><span id="detName" class="u-font-bold u-text-xl">-</span></div>
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
                    </div>
                    <div id="detAllowances" class="u-space-y-sm u-text-sm"></div>
                  </div>
            </div>

            <div id="detPbBox" class="u-bg-section u-p-lg is-hidden" style="border-left: 4px solid var(--danger);">
                  <div class="section-divider u-text-danger">Kompensasi Pengakhiran</div>
                  <div class="u-grid-2">
                      <div><div class="u-text-sm u-muted u-mb-xs">Efektif Berakhir</div><div id="detPbEff" class="u-font-bold u-text-xl">-</div></div>
                      <div><div class="u-text-sm u-muted u-mb-xs">Nilai Kompensasi</div><div id="detPbVal" class="u-font-bold u-text-xl u-text-brand">-</div><div class="u-text-sm u-muted u-mt-xs" id="detPbValW"></div></div>
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

<div id="rejectModal" class="u-modal" hidden><div class="u-modal__backdrop js-close-modal"></div><div class="u-modal__card u-modal__card--sm"><div class="u-modal__head"><div class="u-title u-text-danger">Tolak Dokumen</div><button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button></div><form id="rejectForm" class="u-modal__body"><textarea name="rejection_note" class="u-input" rows="4" required placeholder="Alasan penolakan..."></textarea><div class="u-flex u-justify-end u-gap-sm u-mt-lg"><button type="submit" class="u-btn u-btn--danger" style="border-radius: 999px;">Konfirmasi Tolak</button></div></form></div></div>
<div id="signModal" class="u-modal" hidden><div class="u-modal__backdrop js-close-modal"></div><div class="u-modal__card u-modal__card--md"><div class="u-modal__head"><div class="u-title">Tanda Tangan Digital</div><button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button></div><form id="signForm" class="u-modal__body"><div class="u-card u-card--border u-p-xs u-mb-md"><canvas id="signCanvas" style="width:100%; height:200px; background:var(--surface-1); touch-action:none; border-radius: var(--radius-sm);"></canvas></div><div class="u-flex u-justify-between u-text-xs u-mb-lg"><button type="button" id="clearSign" class="u-text-brand u-font-bold u-pointer">Hapus / Ulangi</button><span id="geoStatus" class="u-muted">Mendeteksi Lokasi...</span></div><input type="hidden" name="signature_image"><input type="hidden" name="geo_lat"><input type="hidden" name="geo_lng"><div class="u-flex u-justify-end u-gap-sm"><button type="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">Simpan & Tanda Tangan</button></div></form></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const doc = document;
    const select = (sel, parent=doc) => parent.querySelector(sel);
    const selectAll = (sel, parent=doc) => [...parent.querySelectorAll(sel)];
    const csrf = select('meta[name="csrf-token"]')?.content;
    
    const hide = el => { if(el) { el.hidden=true; el.style.display='none'; el.classList.add('is-hidden'); } };
    const show = el => { if(el) { el.hidden=false; el.style.display='flex'; el.classList.remove('is-hidden'); } };
    const showBlock = el => { if(el) { el.hidden=false; el.style.display='block'; el.classList.remove('is-hidden'); } };
    
    const money = n => {
        if (!n || n == 0 || n == '0') return '-';
        return n.toString().replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    };
    
    const safeJSON = (v) => { try{ return JSON.parse(v)||{}; } catch(e){ return v && typeof v === 'object' ? v : {}; } };

    const terbilang = (n) => {
       const h = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
       n = Math.abs(parseInt(n)) || 0;
       if(n<12) return h[n];
       if(n<20) return terbilang(n-10)+' belas';
       if(n<100) return terbilang(Math.floor(n/10))+' puluh '+terbilang(n%10);
       if(n<200) return 'seratus '+terbilang(n-100);
       if(n<1000) return terbilang(Math.floor(n/100))+' ratus '+terbilang(n%100);
       if(n<2000) return 'seribu '+terbilang(n-1000);
       if(n<1000000) return terbilang(Math.floor(n/1000))+' ribu '+terbilang(n%1000);
       return terbilang(Math.floor(n/1000000))+' juta '+terbilang(n%1000000);
    };

    const bindCalc = (root) => {
        selectAll('input[data-rupiah="true"]', root).forEach(el => {
            const tgtId = el.dataset.terbilangTarget;
            const tgt = tgtId ? (select(`[name="${tgtId}"]`, root) || select(`#${tgtId}`, root)) : null;
            const upd = () => {
                let v = el.value.replace(/\D/g,'');
                el.value = v ? money(v) : ''; 
                if(tgt && v && v != '0') tgt.value = (terbilang(v) + ' rupiah').toUpperCase();
                else if(tgt) tgt.value = '';
            };
            el.addEventListener('input', upd);
        });
    };

    const openModal = (id) => {
        const m = document.getElementById(id);
        if(m) {
             m.hidden = false; m.style.display = 'flex'; 
             document.body.classList.add('modal-open');
        }
    };
    const closeModal = (m) => {
        if(m) { m.hidden = true; m.style.display = 'none'; document.body.classList.remove('modal-open'); }
    };

    doc.body.addEventListener('click', (e) => {
        if(e.target.closest('.js-close-modal') || e.target.classList.contains('u-modal__backdrop')) {
            const m = e.target.closest('.u-modal');
            closeModal(m);
        }
        const btnOpen = e.target.closest('[data-modal-open]');
        if(btnOpen) {
            e.preventDefault();
            openModal(btnOpen.dataset.modalOpen);
        }
    });

    const btnCreate = select('#btnOpenCreate');
    if(btnCreate) {
        btnCreate.onclick = (e) => { e.preventDefault(); openModal('createContractModal'); };
        
        const cForm = select('#createContractForm');
        bindCalc(cForm);

        const famSel = select('#createFamilySelect');
        const subSel = select('#createSubtypeSelect');
        const subWrap = select('#createSubtypeWrap');
        const srcSel = select('#createSourceSelect');
        const filterUnit = select('#filterSourceUnit');
        
        const updateCreateUI = () => {
            const mode = select('#createModeInput').value;
            const type = select('#createTypeInput').value;

            if (mode === 'new') {
                showBlock(select('[data-mode-section="new"]'));
                hide(select('[data-mode-section="existing"]'));
            } else {
                showBlock(select('[data-mode-section="existing"]'));
                hide(select('[data-mode-section="new"]'));
                const l = select('#labelSourceExisting');
                if(l) l.textContent = (mode === 'terminate') ? 'Pilih Kontrak yang Diakhiri' : 'Pilih Kontrak Dasar';
                
                if(mode === 'extend') showBlock(select('#newUnitSection'));
                else hide(select('#newUnitSection'));
            }
            
            if (mode === 'terminate') {
                hide(select('#sectionPkwtSpk'));
                showBlock(select('#sectionPb'));
            } else {
                showBlock(select('#sectionPkwtSpk'));
                hide(select('#sectionPb'));
            }
        };

        famSel.addEventListener('change', () => {
            const val = famSel.value;
            const opt = famSel.options[famSel.selectedIndex];
            if (val === 'PKWT') {
                showBlock(subWrap);
                hide(select('#createMainSection'));
            } else {
                hide(subWrap);
                if(val) {
                    showBlock(select('#createMainSection'));
                    select('#createTypeInput').value = (val === 'SPK') ? 'SPK' : 'PB_PENGAKHIRAN';
                    select('#createModeInput').value = opt.dataset.mode;
                    updateCreateUI();
                } else {
                    hide(select('#createMainSection'));
                }
            }
        });

        subSel.addEventListener('change', () => {
            const val = subSel.value;
            const opt = subSel.options[subSel.selectedIndex];
            if (val) {
                showBlock(select('#createMainSection'));
                select('#createTypeInput').value = val;
                select('#createModeInput').value = opt.dataset.mode;
                updateCreateUI();
            } else {
                hide(select('#createMainSection'));
            }
        });

        const appSel = select('#createApplicantSelect');
        appSel?.addEventListener('change', () => {
            const o = appSel.options[appSel.selectedIndex];
            if (appSel.value) {
                select('#createPersonIdInput').value = o.dataset.personId;
                select('#createPersonIdInput').disabled = false;
                select('#createEmployeeIdInput').value = '';
                select('#createEmployeeIdInput').disabled = true;
                
                select('#prevName').textContent = o.dataset.fullname;
                select('#prevPos').textContent = o.dataset.pos;
                select('#prevUnit').textContent = o.dataset.unit;
                select('#prevDate').textContent = '-';
                select('#prevNik').textContent = '-';
                
                showBlock(select('#createPersonPreview'));
                
                const uSel = select('#createUnitSelectNew');
                if(uSel && o.dataset.unitId) uSel.value = o.dataset.unitId;
                const uHid = select('#createUnitHiddenNew');
                if(uHid && o.dataset.unitId) uHid.value = o.dataset.unitId;
            } else hide(select('#createPersonPreview'));
        });

        if(filterUnit) {
            filterUnit.addEventListener('change', () => {
                const uId = filterUnit.value;
                const opts = [...srcSel.options];
                srcSel.value = ""; 
                hide(select('#createPersonPreview'));
                
                opts.forEach(o => {
                    if(!o.value) { o.hidden = false; return; }
                    if(!uId || o.dataset.unitId == uId) { o.hidden = false; o.disabled = false; } 
                    else { o.hidden = true; o.disabled = true; }
                });
            });
        }

        srcSel?.addEventListener('change', () => {
            const o = srcSel.options[srcSel.selectedIndex];
            if (srcSel.value) {
                select('#createSourceIdInput').value = srcSel.value;
                select('#createSourceIdInput').disabled = false;
                select('#createPersonIdInput').value = o.dataset.personId;
                select('#createEmployeeIdInput').value = o.dataset.employeeId;
                select('#createPersonIdInput').disabled = false;
                select('#createEmployeeIdInput').disabled = false;
                
                select('#prevName').textContent = o.dataset.person;
                select('#prevPos').textContent = o.dataset.pos;
                select('#prevUnit').textContent = o.dataset.unitName;
                select('#prevNik').textContent = o.dataset.nik;
                select('#prevDate').textContent = (o.dataset.start || '-') + ' s/d ' + (o.dataset.end || '-');
                
                showBlock(select('#createPersonPreview'));
                
                select('#createExistingUnitId').value = o.dataset.unitId;
                select('#createExistingUnitName').value = o.dataset.unitName;
            } else hide(select('#createPersonPreview'));
        });
    }

    doc.body.addEventListener('click', async (e) => {
        const btnDet = e.target.closest('.js-btn-detail');
        if(btnDet) {
            e.preventDefault();
            try {
                const res = await fetch(btnDet.dataset.showUrl).then(r => r.json());
                if(!res.success) throw new Error(res.message);
                const d = res.data;
                const m = safeJSON(d.remuneration_json);
                const isPb = (d.contract_type === 'PB_PENGAKHIRAN');

                select('#detNo').textContent = d.contract_no;
                select('#detType').textContent = d.contract_type_label;
                select('#detStatus').textContent = d.status;
                select('#detUnit').textContent = d.unit?.name;
                select('#detName').textContent = d.person_name;
                select('#detPos').textContent = d.position_name;
                select('#detEmpType').textContent = d.employment_type;

                if (isPb) {
                    hide(select('#detRemunBox'));
                    hide(select('#detPeriodRow'));
                    showBlock(select('#detPbBox'));
                    select('#detPbEff').textContent = m.pb_effective_end;
                    select('#detPbVal').textContent = 'Rp '+money(m.pb_compensation_amount);
                    select('#detPbValW').textContent = m.pb_compensation_amount_words;
                } else {
                    showBlock(select('#detRemunBox'));
                    showBlock(select('#detPeriodRow'));
                    hide(select('#detPbBox'));
                    select('#detPeriod').textContent = `${d.start_date} s/d ${d.end_date}`;
                    select('#detSalary').textContent = 'Rp '+money(m.salary_amount);
                    select('#detLunch').textContent = 'Rp '+money(m.lunch_allowance_daily);
                    
                    const allws = [];
                    if(m.allowance_position_amount) allws.push(['T. Jabatan', m.allowance_position_amount]);
                    if(m.allowance_communication_amount) allws.push(['T. Komunikasi', m.allowance_communication_amount]);
                    if(m.allowance_special_amount) allws.push(['T. Khusus', m.allowance_special_amount]);
                    if(m.allowance_other_amount) allws.push(['Lainnya', m.allowance_other_amount]);
                    
                    select('#detAllowances').innerHTML = allws.map(x => 
                        `<div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-muted">${x[0]}</span><strong>Rp ${money(x[1])}</strong></div>`
                    ).join('');
                }
                
                const boxNewUnit = select('#detNewUnitBox');
                if(d.contract_type === 'PKWT_PERPANJANGAN' && m.new_unit_id) {
                      showBlock(boxNewUnit);
                      // Here ideally we need unit name, assuming backend sends it or we fetch it. 
                      // For now simple placeholder or logic if available
                      select('#detNewUnit').textContent = "(ID: "+m.new_unit_id+")";
                } else hide(boxNewUnit);

                const bPrev = select('#btnPreviewDoc');
                if(d.doc_url) { show(bPrev); bPrev.style.display = 'inline-flex'; bPrev.href = d.doc_url; } else hide(bPrev);
                
                const bApp = select('#btnApprove');
                if(d.can_approve) { show(bApp); bApp.onclick=()=>signAct(d.approve_url, 'Kepala Unit'); } else hide(bApp);

                const bSign = select('#btnSign');
                if(d.can_sign) { show(bSign); bSign.onclick=()=>signAct(d.sign_url, 'Kandidat'); } else hide(bSign);

                const bRej = select('#btnReject');
                if(d.can_approve) { show(bRej); bRej.onclick=()=>rejectAct(d.reject_url); } else hide(bRej);

                openModal('detailContractModal');

            } catch(err) { alert(err.message); }
        }

        const btnEdit = e.target.closest('.js-btn-edit');
        if(btnEdit) {
            e.preventDefault();
            try {
                const res = await fetch(btnEdit.dataset.showUrl).then(r => r.json());
                if(!res.success) throw new Error(res.message);
                const d = res.data; 
                const m = safeJSON(d.remuneration_json);
                const form = select('#editContractForm');
                bindCalc(form);

                form.action = btnEdit.dataset.updateUrl;
                select('#editTypeInput').value = d.contract_type;
                select('#editDisplayPerson').textContent = d.person_name;
                select('#editDisplayType').textContent = d.contract_type_label;
                
                select('#editPos').value = d.position_name;
                select('#editRemarks').value = d.remarks;
                if(select('#editUnitSelect')) select('#editUnitSelect').value = d.unit_id;
                else if(select('#editUnitIdHidden')) { select('#editUnitIdHidden').value = d.unit_id; select('#editUnitDisplay').value = d.unit?.name; }

                if(d.contract_type === 'PB_PENGAKHIRAN') {
                    hide(select('#editSectionPkwtSpk'));
                    showBlock(select('#editSectionPb'));
                    select('#editPbEnd').value = m.pb_effective_end || '';
                    select('#editPbComp').value = money(m.pb_compensation_amount);
                    select('#editPbComp').dispatchEvent(new Event('input'));
                } else {
                    showBlock(select('#editSectionPkwtSpk'));
                    hide(select('#editSectionPb'));
                    select('#editStart').value = d.start_date_raw || d.start_date; 
                    select('#editEnd').value = d.end_date_raw || d.end_date;
                    
                    const setM = (sel, val) => {
                        const el = select(sel); if(!el) return;
                        el.value = money(val);
                        el.dispatchEvent(new Event('input'));
                    };
                    setM('#editSalary', m.salary_amount);
                    setM('#editLunch', m.lunch_allowance_daily);
                    setM('#editAP', m.allowance_position_amount);
                    setM('#editAC', m.allowance_communication_amount);
                    setM('#editAS', m.allowance_special_amount);
                    setM('#editAO', m.allowance_other_amount);
                    select('#editOB').value = m.other_benefits_desc || '';
                }
                
                if(d.contract_type === 'PKWT_PERPANJANGAN') {
                    showBlock(select('#editNewUnitWrapper'));
                    if(m.new_unit_id) select('#editNewUnitId').value = m.new_unit_id;
                } else {
                    hide(select('#editNewUnitWrapper'));
                }

                openModal('editContractModal');

            } catch(err) { alert(err.message); }
        }
    });

    const signAct = (url, role) => {
        const m = select('#signModal');
        const f = select('#signForm');
        const cvs = select('#signCanvas');
        
        cvs.width = cvs.offsetWidth;
        cvs.height = 200;
        
        const ctx = cvs.getContext('2d');
        ctx.clearRect(0,0,cvs.width,cvs.height);
        
        let isDown=false;
        const getXY=(e)=>{const r=cvs.getBoundingClientRect(); return {x:(e.clientX||e.touches[0].clientX)-r.left, y:(e.clientY||e.touches[0].clientY)-r.top}};
        const down=(e)=>{isDown=true; ctx.beginPath(); const p=getXY(e); ctx.moveTo(p.x,p.y); e.preventDefault();};
        const move=(e)=>{if(!isDown)return; const p=getXY(e); ctx.lineTo(p.x,p.y); ctx.stroke(); e.preventDefault();};
        const up=()=>{isDown=false;};
        
        ['mousedown','touchstart'].forEach(ev=>cvs.addEventListener(ev,down,{passive:false}));
        ['mousemove','touchmove'].forEach(ev=>cvs.addEventListener(ev,move,{passive:false}));
        ['mouseup','touchend'].forEach(ev=>cvs.addEventListener(ev,up));
        select('#clearSign').onclick = () => ctx.clearRect(0,0,cvs.width,cvs.height);

        f.onsubmit = async (e) => {
            e.preventDefault();
            select('[name="signature_image"]', f).value = cvs.toDataURL();
            const fd = new FormData(f);
            try {
                const r = await fetch(url, {method:'POST', headers:{'X-CSRF-TOKEN':csrf, 'Accept':'application/json'}, body:fd});
                const j = await r.json();
                if(r.ok) { window.toastOk('Berhasil', j.message); setTimeout(()=>location.reload(),1000); }
                else throw new Error(j.message);
            } catch(err) { alert(err.message); }
        };
        openModal('signModal');
    };

    const rejectAct = (url) => {
        const m = select('#rejectModal');
        const f = select('#rejectForm');
        f.onsubmit = async (e) => {
            e.preventDefault();
            if(!confirm('Yakin reject?')) return;
            const fd = new FormData(f);
            try {
                const r = await fetch(url, {method:'POST', headers:{'X-CSRF-TOKEN':csrf, 'Accept':'application/json'}, body:fd});
                const j = await r.json();
                if(r.ok) { window.toastOk('Berhasil', 'Direject.'); setTimeout(()=>location.reload(),1000); }
                else throw new Error(j.message);
            } catch(err) { alert(err.message); }
        };
        openModal('rejectModal');
    }
});
</script>
@endpush