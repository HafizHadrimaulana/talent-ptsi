<div id="add-modal"
    class="u-modal hidden">
  <!-- Card Modal -->
  <div class="u-modal__card">
    <!-- Header -->
    <div class="u-modal__head">
      <h3 class="u-title">Tambah Data Pelatihan</h3>
    </div>

    <!-- Body -->
    <div class="u-modal__body">
      <div class="u-modal__body">
        <form id="add-form" method="POST">
          @csrf

          <div class="u-panels">
            <div class="u-card">
    
              <div class="grid grid-cols-2 gap-4">
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">NIK</label>
                  <input type="text" name="nik" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Nama Peserta</label>
                  <input type="text" name="nama_peserta" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Status Pegawai</label>
                  <input type="text" name="status_pegawai" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Jabatan Saat Ini</label>
                  <input type="text" name="jabatan_saat_ini" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Unit Kerja</label>
                  <input type="text" name="unit_kerja" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
                  <input type="text" name="judul_sertifikasi" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Penyelenggara</label>
                  <input type="text" name="penyelenggara" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Jumlah Jam</label>
                  <input type="text" name="jumlah_jam" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Waktu Pelaksanaan</label>
                  <input type="text" name="waktu_pelaksanaan" class="w-full border rounded p-2" required placeholder="contoh: 12-15 Mei 2025">
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Nama Proyek</label>
                  <input type="text" name="nama_proyek" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Biaya Pelatihan (Rp)</label>
                  <input type="number" step="0.01" name="biaya_pelatihan" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">UHPD (Rp)</label>
                  <input type="number" step="0.01" name="uhpd" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Biaya Akomodasi (Rp)</label>
                  <input type="number" step="0.01" name="biaya_akomodasi" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Estimasi Total Biaya (Rp)</label>
                  <input type="number" step="0.01" name="estimasi_total_biaya" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Jenis Portofolio</label>
                  <input type="text" name="jenis_portofolio" class="w-full border rounded p-2" required>
                </div>
      
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">Alasan</label>
                  <input type="text" name="alasan" class="w-full border rounded p-2" required>
                </div>

                  <!-- Jenis Pelatihan -->
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">
                    Jenis Pelatihan
                  </label>
                  <select
                    name="jenis_pelatihan"
                    class="w-full border rounded p-2 bg-white"
                    required
                  >
                    <option value="">-- Pilih Jenis Pelatihan --</option>
                    <option value="EDP - Sertifikat Profesi">EDP - Sertifikat Profesi</option>
                    <option value="EDP - Sertifikat Industri">EDP - Sertifikat Industri</option>
                  </select>
                </div>

                <!-- Start Date -->
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">
                    Start Date
                  </label>
                  <input
                    type="date"
                    name="start_date"
                    class="w-full border rounded p-2"
                    required
                  >
                </div>

                <!-- End Date -->
                <div>
                  <label class="block mb-1 font-medium after:content-['*'] after:text-red-500">
                    End Date
                  </label>
                  <input
                    type="date"
                    name="end_date"
                    class="w-full border rounded p-2"
                    required
                  >
                </div>
      
              </div>
  
            </div>
          </div>
          <div class="u-modal__foot">
            <button type="button" id="close-input-modal" class="u-btn u-btn--ghost">Tutup</button>
            <button type="submit" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
