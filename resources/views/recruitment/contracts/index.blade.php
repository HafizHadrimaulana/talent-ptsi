@extends('layouts.app')
@section('title','Penerbitan Kontrak')

@section('content')
@php
  /** @var \App\Models\User $me */
  $me     = auth()->user();
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

  {{-- Jika masih pakai session("ok"), tampilkan sebagai toast --}}
  @if(session('ok'))
    @push('swal')
      <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script>
    @endpush
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class="fas fa-exclamation-circle u-error-icon"></i>
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
      <table id="contracts-table" class="u-table u-table-mobile">
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
              $sameUnit = $meUnit && (string)$meUnit === (string)$c->unit_id;
              $jenis    = $c->type ?? $c->contract_type ?? '—';
              $st       = $c->status;
              $badge    = in_array($st,['approved','signed']) ? 'u-badge--primary'
                         : ($st==='draft' ? 'u-badge--warn' : 'u-badge--glass');
            @endphp
            <tr>
              <td>
                <div class="u-flex u-items-center u-gap-sm">
                  <div class="u-badge u-badge--primary">
                    <i class="fas fa-file-contract u-text-xs u-mr-xs"></i>
                    {{ $c->number ?? '—' }}
                  </div>
                </div>
              </td>
              <td>
                <div class="u-flex u-items-center u-gap-sm">
                  <span class="u-font-medium">{{ $c->person_name }}</span>
                </div>
              </td>
              <td>{{ $c->position }}</td>
              <td><span class="u-badge u-badge--glass">{{ $jenis }}</span></td>
              <td><span class="u-badge {{ $badge }}">{{ ucfirst($st) }}</span></td>
              <td class="cell-actions">
                <div class="cell-actions__group">
                  {{-- SDM: submit draft -> review --}}
                  @if($c->status === 'draft' && $sameUnit)
                    @can('contract.update')
                    <form method="POST"
                          action="{{ route('recruitment.contracts.submit',$c) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Submit kontrak?"
                          data-confirm-text="Kontrak akan dikirim untuk review oleh atasan."
                          data-confirm-icon="question">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" title="Submit for review">
                        <i class="fas fa-paper-plane u-mr-xs"></i> Submit
                      </button>
                    </form>
                    @endcan
                  @endif

                  {{-- GM/VP: approve review --}}
                  @if($c->status === 'review' && $sameUnit)
                    @can('contract.approve')
                    <form method="POST"
                          action="{{ route('recruitment.contracts.approve',$c) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Setujui kontrak?"
                          data-confirm-text="Status akan berubah menjadi Approved."
                          data-confirm-icon="success">
                      @csrf
                      <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-success" title="Approve contract">
                        <i class="fas fa-check u-mr-xs"></i> Approve
                      </button>
                    </form>
                    @endcan
                  @endif

                  {{-- Tandai sudah ditandatangani --}}
                  @if($c->status === 'approved' && $sameUnit)
                    @can('contract.sign')
                    <form method="POST"
                          action="{{ route('recruitment.contracts.sign',$c) }}"
                          class="u-inline js-confirm"
                          data-confirm-title="Tandai sudah ditandatangani?"
                          data-confirm-text="Status akan berubah menjadi Signed."
                          data-confirm-icon="info">
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
    <div class="u-text-sm u-muted">Showing {{ $list->count() }} of {{ $list->total() }} contracts</div>
    <div>{{ $list->links() }}</div>
  </div>
</div>

{{-- Create Contract Modal --}}
<div id="createContractModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class="fas fa-file-contract"></i>
        </div>
        <div>
          <div class="u-title">Draft Kontrak Baru</div>
          <div class="u-muted u-text-sm">Buat kontrak baru untuk karyawan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class="fas fa-times"></i>
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
          <form method="POST"
                action="{{ route('recruitment.contracts.store') }}"
                class="u-grid-2 u-stack-mobile u-gap-md u-p-md"
                id="createContractForm">
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
          <i class="fas fa-save u-mr-xs"></i> Simpan Draft
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const app = {
    init() { this.bindModal(); this.bindTabs(); this.initDT(); this.bindExternalSearch(); },
    bindModal() {
      document.addEventListener('click', function(e) {
        if (e.target.matches('[data-modal-open]')) {
          const id = e.target.getAttribute('data-modal-open');
          const modal = document.getElementById(id);
          if (modal) { modal.hidden = false; document.body.classList.add('modal-open'); }
        }
        if (e.target.matches('[data-modal-close]') || (e.target.closest && e.target.closest('[data-modal-close]'))) {
          const modal = e.target.closest ? e.target.closest('.u-modal') : null;
          if (modal) { modal.hidden = true; document.body.classList.remove('modal-open'); }
        }
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const open = document.querySelector('.u-modal:not([hidden])');
          if (open) { open.hidden = true; document.body.classList.remove('modal-open'); }
        }
      });
    },
    bindTabs() { this.initTabs('createContractTabs'); },
    initTabs(containerId) {
      const tabs   = document.querySelectorAll('#' + containerId + ' .u-tab');
      const panels = document.querySelectorAll('#' + containerId + ' ~ .u-panels .u-panel');
      tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
          const target = tab.dataset.tab;
          tabs.forEach(function(t){ t.classList.remove('is-active'); });
          tab.classList.add('is-active');
          panels.forEach(function(p){ p.classList.toggle('is-active', p.id === 'tab-' + target); });
        });
      });
    },
    initDT() {
      // jQuery DataTables (jika ada) atau simple-DataTable
      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        jQuery('#contracts-table').DataTable({
          responsive: true, paging: false, info: false,
          language: {
            search: "Cari:", zeroRecords: "Tidak ada data kontrak yang ditemukan",
            infoEmpty: "Menampilkan 0 data", infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
        return;
      }
      if (typeof window.DataTable !== 'undefined') {
        // simple-datatables
        new window.DataTable('#contracts-table', {
          responsive: true,
          perPageSelect: false
        });
      }
    },
    bindExternalSearch() {
      // optional: input[name="q"] dari topbar layout; aman jika tidak ada
      const ext = document.querySelector('input[name="q"]');
      if (!ext) return;

      ext.addEventListener('input', function(){
        const tableEl = document.querySelector('#contracts-table');
        if (!tableEl) return;

        // jQuery DataTables
        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
          const dt = jQuery('#contracts-table').DataTable();
          dt.search(ext.value || '').draw();
          return;
        }

        // simple-datatables: cari input internal dan trigger input
        const wrapper = tableEl.closest ? tableEl.closest('.dataTable-wrapper') : null;
        const qs = wrapper ? wrapper.querySelector('.dataTable-input') : null;
        if (qs) {
          qs.value = ext.value || '';
          const evt = new Event('input', { bubbles: true });
          qs.dispatchEvent(evt);
        }
      });
    }
  };
  app.init();
});
</script>
@endsection
