<div id="input-training-modal" class="dt-wrapper u-mb-lg hidden">

  <form id="add-form" method="POST">
    @csrf

    <div class="u-panels u-space-lg">

      <div class="u-modal__head u-mb-lg">
        <h3 class="u-uppercase">Tambah Data Pelatihan</h3>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
        <select name="judul_sertifikasi" class="u-input bg-white" readonly>
          <option value="">-- Pilih Judul Sertifikasi --</option>
        </select>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Penyelenggara</label>
        <input type="text"
            name="penyelenggara"
            class="u-input"
            readonly />
        </select>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Jumlah Jam</label>
        <input type="text"
          name="jumlah_jam"
          class="u-input"
          readonly>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Waktu Pelaksanaan</label>
        <input type="text"
          name="waktu_pelaksanaan"
          class="u-input"
          readonly>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Nama Proyek</label>
        <input type="text"
          name="nama_proyek"
          class="u-input"
          readonly>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Jenis Portofolio</label>
        <input type="text"
          name="jenis_portofolio"
          class="u-input"
          readonly>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Jenis Pelatihan</label>
        <input type="text"
          name="jenis_pelatihan"
          class="u-input"
          readonly>
      </div>

      <div class="u-modal__head u-mb-lg">
        <h3 class="u-uppercase">Biaya</h3>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Biaya Pelatihan (Rp)</label>
        <input type="number" name="biaya_pelatihan" class="u-input">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">UHPD (Rp)</label>
        <input type="number" name="uhpd" class="u-input">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Biaya Akomodasi (Rp)</label>
        <input type="number" name="biaya_akomodasi" class="u-input">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Estimasi Total Biaya (Rp)</label>
        <input type="number" name="estimasi_total_biaya" class="u-input">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Alasan</label>
        <input name="alasan" class="u-input bg-white">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Start Date</label>
        <input type="date" name="start_date" class="u-input">
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">End Date</label>
        <input type="date" name="end_date" class="u-input">
      </div>

      <div class="u-modal__head u-mb-lg">
        <h3 class="u-uppercase">Peserta</h3>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Tambah Peserta</label>
        <div class="w-full relative">
    
          <!-- INPUT MODEL TAG -->
          <div id="peserta-container" class="tags-input">
            <!-- Chips akan muncul di sini -->
            <div id="peserta-selected" class="flex flex-wrap gap-2"></div>

            <!-- Input search -->
            <input 
              type="text" 
              id="peserta-search"
              placeholder="Ketik nama peserta..."
              autocomplete="off"
            >
          </div>

          <!-- Dropdown hasil search -->
          <div id="peserta-dropdown" class="dropdown-search"></div>

          <!-- Hidden input untuk submit ke backend -->
          <input type="hidden" name="peserta_list" id="peserta-list-hidden">
        </div>
      </div>

      <div class="u-modal__head u-mb-lg">
        <h3 class="u-uppercase">Lampiran Penawaran</h3>
      </div>

      <div class="flex items-center gap-4 u-mb-lg">
        <label class="w-48 font-medium after:content-['*'] after:text-red-500">Lampiran Penawaran</label>
        <input type="file" accept=".pdf" name="lampiran_penawaran" class="u-input">
      </div>

    </div>

    <!-- Footer -->
    <div class="flex justify-end">
      <button type="submit" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
    </div>

  </form>
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
