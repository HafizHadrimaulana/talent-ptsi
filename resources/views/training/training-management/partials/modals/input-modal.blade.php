<div id="input-training-modal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">
    
    {{-- Header --}}
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Ajukan Pelatihan Baru</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Ajukan permintaan pelatihan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm js-close-modal" aria-label="Close">
          <i class="fas fa-times"></i>
      </button>
    </div>

    {{-- Body --}}
    <div class="u-modal__body u-p-md">
      <form id="add-form" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 u-gap-md">
          
          {{-- KOLOM KIRI: Informasi Pelatihan & Peserta --}}
          <div class="u-space-y-md">
            
            {{-- Card 1: Data Utama --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-file-alt text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Data Pelatihan</h3>
              </div>

              <div class="u-space-y-sm">
                <div class="u-space-y-xs">
                  <label class="font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
                  <select name="judul_sertifikasi" class="u-input bg-white" required>
                    <option value="">-- Pilih Judul Sertifikasi --</option>
                  </select>
                </div>

                <div class="grid grid-cols-2 u-gap-sm">
                  <div class="u-space-y-xs">
                    <label class="font-medium">Penyelenggara</label>
                    <input type="text" name="penyelenggara" class="u-input bg-gray-100 u-text-gray-500" readonly />
                  </div>
                  <div class="u-space-y-xs">
                    <label class="font-medium">Nama Proyek</label>
                    <input type="text" name="nama_proyek" class="u-input bg-gray-100 u-text-gray-500" readonly>
                  </div>
                </div>

                <div class="grid grid-cols-2 u-gap-sm">
                  <div class="u-space-y-xs">
                    <label class="font-medium">Jumlah Jam</label>
                    <input type="text" name="jumlah_jam" class="u-input bg-gray-100 u-text-gray-500" readonly>
                  </div>
                  <div class="u-space-y-xs">
                    <label class="font-medium">Jenis Portofolio</label>
                    <input type="text" name="jenis_portofolio" class="u-input bg-gray-100 u-text-gray-500" readonly>
                  </div>
                </div>

                <div class="u-space-y-xs">
                  <label class="font-medium">Jenis Pelatihan</label>
                  <input type="text" name="jenis_pelatihan" class="u-input bg-gray-100 u-text-gray-500" readonly>
                </div>
              </div>
            </div>

            {{-- Card 2: Peserta --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-users text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Daftar Peserta</h3>
              </div>
              <div class="u-space-y-xs">
                <label class="font-medium after:content-['*'] after:text-red-500">Tambah Peserta</label>
                <div class="w-full relative">
                  <div id="peserta-container" class="tags-input">
                    <div id="peserta-selected" class="flex flex-wrap gap-2"></div>
                    <input type="text" id="peserta-search" placeholder="Ketik nama peserta..." autocomplete="off">
                  </div>
                  <div id="peserta-dropdown" class="dropdown-search"></div>
                  <input type="hidden" name="peserta_list" id="peserta-list-hidden" required>
                </div>
              </div>
            </div>
          </div>

          {{-- KOLOM KANAN: Waktu, Biaya, Lampiran --}}
          <div class="u-space-y-md">
            
            {{-- Card 3: Jadwal --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-calendar-alt text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Jadwal Pelaksanaan</h3>
              </div>
              <div class="u-space-y-sm">
                <div class="u-space-y-xs">
                  <label class="font-medium">Waktu Pelaksanaan (Rencana)</label>
                  <input type="text" name="waktu_pelaksanaan" class="u-input bg-gray-100 u-text-gray-500" readonly>
                </div>
                <div class="grid grid-cols-2 u-gap-sm">
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Start Date</label>
                    <input type="date" name="start_date" class="u-input" required>
                  </div>
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">End Date</label>
                    <input type="date" name="end_date" class="u-input" required>
                  </div>
                </div>
              </div>
            </div>

            {{-- Card 4: Biaya --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-emerald-50/50 border-l-4 border-l-emerald-400">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md text-emerald-700">
                <i class="fas fa-money-bill-wave"></i>
                <h3 class="u-uppercase u-text-sm">Informasi Biaya</h3>
              </div>
              <div class="u-space-y-sm">
                <div class="u-space-y-xs">
                  <label class="font-medium">Biaya Rencana (Rp)</label>
                  <input type="text" name="biaya_pelatihan" class="u-input bg-gray-100 u-text-gray-500" readonly>
                </div>
                <div id="realisasi-wrapper" class="u-space-y-xs">
                  <label class="font-medium">Realisasi Biaya (Rp)</label>
                  <input type="text" id="realisasi_biaya_pelatihan" name="realisasi_biaya_pelatihan" class="u-input bg-gray-100 u-text-gray-500" readonly>
                  <div class="u-flex u-items-center u-gap-sm u-mt-xs">
                    <input type="checkbox" id="toggle-realisasi" class="u-checkbox">
                    <label for="toggle-realisasi" class="u-text-sm cursor-pointer">Biaya berbeda dengan rencana?</label>
                  </div>
                </div>
              </div>
            </div>

            {{-- Card 5: Lampiran --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-paperclip text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Dokumen Pendukung</h3>
              </div>
              <div class="u-space-y-xs">
                <label class="font-medium after:content-['*'] after:text-red-500">Lampiran Penawaran (PDF)</label>
                <input type="file" accept=".pdf" name="lampiran_penawaran" class="u-input font-normal bg-white" required>
              </div>
            </div>

          </div>
        </div> {{-- End Grid --}}
      </form>
    </div>

    {{-- Footer --}}
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" id="training-close-modal" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button type="submit" form="add-form" class="u-btn u-btn--brand u-hover-lift">
            <i class="fas fa-paper-plane u-mr-xs"></i> Kirim Pengajuan
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  .tags-input {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    min-height: 46px;
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #fff;
    cursor: text;
  }
  .tags-input input {
    border: none;
    outline: none;
    flex: 1;
    min-width: 120px;
  }
  .chip {
    background: #e2e8f0;
    border-radius: 8px;
    padding: 5px 8px;
    display: inline-flex;
    align-items: center;
    font-size: 14px;
  }
  .chip button {
    margin-left: 6px;
    color: red;
    font-weight: bold;
    cursor: pointer;
  }
  .dropdown-search {
    max-height: 180px;
    overflow-y: auto;
    border: 1px solid #ccc;
    background: #fff;
    border-radius: 6px;
    display: none;
    position: absolute;
    width: 100%;
    margin-top: 2px;
    z-index: 999;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  }
  .dropdown-search div {
    padding: 8px;
    cursor: pointer;
  }
  .dropdown-search div:hover {
    background: #f1f5f9;
  }
</style>

<script>
    window.userUnitId = "{{ auth()->user()->unit_id }}";
</script>