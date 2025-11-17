@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Gate;

  /** @var \App\Models\User $me */
  $me     = auth()->user();
  $meUnit = $me?->unit_id;

  $canSeeAll      = $canSeeAll      ?? false;
  $selectedUnitId = $selectedUnitId ?? null;
  $units          = $units          ?? collect();

  if ($units->isEmpty()) {
      $units = $canSeeAll
          ? DB::table('units')->select('id','name')->orderBy('name')->get()
          : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
  }

  // ambil id unit DHC (Divisi Human Capital)
  $dhcUnitId = DB::table('units')
      ->where(function($q){
          $q->where('code','DHC')
            ->orWhere('name','Divisi Human Capital')
            ->orWhere('name','like','Divisi Human Capital%');
      })->value('id');

  // ===== FIX: izinkan SDM Unit melihat tombol create =====
  $canCreate = Gate::check('recruitment.create')
                || Gate::check('recruitment.update')
                || ($me?->hasRole('SDM Unit') ?? false);
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
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createApprovalModal">
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
        <span class="u-font-semibold">Please fix the following errors:</span>
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
            <th>Judul</th>
            <th>Posisi</th>
            <th>HC</th>
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
              'Kepala Unit' => $me?->hasRole('Kepala Unit'),
              'DHC'         => $me?->hasRole('DHC'),
              'Dir SDM'     => $me?->hasRole('Dir SDM'),
            ];

            $status = $r->status;
            $badge  = $status === 'rejected' ? 'u-badge--danger'
                    : ($status === 'draft' ? 'u-badge--warn'
                    : ($status === 'approved' ? 'u-badge--primary' : 'u-badge--soft'));
          @endphp
          <tr>
            <td><span class="u-font-medium">{{ $r->title }}</span></td>
            <td>{{ $r->position }}</td>
            <td><span class="u-badge u-badge--glass">{{ $r->headcount }} orang</span></td>
            <td><span class="u-badge {{ $badge }}">{{ ucfirst($status) }}</span></td>

            <td class="cell-actions">
              <div class="cell-actions__group">
                {{-- SDM Unit submit DRAFT --}}
                @if(($r->status === 'draft') && $sameUnit)
                  @if($canCreate)
                  <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}"
                        class="u-inline js-confirm"
                        data-confirm-title="Submit permintaan?"
                        data-confirm-text="Permintaan akan dikirim untuk berjenjang."
                        data-confirm-icon="question">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift">
                      <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                    </button>
                  </form>
                  @endif
                @endif

                {{-- Approval berjenjang --}}
                @if(in_array($r->status, ['in_review','submitted']) && $stageIndex !== null)
                  @php
                    $canStage = false;

                    if ($stageIndex === 0) {
                      $canStage = $meRoles['Kepala Unit'] && ((string)$meUnit === (string)$r->unit_id);
                    } elseif ($stageIndex === 1) {
                      $isKepalaUnitDHC = $meRoles['Kepala Unit'] && $dhcUnitId && ((string)$meUnit === (string)$dhcUnitId);
                      $canStage = $meRoles['DHC'] || $isKepalaUnitDHC;
                    } elseif ($stageIndex === 2) {
                      $canStage = $meRoles['Dir SDM'];
                    }
                  @endphp

                  @if($canStage)
                    <form method="POST" action="{{ route('recruitment.principal-approval.approve',$r) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Setujui permintaan?"
                          data-confirm-text="Akan diteruskan ke tahap berikutnya."
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

<!-- Create Approval Modal -->
<div id="createApprovalModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title">Buat Izin Prinsip Baru</div>
          <div class="u-muted u-text-sm">Ajukan permintaan rekrutmen baru</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="u-modal__body">
      <form method="POST" action="{{ route('recruitment.principal-approval.store') }}"
            class="u-space-y-md u-p-md" id="createApprovalForm">
        @csrf
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Judul Permintaan</label>
          <input class="u-input" name="title" placeholder="Masukkan judul permintaan" required>
        </div>

        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi</label>
            <input class="u-input" name="position" placeholder="Masukkan posisi" required>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Headcount</label>
            <input class="u-input" type="number" min="1" name="headcount" value="1" placeholder="Jumlah orang" required>
          </div>
        </div>

        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Justifikasi</label>
          <textarea class="u-input" name="justification" rows="4" placeholder="Jelaskan alasan dan kebutuhan rekrutmen ini..."></textarea>
        </div>
      </form>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
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
      document.addEventListener('click', function(e) {
        if (e.target.matches('[data-modal-open]')) {
          const id = e.target.getAttribute('data-modal-open');
          const el = document.getElementById(id);
          if (el) { el.hidden = false; document.body.classList.add('modal-open'); }
        }
        if (e.target.matches('[data-modal-close]') || (e.target.closest && e.target.closest('[data-modal-close]'))) {
          const el = e.target.closest ? e.target.closest('.u-modal') : null;
          if (el) { el.hidden = true; document.body.classList.remove('modal-open'); }
        }
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const open = document.querySelector('.u-modal:not([hidden])');
          if (open) { open.hidden = true; document.body.classList.remove('modal-open'); }
        }
      });
    },
    initDT() {
      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        this.dt = jQuery('#ip-table').DataTable({
          responsive: true, paging: false, info: false,
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
