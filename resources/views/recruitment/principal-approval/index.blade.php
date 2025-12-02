@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Gate;
  use Illuminate\Support\Facades\Schema;

  /** @var \App\Models\User|null $me */
  $me     = auth()->user();
  $meUnit = $me ? $me->unit_id : null;

  // --- 1. SETUP DATA UTAMA ---
  $canSeeAll      = isset($canSeeAll)      ? $canSeeAll      : false;
  $selectedUnitId = isset($selectedUnitId) ? $selectedUnitId : null;
  $units          = isset($units)          ? $units          : collect();

  if ($units->isEmpty()) {
      $units = $canSeeAll
          ? DB::table('units')->select('id','name')->orderBy('name')->get()
          : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
  }

  $unitMap = $units->pluck('name','id');

  $dhcUnitId = DB::table('units')
      ->where(function($q){
          $q->where('code','DHC')
            ->orWhere('name','Divisi Human Capital')
            ->orWhere('name','like','Divisi Human Capital%');
      })->value('id');

  $canCreate = Gate::check('recruitment.create')
                || Gate::check('recruitment.update')
                || ($me && $me->hasRole('SDM Unit'))
                || ($me && $me->hasRole('Superadmin'));


  // --- 2. SETUP DATA DUMMY / LOOKUP ---
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
      $rawPics = DB::table('employees')
          ->join('persons', 'employees.person_id', '=', 'persons.id')
          ->select('employees.id', 'employees.employee_id', 'persons.full_name')
          ->where('employees.unit_id', $selectedUnitId ?? $meUnit)
          ->orderBy('persons.full_name')
          ->get();
  } catch (\Exception $e) {
      $rawPics = collect();
  }

  $picListFormatted = $rawPics->map(function($p) {
      $empId = $p->employee_id ?? '-';
      $name  = $p->full_name ?? '-';
      return ['id' => $p->id, 'name' => $empId . ' - ' . $name];
  })->values();
@endphp

