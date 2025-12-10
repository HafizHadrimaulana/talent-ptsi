<div id="lna-import-modal"
  class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Import Data Excel</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Import data pelatihan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

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

              <div id="selected-file-info" class="hidden"></div>

            </div>
          </div>
        </form>
      </div>
    </div>

        <!-- Footer -->
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" id="lna-close-modal" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        <button type="submit"  form="import-form" class="u-btn u-btn--brand u-hover-lift">Upload</button>
      </div>
    </div>
  </div>
</div>