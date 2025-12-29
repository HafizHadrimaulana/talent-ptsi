<div id="modal-upload-certif"
     class="u-modal hidden">
  <div class="u-modal__card">
    <div class="u-modal__head">
      <h3 class="u-title">Upload Dokumen Sertifikasi</h3>
    </div>

    <div class="u-modal__body">
      <form id="form-upload-certif" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="u-panels">
          <div class="u-card">
            <input type="hidden" name="training_id_upload" id="training_id_upload">

            <!-- <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nama Pelatihan
              </label>
              <input type="text" id="nama_pelatihan" readonly
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nama Peserta
              </label>
              <input type="text" id="nama_peserta" readonly
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div> -->

            <div class="mb-4">
              <label for="certificate_document" class="block text-sm font-medium text-gray-700 mb-1">
                Dokumen Sertifikat (PDF)
              </label>
              <input type="file" name="certificate_document" id="certificate_document"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:outline-none">
              <p class="text-xs text-gray-500 mt-1">Format: PDF, JPG, PNG</p>
            </div>
          </div>
        </div>

        <div class="u-modal__foot">
          <button type="button" id="close-upload-certif"
                  class="u-btn u-btn--ghost">
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
