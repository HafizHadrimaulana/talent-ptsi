@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Gate;

  /** @var \App\Models\User|null $me */
  $me     = auth()->user();
  $meUnit = $me ? $me->unit_id : null;

  // fallback dari controller
  $canSeeAll      = isset($canSeeAll)      ? $canSeeAll      : false;
  $selectedUnitId = isset($selectedUnitId) ? $selectedUnitId : null;
  $units          = isset($units)          ? $units          : collect();

  if ($units->isEmpty()) {
      $units = $canSeeAll
          ? DB::table('units')->select('id','name')->orderBy('name')->get()
          : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
  }

  // map unit id -> name (buat tampilan tabel)
  $unitMap = $units->pluck('name','id');

  // ambil id unit DHC (Divisi Human Capital)
  $dhcUnitId = DB::table('units')
      ->where(function($q){
          $q->where('code','DHC')
            ->orWhere('name','Divisi Human Capital')
            ->orWhere('name','like','Divisi Human Capital%');
      })->value('id');

  // izinkan SDM Unit + Superadmin + permission tertentu melihat tombol create
  $canCreate = Gate::check('recruitment.create')
                || Gate::check('recruitment.update')
                || ($me && $me->hasRole('SDM Unit'))
                || ($me && $me->hasRole('Superadmin'));
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

  {{-- Notifikasi --}}
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

            // progress label: Izin Prinsip → flow sampai approved Dir SDM
            $totalStages   = 3;
            $progressStep  = null;
            if ($status === 'draft') {
                $progressText = 'Draft di SDM Unit';
                $progressStep = 0;
            } elseif ($status === 'rejected') {
                $progressText = 'Ditolak';
            } elseif ($status === 'approved') {
                $progressText = 'Selesai (Approved Dir SDM)';
                $progressStep = $totalStages;
            } elseif ($stageIndex === 0) {
                $progressText = 'Menunggu Kepala Unit';
                $progressStep = 1;
            } elseif ($stageIndex === 1) {
                $progressText = 'Menunggu DHC';
                $progressStep = 2;
            } elseif ($stageIndex === 2) {
                $progressText = 'Menunggu Dir SDM';
                $progressStep = 3;
            } else {
                $progressText = 'In Review';
            }
          @endphp
          <tr>
            <td>
              @if(!empty($r->request_no))
                <span class="u-badge u-badge--glass u-text-2xs">{{ $r->request_no }}</span>
              @else
                <span class="u-text-2xs u-muted">-</span>
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
                {{-- align dg 2 jalur flow: Rekrutmen & Perpanjang Kontrak --}}
                @if($requestType === 'Perpanjang Kontrak')
                  Perpanjang Kontrak
                @elseif($requestType === 'Rekrutmen')
                  Rekrutmen
                @else
                  {{ $requestType }}
                @endif
              </span>
            </td>
            <td>{{ $r->position }}</td>
            <td><span class="u-badge u-badge--glass">{{ $r->headcount }} orang</span></td>
            <td>
              @if($employmentType)
                <span class="u-badge u-badge--glass">{{ $employmentType }}</span>
              @else
                <span class="u-text-2xs u-muted">-</span>
              @endif
            </td>
            <td>
              @if($targetStart)
                <span class="u-text-sm">{{ \Illuminate\Support\Carbon::parse($targetStart)->format('d M Y') }}</span>
              @else
                <span class="u-text-2xs u-muted">-</span>
              @endif
            </td>
            <td>
              <div class="u-text-2xs">
                <span class="u-badge u-badge--glass">{{ $progressText }}</span>
                @if($progressStep !== null)
                  <div class="u-muted u-mt-xxs">Stage {{ $progressStep }} / {{ $totalStages }}</div>
                @endif
              </div>
            </td>
            <td>
              @if($budgetSource)
                <span class="u-badge u-badge--glass u-text-2xs">{{ $budgetSource }}</span>
              @else
                <span class="u-text-2xs u-muted">-</span>
              @endif
            </td>
            <td><span class="u-badge {{ $badge }}">{{ ucfirst($status) }}</span></td>

            <td class="cell-actions">
              <div class="cell-actions__group">
                {{-- Draft: Edit + Submit (SDM unit & Superadmin) --}}
                @if($status === 'draft' && ($sameUnit || $meRoles['Superadmin']))
                  @if($canCreate)
                    <button type="button"
                            class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                            title="Edit draft"
                            data-modal-open="createApprovalModal"
                            data-mode="edit"
                            data-update-url="{{ route('recruitment.principal-approval.update',$r) }}"
                            data-request-type="{{ e($requestType) }}"
                            data-title="{{ e($r->title) }}"
                            data-position="{{ e($r->position) }}"
                            data-headcount="{{ (int) $r->headcount }}"
                            data-employment-type="{{ e($employmentType ?? '') }}"
                            data-target-start="{{ $targetStart }}"
                            data-budget-source-type="{{ e($budgetSource ?? '') }}"
                            data-budget-ref="{{ e($budgetRef) }}"
                            data-publish-pref="{{ e($publishPref) }}"
                            data-justification="{{ e($justif) }}">
                      <i class="fas fa-edit u-mr-xs"></i> Edit
                    </button>

                    <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Submit permintaan?"
                          data-confirm-text="Permintaan akan dikirim untuk persetujuan berjenjang."
                          data-confirm-icon="question">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift">
                        <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                      </button>
                    </form>
                  @endif
                @endif

                {{-- Approval berjenjang --}}
                @if(in_array($status, ['in_review','submitted']) && $stageIndex !== null)
                  @php
                    $canStage = false;

                    if ($meRoles['Superadmin']) {
                        $canStage = true;
                    } else {
                        if ($stageIndex === 0) {
                          $canStage = $meRoles['Kepala Unit'] && ((string)$meUnit === (string)$r->unit_id);
                        } elseif ($stageIndex === 1) {
                          $isKepalaUnitDHC = $meRoles['Kepala Unit'] && $dhcUnitId && ((string)$meUnit === (string)$dhcUnitId);
                          $canStage = $meRoles['DHC'] || $isKepalaUnitDHC;
                        } elseif ($stageIndex === 2) {
                          $canStage = $meRoles['Dir SDM'];
                        }
                    }
                  @endphp

                  @if($canStage)
                    <form method="POST" action="{{ route('recruitment.principal-approval.approve',$r) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Setujui permintaan?"
                          data-confirm-text="Permintaan akan diteruskan ke approver berikutnya."
                          data-confirm-icon="success">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-success">
                        <i class="fas fa-check u-mr-xs"></i> Approve
                      </button>
                    </form>

                    <form method="POST" action="{{ route('recruitment.principal-approval.reject',$r) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Tolak permintaan?"
                          data-confirm-text="Proses akan dihentikan."
                          data-confirm-icon="error">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-danger">
                        <i class="fas fa-times u-mr-xs"></i> Reject
                      </button>
                    </form>
                  @endif
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create / Edit Approval Modal -->
<div id="createApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Buat Izin Prinsip Baru</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Ajukan permintaan rekrutmen atau perpanjangan kontrak</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="u-modal__body">
      <form method="POST"
            action="{{ route('recruitment.principal-approval.store') }}"
            class="u-space-y-md u-p-md"
            id="createApprovalForm"
            data-default-action="{{ route('recruitment.principal-approval.store') }}">
        @csrf

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Permintaan</label>
          <select class="u-input" name="request_type">
            <option value="Rekrutmen">Rekrutmen</option>
            <option value="Perpanjang Kontrak">Perpanjang Kontrak</option>
          </select>
        </div>

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Judul Permintaan</label>
          <input class="u-input" name="title" placeholder="Mis. Rekrutmen Analis TKDN Proyek X" required>
        </div>

        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi</label>
            <input class="u-input" name="position" placeholder="Masukkan posisi (mis. Analis TKDN)" required>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Headcount</label>
            <input class="u-input" type="number" min="1" name="headcount" value="1" placeholder="Jumlah orang" required>
          </div>
        </div>

        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Kontrak</label>
            <select class="u-input" name="employment_type">
              <option value="">Pilih jenis kontrak</option>
              <option value="Organik">Organik</option>
              <option value="Project Based">Project Based</option>
              <option value="Alih Daya">Alih Daya</option>
            </select>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Target Mulai Kerja</label>
            <input class="u-input" type="date" name="target_start_date">
          </div>
        </div>

        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Sumber Anggaran</label>
            <select class="u-input" name="budget_source_type">
              <option value="">Pilih sumber anggaran</option>
              <option value="RKAP">RKAP</option>
              <option value="RAB Proyek">RAB Proyek</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Referensi Anggaran</label>
            <input class="u-input" name="budget_ref" placeholder="No. RKAP / No. RAB / keterangan singkat">
          </div>
        </div>

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Publikasikan Lowongan?</label>
          <select class="u-input" name="publish_vacancy_pref">
            <option value="">Belum ditentukan</option>
            <option value="yes">Ya, akan dipublikasikan</option>
            <option value="no">Tidak perlu dipublikasikan</option>
          </select>
        </div>

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Justifikasi</label>
          <textarea class="u-input" name="justification" rows="4"
            placeholder="Jelaskan kebutuhan rekrutmen, keterkaitan dengan RKAP/RAB, dan urgensi kebutuhan..."></textarea>
        </div>
      </form>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button form="createApprovalForm" class="u-btn u-btn--brand u-hover-lift">
          <i class="fas fa-save u-mr-xs"></i> Simpan Draft
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const page = {
    dt: null,
    init() { this.bindModal(); this.initDT(); this.bindExternalSearch(); },
    bindModal() {
      const modal   = document.getElementById('createApprovalModal');
      const form    = document.getElementById('createApprovalForm');
      const titleEl = document.getElementById('ip-modal-title');
      const subEl   = document.getElementById('ip-modal-subtitle');

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-modal-open]');
        if (btn) {
          const id   = btn.getAttribute('data-modal-open');
          const mode = btn.getAttribute('data-mode') || 'create';
          const el   = document.getElementById(id);
          if (!el || !form) return;

          // reset ke default action
          const defaultAction = form.dataset.defaultAction || form.getAttribute('action');
          form.setAttribute('action', defaultAction);

          // reset _method
          let methodInput = form.querySelector('input[name="_method"]');
          if (methodInput) methodInput.remove();

          if (mode === 'edit') {
            // ubah ke PUT
            const updateUrl = btn.getAttribute('data-update-url') || defaultAction;
            form.setAttribute('action', updateUrl);
            methodInput = document.createElement('input');
            methodInput.type  = 'hidden';
            methodInput.name  = '_method';
            methodInput.value = 'PUT';
            form.appendChild(methodInput);

            // isi field dari data-*
            const setVal = (name, val) => {
              const field = form.querySelector('[name="'+name+'"]');
              if (field) field.value = val ?? '';
            };

            setVal('request_type',        btn.getAttribute('data-request-type') || 'Rekrutmen');
            setVal('title',               btn.getAttribute('data-title') || '');
            setVal('position',            btn.getAttribute('data-position') || '');
            setVal('headcount',           btn.getAttribute('data-headcount') || '1');
            setVal('employment_type',     btn.getAttribute('data-employment-type') || '');
            setVal('target_start_date',   btn.getAttribute('data-target-start') || '');
            setVal('budget_source_type',  btn.getAttribute('data-budget-source-type') || '');
            setVal('budget_ref',          btn.getAttribute('data-budget-ref') || '');
            setVal('publish_vacancy_pref',btn.getAttribute('data-publish-pref') || '');
            const justif = btn.getAttribute('data-justification') || '';
            const ta = form.querySelector('textarea[name="justification"]');
            if (ta) ta.value = justif;

            if (titleEl)   titleEl.textContent   = 'Edit Izin Prinsip';
            if (subEl)     subEl.textContent     = 'Perbarui draft permintaan sebelum dikirim approval';
          } else {
            // mode create
            form.reset();
            if (titleEl)   titleEl.textContent   = 'Buat Izin Prinsip Baru';
            if (subEl)     subEl.textContent     = 'Ajukan permintaan rekrutmen atau perpanjangan kontrak';
          }

          el.hidden = false;
          document.body.classList.add('modal-open');
        }

        if (e.target.matches('[data-modal-close]') || (e.target.closest && e.target.closest('[data-modal-close]'))) {
          const el = e.target.closest ? e.target.closest('.u-modal') : null;
          if (el) {
            el.hidden = true;
            document.body.classList.remove('modal-open');
          }
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const open = document.querySelector('.u-modal:not([hidden])');
          if (open) {
            open.hidden = true;
            document.body.classList.remove('modal-open');
          }
        }
      });
    },
    initDT() {
      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        this.dt = jQuery('#ip-table').DataTable({
          responsive: true,
          paging: false,
          info: false,
          language: {
            search: "Cari:",
            zeroRecords: "Tidak ada data",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
        return;
      }
      if (typeof window.DataTable !== 'undefined') {
        this.dt = new window.DataTable('#ip-table', { responsive: true, perPageSelect: false });
      }
    },
    bindExternalSearch() {
      const ext = document.querySelector('input[name="q"]');
      if (!ext) return;
      const self = this; let t = null;
      function run(v){
        if (!self.dt) return;
        if (self.dt.search && self.dt.draw) { self.dt.search(v).draw(); return; }
        if (typeof self.dt.search === 'function') self.dt.search(v);
      }
      ext.addEventListener('input', function(e){
        clearTimeout(t);
        t = setTimeout(()=>run(e.target.value||''), 120);
      });
    }
  };
  page.init();
});
</script>
@endsection
