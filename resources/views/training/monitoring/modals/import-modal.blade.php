<div id="import-modal"
  class="u-modal hidden">
  <div class="u-modal__card">
    <h2 class="u-modal__head">Import Data Excel</h2>

    <div class="u-modal__body">
      <div class="u-modal__body">
        <form id="import-form" enctype="multipart/form-data">
          @csrf
          <div class="flex items-center gap-2 mb-4">
            <input type="file" name="file" accept=".xlsx,.xls" required class="border rounded p-2 w-full">
          </div>
          <div class="u-modal__foot">
            <button type="submit" class="u-btn u-btn--brand u-hover-lift">
              Upload
            </button>
              <button type="button" id="close-modal" class="u-btn u-btn--ghost">Tutup</button>
          </div>
        </form>
    
      </div>
    </div>
  </div>
</div>