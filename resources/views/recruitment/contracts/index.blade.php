@extends('layouts.app')

@section('title', 'Kontrak')

@section('content')
    @php
        $me = auth()->user();
        $meUnit = $me?->unit_id;
        $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
        
        // --- Config Helpers ---
        $statusOptions = [];
        foreach (config('recruitment.contract_statuses', []) as $key => $row) {
            $statusOptions[is_array($row) ? ($row['code'] ?? $key) : $key] = is_array($row) ? ($row['label'] ?? $row['code']) : $row;
        }

        $rawTypeConfig = config('recruitment.contract_types', []);
        $contractTypeOptions = [];
        foreach ($rawTypeConfig as $key => $row) {
            $contractTypeOptions[is_array($row) ? ($row['code'] ?? $key) : $key] = is_array($row) ? ($row['label'] ?? $row['code']) : $row;
        }
        
        $spkCfg = $rawTypeConfig['SPK'] ?? null;
        $pkwtNewCfg = $rawTypeConfig['PKWT_BARU'] ?? null;
        $pkwtExtCfg = $rawTypeConfig['PKWT_PERPANJANGAN'] ?? null;
        $pbCfg = $rawTypeConfig['PB_PENGAKHIRAN'] ?? null;

        // Filters State
        $currentUnitId = $selectedUnitId ?? $meUnit;
        $searchFilter = request('q', '');
        $statusFilter = request('status');
    @endphp

    <div class="u-card u-card--glass u-hover-lift">
        <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
            <div>
                <h2 class="u-title u-mb-xs">Penerbitan &amp; Penandatanganan Kontrak</h2>
                <p class="u-text-sm u-muted">
                    Monitoring draft–submit–review–e-sign kontrak kerja (SPK, PKWT, Perjanjian Bersama).<br>
                    <span class="u-text-xxs">Format: <code>(TYPE)-xxx/UNITCODE-mm/INISIAL-KEPALA/YYYY</code></span>
                </p>
            </div>
            @can('contract.create')
                <button type="button" class="u-btn u-btn--brand u-btn--sm u-hover-lift" data-modal-open="createContractModal">
                    <i class="fas fa-plus u-mr-xs"></i> Draft Kontrak
                </button>
            @endcan
        </div>

        @if (session('success'))
            @push('swal')
                <script>window.toastOk('Berhasil', {!! json_encode(session('success')) !!});</script>
            @endpush
        @endif

        @if ($errors->any())
            <div class="u-card u-mb-md u-error">
                <div class="u-flex u-items-center u-gap-sm u-mb-sm">
                    <i class="u-error-icon fas fa-exclamation-circle"></i>
                    <span class="u-font-semibold">Mohon periksa kembali isian berikut:</span>
                </div>
                <ul class="u-list">
                    @foreach ($errors->all() as $e)
                        <li class="u-item">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="u-flex u-items-center u-gap-md u-mb-md u-stack-mobile">
            <form method="get" class="u-flex u-items-center u-gap-md u-stack-mobile u-w-full u-flex-wrap">
                <div class="u-flex-1-min">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Unit Kerja</label>
                    @if ($canSeeAll)
                        <select name="unit_id" class="u-input u-input--sm" onchange="this.form.submit()">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" @selected((string)$currentUnitId === (string)$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="u-badge u-badge--glass u-text-xs">{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}</div>
                        <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                    @endif
                </div>
                <div class="u-flex-1-min">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Status</label>
                    <select name="status" class="u-input u-input--sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $code => $label)
                            <option value="{{ $code }}" @selected($statusFilter == $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="u-flex-1-min u-w-full u-max-w-xs u-ml-auto">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Cari</label>
                    <input type="search" name="q" id="contractsSearch" class="u-input u-input--sm" placeholder="No. Kontrak / Nama / Unit" value="{{ $searchFilter }}">
                </div>
            </form>
        </div>

        <div class="dt-wrapper u-mb-xl">
            <div class="u-scroll-x">
                <table id="contracts-table" class="u-table u-table-mobile" data-datatable="contracts" data-datatable-search="#contractsSearch">
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
                                $rawType = $c->contract_type;
                                $jenis = $contractTypeOptions[$rawType] ?? $rawType;
                                $unitName = $c->unit?->name ?? '—';
                                $sd = $c->start_date ? $c->start_date->format('d M Y') : '-';
                                $ed = $c->end_date ? $c->end_date->format('d M Y') : '-';
                                $periode = ($sd !== '-' && $ed !== '-') ? "$sd s/d $ed" : '—';
                                $st = $c->status;
                                $badge = match($st) {
                                    'signed' => 'u-badge--success',
                                    'approved' => 'u-badge--primary',
                                    'review' => 'u-badge--warn',
                                    'active' => 'u-badge--primary',
                                    'ended' => 'u-badge--muted',
                                    default => 'u-badge--glass'
                                };
                                $statusLbl = $statusOptions[$st] ?? ucfirst($st);
                                $flow = match($rawType) {
                                    'SPK' => match($st){ 'draft'=>'Draft SDM', 'review'=>'Review Kepala Unit', 'approved'=>'Approval Kandidat', 'signed'=>'Selesai', default=>'' },
                                    'PKWT_BARU','PKWT_PERPANJANGAN' => match($st){ 'draft'=>'Draft SDM', 'review'=>'Review Kepala Unit', 'approved'=>'e-Sign Kandidat', 'signed'=>'Selesai', default=>'' },
                                    'PB_PENGAKHIRAN' => match($st){ 'draft'=>'Draft SDM', 'review'=>'Review Kepala Unit', 'approved'=>'e-Sign Pihak', 'signed'=>'Selesai', default=>'' },
                                    default => ''
                                };
                            @endphp
                            <tr>
                                <td><span class="u-badge u-badge--glass u-text-xs font-mono">{{ $c->contract_no ?: '—' }}</span></td>
                                <td><span class="u-chip u-chip--soft">{{ $jenis }}</span></td>
                                <td>{{ $unitName }}</td>
                                <td class="u-text-sm">{{ $periode }}</td>
                                <td>
                                    <span class="u-badge {{ $badge }}">{{ $statusLbl }}</span>
                                    @if($flow)<div class="u-text-xxs u-muted u-mt-xxs">{{ $flow }}</div>@endif
                                </td>
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
                <div class="u-mt-md">{{ $contracts->links() }}</div>
            @endif
        </div>
    </div>

    @can('contract.create')
    <div id="createContractModal" class="u-modal" hidden aria-modal="true" role="dialog">
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
                
                <input type="hidden" name="source_contract_id" id="createSourceIdInput" value="{{ old('source_contract_id') }}">
                <input type="hidden" name="employee_id" id="createEmployeeIdInput" value="{{ old('employee_id') }}">
                <input type="hidden" name="person_id" id="createPersonIdInput" value="{{ old('person_id') }}">

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

                    <div data-mode-section="new" hidden class="u-space-y-md">
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div>
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar (Approved)</label>
                                <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                    <option value="">-- Pilih Pelamar --</option>
                                    @foreach ($applicants as $a)
                                        <option value="{{ $a->id }}" 
                                            data-person-id="{{ $a->person_id }}"
                                            data-fullname="{{ $a->full_name }}" 
                                            data-pos="{{ $a->position_applied ?? $a->position_name }}" 
                                            data-unit="{{ $a->unit_name }}" 
                                            @selected(old('applicant_id')==$a->id)>
                                            {{ $a->full_name }} — {{ $a->position_applied }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="createApplicantPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                    <div class="u-text-xs u-muted"><span id="capName">-</span> • <span id="capPos">-</span> • <span id="capUnit">-</span></div>
                                </div>
                            </div>
                            <div>
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">Hubungan Kerja</label>
                                <select name="employment_type" class="u-input">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($employmentTypes as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div data-mode-section="extend" hidden class="u-space-y-md">
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
                                        data-pos="{{ $c->position_name }}" 
                                        data-range="{{ $c->start_date }} s/d {{ $c->end_date }}" 
                                        @selected(old('source_contract_id') == $c->id)>
                                        {{ $c->person_name }} — {{ $c->position_name }} (End: {{ $c->end_date }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="createExtendPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                <div class="u-text-xs u-muted"><span id="cepName">-</span> • <span id="cepPos">-</span> • <span id="cepRange">-</span></div>
                            </div>
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Hubungan Kerja</label>
                            <select name="employment_type" class="u-input">
                                <option value="">-- Pilih --</option>
                                @foreach ($employmentTypes as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div data-mode-section="terminate" hidden class="u-space-y-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak PKWT Diakhiri</label>
                            <select id="createSourceTermSelect" class="u-input">
                                <option value="">-- Pilih Kontrak --</option>
                                @foreach ($expiringContracts as $c)
                                    <option value="{{ $c->id }}" 
                                        data-unit-id="{{ $c->unit_id }}" 
                                        data-person-id="{{ $c->person_id }}"
                                        data-employee-id="{{ $c->employee_id }}"
                                        data-person="{{ $c->person_name }}" 
                                        data-pos="{{ $c->position_name }}" 
                                        data-range="{{ $c->start_date }} s/d {{ $c->end_date }}" 
                                        @selected(old('source_contract_id') == $c->id)>
                                        {{ $c->person_name }} — {{ $c->position_name }} (End: {{ $c->end_date }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="createTermPreview" class="u-card u-card--glass u-p-sm u-mt-xs" hidden>
                                <div class="u-text-xs u-muted"><span id="ctpName">-</span> • <span id="ctpPos">-</span> • <span id="ctpRange">-</span></div>
                            </div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div>
                                <label class="u-text-xs u-font-medium d-block">Efektif Pengakhiran</label>
                                <input type="date" name="pb_effective_end" class="u-input" value="{{ old('pb_effective_end') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium d-block">Kompensasi (Rp)</label>
                                <input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true" data-terbilang-target="pb_compensation_amount_words" value="{{ old('pb_compensation_amount') }}">
                            </div>
                        </div>
                        <div>
                            <label class="u-text-xs u-font-medium d-block">Terbilang (Otomatis)</label>
                            <input type="text" name="pb_compensation_amount_words" class="u-input" readonly value="{{ old('pb_compensation_amount_words') }}">
                        </div>
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md">
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Jabatan</label>
                            <input type="text" name="position_name" class="u-input" list="positionList" value="{{ old('position_name') }}">
                            <datalist id="positionList">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Catatan (Opsional)</label>
                            <input type="text" name="remarks" class="u-input" value="{{ old('remarks') }}">
                        </div>
                    </div>
                    <div class="u-grid-2 u-stack-mobile u-gap-md" data-mode-hide="terminate">
                        <div><label class="u-text-sm u-font-medium d-block">Mulai</label><input type="date" name="start_date" class="u-input" value="{{ old('start_date') }}"></div>
                        <div><label class="u-text-sm u-font-medium d-block">Selesai</label><input type="date" name="end_date" class="u-input" value="{{ old('end_date') }}"></div>
                    </div>

                    <div id="createRemun" data-mode-hide="terminate" class="u-card u-card--soft u-p-md u-space-y-md">
                        <div class="u-text-sm u-font-semibold">Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div>
                                <label class="u-text-xs d-block">Gaji Pokok</label>
                                <input type="text" name="salary_amount" class="u-input" data-rupiah="true" data-terbilang-target="salary_amount_words" value="{{ old('salary_amount') }}">
                                <input type="text" name="salary_amount_words" class="u-input u-mt-xs" readonly value="{{ old('salary_amount_words') }}">
                            </div>
                            <div>
                                <label class="u-text-xs d-block">Uang Makan/Hari</label>
                                <input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true" data-terbilang-target="lunch_allowance_words" value="{{ old('lunch_allowance_daily') }}">
                                <input type="text" name="lunch_allowance_words" class="u-input u-mt-xs" readonly value="{{ old('lunch_allowance_words') }}">
                            </div>
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
                    </div>

                    <div class="u-flex u-gap-md u-flex-wrap">
                        <label class="u-text-xs"><input type="checkbox" name="requires_camera" value="1" @checked(old('requires_camera', 1))> Kamera</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_geolocation" value="1" @checked(old('requires_geolocation', 1))> Lokasi</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_draw_signature" value="1" @checked(old('requires_draw_signature', 1))> Ttd Digital</label>
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

    <div id="editContractModal" class="u-modal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head">
                <div class="u-title">Edit Draft</div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="u-modal__body u-p-md u-space-y-lg" id="editContractForm">
                @csrf @method('PUT')
                <input type="hidden" name="contract_type" id="editTypeInput">
                <input type="hidden" name="source_contract_id" id="editSourceIdInput">
                <input type="hidden" name="unit_id" id="editUnitIdInput">
                <input type="hidden" name="mode" id="editModeInput">
                
                <input type="hidden" name="employee_id" id="editEmployeeId">
                <input type="hidden" name="person_id" id="editPersonId">

                <div id="editFormContent">
                    <div class="u-grid-2 u-stack-mobile u-gap-md">
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Pelamar / Karyawan</label>
                            <input type="text" id="editPersonName" class="u-input" readonly disabled>
                            <input type="hidden" name="applicant_id" id="editApplicantId">
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium d-block">Hubungan Kerja</label>
                            <select name="employment_type" id="editEmploymentType" class="u-input">
                                @foreach ($employmentTypes as $opt)
                                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium d-block">Jabatan</label>
                        <input type="text" name="position_name" id="editPositionName" class="u-input" list="positionListEdit">
                        <datalist id="positionListEdit">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                    </div>
                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md" id="editDates">
                        <div><label class="u-text-sm d-block">Mulai</label><input type="date" name="start_date" id="editStartDate" class="u-input"></div>
                        <div><label class="u-text-sm d-block">Selesai</label><input type="date" name="end_date" id="editEndDate" class="u-input"></div>
                    </div>
                    <div id="editPbFields" class="u-grid-2 u-stack-mobile u-gap-md u-mt-md" hidden>
                        <div><label class="u-text-sm d-block">Efektif Pengakhiran</label><input type="date" name="pb_effective_end" id="editPbEnd" class="u-input"></div>
                        <div>
                            <label class="u-text-sm d-block">Kompensasi</label>
                            <input type="text" name="pb_compensation_amount" id="editPbComp" class="u-input" data-rupiah="true" data-terbilang-target="editPbCompW">
                            <input type="text" name="pb_compensation_amount_words" id="editPbCompW" class="u-input u-mt-xxs" readonly>
                        </div>
                    </div>
                    <div id="editRemun" class="u-card u-card--soft u-p-md u-space-y-md u-mt-md">
                        <div class="u-text-sm u-font-semibold">Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-md">
                            <div>
                                <label class="u-text-xs d-block">Gaji Pokok</label>
                                <input type="text" name="salary_amount" id="editSalary" class="u-input" data-rupiah="true" data-terbilang-target="editSalaryW">
                                <input type="text" name="salary_amount_words" id="editSalaryW" class="u-input u-mt-xs" readonly>
                            </div>
                            <div>
                                <label class="u-text-xs d-block">Uang Makan</label>
                                <input type="text" name="lunch_allowance_daily" id="editLunch" class="u-input" data-rupiah="true" data-terbilang-target="editLunchW">
                                <input type="text" name="lunch_allowance_words" id="editLunchW" class="u-input u-mt-xs" readonly>
                            </div>
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
                        <label class="u-text-xs"><input type="checkbox" name="requires_camera" id="editCam" value="1"> Kamera</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_geolocation" id="editGeo" value="1"> Lokasi</label>
                        <label class="u-text-xs"><input type="checkbox" name="requires_draw_signature" id="editDraw" value="1"> Ttd Digital</label>
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

    <div id="detailContractModal" class="u-modal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--xl">
            <div class="u-modal__head">
                <div class="u-title">Detail Kontrak</div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button>
            </div>
            <div class="u-modal__body u-p-md u-space-y-md">
                <div class="u-card u-card--border u-p-sm u-grid-2 u-gap-sm">
                    <div><div class="u-text-xs u-muted">Nomor</div><div class="u-font-mono" id="detNo">-</div></div>
                    <div><div class="u-text-xs u-muted">Status</div><div id="detStatus">-</div></div>
                    <div><div class="u-text-xs u-muted">Unit</div><div id="detUnit">-</div></div>
                    <div><div class="u-text-xs u-muted">Jenis</div><div id="detType">-</div></div>
                </div>
                <div class="u-card u-card--border u-p-sm u-grid-2 u-gap-sm">
                    <div><div class="u-text-xs u-muted">Nama</div><div id="detName">-</div></div>
                    <div><div class="u-text-xs u-muted">Jabatan</div><div id="detPos">-</div></div>
                    <div><div class="u-text-xs u-muted">Periode</div><div id="detPeriod">-</div></div>
                    <div><div class="u-text-xs u-muted">Hubungan Kerja</div><div id="detEmpType">-</div></div>
                </div>
                <div id="detRemunBox" class="u-card u-card--soft u-p-sm u-space-y-sm" hidden>
                    <div class="u-text-sm u-font-semibold">Remunerasi</div>
                    <div class="u-grid-2 u-gap-sm">
                        <div><div class="u-text-xs u-muted">Gaji Pokok</div><div id="detSalary">-</div><div class="u-text-xxs u-muted" id="detSalaryW"></div></div>
                        <div><div class="u-text-xs u-muted">Uang Makan</div><div id="detLunch">-</div><div class="u-text-xxs u-muted" id="detLunchW"></div></div>
                    </div>
                    <div id="detAllowances" class="u-grid-2 u-gap-sm u-border-t u-pt-sm"></div> </div>
                <div id="detPbBox" class="u-card u-card--soft u-p-sm u-space-y-sm" hidden>
                    <div class="u-text-sm u-font-semibold">Kompensasi PB</div>
                    <div class="u-grid-2 u-gap-sm">
                        <div><div class="u-text-xs u-muted">Nilai</div><div id="detPbVal">-</div><div class="u-text-xxs u-muted" id="detPbValW"></div></div>
                        <div><div class="u-text-xs u-muted">Efektif</div><div id="detPbEff">-</div></div>
                    </div>
                </div>
                <div id="detApprovalInfo" class="u-card u-card--glass u-p-sm" hidden>
                    <div class="u-text-xs u-font-semibold">Approval</div>
                    <div id="detAppStatus" class="u-text-sm">-</div>
                    <div id="detAppNote" class="u-text-xs u-muted">-</div>
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost" data-modal-close>Tutup</button>
                <button type="button" id="btnReject" class="u-btn u-btn--danger" hidden>Reject</button>
                <button type="button" id="btnApprove" class="u-btn u-btn--brand" hidden>Approve & Sign</button>
                <button type="button" id="btnSign" class="u-btn u-btn--primary" hidden>Sign</button>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="u-modal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--sm">
            <div class="u-modal__head"><div class="u-title">Reject Kontrak</div></div>
            <form id="rejectForm" class="u-modal__body u-p-md u-space-y-md">
                <p class="u-text-xs u-muted">Kembalikan status ke Draft untuk diperbaiki SDM Unit.</p>
                <textarea name="note" class="u-input" rows="3" placeholder="Alasan reject..."></textarea>
                <div class="u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--danger">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <div id="signModal" class="u-modal" hidden>
        <div class="u-modal__backdrop" data-modal-close></div>
        <div class="u-modal__card u-modal__card--md">
            <div class="u-modal__head"><div class="u-title">Tanda Tangan</div></div>
            <form id="signForm" class="u-modal__body u-p-md u-space-y-md">
                <p class="u-text-xs u-muted">Bubuhkan tanda tangan pada area di bawah ini.</p>
                <div class="u-card u-card--border u-p-xs">
                    <canvas id="signCanvas" width="400" height="200" style="width:100%;height:200px;touch-action:none;background:#fff"></canvas>
                </div>
                <div class="u-flex u-justify-between u-text-xs">
                    <button type="button" id="clearSign" class="u-text-brand">Bersihkan</button>
                    <span id="geoStatus" class="u-muted">Lokasi: Menunggu...</span>
                </div>
                <input type="hidden" name="signature_image">
                <input type="hidden" name="geo_lat"><input type="hidden" name="geo_lng"><input type="hidden" name="geo_accuracy">
                <textarea name="note" class="u-input" rows="2" placeholder="Catatan opsional..."></textarea>
                <div class="u-flex u-justify-end u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--brand">Simpan Tanda Tangan</button>
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
    const show = el => el && (el.hidden = false);
    const hide = el => el && (el.hidden = true);
    const text = (el, t) => el && (el.textContent = t || '-');

    // --- Auto Terbilang ---
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
                if(target) target.value = val ? (terbilang(val) + ' rupiah').toUpperCase() : '';
            };
            on(el, 'input', update);
            update(); // init
        });
    };

    // --- Create Modal Logic ---
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

        // Logic switch section
        const switchSection = (mode) => {
            $$('[data-mode-section]', cForm).forEach(el => el.hidden = el.dataset.modeSection !== mode);
            const remun = $('#createRemun');
            if(remun) remun.hidden = remun.dataset.modeHide === mode;
            show(mainSec);
        };

        const resetSource = () => {
             // Hanya reset input visual, jangan hapus hidden ID sembarangan kecuali user ganti unit
             // srcIdInp tidak di-reset disini
             hide($('#createExtendPreview'));
             hide($('#createTermPreview'));
        };

        on(famSel, 'change', () => {
            const val = famSel.value;
            const opt = famSel.options[famSel.selectedIndex];
            $('#createFamilyHint').textContent = opt.dataset.hint || '';
            subSel.value = '';
            
            if(val === 'PKWT') {
                show(subWrap);
                hide(mainSec);
                typeInp.value = '';
            } else {
                hide(subWrap);
                typeInp.value = (val === 'SPK') ? 'SPK' : 'PB_PENGAKHIRAN';
                modeInp.value = opt.dataset.mode;
                switchSection(opt.dataset.mode);
            }
        });

        on(subSel, 'change', () => {
            const val = subSel.value;
            const opt = subSel.options[subSel.selectedIndex];
            if(val) {
                typeInp.value = val;
                modeInp.value = opt.dataset.mode;
                switchSection(opt.dataset.mode);
            } else {
                hide(mainSec);
            }
        });

        // Filter Source Contract by Unit
        const filterSource = (uId) => {
            ['#createSourceExtendSelect', '#createSourceTermSelect'].forEach(selId => {
                const sel = $(selId);
                if(!sel) return;
                [...sel.options].forEach(opt => {
                    if(!opt.value) return; 
                    const show = opt.dataset.unitId == uId;
                    opt.hidden = !show;
                    opt.disabled = !show;
                });
            });
        };

        if(unitSel) on(unitSel, 'change', () => {
            // Jika user MANUAL ganti unit, baru reset ID kontrak dasar
            if(srcIdInp) srcIdInp.value = ''; 
            if(empIdInp) empIdInp.value = ''; 
            if(perIdInp) perIdInp.value = ''; 
            ['#createSourceExtendSelect', '#createSourceTermSelect'].forEach(id => { if($(id)) $(id).value = ''; });
            resetSource();
            filterSource(unitSel.value);
        });

        // Init filter (tanpa reset)
        const initUnitId = unitSel ? unitSel.value : ($('input[name="unit_id"]')?.value || '');
        if(initUnitId) filterSource(initUnitId);

        // Previews
        on($('#createApplicantSelect'), 'change', function() {
            const o = this.options[this.selectedIndex];
            const p = $('#createApplicantPreview');
            if(this.value){
                if(empIdInp) empIdInp.value = ''; 
                if(perIdInp) perIdInp.value = o.dataset.personId || '';
                text($('#capName'), o.dataset.fullname);
                text($('#capPos'), o.dataset.pos);
                text($('#capUnit'), o.dataset.unit);
                show(p);
            } else hide(p);
        });
        
        const updateSrcPreview = (sel, pId, pre) => {
            const o = sel.options[sel.selectedIndex];
            const p = $(pId);
            if(sel.value) {
                srcIdInp.value = sel.value; // Set Hidden ID
                if(empIdInp) empIdInp.value = o.dataset.employeeId || '';
                if(perIdInp) perIdInp.value = o.dataset.personId || '';

                text($(`#${pre}Name`), o.dataset.person);
                text($(`#${pre}Pos`), o.dataset.pos);
                text($(`#${pre}Range`), o.dataset.range);
                show(p);
            } else {
                hide(p);
            }
        };
        on($('#createSourceExtendSelect'), 'change', function(){ updateSrcPreview(this, '#createExtendPreview', 'cep'); });
        on($('#createSourceTermSelect'), 'change', function(){ updateSrcPreview(this, '#createTermPreview', 'ctp'); });

        // Submit Validation
        cForm.onsubmit = (e) => {
            const mode = modeInp.value;
            // Jika mode butuh source contract
            if ((mode === 'extend' || mode === 'terminate') && !srcIdInp.value) {
                e.preventDefault();
                alert('Harap pilih Kontrak Dasar (Existing PKWT)!');
                return false;
            }
            // Pastikan unit_id terisi
            const uVal = unitSel ? unitSel.value : ($('input[name="unit_id"]')?.value);
            if(!uVal) {
                e.preventDefault();
                alert('Harap pilih Unit Kerja!');
                return false;
            }
        };
    }

    // --- Restore State on Error ---
    @if($errors->any())
        const oldType = "{{ old('contract_type') }}";
        if(oldType) {
            const openBtn = $('[data-modal-open="createContractModal"]');
            if(openBtn) {
                setTimeout(() => {
                    openBtn.click();
                    // Restore types
                    if(oldType === 'SPK') { 
                        $('#createFamilySelect').value='SPK'; 
                        $('#createFamilySelect').dispatchEvent(new Event('change')); 
                    } else if(oldType.includes('PKWT')) { 
                        $('#createFamilySelect').value='PKWT'; 
                        $('#createFamilySelect').dispatchEvent(new Event('change'));
                        $('#createSubtypeSelect').value=oldType;
                        $('#createSubtypeSelect').dispatchEvent(new Event('change'));
                    } else if(oldType === 'PB_PENGAKHIRAN') { 
                        $('#createFamilySelect').value='PB'; 
                        $('#createFamilySelect').dispatchEvent(new Event('change')); 
                    }
                    
                    // Restore Source ID manually
                    const oldSrc = "{{ old('source_contract_id') }}";
                    if(oldSrc) {
                        const srcIdInp = $('#createSourceIdInput');
                        if(srcIdInp) srcIdInp.value = oldSrc;

                        const selEx = $('#createSourceExtendSelect');
                        const selTm = $('#createSourceTermSelect');
                        if(selEx && !selEx.closest('[hidden]')) { selEx.value = oldSrc; selEx.dispatchEvent(new Event('change')); }
                        if(selTm && !selTm.closest('[hidden]')) { selTm.value = oldSrc; selTm.dispatchEvent(new Event('change')); }
                    }
                }, 300);
            }
        }
    @endif

    // --- Detail & Actions ---
    let currentContract = null;
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-contract-detail]');
        if(btn) {
            const url = btn.dataset.showUrl;
            const res = await fetch(url).then(r=>r.json());
            const d = res.data;
            currentContract = d;
            
            // Populate Detail
            text($('#detNo'), d.contract_no);
            text($('#detStatus'), d.status);
            text($('#detUnit'), d.unit?.name);
            text($('#detType'), d.contract_type_label);
            text($('#detName'), d.person_name);
            text($('#detPos'), d.position_name);
            text($('#detPeriod'), `${d.start_date||'-'} s/d ${d.end_date||'-'}`);
            text($('#detEmpType'), d.employment_type);

            // Populate Remun (fix 0 issue)
            // Use specific field or remuneration_json fallback
            const meta = d.remuneration_json || d.remuneration || d.meta || {};
            
            if(d.contract_type === 'PB_PENGAKHIRAN') {
                hide($('#detRemunBox'));
                show($('#detPbBox'));
                text($('#detPbVal'), money(meta.pb_compensation_amount));
                text($('#detPbValW'), meta.pb_compensation_amount_words);
                text($('#detPbEff'), meta.pb_effective_end);
            } else {
                show($('#detRemunBox'));
                hide($('#detPbBox'));
                text($('#detSalary'), money(meta.salary_amount));
                text($('#detSalaryW'), meta.salary_amount_words);
                text($('#detLunch'), money(meta.lunch_allowance_daily));
                text($('#detLunchW'), meta.lunch_allowance_words);
                
                const allws = [];
                if(meta.allowance_position_amount) allws.push(['T. Jabatan', meta.allowance_position_amount]);
                if(meta.allowance_communication_amount) allws.push(['T. Komunikasi', meta.allowance_communication_amount]);
                if(meta.allowance_special_amount) allws.push(['T. Khusus', meta.allowance_special_amount]);
                if(meta.allowance_other_amount) allws.push(['Lainnya', meta.allowance_other_amount]);
                
                $('#detAllowances').innerHTML = allws.map(x => `<div><div class="u-text-xs u-muted">${x[0]}</div><div>${money(x[1])}</div></div>`).join('');
            }

            const appBox = $('#detApprovalInfo');
            if(d.approval) {
                show(appBox);
                text($('#detAppStatus'), d.approval.status);
                text($('#detAppNote'), d.approval.note);
            } else hide(appBox);

            const bApp = $('#btnApprove'), bSign = $('#btnSign'), bRej = $('#btnReject');
            if(bApp) bApp.hidden = !d.can_approve;
            if(bRej) bRej.hidden = !d.can_approve;
            if(bSign) bSign.hidden = !d.can_sign;
            
            const dm = $('#detailContractModal');
            dm.hidden = false;
            document.body.classList.add('modal-open');
        }

        const editBtn = e.target.closest('[data-contract-edit]');
        if(editBtn) {
            const url = editBtn.dataset.showUrl;
            const res = await fetch(url).then(r=>r.json());
            const d = res.data;
            const meta = d.remuneration_json || d.remuneration || {};
            const frm = $('#editContractForm');
            frm.action = editBtn.dataset.updateUrl;
            
            $('#editTypeInput').value = d.contract_type;
            $('#editSourceIdInput').value = d.source_contract_id;
            $('#editUnitIdInput').value = d.unit_id;
            $('#editEmployeeId').value = d.employee_id;
            $('#editPersonId').value = d.person_id;
            
            $('#editPersonName').value = d.person_name;
            $('#editPositionName').value = d.position_name;
            $('#editStartDate').value = d.start_date;
            $('#editEndDate').value = d.end_date;
            $('#editEmploymentType').value = d.employment_type;
            
            if(d.contract_type === 'PB_PENGAKHIRAN') {
                show($('#editPbFields'));
                hide($('#editRemun'));
                hide($('#editDates'));
                $('#editPbEnd').value = meta.pb_effective_end;
                $('#editPbComp').value = meta.pb_compensation_amount;
            } else {
                hide($('#editPbFields'));
                show($('#editRemun'));
                show($('#editDates'));
                $('#editSalary').value = meta.salary_amount;
                $('#editLunch').value = meta.lunch_allowance_daily;
                $('#editAP').value = meta.allowance_position_amount;
                $('#editAC').value = meta.allowance_communication_amount;
                $('#editAS').value = meta.allowance_special_amount;
                $('#editAO').value = meta.allowance_other_amount;
                $('#editAOD').value = meta.allowance_other_desc;
                $('#editOB').value = meta.other_benefits_desc;
            }

            $('#editCam').checked = !!d.requires_camera;
            $('#editGeo').checked = !!d.requires_geolocation;
            $('#editDraw').checked = !!d.requires_draw_signature;

            bindRupiahAndTerbilang(frm);
            $('#editContractModal').hidden = false;
            document.body.classList.add('modal-open');
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
            return { x: (e.clientX||e.touches[0].clientX)-r.left, y: (e.clientY||e.touches[0].clientY)-r.top };
        };
        const start = (e) => { isDraw=true; ctx.beginPath(); const p=getPos(e); ctx.moveTo(p.x, p.y); e.preventDefault(); };
        const move = (e) => { if(!isDraw)return; const p=getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); };
        const end = () => { isDraw=false; };
        
        ['mousedown','touchstart'].forEach(ev=>cvs.addEventListener(ev, start));
        ['mousemove','touchmove'].forEach(ev=>cvs.addEventListener(ev, move));
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

        sm.hidden = false;
        document.body.classList.add('modal-open');
    };

    on($('#btnApprove'), 'click', () => handleSignAction(currentContract.approve_url));
    on($('#btnSign'), 'click', () => handleSignAction(currentContract.sign_url));
    
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
        rm.hidden = false;
        document.body.classList.add('modal-open');
    });
});
</script>
@endpush