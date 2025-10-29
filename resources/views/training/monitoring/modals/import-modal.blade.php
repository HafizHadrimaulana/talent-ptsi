<div id="import-modal" 
  class="modal hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white p-6 rounded shadow-lg w-96">
    <h2 class="text-lg font-semibold mb-4">Import Data Excel</h2>

    <form id="import-form" enctype="multipart/form-data">
      @csrf
      <div class="flex items-center gap-2 mb-4">
        <input type="file" name="file" accept=".xlsx,.xls" required class="border rounded p-2 w-full">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
          Upload
        </button>
      </div>
    </form>

    <div class="flex justify-end">
      <button type="button" id="close-modal" class="text-gray-600 hover:text-gray-800">Tutup</button>
    </div>
  </div>
</div>