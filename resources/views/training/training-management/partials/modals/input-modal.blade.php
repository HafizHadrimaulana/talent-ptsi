<div id="input-training-modal"
  class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Ajukan Pelatihan Baru</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Ajukan permintaan pelatihan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="u-modal__body u-p-md u-space-y-lg">
      <form id="add-form" method="POST">
        @csrf
          <div class="font-bold u-mb-sm">
            <h3 class="u-uppercase">Tambah Data Pelatihan</h3>
          </div>
          <div class="u-mb-xl">
            <div class="u-space-y-sm u-mb-sm">
              <label class="w-48 font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
              <select name="judul_sertifikasi" class="u-input bg-white" required>
                <option value="">-- Pilih Judul Sertifikasi --</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-x-10 gap-y-2">
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Penyelenggara</label>
                <input type="text"
                    name="penyelenggara"
                    class="font-normal u-input"
                    readonly />
                </select>
              </div>
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Nama Proyek</label>
                <input type="text"
                  name="nama_proyek"
                  class="font-normal u-input"
                  readonly>
              </div>
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Jumlah Jam</label>
                <input type="text"
                  name="jumlah_jam"
                  class="font-normal u-input"
                  readonly>
              </div>
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Waktu Pelaksanaan</label>
                <input type="text"
                  name="waktu_pelaksanaan"
                  class="font-normal u-input"
                  readonly>
              </div>
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Jenis Portofolio</label>
                <input type="text"
                  name="jenis_portofolio"
                  class="font-normal u-input"
                  readonly>
              </div>
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium">Jenis Pelatihan</label>
                <input type="text"
                  name="jenis_pelatihan"
                  class="font-normal u-input"
                  readonly>
              </div>
              
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium after:content-['*'] after:text-red-500">Start Date</label>
                <input type="date" name="start_date" class="u-input font-normal" required>
              </div>
              
              <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium after:content-['*'] after:text-red-500">End Date</label>
                <input type="date" name="end_date" class="u-input font-normal" required>
              </div>
    
              <!-- <div class="font-medium u-mb-sm">
                <label class="w-48 font-medium after:content-['*'] after:text-red-500">Alasan</label>
                <input name="alasan" class="u-input bg-white">
              </div> -->
    
            </div>
          </div>
    
          <div class="font-medium u-mb-sm">
            <h3 class="u-uppercase">Biaya</h3>
          </div>
          <div class="grid grid-cols-2 gap-x-10 gap-y-2 u-space-lg u-mb-xl">
            <div class="font-medium u-mb-sm">
              <label class="w-48 font-medium">Biaya Pelatihan (Rp)</label>
              <input type="text" 
                    name="biaya_pelatihan" 
                    class="font-normal u-input u-bg-gray-100 u-text-gray-500 u-pointer-events-none"
                    readonly>
            </div>

            <!-- REALISASI BIAYA -->
            <div id="realisasi-wrapper" class="font-medium u-mb-sm">
              <label class="font-medium">Realisasi Biaya Pelatihan (Rp)</label>

              <input type="text" 
                    id="realisasi_biaya_pelatihan" 
                    name="realisasi_biaya_pelatihan" 
                    class="font-normal u-input u-bg-gray-100 u-text-gray-500 u-pointer-events-none"
                    readonly>

              <div class="u-flex u-items-center u-gap-sm u-mt-sm">
                  <input type="checkbox" id="toggle-realisasi" class="u-checkbox">
                  <label for="toggle-realisasi" class="font-medium">Biaya berbeda dengan rencana?</label>
              </div>
            </div>
          </div>
    
          <div class="font-medium u-mb-sm">
            <h3 class="u-uppercase">Peserta</h3>
          </div>
          <div class="u-space-lg u-mb-xl">
    
            <div class="font-medium u-mb-sm">
              <label class="w-48 font-medium after:content-['*'] after:text-red-500">Tambah Peserta</label>
              <div class="w-full relative">
          
                <div id="peserta-container" class="tags-input">
                  <div id="peserta-selected" class="flex flex-wrap gap-2"></div>
                  <input
                    type="text"
                    id="peserta-search"
                    placeholder="Ketik nama peserta..."
                    autocomplete="off"
                    class=""
                  >
                </div>
    
                <div id="peserta-dropdown" class="dropdown-search"></div>
                <input type="hidden" name="peserta_list" id="peserta-list-hidden" class="font-normal u-input font-normal" required>
              </div>
            </div>
          </div>
    
          <div class="font-medium u-mb-sm">
            <h3 class="u-uppercase">Lampiran Penawaran</h3>
          </div>
    
          <div class="font-medium u-mb-xl">
            <label class="w-48 font-medium after:content-['*'] after:text-red-500">Lampiran Penawaran</label>
            <input type="file" accept=".pdf" name="lampiran_penawaran" class="u-input font-normal" required>
          </div>
      </form>
    </div>
  
    <!-- Footer -->
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" id="training-close-modal" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button type="submit"  form="add-form" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
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