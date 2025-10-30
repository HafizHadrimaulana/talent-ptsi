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

  @if(session('ok')) 
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='fas fa-check-circle u-success-icon'></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif
  
  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class='fas fa-exclamation-circle u-error-icon'></i>
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
          @php
            $sameUnit = $meUnit && $meUnit === $r->unit_id;
          @endphp
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-avatar u-avatar--sm u-avatar--brand">
                  <i class='fas fa-clipboard-list u-text-xs'></i>
                </div>
                <span class="u-font-medium">{{ $r->title }}</span>
              </div>
            </td>
            <td>{{ $r->position }}</td>
            <td>
              <span class="u-badge u-badge--glass">{{ $r->headcount }} orang</span>
            </td>
            <td>
              <span class="u-badge
                @if($r->status === 'rejected') u-badge--danger
                @elseif($r->status === 'draft') u-badge--warn
                @elseif($r->status === 'approved') u-badge--success
                @else u-badge--soft @endif">
                {{ ucfirst($r->status) }}
              </span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                {{-- SDM: submit DRAFT (unit sama + izin) --}}
                @if($r->status === 'draft' && $sameUnit)
                  @can('recruitment.submit')
                  <form method="POST" action="{{ route('recruitment.principal-approval.submit',$r) }}" class="u-inline">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Submit for approval">
                      <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                    </button>
                  </form>
                  @endcan
                @endif

                {{-- GM/VP: approve/reject SUBMITTED (unit sama + izin) --}}
                @if($r->status === 'submitted' && $sameUnit)
                  @can('recruitment.approve')
                  <form method="POST" action="{{ route('recruitment.principal-approval.approve',$r) }}" class="u-inline">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-success" title="Approve">
                      <i class="fas fa-check u-mr-xs"></i> Approve
                    </button>
                  </form>
                  <form method="POST" action="{{ route('recruitment.principal-approval.reject',$r) }}" class="u-inline">
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
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-clipboard-check'></i>
        </div>
        <div>
          <div class="u-title">Buat Izin Prinsip Baru</div>
          <div class="u-muted u-text-sm">Ajukan permintaan rekrutmen baru</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <form method="POST" action="{{ route('recruitment.principal-approval.store') }}" class="u-space-y-md u-p-md" id="createApprovalForm">
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
          <i class='fas fa-save u-mr-xs'></i> Simpan Draft
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Approval Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const approvalManager = {
    init: function() {
      this.bindModalEvents();
      this.initDataTable();
    },
    
    bindModalEvents: function() {
      // Modal open/close handlers
      document.addEventListener('click', function(e) {
        if (e.target.matches('[data-modal-open]')) {
          const modalId = e.target.getAttribute('data-modal-open');
          this.openModal(modalId);
        }
        
        if (e.target.matches('[data-modal-close]') || e.target.closest('[data-modal-close]')) {
          this.closeModal(e.target.closest('.u-modal'));
        }
      }.bind(this));

      // ESC key handler
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const openModal = document.querySelector('.u-modal:not([hidden])');
          if (openModal) {
            this.closeModal(openModal);
          }
        }
      }.bind(this));
    },
    
    openModal: function(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.hidden = false;
        document.body.classList.add('modal-open');
      }
    },
    
    closeModal: function(modal) {
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    },
    
    initDataTable: function() {
      if (typeof DataTable !== 'undefined') {
        new DataTable('#ip-table', { 
          responsive: true, 
          paging: false, 
          info: false,
          language: {
            search: "Cari:",
            zeroRecords: "Tidak ada data izin prinsip yang ditemukan",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
      }
    }
  };
  
  approvalManager.init();
});
</script>
@endsection