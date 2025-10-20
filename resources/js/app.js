// resources/js/app.js
import './bootstrap';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

document.addEventListener('DOMContentLoaded', () => {

  // ===== USERS
  initDataTables('#users-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 2, responsivePriority: 3 },
      { targets: 1, responsivePriority: 4 },
    ],
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

  // ===== IZIN TABLE (Izin Prinsip Terbaru)
  if (document.querySelector('#izin-table')) {
    initDataTables('#izin-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
        { targets: 2, responsivePriority: 4 },
      ],
    });
  }

  // ===== MONITOR TABLE (Kontrak Terbaru)
  if (document.querySelector('#monitor-table')) {
    initDataTables('#monitor-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
        { targets: 2, responsivePriority: 4 },
      ],
    });
  }
});
