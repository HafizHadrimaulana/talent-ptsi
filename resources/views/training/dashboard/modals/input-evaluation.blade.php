<div id="modal-input-evaluation"
     class="u-modal hidden">
  <div class="u-modal__card">
    <div class="u-modal__head">
      <h3 class="u-title">Input Data Evaluasi</h3>
    </div>

    <div class="u-modal__body">
      <form id="form-input-evaluation" enctype="multipart/form-data">
        @csrf

        <div class="u-panels">
          <div class="u-card">
            <input type="hidden" name="training_id" id="training_id">

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nama Peserta
              </label>
              <input type="text" id="nama_peserta" readonly
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nama Pelatihan
              </label>
              <input type="text" id="nama_pelatihan" readonly
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div>

            <!-- <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tanggal Realisasi
              </label>
              <input type="text" id="realisasi_date" readonly
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div> -->

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Evaluasi
              </label>
              <input type="text" id="evaluasi" name="evaluasi"
                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-100">
            </div>
          </div>
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
