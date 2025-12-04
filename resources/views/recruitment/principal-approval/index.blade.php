@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
{{-- Style & Modal Stacking Fix --}}
<style>
    /* --- CUSTOM STYLE FOR MODAL STACKING & CENTERING --- */
    #createApprovalModal {
        z-index: 1050 !important;
    }
    
    /* Fix: Agar modal Uraian selalu di atas & DI TENGAH layar */
    #uraianModal {
        z-index: 2000 !important; 
        background-color: rgba(0, 0, 0, 0.5); 
        /* Flexbox centering magic */
        display: none; /* Default hidden, nanti di-override JS/CSS saat aktif */
        align-items: center;
        justify-content: center;
        position: fixed;
        inset: 0;
    }
    
    /* Saat modal tidak hidden, paksa display flex agar centering jalan */
    #uraianModal:not([hidden]) {
        display: flex !important;
    }

    /* Style Form */
    .modal-card-wide {
        width: 95% !important; 
        max-width: 1000px !important; 
        max-height: 90vh; /* Agar tidak melebihi tinggi layar */
        display: flex;
        flex-direction: column;
    }
    .u-modal__body {
        overflow-y: auto; /* Scrollable body */
    }
    .uj-section-title {
        font-weight: 700;
        font-size: 0.95rem;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        color: #374151;
        text-transform: uppercase;
        background-color: #f3f4f6; /* Highlight judul section */
        padding: 8px;
        margin-top: 10px;
    }
    .uj-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 0.25rem;
        display: block;
    }
    .u-grid-2-custom {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 768px) {
        .u-grid-2-custom { grid-template-columns: 1fr; }
    }
</style>

@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Gate;
  
  $me      = auth()->user();
  $meUnit = $me ? $me->unit_id : null;
  
  // AMBIL NAMA UNIT USER LOGIN UNTUK AUTOFILL
  $meUnitName = $meUnit ? DB::table('units')->where('id', $meUnit)->value('name') : '';

  $canSeeAll       = isset($canSeeAll)       ? $canSeeAll       : false;
  $selectedUnitId = isset($selectedUnitId) ? $selectedUnitId : null;
  $units          = isset($units)          ? $units          : collect();

  if ($units->isEmpty()) {
      $units = $canSeeAll
          ? DB::table('units')->select('id','name')->orderBy('name')->get()
          : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
  }
  $unitMap = $units->pluck('name','id');
  $dhcUnitId = DB::table('units')->where(function($q){ $q->where('code','DHC')->orWhere('name','Divisi Human Capital')->orWhere('name','like','Divisi Human Capital%'); })->value('id');

  $canCreate = Gate::check('recruitment.create') || Gate::check('recruitment.update') || ($me && $me->hasRole('SDM Unit')) || ($me && $me->hasRole('Superadmin'));

  $rkapList = isset($rkapList) ? $rkapList : collect([
    (object)['name' => 'Reporter', 'rkap' => 5, 'existing' => 2],
    (object)['name' => 'KJJJ', 'rkap' => 3, 'existing' => 1],
    (object)['name' => 'Inspektor', 'rkap' => 4, 'existing' => 4],
  ]);

  $projectList = [
    ['kode' => 'PRJ001', 'nama' => 'Proyek A (Pembangunan X)'],
    ['kode' => 'PRJ002', 'nama' => 'Proyek B (Maintenance Y)'],
    ['kode' => 'PRJ003', 'nama' => 'Proyek C (Consulting Z)'],
  ];

  $positions = DB::table('positions')->select('id', 'name')->where('is_active',1)->orderBy('name')->get();

  try {
      $rawPics = DB::table('employees')->join('persons', 'employees.person_id', '=', 'persons.id')->select('employees.id', 'employees.employee_id', 'persons.full_name')->where('employees.unit_id', $selectedUnitId ?? $meUnit)->orderBy('persons.full_name')->get();
  } catch (\Exception $e) { $rawPics = collect(); }

  $picListFormatted = $rawPics->map(function($p) { return ['id' => $p->id, 'name' => ($p->employee_id ?? '-') . ' - ' . ($p->full_name ?? '-')]; })->values();
@endphp

