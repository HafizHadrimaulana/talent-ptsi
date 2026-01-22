@extends('layouts.app')
@section('title','Izin Prinsip')
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endpush
@section('content')
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Gate;
  use Illuminate\Support\Facades\Auth;
  $me       = auth()->user();
  $meUnit = $me ? $me->unit_id : null;
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
  $rkapList = isset($rkapList) ? $rkapList : collect([ (object)['name' => 'Reporter', 'rkap' => 5, 'existing' => 2],(object)['name' => 'KJJJ', 'rkap' => 3, 'existing' => 1],(object)['name' => 'Inspektor', 'rkap' => 4, 'existing' => 4],]);
  $positions = DB::table('positions')->select('id', 'name')->where('is_active',1)->orderBy('name')->get();
  try {
      $rawPics = DB::table('employees')->join('persons', 'employees.person_id', '=', 'persons.id')->select('employees.id', 'employees.employee_id', 'persons.full_name')->where('employees.unit_id', $selectedUnitId ?? $meUnit)->orderBy('persons.full_name')->get();
  } catch (\Exception $e) { $rawPics = collect(); }
  $picListFormatted = $rawPics->map(function($p) { return ['id' => $p->id, 'name' => ($p->employee_id ?? '-') . ' - ' . ($p->full_name ?? '-')]; })->values();
  $locationsJs = $locations
      ->unique('city')
      ->map(function($l) {
        return ['id' => $l->id, 'name' => $l->city];
    })->values();
