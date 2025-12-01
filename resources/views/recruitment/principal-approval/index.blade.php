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

  // [PENTING] Definisikan $dhcUnitId di sini agar tidak error di loop bawah
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

  // PIC Options
  $nameCandidates = ['full_name','name','employee_name','display_name']; 
  $nameCol = 'name'; 
  
  foreach ($nameCandidates as $c) {
      if (Schema::hasColumn('employees', $c)) {
          $nameCol = $c;
          break;
      }
  }

  // Fetch Raw Data
  try {
      $rawPics = DB::table('employees')
          ->join('persons', 'employees.person_id', '=', 'persons.id')
          ->select(
              'employees.id', 
              'employees.employee_id', 
              'persons.full_name'
          )
          ->where('employees.unit_id', $selectedUnitId ?? $meUnit)
          ->orderBy('persons.full_name')
          ->get();
  } catch (\Exception $e) {
      $rawPics = collect();
  }

  $picListFormatted = $rawPics->map(function($p) {
      $empId = $p->employee_id ?? '-';
      $name  = $p->full_name ?? '-';
      return [
          'id' => $p->id,
          'name' => $empId . ' - ' . $name
      ];
  })->values();

@endphp

<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>

    {{-- Filter Unit --}}
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

  {{-- Notifikasi & Error --}}
  @if(session('ok'))
    @push('swal')
      <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script>
    @endpush
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class="u-error-icon fas fa-exclamation-circle"></i>
        <span class="u-font-semibold">Periksa kembali data berikut:</span>
      </div>
      <ul class="u-list">
        @foreach($errors->all() as $e)
          <li class="u-item">{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="dt-wrapper">
    <div class="u-flex u-items-center u-justify-between u-mb-sm">
      <div class="u-font-semibold">Daftar Izin Prinsip</div>
      <span class="u-badge u-badge--glass">
        {{ $canSeeAll && !$selectedUnitId ? 'All units' : 'Unit ID: '.($selectedUnitId ?? $meUnit) }}
      </span>
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
            <th>Target Mulai</th>
            <th>Progress</th>
            <th>Sumber Anggaran</th>
            <th>Status</th>
            <th class="cell-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($list as $r)
          @php
            $sameUnit = $meUnit && (string)$meUnit === (string)$r->unit_id;
            $stageIndex = null;
            if ($r->relationLoaded('approvals')) {
              foreach ($r->approvals as $i => $ap) {
                if (($ap->status ?? 'pending') === 'pending') { $stageIndex = $i; break; }
              }
            }
            $meRoles = [
              'Superadmin'  => $me && $me->hasRole('Superadmin'),
              'Kepala Unit' => $me && $me->hasRole('Kepala Unit'),
              'DHC'         => $me && $me->hasRole('DHC'),
              'Dir SDM'     => $me && $me->hasRole('Dir SDM'),
            ];
            $status = $r->status ?? 'draft';
            $badge  = $status === 'rejected' ? 'u-badge--danger'
                    : ($status === 'draft' ? 'u-badge--warn'
                    : ($status === 'approved' ? 'u-badge--primary' : 'u-badge--soft'));
            $employmentType  = $r->employment_type ?? $r->contract_type ?? null;
            $targetStart     = $r->target_start_date ?? $r->start_date ?? null;
            $budgetSource    = $r->budget_source_type ?? $r->budget_source ?? null;
            $requestType     = $r->request_type ?? $r->type ?? 'Rekrutmen';
            $publishPref = $r->publish_vacancy_pref ?? $r->publish_pref ?? $r->publish_vacancy ?? '';
            $budgetRef   = $r->budget_ref ?? $r->rkap_ref ?? $r->rab_ref ?? $r->budget_reference ?? '';
            $justif      = $r->justification ?? $r->reason ?? $r->notes ?? $r->note ?? $r->description ?? '';
            $unitNameRow = $r->unit_id ? ($unitMap[$r->unit_id] ?? ('Unit #'.$r->unit_id)) : '-';
            $totalStages   = 3;
            $progressStep  = null;
            if ($status === 'draft') { $progressText = 'Draft di SDM Unit'; $progressStep = 0; }
            elseif ($status === 'rejected') { $progressText = 'Ditolak'; }
            elseif ($status === 'approved') { $progressText = 'Selesai (Approved Dir SDM)'; $progressStep = $totalStages; }
            elseif ($stageIndex === 0) { $progressText = 'Menunggu Kepala Unit'; $progressStep = 1; }
            elseif ($stageIndex === 1) { $progressText = 'Menunggu DHC'; $progressStep = 2; }
            elseif ($stageIndex === 2) { $progressText = 'Menunggu Dir SDM'; $progressStep = 3; }
            else { $progressText = 'In Review'; }

            // LOGIKA APPROVAL PERMISSION
            $canStage = false;
            if(in_array($status, ['in_review','submitted']) && $stageIndex !== null) {
                if ($meRoles['Superadmin']) $canStage = true;
                else {
                    if ($stageIndex === 0) $canStage = $meRoles['Kepala Unit'] && ((string)$meUnit === (string)$r->unit_id);
                    elseif ($stageIndex === 1) {
                      $isKepalaUnitDHC = $meRoles['Kepala Unit'] && $dhcUnitId && ((string)$meUnit === (string)$dhcUnitId);
                      $canStage = $meRoles['DHC'] || $isKepalaUnitDHC;
                    } elseif ($stageIndex === 2) $canStage = $meRoles['Dir SDM'];
                }
            }
          @endphp
          <tr>
            <td>
              @if(!empty($r->ticket_number))
                <span class="u-badge u-badge--primary u-text-2xs" title="Nomor Ticket">{{ $r->ticket_number }}</span>
              @else
                <span class="u-text-2xs u-text-muted">-</span>
              @endif
            </td>
            <td>
              <span class="u-font-medium">{{ $r->title }}</span>
              <div class="u-text-2xs u-muted">
                Dibuat {{ optional($r->created_at)->format('d M Y') ?? '-' }}
              </div>
            </td>
            <td>{{ $unitNameRow }}</td>
            <td>
              <span class="u-badge u-badge--glass u-text-2xs">
                @if($requestType === 'Perpanjang Kontrak') Perpanjang Kontrak @elseif($requestType === 'Rekrutmen') Rekrutmen @else {{ $requestType }} @endif
              </span>
            </td>
            <td>{{ $r->position }}</td>
            <td><span class="u-badge u-badge--glass">{{ $r->headcount }} orang</span></td>
            <td>@if($employmentType) <span class="u-badge u-badge--glass">{{ $employmentType }}</span> @else <span class="u-text-2xs u-muted">-</span> @endif</td>
            <td>@if($targetStart) <span class="u-text-sm">{{ \Illuminate\Support\Carbon::parse($targetStart)->format('d M Y') }}</span> @else <span class="u-text-2xs u-muted">-</span> @endif</td>
            <td>
              <div class="u-text-2xs">
                <span class="u-badge u-badge--glass">{{ $progressText }}</span>
                @if($progressStep !== null) <div class="u-muted u-mt-xxs">Stage {{ $progressStep }} / {{ $totalStages }}</div> @endif
              </div>
            </td>
            <td>@if($budgetSource) <span class="u-badge u-badge--glass u-text-2xs">{{ $budgetSource }}</span> @else <span class="u-text-2xs u-muted">-</span> @endif</td>
            <td><span class="u-badge {{ $badge }}">{{ ucfirst($status) }}</span></td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                @if($status === 'draft' && ($sameUnit || $meRoles['Superadmin']))
                  @if($canCreate)
                    {{-- Edit Button --}}
                    <button type="button" class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Edit draft"
                            data-modal-open="createApprovalModal" data-mode="edit"
                            data-update-url="{{ route('recruitment.principal-approval.update',$r) }}"
                            data-request-type="{{ e($requestType) }}" data-title="{{ e($r->title) }}"
                            data-position="{{ e($r->position) }}" data-headcount="{{ (int) $r->headcount }}"
                            data-employment-type="{{ e($employmentType ?? '') }}" data-target-start="{{ $targetStart }}"
                            data-budget-source-type="{{ e($budgetSource ?? '') }}" data-budget-ref="{{ e($budgetRef) }}"
                            data-justification="{{ e($justif) }}">
                      <i class="fas fa-edit u-mr-xs"></i> Edit
                    </button>
                    {{-- Submit Button --}}
                    <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}"
                          class="u-inline js-confirm" data-confirm-title="Submit permintaan?"
                          data-confirm-text="Permintaan akan dikirim untuk persetujuan berjenjang."
                          data-confirm-icon="question">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift"><i class="fas fa-paper-plane u-mr-xs"></i> Submit</button>
                    </form>
                  @endif
                @endif

                {{-- Detail Button (Selalu Ada - Fungsi Approve/Reject dipicu dari sini via JS) --}}
                <button type="button" class="u-btn u-btn--outline u-btn--sm u-hover-lift js-open-detail"
                        data-modal-open="detailApprovalModal"
                        data-id="{{ $r->id }}"
                        data-ticket-number="{{ $r->ticket_number ?? '-' }}"
                        data-title="{{ e($r->title) }}"
                        data-unit="{{ e($unitNameRow) }}"
                        data-request-type="{{ e($requestType) }}"
                        data-position="{{ e($r->position) }}"
                        data-headcount="{{ (int) $r->headcount }}"
                        data-employment-type="{{ e($employmentType ?? '') }}"
                        data-target-start="{{ $targetStart ? \Illuminate\Support\Carbon::parse($targetStart)->format('d M Y') : '-' }}"
                        data-budget-source="{{ e($budgetSource ?? '') }}"
                        data-budget-ref="{{ e($budgetRef) }}"
                        data-justification="{{ e($justif) }}"
                        data-status="{{ e(ucfirst($status)) }}"
                        data-can-approve="{{ $canStage ? 'true' : 'false' }}"
                        data-approve-url="{{ route('recruitment.principal-approval.approve',$r) }}"
                        data-reject-url="{{ route('recruitment.principal-approval.reject',$r) }}">
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