<div class="u-card u-card--glass u-hover-lift">
  {{-- Header & Filter --}}
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>
    <form method="get" class="u-flex u-gap-sm u-items-center">
      @if($canSeeAll)
        <label class="u-text-sm u-font-medium">Unit</label>
        <select name="unit_id" class="u-input" onchange="this.form.submit()">
          <option value="">All units</option>
          @foreach($units as $u) <option value="{{ $u->id }}" @selected((string)$u->id === (string)($selectedUnitId ?? ''))>{{ $u->name }}</option> @endforeach
        </select>
      @else
        @php $unitName = ($units[0]->name ?? 'Unit Saya'); @endphp
        <span class="u-badge u-badge--glass">Scoped to: {{ $unitName }}</span>
      @endif
    </form>
    @if($canCreate)
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createApprovalModal" data-mode="create"><i class="fas fa-plus u-mr-xs"></i> Buat Permintaan</button>
    @endif
  </div>

  {{-- Notifications --}}
  @if(session('ok')) @push('swal') <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script> @endpush @endif
  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <ul class="u-list">@foreach($errors->all() as $e)<li class="u-item">{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- Tabel Utama --}}
  <div class="dt-wrapper">
    <div class="u-flex u-items-center u-justify-between u-mb-sm">
      <div class="u-font-semibold">Daftar Izin Prinsip</div>
      <span class="u-badge u-badge--glass">{{ $canSeeAll && !$selectedUnitId ? 'All units' : 'Unit ID: '.($selectedUnitId ?? $meUnit) }}</span>
    </div>
    <div class="u-scroll-x">
      <table id="ip-table" class="u-table" data-dt>
        <thead>
          <tr><th>No Ticket</th><th>Judul</th><th>Unit</th><th>Jenis Permintaan</th><th>Posisi</th><th>HC</th><th>Jenis Kontrak</th><th>Progress</th><th class="cell-actions">Aksi</th></tr>
        </thead>
        <tbody>
          @foreach($list as $r)
          @php
            $meUnit = auth()->user()->unit_id; $sameUnit = $meUnit && (string)$meUnit === (string)$r->unit_id;
            $stageIndex = null; if ($r->relationLoaded('approvals')) { foreach ($r->approvals as $i => $ap) { if (($ap->status ?? 'pending') === 'pending') { $stageIndex = $i; break; } } }
            $me = auth()->user();
            $meRoles = [ 'Superadmin' => $me && $me->hasRole('Superadmin'), 'Kepala Unit' => $me && $me->hasRole('Kepala Unit'), 'DHC' => $me && $me->hasRole('DHC'), 'Dir SDM' => $me && $me->hasRole('Dir SDM') ];
            $status = $r->status ?? 'draft';
            $employmentType  = $r->employment_type ?? $r->contract_type ?? null;
            $targetStart     = $r->target_start_date ?? $r->start_date ?? null;
            $budgetSource    = $r->budget_source_type ?? $r->budget_source ?? null;
            $requestType     = $r->request_type ?? $r->type ?? 'Rekrutmen';
            $budgetRef       = $r->budget_ref ?? $r->rkap_ref ?? $r->rab_ref ?? $r->budget_reference ?? '';
            $justif          = $r->justification ?? $r->reason ?? $r->notes ?? $r->note ?? $r->description ?? '';
            $unitNameRow     = $r->unit_id ? ($unitMap[$r->unit_id] ?? ('Unit #'.$r->unit_id)) : '-';
            
            $totalStages = 3; $progressStep = null;
            if ($status === 'draft') { $progressText = 'Draft di SDM Unit'; $progressStep = 0; }
            elseif ($status === 'rejected') { $progressText = 'Ditolak'; }
            elseif ($status === 'approved') { $progressText = 'Selesai (Approved Dir SDM)'; $progressStep = $totalStages; }
            elseif ($stageIndex === 0) { $progressText = 'Menunggu Kepala Unit'; $progressStep = 1; }
            elseif ($stageIndex === 1) { $progressText = 'Menunggu DHC'; $progressStep = 2; }
            elseif ($stageIndex === 2) { $progressText = 'Menunggu Dir SDM'; $progressStep = 3; }
            else { $progressText = 'In Review'; }

            $canStage = false;
            if(in_array($status, ['in_review','submitted']) && $stageIndex !== null) {
                if ($meRoles['Superadmin']) { $canStage = true; } 
                else {
                    if ($stageIndex === 0) { $canStage = $meRoles['Kepala Unit'] && $sameUnit; } 
                    elseif ($stageIndex === 1) { $isKepalaUnitDHC = $meRoles['Kepala Unit'] && $dhcUnitId && ((string)$meUnit === (string)$dhcUnitId); $canStage = $meRoles['DHC'] || $isKepalaUnitDHC; } 
                    elseif ($stageIndex === 2) { $canStage = $meRoles['Dir SDM']; }
                }
            }
            $recruitmentDetails = collect($r->meta['recruitment_details'] ?? []);
            $hasMultiData = $recruitmentDetails->count() > 1;
            $posObj = $positions->firstWhere('id', $r->position);
            $positionDisplay = $posObj ? $posObj->name : $r->position;
          @endphp
          <tr class="recruitment-main-row u-align-top" data-recruitment-id="{{ $r->id }}">
            <td>@if(!empty($r->ticket_number)) <span class="u-badge u-badge--primary u-text-2xs">{{ $r->ticket_number }}</span> @else <span class="u-text-2xs u-text-muted">-</span> @endif</td>
            <td style="min-width: 200px;">
                @if($hasMultiData)
                    <div class="u-flex u-flex-col u-gap-xs">
                        @foreach($recruitmentDetails as $detail)
                        <div style="border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px; margin-bottom: 4px;">
                            <span class="u-font-medium">{{ $detail['title'] ?? $r->title }}</span>
                            <div class="u-text-2xs u-muted">Dibuat {{ optional($r->created_at)->format('d M Y') ?? '-' }}</div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <span class="u-font-medium">{{ $r->title }}</span>
                    <div class="u-text-2xs u-muted">Dibuat {{ optional($r->created_at)->format('d M Y') ?? '-' }}</div>
                @endif
            </td>
            <td>{{ $unitNameRow }}</td>
            <td><span class="u-badge u-badge--glass u-text-2xs">{{ $requestType }}</span></td>
            <td>
              @if($hasMultiData)
                <div class="u-flex u-flex-col u-gap-xs">
                    @foreach($recruitmentDetails as $detail)
                    <div class="u-text-sm" style="border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px; margin-bottom: 4px;">{{ $detail['position_text'] ?? $detail['position'] ?? '-' }}</div>
                    @endforeach
                </div>
              @else {{ $positionDisplay }} @endif
            </td>
            <td><span class="u-badge u-badge--glass">{{ $r->headcount }} Orang</span></td>
            <td>@if($employmentType) <span class="u-badge u-badge--glass">{{ $employmentType }}</span> @else - @endif</td>
            <td><div class="u-text-2xs"><span class="u-badge u-badge--glass">{{ $progressText }}</span></div></td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                @if($status === 'draft' && ($sameUnit || $meRoles['Superadmin']))
                  @if($canCreate)
                    <button type="button" class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Edit draft" 
                          data-modal-open="createApprovalModal" 
                          data-mode="edit" 
                          data-update-url="{{ route('recruitment.principal-approval.update',$r) }}" 
                          data-delete-url="{{ route('recruitment.principal-approval.destroy', ['req' => $r->id]) }}"
                          data-request-type="{{ e($requestType) }}" 
                          data-title="{{ e($r->title) }}" 
                          data-position="{{ e($r->position) }}" 
                          data-headcount="{{ (int) $r->headcount }}" 
                          data-employment-type="{{ e($employmentType ?? '') }}" 
                          data-target-start="{{ $targetStart }}" 
                          data-budget-source-type="{{ e($budgetSource ?? '') }}" 
                          data-budget-ref="{{ e($budgetRef) }}" 
                          data-justification="{{ e($justif) }}"
                          data-meta-json='{{ json_encode($r->meta['recruitment_details'] ?? []) }}'>
                          <i class="fas fa-edit u-mr-xs"></i> Edit
                    </button>
                    <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}" class="u-inline js-confirm">@csrf<button class="u-btn u-btn--outline u-btn--sm u-hover-lift"><i class="fas fa-paper-plane u-mr-xs"></i> Submit</button></form>
                  @endif
                @endif
                <button type="button" class="u-btn u-btn--outline u-btn--sm u-hover-lift js-open-detail" 
                        data-modal-open="detailApprovalModal" 
                        data-id="{{ $r->id }}" 
                        data-ticket-number="{{ $r->ticket_number ?? '-' }}" 
                        data-title="{{ e($r->title) }}" 
                        data-unit="{{ e($unitNameRow) }}" 
                        data-request-type="{{ e($requestType) }}" 
                        data-position="{{ e($positionDisplay) }}" 
                        data-headcount="{{ (int) $r->headcount }}" 
                        data-employment-type="{{ e($employmentType ?? '') }}" 
                        data-target-start="{{ $targetStart ? \Illuminate\Support\Carbon::parse($targetStart)->format('d M Y') : '-' }}" 
                        data-budget-source="{{ e($budgetSource ?? '') }}" 
                        data-budget-ref="{{ e($budgetRef) }}" 
                        data-justification="{{ e($justif) }}" 
                        data-status="{{ e(ucfirst($status)) }}" 
                        data-can-approve="{{ $canStage ? 'true' : 'false' }}" 
                        data-approve-url="{{ route('recruitment.principal-approval.approve',$r) }}" 
                        data-reject-url="{{ route('recruitment.principal-approval.reject',$r) }}" 
                        data-meta-json='{{ json_encode($r->meta['recruitment_details'] ?? []) }}'>
                        <i class="fas fa-info-circle u-mr-xs"></i> Detail
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- MODAL URAIAN JABATAN (FORM BASED) - Z-INDEX 2000 --}}
<div id="uraianModal" class="u-modal" hidden>
  <div class="u-modal__card modal-card-wide">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="u-title">Form Uraian Jabatan</div>
            <div class="u-muted u-text-sm">Silakan lengkapi data uraian jabatan.</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="u-modal__body u-p-md" style="background-color: #f9fafb;">
      <form id="uraianForm" class="u-space-y-md">
        
        {{-- SECTION 1: IDENTITAS JABATAN --}}
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">1. Identitas Jabatan</h3>
            <div class="u-grid-2-custom">
                {{-- Kiri --}}
                <div class="u-space-y-sm">
                    <div><label class="uj-label">Nama Jabatan</label><input type="text" class="u-input" id="uj_nama" placeholder="Otomatis dari Posisi/Job Function"></div>
                    <div><label class="uj-label">Unit Kerja (Divisi/UUS/Cabang)</label><input type="text" class="u-input" id="uj_unit" placeholder="Otomatis dari Unit User"></div>
                </div>
                {{-- Kanan --}}
                <div class="u-space-y-sm">
                    <div><label class="uj-label">Melapor Pada</label><input type="text" class="u-input" id="uj_melapor" placeholder="Jabatan Atasan Langsung"></div>
                    <div><label class="uj-label">Pemangku</label><input type="text" class="u-input" id="uj_pemangku" placeholder="Nama Pemangku"></div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: TUJUAN JABATAN --}}
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">2. Tujuan Jabatan</h3>
            <div class="u-mb-xs u-text-xs u-muted">Kesimpulan dari akuntabilitas utama, dalam satu kalimat.</div>
            <textarea class="u-input" rows="3" id="uj_tujuan"></textarea>
        </div>

        {{-- SECTION 3: AKUNTABILITAS UTAMA --}}
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">3. Akuntabilitas Utama</h3>
            <div class="u-mb-xs u-text-xs u-muted">Uraian tugas dan tujuan tugas (gunakan poin-poin).</div>
            <textarea class="u-input" rows="6" id="uj_akuntabilitas"></textarea>
        </div>

        {{-- SECTION 4: DIMENSI --}}
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">4. Dimensi</h3>
            <div class="u-space-y-sm">
                <div class="u-grid-2-custom">
                    <div>
                        <label class="uj-label">Dimensi Keuangan</label>
                        <input type="text" class="u-input" id="uj_dimensi_keuangan" placeholder="-">
                    </div>
                    <div>
                        <label class="uj-label">Dimensi Non-Keuangan</label>
                        <input type="text" class="u-input" id="uj_dimensi_non_keuangan" placeholder="-">
                    </div>
                    
                </div>
                <div class="u-grid-2-custom">
                    <div>
                        <label class="uj-label">Anggaran (Rp)</label>
                        <input type="text" class="u-input" id="uj_anggaran" placeholder="1.000.000">
                    </div>
                    <div>
                        <label class="uj-label">Bawahan Langsung</label>
                        <input type="text" class="u-input" id="uj_bawahan_langsung" placeholder="-">
                    </div>
                    <div>
                        <label class="uj-label">Total Staff</label>
                        <input type="text" class="u-input" id="uj_total_staff" placeholder="-">
                    </div>
                    <div>
                        <label class="uj-label">Total Pegawai (Unit Kerja)</label>
                        <input type="text" class="u-input" id="uj_total_pegawai" placeholder="-">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 5: WEWENANG --}}
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">5. Wewenang</h3>
            <div class="u-mb-xs u-text-xs u-muted">Suatu tugas yang dapat dilakukan pemangku jabatan tanpa meminta persetujuan atasan.</div>
            <textarea class="u-input" rows="4" id="uj_wewenang"></textarea>
        </div>

        {{-- SECTION 6: HUBUNGAN KERJA --}}
        <div class="u-card u-p-md">
          <h3 class="uj-section-title">6. Hubungan Kerja</h3>
          <div class="u-mb-xs u-text-xs u-muted">Jelaskan hubungan kerja internal dan eksternal</div>
          <div class="u-grid-2-custom">
            <div>
              <label class="uj-label">Internal</label>
              <textarea class="u-input" rows="3" id="uj_hub_internal" placeholder="Hubungan internal..."></textarea>
            </div>
            <div>
              <label class="uj-label">Eksternal</label>
              <textarea class="u-input" rows="3" id="uj_hub_eksternal" placeholder="Hubungan eksternal..."></textarea>
            </div>
          </div>
        </div>

        {{-- SECTION 7: SPESIFIKASI JABATAN --}}
        <div class="u-card u-p-md">
          <h3 class="uj-section-title">7. Spesifikasi Jabatan</h3>
          <div class="u-mb-xs u-text-xs u-muted">Latar belakang pendidikan, pengalaman, pengetahuan & kompetensi</div>
          <div class="u-space-y-sm">
            <div>
              <label class="uj-label">Latar belakang pendidikan dan pengalaman</label>
              <textarea class="u-input" rows="3" id="uj_spek_pendidikan" placeholder="Deskripsi pendidikan & pengalaman"></textarea>
            </div>
            <div>
              <label class="uj-label">Pengetahuan dan ketrampilan</label>
              <textarea class="u-input" rows="3" id="uj_spek_pengetahuan" placeholder="Pengetahuan & ketrampilan yang dibutuhkan"></textarea>
            </div>
            <div>
              <label class="uj-label">Kompetensi perilaku</label>
              <textarea class="u-input" rows="3" id="uj_spek_kompetensi" placeholder="Kompetensi perilaku umum"></textarea>
            </div>
            <div class="u-grid-2-custom">
              <div>
                <label class="uj-label">Kompetensi Wajib</label>
                <textarea class="u-input" rows="3" id="uj_spek_kompetensi_wajib" placeholder="Kompetensi wajib (pisahkan baris)"></textarea>
              </div>
              <div>
                <label class="uj-label">Kompetensi Generik Pendukung</label>
                <textarea class="u-input" rows="3" id="uj_spek_kompetensi_generik" placeholder="Kompetensi generik pendukung (pisahkan baris)"></textarea>
              </div>
            </div>
          </div>
        </div>

        {{-- SECTION 8: STRUKTUR ORGANISASI --}}
        <div class="u-card u-p-md">
          <h3 class="uj-section-title">8. Struktur organisasi</h3>
          <div class="u-mb-xs u-text-xs u-muted">Unggah gambar struktur organisasi (opsional)</div>
          <div class="u-space-y-sm">
            <input type="file" accept="image/*" id="uj_struktur" class="u-input" />
            <div id="uj_struktur_preview" style="margin-top:8px;"></div>
          </div>
        </div>

      </form>
    </div>

    <div class="u-modal__foot">
      <div class="u-flex u-justify-between u-items-center u-gap-sm">
        <div id="uj_status_display" class="u-badge u-badge--glass">Status: Draft</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--outline u-btn--primary" id="btnPreviewPdf" style="display:none;">
            <i class="fas fa-file-pdf u-mr-xs"></i> Preview PDF
          </button>
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
          <button type="button" class="u-btn u-btn--outline js-save-uraian-form" data-status="Draft">Simpan Draft</button>
          <button type="button" class="u-btn u-btn--brand js-save-uraian-form" data-status="Final">Simpan & Finalisasi</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL CREATE (UTAMA) - Z-INDEX 1050 --}}
