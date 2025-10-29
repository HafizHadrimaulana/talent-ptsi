<div id="add-modal"
    class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <!-- Card Modal -->
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
    <!-- Header -->
    <div class="flex justify-between items-center border-b px-6 py-3">
      <h3 class="text-lg font-semibold text-gray-800">Tambah Data Pelatihan</h3>
    </div>

    <!-- Body -->
    <div class="p-6">
      <form id="add-form" method="POST">
        @csrf

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

        </div>

        <!-- Tombol -->
        <div class="flex justify-end gap-3 mt-6">
          <button type="submit"
                  class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Simpan
          </button>
          <button type="button" id="close-input-modal" class="text-gray-600 hover:text-gray-800">Tutup</button>
        </div>
      </form>
    </div>
  </div>
</div>