{{-- MODAL 1: URAIAN JABATAN --}}
<div id="uraianModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-alt"></i></div>
        <div><div class="u-title">Editor Uraian Jabatan</div><div class="u-muted u-text-sm">Template formulir uraian dapat diedit sebelum disimpan</div></div>
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
        <div class="u-muted u-text-sm">Simpan draft untuk melanjutkan editing nanti.</div>
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
        <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Permintaan</label><select class="u-input" name="request_type"><option value="Rekrutmen">Rekrutmen</option><option value="Perpanjang Kontrak">Perpanjang Kontrak</option></select></div>
        <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Judul Permintaan</label><input class="u-input" name="title" placeholder="Mis. Rekrutmen Analis TKDN Proyek X" required></div>
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi</label><input class="u-input" name="position" placeholder="Masukkan posisi (mis. Analis TKDN)" required></div>
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Headcount</label><input class="u-input" type="number" min="1" name="headcount" value="1" placeholder="Jumlah orang" required></div>
        </div>
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Kontrak</label><select class="u-input" id="contractTypeSelect" name="employment_type"><option value="">Pilih jenis kontrak</option><option value="Organik">Organik</option><option value="Project Based">Project Based</option></select></div>
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Target Mulai Kerja</label><input class="u-input" type="date" name="target_start_date"></div>
        </div>
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Sumber Anggaran</label><select class="u-input" id="budgetSourceSelect" name="budget_source_type" style="-webkit-appearance: none; -moz-appearance: none; appearance: none;"><option value="">Sumber anggaran</option><option value="RKAP">RKAP</option><option value="RAB Proyek">RAB Proyek</option><option value="Lainnya">Lainnya</option></select></div>
          <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Referensi Anggaran</label><input class="u-input" name="budget_ref" placeholder="No. RKAP / No. RAB / keterangan singkat"></div>
        </div>

        {{-- SECTION PROJECT BASED --}}
        <div id="projectSection" class="u-space-y-md" style="display:none;border:1px dashed #e6e6e6;padding:12px;border-radius:6px;">
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
          
          {{-- SEARCHABLE: Jabatan (Project) --}}
          <div class="u-space-y-sm" style="position: relative;">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Jabatan (Project)</label>
            <input type="text" id="jabatanSearchInput" class="u-input" placeholder="Ketik untuk mencari jabatan..." autocomplete="off">
            <input type="hidden" name="jabatan_project" id="jabatanProjectInput">
            <div id="jabatanSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
          </div>

          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan</label>
            <div class="u-flex u-items-center u-gap-sm">
              <div id="uraianStatusProject" class="u-text-2xs u-muted">Belum ada uraian</div>
              <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian-project">Isi Uraian</button>
            </div>
            <textarea name="uraian_jabatan_project" id="uraianJabatanProject" class="u-input" style="display:none"></textarea>
          </div>

          {{-- SEARCHABLE: PIC (Project) --}}
          <div class="u-space-y-sm" style="position: relative;">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC</label>
            <input type="text" id="picProjectSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
            <input type="hidden" name="pic_project" id="picProjectInput">
            <div id="picProjectSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
          </div>
        </div>

        {{-- SECTION ORGANIK --}}
        <div id="rkapSection" class="u-space-y-md" style="display:none;border:1px dashed #e6e6e6;padding:12px;border-radius:6px;">
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
              <div><label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan</label><div class="u-flex u-items-center u-gap-sm"><div id="uraianStatus" class="u-text-2xs u-muted">Belum ada uraian</div><button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian">Isi Uraian</button></div></div>
              {{-- SEARCHABLE: PIC (Organik) --}}
              <div style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC</label>
                <input type="text" id="picOrganikSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
                <input type="hidden" name="pic" id="picOrganikInput">
                <div id="picOrganikSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="u-space-y-sm"><label class="u-block u-text-sm u-font-medium u-mb-sm">Justifikasi</label><textarea class="u-input" name="justification" rows="4" placeholder="Jelaskan kebutuhan rekrutmen..."></textarea></div>
      </form>
    </div>
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button form="createApprovalForm" class="u-btn u-btn--brand u-hover-lift"><i class="fas fa-save u-mr-xs"></i> Simpan Draft</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL 3: DETAIL APPROVAL (SHOW INFO + APPROVE/REJECT) --}}
<div id="detailApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card">
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
    <div class="u-modal__body">
      {{-- Margin top added here --}}
      <div class="u-space-y-md u-mt-md">
        <div class="u-grid-2">
            <div><strong>No Ticket:</strong> <span class="detail-ticket">-</span></div>
            <div><strong>Status:</strong> <span class="detail-status">-</span></div>
            <div><strong>Judul:</strong> <span class="detail-title u-font-medium">-</span></div>
            <div><strong>Unit:</strong> <span class="detail-unit">-</span></div>
            <div><strong>Jenis Permintaan:</strong> <span class="detail-request-type">-</span></div>
            <div><strong>Posisi:</strong> <span class="detail-position">-</span></div>
            <div><strong>HC:</strong> <span class="detail-headcount">-</span></div>
            <div><strong>Jenis Kontrak:</strong> <span class="detail-employment">-</span></div>
            <div><strong>Target Mulai:</strong> <span class="detail-target">-</span></div>
            <div><strong>Sumber Anggaran:</strong> <span class="detail-budget-source">-</span></div>
            <div><strong>Referensi:</strong> <span class="detail-budget-ref">-</span></div>
        </div>
        <div>
            <strong>Justifikasi:</strong> 
            <div class="detail-justification u-text-sm u-muted u-p-sm u-bg-light u-rounded">-</div>
        </div>
      </div>
    </div>
    {{-- FOOTER WITH APPROVE/REJECT BUTTONS --}}
    <div class="u-modal__foot">
      <div class="u-flex u-justify-between u-items-center u-gap-sm">
        <div class="u-muted u-text-sm"></div>
        <div class="u-flex u-gap-sm action-buttons">
          {{-- Approve Form --}}
          <form method="POST" action="" class="detail-approve-form u-inline js-confirm" style="display:none;">
            @csrf
            <button type="submit" class="u-btn u-btn--outline u-btn--sm u-success detail-approve-btn" data-confirm-title="Setujui permintaan?" data-confirm-text="Lanjut?" data-confirm-icon="success"><i class="fas fa-check u-mr-xs"></i> Approve</button>
          </form>
          {{-- Reject Form --}}
          <form method="POST" action="" class="detail-reject-form u-inline js-confirm" style="display:none;">
            @csrf
            <button type="submit" class="u-btn u-btn--outline u-btn--sm u-danger detail-reject-btn" data-confirm-title="Tolak permintaan?" data-confirm-text="Stop?" data-confirm-icon="error"><i class="fas fa-times u-mr-xs"></i> Reject</button>
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
      
      const contractTypeSelect = form.querySelector('#contractTypeSelect');
      const budgetSourceSelect = form.querySelector('#budgetSourceSelect');
      
      // Organik
      const rkapSection      = form.querySelector('#rkapSection');
      const rkapSelectedInfo = form.querySelector('#rkapSelectedInfo');
      const rkapSelectedName = form.querySelector('#rkapSelectedName');
      const uraianStatus     = form.querySelector('#uraianStatus');
      const picOrganikSearchInput = form.querySelector('#picOrganikSearchInput');
      const picOrganikInput       = form.querySelector('#picOrganikInput');
      const picOrganikResults     = form.querySelector('#picOrganikSearchResults');
      
      // Project
      const projectSection       = form.querySelector('#projectSection');
      const kodeProjectSelect    = form.querySelector('#kodeProjectSelect');
      const namaProjectInput     = form.querySelector('#namaProjectInput');
      const uraianStatusProject  = form.querySelector('#uraianStatusProject');
      const uraianJabatanProject = form.querySelector('#uraianJabatanProject');
      
      // Searchables Project
      const jabatanSearchInput   = form.querySelector('#jabatanSearchInput');
      const jabatanProjectInput  = form.querySelector('#jabatanProjectInput');
      const jabatanSearchResults = form.querySelector('#jabatanSearchResults');
      const picProjectSearchInput = form.querySelector('#picProjectSearchInput');
      const picProjectInput       = form.querySelector('#picProjectInput');
      const picProjectResults     = form.querySelector('#picProjectSearchResults');

      // Modal Uraian
      const uraianModal    = document.getElementById('uraianModal');
      const uraianEditor   = document.getElementById('uraianEditor');
      const uraianModalJob = document.getElementById('uraianModalJob');

      // Modal Detail
      const detailModal = document.getElementById('detailApprovalModal');

      const defaultTemplate = "Nama Jabatan:\nUnit Kerja:\nPemangku:\nMelapor pada:\nTanggal\nLokasi\n...";
      let currentSelectedJob = null; 
      const jobDescriptions = {};    
      let projectUraianContent = ''; 

      // --- Z-Index Helpers ---
      function openUraianModalSafe(title, content, mode) {
          uraianEditor.setAttribute('data-mode', mode);
          uraianModalJob.textContent = title;
          uraianEditor.value = content || '';
          if (modalMain) modalMain.style.zIndex = '1050'; 
          if (uraianModal) uraianModal.style.zIndex = '2000'; 
          uraianModal.hidden = false;
          document.body.classList.add('modal-open');
      }
      function closeUraianModalSafe() {
          uraianModal.hidden = true;
          if (modalMain) modalMain.style.zIndex = '';
          if (uraianModal) uraianModal.style.zIndex = '';
          if (!modalMain.hidden) document.body.classList.add('modal-open');
          else document.body.classList.remove('modal-open');
      }

      // --- REUSABLE SEARCHABLE DROPDOWN FUNCTION ---
      function setupSearchableDropdown(searchInput, hiddenInput, resultsContainer, dataArray, placeholderText) {
          if (!searchInput || !resultsContainer) return;

          const renderOptions = (filterText = '') => {
              resultsContainer.innerHTML = '';
              const lowerFilter = filterText.toLowerCase();
              const filtered = dataArray.filter(item => item.name.toLowerCase().includes(lowerFilter));

              if (filtered.length === 0) {
                  const noRes = document.createElement('div');
                  noRes.className = 'u-p-sm u-text-muted u-text-center';
                  noRes.textContent = 'Tidak ditemukan';
                  resultsContainer.appendChild(noRes);
              } else {
                  filtered.forEach(item => {
                      const div = document.createElement('div');
                      div.className = 'u-p-sm';
                      div.style.cursor = 'pointer';
                      div.style.borderBottom = '1px solid #f0f0f0';
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
              }
          };

          searchInput.addEventListener('focus', () => {
              renderOptions(searchInput.value);
              resultsContainer.style.display = 'block';
          });

          searchInput.addEventListener('input', (e) => {
              renderOptions(e.target.value);
              resultsContainer.style.display = 'block';
          });

          document.addEventListener('click', (e) => {
              if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                  resultsContainer.style.display = 'none';
              }
          });
      }

      setupSearchableDropdown(jabatanSearchInput, jabatanProjectInput, jabatanSearchResults, positionsData);
      setupSearchableDropdown(picProjectSearchInput, picProjectInput, picProjectResults, picData);
      setupSearchableDropdown(picOrganikSearchInput, picOrganikInput, picOrganikResults, picData);


      // --- Visibility Logic ---
      const updateVisibility = () => {
        const type = contractTypeSelect ? contractTypeSelect.value : '';
        const budget = budgetSourceSelect ? budgetSourceSelect.value : '';
        const isOrganik = (type === 'Organik') && (budget === 'RKAP');
        const isProject = (type === 'Project Based'); 

        if (rkapSection) rkapSection.style.display = isOrganik ? 'block' : 'none';
        if (!isOrganik && rkapSelectedInfo) rkapSelectedInfo.style.display = 'none';
        if (projectSection) projectSection.style.display = isProject ? 'block' : 'none';
      };

      const setBudgetLock = (lock, value) => {
        if (!budgetSourceSelect) return;
        if (lock) {
          budgetSourceSelect.value = value;
          budgetSourceSelect.setAttribute('disabled', 'disabled'); 
          let hidden = document.getElementById('budgetSourceHidden');
          if (!hidden) {
            hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.id = 'budgetSourceHidden'; hidden.name = 'budget_source_type'; form.appendChild(hidden);
          }
          hidden.value = value;
        } else {
          budgetSourceSelect.removeAttribute('disabled'); budgetSourceSelect.value = ''; 
          const hidden = document.getElementById('budgetSourceHidden'); if (hidden) hidden.remove();
        }
      };

      if (contractTypeSelect) {
        contractTypeSelect.addEventListener('change', function() {
          const val = this.value;
          if(kodeProjectSelect) kodeProjectSelect.value = '';
          if(namaProjectInput) namaProjectInput.value = '';
          [jabatanSearchInput, jabatanProjectInput, picProjectSearchInput, picProjectInput, picOrganikSearchInput, picOrganikInput].forEach(el => { if(el) el.value = ''; });
          
          if(projectSection) projectSection.style.display = 'none';

          if (val === 'Organik') { setBudgetLock(true, 'RKAP'); }
          else if (val === 'Project Based') { setBudgetLock(true, 'RAB Proyek'); }
          else { setBudgetLock(false, ''); }
          updateVisibility();
        });
      }
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
            openUraianModalSafe('Project Based Position', projectUraianContent || defaultTemplate, 'project');
        }
      });

      function toggleRkapSelect(sel) {
        const tr = sel.closest('tr');
        const job = tr.dataset.jobName;
        form.querySelectorAll('.js-rkap-select.selected').forEach(el => {
            if (el !== sel) { el.classList.remove('selected', 'u-success'); el.classList.add('u-btn--outline'); el.innerHTML = '+'; }
        });
        const isSelected = sel.classList.contains('selected');
        if (isSelected) {
            sel.classList.remove('selected', 'u-success'); sel.classList.add('u-btn--outline'); sel.innerHTML = '+';
            currentSelectedJob = null; rkapSelectedInfo.style.display = 'none';
        } else {
            sel.classList.add('selected', 'u-success'); sel.classList.remove('u-btn--outline'); sel.innerHTML = '<i class="fas fa-check"></i>';
            currentSelectedJob = job; rkapSelectedInfo.style.display = 'block';
            if(rkapSelectedName) rkapSelectedName.textContent = job;
            const saved = jobDescriptions[job];
            if(uraianStatus) uraianStatus.textContent = saved ? (saved.status === 'final' ? 'Tersimpan (Final)' : 'Tersimpan (Draft)') : 'Belum ada uraian';
        }
      }

      form.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-rkap-select');
        if (btn) { e.preventDefault(); toggleRkapSelect(btn); }
      });

      form.addEventListener('click', function(e) {
        if (e.target.closest('.js-open-uraian')) {
            if (!currentSelectedJob) { alert('Pilih Job Function di tabel RKAP terlebih dahulu.'); return; }
            const saved = jobDescriptions[currentSelectedJob];
            openUraianModalSafe(currentSelectedJob, saved ? saved.content : defaultTemplate, 'organik');
        }
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
                projectUraianContent = content;
                if(uraianJabatanProject) uraianJabatanProject.value = content;
                if(uraianStatusProject) uraianStatusProject.textContent = statusText;
            } else if (mode === 'organik') {
                if(currentSelectedJob) {
                    jobDescriptions[currentSelectedJob] = { content: content, status: saveFinal ? 'final' : 'draft' };
                    if(uraianStatus) uraianStatus.textContent = statusText;
                    let hiddenInput = form.querySelector('input[name="selected_job_json"]');
                    if(!hiddenInput) { hiddenInput = document.createElement('input'); hiddenInput.type = 'hidden'; hiddenInput.name = 'selected_job_json'; form.appendChild(hiddenInput); }
                    const dataPayload = { job_function: currentSelectedJob, description: content, status: saveFinal ? 'final' : 'draft' };
                    hiddenInput.value = JSON.stringify(dataPayload);
                }
            }
            closeUraianModalSafe();
        }
      });

      document.addEventListener('click', function(e) {
         if (e.target.matches('[data-modal-close]')) {
             const m = e.target.closest('.u-modal');
             if(m) { m.hidden = true; document.body.classList.remove('modal-open'); }
         }
         
         const btnCreate = e.target.closest('[data-modal-open="createApprovalModal"]');
         if(btnCreate) {
             const m = document.getElementById('createApprovalModal');
             if(m) { 
                 m.hidden = false; document.body.classList.add('modal-open'); 
                 const mode = btnCreate.getAttribute('data-mode');
                 if(mode === 'create') {
                     form.reset(); setBudgetLock(false, ''); updateVisibility();
                     [jabatanSearchInput, jabatanProjectInput, picProjectSearchInput, picProjectInput, picOrganikSearchInput, picOrganikInput].forEach(el => { if(el) el.value = ''; });
                 }
             }
         }

         // Detail Modal Handler
         const btnDetail = e.target.closest('.js-open-detail');
         if (btnDetail && detailModal) {
             const ticket = btnDetail.getAttribute('data-ticket-number') || '-';
             const title = btnDetail.getAttribute('data-title') || '-';
             const unit = btnDetail.getAttribute('data-unit') || '-';
             const reqType = btnDetail.getAttribute('data-request-type') || '-';
             const position = btnDetail.getAttribute('data-position') || '-';
             const headcount = btnDetail.getAttribute('data-headcount') || '-';
             const employment = btnDetail.getAttribute('data-employment-type') || '-';
             const target = btnDetail.getAttribute('data-target-start') || '-';
             const budgetSource = btnDetail.getAttribute('data-budget-source') || '-';
             const budgetRef = btnDetail.getAttribute('data-budget-ref') || '-';
             const justification = btnDetail.getAttribute('data-justification') || '-';
             const status = btnDetail.getAttribute('data-status') || '-';
             const canApprove = btnDetail.getAttribute('data-can-approve') === 'true';
             const approveUrl = btnDetail.getAttribute('data-approve-url');
             const rejectUrl = btnDetail.getAttribute('data-reject-url');

             detailModal.querySelector('.detail-ticket').textContent = ticket;
             detailModal.querySelector('.detail-title').textContent = title;
             detailModal.querySelector('.detail-unit').textContent = unit;
             detailModal.querySelector('.detail-request-type').textContent = reqType;
             detailModal.querySelector('.detail-position').textContent = position;
             detailModal.querySelector('.detail-headcount').textContent = headcount;
             detailModal.querySelector('.detail-employment').textContent = employment;
             detailModal.querySelector('.detail-target').textContent = target;
             detailModal.querySelector('.detail-budget-source').textContent = budgetSource;
             detailModal.querySelector('.detail-budget-ref').textContent = budgetRef;
             detailModal.querySelector('.detail-justification').textContent = justification;
             detailModal.querySelector('.detail-status').textContent = status;

             const approveForm = detailModal.querySelector('form.detail-approve-form');
             const rejectForm = detailModal.querySelector('form.detail-reject-form');
             
             // LOGIKA MENAMPILKAN TOMBOL APPROVE/REJECT
             if (canApprove) {
                 if(approveForm) { approveForm.style.display = 'inline-block'; approveForm.setAttribute('action', approveUrl); }
                 if(rejectForm) { rejectForm.style.display = 'inline-block'; rejectForm.setAttribute('action', rejectUrl); }
             } else {
                 if(approveForm) approveForm.style.display = 'none';
                 if(rejectForm) rejectForm.style.display = 'none';
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