<div id="createApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div><div class="u-title" id="ip-modal-title">Buat Izin Prinsip Baru</div><div class="u-muted u-text-sm" id="ip-modal-subtitle">Ajukan permintaan rekrutmen atau perpanjangan kontrak</div></div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="u-modal__body">
      <form method="POST" action="{{ route('recruitment.principal-approval.store') }}" class="u-space-y-md u-p-md" id="createApprovalForm" data-default-action="{{ route('recruitment.principal-approval.store') }}">
        @csrf
        <input type="hidden" name="details_json" id="detailsJson">

        {{-- [SECTION STATIS] --}}
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Permintaan</label>
          <select class="u-input" name="request_type"><option value="Rekrutmen">Rekrutmen</option><option value="Perpanjang Kontrak">Perpanjang Kontrak</option></select>
        </div>
        
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Kontrak</label>
            <select class="u-input" id="contractTypeSelect" name="employment_type"><option value="">Pilih jenis kontrak</option><option value="Organik">Organik</option><option value="Project Based">Project Based</option></select>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Sumber Anggaran</label>
            <select class="u-input" id="budgetSourceSelect" name="budget_source_type" style="-webkit-appearance: none; -moz-appearance: none; appearance: none;"><option value="">Sumber anggaran</option><option value="RKAP">RKAP</option><option value="RAB Proyek">RAB Proyek</option><option value="Lainnya">Lainnya</option></select>
          </div>
        </div>

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Headcount</label>
          <input class="u-input" type="number" min="1" name="headcount" id="headcountInput" value="1" placeholder="Jumlah orang" required>
        </div>
        <div id="dataTabsContainer" class="u-flex u-gap-sm u-flex-wrap u-mb-sm" style="display:none;">
           {{-- Tombol Data 1, Data 2, dll di-generate via JS --}}
        </div>

        {{-- [SECTION DINAMIS] --}}
        <div id="dynamicContentWrapper" class="u-p-md u-border u-rounded u-bg-light">
            <div class="u-space-y-sm u-mb-md">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Judul Permintaan</label>
              <input class="u-input" id="titleInput" name="title" placeholder="Mis. Rekrutmen Analis TKDN Proyek X" required>
            </div>

            {{-- SECTION PROJECT BASED --}}
            <div id="projectSection" class="u-space-y-md" style="display:none;">
              <div class="u-flex u-items-center u-justify-between"><div><label class="u-block u-text-sm u-font-medium u-mb-sm">Data Project</label><div class="u-text-2xs u-muted">Pilih kode project, nama project akan otomatis terisi</div></div></div>
              <div class="u-grid-2 u-stack-mobile u-gap-md">
                <div class="u-space-y-sm">
                  <label class="u-block u-text-sm u-font-medium u-mb-sm">Kode Project</label>
                  <select class="u-input" id="kodeProjectSelect" name="kode_project">
                    <option value="">Pilih kode project</option>
                    @foreach($projectList as $p) <option value="{{ $p['kode'] }}" data-nama="{{ $p['nama'] }}">{{ $p['kode'] }}</option> @endforeach
                  </select>
                </div>
                <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Nama Project</label><input class="u-input" id="namaProjectInput" name="nama_project" readonly placeholder="Nama project akan terisi otomatis"></div>
              </div>
              <div class="u-space-y-sm" style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi Jabatan (Project)</label>
                <input type="text" id="positionSearchInput" name="position_text" class="u-input" placeholder="Ketik untuk mencari jabatan..." autocomplete="off">
                <input type="hidden" name="position" id="positionInput">
                <div id="positionSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
              </div>
              <div class="u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan</label>
                <div class="u-flex u-items-center u-gap-sm">
                  <div id="uraianStatusProject" class="u-text-2xs u-muted">Belum ada uraian</div>
                  <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian-project">Isi Uraian</button>
                </div>
              </div>
              <div class="u-space-y-sm" style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC</label>
                <input type="text" id="picProjectSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
                <input type="hidden" name="pic_project" id="picProjectInput">
                <div id="picProjectSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
              </div>
            </div>

            {{-- SECTION ORGANIK --}}
            <div id="rkapSection" class="u-space-y-md" style="display:none;">
              <div class="u-flex u-items-center u-justify-between"><div><label class="u-block u-text-sm u-font-medium u-mb-sm">Tabel RKAP 2025</label><div class="u-text-2xs u-muted">Pilih job function untuk memakai kuota RKAP</div></div></div>
              <div class="u-scroll-x">
                <table class="u-table u-table--striped" id="rkap-table">
                  <thead><tr><th>Job Function</th><th>RKAP 2025</th><th>Jumlah karyawan existing</th><th class="cell-actions">Aksi</th></tr></thead>
                  <tbody>
                    @foreach($rkapList as $j)
                      <tr data-job-name="{{ $j->name }}" data-job-rkap="{{ $j->rkap }}" data-job-existing="{{ $j->existing }}">
                        <td>{{ $j->name }}</td><td>{{ $j->rkap }}</td><td>{{ $j->existing }}</td>
                        <td class="cell-actions"><button type="button" class="u-btn u-btn--sm u-btn--outline js-rkap-select" title="Pilih {{ $j->name }}">+</button></td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <div id="rkapSelectedInfo" style="display:none;margin-top:12px;" class="u-space-y-sm">
                <div class="u-text-sm u-font-medium">Selected: <span id="rkapSelectedName"></span></div>
                <div class="u-grid-2 u-stack-mobile u-gap-md">
                  <div>
                    <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan</label>
                    <div class="u-flex u-items-center u-gap-sm">
                      <div id="uraianStatus" class="u-text-2xs u-muted">Belum ada uraian</div>
                      <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian">Isi Uraian</button>
                    </div>
                  </div>
                  <div>
                    <div style="position: relative;" class="u-mb-md">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC</label>
                      <input type="text" id="picOrganikSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
                      <input type="hidden" name="pic" id="picOrganikInput">
                      <div id="picOrganikSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                    </div>
                    <div style="position: relative;">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi Jabatan (Organik)</label>
                      <input type="text" id="positionOrganikSearchInput" class="u-input" placeholder="Cari atau ketik posisi jabatan..." autocomplete="off">
                      <input type="hidden" id="positionOrganikInput"> 
                      <div id="positionOrganikSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Tanggal Mulai Kerja --}}
            <div class="u-space-y-sm u-mt-md">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Mulai Kerja</label>
              <input class="u-input" type="date" id="targetStartInput" name="target_start_date">
            </div>
        </div>

        {{-- Justifikasi --}}
        <div class="u-space-y-sm u-mt-md">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Justifikasi</label>
            <textarea class="u-input" name="justification" rows="4" placeholder="Jelaskan kebutuhan rekrutmen..."></textarea>
        </div>
      </form>
    </div>
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <form method="POST" action="" id="deleteDraftForm" class="u-inline js-confirm" data-confirm-title="Hapus Draft?" style="display:none;">@csrf @method('DELETE')<button type="submit" class="u-btn u-btn--outline u-hover-lift" style="color:#ef4444; border-color:#ef4444;"><i class="fas fa-trash-alt u-mr-xs"></i> Hapus</button></form>
        <button form="createApprovalForm" class="u-btn u-btn--brand u-hover-lift" id="submitApprovalBtn"><i class="fas fa-save u-mr-xs"></i> Simpan Draft</button>
      </div>
    </div>
  </div>
