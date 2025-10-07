// resources/js/app.js
import './bootstrap';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

document.addEventListener('DOMContentLoaded', () => {
  // USERS
  initDataTables('#users-table', {
    dom: "<'dt-top'<'dt-left dataTables_length'l><'dt-right dataTables_filter'f>>t<'dt-bottom'<'dt-left dataTables_info'i><'dt-right dataTables_paginate'p>>",
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140 }
    ]
  });
  bindExternalSearch({
    searchSelector: 'input[name="q"]',
    buttonSelector: 'form [type="submit"]',
    tableSelector: '#users-table',
    delay: 250,
  });

  // ROLES
  initDataTables('#roles-table', {
    dom: "<'dt-top'<'dt-left dataTables_length'l><'dt-right dataTables_filter'f>>t<'dt-bottom'<'dt-left dataTables_info'i><'dt-right dataTables_paginate'p>>",
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140 }
    ]
  });

  // PERMISSIONS
  initDataTables('#perms-table', {
    dom: "<'dt-top'<'dt-left dataTables_length'l><'dt-right dataTables_filter'f>>t<'dt-bottom'<'dt-left dataTables_info'i><'dt-right dataTables_paginate'p>>",
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140 }
    ]
  });
});