<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>
    <form method="get" class="u-flex u-gap-sm u-items-center">
      @if($canSeeAll)
        <label class="u-text-sm u-font-medium">Unit</label>
        <select name="unit_id" class="u-input" onchange="this.form.submit()">
          <option value="">All units</option>
          @foreach($units as $u)
            <option value="{{ $u->id }}" @selected((string)$u->id === (string)($selectedUnitId ?? ''))>
              {{ $u->name }}
            </option>
          @endforeach
        </select>
      @else
        @php $unitName = ($units[0]->name ?? 'Unit Saya'); @endphp
        <span class="u-badge u-badge--glass">Scoped to: {{ $unitName }}</span>
      @endif
    </form>

    @if($canCreate)
    <button class="u-btn u-btn--brand u-hover-lift"
            data-modal-open="createApprovalModal"
            data-mode="create">
      <i class="fas fa-plus u-mr-xs"></i> Buat Permintaan
    </button>
    @endif
  </div>

  {{-- Notifications --}}
  @if(session('ok')) @push('swal') <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script> @endpush @endif
  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm"><i class="u-error-icon fas fa-exclamation-circle"></i><span class="u-font-semibold">Periksa kembali data berikut:</span></div>
      <ul class="u-list">@foreach($errors->all() as $e)<li class="u-item">{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="dt-wrapper">
    <div class="u-flex u-items-center u-justify-between u-mb-sm">
      <div class="u-font-semibold">Daftar Izin Prinsip</div>
      <span class="u-badge u-badge--glass">{{ $canSeeAll && !$selectedUnitId ? 'All units' : 'Unit ID: '.($selectedUnitId ?? $meUnit) }}</span>
    </div>
    <div class="u-scroll-x">
      <table id="ip-table" class="u-table" data-dt>
        <thead>
          <tr>
            <th>No Ticket</th>
            <th>Judul</th>
            <th>Unit</th>
            <th>Jenis Permintaan</th>
            <th>Posisi</th>
            <th>HC</th>
            <th>Jenis Kontrak</th>
            <th>Progress</th>
            <th class="cell-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($list as $r)
          @php
            // --- 1. LOGIC PHP ORIGINAL (Agar kolom Aksi & Anggaran bekerja normal) ---
            $meUnit = auth()->user()->unit_id; // Sesuaikan jika user logic berbeda
            $sameUnit = $meUnit && (string)$meUnit === (string)$r->unit_id;
            
            // Logic Stage Approval
            $stageIndex = null;
            if ($r->relationLoaded('approvals')) {
              foreach ($r->approvals as $i => $ap) {
                if (($ap->status ?? 'pending') === 'pending') { $stageIndex = $i; break; }
              }
            }
            
            // Logic Roles (Sesuaikan dengan auth setup Anda)
            $me = auth()->user();
            $meRoles = [ 
                'Superadmin' => $me && $me->hasRole('Superadmin'), 
                'Kepala Unit' => $me && $me->hasRole('Kepala Unit'), 
                'DHC' => $me && $me->hasRole('DHC'), 
                'Dir SDM' => $me && $me->hasRole('Dir SDM') 
            ];

            $status = $r->status ?? 'draft';
            $employmentType  = $r->employment_type ?? $r->contract_type ?? null;
            $targetStart     = $r->target_start_date ?? $r->start_date ?? null;
            $budgetSource    = $r->budget_source_type ?? $r->budget_source ?? null;
            $requestType     = $r->request_type ?? $r->type ?? 'Rekrutmen';
            $budgetRef       = $r->budget_ref ?? $r->rkap_ref ?? $r->rab_ref ?? $r->budget_reference ?? '';
            $justif          = $r->justification ?? $r->reason ?? $r->notes ?? $r->note ?? $r->description ?? '';
            $unitNameRow     = $r->unit_id ? ($unitMap[$r->unit_id] ?? ('Unit #'.$r->unit_id)) : '-';
            
            // Logic Progress Text
            $totalStages = 3; $progressStep = null;
            if ($status === 'draft') { $progressText = 'Draft di SDM Unit'; $progressStep = 0; }
            elseif ($status === 'rejected') { $progressText = 'Ditolak'; }
            elseif ($status === 'approved') { $progressText = 'Selesai (Approved Dir SDM)'; $progressStep = $totalStages; }
            elseif ($stageIndex === 0) { $progressText = 'Menunggu Kepala Unit'; $progressStep = 1; }
            elseif ($stageIndex === 1) { $progressText = 'Menunggu DHC'; $progressStep = 2; }
            elseif ($stageIndex === 2) { $progressText = 'Menunggu Dir SDM'; $progressStep = 3; }
            else { $progressText = 'In Review'; }

            $canStage = false;

            // --- 2. LOGIC MULTI DATA (Untuk Judul & Posisi) ---
            $recruitmentDetails = collect($r->meta['recruitment_details'] ?? []);
            $detailCount = $recruitmentDetails->count();
            $hasMultiData = $detailCount > 1;
            
            $posObj = $positions->firstWhere('id', $r->position);
            $positionDisplay = $posObj ? $posObj->name : $r->position;
          @endphp
          
          <tr class="recruitment-main-row u-align-top" data-recruitment-id="{{ $r->id }}">
            
            {{-- 1. No Ticket --}}
            <td>
              @if(!empty($r->ticket_number)) <span class="u-badge u-badge--primary u-text-2xs" title="Nomor Ticket">{{ $r->ticket_number }}</span>
              @else <span class="u-text-2xs u-text-muted">-</span> @endif
            </td>

            {{-- 2. JUDUL (MODIFIED: In-Cell Looping) --}}
            <td style="min-width: 200px;">
                @if($hasMultiData)
                    <div class="u-flex u-flex-col u-gap-xs">
                        @foreach($recruitmentDetails as $detail)
                        <div style="border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px; margin-bottom: 4px;">
                            <span class="u-font-medium">{{ $detail['title'] ?? $r->title }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <span class="u-font-medium">{{ $r->title }}</span>
                    <div class="u-text-2xs u-muted">Dibuat {{ optional($r->created_at)->format('d M Y') ?? '-' }}</div>
                @endif
            </td>

            {{-- 3. Unit --}}
            <td>{{ $unitNameRow }}</td>

            {{-- 4. Jenis Permintaan --}}
            <td><span class="u-badge u-badge--glass u-text-2xs">@if($requestType === 'Perpanjang Kontrak') Perpanjang Kontrak @elseif($requestType === 'Rekrutmen') Rekrutmen @else {{ $requestType }} @endif</span></td>

            {{-- 5. POSISI (MODIFIED: In-Cell Looping) --}}
            <td>
              @if($hasMultiData)
                <div class="u-flex u-flex-col u-gap-xs">
                    @foreach($recruitmentDetails as $detail)
                    <div class="u-text-sm" style="border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px; margin-bottom: 4px;">
                        {{ $detail['position_text'] ?? $detail['position'] ?? '-' }}
                    </div>
                    @endforeach
                </div>
              @else
                {{ $positionDisplay }}
              @endif
            </td>

            {{-- 6. HC --}}
            <td>
                <span class="u-badge u-badge--glass">{{ $r->headcount }} Orang</span>
            </td>

            {{-- 7. Jenis Kontrak --}}
            <td>@if($employmentType) <span class="u-badge u-badge--glass">{{ $employmentType }}</span> @else <span class="u-text-2xs u-muted">-</span> @endif</td>

            {{-- 9. Progress --}}
            <td><div class="u-text-2xs"><span class="u-badge u-badge--glass">{{ $progressText }}</span>@if($progressStep !== null) <div class="u-muted u-mt-xxs">Stage {{ $progressStep }} / {{ $totalStages }}</div> @endif</div></td>
            
            {{-- 10. Sumber Anggaran --}}
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
                    <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}" class="u-inline js-confirm" data-confirm-title="Submit permintaan?" data-confirm-text="Permintaan akan dikirim." data-confirm-icon="question">@csrf<button class="u-btn u-btn--outline u-btn--sm u-hover-lift"><i class="fas fa-paper-plane u-mr-xs"></i> Submit</button></form>
                  @endif
                @endif
                <button type="button" class="u-btn u-btn--outline u-btn--sm u-hover-lift js-open-detail" data-modal-open="detailApprovalModal" data-id="{{ $r->id }}" data-ticket-number="{{ $r->ticket_number ?? '-' }}" data-title="{{ e($r->title) }}" data-unit="{{ e($unitNameRow) }}" data-request-type="{{ e($requestType) }}" data-position="{{ e($positionDisplay) }}" data-headcount="{{ (int) $r->headcount }}" data-employment-type="{{ e($employmentType ?? '') }}" data-target-start="{{ $targetStart ? \Illuminate\Support\Carbon::parse($targetStart)->format('d M Y') : '-' }}" data-budget-source="{{ e($budgetSource ?? '') }}" data-budget-ref="{{ e($budgetRef) }}" data-justification="{{ e($justif) }}" data-status="{{ e(ucfirst($status)) }}" data-can-approve="{{ $canStage ? 'true' : 'false' }}" data-approve-url="{{ route('recruitment.principal-approval.approve',$r) }}" data-reject-url="{{ route('recruitment.principal-approval.reject',$r) }}" data-meta-json='{{ json_encode($r->meta['recruitment_details'] ?? []) }}'><i class="fas fa-info-circle u-mr-xs"></i> Detail</button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- MODAL 1: URAIAN JABATAN --}}