</div>

{{-- DETAIL APPROVAL (READ ONLY) --}}
<div id="detailApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card" style="max-width: 900px;"> 
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-info-circle"></i></div>
        <div>
          <div class="u-title">Detail Izin Prinsip</div>
          <div class="u-muted u-text-sm">Informasi lengkap permintaan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="u-modal__body u-p-md">
        <div id="detailTabsContainer" class="u-flex u-gap-sm u-flex-wrap u-mb-md u-border-b u-pb-sm"></div>
        <div id="detailContentContainer" class="u-animate-fade-in">
            <div class="u-grid-2 u-stack-mobile u-gap-lg">
                <div class="u-space-y-sm">
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">No Ticket</div><div class="u-font-medium" id="view-ticket">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Judul</div><div class="u-font-medium" id="view-title">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Jenis Permintaan</div><div id="view-request-type">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Headcount</div><div id="view-headcount">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Target Mulai</div><div id="view-target">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">PIC Request</div><div id="view-pic">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Justifikasi</div><div id="view-justification" class="u-text-sm u-muted u-p-sm u-bg-light u-rounded u-mt-xs" style="white-space: pre-line;">-</div></div>
                </div>
                <div class="u-space-y-sm">
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Status</div><div id="view-status">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Unit</div><div id="view-unit">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Posisi</div><div id="view-position" class="u-font-medium">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Jenis Kontrak</div><div id="view-employment">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Sumber Anggaran</div><div id="view-budget-source">-</div></div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Uraian Jabatan</div>
                        <div class="u-mt-xs">
                             <button type="button" id="btn-view-pdf-detail" class="u-btn u-btn--xs u-btn--outline" style="display:none;">
                                <i class="fas fa-file-pdf u-mr-xs"></i> Download PDF
                             </button>
                             <div id="view-uraian-status" class="u-text-2xs u-muted u-mt-xxs">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="u-modal__foot">
      <div class="u-flex u-justify-between u-items-center u-gap-sm">
        <div class="u-muted u-text-sm"><span id="tab-indicator-text">Menampilkan Data 1</span></div>
        <div class="u-flex u-gap-sm action-buttons">
          <form method="POST" action="" class="detail-approve-form u-inline js-confirm" style="display:none;">@csrf<button type="submit" class="u-btn u-btn--brand u-success detail-approve-btn" data-confirm-title="Setujui permintaan?" data-confirm-text="Data pada tab yang aktif akan disetujui." data-confirm-icon="success"><i class="fas fa-check u-mr-xs"></i> Approve</button></form>
          <form method="POST" action="" class="detail-reject-form u-inline js-confirm" style="display:none;">@csrf<button type="submit" class="u-btn u-btn--outline u-danger detail-reject-btn" data-confirm-title="Tolak permintaan?" data-confirm-text="Permintaan ini akan ditolak." data-confirm-icon="error"><i class="fas fa-times u-mr-xs"></i> Reject</button></form>
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Tutup</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const positionsData = {!! json_encode($positions) !!};
  const picData       = {!! json_encode($picListFormatted) !!};
  // Pass variabel unit kerja user login ke JS
  const meUnitName    = {!! json_encode($meUnitName) !!}; 

  const page = {
    dt: null,
    init() { 
        this.bindModal(); 
        this.initDT(); 
        this.bindExternalSearch(); 
    },

    bindModal() {
      const modalMain   = document.getElementById('createApprovalModal');
      const detailModal = document.getElementById('detailApprovalModal');
      const uraianModal = document.getElementById('uraianModal');
      
      const form          = document.getElementById('createApprovalForm');
      const submitBtn     = document.getElementById('submitApprovalBtn');
      const modalTitle    = document.getElementById('ip-modal-title');
      const modalSubtitle = document.getElementById('ip-modal-subtitle');
      
      const contractTypeSelect = form.querySelector('#contractTypeSelect');
      const budgetSourceSelect = form.querySelector('#budgetSourceSelect');
      const headcountInput     = form.querySelector('#headcountInput');
      const dataTabsContainer  = document.getElementById('dataTabsContainer');
      const detailsJsonInput   = document.getElementById('detailsJson');
      
      // Inputs Form Utama
      const requestTypeSelect  = form.querySelector('[name="request_type"]');
      const titleInput         = form.querySelector('#titleInput');
      const targetStartInput   = form.querySelector('#targetStartInput');
      const justifInput        = form.querySelector('[name="justification"]');

      // Elements Form Uraian (Baru - Sesuai Gambar)
      const uraianForm = document.getElementById('uraianForm');
      const ujInputs = {
          nama: document.getElementById('uj_nama'),
          unit: document.getElementById('uj_unit'),
          pemangku: document.getElementById('uj_pemangku'),
          melapor: document.getElementById('uj_melapor'),
          tujuan: document.getElementById('uj_tujuan'),
          akuntabilitas: document.getElementById('uj_akuntabilitas'),
          
          // Section 4 Dimensi
          dimensi_keuangan: document.getElementById('uj_dimensi_keuangan'),
          anggaran: document.getElementById('uj_anggaran'),
          dimensi_non_keuangan: document.getElementById('uj_dimensi_non_keuangan'),
          bawahan_langsung: document.getElementById('uj_bawahan_langsung'),
          total_staff: document.getElementById('uj_total_staff'),
          total_pegawai: document.getElementById('uj_total_pegawai'),
          
          // Section 5 Wewenang
          wewenang: document.getElementById('uj_wewenang'),
          
          // Section 6 Hubungan Kerja
          hub_internal: document.getElementById('uj_hub_internal'),
          hub_eksternal: document.getElementById('uj_hub_eksternal'),
          
          // Section 7 Spesifikasi Jabatan
          spek_pendidikan: document.getElementById('uj_spek_pendidikan'),
          spek_pengetahuan: document.getElementById('uj_spek_pengetahuan'),
          spek_kompetensi: document.getElementById('uj_spek_kompetensi'),
          spek_kompetensi_wajib: document.getElementById('uj_spek_kompetensi_wajib'),
          spek_kompetensi_generik: document.getElementById('uj_spek_kompetensi_generik'),
          
          // Section 8 Struktur Organisasi
          struktur: document.getElementById('uj_struktur')
      };
      const btnPreviewPdf = document.getElementById('btnPreviewPdf');
      const uraianStatusDisplay = document.getElementById('uj_status_display');

      // Handle file input for struktur organisasi (convert to base64)
      if(ujInputs.struktur) {
          ujInputs.struktur.addEventListener('change', function(e) {
              const file = e.target.files[0];
              const previewDiv = document.getElementById('uj_struktur_preview');
              if(file && file.type.startsWith('image/')) {
                  const reader = new FileReader();
                  reader.onload = function(evt) {
                      // Simpan base64 di memory untuk PDF nanti
                      ujInputs.struktur._base64 = evt.target.result;
                      // Preview gambar
                      previewDiv.innerHTML = `<img src="${evt.target.result}" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />`;
                  };
                  reader.readAsDataURL(file);
              } else if(file) {
                  alert('Silakan pilih file gambar (JPG, PNG, dll)');
                  previewDiv.innerHTML = '';
              }
          });
      }


      // Organik & Project Logic References
      const rkapSection      = form.querySelector('#rkapSection');
      const rkapSelectedInfo = form.querySelector('#rkapSelectedInfo');
      const rkapSelectedName = form.querySelector('#rkapSelectedName');
      const uraianStatus     = form.querySelector('#uraianStatus'); 
      const projectSection       = form.querySelector('#projectSection');
      const kodeProjectSelect    = form.querySelector('#kodeProjectSelect');
      const namaProjectInput     = form.querySelector('#namaProjectInput');
      const uraianStatusProject  = form.querySelector('#uraianStatusProject');
      
      // Searchables
      const picOrganikSearchInput = form.querySelector('#picOrganikSearchInput');
      const picOrganikInput       = form.querySelector('#picOrganikInput');
      const picOrganikResults     = form.querySelector('#picOrganikSearchResults');
      const positionOrganikSearchInput = form.querySelector('#positionOrganikSearchInput');
      const positionOrganikInput       = form.querySelector('#positionOrganikInput');
      const positionOrganikSearchResults = form.querySelector('#positionOrganikSearchResults');
      const positionSearchInput  = form.querySelector('#positionSearchInput');
      const positionInput        = form.querySelector('#positionInput');
      const positionSearchResults = form.querySelector('#positionSearchResults');
      const picProjectSearchInput = form.querySelector('#picProjectSearchInput');
      const picProjectInput       = form.querySelector('#picProjectInput');
      const picProjectResults     = form.querySelector('#picProjectSearchResults');

      // State Multi Data
      let activeDataIndex = 1;
      let totalDataCount  = 1;
      let multiDataStore  = {}; 

      function getActiveContractType() { return contractTypeSelect ? contractTypeSelect.value : ''; }

      // --- LOGIC FORM URAIAN (POPUP) ---
      window.openUraianForm = function(currentData, mode) {
          // Reset Form
          uraianForm.reset();
          
          // Fill Data if Exists
          const d = currentData.uraian_data || {};
          ujInputs.nama.value = d.nama || '';
          ujInputs.unit.value = d.unit || '';
          ujInputs.pemangku.value = d.pemangku || '';
          ujInputs.melapor.value = d.melapor || '';
          ujInputs.tujuan.value = d.tujuan || '';
          ujInputs.akuntabilitas.value = d.akuntabilitas || '';
          
          // Populate Dimensi
          if(ujInputs.dimensi_keuangan) ujInputs.dimensi_keuangan.value = d.dimensi_keuangan || '';
          if(ujInputs.anggaran) ujInputs.anggaran.value = d.anggaran || '';
          if(ujInputs.dimensi_non_keuangan) ujInputs.dimensi_non_keuangan.value = d.dimensi_non_keuangan || '';
          if(ujInputs.bawahan_langsung) ujInputs.bawahan_langsung.value = d.bawahan_langsung || '';
          if(ujInputs.total_staff) ujInputs.total_staff.value = d.total_staff || '';
          if(ujInputs.total_pegawai) ujInputs.total_pegawai.value = d.total_pegawai || '';
          
          if(ujInputs.wewenang) ujInputs.wewenang.value = d.wewenang || '';

          // Populate Hubungan Kerja (6)
          if(ujInputs.hub_internal) ujInputs.hub_internal.value = d.hub_internal || '';
          if(ujInputs.hub_eksternal) ujInputs.hub_eksternal.value = d.hub_eksternal || '';

          // Populate Spesifikasi Jabatan (7)
          if(ujInputs.spek_pendidikan) ujInputs.spek_pendidikan.value = d.spek_pendidikan || '';
          if(ujInputs.spek_pengetahuan) ujInputs.spek_pengetahuan.value = d.spek_pengetahuan || '';
          if(ujInputs.spek_kompetensi) ujInputs.spek_kompetensi.value = d.spek_kompetensi || '';
          if(ujInputs.spek_kompetensi_wajib) ujInputs.spek_kompetensi_wajib.value = d.spek_kompetensi_wajib || '';
          if(ujInputs.spek_kompetensi_generik) ujInputs.spek_kompetensi_generik.value = d.spek_kompetensi_generik || '';

          // Preview struktur organisasi if exists (8)
          if(d.struktur_organisasi) {
              const previewDiv = document.getElementById('uj_struktur_preview');
              if(previewDiv) {
                  previewDiv.innerHTML = `<img src="${d.struktur_organisasi}" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />`;
              }
              if(ujInputs.struktur) ujInputs.struktur._base64 = d.struktur_organisasi;
          }

          // --- AUTO FILL LOGIC (START) ---
          
          // 1. Autofill Unit Kerja (jika kosong, isi dengan unit user login)
          if (!ujInputs.unit.value && meUnitName) {
              ujInputs.unit.value = meUnitName;
          }

          // 2. Autofill Nama Jabatan (jika kosong)
          if (!ujInputs.nama.value) {
              if (mode === 'organik') {
                  // Coba ambil dari row RKAP selected
                  const selectedRow = form.querySelector('.js-rkap-select.selected');
                  if(selectedRow) {
                      ujInputs.nama.value = selectedRow.closest('tr').dataset.jobName;
                  } 
                  // Atau coba ambil dari input posisi manual organik
                  else if (positionOrganikSearchInput && positionOrganikSearchInput.value) {
                      ujInputs.nama.value = positionOrganikSearchInput.value;
                  }
              } else if (mode === 'project') {
                  // Ambil dari input posisi project
                  if(positionSearchInput && positionSearchInput.value) {
                      ujInputs.nama.value = positionSearchInput.value;
                  }
              }
          }
          // --- AUTO FILL LOGIC (END) ---

          const status = currentData.uraian_status || 'Belum diisi';
          uraianStatusDisplay.textContent = 'Status: ' + status;
          
          // Logic Tombol Preview
          if(status === 'Final' || status === 'Finalized') {
              btnPreviewPdf.style.display = 'inline-flex';
              btnPreviewPdf.dataset.json = JSON.stringify(d);
          } else {
              btnPreviewPdf.style.display = 'none';
          }

          // Show Modal with High Z-Index & Flex Display
          uraianModal.hidden = false;
          uraianModal.style.zIndex = '2000'; // Safety force inline style
          document.body.classList.add('modal-open');
      };

      // Handle Save Button di dalam Modal Uraian
      document.addEventListener('click', function(e) {
          if(e.target.classList.contains('js-save-uraian-form')) {
              const status = e.target.getAttribute('data-status');
              
              // Collect Data
              const dataObj = {
                  nama: ujInputs.nama.value,
                  unit: ujInputs.unit.value,
                  pemangku: ujInputs.pemangku.value,
                  melapor: ujInputs.melapor.value,
                  tujuan: ujInputs.tujuan.value,
                  akuntabilitas: ujInputs.akuntabilitas.value,
                  
                  dimensi_keuangan: ujInputs.dimensi_keuangan ? ujInputs.dimensi_keuangan.value : '',
                  anggaran: ujInputs.anggaran ? ujInputs.anggaran.value : '',
                  dimensi_non_keuangan: ujInputs.dimensi_non_keuangan ? ujInputs.dimensi_non_keuangan.value : '',
                  bawahan_langsung: ujInputs.bawahan_langsung ? ujInputs.bawahan_langsung.value : '',
                  total_staff: ujInputs.total_staff ? ujInputs.total_staff.value : '',
                  total_pegawai: ujInputs.total_pegawai ? ujInputs.total_pegawai.value : '',
                  
                  wewenang: ujInputs.wewenang ? ujInputs.wewenang.value : '',
                  
                  hub_internal: ujInputs.hub_internal ? ujInputs.hub_internal.value : '',
                  hub_eksternal: ujInputs.hub_eksternal ? ujInputs.hub_eksternal.value : '',
                  
                  spek_pendidikan: ujInputs.spek_pendidikan ? ujInputs.spek_pendidikan.value : '',
                  spek_pengetahuan: ujInputs.spek_pengetahuan ? ujInputs.spek_pengetahuan.value : '',
                  spek_kompetensi: ujInputs.spek_kompetensi ? ujInputs.spek_kompetensi.value : '',
                  spek_kompetensi_wajib: ujInputs.spek_kompetensi_wajib ? ujInputs.spek_kompetensi_wajib.value : '',
                  spek_kompetensi_generik: ujInputs.spek_kompetensi_generik ? ujInputs.spek_kompetensi_generik.value : '',
                  
                  struktur_organisasi: (ujInputs.struktur && ujInputs.struktur._base64) ? ujInputs.struktur._base64 : ''
              };

              // Simpan ke Store
              if(!multiDataStore[activeDataIndex]) multiDataStore[activeDataIndex] = {};
              multiDataStore[activeDataIndex].uraian_data = dataObj;
              multiDataStore[activeDataIndex].uraian_status = status; // Draft / Final

              // Update UI di form utama
              const type = getActiveContractType();
              const textStatus = (status === 'Final') ? 'Tersimpan (Final)' : 'Tersimpan (Draft)';
              
              if(type === 'Organik') {
                  if(uraianStatus) uraianStatus.textContent = textStatus;
              } else {
                  if(uraianStatusProject) uraianStatusProject.textContent = textStatus;
              }

              // Update tombol preview di modal itu sendiri
              if(status === 'Final') {
                  btnPreviewPdf.dataset.json = JSON.stringify(dataObj);
                  btnPreviewPdf.style.display = 'inline-flex';
                  uraianStatusDisplay.textContent = 'Status: Final';
                  alert('Data berhasil difinalisasi.');
              } else {
                  // Jika draft, tutup modal
                  uraianModal.hidden = true;
                  // Jangan hapus class modal-open jika modal utama masih terbuka
              }
          }
      });

      // Handle Preview PDF Button
      function submitPdfForm(jsonData) {
          if(!jsonData) return;
          let formPdf = document.getElementById('pdf-generator-form');
          if(!formPdf) {
              formPdf = document.createElement('form');
              formPdf.id = 'pdf-generator-form';
              formPdf.method = 'POST';
              formPdf.action = "{{ route('recruitment.uraian-jabatan.preview-pdf') }}"; 
              formPdf.target = '_blank';
              const csrf = document.createElement('input');
              csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = "{{ csrf_token() }}";
              formPdf.appendChild(csrf);
              const inp = document.createElement('input');
              inp.type = 'hidden'; inp.name = 'data'; inp.id = 'pdf-data-input';
              formPdf.appendChild(inp);
              document.body.appendChild(formPdf);
          }
          document.getElementById('pdf-data-input').value = jsonData;
          formPdf.submit();
      }

      if(btnPreviewPdf) {
          btnPreviewPdf.addEventListener('click', function() {
              submitPdfForm(this.dataset.json);
          });
      }

      // --- LOGIC FORM UTAMA (Sama seperti sebelumnya) ---
      function saveCurrentTabData() {
          const type = getActiveContractType();
          const idx = activeDataIndex;
          if (!multiDataStore[idx]) multiDataStore[idx] = {};

          multiDataStore[idx].title = titleInput.value;
          multiDataStore[idx].target_start_date = targetStartInput.value;

          if (type === 'Organik') {
              const selectedRow = form.querySelector('.js-rkap-select.selected');
              const rkapJob = selectedRow ? selectedRow.closest('tr').dataset.jobName : null;
              multiDataStore[idx].type = 'Organik';
              multiDataStore[idx].rkap_job = rkapJob;
              
              multiDataStore[idx].pic_id = picOrganikInput.value;
              multiDataStore[idx].pic_text = picOrganikSearchInput.value;
              multiDataStore[idx].position = positionOrganikInput.value; 
              multiDataStore[idx].position_text = positionOrganikSearchInput.value;
          } else if (type === 'Project Based') {
              multiDataStore[idx].type = 'Project Based';
              multiDataStore[idx].project_code = kodeProjectSelect.value;
              multiDataStore[idx].project_name = namaProjectInput.value;
              multiDataStore[idx].position = positionInput.value; 
              multiDataStore[idx].position_text = positionSearchInput.value;
              multiDataStore[idx].pic_id = picProjectInput.value;
              multiDataStore[idx].pic_text = picProjectSearchInput.value;
          }
      }

      function loadTabData(idx) {
          resetDynamicInputs(); 
          const data = multiDataStore[idx];
          if (!data) return; 
          if(data.title) titleInput.value = data.title;
          if(data.target_start_date) targetStartInput.value = data.target_start_date;

          const type = getActiveContractType();
          const statusText = (data.uraian_status === 'Final') ? 'Tersimpan (Final)' : (data.uraian_status === 'Draft' ? 'Tersimpan (Draft)' : 'Belum ada uraian');

          if (type === 'Organik' && data.type === 'Organik') {
              if (data.rkap_job) {
                  const rows = form.querySelectorAll('#rkap-table tbody tr');
                  rows.forEach(tr => {
                      if (tr.dataset.jobName === data.rkap_job) {
                          const btn = tr.querySelector('.js-rkap-select');
                          toggleRkapSelect(btn, true);
                      }
                  });
              }
              if(uraianStatus) uraianStatus.textContent = statusText;
              
              if(data.pic_id) picOrganikInput.value = data.pic_id;
              if(data.pic_text) picOrganikSearchInput.value = data.pic_text;
              if(data.position) positionOrganikInput.value = data.position;
              if(data.position_text) positionOrganikSearchInput.value = data.position_text;

          } else if (type === 'Project Based' && data.type === 'Project Based') {
              if(data.project_code) {
                  kodeProjectSelect.value = data.project_code;
                  namaProjectInput.value = data.project_name || ''; 
              }
              if(data.position) positionInput.value = data.position;
              if(data.position_text) positionSearchInput.value = data.position_text;
              if(uraianStatusProject) uraianStatusProject.textContent = statusText;
              
              if(data.pic_id) picProjectInput.value = data.pic_id;
              if(data.pic_text) picProjectSearchInput.value = data.pic_text;
          }
      }

      function resetDynamicInputs() {
          if(titleInput) titleInput.value = '';
          if(targetStartInput) targetStartInput.value = '';
          // Organik
          form.querySelectorAll('.js-rkap-select.selected').forEach(b => {
             b.classList.remove('selected', 'u-success'); b.classList.add('u-btn--outline'); b.innerHTML = '+';
          });
          if(rkapSelectedInfo) rkapSelectedInfo.style.display = 'none';
          if(rkapSelectedName) rkapSelectedName.textContent = '';
          if(uraianStatus) uraianStatus.textContent = 'Belum ada uraian';
          if(picOrganikInput) picOrganikInput.value = '';
          if(picOrganikSearchInput) picOrganikSearchInput.value = '';
          if(positionOrganikSearchInput) positionOrganikSearchInput.value = '';
          if(positionOrganikInput) positionOrganikInput.value = '';
          // Project
          if(kodeProjectSelect) kodeProjectSelect.value = '';
          if(namaProjectInput) namaProjectInput.value = '';
          if(positionSearchInput) positionSearchInput.value = '';
          if(positionInput) positionInput.value = '';
          if(uraianStatusProject) uraianStatusProject.textContent = 'Belum ada uraian';
          if(picProjectInput) picProjectInput.value = '';
          if(picProjectSearchInput) picProjectSearchInput.value = '';
      }

      function renderTabs(count) {
          dataTabsContainer.innerHTML = '';
          if (count <= 1) { dataTabsContainer.style.display = 'none'; return; }
          dataTabsContainer.style.display = 'flex';
          for (let i = 1; i <= count; i++) {
              const btn = document.createElement('button');
              btn.type = 'button';
              const activeClass = (i === activeDataIndex) ? 'u-btn--brand' : 'u-btn--soft';
              btn.className = `u-btn u-btn--sm ${activeClass}`;
              btn.textContent = `Data ${i}`;
              btn.dataset.idx = i;
              btn.addEventListener('click', () => {
                  saveCurrentTabData();
                  const prevBtn = dataTabsContainer.querySelector(`button[data-idx="${activeDataIndex}"]`);
                  if(prevBtn) { prevBtn.classList.remove('u-btn--brand'); prevBtn.classList.add('u-btn--soft'); }
                  btn.classList.remove('u-btn--soft'); btn.classList.add('u-btn--brand');
                  activeDataIndex = i; loadTabData(i);
              });
              dataTabsContainer.appendChild(btn);
          }
      }

      if(headcountInput) {
          headcountInput.addEventListener('input', function(e) {
              let val = parseInt(e.target.value);
              if (isNaN(val) || val < 1) val = 1;
              saveCurrentTabData();
              totalDataCount = val;
              if (activeDataIndex > totalDataCount) activeDataIndex = 1;
              renderTabs(totalDataCount);
              if (totalDataCount === 1) { if (activeDataIndex !== 1) { activeDataIndex = 1; loadTabData(1); } }
          });
      }

      if (contractTypeSelect) {
        contractTypeSelect.addEventListener('change', function() {
          const val = this.value;
          multiDataStore = {}; 
          resetDynamicInputs();
          activeDataIndex = 1;
          renderTabs(totalDataCount); 
          if (val === 'Organik') { setBudgetLock(true, 'RKAP'); }
          else if (val === 'Project Based') { setBudgetLock(true, 'RAB Proyek'); }
          else { setBudgetLock(false, ''); }
          updateVisibility();
        });
      }

      submitBtn.addEventListener('click', function(e) {
          saveCurrentTabData();
          const payload = [];
          for (let i = 1; i <= totalDataCount; i++) {
              let d = multiDataStore[i] || {};
              d.type = getActiveContractType(); 
              if(!d.title) d.title = titleInput.value; 
              if(!d.target_start_date) d.target_start_date = targetStartInput.value;
              payload.push(d);
          }
          detailsJsonInput.value = JSON.stringify(payload);
      });

      document.addEventListener('click', function(e) {
         // Close Modal Uraian
         if (e.target.closest('#uraianModal [data-modal-close]')) {
             const m = document.getElementById('uraianModal');
             m.hidden = true; 
             // Jangan remove modal-open jika modal utama masih ada
         }
         // Close Modal Utama
         else if (e.target.closest('#createApprovalModal [data-modal-close]') || e.target.closest('#detailApprovalModal [data-modal-close]')) {
             const m = e.target.closest('.u-modal');
             if(m) { m.hidden = true; document.body.classList.remove('modal-open'); }
         }

         const btnCreate = e.target.closest('[data-modal-open="createApprovalModal"]');
         if(btnCreate) {
             const m = document.getElementById('createApprovalModal');
             const deleteForm = document.getElementById('deleteDraftForm');
             if(m) { 
                 m.hidden = false; document.body.classList.add('modal-open'); 
                 const mode = btnCreate.getAttribute('data-mode');
                 multiDataStore = {}; activeDataIndex = 1; totalDataCount = 1; renderTabs(1); resetDynamicInputs();

                 if(mode === 'create') {
                     form.reset(); 
                     setBudgetLock(false, ''); 
                     if(modalTitle) modalTitle.textContent = "Buat Izin Prinsip Baru";
                     if(modalSubtitle) modalSubtitle.textContent = "Ajukan permintaan rekrutmen atau perpanjangan kontrak";
                     form.action = form.getAttribute('data-default-action'); 
                     if(deleteForm) deleteForm.style.display = 'none';
                     let methodField = form.querySelector('input[name="_method"]');
                     if (methodField) methodField.remove();
                     [positionSearchInput, positionInput, positionOrganikSearchInput, positionOrganikInput, 
                      picProjectSearchInput, picProjectInput, picOrganikSearchInput, picOrganikInput].forEach(el => { if(el) el.value = ''; });
                     updateVisibility();
                 } else if (mode === 'edit') {
                     if(modalTitle) modalTitle.textContent = "Edit Izin Prinsip";
                     const updateUrl = btnCreate.getAttribute('data-update-url');
                     if(updateUrl) form.action = updateUrl;
                     const deleteUrl = btnCreate.getAttribute('data-delete-url');
                     if(deleteForm) { deleteForm.style.display = 'block'; deleteForm.action = deleteUrl || ''; }
                     
                     let methodField = form.querySelector('input[name="_method"]');
                     if (!methodField) { methodField = document.createElement('input'); methodField.type = 'hidden'; methodField.name = '_method'; methodField.value = 'PUT'; form.appendChild(methodField); }

                     // Load basic data
                     if(requestTypeSelect) requestTypeSelect.value = btnCreate.getAttribute('data-request-type');
                     if(titleInput) titleInput.value = btnCreate.getAttribute('data-title');
                     if(headcountInput) headcountInput.value = btnCreate.getAttribute('data-headcount');
                     if(targetStartInput) targetStartInput.value = btnCreate.getAttribute('data-target-start');
                     if(justifInput) justifInput.value = btnCreate.getAttribute('data-justification');

                     const contractType = btnCreate.getAttribute('data-employment-type');
                     const budgetType   = btnCreate.getAttribute('data-budget-source-type');
                     
                     if(contractTypeSelect) contractTypeSelect.value = contractType;
                     if (contractType === 'Organik') { setBudgetLock(true, 'RKAP'); }
                     else if (contractType === 'Project Based') { setBudgetLock(true, 'RAB Proyek'); }
                     else { setBudgetLock(false, ''); if(budgetSourceSelect) budgetSourceSelect.value = budgetType; }
                     updateVisibility();

                     const posName = btnCreate.getAttribute('data-position');
                     if (contractType === 'Organik') {
                         if(positionOrganikSearchInput) positionOrganikSearchInput.value = posName;
                         if(positionOrganikInput) positionOrganikInput.value = posName; 
                     } else {
                         if(positionSearchInput) positionSearchInput.value = posName;
                         if(positionInput) positionInput.value = posName;
                     }

                     const metaJsonStr = btnCreate.getAttribute('data-meta-json');
                     if (metaJsonStr) {
                         try {
                             const detailsArray = JSON.parse(metaJsonStr);
                             if (Array.isArray(detailsArray) && detailsArray.length > 0) {
                                 totalDataCount = detailsArray.length;
                                 headcountInput.value = totalDataCount;
                                 renderTabs(totalDataCount);
                                 detailsArray.forEach((detail, idx) => { multiDataStore[idx + 1] = detail; });
                                 activeDataIndex = 1;
                                 loadTabData(1);
                             }
                         } catch (e) { console.warn('Failed to parse meta-json:', e); }
                     }
                 }
             }
         }
         // ... Detail Modal Logic ...
         const btnDetail = e.target.closest('.js-open-detail');
         if(btnDetail && detailModal) {
             const safeTxt = (attr) => btnDetail.getAttribute(attr) || '-';
             const metaJsonStr = btnDetail.getAttribute('data-meta-json');
             let detailsArray = [];
             try { detailsArray = JSON.parse(metaJsonStr); } catch(e){}
             if(!Array.isArray(detailsArray) || detailsArray.length===0) detailsArray = [{}];

             const setTxt = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '-'; };
             setTxt('view-ticket', safeTxt('data-ticket-number'));
             setTxt('view-status', safeTxt('data-status'));
             setTxt('view-unit', safeTxt('data-unit'));
             setTxt('view-request-type', safeTxt('data-request-type'));
             setTxt('view-justification', safeTxt('data-justification'));
             setTxt('view-budget-source', safeTxt('data-budget-source'));
             
             const tabsContainer = document.getElementById('detailTabsContainer');
             tabsContainer.innerHTML = '';
             
             // Get approval URLs dan status
             const canApprove = btnDetail.getAttribute('data-can-approve') === 'true';
             const approveUrl = btnDetail.getAttribute('data-approve-url');
             const rejectUrl = btnDetail.getAttribute('data-reject-url');
             const recruitmentId = btnDetail.getAttribute('data-id');
             
             // Setup approve/reject forms
             const approveForm = detailModal.querySelector('.detail-approve-form');
             const rejectForm = detailModal.querySelector('.detail-reject-form');
             const actionButtons = detailModal.querySelector('.action-buttons');
             
             if(canApprove && approveForm && rejectForm) {
                 approveForm.style.display = 'block';
                 rejectForm.style.display = 'block';
                 if(approveForm.querySelector('button')) approveForm.querySelector('button').disabled = false;
                 if(rejectForm.querySelector('button')) rejectForm.querySelector('button').disabled = false;
                 if(approveUrl) approveForm.action = approveUrl;
                 if(rejectUrl) rejectForm.action = rejectUrl;
             } else {
                 if(approveForm) approveForm.style.display = 'none';
                 if(rejectForm) rejectForm.style.display = 'none';
             }
             
             const renderContent = (index) => {
                 const data = detailsArray[index];
                 const globalTitle = safeTxt('data-title');
                 setTxt('view-title', data.title || globalTitle);
                 setTxt('view-position', data.position_text || safeTxt('data-position'));
                 setTxt('view-headcount', '1 Orang');
                 setTxt('view-target', data.target_start_date || safeTxt('data-target-start'));
                 setTxt('view-employment', data.type || safeTxt('data-employment-type'));
                 setTxt('view-pic', data.pic_text || '-');

                 const btnPdf = document.getElementById('btn-view-pdf-detail');
                 const statusUraian = document.getElementById('view-uraian-status');
                 if(data && data.uraian_data) {
                     statusUraian.textContent = 'Tersedia';
                     btnPdf.style.display = 'inline-flex';
                     btnPdf.onclick = function() { submitPdfForm(JSON.stringify(data.uraian_data)); };
                 } else {
                     statusUraian.textContent = 'Tidak ada uraian';
                     btnPdf.style.display = 'none';
                 }
             };
             
             detailsArray.forEach((item, i) => {
                  const btnTab = document.createElement('button');
                  btnTab.type = 'button';
                  
                  // Logika style: Jika index 0 (pertama), pakai style brand (biru), sisanya ghost
                  const initialStyle = (i === 0) ? 'u-btn--brand u-text-white' : 'u-btn--ghost';
                  btnTab.className = `u-btn u-btn--sm ${initialStyle} u-hover-lift`;
                  
                  btnTab.textContent = `Data ${i + 1}`;
                  btnTab.style.borderRadius = '20px'; // Opsional: agar lebih bulat

                  btnTab.addEventListener('click', (evt) => {
                      evt.preventDefault();
                      
                      // Reset semua tombol tab menjadi style ghost (polos)
                      Array.from(tabsContainer.children).forEach(c => {
                          c.classList.remove('u-btn--brand', 'u-text-white');
                          c.classList.add('u-btn--ghost');
                      });
                      
                      // Set tombol yang diklik menjadi style brand (biru)
                      btnTab.classList.remove('u-btn--ghost');
                      btnTab.classList.add('u-btn--brand', 'u-text-white');
                      
                      renderContent(i);
                  });
                  tabsContainer.appendChild(btnTab);
             });
             
             // Load konten pertama
             renderContent(0);
             
             detailModal.hidden = false; 
             document.body.classList.add('modal-open');
         }
      });

      form.addEventListener('click', function(e) {
        if (e.target.closest('.js-open-uraian-project')) {
            const currentData = multiDataStore[activeDataIndex] || {};
            openUraianForm(currentData, 'project');
        }
        if (e.target.closest('.js-open-uraian')) {
            const selectedRow = form.querySelector('.js-rkap-select.selected');
            if (!selectedRow) { alert('Pilih Job Function di tabel RKAP terlebih dahulu.'); return; }
            const currentData = multiDataStore[activeDataIndex] || {};
            openUraianForm(currentData, 'organik');
        }
      });
      
      // SEARCHABLES & VISIBILITY
      function toggleRkapSelect(sel, forceSelect = false) { 
        const tr = sel.closest('tr'); const job = tr.dataset.jobName;
        form.querySelectorAll('.js-rkap-select.selected').forEach(el => { if (el !== sel) { el.classList.remove('selected', 'u-success'); el.classList.add('u-btn--outline'); el.innerHTML = '+'; } });
        const isSelected = sel.classList.contains('selected') && !forceSelect;
        if (isSelected) {
            sel.classList.remove('selected', 'u-success'); sel.classList.add('u-btn--outline'); sel.innerHTML = '+';
            rkapSelectedInfo.style.display = 'none';
            if(multiDataStore[activeDataIndex]) { multiDataStore[activeDataIndex].rkap_job = null; }
        } else {
            sel.classList.add('selected', 'u-success'); sel.classList.remove('u-btn--outline'); sel.innerHTML = '<i class="fas fa-check"></i>';
            rkapSelectedInfo.style.display = 'block';
            if(rkapSelectedName) rkapSelectedName.textContent = job;
            const stored = multiDataStore[activeDataIndex];
            const statusText = (stored && stored.uraian_status === 'Final') ? 'Tersimpan (Final)' : (stored && stored.uraian_status === 'Draft' ? 'Tersimpan (Draft)' : 'Belum ada uraian');
            if(uraianStatus) uraianStatus.textContent = statusText;
        }
      }
      form.addEventListener('click', function(e) { const btn = e.target.closest('.js-rkap-select'); if (btn) { e.preventDefault(); toggleRkapSelect(btn); } });
      
      function setupSearchableDropdown(searchInput, hiddenInput, resultsContainer, dataArray, allowNew = false) { 
          if (!searchInput || !resultsContainer) return;
          const renderOptions = (filterText = '') => {
              resultsContainer.innerHTML = ''; const lowerFilter = filterText.toLowerCase(); const filtered = dataArray.filter(item => item.name.toLowerCase().includes(lowerFilter));
              if (filtered.length > 0) {
                  filtered.forEach(item => {
                      const div = document.createElement('div'); div.className = 'u-p-sm'; div.style.cursor = 'pointer'; div.style.borderBottom = '1px solid #f0f0f0'; div.textContent = item.name;
                      div.addEventListener('click', () => { searchInput.value = item.name; if(hiddenInput) hiddenInput.value = item.id; resultsContainer.style.display = 'none'; });
                      resultsContainer.appendChild(div);
                  });
              } else { if (!allowNew) { const noRes = document.createElement('div'); noRes.className = 'u-p-sm u-text-muted'; noRes.textContent = 'Tidak ditemukan'; resultsContainer.appendChild(noRes); } }
              if (allowNew && filterText.trim() !== '') {
                  const addNewDiv = document.createElement('div'); addNewDiv.className = 'u-p-sm u-text-brand'; addNewDiv.style.cursor = 'pointer'; addNewDiv.innerHTML = `Gunakan: "${filterText}"`;
                  addNewDiv.addEventListener('click', () => { searchInput.value = filterText; if(hiddenInput) hiddenInput.value = filterText; resultsContainer.style.display = 'none'; });
                  resultsContainer.appendChild(addNewDiv);
              }
          };
          searchInput.addEventListener('focus', () => { renderOptions(searchInput.value); resultsContainer.style.display = 'block'; });
          searchInput.addEventListener('input', (e) => { renderOptions(e.target.value); resultsContainer.style.display = 'block'; });
          document.addEventListener('click', (e) => { if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) resultsContainer.style.display = 'none'; });
      }
      setupSearchableDropdown(positionSearchInput, positionInput, positionSearchResults, positionsData, true);
      setupSearchableDropdown(positionOrganikSearchInput, positionOrganikInput, positionOrganikSearchResults, positionsData, true);
      setupSearchableDropdown(picProjectSearchInput, picProjectInput, picProjectResults, picData, false);
      setupSearchableDropdown(picOrganikSearchInput, picOrganikInput, picOrganikResults, picData, false);
      
      const updateVisibility = () => { 
        const type = contractTypeSelect ? contractTypeSelect.value : ''; 
        const budget = budgetSourceSelect ? budgetSourceSelect.value : '';
        
        const isOrganik = (type === 'Organik') && (budget === 'RKAP'); 
        const isProject = (type === 'Project Based'); 
        
        if (rkapSection) rkapSection.style.display = isOrganik ? 'block' : 'none';
        if (!isOrganik && rkapSelectedInfo) rkapSelectedInfo.style.display = 'none';
        if (projectSection) projectSection.style.display = isProject ? 'block' : 'none';

        // LOGIC PENENTUAN FIELD "POSITION" YANG DIKIRIM KE SERVER
        if (type === 'Organik') {
            // Jika Organik, field 'position' diambil dari input Organik
            if(positionInput) positionInput.removeAttribute('name'); // Matikan input project
            if(positionOrganikInput) positionOrganikInput.setAttribute('name', 'position'); // Aktifkan input organik
            
            // Pastikan nilai positionOrganikInput terisi dari selection RKAP jika ada
            const selectedRow = form.querySelector('.js-rkap-select.selected');
            if(selectedRow && positionOrganikInput) {
                positionOrganikInput.value = selectedRow.closest('tr').dataset.jobName;
            }
            
        } else if (type === 'Project Based') {
            // Jika Project, field 'position' diambil dari input Project
            if(positionOrganikInput) positionOrganikInput.removeAttribute('name'); // Matikan input organik
            if(positionInput) positionInput.setAttribute('name', 'position'); // Aktifkan input project
        } else {
            // Jika belum pilih, matikan keduanya
            if(positionInput) positionInput.removeAttribute('name');
            if(positionOrganikInput) positionOrganikInput.removeAttribute('name');
        }
      };
      const setBudgetLock = (lock, value) => { 
        if (!budgetSourceSelect) return;
        if (lock) { budgetSourceSelect.value = value; budgetSourceSelect.setAttribute('disabled', 'disabled'); let hidden = document.getElementById('budgetSourceHidden'); if (!hidden) { hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.id = 'budgetSourceHidden'; hidden.name = 'budget_source_type'; form.appendChild(hidden); } hidden.value = value; } 
        else { budgetSourceSelect.removeAttribute('disabled'); budgetSourceSelect.value = ''; const hidden = document.getElementById('budgetSourceHidden'); if (hidden) hidden.remove(); }
      };
      if (budgetSourceSelect) budgetSourceSelect.addEventListener('change', updateVisibility);
      if (kodeProjectSelect && namaProjectInput) { kodeProjectSelect.addEventListener('change', function() { const selectedOption = this.options[this.selectedIndex]; const nama = selectedOption.getAttribute('data-nama'); namaProjectInput.value = nama ? nama : ''; }); }
      
      updateVisibility(); 
    }, 
    initDT() { /* ... */ },
    bindExternalSearch() { /* ... */ }
  };
  page.init();
});
</script>
@endsection