@extends('layouts.app')
@section('title','Penerbitan & Penandatanganan Kontrak')

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    /** @var \App\Models\User|null $me */
    $me     = auth()->user();
    $meUnit = $me?->unit_id;

    $canSeeAll      = $canSeeAll      ?? false;
    $selectedUnitId = $selectedUnitId ?? null;
    $units          = $units          ?? collect();
    $contracts      = $contracts      ?? ($list ?? collect());
    $statusOptions  = collect($statusOptions ?? []);

    if ($units->isEmpty()) {
        $units = $canSeeAll
            ? DB::table('units')->select('id','name')->orderBy('name')->get()
            : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
    }

    if (! $canSeeAll) {
        $selectedUnitId = (int) $meUnit;
    }

    $statusFilter = request('status');
    $searchFilter = request('q');

    $contractTypes        = $contractTypes        ?? [];
    $contractTypeConfigs  = $contractTypeConfigs  ?? [];
    $employmentTypes      = $employmentTypes      ?? [];
    $applicants           = $applicants           ?? collect();
    $expiringContracts    = $expiringContracts    ?? collect();

    $contractTypeMap = collect($contractTypeConfigs)->keyBy('code');
    $spkCfg          = $contractTypeMap->get('SPK');
    $pkwtNewCfg      = $contractTypeMap->get('PKWT_BARU');
    $pkwtExtCfg      = $contractTypeMap->get('PKWT_PERPANJANGAN');
    $pbCfg           = $contractTypeMap->get('PB_PENGAKHIRAN');
@endphp

