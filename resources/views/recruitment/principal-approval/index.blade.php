@extends('layouts.app')
@section('title','Izin Prinsip')

@section('content')
@php
  $me = auth()->user();
  $meUnit = $me?->unit_id;
@endphp

<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>
    @can('recruitment.update')
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createApprovalModal">
      <i class="fas fa-plus u-mr-xs"></i> Buat Permintaan
    </button>
    @endcan
  </div>

  {{-- Jika masih pakai session("ok"), tampilkan toast --}}
  @if(session('ok'))
    @push('swal')
      <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script>
    @endpush
  @endif

  <div class="dt-wrapper">
    <div class="u-scroll-x">
      <table id="ip-table" class="u-table">
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
              <span class="u-badge
                @if($r->status === 'rejected') u-badge--danger
                @elseif($r->status === 'draft') u-badge--warn
                @elseif($r->status === 'approved') u-badge--primary
                @else u-badge--soft @endif">
                {{ ucfirst($r->status) }}
              </span>
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
          <p class="u-text-xs u-muted u-mt-xs">Contoh: "Rekrutmen Software Engineer Q4 2024"</p>
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
          <p class="u-text-xs u-muted u-mt-xs">Berikan penjelasan mengapa posisi ini diperlukan</p>
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
    init: function() {
      this.bindModal();
      this.initDT();
      this.bindExternalSearch();
    },
    bindModal: function() {
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
    initDT: function() {
      // jQuery DataTables (prioritas) atau simple-DataTable
      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        this.dt = jQuery('#ip-table').DataTable({
          responsive: true, paging: false, info: false,
          language: {
            search: "Cari:", zeroRecords: "Tidak ada data",
            infoEmpty: "Menampilkan 0 data", infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
        return;
      }
      if (typeof window.DataTable !== 'undefined') {
        this.dt = new window.DataTable('#ip-table', { responsive: true, perPageSelect: false });
      }
    },
    bindExternalSearch: function() {
      const ext = document.querySelector('input[name="q"]');
      if (!ext) return;
      const self = this;
      let t = null;
      function run(v) {
        if (!self.dt) return;
        // jQuery DT
        if (self.dt.search && self.dt.draw) { self.dt.search(v).draw(); return; }
        // simple-DataTable
        if (typeof self.dt.search === 'function') self.dt.search(v);
      }
      ext.addEventListener('input', function(e) {
        clearTimeout(t);
        t = setTimeout(function(){ run(e.target.value || ''); }, 120);
      });
    }
  };
  page.init();
});
</script>
@endsection
