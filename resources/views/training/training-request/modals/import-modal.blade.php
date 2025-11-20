<div id="import-modal"
  class="u-modal hidden">
  <div class="u-modal__card">
    <h2 class="u-modal__head">Import Data Excel</h2>

    <div class="u-modal__body">
      <div class="u-modal__body">
        <form id="import-form" enctype="multipart/form-data">
          @csrf
          <div class="u-panels">
            <div class="u-card">
                <div id="dragdrop-wrapper">
                  <div
                      id="drag-drop-area"
                      class="w-full border-2 border-dashed border-gray-200 rounded-lg u-p-lg text-center cursor-pointer hover:border-blue-500 transition bg-gray-50"
                  >
                    <div class="u-flex u-flex-col u-items-center u-justify-center u-gap-md">
                        <p class="text-gray-600 text-lg font-medium">
                            Drag & Drop your Excel file here
                        </p>
                        <p class="text-gray-400 text-sm">
                            or click to select a file
                        </p>

                        <input type="file" name="file" id="drag-drop-input" class="hidden" accept=".xlsx,.xls" required>
                    </div>
                  </div>
                </div>

              <div id="selected-file-info" class="hidden mt-4"></div>

            </div>
          </div>

          <div class="u-modal__foot">
            <button type="button" id="close-modal" class="u-btn u-btn--ghost">Tutup</button>
            <button type="submit" class="u-btn u-btn--brand u-hover-lift">
              Upload
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>