<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
        <div>
            <h2 class="u-title u-mb-sm">Penerbitan &amp; Penandatanganan Kontrak</h2>
            <p class="u-text-sm u-muted">
                Monitoring draft–publish–e-sign kontrak kerja (SPK, PKWT, Perjanjian Bersama).
            </p>
        </div>

        @can('contract.create')
        <button type="button"
                class="u-btn u-btn--brand u-btn--sm u-hover-lift"
                data-modal-open="createContractModal">
            <i class="fas fa-plus u-mr-xs"></i> Draft Kontrak
        </button>
        @endcan
    </div>

    {{-- Toast sukses --}}
    @if(session('ok'))
        @push('swal')
            <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script>
        @endpush
    @endif

    {{-- Error box --}}
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
            {{-- Unit filter --}}
            @if($canSeeAll)
                <div class="u-flex-1-min">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Unit Kerja</label>
                    <select name="unit_id" class="u-input u-input--sm" onchange="this.form.submit()">
                        <option value="">Semua Unit</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}"
                                    @selected((string)($selectedUnitId ?? '') === (string)$u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                @php $unitName = ($units[0]->name ?? 'Unit Saya'); @endphp
                <div class="u-flex-1-min">
                    <label class="u-text-xs u-font-medium u-mb-xs d-block">Unit Kerja</label>
                    <div class="u-badge u-badge--glass u-text-xs">{{ $unitName }}</div>
                    <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                </div>
            @endif

            {{-- Status --}}
            <div class="u-flex-1-min">
                <label class="u-text-xs u-font-medium u-mb-xs d-block">Status Kontrak</label>
                <select name="status" class="u-input u-input--sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $st)
                        @php
                            $code  = $st['code']  ?? ($st->code  ?? null);
                            $label = $st['label'] ?? ($st->label ?? $code);
                        @endphp
                        @if($code)
                            <option value="{{ $code }}" @selected($statusFilter === $code)>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Search eksternal --}}
            <div class="u-flex-1-min u-w-full u-max-w-xs u-ml-auto">
                <label class="u-text-xs u-font-medium u-mb-xs d-block">Cari</label>
                <input type="search"
                       name="q"
                       id="contractsSearch"
                       class="u-input u-input--sm"
                       placeholder="Nama / No. Kontrak / Posisi"
                       value="{{ $searchFilter }}">
            </div>
        </form>
    </div>

    {{-- LIST KONTRAK --}}
    <div class="dt-wrapper u-mb-xl">
        <div class="u-flex u-items-center u-justify-between u-mb-sm u-stack-mobile">
            <div class="u-font-semibold">Monitoring Kontrak</div>
            <span class="u-badge u-badge--glass u-text-xs u-mt-xs-sm">
                {{ $canSeeAll && !$selectedUnitId ? 'Semua Unit' : 'Unit ID: '.($selectedUnitId ?? $meUnit) }}
            </span>
        </div>

        <div class="u-scroll-x">
            <table id="contracts-table"
                   class="u-table u-table-mobile"
                   data-datatable="contracts"
                   data-datatable-search="#contractsSearch">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Kontrak</th>
                        <th>Jenis</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>e-Sign</th>
                        <th class="cell-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $idx => $c)
                        @php
                            if (!is_object($c)) continue;

                            $jenis    = $c->contract_type ?? '—';
                            $unitName = optional($c->unit)->name ?? '—';

                            $start = $c->start_date ? Carbon::parse($c->start_date) : null;
                            $end   = $c->end_date   ? Carbon::parse($c->end_date)   : null;

                            $periode = ($start && $end)
                                ? $start->format('d M Y').' s/d '.$end->format('d M Y')
                                : 's/d';

                            $st = $c->status ?? 'draft';

                            $statusBadgeClass =
                                $st === 'signed'           ? 'u-badge--success' :
                                ($st === 'approved'        ? 'u-badge--primary' :
                                ($st === 'review'          ? 'u-badge--warn' :
                                ($st === 'active'          ? 'u-badge--primary' :
                                ($st === 'ended'           ? 'u-badge--muted' : 'u-badge--glass'))));

                            $eSignLabel =
                                $st === 'signed'            ? 'signed' :
                                ($st === 'signed_candidate' ? 'candidate' :
                                ($st === 'approved'         ? 'ready' : 'draft'));

                            $eSignBadgeClass =
                                in_array($st, ['signed','signed_candidate']) ? 'u-badge--success' : 'u-badge--glass';
                        @endphp
                        <tr>
                            <td>{{ $contracts->firstItem() + $idx }}</td>
                            <td>
                                <span class="u-badge u-badge--glass u-text-xs">
                                    {{ $c->contract_no ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $jenis }}</td>
                            <td>{{ $unitName }}</td>
                            <td class="u-text-sm">{{ $periode }}</td>
                            <td>
                                <span class="u-badge {{ $statusBadgeClass }}">
                                    {{ ucfirst(str_replace('_',' ',$st)) }}
                                </span>
                            </td>
                            <td>
                                <span class="u-badge {{ $eSignBadgeClass }}">
                                    {{ ucfirst($eSignLabel) }}
                                </span>
                            </td>
                            <td class="cell-actions">
                                <div class="cell-actions__group">
                                    {{-- TODO: tombol Submit / Approve / E-Sign --}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($contracts instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="u-mt-md">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL DRAFT KONTRAK BARU --}}
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
                        Susun draft kontrak (SPK / PKWT Baru / Perpanjangan / Pengakhiran) sesuai flow Rekrutmen SI.
                    </div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST"
              action="{{ route('recruitment.contracts.store') }}"
              class="u-modal__body u-p-md u-space-y-lg"
              id="createContractForm">
            @csrf
            <input type="hidden" name="mode" id="contractModeInput" value="{{ old('mode') }}">
            <input type="hidden" name="source_contract_id" id="sourceContractInput" value="{{ old('source_contract_id') }}">
            <input type="hidden" name="contract_type" id="contractTypeInput" value="{{ old('contract_type') }}">

            {{-- STEP 1: JENIS KONTRAK --}}
            <div class="u-space-y-sm">
                <label class="u-text-sm u-font-medium d-block">Pilih Jenis Kontrak</label>

                <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div>
                        {{-- LEVEL 1: FAMILY (SPK vs PKWT) --}}
                        <select id="contractFamilySelect" class="u-input" required>
                            <option value="">Pilih jenis kontrak...</option>
                            @if($spkCfg)
                                <option value="SPK"
                                        data-mode="{{ $spkCfg['mode'] ?? 'new' }}"
                                        data-hint="{{ $spkCfg['description'] ?? 'Surat Penawaran Kerja (SPK).' }}"
                                        @selected(old('contract_type') === 'SPK')>
                                    {{ $spkCfg['label'] ?? 'Surat Penawaran Kerja (SPK)' }}
                                </option>
                            @else
                                <option value="SPK"
                                        data-mode="new"
                                        data-hint="Surat Penawaran Kerja (SPK) untuk kandidat baru."
                                        @selected(old('contract_type') === 'SPK')>
                                    Surat Penawaran Kerja (SPK)
                                </option>
                            @endif

                            {{-- Semua varian PKWT disatukan --}}
                            <option value="PKWT"
                                    data-hint="Pilih detail PKWT: baru, perpanjangan, atau pengakhiran (PB)."
                                    @selected(in_array(old('contract_type'), ['PKWT_BARU','PKWT_PERPANJANGAN','PB_PENGAKHIRAN']))>
                                PKWT (Baru / Perpanjangan / Pengakhiran)
                            </option>
                        </select>
                        <p id="contractFamilyHint" class="u-text-xs u-muted u-mt-xs"></p>
                    </div>

                    <div class="u-hidden-mobile">
                        <div class="u-text-xs u-muted">
                            <span class="u-font-semibold">Catatan:</span><br>
                            • SPK untuk offering kandidat baru.<br>
                            • PKWT digunakan untuk kontrak kerja PKWT baru, perpanjangan, maupun PB pengakhiran.
                        </div>
                    </div>
                </div>

                {{-- LEVEL 2: DETAIL PKWT (muncul hanya jika family = PKWT) --}}
                <div id="contractSubtypeWrapper" class="u-mt-sm" hidden>
                    <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                        Detail PKWT
                    </label>
                    <select id="contractSubtypeSelect" class="u-input">
                        <option value="">Pilih detail PKWT...</option>
                        @if($pkwtNewCfg)
                            <option value="PKWT_BARU"
                                    data-mode="{{ $pkwtNewCfg['mode'] ?? 'new' }}"
                                    data-hint="{{ $pkwtNewCfg['description'] ?? 'PKWT pertama untuk kandidat baru.' }}"
                                    @selected(old('contract_type') === 'PKWT_BARU')>
                                {{ $pkwtNewCfg['label'] ?? 'PKWT Baru' }}
                            </option>
                        @endif
                        @if($pkwtExtCfg)
                            <option value="PKWT_PERPANJANGAN"
                                    data-mode="{{ $pkwtExtCfg['mode'] ?? 'extend' }}"
                                    data-hint="{{ $pkwtExtCfg['description'] ?? 'Perpanjangan PKWT dari kontrak aktif yang akan berakhir.' }}"
                                    @selected(old('contract_type') === 'PKWT_PERPANJANGAN')>
                                {{ $pkwtExtCfg['label'] ?? 'Perpanjangan PKWT' }}
                            </option>
                        @endif
                        @if($pbCfg)
                            <option value="PB_PENGAKHIRAN"
                                    data-mode="{{ $pbCfg['mode'] ?? 'extend' }}"
                                    data-hint="{{ $pbCfg['description'] ?? 'Perjanjian Bersama pengakhiran PKWT.' }}"
                                    @selected(old('contract_type') === 'PB_PENGAKHIRAN')>
                                {{ $pbCfg['label'] ?? 'PB Pengakhiran PKWT' }}
                            </option>
                        @endif
                    </select>
                    <p id="contractSubtypeHint" class="u-text-xs u-muted u-mt-xxs"></p>
                </div>
            </div>

            {{-- STEP 2: UNIT & DETAIL FORM (muncul setelah jenis kontrak final) --}}
            <div id="contractFormSections" class="u-space-y-lg" hidden>

                {{-- UNIT KERJA --}}
                <div id="unitFieldWrapper" class="u-mt-md" hidden>
                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>
                    @if($canSeeAll)
                        <select name="unit_id" class="u-input">
                            <option value="">Pilih Unit</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}"
                                        @selected(old('unit_id', $selectedUnitId ?? $meUnit) == $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        @php $unitName = ($units[0]->name ?? 'Unit Saya'); @endphp
                        <input type="hidden" name="unit_id" value="{{ $meUnit }}">
                        <div class="u-badge u-badge--glass">{{ $unitName }}</div>
                    @endif
                </div>

                {{-- MODE: NEW (SPK / PKWT Baru) --}}
                <div data-mode-section="new" hidden>
                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar / Kandidat</label>
                            <select name="applicant_id" id="applicantSelect" class="u-input">
                                <option value="">Pilih Pelamar</option>
                                @foreach($applicants as $a)
                                    @php
                                        $appUnitName  = optional($a->unit)->name ?? 'Unit ?';
                                        $appPosition  = $a->position_applied ?? ($a->position_name ?? 'Posisi belum diisi');
                                    @endphp
                                    <option value="{{ $a->id }}"
                                            data-full-name="{{ $a->full_name }}"
                                            data-position="{{ $appPosition }}"
                                            data-unit="{{ $appUnitName }}"
                                            @selected(old('applicant_id') == $a->id)>
                                        {{ $a->full_name }} — {{ $appPosition }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="u-text-xs u-muted u-mt-xxs">
                                Data kandidat dari Monitoring Rekrutmen (status <strong>APPROVED / READY FOR CONTRACT</strong>).
                            </p>

                            {{-- Preview Dinamis Pelamar --}}
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
                                @foreach($employmentTypes as $val => $label)
                                    <option value="{{ $val }}" @selected(old('employment_type') == $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Nama Jabatan</label>
                        <input type="text"
                               name="position_name"
                               class="u-input"
                               placeholder="Mis. Staff HCIS, Analis TKDN, dsb."
                               value="{{ old('position_name') }}">
                    </div>

                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai</label>
                            <input type="date"
                                   name="start_date"
                                   class="u-input"
                                   value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Selesai</label>
                            <input type="date"
                                   name="end_date"
                                   class="u-input"
                                   value="{{ old('end_date') }}">
                        </div>
                    </div>

                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">
                            Catatan Tambahan <span class="u-muted">(opsional)</span>
                        </label>
                        <textarea name="remarks"
                                  class="u-input"
                                  rows="3"
                                  placeholder="Catatan khusus benefit / lokasi / klausul lain">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                {{-- MODE: EXTEND (Perpanjangan PKWT / PB Pengakhiran) --}}
                <div data-mode-section="extend" hidden>
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak Dasar</label>
                        <select id="sourceContractSelect" class="u-input">
                            <option value="">Pilih kontrak aktif yang akan berakhir</option>
@foreach($expiringContracts as $c)
    @php
        $startRaw = $c->start_date;
        $endRaw   = $c->end_date;

        $start = $startRaw ? \Carbon\Carbon::parse($startRaw)->format('d M Y') : '-';
        $end   = $endRaw   ? \Carbon\Carbon::parse($endRaw)->format('d M Y')   : '-';

        $unit       = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
        $personName = $c->person_name ?? '-';
        $position   = $c->position_name ?? '-';
    @endphp

    <option value="{{ $c->id }}"
            data-contract-no="{{ $c->id }}"
            data-person="{{ $personName }}"
            data-position="{{ $position }}"
            data-unit="{{ $unit }}"
            data-start="{{ $start }}"
            data-end="{{ $end }}"
            @selected(old('source_contract_id') == $c->id)
    >
        {{ $personName }} — {{ $position }} • {{ $unit }} ({{ $start }} s/d {{ $end }})
    </option>
@endforeach

                        </select>
                        <p class="u-text-xs u-muted u-mt-xxs">
                            Diambil dari kontrak PKWT (baru / perpanjangan) dengan status masih aktif dan akan berakhir ≤ 60 hari ke depan.
                        </p>

                        {{-- Preview Dinamis Kontrak Dasar --}}
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


                    {{-- FIELD PERIODE BARU: HANYA UNTUK PERPANJANGAN PKWT --}}
                    <div id="extendPeriodFields" class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai (Kontrak Baru)</label>
                            <input type="date"
                                   name="start_date"
                                   class="u-input"
                                   value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Selesai (Kontrak Baru)</label>
                            <input type="date"
                                   name="end_date"
                                   class="u-input"
                                   value="{{ old('end_date') }}">
                        </div>
                    </div>

                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">
                            Catatan / Hasil Evaluasi <span class="u-muted">(opsional)</span>
                        </label>
                        <textarea name="remarks"
                                  class="u-input"
                                  rows="3"
                                  placeholder="Ringkasan hasil evaluasi kinerja, etika, dan kesepakatan perpanjangan / pengakhiran">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="u-modal__foot">
                <div class="u-text-sm u-muted">
                    Tekan <kbd>Esc</kbd> untuk menutup modal.
                </div>
                <div class="u-flex u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="submit" class="u-btn u-btn--brand u-hover-lift">
                        <i class="fas fa-save u-mr-xs"></i> Simpan Draft
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = {
        init() {
            this.bindModal();
            this.bindExternalSearch();
            this.initDataTable();
        },

        bindModal() {
            const modal              = document.getElementById('createContractModal');
            const form               = document.getElementById('createContractForm');
            const familySelect       = document.getElementById('contractFamilySelect');
            const familyHint         = document.getElementById('contractFamilyHint');
            const subtypeWrapper     = document.getElementById('contractSubtypeWrapper');
            const subtypeSelect      = document.getElementById('contractSubtypeSelect');
            const subtypeHint        = document.getElementById('contractSubtypeHint');
            const typeInput          = document.getElementById('contractTypeInput');
            const modeInput          = document.getElementById('contractModeInput');
            const sectionsWrapper    = document.getElementById('contractFormSections');
            const sourceSelect       = document.getElementById('sourceContractSelect');
            const sourceInput        = document.getElementById('sourceContractInput');
            const extendPeriodFields = document.getElementById('extendPeriodFields');
            const unitWrapper        = document.getElementById('unitFieldWrapper');
            const applicantSelect    = document.getElementById('applicantSelect');

            // Preview elements
            const appPreviewBox   = document.getElementById('applicantPreview');
            const appPrevName     = document.getElementById('applicantPreviewName');
            const appPrevPos      = document.getElementById('applicantPreviewPosition');
            const appPrevUnit     = document.getElementById('applicantPreviewUnit');

            const srcPreviewBox   = document.getElementById('sourceContractPreview');
            const srcPrevNo       = document.getElementById('sourcePreviewNo');
            const srcPrevPerson   = document.getElementById('sourcePreviewPerson');
            const srcPrevPosition = document.getElementById('sourcePreviewPosition');
            const srcPrevUnit     = document.getElementById('sourcePreviewUnit');
            const srcPrevPeriod   = document.getElementById('sourcePreviewPeriod');

            function hideAllSections() {
                if (sectionsWrapper) sectionsWrapper.hidden = true;
                document.querySelectorAll('[data-mode-section]').forEach(el => { el.hidden = true; });
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
            }

            function updateExtendPeriodVisibility(contractTypeCode) {
                if (!extendPeriodFields) return;
                const isPerpanjangan = contractTypeCode === 'PKWT_PERPANJANGAN';
                extendPeriodFields.hidden = !isPerpanjangan;
                extendPeriodFields.querySelectorAll('input').forEach(input => {
                    input.required = isPerpanjangan;
                });
            }

            function hideUnit() {
                if (unitWrapper) unitWrapper.hidden = true;
            }

            function showUnit() {
                if (unitWrapper) unitWrapper.hidden = false;
            }

            function resetPreviews() {
                if (appPreviewBox) appPreviewBox.hidden = true;
                if (appPrevName)   appPrevName.textContent   = '-';
                if (appPrevPos)    appPrevPos.textContent    = '-';
                if (appPrevUnit)   appPrevUnit.textContent   = '-';

                if (srcPreviewBox) srcPreviewBox.hidden      = true;
                if (srcPrevNo)       srcPrevNo.textContent       = '-';
                if (srcPrevPerson)   srcPrevPerson.textContent   = '-';
                if (srcPrevPosition) srcPrevPosition.textContent = '-';
                if (srcPrevUnit)     srcPrevUnit.textContent     = '-';
                if (srcPrevPeriod)   srcPrevPeriod.textContent   = '-';
            }

            function resetFormLayout() {
                if (!form) return;

                if (modeInput)   modeInput.value   = '';
                if (typeInput)   typeInput.value   = '';
                if (familyHint)  familyHint.textContent  = '';
                if (subtypeHint) subtypeHint.textContent = '';
                if (subtypeWrapper) subtypeWrapper.hidden = true;

                if (extendPeriodFields) {
                    extendPeriodFields.hidden = false;
                    extendPeriodFields.querySelectorAll('input').forEach(i => { i.required = false; });
                }

                hideAllSections();
                hideUnit();
                resetPreviews();
            }

            function handleFamilyChange() {
                if (!familySelect) return;

                const familyVal = familySelect.value || '';

                if (subtypeSelect) subtypeSelect.value = '';
                if (subtypeHint)   subtypeHint.textContent = '';

                if (!familyVal) {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (familyHint)  familyHint.textContent = '';
                    if (subtypeWrapper) subtypeWrapper.hidden = true;
                    hideAllSections();
                    hideUnit();
                    updateExtendPeriodVisibility('');
                    return;
                }

                if (familyVal === 'SPK') {
                    if (subtypeWrapper) subtypeWrapper.hidden = true;

                    const opt  = familySelect.options[familySelect.selectedIndex];
                    const mode = opt ? (opt.dataset.mode || 'new') : 'new';
                    const hint = opt ? (opt.dataset.hint || '') : '';

                    if (typeInput)  typeInput.value  = 'SPK';
                    if (modeInput)  modeInput.value  = mode;
                    if (familyHint) familyHint.textContent = hint;

                    showUnit();
                    showSectionByMode(mode);
                    updateExtendPeriodVisibility('SPK');
                    return;
                }

                if (familyVal === 'PKWT') {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (subtypeWrapper) subtypeWrapper.hidden = false;
                    if (familyHint) {
                        const opt = familySelect.options[familySelect.selectedIndex];
                        familyHint.textContent = opt?.dataset.hint || 'Pilih detail PKWT (baru / perpanjangan / pengakhiran).';
                    }
                    hideAllSections();
                    hideUnit();
                    updateExtendPeriodVisibility('');
                    return;
                }

                if (typeInput)  typeInput.value  = '';
                if (modeInput)  modeInput.value  = '';
                hideAllSections();
                hideUnit();
                updateExtendPeriodVisibility('');
            }

            function handleSubtypeChange() {
                if (!subtypeSelect) return;

                const code = subtypeSelect.value || '';
                if (!code) {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (subtypeHint) subtypeHint.textContent = '';
                    hideAllSections();
                    hideUnit();
                    updateExtendPeriodVisibility('');
                    return;
                }

                const opt  = subtypeSelect.options[subtypeSelect.selectedIndex];
                const mode = opt ? (opt.dataset.mode || 'new') : 'new';
                const hint = opt ? (opt.dataset.hint || '') : '';

                if (typeInput)   typeInput.value   = code;
                if (modeInput)   modeInput.value   = mode;
                if (subtypeHint) subtypeHint.textContent = hint;

                showUnit();
                showSectionByMode(mode);
                updateExtendPeriodVisibility(code);
            }

            function updateApplicantPreview() {
                if (!applicantSelect || !appPreviewBox) return;

                const val = applicantSelect.value || '';
                const opt = val ? applicantSelect.options[applicantSelect.selectedIndex] : null;
                if (!opt || !opt.dataset.fullName) {
                    appPreviewBox.hidden = true;
                    if (appPrevName) appPrevName.textContent = '-';
                    if (appPrevPos)  appPrevPos.textContent  = '-';
                    if (appPrevUnit) appPrevUnit.textContent = '-';
                    return;
                }

                if (appPrevName) appPrevName.textContent = opt.dataset.fullName || '-';
                if (appPrevPos)  appPrevPos.textContent  = opt.dataset.position  || '-';
                if (appPrevUnit) appPrevUnit.textContent = opt.dataset.unit      || '-';
                appPreviewBox.hidden = false;
            }

            function updateSourcePreview() {
                if (!sourceSelect || !srcPreviewBox) return;

                const val = sourceSelect.value || '';
                const opt = val ? sourceSelect.options[sourceSelect.selectedIndex] : null;
                if (!opt || !opt.dataset.contractNo) {
                    srcPreviewBox.hidden = true;
                    if (srcPrevNo)       srcPrevNo.textContent       = '-';
                    if (srcPrevPerson)   srcPrevPerson.textContent   = '-';
                    if (srcPrevPosition) srcPrevPosition.textContent = '-';
                    if (srcPrevUnit)     srcPrevUnit.textContent     = '-';
                    if (srcPrevPeriod)   srcPrevPeriod.textContent   = '-';
                    return;
                }

                if (srcPrevNo)       srcPrevNo.textContent       = opt.dataset.contractNo || '-';
                if (srcPrevPerson)   srcPrevPerson.textContent   = opt.dataset.person     || '-';
                if (srcPrevPosition) srcPrevPosition.textContent = opt.dataset.position   || '-';
                if (srcPrevUnit)     srcPrevUnit.textContent     = opt.dataset.unit       || '-';
                if (srcPrevPeriod)   srcPrevPeriod.textContent   =
                    (opt.dataset.start || '-') + ' s/d ' + (opt.dataset.end || '-');

                srcPreviewBox.hidden = false;
            }

            document.addEventListener('click', function (e) {
                const openBtn = e.target.closest && e.target.closest('[data-modal-open]');
                if (openBtn && openBtn.getAttribute('data-modal-open') === 'createContractModal') {
                    if (modal) {
                        resetFormLayout();
                        modal.hidden = false;
                        document.body.classList.add('modal-open');

                        const initialType = typeInput ? (typeInput.value || '') : '';
                        if (initialType === 'SPK') {
                            if (familySelect) {
                                familySelect.value = 'SPK';
                                handleFamilyChange();
                            }
                        } else if (['PKWT_BARU','PKWT_PERPANJANGAN','PB_PENGAKHIRAN'].includes(initialType)) {
                            if (familySelect) {
                                familySelect.value = 'PKWT';
                                handleFamilyChange();
                            }
                            if (subtypeSelect) {
                                subtypeSelect.value = initialType;
                                handleSubtypeChange();
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

                        updateApplicantPreview();
                    }
                }

                const closeBtn = e.target.matches('[data-modal-close]')
                    ? e.target
                    : (e.target.closest && e.target.closest('[data-modal-close]'));

                if (closeBtn) {
                    const m = closeBtn.closest('.u-modal');
                    if (m) {
                        m.hidden = true;
                        document.body.classList.remove('modal-open');
                    }
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const open = document.querySelector('.u-modal:not([hidden])');
                    if (open) {
                        open.hidden = true;
                        document.body.classList.remove('modal-open');
                    }
                }
            });

            if (familySelect)  familySelect.addEventListener('change', handleFamilyChange);
            if (subtypeSelect) subtypeSelect.addEventListener('change', handleSubtypeChange);

            if (sourceSelect && sourceInput) {
                sourceSelect.addEventListener('change', function () {
                    sourceInput.value = this.value || '';
                    updateSourcePreview();
                });
            }

            if (applicantSelect) {
                applicantSelect.addEventListener('change', updateApplicantPreview);
            }

            // Auto-open modal jika ada validation error dari store
            @if($errors->any())
            if (modal) {
                modal.hidden = false;
                document.body.classList.add('modal-open');

                const initialType = typeInput ? (typeInput.value || '') : '';
                if (initialType === 'SPK') {
                    if (familySelect) {
                        familySelect.value = 'SPK';
                        handleFamilyChange();
                    }
                } else if (['PKWT_BARU','PKWT_PERPANJANGAN','PB_PENGAKHIRAN'].includes(initialType)) {
                    if (familySelect) {
                        familySelect.value = 'PKWT';
                        handleFamilyChange();
                    }
                    if (subtypeSelect) {
                        subtypeSelect.value = initialType;
                        handleSubtypeChange();
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

                updateApplicantPreview();
            }
            @endif
        },

        bindExternalSearch() {
            const ext = document.querySelector('#contractsSearch');
            const tableEl = document.querySelector('#contracts-table');
            if (!ext || !tableEl) return;

            if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
                const dt = jQuery('#contracts-table').DataTable();
                ext.addEventListener('input', function () {
                    dt.search(ext.value || '').draw();
                });
                return;
            }

            const wrapper = tableEl.closest('.dataTable-wrapper');
            const qs = wrapper ? wrapper.querySelector('.dataTable-input') : null;
            if (qs) {
                ext.addEventListener('input', function () {
                    qs.value = ext.value || '';
                    qs.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }
        },

        initDataTable() {
            if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
                jQuery('#contracts-table').DataTable({
                    responsive: true,
                    paging: true,
                    info: true,
                });
                return;
            }
            if (typeof window.DataTable !== 'undefined') {
                new window.DataTable('#contracts-table', {
                    responsive: true,
                    perPageSelect: [10, 25, 50],
                });
            }
        }
    };

    app.init();
});
</script>
@endsection
