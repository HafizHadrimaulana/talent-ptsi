@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
@php
  use Illuminate\Support\Facades\DB;

  /** @var \App\Models\User $me */
  $me     = auth()->user();
  $meUnit = $me?->unit_id;

  // ===== Fallback canSeeAll & units bila controller belum provide =====
  $canSeeAll      = $canSeeAll      ?? false;
  $selectedUnitId = $selectedUnitId ?? null;
  $units          = $units          ?? collect();

  if ($canSeeAll === false) {
      $hasRoleAll = $me?->hasRole('Superadmin') || $me?->hasRole('DHC');

      // Optional: Head Office dianggap bisa lihat semua (meniru blade Kontrak yang kamu kirim)
      $hoUnitId = $hoUnitId ?? DB::table('units')->select('id')
          ->where(function ($q) {
              $q->where('code', 'HO')
                ->orWhere('code', 'HEADOFFICE')
                ->orWhere('name', 'SI Head Office')
                ->orWhere('name', 'Head Office')
                ->orWhere('name', 'LIKE', '%Head Office%');
          })
          ->value('id');

      $isHeadOfficeUser = $hoUnitId && $me?->unit_id == $hoUnitId;
      $canSeeAll = $hasRoleAll || $isHeadOfficeUser;
  }

  if ($units->isEmpty()) {
      $units = $canSeeAll
          ? DB::table('units')->select('id','name')->orderBy('name')->get()
          : DB::table('units')->select('id','name')->where('id',$meUnit)->get();
  }

  if (!$canSeeAll) {
      $selectedUnitId = (int) $meUnit;
  }
@endphp

<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>

    {{-- Toolbar filter unit --}}
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

    @can('recruitment.update')
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createApprovalModal">
      <i class="fas fa-plus u-mr-xs"></i> Buat Permintaan
    </button>
    @endcan
  </div>

  {{-- Toast OK --}}
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
          @php $sameUnit = $meUnit && (string)$meUnit === (string)$r->unit_id; @endphp
          <tr>
            <td><span class="u-font-medium">{{ $r->title }}</span></td>
            <td>{{ $r->position }}</td>
            <td><span class="u-badge u-badge--glass">{{ $r->headcount }} orang</span></td>
            <td>
              @php
                $st = $r->status;
                $badge = $st === 'rejected' ? 'u-badge--danger'
                       : ($st === 'draft' ? 'u-badge--warn'
                       : ($st === 'approved' ? 'u-badge--primary' : 'u-badge--soft'));
              @endphp
              <span class="u-badge {{ $badge }}">{{ ucfirst($st) }}</span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                {{-- SDM: submit DRAFT --}}
                @if($r->status === 'draft' && $sameUnit)
                  @can('recruitment.submit')
                  <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}"
                        class="u-inline js-confirm"
                        data-confirm-title="Submit permintaan?"
                        data-confirm-text="Permintaan akan dikirim ke VP/GM untuk persetujuan."
                        data-confirm-icon="question">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Submit for approval">
                      <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                    </button>
                  </form>
                  @endcan
                @endif

                {{-- GM/VP: approve/reject SUBMITTED --}}
                @if($r->status === 'submitted' && $sameUnit)
                  @can('recruitment.approve')
                  <form method="POST" action="{{ route('recruitment.principal-approval.approve',$r) }}"
                        class="u-inline js-confirm"
                        data-confirm-title="Setujui permintaan?"
                        data-confirm-text="Status akan berubah menjadi Approved."
                        data-confirm-icon="success">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-success" title="Approve">
                      <i class="fas fa-check u-mr-xs"></i> Approve
                    </button>
                  </form>

                  <form method="POST" action="{{ route('recruitment.principal-approval.reject',$r) }}"
                        class="u-inline js-confirm"
                        data-confirm-title="Tolak permintaan?"
                        data-confirm-text="Status akan berubah menjadi Rejected."
                        data-confirm-icon="error">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-danger" title="Reject">
                      <i class="fas fa-times u-mr-xs"></i> Reject
                    </button>
                  </form>
                  @endcan
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="u-flex u-items-center u-justify-between u-mt-lg">
    <div class="u-text-sm u-muted">
      Showing {{ $list->count() }} of {{ $list->total() }} requests
    </div>
    <div>{{ $list->links() }}</div>
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
      // jQuery DataTables (prioritas) atau simple-datatables
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
        // jQuery DT
        if (self.dt.search && self.dt.draw) { self.dt.search(v).draw(); return; }
        // simple-datatables
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