<div id="uraianModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-alt"></i></div>
        <div><div class="u-title">Editor Uraian Jabatan</div><div class="u-muted u-text-sm">Template formulir uraian</div></div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="u-modal__body">
      <div class="u-space-y-md">
        <div><label class="u-block u-text-sm u-font-medium u-mb-sm">Job Function / Jabatan</label><div id="uraianModalJob" class="u-font-medium">-</div></div>
        <div><label class="u-block u-text-sm u-font-medium u-mb-sm">Isi Uraian</label><textarea id="uraianEditor" class="u-input" rows="10" placeholder="Ketik uraian jabatan disini..."></textarea></div>
      </div>
    </div>
    <div class="u-modal__foot">
      <div class="u-flex u-justify-between u-items-center u-gap-sm">
        <div class="u-muted u-text-sm">Simpan draft untuk melanjutkan editing.</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
          <button type="button" class="u-btn u-btn--outline js-save-uraian-draft">Simpan Draf</button>
          <button type="button" class="u-btn u-btn--brand js-save-uraian-final">Simpan dan Finalisasi</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL 2: CREATE / EDIT APPROVAL --}}
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

        {{-- TAB NAVIGASI DATA --}}
        <div id="dataTabsContainer" class="u-flex u-gap-sm u-flex-wrap u-mb-sm" style="display:none;">
           {{-- Tombol Data 1, Data 2, dll di-generate via JS --}}
        </div>

        {{-- [SECTION DINAMIS] --}}
        <div id="dynamicContentWrapper" class="u-p-md u-border u-rounded u-bg-light">
            
            {{-- 4a. Judul Permintaan --}}
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
                <textarea name="uraian_jabatan_project" id="uraianJabatanProject" class="u-input" style="display:none"></textarea>
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
                  
                  {{-- Kolom Kiri: Uraian --}}
                  <div>
                    <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan</label>
                    <div class="u-flex u-items-center u-gap-sm">
                      <div id="uraianStatus" class="u-text-2xs u-muted">Belum ada uraian</div>
                      <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian">Isi Uraian</button>
                    </div>
                  </div>

                  {{-- Kolom Kanan: PIC & Posisi (Organik) --}}
                  <div>
                    {{-- Field PIC (Existing) --}}
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

            {{-- 4c. Tanggal Mulai Kerja --}}
            <div class="u-space-y-sm u-mt-md">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Mulai Kerja</label>
              <input class="u-input" type="date" id="targetStartInput" name="target_start_date">
            </div>
        </div>

        {{-- 5. Justifikasi (Footer) --}}
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
        <form method="POST" action="" id="deleteDraftForm" class="u-inline js-confirm" data-confirm-title="Hapus Draft?" data-confirm-text="Data draft akan dihapus permanen." data-confirm-icon="warning" style="display:none;">
            @csrf
            @method('DELETE')
            {{-- Style khusus warna merah --}}
            <button type="submit" class="u-btn u-btn--outline u-hover-lift" style="color:#ef4444; border-color:#ef4444;">
                <i class="fas fa-trash-alt u-mr-xs"></i> Hapus
            </button>
        </form>
        <button form="createApprovalForm" class="u-btn u-btn--brand u-hover-lift" id="submitApprovalBtn"><i class="fas fa-save u-mr-xs"></i> Simpan Draft</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL 3: DETAIL APPROVAL --}}
{{-- MODAL 3: DETAIL APPROVAL (REVISED FOR MULTI-DATA TABS) --}}
<div id="detailApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card" style="max-width: 900px;"> {{-- Sedikit diperlebar --}}
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
        {{-- AREA TABS NAVIGASI --}}
        <div id="detailTabsContainer" class="u-flex u-gap-sm u-flex-wrap u-mb-md u-border-b u-pb-sm">
            {{-- Tombol Tab (Data 1, Data 2, ...) akan di-generate via JS disini --}}
        </div>

        {{-- AREA KONTEN DATA --}}
        <div id="detailContentContainer" class="u-animate-fade-in">
            <div class="u-grid-2 u-stack-mobile u-gap-lg">
                {{-- Kolom Kiri --}}
                <div class="u-space-y-sm">
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">No Ticket</div>
                        <div class="u-font-medium" id="view-ticket">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Judul</div>
                        <div class="u-font-medium" id="view-title">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Jenis Permintaan</div>
                        <div id="view-request-type">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Headcount</div>
                        <div id="view-headcount">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Target Mulai</div>
                        <div id="view-target">-</div>
                    </div>
                     <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">PIC Request</div>
                        <div id="view-pic">-</div>
                    </div>
                    <div>
                         <div class="u-text-xs u-font-bold u-muted u-uppercase">Justifikasi</div>
                         <div id="view-justification" class="u-text-sm u-muted u-p-sm u-bg-light u-rounded u-mt-xs" style="white-space: pre-line;">-</div>
                    </div>
                </div>

                {{-- Kolom Kanan --}}
                <div class="u-space-y-sm">
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Status</div>
                        <div id="view-status">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Unit</div>
                        <div id="view-unit">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Posisi</div>
                        <div id="view-position" class="u-font-medium">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Jenis Kontrak</div>
                        <div id="view-employment">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Sumber Anggaran</div>
                        <div id="view-budget-source">-</div>
                    </div>
                    <div>
                        <div class="u-text-xs u-font-bold u-muted u-uppercase">Uraian Jabatan</div>
                        <div class="u-mt-xs">
                             {{-- Tombol trigger modal uraian (read only) --}}
                             <button type="button" id="btn-view-uraian" class="u-btn u-btn--xs u-btn--outline">
                                <i class="fas fa-file-alt u-mr-xs"></i> Lihat Uraian
                             </button>
                             <div id="view-uraian-status" class="u-text-2xs u-muted u-mt-xxs">-</div>
                             <input type="hidden" id="view-uraian-content">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-flex u-justify-between u-items-center u-gap-sm">
        <div class="u-muted u-text-sm">
             <span id="tab-indicator-text">Menampilkan Data 1</span>
        </div>
        <div class="u-flex u-gap-sm action-buttons">
          {{-- Action Buttons (Approve/Reject) --}}
          <form method="POST" action="" class="detail-approve-form u-inline js-confirm" style="display:none;">
              @csrf
              <button type="submit" class="u-btn u-btn--brand u-success detail-approve-btn" 
                      data-confirm-title="Setujui permintaan?" 
                      data-confirm-text="Data pada tab yang aktif akan disetujui." 
                      data-confirm-icon="success">
                  <i class="fas fa-check u-mr-xs"></i> Approve
              </button>
          </form>
          
          <form method="POST" action="" class="detail-reject-form u-inline js-confirm" style="display:none;">
              @csrf
              <button type="submit" class="u-btn u-btn--outline u-danger detail-reject-btn" 
                      data-confirm-title="Tolak permintaan?" 
                      data-confirm-text="Permintaan ini akan ditolak." 
                      data-confirm-icon="error">
                  <i class="fas fa-times u-mr-xs"></i> Reject
              </button>
          </form>

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

  const page = {
    dt: null,
    init() { this.bindModal(); this.initDT(); this.bindExternalSearch(); },
    bindModal() {
      const modalMain     = document.getElementById('createApprovalModal');
      const form          = document.getElementById('createApprovalForm');
      const submitBtn     = document.getElementById('submitApprovalBtn');
      const modalTitle    = document.getElementById('ip-modal-title'); // Judul Modal
      const modalSubtitle = document.getElementById('ip-modal-subtitle');
      
      const contractTypeSelect = form.querySelector('#contractTypeSelect');
      const budgetSourceSelect = form.querySelector('#budgetSourceSelect');
      const headcountInput     = form.querySelector('#headcountInput');
      const dataTabsContainer  = document.getElementById('dataTabsContainer');
      const detailsJsonInput   = document.getElementById('detailsJson');
      
      // Dynamic fields
      const requestTypeSelect  = form.querySelector('[name="request_type"]'); // Tambahan selector
      const titleInput         = form.querySelector('#titleInput');
      const targetStartInput   = form.querySelector('#targetStartInput');
      const justifInput        = form.querySelector('[name="justification"]'); // Tambahan selector

      // Organik Elements
      const rkapSection      = form.querySelector('#rkapSection');
      const rkapSelectedInfo = form.querySelector('#rkapSelectedInfo');
      const rkapSelectedName = form.querySelector('#rkapSelectedName');
      const uraianStatus     = form.querySelector('#uraianStatus');
      const picOrganikSearchInput = form.querySelector('#picOrganikSearchInput');
      const picOrganikInput       = form.querySelector('#picOrganikInput');
      const picOrganikResults     = form.querySelector('#picOrganikSearchResults');
      const positionOrganikSearchInput = form.querySelector('#positionOrganikSearchInput');
      const positionOrganikInput       = form.querySelector('#positionOrganikInput');
      const positionOrganikSearchResults = form.querySelector('#positionOrganikSearchResults');
      
      // Project Elements
      const projectSection       = form.querySelector('#projectSection');
      const kodeProjectSelect    = form.querySelector('#kodeProjectSelect');
      const namaProjectInput     = form.querySelector('#namaProjectInput');
      const uraianStatusProject  = form.querySelector('#uraianStatusProject');
      const uraianJabatanProject = form.querySelector('#uraianJabatanProject');
      const positionSearchInput  = form.querySelector('#positionSearchInput');
      const positionInput        = form.querySelector('#positionInput');
      const positionSearchResults = form.querySelector('#positionSearchResults');
      const picProjectSearchInput = form.querySelector('#picProjectSearchInput');
      const picProjectInput       = form.querySelector('#picProjectInput');
      const picProjectResults     = form.querySelector('#picProjectSearchResults');

      // Modal Uraian
      const uraianModal    = document.getElementById('uraianModal');
      const uraianEditor   = document.getElementById('uraianEditor');
      const uraianModalJob = document.getElementById('uraianModalJob');

      const defaultTemplate = "Nama Jabatan:\nUnit Kerja:\nPemangku:\nMelapor pada:\nTanggal\nLokasi\n...";
      
      // --- MULTI DATA STATE ---
      let activeDataIndex = 1;
      let totalDataCount  = 1;
      let multiDataStore  = {}; 

      function getActiveContractType() { return contractTypeSelect ? contractTypeSelect.value : ''; }

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
              multiDataStore[idx].uraian_content = uraianEditor.dataset.savedContent || '';
              multiDataStore[idx].uraian_status = uraianStatus.textContent;
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
              multiDataStore[idx].uraian_content = uraianJabatanProject.value;
              multiDataStore[idx].uraian_status = uraianStatusProject.textContent;
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
              if(data.uraian_status) uraianStatus.textContent = data.uraian_status;
              if(data.pic_id) picOrganikInput.value = data.pic_id;
              if(data.pic_text) picOrganikSearchInput.value = data.pic_text;
              if(data.uraian_content) uraianEditor.dataset.savedContent = data.uraian_content;
              if(data.position) positionOrganikInput.value = data.position;
              if(data.position_text) positionOrganikSearchInput.value = data.position_text;

          } else if (type === 'Project Based' && data.type === 'Project Based') {
              if(data.project_code) {
                  kodeProjectSelect.value = data.project_code;
                  namaProjectInput.value = data.project_name || ''; 
              }
              if(data.position) positionInput.value = data.position;
              if(data.position_text) positionSearchInput.value = data.position_text;
              if(data.uraian_content) uraianJabatanProject.value = data.uraian_content;
              if(data.uraian_status) uraianStatusProject.textContent = data.uraian_status;
              if(data.pic_id) picProjectInput.value = data.pic_id;
              if(data.pic_text) picProjectSearchInput.value = data.pic_text;
          }
      }

      function resetDynamicInputs() {
          if(titleInput) titleInput.value = '';
          if(targetStartInput) targetStartInput.value = '';
          // Organik
          form.querySelectorAll('.js-rkap-select.selected').forEach(b => {
             b.classList.remove('selected', 'u-success');
             b.classList.add('u-btn--outline');
             b.innerHTML = '+';
          });
          if(rkapSelectedInfo) rkapSelectedInfo.style.display = 'none';
          if(rkapSelectedName) rkapSelectedName.textContent = '';
          if(uraianStatus) uraianStatus.textContent = 'Belum ada uraian';
          if(picOrganikInput) picOrganikInput.value = '';
          if(picOrganikSearchInput) picOrganikSearchInput.value = '';
          if(uraianEditor) uraianEditor.dataset.savedContent = '';
          if(positionOrganikSearchInput) positionOrganikSearchInput.value = '';
          if(positionOrganikInput) positionOrganikInput.value = '';
          // Project
          if(kodeProjectSelect) kodeProjectSelect.value = '';
          if(namaProjectInput) namaProjectInput.value = '';
          if(positionSearchInput) positionSearchInput.value = '';
          if(positionInput) positionInput.value = '';
          if(uraianStatusProject) uraianStatusProject.textContent = 'Belum ada uraian';
          if(uraianJabatanProject) uraianJabatanProject.value = '';
          if(picProjectInput) picProjectInput.value = '';
          if(picProjectSearchInput) picProjectSearchInput.value = '';
      }

      function renderTabs(count) {
          dataTabsContainer.innerHTML = '';
          if (count <= 1) {
              dataTabsContainer.style.display = 'none';
              return;
          }
          dataTabsContainer.style.display = 'flex';
          for (let i = 1; i <= count; i++) {
              const btn = document.createElement('button');
              btn.type = 'button';
              const activeClass = (i === activeDataIndex) ? 'u-btn--brand' : 'u-btn--soft'; // Fixed class
              btn.className = `u-btn u-btn--sm ${activeClass}`;
              btn.textContent = `Data ${i}`;
              btn.dataset.idx = i;
              btn.addEventListener('click', () => {
                  saveCurrentTabData();
                  const prevBtn = dataTabsContainer.querySelector(`button[data-idx="${activeDataIndex}"]`);
                  if(prevBtn) { prevBtn.classList.remove('u-btn--brand'); prevBtn.classList.add('u-btn--soft'); }
                  
                  btn.classList.remove('u-btn--soft');
                  btn.classList.add('u-btn--brand');
                  
                  activeDataIndex = i;
                  loadTabData(i);
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
              if (totalDataCount === 1) {
                  if (activeDataIndex !== 1) { activeDataIndex = 1; loadTabData(1); }
              }
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

      function openUraianModalSafe(title, content, mode) {
          uraianEditor.setAttribute('data-mode', mode);
          uraianModalJob.textContent = title;
          uraianEditor.value = content || '';
          
          if (mode === 'view') {
              uraianEditor.setAttribute('readonly', true);
          } else {
              uraianEditor.removeAttribute('readonly');
          }
          
          if (modalMain) modalMain.style.zIndex = '1050';
          if (detailModal) detailModal.style.zIndex = '1050';
          
          if (uraianModal) uraianModal.style.zIndex = '2000'; 
          
          uraianModal.hidden = false;
          document.body.classList.add('modal-open');
      }

      function closeUraianModalSafe() {
          uraianModal.hidden = true;
          uraianEditor.removeAttribute('readonly'); 
          
          if (modalMain) modalMain.style.zIndex = '';
          if (detailModal) detailModal.style.zIndex = ''; 
          if (uraianModal) uraianModal.style.zIndex = '';
          
          const isDetailOpen = detailModal && !detailModal.hidden;
          const isMainOpen = modalMain && !modalMain.hidden;

          if (isDetailOpen || isMainOpen) {
             document.body.classList.add('modal-open'); 
          } else {
             document.body.classList.remove('modal-open');
          }
      }

      // Dropdown Setup
      function setupSearchableDropdown(searchInput, hiddenInput, resultsContainer, dataArray, allowNew = false) {
          if (!searchInput || !resultsContainer) return;

          const renderOptions = (filterText = '') => {
              resultsContainer.innerHTML = '';
              const lowerFilter = filterText.toLowerCase();
              const filtered = dataArray.filter(item => item.name.toLowerCase().includes(lowerFilter));
              let hasExactMatch = false;

              // Render Matches
              if (filtered.length > 0) {
                  filtered.forEach(item => {
                      if (item.name.toLowerCase() === lowerFilter) hasExactMatch = true;
                      
                      const div = document.createElement('div');
                      div.className = 'u-p-sm'; div.style.cursor = 'pointer'; div.style.borderBottom = '1px solid #f0f0f0';
                      div.textContent = item.name;
                      div.addEventListener('mouseenter', () => { div.style.backgroundColor = '#f9fafb'; });
                      div.addEventListener('mouseleave', () => { div.style.backgroundColor = 'transparent'; });
                      div.addEventListener('click', () => {
                          searchInput.value = item.name;
                          if(hiddenInput) hiddenInput.value = item.id;
                          resultsContainer.style.display = 'none';
                      });
                      resultsContainer.appendChild(div);
                  });
              } else {
                  if (!allowNew) {
                      const noRes = document.createElement('div');
                      noRes.className = 'u-p-sm u-text-muted u-text-center'; noRes.textContent = 'Tidak ditemukan';
                      resultsContainer.appendChild(noRes);
                  }
              }

              //  opsi "Gunakan: ..."
              if (allowNew && filterText.trim() !== '' && !hasExactMatch) {
                  const addNewDiv = document.createElement('div');
                  addNewDiv.className = 'u-p-sm u-text-brand';
                  addNewDiv.style.cursor = 'pointer';
                  addNewDiv.style.borderTop = '2px solid #f0f0f0';
                  addNewDiv.style.fontWeight = '500';
                  addNewDiv.innerHTML = `<i class="fas fa-plus-circle u-mr-xs"></i> Gunakan: "${filterText}"`;
                  
                  addNewDiv.addEventListener('mouseenter', () => { addNewDiv.style.backgroundColor = '#eff6ff'; });
                  addNewDiv.addEventListener('mouseleave', () => { addNewDiv.style.backgroundColor = 'transparent'; });
                  
                  addNewDiv.addEventListener('click', () => {
                      searchInput.value = filterText;
                      if(hiddenInput) hiddenInput.value = filterText; 
                      resultsContainer.style.display = 'none';
                  });
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

        if (type === 'Organik') {
            if(positionInput) positionInput.removeAttribute('name');
            if(positionOrganikInput) positionOrganikInput.setAttribute('name', 'position');
        } else if (type === 'Project Based') {
            if(positionOrganikInput) positionOrganikInput.removeAttribute('name');
            if(positionInput) positionInput.setAttribute('name', 'position');
        } else {
            if(positionInput) positionInput.removeAttribute('name');
            if(positionOrganikInput) positionOrganikInput.removeAttribute('name');
        }
      };

      const setBudgetLock = (lock, value) => {
        if (!budgetSourceSelect) return;
        if (lock) {
          budgetSourceSelect.value = value; budgetSourceSelect.setAttribute('disabled', 'disabled'); 
          let hidden = document.getElementById('budgetSourceHidden');
          if (!hidden) { hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.id = 'budgetSourceHidden'; hidden.name = 'budget_source_type'; form.appendChild(hidden); }
          hidden.value = value;
        } else {
          budgetSourceSelect.removeAttribute('disabled'); budgetSourceSelect.value = ''; 
          const hidden = document.getElementById('budgetSourceHidden'); if (hidden) hidden.remove();
        }
      };
      if (budgetSourceSelect) budgetSourceSelect.addEventListener('change', updateVisibility);
      if (kodeProjectSelect && namaProjectInput) {
        kodeProjectSelect.addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          const nama = selectedOption.getAttribute('data-nama');
          namaProjectInput.value = nama ? nama : '';
        });
      }

      form.addEventListener('click', function(e) {
        if (e.target.closest('.js-open-uraian-project')) {
            const existing = multiDataStore[activeDataIndex] ? multiDataStore[activeDataIndex].uraian_content : uraianJabatanProject.value;
            openUraianModalSafe('Project Based Position', existing || defaultTemplate, 'project');
        }
        if (e.target.closest('.js-open-uraian')) {
            const selectedRow = form.querySelector('.js-rkap-select.selected');
            if (!selectedRow) { alert('Pilih Job Function di tabel RKAP terlebih dahulu.'); return; }
            const job = selectedRow.closest('tr').dataset.jobName;
            const existing = (multiDataStore[activeDataIndex] && multiDataStore[activeDataIndex].uraian_content) 
                             ? multiDataStore[activeDataIndex].uraian_content 
                             : uraianEditor.dataset.savedContent;
            openUraianModalSafe(job, existing || defaultTemplate, 'organik');
        }
      });
      
      function toggleRkapSelect(sel, forceSelect = false) {
        const tr = sel.closest('tr');
        const job = tr.dataset.jobName;
        form.querySelectorAll('.js-rkap-select.selected').forEach(el => {
            if (el !== sel) { el.classList.remove('selected', 'u-success'); el.classList.add('u-btn--outline'); el.innerHTML = '+'; }
        });
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
            if(uraianStatus) {
                uraianStatus.textContent = (stored && stored.uraian_content) ? 'Tersimpan (Draft)' : 'Belum ada uraian';
            }
        }
      }
      form.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-rkap-select');
        if (btn) { e.preventDefault(); toggleRkapSelect(btn); }
      });

      document.addEventListener('click', function(e) {
        const saveDraft = e.target.closest('.js-save-uraian-draft');
        const saveFinal = e.target.closest('.js-save-uraian-final');
        const closeModal = e.target.closest('[data-modal-close]') && e.target.closest('#uraianModal');
        if (closeModal) { closeUraianModalSafe(); return; }

        if (saveDraft || saveFinal) {
            const mode = uraianEditor.getAttribute('data-mode');
            const content = uraianEditor.value;
            const statusText = saveFinal ? 'Tersimpan (Final)' : 'Tersimpan (Draft)';

            if (mode === 'project') {
                if(uraianJabatanProject) uraianJabatanProject.value = content;
                if(uraianStatusProject) uraianStatusProject.textContent = statusText;
            } else if (mode === 'organik') {
                if(uraianStatus) uraianStatus.textContent = statusText;
                uraianEditor.dataset.savedContent = content;
            }
            if(!multiDataStore[activeDataIndex]) multiDataStore[activeDataIndex] = {};
            multiDataStore[activeDataIndex].uraian_content = content;
            multiDataStore[activeDataIndex].uraian_status = statusText;
            closeUraianModalSafe();
        }
      });

      const detailModal = document.getElementById('detailApprovalModal');
      document.addEventListener('click', function(e) {
         if (e.target.matches('[data-modal-close]')) {
             const m = e.target.closest('.u-modal');
             if(m) { m.hidden = true; document.body.classList.remove('modal-open'); }
         }

         // --- HANDLING CREATE / EDIT BUTTON ---
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
                     form.action = form.getAttribute('data-default-action'); // Reset Action URL

                     if(deleteForm) deleteForm.style.display = 'none';
                     let methodField = form.querySelector('input[name="_method"]');
                     if (methodField) {
                         methodField.remove();
                     }
                     
                     [positionSearchInput, positionInput, positionOrganikSearchInput, positionOrganikInput, 
                      picProjectSearchInput, picProjectInput, picOrganikSearchInput, picOrganikInput]
                      .forEach(el => { if(el) el.value = ''; });
                     updateVisibility();

                 } else if (mode === 'edit') {
                     if(modalTitle) modalTitle.textContent = "Edit Izin Prinsip";
                     if(modalSubtitle) modalSubtitle.textContent = "Ubah data permintaan";
                     
                     const updateUrl = btnCreate.getAttribute('data-update-url');
                     if(updateUrl) form.action = updateUrl;

                     const deleteUrl = btnCreate.getAttribute('data-delete-url');
                     if(deleteForm) {
                        deleteForm.style.display = 'block';
                        deleteForm.action = deleteUrl || '';
                     }

                     let methodField = form.querySelector('input[name="_method"]');
                     if (!methodField) {
                         methodField = document.createElement('input');
                         methodField.type = 'hidden';
                         methodField.name = '_method';
                         methodField.value = 'PUT';
                         form.appendChild(methodField);
                     }

                     if(requestTypeSelect) requestTypeSelect.value = btnCreate.getAttribute('data-request-type');
                     if(titleInput) titleInput.value = btnCreate.getAttribute('data-title');
                     if(headcountInput) headcountInput.value = btnCreate.getAttribute('data-headcount');
                     if(targetStartInput) targetStartInput.value = btnCreate.getAttribute('data-target-start');
                     if(justifInput) justifInput.value = btnCreate.getAttribute('data-justification');

                     // Jenis Kontrak & Sumber Anggaran
                     const contractType = btnCreate.getAttribute('data-employment-type');
                     const budgetType   = btnCreate.getAttribute('data-budget-source-type');
                     
                     if(contractTypeSelect) contractTypeSelect.value = contractType;

                     if (contractType === 'Organik') { setBudgetLock(true, 'RKAP'); }
                     else if (contractType === 'Project Based') { setBudgetLock(true, 'RAB Proyek'); }
                     else { 
                         setBudgetLock(false, ''); 
                         if(budgetSourceSelect) budgetSourceSelect.value = budgetType;
                     }
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
                                 
                                 detailsArray.forEach((detail, idx) => {
                                     multiDataStore[idx + 1] = detail;
                                 });
                                 
                                 activeDataIndex = 1;
                                 loadTabData(1);
                             }
                         } catch (e) {
                             console.warn('Failed to parse meta-json:', e);
                         }
                     }
                 }
             }
         }

         // --- HANDLING TOGGLE DETAIL ROWS ---
         const toggleBtn = e.target.closest('.toggle-details');
         if (toggleBtn) {
             e.preventDefault();
             const recruitmentId = toggleBtn.getAttribute('data-target-id');
             const detailRows = document.querySelectorAll(`tr.recruitment-detail-row[data-recruitment-id="${recruitmentId}"]`);
             const isHidden = detailRows[0]?.style.display === 'none';
             
             detailRows.forEach(row => {
                 row.style.display = isHidden ? 'table-row' : 'none';
             });
             
             const icon = toggleBtn.querySelector('i');
             if (icon) {
                 icon.classList.toggle('fa-chevron-down');
                 icon.classList.toggle('fa-chevron-up');
             }
         }

         // --- HANDLING DETAIL BUTTON ---
          const btnDetail = e.target.closest('.js-open-detail');
          if (btnDetail && detailModal) {
              e.preventDefault();

              const safeTxt = (attr) => btnDetail.getAttribute(attr) || '-';
              const globalData = {
                  ticket: safeTxt('data-ticket-number'),
                  unit: safeTxt('data-unit'),
                  status: safeTxt('data-status'),
                  requestType: safeTxt('data-request-type'),
                  budgetSource: safeTxt('data-budget-source'),
                  employment: safeTxt('data-employment-type'),
                  justification: safeTxt('data-justification'),
                  
                  title: safeTxt('data-title'),
                  position: safeTxt('data-position'),
                  headcount: safeTxt('data-headcount'),
                  target: safeTxt('data-target-start'),
              };

              let detailsArray = [];
              const metaJsonStr = btnDetail.getAttribute('data-meta-json');
              
              console.log('=== DETAIL BUTTON DEBUG ===');
              console.log('Raw data-meta-json:', metaJsonStr);
              
              try {
                  if(metaJsonStr && metaJsonStr.trim() !== '' && metaJsonStr !== '[]') {
                      detailsArray = JSON.parse(metaJsonStr);
                      console.log('Parsed detailsArray:', detailsArray);
                  } else {
                      console.log('Meta JSON is empty or invalid:', metaJsonStr);
                  }
              } catch(err) { 
                  console.error('Parse Error:', err, 'Raw string:', metaJsonStr);
                  detailsArray = [];
              }

              if (!Array.isArray(detailsArray) || detailsArray.length === 0) {
                  console.log('Using fallback single data');
                  detailsArray = [{
                      title: globalData.title,
                      position_text: globalData.position,
                      headcount: globalData.headcount,
                      target_start_date: globalData.target,
                      type: globalData.employment,
                      budget_source_type: globalData.budgetSource,
                      uraian_content: '',
                      pic_text: '-'
                  }];
              }

              console.log('Final detailsArray:', detailsArray);
              console.log('Array length:', detailsArray.length);
              console.log('========================');

              const tabsContainer = document.getElementById('detailTabsContainer');
              const indicatorText = document.getElementById('tab-indicator-text');
              
              if (!tabsContainer) {
                  console.error('tabsContainer not found!');
                  return;
              }
              
              tabsContainer.innerHTML = '';
              
              const renderContent = (index) => {
                  const data = detailsArray[index];
                  console.log(`Rendering content for index ${index}:`, data);
                  if(!data) {
                      console.warn(`No data at index ${index}`);
                      return;
                  }

                  const setTxt = (id, val) => { 
                      const el = document.getElementById(id); 
                      if(el) { 
                          el.textContent = val || '-'; 
                      } else {
                          console.warn(`Element ${id} not found`);
                      }
                  };
                  
                  setTxt('view-ticket', globalData.ticket);
                  setTxt('view-status', globalData.status);
                  setTxt('view-unit', globalData.unit);
                  setTxt('view-request-type', globalData.requestType);
                  setTxt('view-justification', globalData.justification);

                  setTxt('view-title', data.title || globalData.title);
                  setTxt('view-position', data.position_text || data.position || globalData.position);
                  setTxt('view-headcount', '1 Orang');
                  
                  let dateShow = data.target_start_date || globalData.target;
                  setTxt('view-target', dateShow); 

                  setTxt('view-employment', data.type || globalData.employment);
                  setTxt('view-budget-source', data.budget_source_type || globalData.budgetSource);
                  setTxt('view-pic', data.pic_text || '-');

                  const uraianContent = data.uraian_content || '';
                  const btnUraian = document.getElementById('btn-view-uraian');
                  const hiddenUraian = document.getElementById('view-uraian-content');
                  const statusUraian = document.getElementById('view-uraian-status');
                  
                  if(hiddenUraian) hiddenUraian.value = uraianContent;
                  if(statusUraian) statusUraian.textContent = uraianContent ? 'Tersedia' : 'Tidak ada uraian';
                  
                  if(btnUraian) {
                      btnUraian.onclick = function() {
                          openUraianModalSafe(data.position_text || 'Detail Jabatan', uraianContent, 'view');
                      }
                  }

                  if(indicatorText) indicatorText.textContent = `Menampilkan Data ${index + 1} dari ${detailsArray.length}`;
              };

              // Loop untuk membuat tombol Tab
              console.log('Creating tabs...');
              detailsArray.forEach((item, i) => {
                  const btnTab = document.createElement('button');
                  btnTab.type = 'button';
                  btnTab.className = 'u-btn u-btn--sm u-btn--ghost u-hover-lift';
                  btnTab.textContent = `Data ${i + 1}`;
                  btnTab.style.borderRadius = '20px';
                  
                  btnTab.addEventListener('click', (evt) => {
                      evt.preventDefault();
                      Array.from(tabsContainer.children).forEach(c => {
                          c.classList.remove('u-btn--brand', 'u-text-white');
                          c.classList.add('u-btn--ghost');
                      });

                      btnTab.classList.remove('u-btn--ghost');
                      btnTab.classList.add('u-btn--brand', 'u-text-white');
                      
                      renderContent(i);
                  });

                  tabsContainer.appendChild(btnTab);
              });

              console.log(`Created ${detailsArray.length} tabs`);

              const firstTab = tabsContainer.querySelector('button');
              if(firstTab) {
                  console.log('Clicking first tab...');
                  firstTab.click();
              } else {
                  console.log('No first tab found, rendering content directly');
                  renderContent(0);
              }

              const canApprove = btnDetail.getAttribute('data-can-approve') === 'true';
              const formApprove = detailModal.querySelector('.detail-approve-form');
              const formReject = detailModal.querySelector('.detail-reject-form');
              
              if(formApprove) {
                  formApprove.style.display = canApprove ? 'inline-block' : 'none';
                  formApprove.action = btnDetail.getAttribute('data-approve-url') || '';
              }
              if(formReject) {
                  formReject.style.display = canApprove ? 'inline-block' : 'none';
                  formReject.action = btnDetail.getAttribute('data-reject-url') || '';
              }

              detailModal.hidden = false; 
              document.body.classList.add('modal-open');
          }
      });
      updateVisibility(); 
    }, 
    initDT() { /* ... */ },
    bindExternalSearch() { /* ... */ }
  };
  page.init();
});
</script>
@endsection