@endphp
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Izin Prinsip</h2>
    <form method="get" class="u-flex u-gap-sm u-items-center">
      @if($canSeeAll)
        <label class="u-text-sm u-font-medium">Unit</label>
        <select name="unit_id" class="u-input">
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
  @if(session('ok')) @push('swal') <script>window.toastOk('Berhasil', {!! json_encode(session('ok')) !!});</script> @endpush @endif
  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <ul class="u-list">@foreach($errors->all() as $e)<li class="u-item">{{ $e }}</li>@endforeach</ul>
    </div>
  @endif
  <div class="u-flex u-justify-between u-items-center u-mb-md u-flex-wrap u-gap-md" style="margin-top: 20px;">
    <div class="u-flex u-gap-xs" style="background-color: var(--surface-1); padding: 4px; border-radius: 999px; border: 1px solid var(--border);">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'disetujui', 'page' => null]) }}" 
           class="u-btn {{ $currentTab === 'disetujui' ? 'u-btn--brand' : 'u-btn--ghost' }}"
           style="border-radius: 999px; min-width: 120px; justify-content: center; {{ $currentTab !== 'disetujui' ? 'color: var(--muted);' : '' }}">
           <i class="fas fa-check-circle u-mr-xs"></i> Selesai
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'berjalan', 'page' => null]) }}" 
           class="u-btn {{ $currentTab === 'berjalan' ? 'u-btn--brand' : 'u-btn--ghost' }}"
           style="border-radius: 999px; min-width: 120px; justify-content: center; {{ $currentTab !== 'berjalan' ? 'color: var(--muted);' : '' }}">
           <i class="fas fa-spinner u-mr-xs"></i> Berjalan
        </a>
    </div>
    <div class="u-flex u-gap-sm">
        <form method="GET" action="{{ route('recruitment.principal-approval.index') }}" class="u-relative">
            @foreach(request()->except(['q', 'page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
        </form>
    </div>
  </div>
  <div class="dt-wrapper">
    <div class="u-flex u-items-center u-justify-between u-mb-sm">
      <div class="u-font-semibold">Daftar Izin Prinsip</div>
      <span class="u-badge u-badge--glass">{{ $canSeeAll && !$selectedUnitId ? 'All units' : 'Unit ID: '.($selectedUnitId ?? $meUnit) }}</span>
    </div>
    <div class="u-scroll-x">
      <table id="ip-table" class="u-table nowrap" style="width:100%" data-dt>
        <thead>
          <tr><th>No Ticket</th><th>Judul</th><th>Unit</th><th>Jenis Permintaan</th><th>Posisi</th><th>HC</th><th>Jenis Kontrak</th><th>Progres</th><th>SLA</th><th class="cell-actions">Aksi</th></tr>
        </thead>
        <tbody>
          
        </tbody>
      </table>
    </div>
  </div>
</div>
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
    <div class="u-modal__body u-p-md">
      <form id="uraianForm" class="u-space-y-md">
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">1. Identitas Jabatan</h3>
            <div class="u-grid-2-custom">
                <div class="u-space-y-sm">
                    <div><label class="uj-label">Nama Jabatan</label><input type="text" class="u-input" id="uj_nama" placeholder="Otomatis dari Posisi/Job Function"></div>
                    <div><label class="uj-label">Unit Kerja (Divisi/UUS/Cabang)</label><input type="text" class="u-input" id="uj_unit" placeholder="Otomatis dari Unit User"></div>
                </div>
                <div class="u-space-y-sm">
                    <div><label class="uj-label">Melapor Pada</label><input type="text" class="u-input" id="uj_melapor" placeholder="Jabatan Atasan Langsung"></div>
                    <div><label class="uj-label">Pemangku</label><input type="text" class="u-input" id="uj_pemangku" placeholder="Nama Pemangku"></div>
                </div>
            </div>
        </div>
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">2. Tujuan Jabatan</h3>
            <div class="u-mb-xs u-text-xs u-muted">Kesimpulan dari akuntabilitas utama, dalam satu kalimat.</div>
            <textarea class="u-input" rows="3" id="uj_tujuan"></textarea>
        </div>
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">3. Akuntabilitas Utama</h3>
            <div class="u-mb-xs u-text-xs u-muted">Uraian tugas dan tujuan tugas (gunakan poin-poin).</div>
            <textarea class="u-input" rows="6" id="uj_akuntabilitas"></textarea>
        </div>
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
        <div class="u-card u-p-md">
            <h3 class="uj-section-title">5. Wewenang</h3>
            <div class="u-mb-xs u-text-xs u-muted">Suatu tugas yang dapat dilakukan pemangku jabatan tanpa meminta persetujuan atasan.</div>
            <textarea class="u-input" rows="4" id="uj_wewenang"></textarea>
        </div>
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
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Permintaan<span class="text-red-500">*</span></label>
          <select class="u-input" name="request_type"><option value="Rekrutmen">Rekrutmen</option><option value="Perpanjang Kontrak">Perpanjang Kontrak</option></select>
        </div>
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Jenis Kontrak<span class="text-red-500">*</span></label>
            <select class="u-input" id="contractTypeSelect" name="employment_type" required>
                <option value="">Pilih jenis kontrak</option>
                <option value="Organik">Organik</option>
                <option value="Project Based">Project Based</option>
                <option value="Kontrak MPS">Kontrak MPS</option>
                <option value="Kontrak On-call">Kontrak On-call</option>
                <option value="Alihdaya">Alihdaya</option>
            </select>
          </div>
          <div class="u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Sumber Anggaran<span class="text-red-500">*</span></label>
            <select class="u-input" id="budgetSourceSelect" name="budget_source_type" style="-webkit-appearance: none; -moz-appearance: none; appearance: none;"><option value="">Sumber anggaran</option><option value="RKAP">RKAP</option><option value="RAB Proyek">RAB Proyek</option><option value="Lainnya">Lainnya</option></select>
          </div>
        </div>
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Headcount<span class="text-red-500">*</span></label>
          <input class="u-input" type="number" min="1" name="headcount" id="headcountInput" value="1" placeholder="Jumlah orang" required>
        </div>
        <div id="dataTabsContainer" class="u-flex u-gap-sm u-flex-wrap u-mb-sm" style="display:none;"></div>
        <div id="dynamicContentWrapper" class="u-p-md u-border u-rounded u-bg-light">
            <div class="u-space-y-sm u-mb-md">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Judul Permintaan<span class="text-red-500">*</span></label>
              <input class="u-input" id="titleInput" name="title" placeholder="Mis. Rekrutmen Analis TKDN Proyek X" required>
            </div>
            <div id="projectSection" class="u-space-y-md" style="display:none;">
              <div class="u-flex u-items-center u-justify-between"><div><label class="u-block u-text-sm u-font-medium u-mb-sm">Data Project</label><div class="u-text-2xs u-muted">Pilih kode project, nama project akan otomatis terisi</div></div></div>
              <div class="u-grid-2 u-stack-mobile u-gap-md">
                <div class="u-space-y-sm" style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Kode Project / Nama Project</label>
                <input type="text" id="kodeProjectSearchInput" class="u-input" placeholder="Ketik Kode atau Nama Project..." autocomplete="off">
                <input type="hidden" id="kodeProjectInput" name="kode_project">                
                <div id="kodeProjectSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                </div>
                <div class="u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Nama Project<span class="text-red-500">*</span></label>
                <input class="u-input" id="namaProjectInput" name="nama_project" readonly placeholder="Nama project akan terisi otomatis">
                </div>
            </div>
              <div class="u-space-y-sm" style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi Jabatan<span class="text-red-500">*</span></label>
                <input type="text" id="positionSearchInput" name="position_text" class="u-input" placeholder="Ketik untuk mencari jabatan..." autocomplete="off">
                <input type="hidden" name="position" id="positionInput">
                <div id="positionSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
              </div>
              <div class="u-space-y-sm">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan<span class="text-red-500">*</span></label>
                <div class="u-flex u-items-center u-gap-sm">
                  <div id="uraianStatusProject" class="u-text-2xs u-muted">Belum ada uraian</div>
                  <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian-project">Isi Uraian</button>
                </div>
              </div>
              <div class="u-space-y-sm" style="position: relative;">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC<span class="text-red-500">*</span></label>
                <input type="text" id="picProjectSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
                <input type="hidden" name="pic_project" id="picProjectInput">
                <div id="picProjectSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
              </div>
            </div>
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
                    <label class="u-block u-text-sm u-font-medium u-mb-sm">Uraian Jabatan<span class="text-red-500">*</span></label>
                    <div class="u-flex u-items-center u-gap-sm">
                      <div id="uraianStatus" class="u-text-2xs u-muted">Belum ada uraian</div>
                      <button type="button" class="u-btn u-btn--sm u-btn--outline js-open-uraian">Isi Uraian</button>
                    </div>
                    <div style="position: relative;">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Posisi Jabatan<span class="text-red-500">*</span></label> 
                      <input type="text" id="positionOrganikSearchInput" class="u-input" placeholder="Cari atau ketik posisi jabatan..." autocomplete="off">
                      <input type="hidden" id="positionOrganikInput"> 
                      <div id="positionOrganikSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                    </div>  
                  </div>
                  <div>
                    <div style="position: relative;" class="u-mb-md">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">PIC<span class="text-red-500">*</span></label>
                      <input type="text" id="picOrganikSearchInput" class="u-input" placeholder="Cari PIC (ID / Nama)..." autocomplete="off">
                      <input type="hidden" name="pic" id="picOrganikInput">
                      <div id="picOrganikSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div id="extraDynamicFields" class="u-mt-md u-pt-md u-border-t">
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Mulai Kerja<span class="text-red-500">*</span></label>
                      <input class="u-input" type="date" id="dyn_start_date" required>
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tanggal Selesai Kerja<span class="text-red-500">*</span></label>
                      <input class="u-input" type="date" id="dyn_end_date" required>
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm" style="position: relative;">
                    <label class="u-block u-text-sm u-font-medium u-mb-sm">Kota Lokasi Penempatan Kerja<span class="text-red-500">*</span></label>
                    <input class="u-input" type="text" id="dyn_location" placeholder="Ketik atau Pilih Kota/Kabupaten" autocomplete="off">
                    <input type="hidden" id="dyn_location_id">
                    <div id="dynLocationSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Pendidikan<span class="text-red-500">*</span></label>
                      <select class="u-input" id="dyn_education" required>
                          <option value="">Pilih Pendidikan</option>
                          <option value="SMA">SMA</option>
                          <option value="D3">D3</option>
                          <option value="D4">D4</option>
                          <option value="S1">S1</option>
                          <option value="S2">S2</option>
                          <option value="S3">S3</option>
                      </select>
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-md">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Pelatihan</label>
                      <input class="u-input" type="text" id="dyn_brevet" placeholder="Masukkan pelatihan">
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Pengalaman<span class="text-red-500">*</span></label>
                      <input class="u-input" type="text" id="dyn_experience" placeholder="Masukkan pengalaman yang paling utama" required>
                  </div>
              </div>
              <div class="u-bg-light u-p-sm u-rounded u-font-bold u-text-sm u-mb-sm" style="color:#374151;"><b>Remunerasi</b></div>
              <div class="u-grid-2-custom u-mb-sm">
                <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Resiko Pekerjaan<span class="text-red-500">*</span></label>
                      <select class="u-input" id="dyn_resiko" required>
                          <option value="">Pilih Resiko</option>
                          <option value="Tinggi">Tinggi</option>
                          <option value="Rendah">Rendah</option>
                      </select>
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Gaji Pokok (Rp)<span class="text-red-500">*</span></label>
                      <input class="u-input input-currency" type="text" id="dyn_salary" placeholder="1.000.000" required>
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Terbilang</label>
                      <input class="u-input" type="text" id="dyn_terbilang" placeholder="NOL RUPIAH" readonly>
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tunjangan Jabatan (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_allowanceJ" placeholder="Masukkan angka">
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tunjangan Project (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_allowanceP" placeholder="Masukkan angka">
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tunjangan Kinerja (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_allowanceK" placeholder="Masukkan angka">
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Tunjangan Lainnya (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_allowanceL" placeholder="Masukkan angka">
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">BPJS Kesehatan (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_bpjs_kes" readonly placeholder="Autofill">
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">BPJS Ketenagakerjaan (Rp)</label>
                      <input class="u-input input-currency" type="text" id="dyn_bpjs_tk" readonly placeholder="Autofill">
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">THR (1x Gaji Pokok)</label>
                      <input class="u-input input-currency" type="text" id="dyn_thr" readonly placeholder="Autofill">
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Kompensasi (1x Gaji Pokok)</label>
                      <input class="u-input input-currency" type="text" id="dyn_kompensasi" readonly placeholder="Autofill">
                  </div>
              </div>
              <div class="u-grid-2-custom u-mb-sm">
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">Apakah sudah ada kandidat? (Jika ya, Upload CV)</label>
                      <input type="file" id="dyn_cv" class="u-input" accept=".pdf,.doc,.docx">
                      <div id="dyn_cv_preview_text" class="u-text-2xs u-text-brand u-mt-xs"></div>
                  </div>
                  <div class="u-space-y-sm">
                      <label class="u-block u-text-sm u-font-medium u-mb-sm">PPH 21</label>
                      <input class="u-input input-currency" type="text" id="dyn_pph21" readonly placeholder="0">
                  </div>
              </div>
          </div>
        </div>
        <div class="u-space-y-sm u-mt-md">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Detail Penjelasan Kebutuhan<span class="text-red-500">*</span></label>
            <textarea class="u-input" name="justification" rows="4" placeholder="Jelaskan secara detail..." required></textarea>
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
                <div class="u-card u-p-md">
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">No Ticket</div><div class="u-font-medium" id="view-ticket">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Judul</div><div class="u-font-medium" id="view-title">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Jenis Permintaan</div><div id="view-request-type">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Headcount</div><div id="view-headcount">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">PIC Request</div><div id="view-pic">-</div></div>
                    <div><div class="u-text-xs u-font-bold u-muted u-uppercase">Detail Penjelasan</div><div id="view-justification">-</div></div>
                    <div id="view-project-info-container" style="display:none; margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e5e7eb;">
                        <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-xs">Informasi Proyek</div>
                        <div class="u-bg-light u-p-sm u-rounded" style="font-size: 0.9rem;">
                            <div class="u-grid-2" style="grid-template-columns: 100px 1fr; gap: 4px;">
                                <div class="u-muted">Kode Project:</div>
                                <div class="u-font-medium u-text-dark" id="view-project-code">-</div>           
                                <div class="u-muted">Nama Project:</div>
                                <div class="u-font-medium u-text-dark" id="view-project-name">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="u-card u-p-md">
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
            <button type="button" class="u-btn u-btn--ghost" data-modal-close>Tutup</button>
            <button type="button" class="u-btn u-btn--info u-hover-lift js-btn-publish" style="display:none; background-color: #0ea5e9; border-color: #0ea5e9; color: white;">
                <i class="fas fa-bullhorn u-mr-xs"></i> Publikasikan
            </button>
            <form method="POST" action="" class="detail-reject-form u-inline" style="display:none;">
                @csrf
                <input type="hidden" name="note" class="reject-note-input" id="real_reject_note"> 
                <button type="button" class="u-btn u-btn--outline u-danger detail-reject-btn" 
                    onclick="triggerCustomConfirm(this, 'reject')">
                    <i class="fas fa-times u-mr-xs"></i> Reject
                </button>
            </form>
            <button type="button" class="u-btn u-btn--outline u-text-brand js-open-note-modal" style="display:none;" id="btn-add-note">
                <i class="fas fa-edit u-mr-xs"></i> Catatan
            </button>
            <form method="POST" action="" class="detail-approve-form u-inline" style="display:none;">
                @csrf
                <input type="hidden" name="extended_note" id="hidden_extended_note">
                <button type="button" class="u-btn u-btn--brand u-success detail-approve-btn" 
                    onclick="triggerCustomConfirm(this, 'approve')">
                    <i class="fas fa-check u-mr-xs"></i> Approve
                </button>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="confirmationModal" class="u-modal" style="z-index: 3000; display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background-color: rgba(0,0,0,0.6);">
    <div class="u-modal__card" style="width: 100%; max-width: 400px; background: white; border-radius: 12px; overflow: hidden; animation: u-slide-up 0.2s ease-out; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div class="u-p-lg u-text-center">
            <div class="u-mb-lg">
                <h3 id="conf-title" class="u-font-bold u-text-dark" style="font-size: 1.1rem; line-height: 1.5;">Apakah Anda yakin menyetujui izin prinsip ini?</h3>
            </div>
            <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 25px;">
                <button type="button" id="btn-conf-yes" class="u-btn" style="background-color: #22c55e; color: white; border: none; padding: 10px 35px; border-radius: 50px; font-weight: 600; min-width: 100px; cursor: pointer;">Ya</button>
                <button type="button" id="btn-conf-no" class="u-btn" style="background-color: #ef4444; color: white; border: none; padding: 10px 35px; border-radius: 50px; font-weight: 600; min-width: 100px; cursor: pointer;">Tidak</button>
            </div>
        </div>
    </div>
</div>

<div id="noteEditorModal" class="u-modal" hidden>
  <div class="u-modal__card" style="width: 700px; max-width: 95%;">
    <div class="u-modal__head">
      <div class="u-title"><i class="fas fa-sticky-note u-mr-xs"></i> Tambahkan Catatan</div>
      <button class="u-btn u-btn--ghost u-btn--sm js-close-note-modal"><i class="fas fa-times"></i></button>
    </div>
    <div class="u-modal__body u-p-md">
      <div class="u-mb-sm u-text-sm u-muted">Catatan ini akan tersimpan dan dapat dilihat oleh admin SDM Unit.</div>
      <textarea id="editorContent"></textarea>
    </div>
    <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
      <button type="button" class="u-btn u-btn--ghost js-close-note-modal">Batal</button>
      <button type="button" class="u-btn u-btn--brand js-save-note">Simpan Catatan</button>
    </div>
  </div>
</div>
<div id="createProjectModal" class="u-modal" style="z-index: 3050; display: none; align-items: center; justify-content: center; position: fixed; inset: 0;">
    <div class="u-modal__card" style="width: 100%; max-width: 600px; border-radius: 8px; overflow: hidden; animation: u-slide-up 0.2s ease-out;">
        <div class="u-modal__head">
            <div class="u-font-bold u-text-lg">Tambah Project Baru</div>
            <button type="button" class="u-btn u-btn--ghost u-btn--sm js-close-project-modal"><i class="fas fa-times"></i></button>
        </div>  
        <form id="formCreateProject" enctype="multipart/form-data">
            @csrf
            <div class="u-modal__body">
                <div class="u-space-y-sm">
                    <label class="u-label u-font-medium u-text-sm">Kode Project <span class="u-text-danger">*</span></label>
                    <input type="text" name="project_code" class="u-input" placeholder="Contoh: PRJ-2025-001" required>
                </div>  
                <div class="u-space-y-sm">
                    <label class="u-label u-font-medium u-text-sm">Nama Project <span class="u-text-danger">*</span></label>
                    <input type="text" name="project_name" class="u-input" placeholder="Contoh: Pembangunan Infrastruktur X" required>
                </div>
                <div class="u-space-y-sm" style="position: relative;">
                    <label class="u-label u-font-medium u-text-sm">Lokasi Project <span class="u-text-danger">*</span></label>
                    <input type="text" id="projectLocationSearchInput" class="u-input" placeholder="Ketik Kota atau Nama Lokasi..." autocomplete="off" required>
                    <input type="hidden" name="location_id" id="projectLocationInput">                    
                    <div id="projectLocationSearchResults" class="u-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 3100; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 4px;"></div>
                </div>
                <div class="u-space-y-sm">
                    <label class="u-label u-font-medium u-text-sm">Dokumen (Proposal/RAB) <span class="u-text-danger">*</span></label>
                    <div class="u-text-2xs u-muted u-mb-xs">Format: PDF, DOC, DOCX (Max 5MB)</div>
                    <input type="file" name="document" class="u-input" accept=".pdf,.doc,.docx" required>
                </div>
            </div>
            <div class="u-modal__foot u-p-md u-border-t u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost js-close-project-modal">Batal</button>
                <button type="submit" class="u-btn u-btn--brand" id="btnSaveProject"><i class="fas fa-save u-mr-xs"></i> Simpan Project</button>
            </div>
        </form>
    </div>
</div>
<div id="publishDescriptionModal" class="u-modal" hidden style="z-index: 2200;">
  <div class="u-modal__card" style="width: 900px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
    <div class="u-modal__head">
      <div class="u-title"><i class="fas fa-bullhorn u-mr-xs"></i> Publikasi Lowongan</div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close><i class="fas fa-times"></i></button>
    </div>
    <div class="u-modal__body u-p-md" style="overflow-y: auto;">
      <div class="u-alert u-alert--info u-mb-md">
        <i class="fas fa-info-circle u-mr-xs"></i> Masukkan detail lowongan yang akan tampil di portal pelamar.
      </div>
      <form id="publishForm">
          <input type="hidden" id="publish_req_id">
          <div class="u-grid-2-custom u-mb-md">
              <div>
                  <label class="u-label u-font-bold u-mb-xs">Tanggal Dibuka <span class="text-red-500">*</span></label>
                  <input type="date" id="publish_start_date" class="u-input" required>
              </div>
              <div>
                  <label class="u-label u-font-bold u-mb-xs">Tanggal Ditutup <span class="text-red-500">*</span></label>
                  <input type="date" id="publish_end_date" class="u-input" required>
              </div>
          </div>
          <div class="u-mb-md">
            <label class="u-label u-font-bold u-mb-xs">Lokasi Penempatan Kerja</label>
            <div class="u-text-xs u-muted u-mb-xs">Lokasi ini yang akan tampil di halaman pelamar.</div>
            <input type="text" id="publish_location" class="u-input" placeholder="Contoh: Jakarta Selatan, Site Balikpapan, dll...">
        </div>
          <div class="u-mb-md">
              <label class="u-label u-font-bold u-mb-xs">Deskripsi & Kualifikasi</label>
              <div style="color: #000;">
                 <textarea id="publishEditorContent"></textarea>
              </div>
          </div>
          <div id="previewArea" style="display: none; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; background: #f8fafc; margin-top: 20px;">
              <div class="u-text-center u-mb-md">
                  <span class="u-badge u-badge--warning">PREVIEW PUBLIKASI</span>
              </div>
              <h3 class="u-font-bold u-text-xl u-mb-xs" id="previewTitle">Posisi</h3>
              <div class="u-text-sm u-muted u-mb-md">
                  <span id="previewDates"></span> <br> <span id="previewLocation"></span>
              </div>
              <div id="previewContent" class="ck-content"></div>
          </div>
      </form>
    </div>
    <div class="u-modal__foot u-flex u-justify-between u-gap-sm">
      <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
      <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--outline" id="btnPreviewPublish">
            <i class="fas fa-eye u-mr-xs"></i> Preview
          </button>
          <button type="button" class="u-btn u-btn--brand" id="btnConfirmPublish">
            <i class="fas fa-paper-plane u-mr-xs"></i> Simpan & Publikasikan
          </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    function toggleHistoryNote(id) {
        const el = document.getElementById(id);
        if (el) {
            if (el.style.display === "none" || el.style.display === "") {
                el.style.display = "block";
                const icon = document.getElementById('icon-' + id);
                if(icon) icon.className = "fas fa-chevron-up u-mr-xs";
            } else {
                el.style.display = "none";
                const icon = document.getElementById('icon-' + id);
                if(icon) icon.className = "fas fa-chevron-down u-mr-xs";
            }
        }
    }
    function setBudgetLock(isLocked, budgetType) {
        const budgetSelect = document.getElementById('budgetSourceSelect');
        if (!budgetSelect) return;
        if (isLocked) {
            budgetSelect.value = budgetType;
            budgetSelect.classList.add('is-locked');
            budgetSelect.style.pointerEvents = 'none';
            budgetSelect.classList.add('u-muted');
        } else {
            budgetSelect.style.pointerEvents = 'auto';
            budgetSelect.classList.remove('is-locked');
            budgetSelect.classList.remove('u-muted');
            if (budgetType !== '') {
                budgetSelect.value = budgetType;
            }
        }
    }
    let formToSubmit = null;
    function triggerCustomConfirm(btn, action) {
        const modal = document.getElementById('confirmationModal');
        const titleEl = document.getElementById('conf-title');
        formToSubmit = btn.closest('form');
        const noteEditorValue = document.getElementById('hidden_extended_note').value;
        if(action === 'reject') {
            const rejectInput = document.getElementById('real_reject_note');
            if(rejectInput) rejectInput.value = noteEditorValue;
            titleEl.textContent = "Apakah Anda yakin menolak izin prinsip ini?";
        } else {
            titleEl.textContent = "Apakah Anda yakin menyetujui izin prinsip ini?";
        }
        modal.style.display = 'flex';
    }
    function terbilang(nilai) {
        nilai = Math.floor(Math.abs(nilai));
        var huruf = ['', 'SATU', 'DUA', 'TIGA', 'EMPAT', 'LIMA', 'ENAM', 'TUJUH', 'DELAPAN', 'SEMBILAN', 'SEPULUH', 'SEBELAS'];
        var temp = '';
        if (nilai < 12) {temp = ' ' + huruf[nilai];} 
        else if (nilai < 20) {temp = terbilang(nilai - 10) + ' BELAS ';} else if (nilai < 100) {temp = terbilang(Math.floor(nilai / 10)) + ' PULUH ' + terbilang(nilai % 10);} 
        else if (nilai < 200) {temp = ' SERATUS ' + terbilang(nilai - 100);} else if (nilai < 1000) {temp = terbilang(Math.floor(nilai / 100)) + ' RATUS ' + terbilang(nilai % 100);} 
        else if (nilai < 2000) {temp = ' SERIBU ' + terbilang(nilai - 1000);} else if (nilai < 1000000) {temp = terbilang(Math.floor(nilai / 1000)) + ' RIBU ' + terbilang(nilai % 1000);} 
        else if (nilai < 1000000000) {temp = terbilang(Math.floor(nilai / 1000000)) + ' JUTA ' + terbilang(nilai % 1000000);} 
        else if (nilai < 1000000000000) {temp = terbilang(Math.floor(nilai / 1000000000)) + ' MILIAR ' + terbilang(nilai % 1000000000);} 
        else if (nilai < 1000000000000000) {temp = terbilang(Math.floor(nilai / 1000000000000)) + ' TRILIUN ' + terbilang(nilai % 1000000000000);}
        return temp.trim();
    }
    document.addEventListener('DOMContentLoaded', function() {
        let publishEditor = null;
        if (document.querySelector('#publishEditorContent')) {
            ClassicEditor
                .create(document.querySelector('#publishEditorContent'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'undo', 'redo'],
                    placeholder: 'Kualifikasi, persyaratan, dan deskripsi pekerjaan...',
                })
                .then(editor => { publishEditor = editor; })
                .catch(error => { console.error(error); });
        }
        const btnPreviewPublish = document.getElementById('btnPreviewPublish');
        const previewArea = document.getElementById('previewArea');
        if(btnPreviewPublish) {
            btnPreviewPublish.addEventListener('click', function() {
                const content = publishEditor ? publishEditor.getData() : '';
                const startDate = document.getElementById('publish_start_date').value;
                const endDate = document.getElementById('publish_end_date').value;
                if(!content.trim() || !startDate || !endDate) {
                    alert('Mohon lengkapi Tanggal dan Deskripsi untuk melihat preview.');
                    return;
                }
                const reqId = document.getElementById('publish_req_id').value;
                const row = document.querySelector(`tr[data-recruitment-id="${reqId}"]`);
                const detailBtn = row ? row.querySelector('.js-open-detail') : null;
                const positionName = detailBtn ? detailBtn.getAttribute('data-position') : 'Posisi';
                document.getElementById('previewTitle').textContent = positionName;
                const d1 = new Date(startDate).toLocaleDateString('id-ID');
                const d2 = new Date(endDate).toLocaleDateString('id-ID');
                document.getElementById('previewDates').textContent = `Periode: ${d1} s/d ${d2}`;
                document.getElementById('previewLocation').textContent = `Lokasi: ${document.getElementById('publish_location').value}`;
                document.getElementById('previewContent').innerHTML = content;
                previewArea.style.display = 'block';
                previewArea.scrollIntoView({ behavior: 'smooth' });
            });
        }
        const btnConfirmPublish = document.getElementById('btnConfirmPublish');
        if(btnConfirmPublish) {
            btnConfirmPublish.addEventListener('click', function() {
                const reqId = document.getElementById('publish_req_id').value;
                const description = publishEditor ? publishEditor.getData() : '';
                const startDate = document.getElementById('publish_start_date').value;
                const endDate = document.getElementById('publish_end_date').value;
                const locationVal = document.getElementById('publish_location').value;
                if(!description.trim()) { alert('Deskripsi wajib diisi.'); return; }
                if(!startDate) { alert('Tanggal Dibuka wajib diisi.'); return; }
                if(!endDate) { alert('Tanggal Ditutup wajib diisi.'); return; }
                if(!locationVal.trim()) { alert('Lokasi Penempatan wajib diisi.'); return; }
                if(!confirm('Apakah Anda yakin ingin mempublikasikan lowongan ini?')) return;
                const btn = this;
                const originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Memproses...';
                fetch(`/recruitment/principal-approval/${reqId}/publish`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type': 'application/json'},
                    body: JSON.stringify({description: description, publish_start_date: startDate, publish_end_date: endDate, publish_location: locationVal})
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Gagal: ' + data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan sistem.');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                });
            });
        }
        const btnYes = document.getElementById('btn-conf-yes');
        if(btnYes) {
            btnYes.addEventListener('click', function() {
                if(formToSubmit) {
                    formToSubmit.submit();
                }
                document.getElementById('confirmationModal').style.display = 'none';
            });
        }
        const btnNo = document.getElementById('btn-conf-no');
        if(btnNo) {
            btnNo.addEventListener('click', function() {
                document.getElementById('confirmationModal').style.display = 'none';
                formToSubmit = null;
            });
        }
        const positionsData = {!! json_encode($positions) !!};
        const locationsData = {!! json_encode($locationsJs) !!};
        const picData       = {!! json_encode($picListFormatted) !!};
        const meUnitName    = {!! json_encode($meUnitName) !!}; 
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
        const page = {
            dt: null,
            init() { 
                this.bindModal(); 
                this.initDT(); 
                this.bindExternalSearch(); 
                const projectModal = document.getElementById('createProjectModal');
                const projectForm = document.getElementById('formCreateProject');
                const projectSelect = document.getElementById('kodeProjectSelect');
                const projectNameInput = document.getElementById('namaProjectInput');
                const locSearchInput = document.getElementById('projectLocationSearchInput');
                const locHiddenInput = document.getElementById('projectLocationInput');
                const locResultsContainer = document.getElementById('projectLocationSearchResults');
                setupSearchableDropdown(locSearchInput, locHiddenInput, locResultsContainer, locationsData, false);
                if (projectSelect) {
                    projectSelect.addEventListener('change', function() {
                        if (this.value === 'NEW') {
                            this.value = ""; 
                            if(projectNameInput) projectNameInput.value = "";
                            projectForm.reset();
                            if(locSearchInput) locSearchInput.value = "";
                            if(locHiddenInput) locHiddenInput.value = "";
                            projectModal.style.display = 'flex';
                        }
                    });
                }
                document.querySelectorAll('.js-close-project-modal').forEach(btn => {
                    btn.addEventListener('click', () => {
                        projectModal.style.display = 'none';
                    });
                });
                if (projectForm) {
                    projectForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const btnSave = document.getElementById('btnSaveProject');
                        const originalText = btnSave.innerHTML;
                        
                        btnSave.disabled = true;
                        btnSave.innerHTML = '<i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Menyimpan...';
                        
                        const formData = new FormData(this);
                        
                        fetch("{{ route('recruitment.project.store') }}", {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json' // Penting agar return JSON
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // 1. Tutup Modal
                                const projectModal = document.getElementById('createProjectModal');
                                if(projectModal) projectModal.style.display = 'none';

                                // 2. UPDATE UI (PERBAIKAN DISINI)
                                // Kita update Input Search & Hidden Input, bukan Select Option
                                const searchInput = document.getElementById('kodeProjectSearchInput');
                                const hiddenInput = document.getElementById('kodeProjectInput');
                                const namaInput   = document.getElementById('namaProjectInput');

                                if (data.data) {
                                    const newCode = data.data.project_code;
                                    const newName = data.data.project_name;

                                    // Isi kolom search dengan format "KODE - NAMA"
                                    if (searchInput) searchInput.value = newCode + ' - ' + newName;
                                    
                                    // Isi input hidden agar form utama bisa dikirim
                                    if (hiddenInput) hiddenInput.value = newCode;
                                    
                                    // Isi nama project readonly
                                    if (namaInput) namaInput.value = newName;
                                }

                                alert('Project berhasil dibuat dan dipilih!');
                                
                                // Reset form project untuk penggunaan berikutnya
                                this.reset();
                                
                            } else {
                                alert('Gagal menyimpan: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Alert ini hanya muncul jika benar-benar error koneksi atau server crash (500)
                            alert('Terjadi kesalahan sistem atau validasi.');
                        })
                        .finally(() => {
                            btnSave.disabled = false;
                            btnSave.innerHTML = originalText;
                        });
                    });
                }
            },
            bindModal() {
                const formatRupiahTyping = (angka, prefix) => {
                    let number_string = angka.replace(/[^,\d]/g, '').toString(),
                        split = number_string.split(','),
                        sisa = split[0].length % 3,
                        rupiah = split[0].substr(0, sisa),
                        ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                    if (ribuan) {
                        let separator = sisa ? '.' : '';
                        rupiah += separator + ribuan.join('.');
                    }
                    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
                    return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
                };
                const parseRupiah = (str) => {
                    if (!str) return 0;
                    return parseFloat(str.toString().replace(/\./g, '')) || 0;
                };
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
                const requestTypeSelect  = form.querySelector('[name="request_type"]');
                const titleInput         = form.querySelector('#titleInput');
                const targetStartInput   = form.querySelector('#targetStartInput'); 
                const justifInput        = form.querySelector('[name="justification"]');
                const dynInputs = {
                    start_date:  form.querySelector('#dyn_start_date'),
                    end_date:    form.querySelector('#dyn_end_date'),
                    location:    form.querySelector('#dyn_location'),
                    education:   form.querySelector('#dyn_education'),
                    brevet:      form.querySelector('#dyn_brevet'),
                    experience:  form.querySelector('#dyn_experience'),
                    resiko: form.querySelector('#dyn_resiko'),
                    salary:      form.querySelector('#dyn_salary'),
                    terbilang:   form.querySelector('#dyn_terbilang'),
                    allowanceJ:   form.querySelector('#dyn_allowanceJ'),
                    allowanceP:   form.querySelector('#dyn_allowanceP'),
                    allowanceL:   form.querySelector('#dyn_allowanceL'),
                    allowanceK:   form.querySelector('#dyn_allowanceK'),
                    pph21:       form.querySelector('#dyn_pph21'),
                    bpjs_kes:    form.querySelector('#dyn_bpjs_kes'),
                    bpjs_tk:     form.querySelector('#dyn_bpjs_tk'),
                    thr:         form.querySelector('#dyn_thr'),
                    kompensasi:  form.querySelector('#dyn_kompensasi'),
                    cv:          form.querySelector('#dyn_cv'),
                    cv_preview:  form.querySelector('#dyn_cv_preview_text')
                };
                const dynLocationId = document.getElementById('dyn_location_id');
                const dynLocationResults = document.getElementById('dynLocationSearchResults');
                setupSearchableDropdown(dynInputs.location, dynLocationId, dynLocationResults, locationsData, true);
                const currencyInputs = [dynInputs.salary, dynInputs.allowanceJ, dynInputs.allowanceP, dynInputs.allowanceL, dynInputs.allowanceK, dynInputs.thr, dynInputs.kompensasi, dynInputs.bpjs_kes, dynInputs.bpjs_tk, dynInputs.pph21];
                currencyInputs.forEach(input => {
                    if(input) {
                        input.addEventListener('input', function(e) {
                            this.value = formatRupiahTyping(this.value);
                        });
                    }
                });
                const calcTriggers = [ dynInputs.thr, dynInputs.kompensasi, dynInputs.start_date, dynInputs.end_date, dynInputs.resiko ];
                function calculateRemuneration() {
                    const salary = parseRupiah(dynInputs.salary ? dynInputs.salary.value : 0);
                    const start  = dynInputs.start_date ? dynInputs.start_date.value : '';
                    const end    = dynInputs.end_date ? dynInputs.end_date.value : '';
                    const riskVal = dynInputs.resiko ? dynInputs.resiko.value : 'Rendah';
                    if (salary <= 0 || !start || !end) return;
                    let valThr = parseRupiah(dynInputs.thr ? dynInputs.thr.value : 0);
                    let valKomp = parseRupiah(dynInputs.kompensasi ? dynInputs.kompensasi.value : 0);
                    if (salary > 0 && valThr === 0) {
                        valThr = salary;
                        if(dynInputs.thr) dynInputs.thr.value = formatRupiahTyping(salary.toString());
                    }
                    if (salary > 0 && valKomp === 0) {
                        valKomp = salary;
                        if(dynInputs.kompensasi) dynInputs.kompensasi.value = formatRupiahTyping(salary.toString());
                    }
                    const payload = {salary: salary, start_date: start, end_date: end, thr: valThr, kompensasi: valKomp, risk_level: riskVal, _token: '{{ csrf_token() }}'};
                    if(dynInputs.pph21) dynInputs.pph21.placeholder = "Menghitung...";
                    fetch("{{ route('api.calculate.salary') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("RESPONSE:", data);
                        if(dynInputs.pph21)   dynInputs.pph21.value   = formatRupiahTyping(data.pph21_bulanan.toString());
                        if(dynInputs.bpjs_kes) dynInputs.bpjs_kes.value = formatRupiahTyping(data.bpjs_kesehatan.toString());
                        if(dynInputs.bpjs_tk)  dynInputs.bpjs_tk.value  = formatRupiahTyping(data.bpjs_ketenagakerjaan.toString());
                    })
                    .catch(error => { console.error('Error calculating:', error); });
                }
                let calcTimeout;
                calcTriggers.forEach(input => {
                    if(input) {
                        if (input.tagName === 'SELECT' || input.type === 'date') {
                            input.addEventListener('change', calculateRemuneration);
                        } else {
                            input.addEventListener('keyup', function() {
                                clearTimeout(calcTimeout);
                                calcTimeout = setTimeout(calculateRemuneration, 800); 
                            });
                        }
                    }
                });
                if(dynInputs.salary) {
                    dynInputs.salary.addEventListener('keyup', function(e) { 
                        let rawVal = e.target.value;
                        e.target.value = formatRupiahTyping(rawVal);
                        const cleanVal = parseRupiah(rawVal);
                        const formattedVal = formatRupiahTyping(cleanVal.toString());
                        if(dynInputs.thr) dynInputs.thr.value = formattedVal;
                        if(dynInputs.kompensasi) dynInputs.kompensasi.value = formattedVal;
                        if(dynInputs.terbilang) {
                            if(cleanVal && !isNaN(cleanVal)) {
                                let text = terbilang(cleanVal) + ' RUPIAH';
                                text = text.charAt(0).toUpperCase() + text.slice(1);
                                dynInputs.terbilang.value = text;
                            } else {
                                dynInputs.terbilang.value = '';
                            }
                        }
                        clearTimeout(calcTimeout);
                        calcTimeout = setTimeout(calculateRemuneration, 800); 
                    });
                }
                if(dynInputs.cv) {
                    dynInputs.cv.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if(file) {
                            const reader = new FileReader();
                            reader.onload = function(evt) {
                                dynInputs.cv._base64 = evt.target.result;
                                dynInputs.cv._filename = file.name;
                                if(dynInputs.cv_preview) dynInputs.cv_preview.textContent = "File selected: " + file.name;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
                const uraianForm = document.getElementById('uraianForm');
                const ujInputs = {
                    nama: document.getElementById('uj_nama'),
                    unit: document.getElementById('uj_unit'),
                    pemangku: document.getElementById('uj_pemangku'),
                    melapor: document.getElementById('uj_melapor'),
                    tujuan: document.getElementById('uj_tujuan'),
                    akuntabilitas: document.getElementById('uj_akuntabilitas'),
                    dimensi_keuangan: document.getElementById('uj_dimensi_keuangan'),
                    anggaran: document.getElementById('uj_anggaran'),
                    dimensi_non_keuangan: document.getElementById('uj_dimensi_non_keuangan'),
                    bawahan_langsung: document.getElementById('uj_bawahan_langsung'),
                    total_staff: document.getElementById('uj_total_staff'),
                    total_pegawai: document.getElementById('uj_total_pegawai'),
                    wewenang: document.getElementById('uj_wewenang'),
                    hub_internal: document.getElementById('uj_hub_internal'),
                    hub_eksternal: document.getElementById('uj_hub_eksternal'),
                    spek_pendidikan: document.getElementById('uj_spek_pendidikan'),
                    spek_pengetahuan: document.getElementById('uj_spek_pengetahuan'),
                    spek_kompetensi: document.getElementById('uj_spek_kompetensi'),
                    spek_kompetensi_wajib: document.getElementById('uj_spek_kompetensi_wajib'),
                    spek_kompetensi_generik: document.getElementById('uj_spek_kompetensi_generik'),
                    struktur: document.getElementById('uj_struktur')
                };
                const btnPreviewPdf = document.getElementById('btnPreviewPdf');
                const uraianStatusDisplay = document.getElementById('uj_status_display');
                if(ujInputs.struktur) {
                    ujInputs.struktur.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        const previewDiv = document.getElementById('uj_struktur_preview');
                        if(file && file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(evt) {
                                ujInputs.struktur._base64 = evt.target.result;
                                previewDiv.innerHTML = `<img src="${evt.target.result}" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />`;
                            };
                            reader.readAsDataURL(file);
                        } else if(file) {
                            alert('Silakan pilih file gambar (JPG, PNG, dll)');
                            previewDiv.innerHTML = '';
                        }
                    });
                }
                const rkapSection       = form.querySelector('#rkapSection');
                const rkapSelectedInfo = form.querySelector('#rkapSelectedInfo');
                const rkapSelectedName = form.querySelector('#rkapSelectedName');
                const uraianStatus      = form.querySelector('#uraianStatus'); 
                const projectSection        = form.querySelector('#projectSection');
                const kodeProjectSelect     = form.querySelector('#kodeProjectSelect');
                const namaProjectInput      = form.querySelector('#namaProjectInput');
                const uraianStatusProject   = form.querySelector('#uraianStatusProject');
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
                let activeDataIndex = 1;
                let totalDataCount  = 1;
                let multiDataStore  = {}; 
                function getActiveContractType() { return contractTypeSelect ? contractTypeSelect.value : ''; }
                window.openUraianForm = function(currentData, mode) {
                    uraianForm.reset();
                    const d = currentData.uraian_data || {};
                    ujInputs.nama.value = d.nama || '';
                    ujInputs.unit.value = d.unit || '';
                    ujInputs.pemangku.value = d.pemangku || '';
                    ujInputs.melapor.value = d.melapor || '';
                    ujInputs.tujuan.value = d.tujuan || '';
                    ujInputs.akuntabilitas.value = d.akuntabilitas || '';
                    if(ujInputs.dimensi_keuangan) ujInputs.dimensi_keuangan.value = d.dimensi_keuangan || '';
                    if(ujInputs.anggaran) ujInputs.anggaran.value = d.anggaran || '';
                    if(ujInputs.dimensi_non_keuangan) ujInputs.dimensi_non_keuangan.value = d.dimensi_non_keuangan || '';
                    if(ujInputs.bawahan_langsung) ujInputs.bawahan_langsung.value = d.bawahan_langsung || '';
                    if(ujInputs.total_staff) ujInputs.total_staff.value = d.total_staff || '';
                    if(ujInputs.total_pegawai) ujInputs.total_pegawai.value = d.total_pegawai || '';
                    if(ujInputs.wewenang) ujInputs.wewenang.value = d.wewenang || '';
                    if(ujInputs.hub_internal) ujInputs.hub_internal.value = d.hub_internal || '';
                    if(ujInputs.hub_eksternal) ujInputs.hub_eksternal.value = d.hub_eksternal || '';
                    if(ujInputs.spek_pendidikan) ujInputs.spek_pendidikan.value = d.spek_pendidikan || '';
                    if(ujInputs.spek_pengetahuan) ujInputs.spek_pengetahuan.value = d.spek_pengetahuan || '';
                    if(ujInputs.spek_kompetensi) ujInputs.spek_kompetensi.value = d.spek_kompetensi || '';
                    if(ujInputs.spek_kompetensi_wajib) ujInputs.spek_kompetensi_wajib.value = d.spek_kompetensi_wajib || '';
                    if(ujInputs.spek_kompetensi_generik) ujInputs.spek_kompetensi_generik.value = d.spek_kompetensi_generik || '';
                    if(d.struktur_organisasi) {
                        const previewDiv = document.getElementById('uj_struktur_preview');
                        if(previewDiv) previewDiv.innerHTML = `<img src="${d.struktur_organisasi}" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />`;
                        if(ujInputs.struktur) ujInputs.struktur._base64 = d.struktur_organisasi;
                    }
                    if (!ujInputs.unit.value && meUnitName) ujInputs.unit.value = meUnitName;
                    if (!ujInputs.nama.value) {
                        if (mode === 'organik') {
                            const selectedRow = form.querySelector('.js-rkap-select.selected');
                            if(selectedRow) ujInputs.nama.value = selectedRow.closest('tr').dataset.jobName;
                            else if (positionOrganikSearchInput && positionOrganikSearchInput.value) ujInputs.nama.value = positionOrganikSearchInput.value;
                        } else if (mode === 'project') {
                            if(positionSearchInput && positionSearchInput.value) ujInputs.nama.value = positionSearchInput.value;
                        }
                    }
                    const status = currentData.uraian_status || 'Belum diisi';
                    uraianStatusDisplay.textContent = 'Status: ' + status;
                    if(status === 'Final' || status === 'Finalized') {
                        btnPreviewPdf.style.display = 'inline-flex';
                        btnPreviewPdf.dataset.json = JSON.stringify(d);
                    } else {
                        btnPreviewPdf.style.display = 'none';
                    }
                    uraianModal.hidden = false;
                    uraianModal.style.zIndex = '2000'; 
                    document.body.classList.add('modal-open');
                };
                document.addEventListener('click', function(e) {
                    if(e.target.classList.contains('js-save-uraian-form')) {
                        const status = e.target.getAttribute('data-status');
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
                        if(!multiDataStore[activeDataIndex]) multiDataStore[activeDataIndex] = {};
                        multiDataStore[activeDataIndex].uraian_data = dataObj;
                        multiDataStore[activeDataIndex].uraian_status = status; 
                        const type = getActiveContractType();
                        const textStatus = (status === 'Final') ? 'Tersimpan (Final)' : 'Tersimpan (Draft)';
                        const currentBudget = budgetSourceSelect ? budgetSourceSelect.value : '';
                        if(currentBudget === 'RKAP') { 
                            if(uraianStatus) uraianStatus.textContent = textStatus; 
                        } 
                        else { 
                            if(uraianStatusProject) uraianStatusProject.textContent = textStatus; 
                        }
                        if(status === 'Final') {
                            btnPreviewPdf.dataset.json = JSON.stringify(dataObj);
                            btnPreviewPdf.style.display = 'inline-flex';
                            uraianStatusDisplay.textContent = 'Status: Final';
                            alert('Data berhasil difinalisasi.');
                        } else {
                            uraianModal.hidden = true;
                        }
                    }
                });
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
                    btnPreviewPdf.addEventListener('click', function() { submitPdfForm(this.dataset.json); });
                }
                function resetDynamicInputs() {
                    const projectSearchInput = document.getElementById('kodeProjectSearchInput');
                    const projectHiddenInput = document.getElementById('kodeProjectInput');
                    const namaProjectInput   = document.getElementById('namaProjectInput');
                    if(titleInput) titleInput.value = '';
                    Object.values(dynInputs).forEach(el => {
                        if(el && (el.tagName === 'INPUT' || el.tagName === 'SELECT')) el.value = '';
                    });
                    if (dynLocationId) dynLocationId.value = '';
                    if(dynInputs.cv) { dynInputs.cv.value = ''; dynInputs.cv._base64 = null; dynInputs.cv._filename = null; }
                    if(dynInputs.cv_preview) dynInputs.cv_preview.textContent = '';
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
                    if(projectSearchInput) projectSearchInput.value = '';
                    if(projectHiddenInput) projectHiddenInput.value = '';
                    if(namaProjectInput)   namaProjectInput.value = '';
                    if(namaProjectInput) namaProjectInput.value = '';
                    if(positionSearchInput) positionSearchInput.value = '';
                    if(positionInput) positionInput.value = '';
                    if(uraianStatusProject) uraianStatusProject.textContent = 'Belum ada uraian';
                    if(picProjectInput) picProjectInput.value = '';
                    if(picProjectSearchInput) picProjectSearchInput.value = '';
                }
                function saveCurrentTabData() {
                    const idx = activeDataIndex;
                    if (!multiDataStore[idx]) multiDataStore[idx] = {};
                    if(titleInput) multiDataStore[idx].title = titleInput.value;
                    multiDataStore[idx].start_date = dynInputs.start_date?.value || '';
                    multiDataStore[idx].end_date    = dynInputs.end_date?.value || '';
                    multiDataStore[idx].location    = dynInputs.location?.value || '';
                    multiDataStore[idx].education   = dynInputs.education?.value || '';
                    multiDataStore[idx].brevet      = dynInputs.brevet?.value || '';
                    multiDataStore[idx].experience  = dynInputs.experience?.value || '';
                    multiDataStore[idx].resiko = dynInputs.resiko?.value || '';
                    multiDataStore[idx].salary      = dynInputs.salary?.value || '';
                    multiDataStore[idx].terbilang   = dynInputs.terbilang?.value || '';
                    multiDataStore[idx].allowanceJ  = dynInputs.allowanceJ?.value || '';
                    multiDataStore[idx].allowanceP  = dynInputs.allowanceP?.value || '';
                    multiDataStore[idx].allowanceL  = dynInputs.allowanceL?.value || '';
                    multiDataStore[idx].allowanceK  = dynInputs.allowanceK?.value || '';
                    multiDataStore[idx].pph21       = dynInputs.pph21?.value || '';
                    multiDataStore[idx].bpjs_kes    = dynInputs.bpjs_kes?.value || '';
                    multiDataStore[idx].bpjs_tk     = dynInputs.bpjs_tk?.value || '';
                    multiDataStore[idx].thr         = dynInputs.thr?.value || '';
                    multiDataStore[idx].kompensasi  = dynInputs.kompensasi?.value || '';
                    if(dynInputs.cv && dynInputs.cv._base64) {
                        multiDataStore[idx].cv_file = dynInputs.cv._base64;
                        multiDataStore[idx].cv_filename = dynInputs.cv._filename;
                    }
                    const isRkapVisible = rkapSection && rkapSection.style.display !== 'none';
                    const isProjectVisible = projectSection && projectSection.style.display !== 'none';
                    if (isRkapVisible) {
                        const selectedRow = form.querySelector('.js-rkap-select.selected');
                        const rkapJob = selectedRow ? selectedRow.closest('tr').dataset.jobName : null;
                        multiDataStore[idx].type = 'Organik';
                        multiDataStore[idx].rkap_job = rkapJob;
                        multiDataStore[idx].pic_id = picOrganikInput.value;
                        multiDataStore[idx].pic_text = picOrganikSearchInput.value;
                        multiDataStore[idx].position = positionOrganikInput.value; 
                        multiDataStore[idx].position_text = positionOrganikSearchInput.value;
                    } 
                    else if (isProjectVisible) {
                        const projectSearchInput = document.getElementById('kodeProjectSearchInput');
                        const projectHiddenInput = document.getElementById('kodeProjectInput');
                        const namaProjectInput   = document.getElementById('namaProjectInput');
                        const positionInput       = document.getElementById('positionInput');
                        const positionSearchInput = document.getElementById('positionSearchInput');
                        const picProjectInput     = document.getElementById('picProjectInput');
                        const picProjectSearch    = document.getElementById('picProjectSearchInput');
                        multiDataStore[idx].type = document.getElementById('contractTypeSelect').value;
                        multiDataStore[idx].project_code = projectHiddenInput ? projectHiddenInput.value : '';
                        multiDataStore[idx].project_name = namaProjectInput ? namaProjectInput.value : '';
                        multiDataStore[idx].project_search_text = projectSearchInput ? projectSearchInput.value : '';
                        multiDataStore[idx].position = positionInput ? positionInput.value : '';
                        multiDataStore[idx].position_text = positionSearchInput ? positionSearchInput.value : '';
                        multiDataStore[idx].pic_id = picProjectInput ? picProjectInput.value : '';
                        multiDataStore[idx].pic_text = picProjectSearch ? picProjectSearch.value : '';
                    }
                }
                function loadTabData(idx) {
                    resetDynamicInputs(); 
                    const data = multiDataStore[idx];
                    if (!data) return; 
                    if(data.title && titleInput) titleInput.value = data.title;
                    if(dynInputs.start_date) dynInputs.start_date.value = data.start_date || '';
                    if(dynInputs.end_date)   dynInputs.end_date.value   = data.end_date || '';
                    if(dynInputs.location)   dynInputs.location.value   = data.location || '';
                    if(dynInputs.education)  dynInputs.education.value  = data.education || '';
                    if(dynInputs.brevet)     dynInputs.brevet.value     = data.brevet || '';
                    if(dynInputs.experience) dynInputs.experience.value = data.experience || '';
                    if(dynInputs.resiko) dynInputs.resiko.value = data.resiko || '';
                    if(dynInputs.salary)     dynInputs.salary.value     = data.salary || '';
                    if(dynInputs.terbilang)  dynInputs.terbilang.value  = data.terbilang || '';
                    if(dynInputs.allowanceJ)  dynInputs.allowanceJ.value  = data.allowanceJ || '';
                    if(dynInputs.allowanceP)  dynInputs.allowanceP.value  = data.allowanceP || '';
                    if(dynInputs.allowanceL)  dynInputs.allowanceL.value  = data.allowanceL || '';
                    if(dynInputs.allowanceK)  dynInputs.allowanceK.value  = data.allowanceK || '';
                    if(dynInputs.pph21)      dynInputs.pph21.value      = data.pph21 || '';
                    if(dynInputs.bpjs_kes)   dynInputs.bpjs_kes.value   = data.bpjs_kes || '';
                    if(dynInputs.bpjs_tk)    dynInputs.bpjs_tk.value    = data.bpjs_tk || '';
                    if(dynInputs.thr)        dynInputs.thr.value        = data.thr || '';
                    if(dynInputs.kompensasi) dynInputs.kompensasi.value = data.kompensasi || '';
                    if(data.salary && dynInputs.terbilang) {
                        let numericSalary = parseRupiah(data.salary); 
                        if (!isNaN(numericSalary) && numericSalary !== 0) {
                            let text = terbilang(numericSalary) + ' RUPIAH';
                            text = text.charAt(0).toUpperCase() + text.slice(1);
                            dynInputs.terbilang.value = text;
                        } else {
                            dynInputs.terbilang.value = data.terbilang || ''; 
                        }
                    } else if (dynInputs.terbilang) {
                        dynInputs.terbilang.value = data.terbilang || '';
                    }
                    if(data.cv_filename && dynInputs.cv_preview) {
                        dynInputs.cv_preview.textContent = "Current File: " + data.cv_filename;
                        dynInputs.cv._base64 = data.cv_file;
                        dynInputs.cv._filename = data.cv_filename;
                    }
                    const type = getActiveContractType();
                    const statusText = (data.uraian_status === 'Final') ? 'Tersimpan (Final)' : (data.uraian_status === 'Draft' ? 'Tersimpan (Draft)' : 'Belum ada uraian');
                    const projectTypes = ['Project Based', 'Kontrak MPS', 'Kontrak On-call','Alihdaya'];
                    const currentType = document.getElementById('contractTypeSelect').value;
                    if (data.rkap_job || (type === 'Organik' && data.type === 'Organik') || (budgetSourceSelect && budgetSourceSelect.value === 'RKAP')) {
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
                    } else if (projectTypes.includes(type) || (data.type && projectTypes.includes(data.type))) {
                        const projectSearchInput = document.getElementById('kodeProjectSearchInput');
                        const projectHiddenInput = document.getElementById('kodeProjectInput');
                        const namaProjectInput   = document.getElementById('namaProjectInput');
                        const positionInput      = document.getElementById('positionInput');
                        const positionSearchInput= document.getElementById('positionSearchInput');
                        const picProjectInput    = document.getElementById('picProjectInput');
                        const picProjectSearch   = document.getElementById('picProjectSearchInput');
                        const uraianStatusProj   = document.getElementById('uraianStatusProject');
                            if(data.project_code) {
                                if(projectHiddenInput) projectHiddenInput.value = data.project_code;
                                if(namaProjectInput)   namaProjectInput.value   = data.project_name || ''; 
                                if(projectSearchInput) {
                                    if (data.project_search_text) {
                                        projectSearchInput.value = data.project_search_text;
                                    } else {
                                        projectSearchInput.value = `${data.project_code} - ${data.project_name || ''}`;
                                    }
                                }
                            }
                            if(data.position && positionInput) positionInput.value = data.position;
                            if(data.position_text && positionSearchInput) positionSearchInput.value = data.position_text;
                            if(uraianStatusProj) uraianStatusProj.textContent = statusText;
                            if(data.pic_id && picProjectInput) picProjectInput.value = data.pic_id;
                            if(data.pic_text && picProjectSearch) picProjectSearch.value = data.pic_text;
                    }
                }
                function renderTabs(count) {
                    dataTabsContainer.innerHTML = '';
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
                        if (activeDataIndex > totalDataCount) { activeDataIndex = 1; loadTabData(1); }
                        renderTabs(totalDataCount);
                    });
                }
                if (contractTypeSelect) {
                    contractTypeSelect.addEventListener('change', function() {
                    const val = this.value;
                    multiDataStore = {}; 
                    resetDynamicInputs();
                    activeDataIndex = 1;
                    renderTabs(totalDataCount); 
                    const projectTypes = ['Project Based', 'Kontrak MPS', 'Kontrak On-call'];
                    if (val === 'Organik') { setBudgetLock(true, 'RKAP'); }
                    else if (projectTypes.includes(val)) { setBudgetLock(true, 'RAB Proyek');}
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
                        payload.push(d);
                    }
                    detailsJsonInput.value = JSON.stringify(payload);
                });
                const btnAddNote       = document.getElementById('btn-add-note');
                const noteModal        = document.getElementById('noteEditorModal');
                const hiddenNoteInput  = document.getElementById('hidden_extended_note');
                const closeNoteBtns    = document.querySelectorAll('.js-close-note-modal');
                const saveNoteBtn      = document.querySelector('.js-save-note');
                let myNoteEditor = null;
                if (!myNoteEditor && document.querySelector('#editorContent')) {
                    ClassicEditor
                        .create(document.querySelector('#editorContent'), {
                            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'undo', 'redo' ],
                            placeholder: 'Tulis catatan persetujuan/penolakan di sini...'
                        })
                        .then(editor => { myNoteEditor = editor; })
                        .catch(error => { console.error(error); });
                }
                if(btnAddNote) {
                    btnAddNote.addEventListener('click', function() {
                        if(myNoteEditor) { myNoteEditor.setData(hiddenNoteInput.value || ''); }
                        noteModal.hidden = false;
                        noteModal.style.display = 'flex'; 
                    });
                }
                closeNoteBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        noteModal.hidden = true;
                        noteModal.style.display = 'none';
                    });
                });
                if(saveNoteBtn) {
                    saveNoteBtn.addEventListener('click', function() {
                        if(myNoteEditor) {
                            const content = myNoteEditor.getData();
                            hiddenNoteInput.value = content; 
                            const cleanText = content.replace(/<[^>]*>?/gm, '').trim(); 
                            if(cleanText !== '') {
                                btnAddNote.classList.remove('u-btn--outline');
                                btnAddNote.classList.add('u-btn--brand');
                                btnAddNote.innerHTML = '<i class="fas fa-check-circle u-mr-xs"></i> Catatan Tersimpan';
                            } else {
                                btnAddNote.classList.add('u-btn--outline');
                                btnAddNote.classList.remove('u-btn--brand');
                                btnAddNote.innerHTML = '<i class="fas fa-edit u-mr-xs"></i> Catatan';
                            }
                        }
                        noteModal.hidden = true;
                        noteModal.style.display = 'none';
                    });
                }
                document.addEventListener('click', function(e) {
                    const closeBtn = e.target.closest('[data-modal-close]');
                    const modalBackdrop = e.target.classList.contains('u-modal');
                    if (closeBtn || modalBackdrop) {
                        const m = e.target.closest('.u-modal');
                        if(m) { 
                            m.hidden = true; 
                            m.style.display = 'none';
                            document.body.classList.remove('modal-open'); 
                        }
                        return;
                    }
                    const btnCreate = e.target.closest('[data-modal-open="createApprovalModal"]');
                    if(btnCreate) {
                        e.preventDefault();
                        const m = document.getElementById('createApprovalModal');
                        const deleteForm = document.getElementById('deleteDraftForm');
                        
                        if(m) { 
                            // PERBAIKAN: Set display flex agar tampil
                            m.hidden = false; 
                            m.style.display = 'flex'; 
                            document.body.classList.add('modal-open'); 
                            
                            const mode = btnCreate.getAttribute('data-mode');
                            multiDataStore = {}; activeDataIndex = 1; totalDataCount = 1;            
                            
                            // Logic Create/Edit bawaan Anda tetap sama di bawah ini
                            if(mode === 'create') {
                                form.reset(); 
                                setBudgetLock(false, ''); 
                                if(modalTitle) modalTitle.textContent = "Buat Izin Prinsip Baru";
                                if(modalSubtitle) modalSubtitle.textContent = "Ajukan permintaan rekrutmen atau perpanjangan kontrak";
                                form.action = form.getAttribute('data-default-action'); 
                                if(deleteForm) deleteForm.style.display = 'none';
                                let methodField = form.querySelector('input[name="_method"]');
                                if (methodField) methodField.remove();
                                
                                // Reset manual inputs
                                [positionSearchInput, positionInput, positionOrganikSearchInput, positionOrganikInput, 
                                picProjectSearchInput, picProjectInput, picOrganikSearchInput, picOrganikInput].forEach(el => { if(el) el.value = ''; });
                                resetDynamicInputs();
                                renderTabs(1); 
                                loadTabData(1);
                                updateVisibility();
                            } else if (mode === 'edit') {
                                if(modalTitle) modalTitle.textContent = "Edit Izin Prinsip";
                                const updateUrl = btnCreate.getAttribute('data-update-url');
                                if(updateUrl) form.action = updateUrl;
                                const deleteUrl = btnCreate.getAttribute('data-delete-url');
                                if(deleteForm) { deleteForm.style.display = 'block'; deleteForm.action = deleteUrl || ''; }
                                let methodField = form.querySelector('input[name="_method"]');
                                if (!methodField) { methodField = document.createElement('input'); methodField.type = 'hidden'; methodField.name = '_method'; methodField.value = 'PUT'; form.appendChild(methodField); }
                                if(requestTypeSelect) requestTypeSelect.value = btnCreate.getAttribute('data-request-type');
                                if(titleInput) titleInput.value = btnCreate.getAttribute('data-title');
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
                                totalDataCount = parseInt(btnCreate.getAttribute('data-headcount')) || 1;
                                if(headcountInput) headcountInput.value = totalDataCount;
                                renderTabs(totalDataCount);
                                const metaJsonStr = btnCreate.getAttribute('data-meta-json');
                                if (metaJsonStr) {
                                    try {
                                        const detailsArray = JSON.parse(metaJsonStr);
                                        if (Array.isArray(detailsArray) && detailsArray.length > 0) {
                                            detailsArray.forEach((detail, idx) => { multiDataStore[idx + 1] = detail; });
                                        }
                                    } catch (e) { console.warn('Failed to parse meta-json:', e); }
                                }
                                activeDataIndex = 1;
                                loadTabData(1);
                            }
                        }
                    }
                    const btnDetail = e.target.closest('.js-open-detail');
                    if(btnDetail && detailModal) {
                        e.preventDefault();
                        const safeTxt = (attr) => btnDetail.getAttribute(attr) || '-';
                        const metaJsonStr = btnDetail.getAttribute('data-meta-json');
                        let detailsArray = [];
                        try { detailsArray = JSON.parse(metaJsonStr); } catch(e){}
                        if(!Array.isArray(detailsArray) || detailsArray.length===0) detailsArray = [{}];
                        const historyJson = btnDetail.getAttribute('data-history');
                        const canViewNotes = btnDetail.getAttribute('data-can-view-notes') === 'true';
                        let historyData = [];
                        try { historyData = JSON.parse(historyJson); } catch(e) {}
                        const setTxt = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '-'; };
                        setTxt('view-ticket', safeTxt('data-ticket-number'));
                        setTxt('view-status', safeTxt('data-status'));
                        setTxt('view-unit', safeTxt('data-unit'));
                        setTxt('view-request-type', safeTxt('data-request-type'));
                        setTxt('view-justification', safeTxt('data-justification'));
                        setTxt('view-budget-source', safeTxt('data-budget-source'));   
                        const projectContainer = document.getElementById('view-project-info-container');
                        const viewProjCode     = document.getElementById('view-project-code');
                        const viewProjName     = document.getElementById('view-project-name');
                        const firstDetail = (detailsArray && detailsArray.length > 0) ? detailsArray[0] : {};
                        if (firstDetail.project_code || firstDetail.project_name) {
                            projectContainer.style.display = 'block';
                            viewProjCode.textContent = firstDetail.project_code || '-';
                            viewProjName.textContent = firstDetail.project_name || '-';
                        } else {
                            projectContainer.style.display = 'none';
                        }
                        const tabsContainer = document.getElementById('detailTabsContainer');
                        tabsContainer.innerHTML = '';
                        const canApprove = btnDetail.getAttribute('data-can-approve') === 'true';
                        const approveUrl = btnDetail.getAttribute('data-approve-url');
                        const rejectUrl = btnDetail.getAttribute('data-reject-url');
                        const approveForm = detailModal.querySelector('.detail-approve-form');
                        const rejectForm = detailModal.querySelector('.detail-reject-form');
                        if(canApprove && approveForm && rejectForm) {
                            approveForm.style.display = 'block';
                            rejectForm.style.display = 'block';
                            if(approveForm.querySelector('button')) approveForm.querySelector('button').disabled = false;
                            if(rejectForm.querySelector('button')) rejectForm.querySelector('button').disabled = false;
                            if(approveUrl) approveForm.action = approveUrl;
                            if(rejectUrl) rejectForm.action = rejectUrl;
                            if(btnAddNote) btnAddNote.style.display = 'block';
                        } else {
                            if(approveForm) approveForm.style.display = 'none';
                            if(rejectForm) rejectForm.style.display = 'none';
                            if(btnAddNote) btnAddNote.style.display = 'none';
                        }
                        const renderContent = (index) => {
                            const data = detailsArray[index];
                            const globalTitle = safeTxt('data-title');
                            setTxt('view-title', data.title || globalTitle);
                            setTxt('view-position', data.position_text || safeTxt('data-position'));
                            setTxt('view-headcount', '1 Orang');
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
                            const container = document.getElementById('detailContentContainer');
                            let extraDetailDiv = document.getElementById('view-extra-details');
                            if(!extraDetailDiv) {
                                extraDetailDiv = document.createElement('div');
                                extraDetailDiv.id = 'view-extra-details';
                                extraDetailDiv.style.marginTop = '24px';
                                container.appendChild(extraDetailDiv);
                            } else {
                                if (extraDetailDiv.parentNode !== container) {
                                    container.appendChild(extraDetailDiv);
                                }
                                extraDetailDiv.style.marginTop = '24px';
                            }
                            const makeRow = (lbl, val, isBold = false, isGrand = false) => {
                                let styleVal = isBold ? 'font-weight: 800; color: #111827;' : 'font-medium';
                                let styleLbl = isBold ? 'font-weight: 700; color: #374151;' : 'font-bold u-text-muted';
                                let borderStyle = 'border-bottom: 1px solid #f3f4f6;';
                                if (isGrand) {
                                    styleVal = 'font-weight: 900; color: #059669; font-size: 1.1em;'; 
                                    styleLbl = 'font-weight: 800; color: #065f46; font-size: 1.0em;';
                                    borderStyle = 'border-top: 2px solid #059669; padding-top: 8px; margin-top: 8px;';
                                }
                                return `<div class="u-flex u-justify-between u-mb-xs u-pb-xs" style="${borderStyle}">
                                            <span class="u-text-xs u-uppercase ${styleLbl}">${lbl}</span>
                                            <span class="u-text-sm u-text-right ${styleVal}">${val || '-'}</span>
                                        </div>`;
                            };
                            const formatRp = (val) => {
                                if (!val) return '-';
                                let cleanVal = val.toString().replace(/\./g, '');
                                let num = parseFloat(cleanVal);
                                if (isNaN(num)) return '-';
                                num = Math.floor(num);
                                return 'Rp ' + num.toLocaleString('id-ID'); 
                            };
                            const parseVal = (v) => {
                                if (!v) return 0;
                                if (typeof v === 'number') return v;
                                return parseFloat(v.toString().replace(/\./g, '')) || 0;
                            };

                            const thrPerBulan = Math.floor(parseVal(data.thr) / 12);
                            const kompensasiPerBulan = Math.floor(parseVal(data.kompensasi) / 12);
                            const totalAnggaranPerBulan = 
                                parseVal(data.salary) + 
                                parseVal(data.allowanceJ) + 
                                parseVal(data.allowanceP) + 
                                parseVal(data.allowanceC) + 
                                parseVal(data.allowanceK) + 
                                thrPerBulan +
                                kompensasiPerBulan + 
                                parseVal(data.bpjs_tk) + 
                                parseVal(data.bpjs_kes) + 
                                parseVal(data.pph21);
                            let duration = 1; 
                            if (data.start_date && data.end_date) {
                                const d1 = new Date(data.start_date);
                                const d2 = new Date(data.end_date);
                                d2.setDate(d2.getDate() + 1);
                                let months = (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth());
                                if (d2.getDate() < d1.getDate()) months--;
                                duration = months > 0 ? months : 1;
                            }
                            const grandTotal = totalAnggaranPerBulan * duration;
                            let historyHtml = '';
                            if (historyData.length > 0) {
                                historyHtml += `<div class="u-mt-lg u-pt-md u-border-t">`;
                                historyHtml += `<div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-md" style="letter-spacing: 0.05em;">Riwayat Persetujuan</div>`;
                                historyHtml += `<div class="u-space-y-sm">`; 
                                console.log("DEBUG HISTORY:", {
                                    roleSaya: "{{ $me->getRoleNames()->first() ?? 'User' }}", 
                                    bisaLihatCatatan: canViewNotes, 
                                    dataHistory: historyData
                                });
                                historyData.forEach((h, index) => {
                                    let badgeClass = 'u-badge--subtle';
                                    let icon = '<i class="fas fa-circle-notch fa-spin"></i>';
                                    if(h.status === 'approved') { badgeClass = 'u-badge--success'; icon = '<i class="fas fa-check-circle"></i>'; } 
                                    else if(h.status === 'rejected') { badgeClass = 'u-badge--danger'; icon = '<i class="fas fa-times-circle"></i>'; } 
                                    else { icon = '<i class="far fa-clock"></i>'; }
                                    let contentNote = (h.note && h.note !== 'null') ? h.note.trim() : '';
                                    let hasNote = (canViewNotes && contentNote.length > 0);
                                    let noteId = `history-note-${index}`; 
                                    let iconId = `icon-history-note-${index}`;
                                    let cursorStyle = hasNote ? 'cursor: pointer;' : '';
                                    let clickAttr = hasNote ? `onclick="toggleHistoryNote('${noteId}')"` : '';
                                    let noteIndicator = hasNote 
                                        ? `<div class="u-text-2xs u-text-brand u-mt-xxs u-font-medium"><i id="${iconId}" class="fas fa-chevron-down u-mr-xs"></i> Lihat Catatan</div>` 
                                        : '';
                                    historyHtml += `
                                    <div class="u-card u-p-sm u-bg-white u-border u-mb-sm u-hover-lift" style="${cursorStyle}" ${clickAttr} title="${hasNote ? 'Klik untuk melihat catatan' : ''}">
                                        <div class="u-flex u-justify-between u-items-center">
                                            <div>
                                                <div class="u-font-bold u-text-sm u-text-dark">${h.role}</div>
                                                <div class="u-text-2xs u-muted"><i class="far fa-calendar-alt"></i> ${h.date}</div>
                                            </div>
                                            <div class="u-text-right">
                                                <div class="u-badge ${badgeClass} u-text-2xs">${icon} ${h.status.toUpperCase()}</div>
                                                ${noteIndicator}
                                            </div>
                                        </div>
                                        ${ hasNote ? 
                                            `<div id="${noteId}" style="display: none;" class="u-bg-light u-p-sm u-rounded u-text-sm u-mt-sm u-animate-fade-in" style="border-left: 3px solid #3b82f6;">
                                                <div class="u-text-xs u-font-bold u-text-muted u-mb-xxs u-uppercase">Isi Catatan:</div>
                                                <div class="ck-content" style="font-size: 0.9em;">${h.note}</div>
                                            </div>` : '' 
                                        }
                                    </div>
                                    `;
                                });
                                historyHtml += `</div></div>`;
                            }
                            extraDetailDiv.innerHTML = `
                                <div class="u-card u-p-md">
                                    <div class="u-text-xs u-font-bold u-muted u-uppercase u-mb-md" style="letter-spacing: 0.05em; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">
                                        Detail Kandidat & Remunerasi
                                    </div>
                                    <div class="u-grid-2 u-stack-mobile u-gap-lg">
                                    <div>
                                        ${makeRow('Mulai Kerja', data.start_date)}
                                        ${makeRow('Selesai Kerja', data.end_date)}
                                        ${makeRow('Durasi Kontrak', duration + ' Bulan')} 
                                        ${makeRow('Lokasi', data.location)}
                                        ${makeRow('Pendidikan', data.education)}
                                        ${makeRow('Pelatihan', data.brevet)}
                                        ${makeRow('Pengalaman', data.experience)}
                                        ${makeRow('Resiko Pekerjaan', data.resiko)}         
                                        <div class="u-mt-sm u-flex u-justify-between u-items-center">
                                            <span class="u-text-muted u-text-xs u-uppercase u-font-bold">CV KANDIDAT</span> 
                                            ${data.cv_filename ? `<a href="${data.cv_file}" download="${data.cv_filename}" class="u-text-brand u-text-sm u-font-medium hover:u-underline"><i class="fas fa-download u-mr-xs"></i> ${data.cv_filename}</a>` : '<span class="u-text-sm">-</span>'}
                                        </div>
                                    </div>
                                    <div> 
                                        ${makeRow('Gaji Pokok', formatRp(data.salary))}
                                        ${makeRow('Tunjangan Jabatan', formatRp(data.allowanceJ))}
                                        ${makeRow('Tunjangan Project', formatRp(data.allowanceP))}
                                        ${makeRow('Tunjangan Kinerja', formatRp(data.allowanceK))}
                                        ${makeRow('Tunjangan Lainnya', formatRp(data.allowanceL))}
                                        ${makeRow('THR (Per Bulan)', formatRp(thrPerBulan))}
                                        ${makeRow('Kompensasi (Per Bulan)', formatRp(kompensasiPerBulan))}
                                        ${makeRow('BPJS Ketenagakerjaan', formatRp(data.bpjs_tk))}
                                        ${makeRow('BPJS Kesehatan', formatRp(data.bpjs_kes))}
                                        ${makeRow('PPh 21', formatRp(data.pph21))}
                                        <div style="margin-top: 12px; padding-top: 8px; border-top: 2px dashed #d1d5db;">
                                            ${makeRow('TOTAL ANGGARAN (PER BULAN)', formatRp(totalAnggaranPerBulan), true)}
                                        </div>
                                        ${makeRow(`TOTAL SELURUHNYA (${duration} BULAN)`, formatRp(grandTotal), false, true)}
                                    </div>
                                    </div>
                                </div>
                                ${historyHtml}
                            `;
                        };
                        detailsArray.forEach((item, i) => {
                            const btnTab = document.createElement('button');
                            btnTab.type = 'button';
                            const initialStyle = (i === 0) ? 'u-btn--brand u-text-white' : 'u-btn--ghost';
                            btnTab.className = `u-btn u-btn--sm ${initialStyle} u-hover-lift`;
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
                        renderContent(0);
                        detailModal.hidden = false; 
                        detailModal.style.display = 'flex';
                        document.body.classList.add('modal-open');
                        const isPublished = safeTxt('data-is-published') === '1';
                        const canPublishUser = btnDetail.getAttribute('data-can-publish') === 'true';
                        const statusTiket = safeTxt('data-status').toLowerCase();
                        const noTicket = safeTxt('data-ticket-number');
                        const reqId = btnDetail.getAttribute('data-id');
                        const btnPublish = detailModal.querySelector('.js-btn-publish');
                        if (btnPublish) {
                            btnPublish.style.display = 'none';
                            btnPublish.disabled = false;
                            btnPublish.innerHTML = '<i class="fas fa-bullhorn u-mr-xs"></i> Publikasikan';
                            btnPublish.classList.add('u-btn--info');
                            btnPublish.classList.remove('u-btn--success');
                            btnPublish.onclick = null;
                            if (canPublishUser && statusTiket === 'approved' && noTicket && noTicket !== '-') {
                                if (isPublished) {
                                    btnPublish.style.display = 'inline-flex';
                                    btnPublish.disabled = true;
                                    btnPublish.innerHTML = '<i class="fas fa-check u-mr-xs"></i> Terpublikasi';
                                    btnPublish.classList.remove('u-btn--info');
                                    btnPublish.classList.add('u-btn--success');
                                } else {
                                    btnPublish.style.display = 'inline-flex';
                                    btnPublish.onclick = function() {
                                        document.getElementById('publish_req_id').value = reqId;
                                        const locInput = document.getElementById('publish_location');
                                        const savedLoc = this.getAttribute('data-default-location');
                                        locInput.value = savedLoc || ''; 
                                        if(publishEditor) publishEditor.setData(''); 
                                        const pubModal = document.getElementById('publishDescriptionModal');
                                        pubModal.hidden = false;
                                        pubModal.style.display = 'flex';
                                        document.getElementById('detailApprovalModal').hidden = true; 
                                    };
                                }
                            }
                            let defaultLocation = '';
                            if (detailsArray && detailsArray.length > 0 && detailsArray[0].location) {
                                defaultLocation = detailsArray[0].location;
                            }
                            btnPublish.setAttribute('data-default-location', defaultLocation);
                        }

                        
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
                setupSearchableDropdown(positionSearchInput, positionInput, positionSearchResults, positionsData, true);
                setupSearchableDropdown(positionOrganikSearchInput, positionOrganikInput, positionOrganikSearchResults, positionsData, true);
                setupSearchableDropdown(picProjectSearchInput, picProjectInput, picProjectResults, picData, false);
                setupSearchableDropdown(picOrganikSearchInput, picOrganikInput, picOrganikResults, picData, false);
                const updateVisibility = () => { 
                    const budget = budgetSourceSelect ? budgetSourceSelect.value : '';
                    const isRkap = (budget === 'RKAP');
                    const isProjectRab = (budget === 'RAB Proyek');
                    if (rkapSection) rkapSection.style.display = isRkap ? 'block' : 'none';
                    if (!isRkap && rkapSelectedInfo) rkapSelectedInfo.style.display = 'none'; 
                    if (projectSection) projectSection.style.display = isProjectRab ? 'block' : 'none';
                    if(positionInput) positionInput.removeAttribute('name');
                    if(positionOrganikInput) positionOrganikInput.removeAttribute('name');
                    if (isRkap) {
                        if(positionOrganikInput) positionOrganikInput.setAttribute('name', 'position'); 
                        const selectedRow = form.querySelector('.js-rkap-select.selected');
                        if(selectedRow && positionOrganikInput) {
                            positionOrganikInput.value = selectedRow.closest('tr').dataset.jobName;
                        }
                    } else if (isProjectRab) { 
                        if(positionInput) positionInput.setAttribute('name', 'position'); 
                    } 
                };
                if (budgetSourceSelect) {
                    budgetSourceSelect.addEventListener('change', function() {
                        resetDynamicInputs(); 
                        updateVisibility();
                    });
                }
                if (contractTypeSelect) {
                    contractTypeSelect.addEventListener('change', function() {
                        const val = this.value;
                        const projectTypes = ['Project Based', 'Kontrak MPS', 'Kontrak On-call'];
                        multiDataStore = {}; 
                        resetDynamicInputs();
                        activeDataIndex = 1;
                        renderTabs(totalDataCount); 
                        if (val === 'Organik') {
                            if(budgetSourceSelect) budgetSourceSelect.value = 'RKAP';
                        } else if (projectTypes.includes(val)) {
                            if(budgetSourceSelect) budgetSourceSelect.value = 'RAB Proyek';
                        } else {
                            if(budgetSourceSelect) budgetSourceSelect.value = '';
                        }
                        updateVisibility();
                    });
                }
                if (kodeProjectSelect && namaProjectInput) { 
                    kodeProjectSelect.addEventListener('change', function() { 
                        const selectedOption = this.options[this.selectedIndex]; 
                        const nama = selectedOption.getAttribute('data-nama'); 
                        namaProjectInput.value = nama ? nama : ''; 
                    }); 
                }
                updateVisibility();
            }, 
            initDT() { 
                setTimeout(() => {
                    const selectElement = document.getElementById('dt-length-0');
                    if (selectElement && !document.getElementById('btn-export-excel')) {
                        const container = selectElement.parentNode; 
                        const currentParams = new URLSearchParams(window.location.search);
                        const exportUrl = "{{ route('recruitment.principal-approval.export') }}?" + currentParams.toString();
                        const exportBtn = document.createElement('a');
                        exportBtn.id = 'btn-export-excel';
                        exportBtn.href = exportUrl;
                        exportBtn.target = '_blank';
                        exportBtn.className = 'u-btn u-btn--brand u-btn--sm';
                        exportBtn.style.marginLeft = '12px';
                        exportBtn.style.display = 'inline-flex';
                        exportBtn.style.alignItems = 'center';
                        exportBtn.style.textDecoration = 'none';
                        exportBtn.style.height = '32px';
                        exportBtn.innerHTML = '<i class="fas fa-file-excel u-mr-xs"></i> Export Excel';
                        container.style.display = 'flex';
                        container.style.alignItems = 'center';
                        container.appendChild(exportBtn);
                    }
                }, 800);
                this.dt = window.initDataTables('#ip-table', {
                    // AKTIFKAN Server Side AJAX
                    serverSide: true,
                    processing: true,
                    
                    // URL endpoint (ke method index controller yg sama)
                    ajax: {
                        url: "{{ route('recruitment.principal-approval.index') }}",
                        data: function(d) {
                            // Kirim parameter filter tambahan ke controller
                            // Pastikan input select filter di HTML punya ID yang sesuai
                            const unitSelect = document.querySelector('select[name="unit_id"]');
                            const tabActive = new URLSearchParams(window.location.search).get('tab');
                            
                            d.unit_id = unitSelect ? unitSelect.value : '';
                            d.tab = tabActive || 'berjalan';
                        }
                    },
                    columns: [
                        { data: 0 }, 
                        { data: 1 }, 
                        { data: 2 }, 
                        { data: 3 }, 
                        { data: 4 }, 
                        { data: 5 }, 
                        { data: 6 }, 
                        { data: 7 }, 
                        { data: 8 }, 
                        { data: 9, className: "text-right" }
                    ],

                    order: [[0, 'desc']], 
                    columnDefs: [
                        { orderable: false, targets: -1 },
                        { orderable: false, targets: 1 },
                        { orderable: false, targets: 4 },
                    ],
                    
                    drawCallback: function() {
                        const wrapper = $(this.api().table().container());
                        wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
                        wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
                        const p = wrapper.find('.dataTables_paginate .paginate_button');
                        p.addClass('u-btn u-btn--sm u-btn--ghost');
                        p.filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
                        p.filter('.disabled').addClass('u-disabled').css('opacity', '0.5');

                        // Re-bind event listener untuk tombol yang baru dirender AJAX
                        // (Misalnya tombol detail atau edit yang ada di dalam tabel)
                        // Karena konten baru muncul, event onclick global document sudah aman,
                        // tapi kalau ada inisialisasi spesifik (tooltip, dll), lakukan di sini.
                    }
                });

                // Event Listener agar saat Filter Unit berubah, tabel reload AJAX
                const unitSelect = document.querySelector('select[name="unit_id"]');
                if(unitSelect) {
                    unitSelect.addEventListener('change', () => {
                        this.dt.draw(); // Refresh tabel via AJAX
                    });
                    // Hapus attribute onchange="this.form.submit()" dari HTML select agar tidak reload page
                    unitSelect.removeAttribute('onchange');
                }
            },
            bindExternalSearch() {
                const projectSearchInput = document.getElementById('kodeProjectSearchInput');
                const projectHiddenInput = document.getElementById('kodeProjectInput');
                const projectNameInput   = document.getElementById('namaProjectInput');
                const projectResults     = document.getElementById('kodeProjectSearchResults');
                let projectSearchTimeout = null;
                const fetchProjects = (query) => {
                    projectResults.innerHTML = '<div class="u-p-sm u-text-muted"><i class="fas fa-circle-notch fa-spin"></i> Memuat...</div>';
                    projectResults.style.display = 'block';
                    fetch(`{{ route('api.project_codes.index') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            projectResults.innerHTML = '';
                            const addNewDiv = document.createElement('div');
                            addNewDiv.className = 'u-p-sm u-text-brand u-font-bold';
                            addNewDiv.style.cursor = 'pointer';
                            addNewDiv.style.borderBottom = '1px solid #e5e7eb';
                            addNewDiv.innerHTML = `<i class="fas fa-plus-circle"></i> Buat Project Baru (Manual)`;
                            addNewDiv.onclick = function() {
                                    const projectModal = document.getElementById('createProjectModal');
                                    const projectForm = document.getElementById('formCreateProject');
                                    if(projectForm) projectForm.reset();
                                    if(projectModal) projectModal.style.display = 'flex';
                                    projectResults.style.display = 'none';
                                    projectSearchInput.value = ""; 
                            };
                            projectResults.appendChild(addNewDiv);
                            if (data.results && data.results.length > 0) {
                                data.results.forEach(item => {
                                    const div = document.createElement('div');
                                    div.className = 'u-p-sm u-hover-bg-light';
                                    div.style.cursor = 'pointer';
                                    div.style.borderBottom = '1px solid #f0f0f0';
                                    const codeDisplay = item.client_id ? `<span class="u-font-bold text-primary">${item.client_id}</span>` : '<span class="u-text-muted">No Code</span>';
                                    const nameDisplay = item.nama_proyek || '(Tanpa Nama)';
                                    const clientDisplay = item.nama_klien ? item.nama_klien : '-';
                                    div.innerHTML = `
                                        <div class="u-font-medium text-sm">${codeDisplay} - ${nameDisplay}</div>
                                        <div class="u-text-2xs u-muted">Klien: ${clientDisplay}</div>
                                    `;

                                    div.addEventListener('click', () => {
                                        projectSearchInput.value = item.client_id; 
                                        projectHiddenInput.value = item.client_id; 
                                        if(projectNameInput) projectNameInput.value = item.nama_proyek;
                                        projectResults.style.display = 'none';
                                    });
                                    projectResults.appendChild(div);
                                });
                            } else {
                                const noRes = document.createElement('div');
                                noRes.className = 'u-p-sm u-text-muted';
                                noRes.textContent = 'Tidak ditemukan di CRM.';
                                projectResults.appendChild(noRes);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            projectResults.innerHTML = '<div class="u-p-sm u-text-danger">Gagal memuat data</div>';
                        });
                };
                if (projectSearchInput) {
                    projectSearchInput.addEventListener('focus', function() {
                        fetchProjects(this.value); 
                    });
                    projectSearchInput.addEventListener('input', function(e) {
                        const query = e.target.value;
                        clearTimeout(projectSearchTimeout);
                        projectSearchTimeout = setTimeout(() => {
                            fetchProjects(query);
                        }, 300);
                    });
                    document.addEventListener('click', (e) => {
                        if (!projectSearchInput.contains(e.target) && !projectResults.contains(e.target)) {
                            projectResults.style.display = 'none';
                        }
                    });
                }
            }
        };
        page.init();
        const urlParams = new URLSearchParams(window.location.search);
            const ticketId = urlParams.get('open_ticket_id');
            if (ticketId) {
                setTimeout(() => {
                    const detailBtn = document.querySelector(`.js-open-detail[data-id="${ticketId}"]`);
                    if (detailBtn) {
                        detailBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const row = detailBtn.closest('tr');
                        if(row) {
                            const originalBg = row.style.backgroundColor;
                            row.style.transition = "background-color 0.5s ease";
                            row.style.backgroundColor = "#fef3c7"; 
                            setTimeout(() => { row.style.backgroundColor = originalBg; }, 2000);
                        }
                        detailBtn.click();
                    }
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({path: newUrl}, '', newUrl);
                }, 800);
            }
        });
</script>
@endpush