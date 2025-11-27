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
    $budgetSourceTypes    = $budgetSourceTypes    ?? [];
    $applicants           = $applicants           ?? collect();
    $expiringContracts    = $expiringContracts    ?? collect();

    $contractTypeMap  = collect($contractTypeConfigs)->keyBy('code');
    $spkCfg           = $contractTypeMap->get('SPK');
    $pkwtNewCfg       = $contractTypeMap->get('PKWT_BARU');
    $pkwtExtCfg       = $contractTypeMap->get('PKWT_PERPANJANGAN');
    $pbCfg            = $contractTypeMap->get('PB_PENGAKHIRAN');
@endphp

<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
        <div>
            <h2 class="u-title u-mb-sm">Penerbitan &amp; Penandatanganan Kontrak</h2>
            <p class="u-text-sm u-muted">
                Monitoring draft–publish–e-sign kontrak kerja (SPK, PKWT, Perjanjian Bersama).
                <br>
                <span class="u-text-xxs">
                    Format nomor: <code>(TYPE)-xxx/UNITCODE-mm/HEADCODE/YYYY</code> &mdash; reset nomor per <strong>jenis kontrak + unit + bulan</strong>.
                </span>
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

    @if(session('ok'))
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

    {{-- FILTER BAR LIST KONTRAK --}}
    <div class="u-flex u-items-center u-gap-md u-mb-md u-stack-mobile">
        <form method="get" class="u-flex u-items-center u-gap-md u-stack-mobile u-w-full u-flex-wrap">
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

            <div class="u-flex-1-min">
                <label class="u-text-xs u-font-medium u-mb-xs d-block">Status Kontrak</label>
                <select name="status" class="u-input u-input--sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $code => $label)
                        @php
                            $statusCode  = is_array($label) ? ($label['code'] ?? null) : $code;
                            $statusLabel = is_array($label) ? ($label['label'] ?? $statusCode) : $label;
                        @endphp
                        @if($statusCode)
                            <option value="{{ $statusCode }}" @selected($statusFilter === $statusCode)>{{ $statusLabel }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="u-flex-1-min u-w-full u-max-w-xs u-ml-auto">
                <label class="u-text-xs u-font-medium u-mb-xs d-block">Cari</label>
                <input type="search"
                       name="q"
                       id="contractsSearch"
                       class="u-input u-input--sm"
                       placeholder="No. Kontrak / Jenis / Unit"
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
                        <th>Nomor</th>
                        <th>Jenis</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>e-Sign</th>
                        <th class="cell-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $c)
                        @php
                            if (!is_object($c)) { continue; }

                            $jenis    = $c->contract_type ?? '—';
                            $unitName = optional($c->unit)->name ?? '—';

                            $start = $c->start_date ? Carbon::parse($c->start_date) : null;
                            $end   = $c->end_date   ? Carbon::parse($c->end_date)   : null;

                            $periode = ($start && $end)
                                ? $start->format('d M Y').' s/d '.$end->format('d M Y')
                                : '—';

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
                            <td>
                                <span class="u-badge u-badge--glass u-text-xs font-mono text-xs">
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
                                    {{-- tombol aksi (detail / approve / sign) akan diisi kemudian --}}
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
                        Susun draft kontrak (SPK / PKWT Baru / Perpanjangan / PB) sesuai flow Rekrutmen SI.
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
                        <select id="contractFamilySelect" class="u-input" required>
                            <option value="">Pilih jenis kontrak...</option>

                            {{-- SPK --}}
                            @if($spkCfg)
                                <option value="SPK"
                                        data-mode="new"
                                        data-hint="{{ $spkCfg['description'] ?? 'Surat Penawaran Kerja (SPK) untuk kandidat baru.' }}"
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

                            {{-- PKWT FAMILY --}}
                            <option value="PKWT"
                                    data-mode=""
                                    data-hint="Pilih detail PKWT: baru atau perpanjangan."
                                    @selected(in_array(old('contract_type'), ['PKWT_BARU','PKWT_PERPANJANGAN']))>
                                PKWT (Baru / Perpanjangan)
                            </option>

                            {{-- PB (Perjanjian Bersama Pengakhiran) --}}
                            @if($pbCfg)
                                <option value="PB"
                                        data-mode="terminate"
                                        data-hint="{{ $pbCfg['description'] ?? 'Perjanjian Bersama pengakhiran PKWT.' }}"
                                        @selected(old('contract_type') === 'PB_PENGAKHIRAN')>
                                    {{ $pbCfg['label'] ?? 'Perjanjian Bersama (PB)' }}
                                </option>
                            @else
                                <option value="PB"
                                        data-mode="terminate"
                                        data-hint="Perjanjian Bersama pengakhiran PKWT."
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
                            • SPK untuk offering kandidat baru.<br>
                            • PKWT untuk kontrak kerja baru dan perpanjangan PKWT existing.<br>
                            • PB untuk pengakhiran PKWT dengan Perjanjian Bersama.
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
                        @if($pkwtNewCfg)
                            <option value="PKWT_BARU"
                                    data-mode="new"
                                    data-hint="{{ $pkwtNewCfg['description'] ?? 'PKWT pertama kali (dari pelamar).' }}"
                                    @selected(old('contract_type') === 'PKWT_BARU')>
                                {{ $pkwtNewCfg['label'] ?? 'PKWT Baru' }}
                            </option>
                        @endif
                        @if($pkwtExtCfg)
                            <option value="PKWT_PERPANJANGAN"
                                    data-mode="extend"
                                    data-hint="{{ $pkwtExtCfg['description'] ?? 'Perpanjangan PKWT dari kontrak aktif yang akan berakhir.' }}"
                                    @selected(old('contract_type') === 'PKWT_PERPANJANGAN')>
                                {{ $pkwtExtCfg['label'] ?? 'PKWT Perpanjangan' }}
                            </option>
                        @endif
                    </select>
                    <p id="contractSubtypeHint" class="u-text-xs u-muted u-mt-xxs"></p>
                </div>
            </div>

            {{-- STEP 2: UNIT & DETAIL FORM --}}
            <div id="contractFormSections" class="u-space-y-lg" hidden>

                {{-- UNIT KERJA --}}
                <div id="unitFieldWrapper" class="u-mt-md" hidden>
                    <label class="u-text-sm u-font-medium u-mb-sm d-block">Unit Kerja</label>

                    @if($canSeeAll)
                        <select name="unit_id" id="contractUnitSelect" class="u-input">
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

                {{-- MODE NEW (SPK + PKWT BARU) --}}
                <div data-mode-section="new" hidden>
                    <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Pelamar / Kandidat</label>
                            <select name="applicant_id" id="applicantSelect" class="u-input">
                                <option value="">Pilih Pelamar</option>
                                @foreach($applicants as $a)
                                    @php
                                        $appUnitName  = $a->unit_name ?? 'Unit ?';
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
                                Data kandidat dari Monitoring Rekrutmen (status <strong>APPROVED/HIRED</strong>).
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
                                @foreach($employmentTypes as $val => $label)
                                    @php
                                        $code  = is_array($label) ? ($label['code'] ?? $val) : $val;
                                        $text  = is_array($label) ? ($label['label'] ?? $code) : $label;
                                    @endphp
                                    <option value="{{ $code }}" @selected(old('employment_type') == $code)>{{ $text }}</option>
                                @endforeach
                            </select>

                            <div class="u-mt-md">
                                <label class="u-text-sm u-font-medium u-mb-sm d-block">Sumber Anggaran</label>
                                <select name="budget_source_type" class="u-input">
                                    <option value="">Pilih Sumber Anggaran</option>
                                    @foreach($budgetSourceTypes as $val => $label)
                                        @php
                                            $code  = is_array($label) ? ($label['code'] ?? $val) : $val;
                                            $text  = is_array($label) ? ($label['label'] ?? $code) : $label;
                                        @endphp
                                        <option value="{{ $code }}" @selected(old('budget_source_type') == $code)>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
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
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Mulai Kontrak</label>
                            <input type="date"
                                   name="start_date"
                                   class="u-input"
                                   value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Berakhir Kontrak</label>
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

                    {{-- RINCIAN UPAH & FASILITAS --}}
                    <div class="u-mt-lg">
                        <div class="u-flex u-items-center u-justify-between u-mb-xs">
                            <label class="u-text-sm u-font-semibold">Rincian Upah &amp; Fasilitas</label>
                            <span class="u-text-xs u-muted">Isi sesuai draft remunerasi (gaji pokok, uang makan, tunjangan).</span>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Rp)</label>
                                <input type="text"
                                       name="salary_amount"
                                       class="u-input"
                                       data-rupiah="true"
                                       data-terbilang-target="salary_amount_words"
                                       placeholder="Mis. 7.500.000"
                                       value="{{ old('salary_amount') }}">
                                <p class="u-text-xxs u-muted u-mt-xxs">Ketik angka, sistem akan memformat &amp; bisa mengisi terbilang otomatis.</p>
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Terbilang)</label>
                                <input type="text"
                                       name="salary_amount_words"
                                       class="u-input"
                                       placeholder="Mis. TUJUH JUTA LIMA RATUS RIBU RUPIAH"
                                       value="{{ old('salary_amount_words') }}">
                            </div>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan per Hari (Rp)</label>
                                <input type="text"
                                       name="lunch_allowance_daily"
                                       class="u-input"
                                       data-rupiah="true"
                                       data-terbilang-target="lunch_allowance_words"
                                       placeholder="Mis. 40.000"
                                       value="{{ old('lunch_allowance_daily') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan (Terbilang)</label>
                                <input type="text"
                                       name="lunch_allowance_words"
                                       class="u-input"
                                       placeholder="Mis. EMPAT PULUH RIBU RUPIAH"
                                       value="{{ old('lunch_allowance_words') }}">
                            </div>
                        </div>

                        {{-- TUNJANGAN OPSIONAL --}}
                        <details class="u-mt-md">
                            <summary class="u-text-xs u-font-medium u-cursor-pointer">
                                Tunjangan Lain (opsional)
                            </summary>
                            <div class="u-mt-sm u-space-y-sm">
                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Khusus (Rp)</label>
                                        <input type="text"
                                               name="allowance_special_amount"
                                               class="u-input"
                                               data-rupiah="true"
                                               data-terbilang-target="allowance_special_words"
                                               value="{{ old('allowance_special_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Khusus (Terbilang)</label>
                                        <input type="text"
                                               name="allowance_special_words"
                                               class="u-input"
                                               value="{{ old('allowance_special_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Jabatan (Rp)</label>
                                        <input type="text"
                                               name="allowance_position_amount"
                                               class="u-input"
                                               data-rupiah="true"
                                               data-terbilang-target="allowance_position_words"
                                               value="{{ old('allowance_position_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Jabatan (Terbilang)</label>
                                        <input type="text"
                                               name="allowance_position_words"
                                               class="u-input"
                                               value="{{ old('allowance_position_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Komunikasi (Rp)</label>
                                        <input type="text"
                                               name="allowance_communication_amount"
                                               class="u-input"
                                               data-rupiah="true"
                                               data-terbilang-target="allowance_communication_words"
                                               value="{{ old('allowance_communication_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Komunikasi (Terbilang)</label>
                                        <input type="text"
                                               name="allowance_communication_words"
                                               class="u-input"
                                               value="{{ old('allowance_communication_words') }}">
                                    </div>
                                </div>

                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Lain (Rp)</label>
                                        <input type="text"
                                               name="allowance_other_amount"
                                               class="u-input"
                                               data-rupiah="true"
                                               data-terbilang-target="allowance_other_words"
                                               value="{{ old('allowance_other_amount') }}">
                                    </div>
                                    <div>
                                        <label class="u-text-xxs u-font-medium u-mb-xxs d-block">Tunjangan Lain (Terbilang)</label>
                                        <input type="text"
                                               name="allowance_other_words"
                                               class="u-input"
                                               value="{{ old('allowance_other_words') }}">
                                    </div>
                                </div>

                                <div>
                                    <label class="u-text-xxs u-font-medium u-mb-xxs d-block">
                                        Keterangan Tunjangan Lain
                                    </label>
                                    <textarea name="allowance_other_desc"
                                              class="u-input"
                                              rows="2"
                                              placeholder="Mis. tunjangan shift, tunjangan remote area, dll.">{{ old('allowance_other_desc') }}</textarea>
                                </div>
                            </div>
                        </details>

                        <div class="u-mt-md">
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">
                                Fasilitas / Benefit Lain <span class="u-muted">(opsional)</span>
                            </label>
                            <textarea name="other_benefits_desc"
                                      class="u-input"
                                      rows="2"
                                      placeholder="Mis. BPJS Kesehatan &amp; Ketenagakerjaan, tunjangan transport, dll.">{{ old('other_benefits_desc') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- MODE EXTEND (PKWT PERPANJANGAN) --}}
                <div data-mode-section="extend" hidden>
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak Dasar untuk Perpanjangan</label>
                        <select id="sourceContractSelect" class="u-input">
                            <option value="">Pilih kontrak aktif yang akan berakhir</option>
                            @foreach($expiringContracts as $c)
                                @php
                                    $startRaw = $c->start_date ?? null;
                                    $endRaw   = $c->end_date ?? null;

                                    $start = $startRaw ? Carbon::parse($startRaw)->format('d M Y') : '-';
                                    $end   = $endRaw   ? Carbon::parse($endRaw)->format('d M Y')   : '-';

                                    $unit       = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
                                    $personName = $c->person_name ?? '-';
                                    $position   = $c->position_name ?? '-';
                                @endphp

                                <option value="{{ $c->id }}"
                                        data-contract-no="{{ $c->contract_no ?? $c->id }}"
                                        data-person="{{ $personName }}"
                                        data-position="{{ $position }}"
                                        data-unit="{{ $unit }}"
                                        data-start="{{ $start }}"
                                        data-end="{{ $end }}"
                                        data-unit-id="{{ $c->unit_id ?? '' }}"
                                        @selected(old('source_contract_id') == $c->id)
                                >
                                    {{ $personName }} — {{ $position }} • {{ $unit }} ({{ $start }} s/d {{ $end }})
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
                            <input type="date"
                                   name="start_date"
                                   class="u-input"
                                   value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">Tanggal Berakhir Kontrak Baru</label>
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
                                  placeholder="Ringkasan hasil evaluasi kinerja, etika, dan kesepakatan perpanjangan">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="u-mt-lg">
                        <div class="u-flex u-items-center u-justify-between u-mb-xs">
                            <label class="u-text-sm u-font-semibold">Rincian Upah &amp; Fasilitas (Kontrak Baru)</label>
                            <span class="u-text-xs u-muted">Isi jika ada perubahan gaji pokok / uang makan / fasilitas pada perpanjangan.</span>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Rp)</label>
                                <input type="text"
                                       name="salary_amount"
                                       class="u-input"
                                       data-rupiah="true"
                                       data-terbilang-target="salary_amount_words"
                                       placeholder="Mis. 8.000.000"
                                       value="{{ old('salary_amount') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Gaji Pokok (Terbilang)</label>
                                <input type="text"
                                       name="salary_amount_words"
                                       class="u-input"
                                       placeholder="Mis. DELAPAN JUTA RUPIAH"
                                       value="{{ old('salary_amount_words') }}">
                            </div>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-md">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan per Hari (Rp)</label>
                                <input type="text"
                                       name="lunch_allowance_daily"
                                       class="u-input"
                                       data-rupiah="true"
                                       data-terbilang-target="lunch_allowance_words"
                                       placeholder="Mis. 40.000"
                                       value="{{ old('lunch_allowance_daily') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Uang Makan (Terbilang)</label>
                                <input type="text"
                                       name="lunch_allowance_words"
                                       class="u-input"
                                       placeholder="Mis. EMPAT PULUH RIBU RUPIAH"
                                       value="{{ old('lunch_allowance_words') }}">
                            </div>
                        </div>

                        <div class="u-mt-md">
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Fasilitas / Benefit Lain (Kontrak Baru)</label>
                            <textarea name="other_benefits_desc"
                                      class="u-input"
                                      rows="2"
                                      placeholder="Isi jika ada perubahan fasilitas lain dibanding kontrak sebelumnya.">{{ old('other_benefits_desc') }}</textarea>
                        </div>
                    </div>

                    <div class="u-mt-lg">
                        <div class="u-flex u-items-center u-justify-between u-mb-xs">
                            <label class="u-text-sm u-font-semibold">Informasi Kontrak Dasar</label>
                            <span class="u-text-xs u-muted">Opsional, untuk melengkapi data di template bila diperlukan.</span>
                        </div>

                        <div class="u-grid-3 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">No. Kontrak Dasar</label>
                                <input type="text"
                                       name="base_contract_no"
                                       class="u-input"
                                       placeholder="Mis. PKWT/DBS-OG/2024/001"
                                       value="{{ old('base_contract_no') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Mulai Kontrak Dasar</label>
                                <input type="date"
                                       name="base_contract_start"
                                       class="u-input"
                                       value="{{ old('base_contract_start') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Selesai Kontrak Dasar</label>
                                <input type="date"
                                       name="base_contract_end"
                                       class="u-input"
                                       value="{{ old('base_contract_end') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODE TERMINATE (PB PENGAKHIRAN) --}}
                <div data-mode-section="terminate" hidden>
                    <div class="u-mt-md">
                        <label class="u-text-sm u-font-medium u-mb-sm d-block">Kontrak PKWT yang Diakhiri</label>
                        <select id="sourceContractSelectTerminate" class="u-input">
                            <option value="">Pilih kontrak PKWT yang diakhiri</option>
                            @foreach($expiringContracts as $c)
                                @php
                                    $startRaw = $c->start_date ?? null;
                                    $endRaw   = $c->end_date ?? null;

                                    $start = $startRaw ? Carbon::parse($startRaw)->format('d M Y') : '-';
                                    $end   = $endRaw   ? Carbon::parse($endRaw)->format('d M Y')   : '-';

                                    $unit       = $c->unit_name ?? $c->unit_name_raw ?? 'Unit ?';
                                    $personName = $c->person_name ?? '-';
                                    $position   = $c->position_name ?? '-';
                                @endphp

                                <option value="{{ $c->id }}"
                                        data-contract-no="{{ $c->contract_no ?? $c->id }}"
                                        data-person="{{ $personName }}"
                                        data-position="{{ $position }}"
                                        data-unit="{{ $unit }}"
                                        data-start="{{ $start }}"
                                        data-end="{{ $end }}"
                                        data-unit-id="{{ $c->unit_id ?? '' }}"
                                        @selected(old('source_contract_id') == $c->id)
                                >
                                    {{ $personName }} — {{ $position }} • {{ $unit }} ({{ $start }} s/d {{ $end }})
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
                            <label class="u-text-sm u-font-semibold">Informasi Kontrak Dasar</label>
                            <span class="u-text-xs u-muted">Digunakan untuk isi template PB.</span>
                        </div>

                        <div class="u-grid-3 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">No. Kontrak Dasar</label>
                                <input type="text"
                                       name="base_contract_no"
                                       class="u-input"
                                       placeholder="Mis. PKWT/DBS-OG/2024/001"
                                       value="{{ old('base_contract_no') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Mulai Kontrak Dasar</label>
                                <input type="date"
                                       name="base_contract_start"
                                       class="u-input"
                                       value="{{ old('base_contract_start') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Selesai Kontrak Dasar</label>
                                <input type="date"
                                       name="base_contract_end"
                                       class="u-input"
                                       value="{{ old('base_contract_end') }}">
                            </div>
                        </div>
                    </div>

                    <div class="u-mt-lg">
                        <div class="u-flex u-items-center u-justify-between u-mb-xs">
                            <label class="u-text-sm u-font-semibold">Detail Perjanjian Bersama (PB)</label>
                            <span class="u-text-xs u-muted">Tanggal efektif pengakhiran &amp; kompensasi (jika ada).</span>
                        </div>

                        <div class="u-grid-2 u-stack-mobile u-gap-md u-mt-sm">
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Tanggal Efektif Pengakhiran</label>
                                <input type="date"
                                       name="pb_effective_end"
                                       class="u-input"
                                       value="{{ old('pb_effective_end') }}">
                            </div>
                            <div>
                                <label class="u-text-xs u-font-medium u-mb-xxs d-block">Kompensasi PB (Rp)</label>
                                <input type="text"
                                       name="pb_compensation_amount"
                                       class="u-input"
                                       data-rupiah="true"
                                       data-terbilang-target="pb_compensation_amount_words"
                                       placeholder="Mis. 10.000.000"
                                       value="{{ old('pb_compensation_amount') }}">
                            </div>
                        </div>

                        <div class="u-mt-md">
                            <label class="u-text-xs u-font-medium u-mb-xxs d-block">Kompensasi PB (Terbilang)</label>
                            <input type="text"
                                   name="pb_compensation_amount_words"
                                   class="u-input"
                                   placeholder="Mis. SEPULUH JUTA RUPIAH"
                                   value="{{ old('pb_compensation_amount_words') }}">
                        </div>

                        <div class="u-mt-md">
                            <label class="u-text-sm u-font-medium u-mb-sm d-block">
                                Catatan / Kesepakatan Lain <span class="u-muted">(opsional)</span>
                            </label>
                            <textarea name="remarks"
                                      class="u-input"
                                      rows="3"
                                      placeholder="Ringkasan kesepakatan PB, klausul khusus, dsb.">{{ old('remarks') }}</textarea>
                        </div>
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
            this.bindRupiahFormatter();
            this.bindTerbilangAutoFill();
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
            const unitWrapper        = document.getElementById('unitFieldWrapper');
            const unitSelect         = document.getElementById('contractUnitSelect');
            const sourceSelect       = document.getElementById('sourceContractSelect');
            const sourceInput        = document.getElementById('sourceContractInput');
            const sourceSelectTerm   = document.getElementById('sourceContractSelectTerminate');

            const applicantSelect    = document.getElementById('applicantSelect');

            const allSourceOptions      = sourceSelect ? Array.from(sourceSelect.options) : [];
            const allSourceTermOptions  = sourceSelectTerm ? Array.from(sourceSelectTerm.options) : [];

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

            const srcPrevBoxTerm   = document.getElementById('sourceContractPreviewTerminate');
            const srcPrevNoTerm    = document.getElementById('sourcePreviewNoTerminate');
            const srcPrevPersonTerm= document.getElementById('sourcePreviewPersonTerminate');
            const srcPrevPosTerm   = document.getElementById('sourcePreviewPositionTerminate');
            const srcPrevUnitTerm  = document.getElementById('sourcePreviewUnitTerminate');
            const srcPrevPeriodTerm= document.getElementById('sourcePreviewPeriodTerminate');

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

                if (srcPrevBoxTerm) srcPrevBoxTerm.hidden        = true;
                if (srcPrevNoTerm)       srcPrevNoTerm.textContent       = '-';
                if (srcPrevPersonTerm)   srcPrevPersonTerm.textContent   = '-';
                if (srcPrevPosTerm)      srcPrevPosTerm.textContent      = '-';
                if (srcPrevUnitTerm)     srcPrevUnitTerm.textContent     = '-';
                if (srcPrevPeriodTerm)   srcPrevPeriodTerm.textContent   = '-';
            }

            function resetFormLayout() {
                if (!form) return;

                if (modeInput)   modeInput.value   = '';
                if (typeInput)   typeInput.value   = '';
                if (familyHint)  familyHint.textContent  = '';
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

                    opt.hidden  = !match;
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

                    opt.hidden  = !match;
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
                if (subtypeHint)   subtypeHint.textContent = '';

                if (!familyVal) {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (familyHint)  familyHint.textContent = '';
                    if (subtypeWrapper) subtypeWrapper.hidden = true;
                    hideAllSections();
                    hideUnit();
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

                    if (unitSelect) {
                        filterSourceByUnit(unitSelect.value || '');
                        filterSourceTermByUnit(unitSelect.value || '');
                    }

                    return;
                }

                if (familyVal === 'PKWT') {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (subtypeWrapper) subtypeWrapper.hidden = false;
                    if (familyHint) {
                        const opt = familySelect.options[familySelect.selectedIndex];
                        familyHint.textContent = opt?.dataset.hint || 'Pilih detail PKWT (baru / perpanjangan).';
                    }
                    hideAllSections();
                    showUnit();
                    return;
                }

                if (familyVal === 'PB') {
                    if (subtypeWrapper) subtypeWrapper.hidden = true;

                    const opt  = familySelect.options[familySelect.selectedIndex];
                    const mode = opt ? (opt.dataset.mode || 'terminate') : 'terminate';
                    const hint = opt ? (opt.dataset.hint || '') : '';

                    if (typeInput)  typeInput.value  = 'PB_PENGAKHIRAN';
                    if (modeInput)  modeInput.value  = mode;
                    if (familyHint) familyHint.textContent = hint;

                    showUnit();
                    showSectionByMode(mode);

                    if (unitSelect) {
                        filterSourceByUnit(unitSelect.value || '');
                        filterSourceTermByUnit(unitSelect.value || '');
                    }

                    return;
                }

                if (typeInput)  typeInput.value  = '';
                if (modeInput)  modeInput.value  = '';
                hideAllSections();
                hideUnit();
            }

            function handleSubtypeChange() {
                if (!subtypeSelect) return;

                const code = subtypeSelect.value || '';
                if (!code) {
                    if (typeInput)   typeInput.value   = '';
                    if (modeInput)   modeInput.value   = '';
                    if (subtypeHint) subtypeHint.textContent = '';
                    hideAllSections();
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

            function updateSourcePreviewTerminate() {
                if (!sourceSelectTerm || !srcPrevBoxTerm) return;

                const val = sourceSelectTerm.value || '';
                const opt = val ? sourceSelectTerm.options[sourceSelectTerm.selectedIndex] : null;
                if (!opt || !opt.dataset.contractNo) {
                    srcPrevBoxTerm.hidden = true;
                    if (srcPrevNoTerm)       srcPrevNoTerm.textContent       = '-';
                    if (srcPrevPersonTerm)   srcPrevPersonTerm.textContent   = '-';
                    if (srcPrevPosTerm)      srcPrevPosTerm.textContent      = '-';
                    if (srcPrevUnitTerm)     srcPrevUnitTerm.textContent     = '-';
                    if (srcPrevPeriodTerm)   srcPrevPeriodTerm.textContent   = '-';
                    return;
                }

                if (srcPrevNoTerm)       srcPrevNoTerm.textContent       = opt.dataset.contractNo || '-';
                if (srcPrevPersonTerm)   srcPrevPersonTerm.textContent   = opt.dataset.person     || '-';
                if (srcPrevPosTerm)      srcPrevPosTerm.textContent      = opt.dataset.position   || '-';
                if (srcPrevUnitTerm)     srcPrevUnitTerm.textContent     = opt.dataset.unit       || '-';
                if (srcPrevPeriodTerm)   srcPrevPeriodTerm.textContent   =
                    (opt.dataset.start || '-') + ' s/d ' + (opt.dataset.end || '-');

                srcPrevBoxTerm.hidden = false;
            }

            document.addEventListener('click', function (e) {
                const openBtn = e.target.closest && e.target.closest('[data-modal-open]');
                if (openBtn && openBtn.getAttribute('data-modal-open') === 'createContractModal') {
                    if (modal) {
                        resetFormLayout();
                        modal.hidden = false;
                        document.body.classList.add('modal-open');

                        const initialType = typeInput ? (typeInput.value || '') : '';
                        const typesPkwt = ['PKWT_BARU','PKWT_PERPANJANGAN'];

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
                modal.hidden = false;
                document.body.classList.add('modal-open');

                const initialType = typeInput ? (typeInput.value || '') : '';
                const typesPkwt = ['PKWT_BARU','PKWT_PERPANJANGAN'];

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
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            document.querySelectorAll('input[data-rupiah="true"]').forEach(input => {
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

                if (n < 12) {
                    return huruf[n];
                } else if (n < 20) {
                    return terbilang(n - 10) + ' belas';
                } else if (n < 100) {
                    return terbilang(Math.floor(n / 10)) + ' puluh ' + terbilang(n % 10);
                } else if (n < 200) {
                    return 'seratus ' + terbilang(n - 100);
                } else if (n < 1000) {
                    return terbilang(Math.floor(n / 100)) + ' ratus ' + terbilang(n % 100);
                } else if (n < 2000) {
                    return 'seribu ' + terbilang(n - 1000);
                } else if (n < 1000000) {
                    return terbilang(Math.floor(n / 1000)) + ' ribu ' + terbilang(n % 1000);
                } else if (n < 1000000000) {
                    return terbilang(Math.floor(n / 1000000)) + ' juta ' + terbilang(n % 1000000);
                } else if (n < 1000000000000) {
                    return terbilang(Math.floor(n / 1000000000)) + ' miliar ' + terbilang(n % 1000000000);
                }
                return String(n);
            }

            document.querySelectorAll('input[data-rupiah="true"][data-terbilang-target]').forEach(input => {
                input.addEventListener('blur', function () {
                    const targetName = this.getAttribute('data-terbilang-target');
                    if (!targetName) return;

                    const form = this.closest('form');
                    if (!form) return;

                    const target = form.querySelector('[name="' + targetName + '"]');
                    if (!target) return;

                    const nilai = toInt(this.value || '');
                    if (!nilai) return;

                    const text = terbilang(nilai).trim() + ' rupiah';
                    target.value = text.toUpperCase();
                });
            });
        },
    };

    app.init();
});
</script>
@endsection
