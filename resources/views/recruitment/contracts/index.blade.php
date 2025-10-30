@extends('layouts.app')
@section('title','Penerbitan Kontrak')

@section('content')
@php
  /** @var \App\Models\User $me */
  $me = auth()->user();
  $meUnit = $me?->unit_id;
@endphp

<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Penerbitan Kontrak</h2>
    @can('contract.create')
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createContractModal">
      <i class="fas fa-plus u-mr-xs"></i> Draft Kontrak
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
      <table id="contracts-table" class="u-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Posisi</th>
            <th>Jenis</th>
            <th>Status</th>
            <th class="cell-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($list as $c)
          @php
            $sameUnit = $meUnit && $meUnit === $c->unit_id;
            $jenis = $c->type ?? $c->contract_type ?? '—';
          @endphp
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-badge u-badge--primary">
                  <i class='fas fa-file-contract u-text-xs u-mr-xs'></i>
                  {{ $c->number ?? '—' }}
                </div>
              </div>
            </td>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-avatar u-avatar--sm u-avatar--brand">
                  {{ substr($c->person_name, 0, 1) }}
                </div>
                <span class="u-font-medium">{{ $c->person_name }}</span>
              </div>
            </td>
            <td>{{ $c->position }}</td>
            <td>
              <span class="u-badge u-badge--glass">{{ $jenis }}</span>
            </td>
            <td>
              <span class="u-badge
                @if($c->status === 'rejected') u-badge--danger
                @elseif($c->status === 'draft') u-badge--warn
                @elseif($c->status === 'approved' || $c->status === 'signed') u-badge--success
                @else u-badge--soft @endif">
                {{ ucfirst($c->status) }}
              </span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                {{-- SDM submit draft -> review (unit sama + izin) --}}
                @if($c->status === 'draft' && $sameUnit)
                  @can('contract.update')
                  <form method="POST" action="{{ route('recruitment.contracts.submit',$c) }}" class="u-inline">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Submit for review">
                      <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                    </button>
                  </form>
                  @endcan
                @endif

                {{-- GM/VP approve review (unit sama + izin) --}}
                @if($c->status === 'review' && $sameUnit)
                  @can('contract.approve')
                  <form method="POST" action="{{ route('recruitment.contracts.approve',$c) }}" class="u-inline">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-success" title="Approve contract">
                      <i class="fas fa-check u-mr-xs"></i> Approve
                    </button>
                  </form>
                  @endcan
                @endif

                {{-- Mark signed (opsional role khusus) --}}
                @if($c->status === 'approved' && $sameUnit)
                  @can('contract.sign')
                  <form method="POST" action="{{ route('recruitment.contracts.sign',$c) }}" class="u-inline">
                    @csrf
                    <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Mark as signed">
                      <i class="fas fa-signature u-mr-xs"></i> Sign
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
      Showing {{ $list->count() }} of {{ $list->total() }} contracts
    </div>
    <div>{{ $list->links() }}</div>
  </div>
</div>

<!-- Create Contract Modal -->
<div id="createContractModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-file-contract'></i>
        </div>
        <div>
          <div class="u-title">Draft Kontrak Baru</div>
          <div class="u-muted u-text-sm">Buat kontrak baru untuk karyawan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="createContractTabs">
          <button class="u-tab is-active" data-tab="basic">Informasi Dasar</button>
          <button class="u-tab" data-tab="details">Detail Kontrak</button>
        </div>
      </div>

      <div class="u-panels">
        <div class="u-panel is-active" id="tab-basic">
          <form method="POST" action="{{ route('recruitment.contracts.store') }}" class="u-grid-2 u-stack-mobile u-gap-md u-p-md" id="createContractForm">
            @csrf
            <div class="u-space-y-sm">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Kontrak</label>
              <select name="type" class="u-input" required>
                <option value="SPK">SPK</option>
                <option value="PKWT">PKWT</option>
              </select>
            </div>

            <div class="u-space-y-sm">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Ambil dari Applicant</label>
              <select name="applicant_id" class="u-input">
                <option value="">— Pilih Applicant —</option>
                @foreach($applicants as $a)
                  <option value="{{ $a->id }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
                @endforeach
              </select>
            </div>

            <div class="u-space-y-sm">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">ID Employee (existing)</label>
              <input name="employee_id" class="u-input" placeholder="Isi untuk karyawan internal">
            </div>

            <div class="u-grid-col-span-2 u-space-y-sm">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Nama Lengkap</label>
              <input name="person_name" class="u-input" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="u-grid-col-span-2 u-space-y-sm">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi</label>
              <input name="position" class="u-input" placeholder="Masukkan posisi/jabatan" required>
            </div>
          </form>
        </div>

        <div class="u-panel" id="tab-details">
          <div class="u-p-md">
            <div class="u-grid-2 u-stack-mobile u-gap-md">
              <div class="u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Mulai</label>
                <input type="date" name="start_date" class="u-input" form="createContractForm">
              </div>

              <div class="u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Selesai</label>
                <input type="date" name="end_date" class="u-input" form="createContractForm">
              </div>

              <div class="u-grid-col-span-2 u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Gaji</label>
                <input type="number" step="0.01" name="salary" class="u-input" placeholder="Masukkan jumlah gaji" form="createContractForm">
                <p class="u-text-xs u-muted u-mt-xs">Isi dengan angka tanpa titik atau koma</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button form="createContractForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Simpan Draft
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Contract Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const contractManager = {
    init: function() {
      this.bindModalEvents();
      this.bindTabs();
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
    
    bindTabs: function() {
      // Initialize tabs for contract modal
      this.initTabs('createContractTabs');
    },
    
    initTabs: function(containerId) {
      const tabs = document.querySelectorAll(`#${containerId} .u-tab`);
      const panels = document.querySelectorAll(`#${containerId} ~ .u-panels .u-panel`);
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const targetTab = this.dataset.tab;
          
          // Update active tab
          tabs.forEach(t => t.classList.remove('is-active'));
          this.classList.add('is-active');
          
          // Show target panel
          panels.forEach(panel => {
            panel.classList.remove('is-active');
            if (panel.id === 'tab-' + targetTab) {
              panel.classList.add('is-active');
            }
          });
        });
      });
    },
    
    initDataTable: function() {
      if (typeof DataTable !== 'undefined') {
        new DataTable('#contracts-table', { 
          responsive: true, 
          paging: false, 
          info: false,
          language: {
            search: "Cari:",
            zeroRecords: "Tidak ada data kontrak yang ditemukan",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
      }
    }
  };
  
  contractManager.init();
});
</script>
@endsection