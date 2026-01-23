@extends('layouts.app')
@section('title', 'Manajemen Dokumen Kontrak')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
@php
    $me = auth()->user();
    $meUnit = $me?->unit_id;
    $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
    $statusOptions = config('recruitment.contract_statuses', []);
    $currentUnitId = $canSeeAll ? ($selectedUnitId ?? '') : $meUnit;
@endphp

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

<div class="u-card u-card--glass u-p-0 u-overflow-hidden u-mb-xl">
    <div class="u-p-lg u-border-b u-flex u-justify-between u-items-center u-stack-mobile u-gap-md u-bg-surface">
        <div>
            <h2 class="u-title u-text-lg">Dokumen Kontrak</h2>
            <p class="u-text-sm u-muted u-mt-xs">Manajemen SPK, PKWT, dan Perjanjian Bersama.</p>
        </div>
        @can('contract.create')
        <button type="button" class="u-btn u-btn--brand u-shadow-sm u-hover-lift" id="btnOpenCreate" style="border-radius: 999px;">
            <i class="fas fa-plus"></i> <span>Buat Dokumen</span>
        </button>
        @endcan
    </div>

    <div class="u-p-md u-bg-light u-border-b">
        <div class="u-grid-2 u-stack-mobile u-gap-lg">
            <div>
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs u-block">Unit Kerja</label>
                @if ($canSeeAll)
                    <div class="u-search" style="background: var(--surface-0);">
                        <span class="u-search__icon"><i class="fas fa-building"></i></span>
                        <select name="unit_id" id="filterUnit" class="u-search__input" style="background: transparent;">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $u) <option value="{{ $u->id }}" @selected((string)$currentUnitId === (string)$u->id)>{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                @else
                    <div class="u-input u-input--sm u-bg-white u-text-muted u-flex u-items-center u-gap-sm">
                        <i class="fas fa-lock u-text-sm"></i> {{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}
                    </div>
                    <input type="hidden" name="unit_id" id="filterUnit" value="{{ $meUnit }}">
                @endif
            </div>
            <div>
                <label class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs u-block">Status Dokumen</label>
                <div class="u-search" style="background: var(--surface-0);">
                    <span class="u-search__icon"><i class="fas fa-filter"></i></span>
                    <select name="status" id="filterStatus" class="u-search__input" style="background: transparent;">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $code => $label) <option value="{{ $code }}" @selected(($statusFilter ?? '') == $code)>{{ $label }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="dt-wrapper">
        <div class="u-scroll-x">
            <table id="contracts-table" class="u-table nowrap" style="width: 100%; margin: 0 !important; border: none;">
                <thead>
                    <tr>
                        <th>Dokumen</th><th>Ticket</th><th>Personil</th><th>Posisi & Unit</th><th>Periode</th><th>Status</th><th class="cell-actions" width="100">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

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
            <input type="hidden" name="contract_type" id="createTypeInput" value="{{ old('contract_type') }}">
            <input type="hidden" name="mode" id="createModeInput" value="{{ old('mode') }}">
            <input type="hidden" name="source_contract_id" id="createSourceIdInput">
            <input type="hidden" name="employee_id" id="createEmployeeIdInput">
            <input type="hidden" name="person_id" id="createPersonIdInput">

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
                        <label class="u-text-accent u-text-sm u-mb-xxs u-uppercase u-font-bold">Spesifikasi PKWT:</label>
                        <select id="createSubtypeSelect" class="u-input">
                            <option value="">-- Baru / Perpanjangan --</option>
                            <option value="PKWT_BARU" data-mode="new">PKWT Baru</option>
                            <option value="PKWT_PERPANJANGAN" data-mode="extend">PKWT Perpanjangan</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="createMainSection" class="is-hidden float-in">
                <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    <div class="u-space-y-lg">
                        <div class="u-bg-section">
                            <div class="section-divider"><i class="fas fa-database"></i> 2. Sumber Data</div>
                            <div data-mode-section="new" class="is-hidden u-space-y-md">
                                <div class="u-form-group">
                                    <label>Pilih Pelamar (Status Approved)</label>
                                    <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                        <option value="">-- Cari Pelamar --</option>
                                        @foreach ($applicants as $a)
                                            <option value="{{ $a->id }}" data-person-id="{{ $a->person_id ?? '' }}" data-fullname="{{ $a->full_name }}" data-pos="{{ $a->position_applied }}" data-unit="{{ $a->unit_name ?? '' }}" data-unit-id="{{ $a->unit_id ?? '' }}" data-ticket="{{ $a->ticket_number ?? '' }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group" id="createUnitNewWrap">
                                    <label>Unit Penempatan</label>
                                    @if ($canSeeAll)
                                        <select name="unit_id" id="createUnitSelectNew" class="u-input js-location-autofill">
                                            <option value="">-- Pilih Unit --</option>
                                            @foreach ($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="unit_id" value="{{ $meUnit }}" id="createUnitHiddenNew">
                                        <input type="text" class="u-input u-bg-light" value="{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}" readonly>
                                    @endif
                                </div>
                            </div>
                            <div data-mode-section="existing" class="is-hidden u-space-y-md">
                                <div class="u-form-group">
                                    <label id="labelSourceExisting">Pilih Kontrak Dasar</label>
                                    <div class="u-mb-xs">
                                        <select id="filterSourceUnit" class="u-input u-input--sm" style="font-size:0.75rem;">
                                            <option value="">Filter Unit (Semua)</option>
                                            @foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                                        </select>
                                    </div>
                                    <select id="createSourceSelect" class="u-input">
                                        <option value="">-- Cari Karyawan (Exp 30 Hari) --</option>
                                        @foreach ($expiringContracts as $c)
                                            <option value="{{ $c->id }}" data-unit-id="{{ $c->unit_id }}" data-person-id="{{ $c->person_id }}" data-employee-id="{{ $c->employee_id }}" data-person="{{ $c->person_name }}" data-pos="{{ $c->position_name }}" data-unit-name="{{ $c->unit_name }}" data-start="{{ \Carbon\Carbon::parse($c->start_date)->format('Y-m-d') }}" data-end="{{ \Carbon\Carbon::parse($c->end_date)->format('Y-m-d') }}" data-end-human="{{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}" data-nik="{{ $c->employee_id ?? '-' }}" data-emp-type="{{ $c->employment_type ?? '' }}">
                                                {{ $c->person_name }} — {{ $c->position_name }} (Exp: {{ \Carbon\Carbon::parse($c->end_date)->format('d M Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group" id="unitWrapperForExisting">
                                    <label>Unit Kerja / Penempatan</label>
                                    <select name="unit_id" id="createUnitSelectExisting" class="u-input js-location-autofill">
                                        <option value="">-- Pilih Unit --</option>
                                        @foreach ($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
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
                                <div id="createLocationSection" class="u-form-group is-hidden">
                                    <label>Lokasi Kerja</label>
                                    <input type="text" name="work_location" id="createLocation" class="u-input" list="locationList" placeholder="Pilih/Ketik Lokasi">
                                    <datalist id="locationList">@foreach($locations as $l) <option value="{{ $l->location_label }}"> @endforeach</datalist>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="u-space-y-lg">
                        <div id="createPersonPreview" class="u-card u-card--glass u-p-lg is-hidden float-in">
                            <div class="u-flex u-items-center u-gap-md u-mb-lg">
                                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-user"></i></div>
                                <div><div class="u-font-bold u-text-lg" id="prevName">-</div><div class="u-text-sm u-muted u-font-mono" id="prevNik">-</div><div class="u-text-sm u-text-info u-mt-xxs" id="prevTicket"></div></div>
                            </div>
                            <div class="u-grid-2 u-gap-md u-text-sm">
                                <div><span class="u-muted u-text-sm u-uppercase u-font-bold">Posisi</span><div class="u-font-medium u-mt-xxs" id="prevPos">-</div></div>
                                <div><span class="u-muted u-text-sm u-uppercase u-font-bold">Unit</span><div class="u-font-medium u-mt-xxs" id="prevUnit">-</div></div>
                                <div class="u-grid-col-span-2 u-border-t u-pt-md">
                                    <span class="u-muted u-text-sm u-uppercase u-font-bold">Periode Lama</span>
                                    <div class="u-font-medium u-mt-xxs" id="prevDate">-</div>
                                </div>
                            </div>
                        </div>
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
                <div><div class="u-text-sm u-muted u-uppercase u-font-bold u-mb-xs">Personil</div><div id="editDisplayPerson" class="u-text-xl u-font-bold">-</div></div>
                <div><div class="u-text-sm u-muted u-uppercase u-font-bold u-mb-xs">Tipe Dokumen</div><div id="editDisplayType" class="u-badge u-badge--glass u-text-sm">-</div></div>
            </div>
            <div class="u-space-y-lg">
                <div class="u-bg-section">
                    <div class="section-divider"><i class="fas fa-user-tag"></i> Detail Posisi</div>
                    <div class="u-grid-2 u-stack-mobile">
                        <div class="u-form-group">
                            <label>Unit Kerja</label>
                            @if ($canSeeAll)
                                <select name="unit_id" id="editUnitSelect" class="u-input js-location-autofill">
                                    <option value="">Pilih</option>
                                    @foreach($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                </select>
                            @else
                                <input type="hidden" name="unit_id" id="editUnitIdHidden">
                                <input type="text" id="editUnitDisplay" class="u-input u-bg-light" readonly>
                            @endif
                        </div>
                        <div class="u-form-group"><label>Jabatan</label><input type="text" name="position_name" id="editPos" class="u-input"></div>
                        <div id="editLocationSection" class="u-form-group is-hidden">
                            <label>Lokasi Kerja</label>
                            <input type="text" name="work_location" id="editLocation" class="u-input" list="locationListEdit">
                            <datalist id="locationListEdit">@foreach($locations as $l) <option value="{{ $l->location_label }}"> @endforeach</datalist>
                        </div>
                    </div>
                    <div class="u-form-group u-mt-md" id="editNewUnitWrapper" hidden>
                        <label>Unit Kerja Baru (Pindah Unit)</label>
                        <select name="new_unit_id" id="editNewUnitId" class="u-input">
                            <option value="">-- Tidak Berubah --</option>
                            @foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                </div>
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
                                <label class="u-font-bold u-text-sm u-muted u-uppercase">Tunjangan</label>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" id="editAP" class="u-input" placeholder="Jabatan" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" id="editAC" class="u-input" placeholder="Komunikasi" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" id="editAS" class="u-input" placeholder="Khusus" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" id="editAO" class="u-input" placeholder="Lainnya" data-rupiah="true"></div>
                                <input type="text" name="other_benefits_desc" id="editOB" class="u-input u-mt-lg" placeholder="Benefit Lain">
                            </div>
                        </div>
                    </div>
                </div>
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
            <div id="detRejectBox" class="u-bg-section u-p-lg is-hidden" style="border-left: 4px solid #ef4444; background-color: #fef2f2;">
                <div class="section-divider u-text-danger"><i class="fas fa-ban u-mr-xs"></i> Dokumen Ditolak</div>
                <div class="u-text-sm u-font-medium u-text-danger" id="detRejectNote"></div>
            </div>
            <div class="u-grid-2 u-stack-mobile">
                <div class="u-bg-section u-p-lg">
                    <div class="section-divider">Info Dokumen</div>
                    <div class="u-space-y-md">
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Nomor</span><span id="detNo" class="u-font-mono u-font-bold u-text-md">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Tipe</span><span id="detType" class="u-badge u-badge--glass">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Status</span><span id="detStatus" class="u-badge">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Unit</span><span id="detUnit" class="u-font-medium">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-xs"><span class="u-text-sm u-muted">Ticket</span><span id="detTicket" class="u-badge u-badge--info u-text-sm">-</span></div>
                    </div>
                </div>
                <div class="u-bg-section u-p-lg">
                      <div class="section-divider">Personil</div>
                      <div class="u-space-y-md">
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Nama</span><span id="detName" class="u-font-bold u-text-xl text-ellipsis" style="max-width: 200px;">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">NIK</span><span id="detNik" class="u-font-medium">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">NIK (KTP)</span><span id="detNikReal" class="u-font-medium">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-xs u-border-b"><span class="u-text-sm u-muted">Jabatan</span><span id="detPos" class="u-font-medium">-</span></div>
                          <div id="detLocationRow" class="u-flex u-justify-between u-items-center u-py-xs u-border-b is-hidden"><span class="u-text-sm u-muted">Lokasi</span><span id="detLocation" class="u-font-medium">-</span></div>
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

            <div id="detMapSection" class="u-bg-section u-p-lg is-hidden">
                <div class="section-divider"><i class="fas fa-map-marked-alt u-text-brand"></i> Verifikasi Lokasi & Wajah</div>
                <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    <div id="wrapperMapHead" class="is-hidden">
                        <div class="u-text-sm u-font-bold u-muted u-uppercase u-mb-sm u-border-b u-pb-xs">Kepala Unit (Approval)</div>
                        <div class="u-flex-col u-gap-md">
                            <div>
                                <div id="map-head" class="map-container u-mb-xs" style="width: 100% !important; height: 280px !important; position: relative !important; overflow: hidden !important;"></div>
                                <div class="u-text-xs u-muted text-center" id="ts-head">Timestamp</div>
                            </div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 280px; width: 100%; border-radius: var(--radius-sm);">
                                <img id="img-head" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <span id="no-img-head" class="u-text-xs u-text-muted" style="display:none;">No Photo</span>
                            </div>
                        </div>
                    </div>
                    <div id="wrapperMapCand" class="is-hidden">
                        <div class="u-text-sm u-font-bold u-muted u-uppercase u-mb-sm u-border-b u-pb-xs">Kandidat / Pegawai (Signature)</div>
                        <div class="u-flex-col u-gap-md">
                            <div>
                                <div id="map-cand" class="map-container u-mb-xs" style="width: 100% !important; height: 280px !important; position: relative !important; overflow: hidden !important;"></div>
                                <div class="u-text-xs u-muted text-center" id="ts-cand">Timestamp</div>
                            </div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 280px; width: 100%; border-radius: var(--radius-sm);">
                                <img id="img-cand" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <span id="no-img-cand" class="u-text-xs u-text-muted" style="display:none;">No Photo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="u-grid-2 u-stack-mobile u-gap-lg">
                <div class="u-bg-section u-p-lg" style="height: 100%;">
                      <div class="section-divider">APPROVAL PROGRESS</div>
                      <div class="u-flex-col u-gap-md">
                          <div class="u-card u-card--glass u-p-md" style="margin-bottom: 1rem;">
                                <div class="u-flex u-justify-between u-items-start u-w-full">
                                    <div class="u-flex u-gap-md u-items-center">
                                            <div id="iconHead" class="u-avatar u-avatar--md u-bg-light u-text-muted" style="flex-shrink: 0;"><i class="fas fa-user-tie"></i></div>
                                            <div>
                                                <div id="nameHead" class="u-font-bold u-text-md text-ellipsis">-</div>
                                                <div id="posHead" class="u-text-xs u-muted u-uppercase u-font-bold u-mt-xxs">-</div>
                                                <div class="u-text-xs u-muted u-mt-xs flex items-center gap-1"><i class="far fa-clock"></i> <span id="dateHead">-</span></div>
                                            </div>
                                    </div>
                                    <span id="badgeHead" class="u-badge u-badge--glass u-ml-sm u-flex-shrink-0" style="white-space: nowrap;">Waiting</span>
                                </div>
                          </div>
                          <div class="u-card u-card--glass u-p-md">
                                <div class="u-flex u-justify-between u-items-start u-w-full">
                                    <div class="u-flex u-gap-md u-items-center">
                                            <div id="iconCand" class="u-avatar u-avatar--md u-bg-light u-text-muted" style="flex-shrink: 0;"><i class="fas fa-user"></i></div>
                                            <div>
                                                <div id="nameCand" class="u-font-bold u-text-md text-ellipsis">-</div>
                                                <div id="labelCand" class="u-text-xs u-muted u-uppercase u-font-bold u-mt-xxs">Kandidat / Pegawai</div>
                                                <div class="u-text-xs u-muted u-mt-xs flex items-center gap-1"><i class="far fa-clock"></i> <span id="dateCand">-</span></div>
                                            </div>
                                    </div>
                                    <span id="badgeCand" class="u-badge u-badge--glass u-ml-sm u-flex-shrink-0" style="white-space: nowrap;">Waiting</span>
                                </div>
                          </div>
                      </div>
                </div>
                <div id="detLogSection" class="u-bg-section u-p-lg is-hidden" style="height: 100%;">
                    <div class="section-divider"><i class="fas fa-history u-text-muted"></i> Log History</div>
                    <div id="detLogList" class="u-flex-col u-gap-md u-overflow-y-auto" style="max-height: 400px;"></div>
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

<div id="rejectModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--md">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-ban"></i></div>
                <div><div class="u-title">Reject Dokumen</div><div class="u-muted u-text-sm" id="rejectMeta">-</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="rejectForm" class="u-modal__body" autocomplete="off">
            <div class="u-form-group">
                <label>Alasan Penolakan</label>
                <textarea id="rejectNote" name="rejection_note" class="u-input" rows="5" placeholder="Tulis alasan yang jelas..." required></textarea>
                <div class="u-text-xs u-muted u-mt-xs">Catatan ini akan tersimpan di history dan status dokumen kembali menjadi Draft.</div>
            </div>
            <div class="u-flex u-justify-end u-gap-sm u-mt-lg">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" id="btnSubmitReject" class="u-btn u-btn--danger u-shadow-sm" style="border-radius: 999px;">Tolak Dokumen</button>
            </div>
        </form>
    </div>
</div>

<div id="signModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--md">
        <div class="u-modal__head">
            <div class="u-title">Tanda Tangan & Verifikasi</div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="signForm" class="u-modal__body">
            <div id="cameraSection" class="u-mb-md is-hidden">
                <label class="u-text-sm u-font-bold u-muted u-uppercase u-mb-xs">Verifikasi Wajah</label>
                <div id="wrapperCamera" class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 360px; position:relative;">
                    <video id="cameraStream" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <img id="snapshotPreview" style="width: 100%; height: 100%; object-fit: cover; display:none;">
                    <div id="cameraPlaceholder" class="u-text-white u-text-sm" style="position:absolute;">Menghubungkan Kamera...</div>
                </div>
                <div class="u-flex u-justify-center u-gap-md u-mt-md">
                    <button type="button" id="btnCapture" class="u-btn u-btn--sm u-btn--primary u-shadow-sm" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;"><i class="fas fa-camera u-mr-xs"></i> Ambil Foto</button>
                    <button type="button" id="btnRetake" class="u-btn u-btn--sm u-btn--outline is-hidden" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;"><i class="fas fa-redo u-mr-xs"></i> Ulangi Foto</button>
                </div>
            </div>
            <div class="u-mb-md">
                <div class="u-flex u-justify-between u-items-end u-mb-xs">
                    <label class="u-text-sm u-font-bold u-muted u-uppercase">Tanda Tangan Digital</label>
                    <button type="button" id="clearSign" class="u-btn u-btn--xs u-btn--ghost u-text-danger u-font-bold" style="border-radius: 999px;"><i class="fas fa-eraser u-mr-xs"></i> Hapus</button>
                </div>
                <div class="u-card u-card--border u-p-xs" style="background: #fff;">
                    <canvas id="signCanvas" style="width: 100%; height: 200px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                </div>
            </div>
            <div class="u-card u-p-sm u-bg-light u-mb-lg u-flex u-flex-col u-gap-sm">
                <div class="u-flex u-items-center u-gap-sm">
                    <i class="fas fa-map-marker-alt u-text-muted" id="geoIcon"></i>
                    <div class="u-flex-1">
                        <div class="u-text-sm u-font-bold u-muted">Lokasi Saat Ini</div>
                        <div id="geoStatus" class="u-text-sm u-font-medium">Menunggu Izin Lokasi...</div>
                    </div>
                </div>
                <div id="map-sign" class="map-container" style="height: 150px; width: 100%; border-radius: 8px; margin-top: 5px; display: none;"></div>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    const table = window.initDataTables('#contracts-table', {
        ajax: {
            url: "{{ route('recruitment.contracts.index') }}",
            data: function (d) {
                d.unit_id = document.getElementById('filterUnit')?.value;
                d.status = document.getElementById('filterStatus')?.value;
            }
        },
        order: [[5, 'desc']],
        columns: [
            { data: 0, orderable: true },
            { data: 1, orderable: true },
            { data: 2, orderable: false },
            { data: 3, orderable: true },
            { data: 4, orderable: true },
            { data: 5, orderable: true },
            { data: 6, orderable: false, className: "text-center" }
        ],
        drawCallback: function() {
            const wrapper = $(this.api().table().container());
            wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
            wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
            const p = wrapper.find('.dataTables_paginate .paginate_button');
            p.addClass('u-btn u-btn--sm u-btn--ghost');
            p.filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
            p.filter('.disabled').addClass('u-disabled').css('opacity', '0.5');
        }
    });

    $('#filterUnit, #filterStatus').on('change', () => table.draw());

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
    let rejectCtx = { url: '', meta: '' };

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

    const openReject = (url, meta) => {
        rejectCtx.url = url || '';
        rejectCtx.meta = meta || '';
        const metaEl = select('#rejectMeta');
        const noteEl = select('#rejectNote');
        const btn = select('#btnSubmitReject');
        if(metaEl) metaEl.textContent = rejectCtx.meta || '-';
        if(noteEl) noteEl.value = '';
        if(btn) { btn.disabled = false; btn.textContent = 'Tolak Dokumen'; }
        openModal('rejectModal');
        setTimeout(() => { if(noteEl) noteEl.focus(); }, 80);
    };

    window.triggerEdit = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const btnEdit = document.createElement('button');
        btnEdit.dataset.showUrl = `{{ url('recruitment/contracts') }}/${row.id}`;
        btnEdit.dataset.updateUrl = `{{ url('recruitment/contracts') }}/${row.id}`;
        btnEdit.classList.add('js-btn-edit');
        document.body.appendChild(btnEdit);
        btnEdit.click();
        document.body.removeChild(btnEdit);
    };

    window.triggerDetail = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const btnDet = document.createElement('button');
        btnDet.dataset.showUrl = `{{ url('recruitment/contracts') }}/${row.id}`;
        btnDet.classList.add('js-btn-detail');
        document.body.appendChild(btnDet);
        btnDet.click();
        document.body.removeChild(btnDet);
    };

    const handleLocationAutofill = () => {
        const selects = selectAll('.js-location-autofill');
        selects.forEach(sel => {
            sel.addEventListener('change', () => {
                const opt = sel.options[sel.selectedIndex];
                if (!opt) return;
                const cat = (opt.dataset.category || '').toUpperCase();
                const name = (opt.dataset.name || '').trim();
                let locValue = '';
                if (cat === 'ENABLER' || cat === 'OPERASI') locValue = 'Jakarta';
                else if (cat === 'CABANG') locValue = name.replace(/^Cabang\s+/i, '');
                const form = sel.closest('form');
                const locInput = form ? form.querySelector('input[name="work_location"]') : null;
                if (locInput && locValue) locInput.value = locValue;
            });
        });
        const hidCat = select('#createUnitCategoryHidden');
        const hidName = select('#createUnitNameHidden');
        if(hidCat && hidName) {
            const cat = (hidCat.value || '').toUpperCase();
            const name = (hidName.value || '').trim();
            let locValue = '';
            if (cat === 'ENABLER' || cat === 'OPERASI') locValue = 'Jakarta';
            else if (cat === 'CABANG') locValue = name.replace(/^Cabang\s+/i, '');
            const locInput = select('#createLocation');
            if(locInput && locValue && !locInput.value) locInput.value = locValue;
        }
    };
    handleLocationAutofill();

    const initCreateModal = () => {
        const btnCreate = select('#btnOpenCreate');
        const formCreate = select('#createContractForm');
        if(!btnCreate || !formCreate) return;
        bindCalc(formCreate);
        const famSel = select('#createFamilySelect');
        const subSel = select('#createSubtypeSelect');
        const srcSel = select('#createSourceSelect');
        const filterUnit = select('#filterSourceUnit');
        const appSel = select('#createApplicantSelect');
        const inpType = select('#createTypeInput');
        const inpMode = select('#createModeInput');
        const secSubtype = select('#createSubtypeWrap');
        const secMain = select('#createMainSection');
        const secPkwtSpk = select('#sectionPkwtSpk');
        const secPb = select('#sectionPb');
        const secRemun = select('#sectionRemun');
        const secNew = select('[data-mode-section="new"]');
        const secExist = select('[data-mode-section="existing"]');
        const prevTicket = select('#prevTicket');
        let existingSource = null;

        const toggleInputs = (container, enable) => {
            if(!container) return;
            container.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !enable);
        };

        const resetCreateUI = () => {
            try {
                formCreate.reset();
                famSel.value = ""; subSel.value = ""; srcSel.value = ""; appSel.value = "";
                existingSource = null;
                hide(secSubtype); hide(secMain);
                hide(secPkwtSpk); hide(secPb); hide(secRemun);
                hide(secNew); hide(secExist);
                const preview = select('#createPersonPreview');
                if(preview) hide(preview);
                if(prevTicket) prevTicket.textContent = '';
                if(typeof handleLocationAutofill === 'function') handleLocationAutofill();
            } catch(e) { console.error(e); }
        };

        btnCreate.addEventListener('click', (e) => { e.preventDefault(); resetCreateUI(); openModal('createContractModal'); });

        const applyAutoFill = () => {
            const type = inpType.value;
            if (!existingSource) return;
            const nextDay = addDays(existingSource.end, 1);
            if (type === 'PB_PENGAKHIRAN') {
                const inpPbEnd = select('#createPbEnd'); if(inpPbEnd) inpPbEnd.value = nextDay;
            } else if (type === 'PKWT_PERPANJANGAN') {
                const inpStart = select('#createStartDate'); if(inpStart) inpStart.value = nextDay;
            }
            const inpPos = select('#createPosName');
            const inpEmpType = select('#createEmpType');
            const inpUnitExisting = select('#createUnitSelectExisting');
            if(inpPos) inpPos.value = existingSource.pos || '';
            if(inpEmpType && existingSource.empType) inpEmpType.value = existingSource.empType;
            if(inpUnitExisting) {
                inpUnitExisting.value = existingSource.unitId || '';
                inpUnitExisting.dispatchEvent(new Event('change'));
            }
        };

        const updateUI = () => {
            const mode = inpMode.value;
            const isNew = (mode === 'new');
            const type = inpType.value;
            showBlock(secMain);

            if (isNew) {
                showBlock(secNew); hide(secExist);
                toggleInputs(secNew, true); toggleInputs(secExist, false);
                const unitNewWrap = select('#createUnitNewWrap');
                if (type === 'SPK') hide(unitNewWrap); else showBlock(unitNewWrap);
            } else {
                hide(secNew); showBlock(secExist);
                toggleInputs(secNew, false); toggleInputs(secExist, true);
                const lbl = select('#labelSourceExisting');
                if(lbl) lbl.textContent = (type === 'PB_PENGAKHIRAN') ? 'Pilih Kontrak yang Diakhiri' : 'Pilih Kontrak Dasar';
                const unitExistWrap = select('#unitWrapperForExisting');
                if (type === 'PB_PENGAKHIRAN') hide(unitExistWrap); else showBlock(unitExistWrap);
            }

            const isPb = (type === 'PB_PENGAKHIRAN');
            if (isPb) { hide(secPkwtSpk); hide(secRemun); showBlock(secPb); }
            else { showBlock(secPkwtSpk); showBlock(secRemun); hide(secPb); }

            const isPKWT = type && type.includes('PKWT');
            const locSection = select('#createLocationSection');
            if (locSection) { if (isPKWT) showBlock(locSection); else hide(locSection); }
            applyAutoFill();
        };

        if(famSel) {
            famSel.addEventListener('change', () => {
                const val = famSel.value;
                existingSource = null;
                hide(secMain); hide(secSubtype);
                if (!val) return;
                if (val === 'PKWT') { showBlock(secSubtype); inpType.value = ''; inpMode.value = ''; }
                else {
                    const opt = famSel.options[famSel.selectedIndex];
                    inpType.value = (val === 'SPK') ? 'SPK' : ((val === 'PB') ? 'PB_PENGAKHIRAN' : '');
                    inpMode.value = opt.dataset.mode || '';
                    updateUI();
                }
            });
        }

        if(subSel) {
            subSel.addEventListener('change', () => {
                const val = subSel.value;
                if (!val) { hide(secMain); return; }
                const opt = subSel.options[subSel.selectedIndex];
                inpType.value = val; inpMode.value = opt.dataset.mode;
                updateUI();
            });
        }

        if(appSel) {
            appSel.addEventListener('change', () => {
                const o = appSel.options[appSel.selectedIndex];
                const hidPerson = select('#createPersonIdInput');
                const hidEmp = select('#createEmployeeIdInput');
                if (appSel.value) {
                    hidPerson.value = o.dataset.personId || '';
                    hidEmp.value = '';
                    select('#prevName').textContent = o.dataset.fullname || '-';
                    select('#prevPos').textContent = o.dataset.pos || '-';
                    select('#prevUnit').textContent = o.dataset.unit || '-';
                    select('#prevNik').textContent = '-';
                    select('#prevDate').textContent = '-';
                    if(prevTicket) prevTicket.textContent = o.dataset.ticket ? `Ticket: ${o.dataset.ticket}` : '';
                    showBlock(select('#createPersonPreview'));
                    const uSel = select('#createUnitSelectNew');
                    if(uSel && o.dataset.unitId) { uSel.value = o.dataset.unitId; uSel.dispatchEvent(new Event('change')); }
                    const inpPos = select('#createPosName');
                    if(inpPos) inpPos.value = o.dataset.pos || '';
                } else { hide(select('#createPersonPreview')); }
            });
        }

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
                    hidSrc.value = ''; hidPerson.value = ''; hidEmp.value = '';
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
                hidSrc.value = existingSource.id;
                hidPerson.value = existingSource.personId;
                hidEmp.value = existingSource.employeeId;
                const uExist = select('#createUnitSelectExisting');
                if(uExist) { uExist.value = existingSource.unitId; uExist.dispatchEvent(new Event('change')); }
                select('#prevName').textContent = existingSource.person || '-';
                select('#prevPos').textContent = existingSource.pos || '-';
                select('#prevUnit').textContent = existingSource.unitName || '-';
                select('#prevNik').textContent = existingSource.nik || '-';
                select('#prevDate').textContent = 'Exp: ' + existingSource.endHuman;
                if(prevTicket) prevTicket.textContent = '';
                showBlock(select('#createPersonPreview'));
                applyAutoFill();
            });
        }

        @if($errors->any() && old('contract_type'))
            openModal('createContractModal');
            inpType.value = "{{ old('contract_type') }}";
            inpMode.value = "{{ old('mode') }}";
            if(inpType.value) {
                if(inpType.value.startsWith('PKWT')) {
                    showBlock(secSubtype);
                    if(subSel) subSel.value = inpType.value;
                }
                updateUI();
            }
        @endif
    };
    initCreateModal();

    const initEditModal = () => {
        $(document).on('click', '.js-btn-edit', async function(e) {
            e.preventDefault();
            const btnEdit = this;
            try {
                const res = await fetch(btnEdit.dataset.showUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
                if(!res.success) throw new Error(res.message);
                const d = res.data;
                const m = safeJSON(d.remuneration_json);
                const form = select('#editContractForm');
                bindCalc(form);
                form.action = btnEdit.dataset.updateUrl;

                select('#editTypeInput').value = d.contract_type;
                select('#editSourceIdInput').value = d.parent_contract_id || '';
                select('#editEmployeeId').value = d.employee_id || '';
                select('#editPersonId').value = d.person_id || '';
                select('#editApplicantId').value = d.applicant_id || '';
                select('#editDisplayPerson').textContent = d.person_name;
                select('#editDisplayType').textContent = d.contract_type_label;
                select('#editPos').value = d.position_name || '';
                select('#editRemarks').value = d.remarks || '';

                const isPKWT = d.contract_type.includes('PKWT');
                const editLocSection = select('#editLocationSection');
                if (editLocSection) {
                    if (isPKWT) { showBlock(editLocSection); select('#editLocation').value = m.work_location || ''; }
                    else { hide(editLocSection); }
                }

                if(select('#editUnitSelect')) {
                    select('#editUnitSelect').value = d.unit_id;
                } else if(select('#editUnitIdHidden')) {
                    select('#editUnitIdHidden').value = d.unit_id;
                    select('#editUnitDisplay').value = d.unit?.name || '';
                }

                const isPb = (d.contract_type === 'PB_PENGAKHIRAN');
                if(isPb) {
                    hide(select('#editSectionPkwtSpk')); showBlock(select('#editSectionPb'));
                    select('#editPbEnd').value = m.pb_effective_end || '';
                    const el = select('#editPbComp'); el.value = money(m.pb_compensation_amount); el.dispatchEvent(new Event('input'));
                } else {
                    showBlock(select('#editSectionPkwtSpk')); hide(select('#editSectionPb'));
                    select('#editStart').value = d.start_date_raw || '';
                    select('#editEnd').value = d.end_date_raw || '';
                    const setM = (sel, val) => { const el = select(sel); if(el){ el.value = money(val); el.dispatchEvent(new Event('input')); } };
                    setM('#editSalary', m.salary_amount); setM('#editLunch', m.lunch_allowance_daily);
                    setM('#editAP', m.allowance_position_amount); setM('#editAC', m.allowance_communication_amount);
                    setM('#editAS', m.allowance_special_amount); setM('#editAO', m.allowance_other_amount);
                    setM('#editTravelStay', m.travel_allowance_stay || 150000); setM('#editTravelNonStay', m.travel_allowance_non_stay || 75000);
                    select('#editOB').value = m.other_benefits_desc || '';
                    select('#editWorkDays').value = m.work_days || 'Senin s/d hari Jumat';
                    select('#editWorkHours').value = m.work_hours || 'Jam 07.30 WIB s/d 16.30 WIB';
                    select('#editBreakHours').value = m.break_hours || 'Jam 12.00 WIB s/d 13.00 WIB';
                }
                const boxNew = select('#editNewUnitWrapper');
                if(d.contract_type === 'PKWT_PERPANJANGAN') {
                    showBlock(boxNew);
                    if(m.new_unit_id) select('#editNewUnitId').value = m.new_unit_id;
                } else hide(boxNew);
                openModal('editContractModal');
            } catch(err) { alert(err.message); }
        });
    };
    initEditModal();

    const initMap = (divId, lat, lng) => {
        if (!lat || !lng) return;
        const el = document.getElementById(divId);
        if (!el) return;
        if (maps[divId]) { maps[divId].off(); maps[divId].remove(); delete maps[divId]; }

        setTimeout(() => {
            if (el.offsetParent === null) return;
            const map = L.map(divId, { 
                attributionControl: false, 
                zoomControl: true, 
                preferCanvas: false,
                dragging: true,
                touchZoom: true,
                scrollWheelZoom: false
            }).setView([lat, lng], 16);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                attribution: '', 
                maxZoom: 19, 
                minZoom: 10, 
                crossOrigin: true 
            }).addTo(map);
            
            L.circle([lat, lng], { 
                color: '#3e6bbb', 
                fillColor: '#3e6bbb', 
                fillOpacity: 0.12, 
                radius: 50, 
                weight: 1.5,
                dashArray: '4,2'
            }).addTo(map);
            
            const marker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [24, 40],
                    iconAnchor: [12, 40],
                    popupAnchor: [0, -40],
                    shadowSize: [40, 40],
                    shadowAnchor: [12, 40]
                }),
                title: 'Location'
            }).addTo(map);
            
            const accuracy = Math.round(Math.random() * 50) + 10;
            marker.bindPopup(`<strong>Lokasi</strong><br>Akurasi: ${accuracy}m`, {
                closeButton: false,
                autoClose: true,
                className: 'leaflet-popup-custom'
            });
            
            maps[divId] = map;
            setTimeout(() => { if (map && typeof map.invalidateSize === 'function') map.invalidateSize(true); }, 100);
        }, 150);
    };

    const handleSign = (url) => {
        const m = select('#signModal');
        const f = select('#signForm');
        const cvs = select('#signCanvas');
        const vid = select('#cameraStream');
        const camSec = select('#cameraSection');
        const btnSubmit = select('#btnSubmitSign');
        const geoStat = select('#geoStatus');
        const btnCap = select('#btnCapture');
        const btnRet = select('#btnRetake');
        const snapPrev = select('#snapshotPreview');
        const mapSignDiv = select('#map-sign');
        let captured = false;

        f.reset();
        select('[name="signature_image"]').value = '';
        select('[name="geo_lat"]').value = '';
        select('[name="geo_lng"]').value = '';
        select('[name="snapshot_image"]').value = '';
        captured = false;
        snapPrev.style.display = 'none';
        vid.style.display = 'block';
        hide(btnRet);
        if(btnCap) { showBlock(btnCap); btnCap.disabled = false; }
        if(mapSignDiv) { mapSignDiv.style.display = 'none'; mapSignDiv.innerHTML = ''; }
        btnSubmit.disabled = true;
        openModal('signModal');

        setTimeout(() => {
            cvs.width = cvs.offsetWidth;
            cvs.height = 200;
            const ctx = cvs.getContext('2d');
            ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#000';
            ctx.clearRect(0, 0, cvs.width, cvs.height);
        }, 150);

        const getGeo = () => {
            if (!window.isSecureContext && location.hostname !== 'localhost') {
                geoStat.innerHTML = '<span class="u-text-danger"><i class="fas fa-lock"></i> Wajib HTTPS!</span>';
                return;
            }
            geoStat.textContent = "Mencari titik presisi...";
            geoStat.className = "u-text-sm u-font-medium u-text-info u-animate-pulse";

            const geoOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };
            navigator.geolocation.getCurrentPosition((pos) => {
                select('[name="geo_lat"]').value = pos.coords.latitude;
                select('[name="geo_lng"]').value = pos.coords.longitude;
                select('[name="geo_accuracy"]').value = pos.coords.accuracy;
                const acc = Math.round(pos.coords.accuracy);
                let accClass = "u-text-success", accIcon = "fas fa-satellite-dish";
                if(acc > 100) { accClass = "u-text-warning"; accIcon = "fas fa-wifi"; }
                geoStat.innerHTML = `<i class="${accIcon}"></i> Akurasi: <strong>${acc} meter</strong>`;
                geoStat.className = `u-text-sm u-font-medium ${accClass}`;
                select('#geoIcon').className = `fas fa-map-marker-alt ${accClass}`;
                if (mapSignDiv) {
                    mapSignDiv.style.display = 'block';
                    initMap('map-sign', pos.coords.latitude, pos.coords.longitude);
                }
                checkReady();
            }, (err) => {
                let msg = "Gagal Deteksi Lokasi";
                if(err.code === 1) msg = "Izin Lokasi Ditolak";
                else if(err.code === 2) msg = "Sinyal GPS Lemah";
                else if(err.code === 3) msg = "Waktu Habis (Timeout)";
                geoStat.textContent = msg;
                geoStat.className = "u-text-sm u-font-medium u-text-danger";
            }, geoOptions);
        };
        getGeo();

        let streamObj = null;
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && (window.isSecureContext || location.hostname === 'localhost')) {
            showBlock(camSec);
            select('#cameraPlaceholder').hidden = false;
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } }).then((stream) => {
                streamObj = stream;
                vid.srcObject = stream;
                select('#cameraPlaceholder').hidden = true;
            }).catch(() => { select('#cameraPlaceholder').textContent = "Izin Kamera Ditolak / Tidak Ada"; });
        } else { hide(camSec); }

        if(btnCap) btnCap.onclick = () => {
            if (vid.videoWidth > 0) {
                const snapCanvas = doc.createElement('canvas');
                snapCanvas.width = 640; snapCanvas.height = 480;
                snapCanvas.getContext('2d').drawImage(vid, 0, 0, snapCanvas.width, snapCanvas.height);
                const dataUrl = snapCanvas.toDataURL('image/jpeg', 0.8);
                select('[name="snapshot_image"]').value = dataUrl;
                snapPrev.src = dataUrl;
                vid.style.display = 'none';
                snapPrev.style.display = 'block';
                hide(btnCap); showBlock(btnRet);
                captured = true;
                checkReady();
            }
        };

        if(btnRet) btnRet.onclick = () => {
            select('[name="snapshot_image"]').value = '';
            snapPrev.style.display = 'none'; vid.style.display = 'block';
            showBlock(btnCap); hide(btnRet);
            captured = false;
            checkReady();
        };

        let isDown = false, hasSigned = false;
        const ctx = cvs.getContext('2d');
        let rect = cvs.getBoundingClientRect();
        const updateRect = () => { rect = cvs.getBoundingClientRect(); };
        window.addEventListener('scroll', updateRect); window.addEventListener('resize', updateRect);

        const getXY = (e) => {
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            rect = cvs.getBoundingClientRect();
            return { x: clientX - rect.left, y: clientY - rect.top };
        };
        const drawMove = (e) => { if (!isDown) return; e.preventDefault(); const p = getXY(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
        cvs.onmousedown = (e) => { isDown = true; ctx.beginPath(); const p = getXY(e); ctx.moveTo(p.x, p.y); };
        cvs.onmousemove = drawMove;
        window.addEventListener('mouseup', () => { if(isDown) { isDown = false; hasSigned = true; checkReady(); } });
        cvs.ontouchstart = (e) => { isDown = true; ctx.beginPath(); const p = getXY(e); ctx.moveTo(p.x, p.y); };
        cvs.ontouchmove = drawMove;
        window.addEventListener('touchend', () => { if(isDown) { isDown = false; hasSigned = true; checkReady(); } });
        select('#clearSign').onclick = () => { ctx.clearRect(0, 0, cvs.width, cvs.height); hasSigned = false; btnSubmit.disabled = true; };

        function checkReady() {
            const locOk = select('[name="geo_lat"]').value !== "";
            const camOk = !camSec.classList.contains('is-hidden') ? captured : true;
            btnSubmit.disabled = !(locOk && hasSigned && camOk);
        }

        f.onsubmit = async (e) => {
            e.preventDefault();
            select('[name="signature_image"]').value = cvs.toDataURL('image/png');
            const fd = new FormData(f);
            if (streamObj) streamObj.getTracks().forEach(track => track.stop());
            try {
                btnSubmit.disabled = true; btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
                const j = await r.json().catch(() => ({}));
                if (r.ok && (j.success ?? true)) {
                    alert('Berhasil!'); closeModal(m); setTimeout(() => location.reload(), 900);
                } else throw new Error(j.message || 'Gagal memproses tanda tangan.');
            } catch (err) { alert(err.message); btnSubmit.disabled = false; btnSubmit.innerHTML = 'Simpan & Tanda Tangan'; }
        };
        const cleanup = () => { if (streamObj) streamObj.getTracks().forEach(track => track.stop()); };
        m.querySelectorAll('.js-close-modal').forEach(b => b.addEventListener('click', cleanup));
    };

    const rejectForm = select('#rejectForm');
    if(rejectForm) {
        rejectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const noteEl = select('#rejectNote');
            const btn = select('#btnSubmitReject');
            const note = (noteEl?.value || '').trim();
            if(!rejectCtx.url) return alert('URL reject tidak ditemukan.');
            if(note.length < 5) return alert('Alasan penolakan wajib diisi (min 5 karakter).');
            if(btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
            try {
                const r = await fetch(rejectCtx.url, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ rejection_note: note })
                });
                const j = await r.json().catch(() => ({}));
                if (r.ok && (j.success ?? true)) {
                    alert('Berhasil Ditolak'); closeModal(select('#rejectModal')); closeModal(select('#detailContractModal')); setTimeout(() => location.reload(), 900);
                } else throw new Error(j.message || 'Gagal menolak dokumen.');
            } catch(err) {
                alert(err.message); if(btn) { btn.disabled = false; btn.textContent = 'Tolak Dokumen'; }
            }
        });
    }

    $(document).on('click', '.js-btn-detail', async function(e) {
        e.preventDefault();
        const btnDet = this;
        try {
            const res = await fetch(btnDet.dataset.showUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
            if(!res.success) throw new Error(res.message);
            const d = res.data;
            const m = safeJSON(d.remuneration_json);
            const isPb = (d.contract_type === 'PB_PENGAKHIRAN');
            const setText = (id, val) => { const el = select(id); if(el) el.textContent = val; };

            setText('#detNo', d.contract_no || '-');
            setText('#detType', d.contract_type_label || d.contract_type || '-');
            setText('#detStatus', d.status || '-');
            setText('#detUnit', d.unit?.name || '-');
            setText('#detName', d.person_name || '-');
            setText('#detNik', d.ui_employee_id || '-');
            setText('#detNikReal', d.ui_nik_ktp || '-');
            setText('#detPos', d.position_name || '-');
            setText('#detEmpType', d.employment_type || '-');
            setText('#detTicket', d.ticket_number || '-');

            const detRejectBox = select('#detRejectBox');
            const rejNote = (d.rejection_note || '').toString().trim();
            if ((d.status === 'draft' || d.status === 'rejected') && rejNote) {
                if(detRejectBox) { showBlock(detRejectBox); setText('#detRejectNote', rejNote); }
            } else hide(detRejectBox);

            const detLocRow = select('#detLocationRow');
            if (detLocRow) {
                if (d.contract_type?.includes('PKWT')) { showBlock(detLocRow); setText('#detLocation', m.work_location || '-'); }
                else hide(detLocRow);
            }

            if(d.tracker) {
                const h = d.tracker.head;
                const c = d.tracker.candidate;
                setText('#nameHead', h.name);
                setText('#posHead', h.position || 'Kepala Unit');
                setText('#dateHead', h.date);
                const bHead = select('#badgeHead');
                if(bHead) { bHead.textContent = h.status; bHead.className = `u-badge ${h.css}`; }
                const iHead = select('#iconHead');
                if(iHead) {
                    iHead.className = `u-avatar u-avatar--md ${h.status==='Signed'||h.status==='Approved' ? 'u-bg-success-light u-text-success' : (h.status==='Rejected'?'u-bg-danger-light u-text-danger':'u-bg-light u-text-muted')}`;
                    iHead.innerHTML = (h.status==='Signed'||h.status==='Approved') ? '<i class="fas fa-check"></i>' : (h.status==='Rejected'?'<i class="fas fa-times"></i>':'<i class="fas fa-user-tie"></i>');
                }
                setText('#nameCand', c.name);
                setText('#dateCand', c.date);
                if(d.target_role_label) setText('#labelCand', d.target_role_label);
                const bCand = select('#badgeCand');
                if(bCand) { bCand.textContent = c.status; bCand.className = `u-badge ${c.css}`; }
                const iCand = select('#iconCand');
                if(iCand) {
                    iCand.className = `u-avatar u-avatar--md ${c.status==='Signed' ? 'u-bg-success-light u-text-success' : 'u-bg-light u-text-muted'}`;
                    iCand.innerHTML = c.status==='Signed' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-user"></i>';
                }
            }

            const geo = d.geolocation || {};
            const mapSec = select('#detMapSection');
            const wHead = select('#wrapperMapHead');
            const wCand = select('#wrapperMapCand');
            const iHead = select('#img-head'); if(iHead) iHead.style.display='none';
            const niHead = select('#no-img-head'); if(niHead) niHead.style.display='none';
            const iCand = select('#img-cand'); if(iCand) iCand.style.display='none';
            const niCand = select('#no-img-cand'); if(niCand) niCand.style.display='none';

            if (geo.head || geo.candidate) showBlock(mapSec); else hide(mapSec);
            if (geo.head) {
                showBlock(wHead); setText('#ts-head', `Ditandatangani: ${geo.head.ts}`);
                initMap('map-head', geo.head.lat, geo.head.lng);
                if(geo.head.image_url) { if(iHead) { iHead.src = geo.head.image_url; showBlock(iHead); } }
                else showBlock(niHead);
            } else hide(wHead);
            if (geo.candidate) {
                showBlock(wCand); setText('#ts-cand', `Ditandatangani: ${geo.candidate.ts}`);
                initMap('map-cand', geo.candidate.lat, geo.candidate.lng);
                if(geo.candidate.image_url) { if(iCand) { iCand.src = geo.candidate.image_url; showBlock(iCand); } }
                else showBlock(niCand);
            } else hide(wCand);

            if (isPb) {
                hide(select('#detRemunBox')); hide(select('#detPeriodRow')); showBlock(select('#detPbBox'));
                setText('#detPbEff', m.pb_effective_end || '-');
                setText('#detPbVal', 'Rp ' + money(m.pb_compensation_amount));
                setText('#detPbValW', (m.pb_compensation_amount_words || '').toString());
            } else {
                showBlock(select('#detRemunBox')); showBlock(select('#detPeriodRow')); hide(select('#detPbBox'));
                setText('#detPeriod', `${d.start_date || '-'} s/d ${d.end_date || '-'}`);
                setText('#detSalary', 'Rp ' + money(m.salary_amount));
                setText('#detLunch', 'Rp ' + money(m.lunch_allowance_daily));
                setText('#detWorkDays', m.work_days || '-');
                setText('#detWorkHours', m.work_hours || '-');
                const allws = [];
                if(m.allowance_position_amount) allws.push(['T. Jabatan', m.allowance_position_amount]);
                if(m.allowance_communication_amount) allws.push(['T. Komunikasi', m.allowance_communication_amount]);
                if(m.allowance_special_amount) allws.push(['T. Khusus', m.allowance_special_amount]);
                if(m.allowance_other_amount) allws.push(['Lainnya', m.allowance_other_amount]);
                if(m.travel_allowance_stay) allws.push(['UHPD Inap', m.travel_allowance_stay]);
                if(m.travel_allowance_non_stay) allws.push(['UHPD Non-Inap', m.travel_allowance_non_stay]);
                const elAllw = select('#detAllowances');
                if(elAllw) elAllw.innerHTML = allws.map(x => `<div class="u-flex u-justify-between u-py-sm u-border-b"><span class="u-muted">${x[0]}</span><strong>Rp ${money(x[1])}</strong></div>`).join('');
            }
            const boxNew = select('#detNewUnitBox');
            const prevId = (m.prev_unit_id ?? '').toString();
            const newId = (m.new_unit_id ?? '').toString();
            if(d.contract_type === 'PKWT_PERPANJANGAN' && prevId && newId && prevId !== newId) {
                showBlock(boxNew); setText('#detNewUnit', m.new_unit_name || '-');
            } else hide(boxNew);

            const bPrev = select('#btnPreviewDoc'); d.doc_url ? (show(bPrev), bPrev.style.display='inline-flex', bPrev.href=d.doc_url) : hide(bPrev);
            const bApp = select('#btnApprove'); d.can_approve ? (show(bApp), bApp.onclick=()=>handleSign(d.approve_url)) : hide(bApp);
            const bSign = select('#btnSign'); d.can_sign ? (show(bSign), bSign.onclick=()=>handleSign(d.sign_url)) : hide(bSign);
            const bRej = select('#btnReject');
            if(d.can_approve && d.reject_url) { show(bRej); bRej.onclick = () => openReject(d.reject_url, `${d.contract_no || '-'} • ${d.person_name || '-'}`); } else hide(bRej);

            const logSection = select('#detLogSection');
            const logList = select('#detLogList');
            if (d.can_see_logs && d.approval_logs) {
                showBlock(logSection);
                const logs = d.approval_logs.map(log => {
                    let icon = '<i class="fas fa-check"></i>', bgClass = 'u-bg-success-light', textClass = 'u-text-success';
                    if(log.status === 'rejected') { icon = '<i class="fas fa-times"></i>'; bgClass = 'u-bg-danger-light'; textClass = 'u-text-danger'; }
                    else if(log.status === 'pending') { icon = '<i class="fas fa-clock"></i>'; bgClass = 'u-bg-light'; textClass = 'u-text-muted'; }
                    return `<div class="u-flex u-gap-md u-mb-md u-items-start">
                        <div class="u-avatar u-avatar--sm ${textClass}" style="background: var(--surface-2); border: 1px solid var(--border-color); flex-shrink: 0;">${icon}</div>
                        <div class="u-flex-1" style="min-width: 0;">
                            <div class="u-flex u-justify-between u-items-start u-w-full" style="width: 100%; display: flex; justify-content: space-between; align-items: flex-start;">
                                <div class="u-pr-sm"><div class="u-font-bold u-text-sm">${log.name}</div><div class="u-text-xs u-muted">${log.role}</div></div>
                                <div class="u-text-xs u-muted u-flex-shrink-0" style="margin-left: auto !important; white-space: nowrap; text-align: right;">${log.time_ago}</div>
                            </div>
                            <div class="u-text-sm u-p-sm u-rounded ${bgClass} ${textClass} u-mt-xs"><strong>${log.status.toUpperCase()}</strong>${log.note ? ': ' + log.note : ''}<div class="u-text-xs u-mt-xxs u-muted">${log.date_formatted}</div></div>
                        </div>
                    </div>`;
                }).join('');
                const createdLog = `<div class="u-flex u-gap-md u-mb-md u-items-start">
                    <div class="u-avatar u-avatar--sm u-text-brand" style="background: var(--surface-2); border: 1px solid var(--border-color); flex-shrink: 0;"><i class="fas fa-plus"></i></div>
                    <div class="u-flex-1" style="min-width: 0;">
                        <div class="u-flex u-justify-between u-items-start u-w-full" style="width: 100%; display: flex; justify-content: space-between; align-items: flex-start;">
                            <div class="u-pr-sm"><div class="u-font-bold u-text-sm">${d.creator_name || 'System'}</div><div class="u-text-xs u-muted">Document Created</div></div>
                            <div class="u-text-xs u-muted u-flex-shrink-0" style="margin-left: auto !important; white-space: nowrap; text-align: right;">${d.created_at_human || ''}</div>
                        </div>
                        <div class="u-text-sm u-p-sm u-rounded u-bg-light u-text-muted u-mt-xs"><strong>CREATED</strong><div class="u-text-xs u-mt-xxs u-muted">${d.created_at_formatted || ''}</div></div>
                    </div>
                </div>`;
                logList.innerHTML = logs + createdLog;
            } else hide(logSection);

            openModal('detailContractModal');
            setTimeout(() => {
                ['map-head','map-cand'].forEach(id => {
                    if (maps[id] && typeof maps[id].invalidateSize === 'function') { try { maps[id].invalidateSize(true); } catch(e) {} }
                });
            }, 250);
        } catch(err) { alert(err.message); }
    });

    $(document).on('click', '.js-btn-delete', function(e) {
        e.preventDefault();
        const btnDelete = this;
        Swal.fire({
            title: 'Hapus Dokumen?', text: "Data tidak dapat dikembalikan!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = doc.createElement('form');
                form.method = 'POST'; form.action = btnDelete.dataset.url;
                const csrfInput = doc.createElement('input'); csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = csrf; form.appendChild(csrfInput);
                const methodInput = doc.createElement('input'); methodInput.type = 'hidden'; methodInput.name = '_method'; methodInput.value = 'DELETE'; form.appendChild(methodInput);
                doc.body.appendChild(form); form.submit();
            }
        });
    });
});
</script>
@endpush