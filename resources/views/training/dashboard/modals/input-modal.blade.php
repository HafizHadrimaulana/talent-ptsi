<div id="modal-input-evaluation"
     class="u-modal hidden">
  <div class="u-modal__card">
    <div class="u-modal__head">
      <h3 class="u-title">Tambah Data Evaluasi</h3>
    </div>

    <div class="u-modal__body">
      <form action="{{ route('training.dashboard.input-evaluation') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
          <label for="nama_pelatihan" class="block text-sm font-medium text-gray-700 mb-1">
            Nama Pelatihan
          </label>
          <input type="text" name="nama_pelatihan" id="nama_pelatihan"
                 class="w-full border rounded p-2"
                 placeholder="Masukkan nama pelatihan" required>
        </div>

        <div class="mb-4">
          <label for="nama_peserta" class="block text-sm font-medium text-gray-700 mb-1">
            Nama Peserta
          </label>
          <input type="text" name="nama_peserta" id="nama_peserta"
                 class="w-full border rounded p-2"
                 placeholder="Masukkan nama peserta" required>
        </div>

        <div class="mb-4">
          <label for="tanggal_realisasi" class="block text-sm font-medium text-gray-700 mb-1">
            Tanggal Realisasi
          </label>
          <input type="date" name="tanggal_realisasi" id="tanggal_realisasi"
                 class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
          <label for="dokumen_sertifikat" class="block text-sm font-medium text-gray-700 mb-1">
            Dokumen Sertifikat (PDF)
          </label>
          <input type="file" name="dokumen_sertifikat" id="dokumen_sertifikat" accept=".pdf,.jpg,.jpeg,.png"
                 class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:outline-none">
          <p class="text-xs text-gray-500 mt-1">Format yang didukung: PDF, JPG, PNG</p>
        </div>

        <div class="u-modal__foot">
          <button type="button" id="close-input-evaluation"
                  class="u-btn u-btn--ghost0">
            Batal
          </button>
          <button type="submit"
                  class="u-btn u-btn--brand u-hover-lift">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
