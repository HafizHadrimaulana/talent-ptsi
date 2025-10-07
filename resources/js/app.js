// resources/js/app.js
import './bootstrap';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

document.addEventListener('DOMContentLoaded', () => {
  // ===== USERS
  initDataTables('#users-table', {
    // Layout atas/bawah tetap, tapi responsive aktif
    columnDefs: [
      // Aksi: jangan disort/cari, selalu tampil (priority 1 = paling penting)
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      // Nama paling penting kedua
      { targets: 0, responsivePriority: 2 },
      // Roles lebih penting dari email di layar kecil? atur prioritasnya
      { targets: 2, responsivePriority: 3 },
      { targets: 1, responsivePriority: 4 },
    ],
    // order: [[0,'asc']], // kalau mau default sort
  });
  bindExternalSearch({
    searchSelector: 'input[name="q"]',
    buttonSelector: 'form [type="submit"]',
    tableSelector: '#users-table',
    delay: 250,
  });

  // ===== ROLES
  initDataTables('#roles-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 1, responsivePriority: 3 },
    ],
  });

  // ===== PERMISSIONS
  initDataTables('#perms-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 1, responsivePriority: 3 },
    ],
  });
});
