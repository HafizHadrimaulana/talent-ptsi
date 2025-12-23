@extends('layouts.app')

@section('title', 'Kontrak')

@section('content')
    @php
        $me = auth()->user();
        $meUnit = $me?->unit_id;
        $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
        
        $statusOptions = config('recruitment.contract_statuses', []);
        $rawTypeConfig = config('recruitment.contract_types', []);
        
        $spkCfg = $rawTypeConfig['SPK'] ?? null;
        $pkwtNewCfg = $rawTypeConfig['PKWT_BARU'] ?? null;
        $pkwtExtCfg = $rawTypeConfig['PKWT_PERPANJANGAN'] ?? null;
        $pbCfg = $rawTypeConfig['PB_PENGAKHIRAN'] ?? null;

        $currentUnitId = $selectedUnitId ?? $meUnit;
    @endphp

    <style>
        .modal-card-wide { width: 95% !important; max-width: 900px !important; }
        .u-grid-2-custom { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) { .u-grid-2-custom { grid-template-columns: 1fr; } }
        .u-bg-light-soft { background-color: #f9fafb; }
    </style>

    <div class="u-card u-card--glass u-hover-lift">
        {{-- HEADER --}}
        <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
            <div>
                <h2 class="u-title u-mb-xs">Penerbitan &amp; Penandatanganan Kontrak</h2>
                <p class="u-text-sm u-muted">
                    Monitoring draft–submit–review–e-sign kontrak kerja.<br>
                    <span class="u-text-xxs">Format: <code>(TYPE)-xxx/UNITCODE-mm/INISIAL-KEPALA/YYYY</code></span>
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
                            <th>Status</th>
                            <th class="cell-actions">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $c)
                            <tr>
                                <td><span class="u-badge u-badge--glass u-text-xs font-mono">{{ $c->contract_no ?: '—' }}</span></td>
                                <td><span class="u-chip u-chip--soft">{{ $c->contract_type }}</span></td>
                                <td>{{ $c->unit?->name ?? '—' }}</td>
                                <td class="u-text-sm">{{ $c->start_date?->format('d M Y') }} s/d {{ $c->end_date?->format('d M Y') }}</td>
                                <td><span class="u-badge u-badge--glass">{{ $c->status }}</span></td>
                                <td class="cell-actions">
                                    <div class="cell-actions__group">
                                        <button type="button" class="u-btn u-btn--ghost u-btn--xs" data-contract-detail data-show-url="{{ route('recruitment.contracts.show', $c) }}">
                                            <i class="fas fa-eye u-mr-xxs"></i> Detail
                                        </button>
                                        @if ($c->status === 'draft' && auth()->user()->can('contract.update', $c))
                                            <button type="button" class="u-btn u-btn--primary u-btn--xs" data-contract-edit data-show-url="{{ route('recruitment.contracts.show', $c) }}" data-update-url="{{ route('recruitment.contracts.update', $c) }}">
                                                <i class="fas fa-edit u-mr-xxs"></i> Edit
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
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
    <div id="createContractModal" class="u-modal" hidden aria-modal="true" role="dialog" style="display:none;align-items:center;justify-content:center;">
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head">
                <div class="u-flex u-items-center u-gap-md">
                    <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-contract"></i></div>
                    <div><div class="u-title">Draft Kontrak Baru</div><div class="u-muted u-text-sm">Buat draft SPK, PKWT, atau PB.</div></div>
                </div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="{{ route('recruitment.contracts.store') }}" class="u-modal__body u-p-md u-space-y-lg" id="createContractForm">
                @csrf
                <input type="hidden" name="contract_type" id="createTypeInput" value="{{ old('contract_type') }}">
                <input type="hidden" name="mode" id="createModeInput" value="{{ old('mode') }}">
                
                <input type="hidden" name="source_contract_id" id="createSourceIdInput" value="{{ old('source_contract_id') }}" disabled>
                <input type="hidden" name="employee_id" id="createEmployeeIdInput" value="{{ old('employee_id') }}" disabled>
                <input type="hidden" name="person_id" id="createPersonIdInput" value="{{ old('person_id') }}" disabled>

                <div class="u-space-y-sm">
                    <label class="u-text-sm u-font-medium d-block">Pilih Jenis Kontrak</label>
                    <div class="u-grid-2 u-stack-mobile u-gap-md">
                        <div>
                            <select id="createFamilySelect" class="u-input" required>
                                <option value="">-- Pilih --</option>
                                <option value="SPK" data-mode="new" data-hint="{{ $spkCfg['label'] ?? 'SPK' }}" @selected(old('contract_type') === 'SPK')>SPK (Offering Letter)</option>
                                <option value="PKWT" data-mode="" data-hint="Pilih detail PKWT" @selected(in_array(old('contract_type'), ['PKWT_BARU','PKWT_PERPANJANGAN']))>PKWT</option>
                                <option value="PB" data-mode="terminate" data-hint="{{ $pbCfg['label'] ?? 'PB' }}" @selected(old('contract_type') === 'PB_PENGAKHIRAN')>PB (Perjanjian Bersama)</option>
                            </select>
                            <p id="createFamilyHint" class="u-text-xs u-muted u-mt-xs"></p>
                        </div>
                        <div id="createSubtypeWrap" hidden>
                            <select id="createSubtypeSelect" class="u-input">
                                <option value="">-- Detail PKWT --</option>
                                <option value="PKWT_BARU" data-mode="new" @selected(old('contract_type') === 'PKWT_BARU')>{{ $pkwtNewCfg['label'] ?? 'PKWT Baru' }}</option>
                                <option value="PKWT_PERPANJANGAN" data-mode="extend" @selected(old('contract_type') === 'PKWT_PERPANJANGAN')>{{ $pkwtExtCfg['label'] ?? 'PKWT Perpanjangan' }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="createMainSection" class="u-space-y-lg" hidden>
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>
                        @if ($canSeeAll)
                            <select name="unit_id" id="createUnitSelect" class="u-input" required>
                                <option value="">-- Pilih Unit --</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}" @selected(old('unit_id', $currentUnitId) == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                            <div class="u-badge u-badge--glass">{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}</div>
                        @endif
                    </div>
                    
                    <div class="u-mt-md">
                         <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja Baru <span class="u-text-xs u-muted">(Opsional, jika pindah unit)</span></label>
                         <select name="new_unit_id" class="u-input">
                             <option value="">-- Tidak Berubah / Sama --</option>
                             @foreach ($units as $u)
                                 <option value="{{ $u->id }}">{{ $u->name }}</option>
                             @endforeach
                         </select>
                    </div>

                    <div data-mode-section="new" hidden class="u-space-y-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar (Approved)</label>
                            <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                <option value="">-- Pilih Pelamar --</option>
                                @foreach ($applicants as $a)
                                    <option value="{{ $a->id }}" data-person-id="{{ $a->person_id }}" data-fullname="{{ $a->full_name }}" data-pos="{{ $a->position_applied ?? $a->position_name }}" data-unit="{{ $a->unit_name }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
                                @endforeach
                            </select>
                            <div id="createApplicantPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                <div class="u-text-xs u-muted"><span id="capName">-</span> • <span id="capPos">-</span> • <span id="capUnit">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div data-mode-section="existing" hidden class="u-space-y-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak Dasar (Existing PKWT)</label>
                            <select id="createSourceExtendSelect" class="u-input">
                                <option value="">-- Pilih Kontrak Aktif (<=30 hari) --</option>
                                @foreach ($expiringContracts as $c)
                                    <option value="{{ $c->id }}" 
                                        data-unit-id="{{ $c->unit_id }}" 
                                        data-person-id="{{ $c->person_id }}"
                                        data-employee-id="{{ $c->employee_id }}"
                                        data-person="{{ $c->person_name }}" 
                                        data-pos="{{ $c->position_name }}">
                                        {{ $c->person_name }} — {{ $c->position_name }} ({{ $c->employee_status }}) [{{ $c->unit_name }}]
                                    </option>
                                @endforeach
                            </select>
                            <div id="createExtendPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                <div class="u-text-xs u-muted"><span id="cepName">-</span> • <span id="cepPos">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div data-mode-section="terminate" hidden class="u-space-y-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak PKWT Diakhiri</label>
                            <select id="createSourceTermSelect" class="u-input">
                                <option value="">-- Pilih Kontrak --</option>
                                @foreach ($expiringContracts as $c)
                                    <option value="{{ $c->id }}" data-unit-id="{{ $c->unit_id }}" data-person-id="{{ $c->person_id }}" data-employee-id="{{ $c->employee_id }}" data-person="{{ $c->person_name }}" data-pos="{{ $c->position_name }}">
                                        {{ $c->person_name }} — {{ $c->position_name }} ({{ $c->employee_status }}) [{{ $c->unit_name }}]
                                    </option>
                                @endforeach
                            </select>
                            <div id="createTermPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                <div class="u-text-xs u-muted"><span id="ctpName">-</span> • <span id="ctpPos">-</span></div>
                            </div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div><label class="u-text-xs u-font-medium d-block">Efektif Pengakhiran</label><input type="date" name="pb_effective_end" class="u-input" value="{{ old('pb_effective_end') }}"></div>
                            <div><label class="u-text-xs u-font-medium d-block">Kompensasi (Rp)</label><input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true" data-terbilang-target="pb_compensation_amount_words" value="{{ old('pb_compensation_amount') }}"></div>
                        </div>
                        <div><label class="u-text-xs u-font-medium d-block">Terbilang (Otomatis)</label><input type="text" name="pb_compensation_amount_words" class="u-input" readonly value="{{ old('pb_compensation_amount_words') }}"></div>
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md">
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Hubungan Kerja</label>
                            <select name="employment_type" class="u-input">
                                <option value="">-- Pilih --</option>
                                @foreach ($employmentTypes as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Jabatan</label>
                            <input type="text" name="position_name" class="u-input" list="positionList">
                            <datalist id="positionList">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                        </div>
                    </div>
                    
                    <div class="u-grid-2 u-stack-mobile u-gap-md" data-mode-hide="terminate">
                         <div><label class="u-text-sm u-font-medium d-block">Mulai</label><input type="date" name="start_date" class="u-input" value="{{ old('start_date') }}"></div>
                         <div><label class="u-text-sm u-font-medium d-block">Selesai</label><input type="date" name="end_date" class="u-input" value="{{ old('end_date') }}"></div>
                    </div>
                    
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium d-block">Catatan (Opsional)</label>
                        <input type="text" name="remarks" class="u-input" value="{{ old('remarks') }}">
                    </div>

                    <div id="createRemun" data-mode-hide="terminate" class="u-card u-card--soft u-p-md u-space-y-md u-mt-md">
                        <div class="u-text-sm u-font-semibold">Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div><label class="u-text-xs d-block">Gaji Pokok</label><input type="text" name="salary_amount" class="u-input" data-rupiah="true" data-terbilang-target="salary_amount_words" value="{{ old('salary_amount') }}"><input type="text" name="salary_amount_words" class="u-input u-mt-xs" readonly value="{{ old('salary_amount_words') }}"></div>
                            <div><label class="u-text-xs d-block">Uang Makan/Hari</label><input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true" data-terbilang-target="lunch_allowance_words" value="{{ old('lunch_allowance_daily') }}"><input type="text" name="lunch_allowance_words" class="u-input u-mt-xs" readonly value="{{ old('lunch_allowance_words') }}"></div>
                        </div>
                        <details>
                            <summary class="u-text-xs u-cursor-pointer">Tunjangan Lain</summary>
                            <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                                <div><label class="u-text-xxs">T. Jabatan</label><input type="text" name="allowance_position_amount" class="u-input" data-rupiah="true" data-terbilang-target="allowance_position_words" value="{{ old('allowance_position_amount') }}"><input name="allowance_position_words" class="u-input u-mt-xxs" readonly value="{{ old('allowance_position_words') }}"></div>
                                <div><label class="u-text-xxs">T. Komunikasi</label><input type="text" name="allowance_communication_amount" class="u-input" data-rupiah="true" data-terbilang-target="allowance_communication_words" value="{{ old('allowance_communication_amount') }}"><input name="allowance_communication_words" class="u-input u-mt-xxs" readonly value="{{ old('allowance_communication_words') }}"></div>
                                <div><label class="u-text-xxs">T. Khusus</label><input type="text" name="allowance_special_amount" class="u-input" data-rupiah="true" data-terbilang-target="allowance_special_words" value="{{ old('allowance_special_amount') }}"><input name="allowance_special_words" class="u-input u-mt-xxs" readonly value="{{ old('allowance_special_words') }}"></div>
                                <div><label class="u-text-xxs">Lainnya</label><input type="text" name="allowance_other_amount" class="u-input" data-rupiah="true" data-terbilang-target="allowance_other_words" value="{{ old('allowance_other_amount') }}"><input name="allowance_other_words" class="u-input u-mt-xxs" readonly value="{{ old('allowance_other_words') }}"></div>
                            </div>
                            <div class="u-mt-sm"><input type="text" name="allowance_other_desc" class="u-input" placeholder="Ket. Tunjangan Lain" value="{{ old('allowance_other_desc') }}"></div>
                            <div class="u-mt-sm"><input type="text" name="other_benefits_desc" class="u-input" placeholder="Benefit Lain (BPJS dll)" value="{{ old('other_benefits_desc') }}"></div>
                        </details>

                        <div class="u-mt-md">
                            <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                Fasilitas / Benefit Lain <span class="u-muted">(opsional)</span>
                            </label>
                            <textarea name="other_benefits_desc" class="u-input" rows="2"
                                placeholder="Mis. BPJS Kesehatan &amp; Ketenagakerjaan, tunjangan transport, dll.">{{ old('other_benefits_desc') }}</textarea>
                        </div>

                    <div class="u-flex u-gap-md u-flex-wrap u-mt-md">
                        <label class="u-text-xs"><input type="checkbox" name="requires_draw_signature" value="1" @checked(old('requires_draw_signature', 1))> Ttd Digital</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_camera" value="1" @checked(old('requires_camera', 1))> Kamera</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_geolocation" value="1" @checked(old('requires_geolocation', 1))> Lokasi</label>
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

    <div id="editContractModal" class="u-modal" hidden style="display:none;align-items:center;justify-content:center;">
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head"><div class="u-title">Edit Draft</div><button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button></div>
            <form method="POST" class="u-modal__body u-p-md u-space-y-lg" id="editContractForm">
                @csrf @method('PUT')
                <input type="hidden" name="contract_type" id="editTypeInput">
                <input type="hidden" name="source_contract_id" id="editSourceIdInput" disabled>
                <input type="hidden" name="mode" id="editModeInput">
                <input type="hidden" name="employee_id" id="editEmployeeId" disabled>
                <input type="hidden" name="person_id" id="editPersonId" disabled>

                <div id="editFormContent">
                    <div class="u-grid-2 u-stack-mobile u-gap-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>
                            @if ($canSeeAll)
                                <select name="unit_id" id="editContractUnitSelect" class="u-input"><option value="">Pilih Unit</option>@foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach</select>
                            @else
                                <input type="hidden" name="unit_id" id="editUnitIdInput">
                                <div class="u-badge u-badge--glass" id="editUnitBadge">-</div>
                            @endif
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja Baru (Opsional)</label>
                            <select name="new_unit_id" id="editNewUnitId" class="u-input">
                                <option value="">-- Tidak Berubah --</option>
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Pelamar / Karyawan</label>
                            <input type="text" id="editPersonName" class="u-input" readonly disabled>
                            <input type="hidden" name="applicant_id" id="editApplicantId">
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
                    <div id="editPbFields" class="u-grid-2 u-stack-mobile u-gap-md u-mt-md" hidden>
                        <div><label class="u-text-sm d-block">Efektif Pengakhiran</label><input type="date" name="pb_effective_end" id="editPbEnd" class="u-input"></div>
                        <div>
                            <label class="u-text-sm d-block">Kompensasi</label>
                            <input type="text" name="pb_compensation_amount" id="editPbComp" class="u-input" data-rupiah="true" data-terbilang-target="editPbCompW">
                            <input type="text" name="pb_compensation_amount_words" id="editPbCompW" class="u-input u-mt-xxs" readonly>
                        </div>
                    </div>
                    <div class="u-mt-md"><label class="u-text-sm d-block">Catatan</label><input type="text" name="remarks" id="editRemarks" class="u-input"></div>

                    <div id="editRemun" class="u-card u-card--soft u-p-md u-space-y-md u-mt-md">
                        <div class="u-text-sm u-font-semibold">Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div><label class="u-text-xs d-block">Gaji Pokok</label><input type="text" name="salary_amount" id="editSalary" class="u-input" data-rupiah="true" data-terbilang-target="editSalaryW"><input type="text" name="salary_amount_words" id="editSalaryW" class="u-input u-mt-xs" readonly></div>
                            <div><label class="u-text-xs d-block">Uang Makan</label><input type="text" name="lunch_allowance_daily" id="editLunch" class="u-input" data-rupiah="true" data-terbilang-target="editLunchW"><input type="text" name="lunch_allowance_words" id="editLunchW" class="u-input u-mt-xs" readonly></div>
                        </div>
                        <details>
                            <summary class="u-text-xs u-cursor-pointer">Tunjangan Lain</summary>
                            <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                                <div><label class="u-text-xxs">T. Jabatan</label><input type="text" name="allowance_position_amount" id="editAP" class="u-input" data-rupiah="true" data-terbilang-target="editAPW"><input name="allowance_position_words" id="editAPW" class="u-input u-mt-xxs" readonly></div>
                                <div><label class="u-text-xxs">T. Komunikasi</label><input type="text" name="allowance_communication_amount" id="editAC" class="u-input" data-rupiah="true" data-terbilang-target="editACW"><input name="allowance_communication_words" id="editACW" class="u-input u-mt-xxs" readonly></div>
                                <div><label class="u-text-xxs">T. Khusus</label><input type="text" name="allowance_special_amount" id="editAS" class="u-input" data-rupiah="true" data-terbilang-target="editASW"><input name="allowance_special_words" id="editASW" class="u-input u-mt-xxs" readonly></div>
                                <div><label class="u-text-xxs">Lainnya</label><input type="text" name="allowance_other_amount" id="editAO" class="u-input" data-rupiah="true" data-terbilang-target="editAOW"><input name="allowance_other_words" id="editAOW" class="u-input u-mt-xxs" readonly></div>
                            </div>
                            <div class="u-mt-sm"><input type="text" name="allowance_other_desc" id="editAOD" class="u-input" placeholder="Ket. Tunjangan Lain"></div>
                            <div class="u-mt-sm"><input type="text" name="other_benefits_desc" id="editOB" class="u-input" placeholder="Benefit Lain"></div>
                        </details>
                    </div>
                    <div class="u-flex u-gap-md u-flex-wrap u-mt-md">
                        <label class="u-text-xs"><input type="checkbox" name="requires_draw_signature" id="editDraw" value="1"> Ttd Digital</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_camera" id="editCam" value="1"> Kamera</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_geolocation" id="editGeo" value="1"> Lokasi</label>
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
    
    {{-- MODAL DETAIL KONTRAK (Clean Layout) --}}
    <div id="detailContractModal" class="u-modal" hidden style="display:none;align-items:center;justify-content:center;">
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl modal-card-wide">
            <div class="u-modal__head"><div class="u-title">Detail Kontrak</div><button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button></div>
            <div class="u-modal__body u-p-md u-space-y-md u-bg-light-soft">
                <div class="u-grid-2-custom">
                    <div class="u-card u-p-md">
                        <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-sm">Info Utama</div>
                        <div class="u-space-y-xs">
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Nomor</span><span id="detNo" class="u-font-medium">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Status</span><span id="detStatus">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Unit</span><span id="detUnit">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Jenis</span><span id="detType">-</span></div>
                        </div>
                    </div>
                    <div class="u-card u-p-md">
                         <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-sm">Detail Personil</div>
                         <div class="u-space-y-xs">
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Nama</span><span id="detName" class="u-font-medium">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Jabatan</span><span id="detPos">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Periode</span><span id="detPeriod">-</span></div>
                             <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Hubungan Kerja</span><span id="detEmpType">-</span></div>
                         </div>
                    </div>
                </div>

                <div class="u-card u-card--glass u-p-sm" id="detNewUnitBox" hidden>
                     <div class="u-text-xs u-muted">Unit Baru: <span id="detNewUnit" class="u-text-dark font-bold">-</span></div>
                </div>

                <div id="detRemunBox" class="u-card u-p-md">
                     <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-sm">Rincian Remunerasi</div>
                     <div class="u-grid-2-custom u-gap-md">
                        <div class="u-space-y-xs">
                            <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Gaji Pokok</span><span id="detSalary" class="u-font-medium">-</span></div>
                            <div class="u-text-xxs u-muted u-text-right" id="detSalaryW"></div>
                            <div class="u-flex u-justify-between"><span class="u-text-xs u-muted">Uang Makan</span><span id="detLunch" class="u-font-medium">-</span></div>
                            <div class="u-text-xxs u-muted u-text-right" id="detLunchW"></div>
                        </div>
                        <div id="detAllowances" class="u-space-y-xs"></div>
                     </div>
                </div>

                <div id="detPbBox" class="u-card u-p-md" hidden>
                     <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-sm">Kompensasi Pengakhiran</div>
                     <div class="u-grid-2 u-gap-md">
                         <div><div class="u-text-xs u-muted">Nilai Kompensasi</div><div id="detPbVal" class="u-text-lg u-font-bold">-</div><div class="u-text-xxs u-muted" id="detPbValW"></div></div>
                         <div><div class="u-text-xs u-muted">Efektif Tanggal</div><div id="detPbEff">-</div></div>
                     </div>
                </div>

                <div id="detApprovalInfo" class="u-card u-card--glass u-p-sm" hidden>
                     <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Status Approval</div>
                     <div id="detAppStatus" class="u-text-sm u-font-medium">-</div>
                     <div id="detAppNote" class="u-text-xs u-muted u-mt-xxs">-</div>
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost" data-modal-close>Tutup</button>
                
                {{-- Secure Document Preview Link (FIXED ROUTE) --}}
                <a href="#" id="btnPreviewDoc" target="_blank" class="u-btn u-btn--outline u-text-brand" hidden>
                    <i class="fas fa-file-pdf u-mr-xxs"></i> Lihat Dokumen
                </a>

                <button type="button" id="btnReject" class="u-btn u-btn--danger" hidden>Reject</button>
                <button type="button" id="btnApprove" class="u-btn u-btn--brand" hidden>Approve</button>
                <button type="button" id="btnSign" class="u-btn u-btn--primary" hidden>Sign</button>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="u-modal" hidden style="display:none;align-items:center;justify-content:center;">
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--sm">
            <div class="u-modal__head">
                <div class="u-title text-danger">Reject Kontrak</div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button>
            </div>
            <form id="rejectForm" class="u-modal__body u-p-md u-space-y-md">
                <p class="u-text-sm u-muted">Mohon berikan alasan penolakan kontrak ini.</p>
                <div>
                    <label class="u-text-xs u-font-medium d-block u-mb-xs">Alasan Reject</label>
                    <textarea name="rejection_note" class="u-input" rows="3" required placeholder="Contoh: Gaji tidak sesuai kesepakatan..."></textarea>
                </div>
                <div class="u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--danger">Konfirmasi Reject</button>
                </div>
            </form>
        </div>
    </div>

    <div id="signModal" class="u-modal" hidden style="display:none;align-items:center;justify-content:center;">
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--md">
            <div class="u-modal__head"><div class="u-title">Tanda Tangan</div></div>
            <form id="signForm" class="u-modal__body u-p-md u-space-y-md">
                <p class="u-text-xs u-muted">Bubuhkan tanda tangan.</p>
                <div class="u-card u-card--border u-p-xs">
                    <canvas id="signCanvas" width="400" height="200" style="width:100%;height:200px;touch-action:none;background:#fff"></canvas>
                </div>
                <div class="u-flex u-justify-between u-text-xs">
                    <button type="button" id="clearSign" class="u-text-brand">Bersihkan</button>
                    <span id="geoStatus" class="u-muted">Lokasi: Menunggu...</span>
                </div>
                <input type="hidden" name="signature_image">
                <div class="u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--brand">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (s, p=document) => p.querySelector(s);
    const $$ = (s, p=document) => [...p.querySelectorAll(s)];
    const csrf = $('meta[name="csrf-token"]')?.content;
    const money = n => 'Rp. ' + (n||0).toString().replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    const on = (el, ev, fn) => el && el.addEventListener(ev, fn);
    const show = el => { 
        if(el) {
             el.hidden = false; 
             el.style.display = 'flex'; 
        }
    };
    const hide = el => { 
        if(el) {
            el.hidden = true; 
            el.style.display = 'none';
        }
    };
    const text = (el, t) => el && (el.textContent = t || '-');

    const safeJSON = (v) => {
        if (!v) return {};
        if (typeof v === 'object') return v;
        try { return JSON.parse(v); } catch(e) { return {}; }
    };

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
        if(n<1000000000) return terbilang(Math.floor(n/1000000))+' juta '+terbilang(n%1000000);
        return terbilang(Math.floor(n/1000000000))+' miliar '+terbilang(n%1000000000);
    };

    const bindRupiahAndTerbilang = (root) => {
        $$('input[data-rupiah="true"]', root).forEach(el => {
            const targetId = el.dataset.terbilangTarget;
            const target = targetId ? root.querySelector(`[name="${targetId}"], #${targetId}`) : null;
            const update = () => {
                let val = el.value.replace(/\D/g,'');
                el.value = val ? money(val) : '';
                if(target && val) target.value = (terbilang(val) + ' rupiah').toUpperCase();
                else if(target) target.value = '';
            };
            on(el, 'input', update);
            update(); 
        });
    };

    // CREATE Logic
    const cm = $('#createContractModal');
    const cForm = $('#createContractForm');
    if(cm && cForm) {
        bindRupiahAndTerbilang(cForm);
        const famSel = $('#createFamilySelect');
        const subWrap = $('#createSubtypeWrap');
        const subSel = $('#createSubtypeSelect');
        const typeInp = $('#createTypeInput');
        const modeInp = $('#createModeInput');
        const mainSec = $('#createMainSection');
        const unitSel = $('#createUnitSelect');
        
        const srcIdInp = $('#createSourceIdInput');
        const empIdInp = $('#createEmployeeIdInput');
        const perIdInp = $('#createPersonIdInput');

        const switchSection = (mode) => {
            $$('[data-mode-section]', cForm).forEach(el => {
                if(el.dataset.modeSection === (mode === 'extend' ? 'existing' : mode)){
                     el.hidden = false;
                     el.style.display = 'block';
                } else {
                     el.hidden = true;
                     el.style.display = 'none';
                }
            });
            const remun = $('#createRemun');
            if(remun) remun.hidden = (mode === 'terminate');
            
            const pbSpec = $$('[data-mode-show="terminate"]', cForm);
            pbSpec.forEach(el => el.hidden = (mode !== 'terminate'));

            mainSec.hidden = false;
            mainSec.style.display = 'block';
        };

        const resetSource = () => {
             const e = $('#createExtendPreview');
             if(e) { e.hidden = true; e.style.display = 'none'; }
             const t = $('#createTermPreview');
             if(t) { t.hidden = true; t.style.display = 'none'; }
        };

        on(famSel, 'change', () => {
            const val = famSel.value;
            const opt = famSel.options[famSel.selectedIndex];
            $('#createFamilyHint').textContent = opt.dataset.hint || '';
            subSel.value = '';
            resetSource();
            
            if(val === 'PKWT') {
                if(subWrap) { subWrap.hidden = false; subWrap.style.display = 'block'; }
                if(mainSec) { mainSec.hidden = true; mainSec.style.display = 'none'; }
                typeInp.value = '';
            } else {
                if(subWrap) { subWrap.hidden = true; subWrap.style.display = 'none'; }
                typeInp.value = (val === 'SPK') ? 'SPK' : 'PB_PENGAKHIRAN';
                modeInp.value = opt.dataset.mode;
                switchSection(opt.dataset.mode);
            }
        });

        on(subSel, 'change', () => {
            const val = subSel.value;
            const opt = subSel.options[subSel.selectedIndex];
            resetSource();
            if(val) {
                typeInp.value = val; modeInp.value = opt.dataset.mode; switchSection(opt.dataset.mode);
            } else {
                if(mainSec) { mainSec.hidden = true; mainSec.style.display = 'none'; }
            }
        });

        const filterSource = (uId) => {
            ['#createSourceExtendSelect', '#createSourceTermSelect'].forEach(selId => {
                const sel = $(selId); if(!sel) return;
                [...sel.options].forEach(opt => {
                    if(!opt.value) return; 
                    const show = opt.dataset.unitId == uId;
                    opt.hidden = !show; opt.disabled = !show;
                });
            });
        };

        if(unitSel) on(unitSel, 'change', () => { resetSource(); filterSource(unitSel.value); });
        const initUnitId = unitSel ? unitSel.value : ($('input[name="unit_id"]')?.value || '');
        if(initUnitId) filterSource(initUnitId);

        on($('#createApplicantSelect'), 'change', function() {
            const o = this.options[this.selectedIndex];
            const p = $('#createApplicantPreview');
            if(this.value){
                empIdInp.value = ''; 
                perIdInp.value = o.dataset.personId || '';
                empIdInp.disabled = false; perIdInp.disabled = false;
                text($('#capName'), o.dataset.fullname);
                if(p) { p.hidden = false; p.style.display = 'block'; }
            } else {
                if(p) { p.hidden = true; p.style.display = 'none'; }
            }
        });
        
        const updateSrcPreview = (sel, pId, pre) => {
            const o = sel.options[sel.selectedIndex];
            const p = $(pId);
            if(sel.value) {
                srcIdInp.value = sel.value; 
                empIdInp.value = o.dataset.employeeId || '';
                perIdInp.value = o.dataset.personId || '';
                
                srcIdInp.disabled = false; empIdInp.disabled = false; perIdInp.disabled = false;
                
                text($(`#${pre}Name`), o.dataset.person);
                text($(`#${pre}Pos`), o.dataset.pos);
                if(p) { p.hidden = false; p.style.display = 'block'; }
            } else if(!srcIdInp.value) {
                if(p) { p.hidden = true; p.style.display = 'none'; }
            }
        };
        on($('#createSourceExtendSelect'), 'change', function(){ updateSrcPreview(this, '#createExtendPreview', 'cep'); });
        on($('#createSourceTermSelect'), 'change', function(){ updateSrcPreview(this, '#createTermPreview', 'ctp'); });

        cForm.onsubmit = (e) => {
            const mode = modeInp.value;
            if ((mode === 'extend' || mode === 'terminate') && !srcIdInp.value) {
                e.preventDefault();
                alert('Harap pilih Kontrak Dasar!');
                return false;
            }
            if(srcIdInp.value) srcIdInp.disabled = false; 
            if(empIdInp.value) empIdInp.disabled = false;
            if(perIdInp.value) perIdInp.disabled = false;
            
            if(!srcIdInp.value) srcIdInp.disabled = true;
            if(!empIdInp.value) empIdInp.disabled = true;
            if(!perIdInp.value) perIdInp.disabled = true;
        };
    }

    let currentContract = null;
    
    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-modal-close]')) {
             const m = e.target.closest('.u-modal');
             if(m) hide(m);
             document.body.classList.remove('modal-open');
             return;
        }

        const detailBtn = e.target.closest('[data-contract-detail]');
        if(detailBtn) {
            const url = detailBtn.dataset.showUrl;
            try {
                // FIXED: Better Error Handling (JSON vs HTML)
                const resRaw = await fetch(url);
                if (!resRaw.ok) {
                     const errText = await resRaw.text();
                     throw new Error(`Server Error (${resRaw.status}): ${errText.substring(0, 100)}...`);
                }
                const res = await resRaw.json();
                
                if(!res.success) throw new Error(res.message);
                const d = res.data;
                currentContract = d;
                
                text($('#detNo'), d.contract_no);
                text($('#detStatus'), d.status);
                text($('#detUnit'), d.unit?.name);
                text($('#detType'), d.contract_type_label);
                text($('#detName'), d.person_name);
                text($('#detPos'), d.position_name);
                text($('#detPeriod'), `${d.start_date||'-'} s/d ${d.end_date||'-'}`);
                text($('#detEmpType'), d.employment_type);

                const meta = safeJSON(d.remuneration_json);
                
                if(meta.new_unit_name) {
                    text($('#detNewUnit'), meta.new_unit_name);
                    const b = $('#detNewUnitBox');
                    if(b) { b.hidden = false; b.style.display='block'; }
                } else {
                    const b = $('#detNewUnitBox');
                    if(b) { b.hidden = true; b.style.display='none'; }
                }

                if(d.contract_type === 'PB_PENGAKHIRAN') {
                      const r = $('#detRemunBox'); if(r) { r.hidden = true; r.style.display='none'; }
                      const p = $('#detPbBox'); if(p) { p.hidden = false; p.style.display='block'; }
                      text($('#detPbVal'), money(meta.pb_compensation_amount));
                      text($('#detPbValW'), meta.pb_compensation_amount_words);
                      text($('#detPbEff'), meta.pb_effective_end);
                } else {
                      const r = $('#detRemunBox'); if(r) { r.hidden = false; r.style.display='block'; }
                      const p = $('#detPbBox'); if(p) { p.hidden = true; p.style.display='none'; }
                      text($('#detSalary'), money(meta.salary_amount));
                      text($('#detSalaryW'), meta.salary_amount_words);
                      text($('#detLunch'), money(meta.lunch_allowance_daily));
                      text($('#detLunchW'), meta.lunch_allowance_words);
                      
                      const allws = [];
                      if(meta.allowance_position_amount) allws.push(['T. Jabatan', meta.allowance_position_amount, meta.allowance_position_words]);
                      if(meta.allowance_communication_amount) allws.push(['T. Komunikasi', meta.allowance_communication_amount, meta.allowance_communication_words]);
                      if(meta.allowance_special_amount) allws.push(['T. Khusus', meta.allowance_special_amount, meta.allowance_special_words]);
                      if(meta.allowance_other_amount) allws.push(['Lainnya', meta.allowance_other_amount, meta.allowance_other_words]);
                      
                      $('#detAllowances').innerHTML = allws.map(x => `
                          <div class="u-flex u-justify-between u-items-center">
                              <div>
                                  <div class="u-text-xs u-muted">${x[0]}</div>
                                  <div class="u-text-xxs u-muted" style="font-style: italic;">${x[2] || ''}</div>
                              </div>
                              <div class="u-font-medium">${money(x[1])}</div>
                          </div>
                      `).join('');
                }

                const appBox = $('#detApprovalInfo');
                if(d.status === 'approved' || d.status === 'rejected' || d.status === 'signed') {
                      if(appBox) { appBox.hidden = false; appBox.style.display='block'; }
                      text($('#detAppStatus'), d.status); 
                } else {
                      if(appBox) { appBox.hidden = true; appBox.style.display='none'; }
                }

                // --- TOMBOL LIHAT DOKUMEN ---
const btnPreview = $('#btnPreviewDoc');
if (btnPreview) {
  if (d.doc_url) {
    btnPreview.hidden = false;
    btnPreview.href = d.doc_url;
    btnPreview.onclick = (ev) => { ev.preventDefault(); window.open(d.doc_url, '_blank', 'noopener'); };
  } else {
    btnPreview.hidden = true;
    btnPreview.href = '#';
    btnPreview.onclick = null;
  }
}


                const bApp=$('#btnApprove'), bSign=$('#btnSign'), bRej=$('#btnReject');
                if(bApp) {
                      bApp.hidden = !d.can_approve;
                      bApp.onclick = () => handleSignAction(d.approve_url);
                }
                if(bRej) bRej.hidden = !d.can_approve; 
                if(bSign) {
                      bSign.hidden = !d.can_sign;
                      bSign.onclick = () => handleSignAction(d.sign_url);
                }
                
                show($('#detailContractModal'));
                document.body.classList.add('modal-open');
            } catch(err) {
                console.error(err);
                alert('Gagal memuat data: ' + err.message);
            }
            return;
        }

        const editBtn = e.target.closest('[data-contract-edit]');
        if(editBtn) {
            const url = editBtn.dataset.showUrl;
            try {
                const resRaw = await fetch(url);
                if (!resRaw.ok) throw new Error(`Server Error (${resRaw.status})`);
                const res = await resRaw.json();
                
                if(!res.success) throw new Error(res.message);
                const d = res.data;
                const meta = safeJSON(d.remuneration_json);
                const frm = $('#editContractForm');
                frm.action = editBtn.dataset.updateUrl;
                
                $('#editTypeInput').value = d.contract_type;
                $('#editSourceIdInput').value = d.source_contract_id || '';
                
                const uSel = $('#editContractUnitSelect');
                const uHid = $('#editUnitIdInput');
                if(uSel) uSel.value = d.unit_id;
                else if(uHid) { uHid.value = d.unit_id; text($('#editUnitBadge'), d.unit?.name); }
                
                if(meta.new_unit_id) $('#editNewUnitId').value = meta.new_unit_id;
                else $('#editNewUnitId').value = "";

                $('#editEmployeeId').value = d.employee_id || '';
                $('#editPersonId').value = d.person_id || '';
                $('#editPersonName').value = d.person_name;
                $('#editPositionName').value = d.position_name;
                $('#editStartDate').value = d.start_date;
                $('#editEndDate').value = d.end_date;
                $('#editEmploymentType').value = d.employment_type;
                
                $('#editSalary').value = meta.salary_amount || '';
                $('#editLunch').value = meta.lunch_allowance_daily || '';
                $('#editAP').value = meta.allowance_position_amount || '';
                $('#editAC').value = meta.allowance_communication_amount || '';
                $('#editAS').value = meta.allowance_special_amount || '';
                $('#editAO').value = meta.allowance_other_amount || '';
                $('#editAOD').value = meta.allowance_other_desc || '';
                $('#editOB').value = meta.other_benefits_desc || '';
                $('#editRemarks').value = d.remarks || '';
                
                if(d.contract_type === 'PB_PENGAKHIRAN') {
                    const f = $('#editPbFields'); if(f) { f.hidden = false; f.style.display='grid'; }
                    const r = $('#editRemun'); if(r) { r.hidden = true; r.style.display='none'; }
                    const dt = $('#editDates'); if(dt) { dt.hidden = true; dt.style.display='none'; }
                    $('#editPbEnd').value = meta.pb_effective_end || '';
                    $('#editPbComp').value = meta.pb_compensation_amount || '';
                } else {
                    const f = $('#editPbFields'); if(f) { f.hidden = true; f.style.display='none'; }
                    const r = $('#editRemun'); if(r) { r.hidden = false; r.style.display='block'; }
                    const dt = $('#editDates'); if(dt) { dt.hidden = false; dt.style.display='grid'; }
                }
                
                $('#editCam').checked = !!d.requires_camera;
                $('#editGeo').checked = !!d.requires_geolocation;
                $('#editDraw').checked = !!d.requires_draw_signature;

                bindRupiahAndTerbilang(frm);
                show($('#editContractModal'));
                document.body.classList.add('modal-open');

                frm.onsubmit = () => {
                    const srcInp = $('#editSourceIdInput');
                    if(srcInp.value) srcInp.disabled = false; else srcInp.disabled = true;
                    
                    const uInp = $('#editUnitIdInput'); 
                    const uSel = $('#editContractUnitSelect'); 
                    if(uSel && uInp) uInp.disabled = true; 
                    
                    const empInp = $('#editEmployeeId');
                    const perInp = $('#editPersonId');
                    if(empInp.value) empInp.disabled = false; else empInp.disabled = true;
                    if(perInp.value) perInp.disabled = false; else perInp.disabled = true;
                };
            } catch(err) {
                console.error(err);
                alert('Gagal memuat data: ' + err.message);
            }
        }
        
        if (e.target.closest('[data-modal-open]')) {
             const id = e.target.closest('[data-modal-open]').dataset.modalOpen;
             const m = document.getElementById(id);
             if(m) {
                 show(m);
                 document.body.classList.add('modal-open');
             }
        }
    });

    const handleSignAction = (actionUrl) => {
        const sm = $('#signModal');
        const sf = $('#signForm');
        const cvs = $('#signCanvas');
        const ctx = cvs.getContext('2d');
        ctx.clearRect(0,0,cvs.width,cvs.height);
        ctx.lineWidth = 2; ctx.strokeStyle = '#000';
        let isDraw = false;
        
        const getPos = (e) => {
            const r = cvs.getBoundingClientRect();
            const scaleX = cvs.width / r.width;
            const scaleY = cvs.height / r.height;

            const clientX = (e.clientX || (e.touches && e.touches[0].clientX));
            const clientY = (e.clientY || (e.touches && e.touches[0].clientY));

            return {
                x: (clientX - r.left) * scaleX,
                y: (clientY - r.top) * scaleY
            };
        };

        const start = (e) => { isDraw=true; ctx.beginPath(); const p=getPos(e); ctx.moveTo(p.x, p.y); e.preventDefault(); };
        const move = (e) => { if(!isDraw)return; const p=getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); };
        const end = () => { isDraw=false; };
        
        ['mousedown','touchstart'].forEach(ev=>cvs.addEventListener(ev, start, {passive: false}));
        ['mousemove','touchmove'].forEach(ev=>cvs.addEventListener(ev, move, {passive: false}));
        ['mouseup','touchend','mouseleave'].forEach(ev=>cvs.addEventListener(ev, end));
        
        $('#clearSign').onclick = () => ctx.clearRect(0,0,cvs.width,cvs.height);

        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(p => {
                $('[name="geo_lat"]', sf).value = p.coords.latitude;
                $('[name="geo_lng"]', sf).value = p.coords.longitude;
                $('[name="geo_accuracy"]', sf).value = p.coords.accuracy;
                $('#geoStatus').textContent = 'Lokasi: OK';
                $('#geoStatus').className = 'u-text-success';
            }, () => {
                $('#geoStatus').textContent = 'Lokasi: Gagal (lanjut tanpa lokasi?)';
                $('#geoStatus').className = 'u-text-danger';
            });
        } else {
            $('#geoStatus').textContent = 'Lokasi: Tidak diperlukan';
        }

        sf.onsubmit = async (e) => {
            e.preventDefault();
            if(currentContract.requires_draw_signature) {
                const empty = !ctx.getImageData(0,0,cvs.width,cvs.height).data.some(c=>c!==0);
                if(empty) { alert('Tanda tangan wajib diisi!'); return; }
                $('[name="signature_image"]', sf).value = cvs.toDataURL();
            }
            
            const btn = $('button[type="submit"]', sf);
            const orig = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = 'Memproses...';

            try {
                const fd = new FormData(sf);
                const res = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                    body: fd
                });
                const j = await res.json();
                if(res.ok) {
                    window.toastOk('Berhasil', j.message);
                    setTimeout(() => location.reload(), 1000);
                } else throw new Error(j.message || 'Gagal memproses');
            } catch(err) {
                alert(err.message);
                btn.disabled = false; btn.innerHTML = orig;
            }
        };
        show(sm);
        document.body.classList.add('modal-open');
    };
    
    on($('#btnReject'), 'click', () => {
        const rm = $('#rejectModal');
        const rf = $('#rejectForm');
        rf.onsubmit = async (e) => {
            e.preventDefault();
            if(!confirm('Yakin reject kontrak ini?')) return;
            const fd = new FormData(rf);
            const res = await fetch(currentContract.reject_url, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                body: fd
            });
            const j = await res.json();
            if(res.ok) {
                window.toastOk('Berhasil', 'Kontrak direject.');
                setTimeout(() => location.reload(), 1000);
            } else alert('Gagal reject: ' + (j.message || 'Unknown error'));
        };
        show(rm);
        document.body.classList.add('modal-open');
    });
});
</script>
@endpush