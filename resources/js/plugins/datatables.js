// resources/js/plugins/datatables.js
import $ from 'jquery';
window.$ = window.jQuery = $;

import DataTable from 'datatables.net';
// Boleh pakai theme default DT; CSS kustom kita override di app-ui.css
import 'datatables.net-dt/css/dataTables.dataTables.css';

// ===== Helpers (idempotent) =====
const helpers = window.__DT_HELPERS__ || (() => {
  const debounce = (fn, delay = 300) => { let t; return (...a)=>{clearTimeout(t); t=setTimeout(()=>fn(...a),delay)} };

  // DOM template: top (left length, right search), bottom (left info, right paginate)
  const DOM_CHROME = "<'dt-top'<'dt-left dataTables_length'l><'dt-right dataTables_filter'f>>" +
                     "t" +
                     "<'dt-bottom'<'dt-left dataTables_info'i><'dt-right dataTables_paginate'p>>";

  const DEFAULTS = {
    dom: DOM_CHROME,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [],
    orderMulti: false,
    autoWidth: false,
    stateSave: true,
    language: {
      search: "",
      searchPlaceholder: "Ketik untuk cari…",
      lengthMenu: "Tampilkan _MENU_",
      info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      zeroRecords: "Tidak ditemukan",
      paginate: { first: "«", last: "»", next: "›", previous: "‹" },
      processing: "Memproses…",
    }
  };

  // Tambah kelas & tata letak agar nempel ke CSS iOS glass kita
  function applyChrome(dtApi) {
    const $table = $(dtApi.table().node());
    const $wrap  = $(dtApi.table().container());

    // Bungkus card & grid toolbar
    $wrap.addClass('dataTables_wrapper'); // ensure
    $wrap.closest('.table-responsive, .card, .glass, .dt-card').addClass('dt-card'); // fallback
    $wrap.find('.dt-top, .dt-bottom').each(function(){
      const grp = $(this);
      grp.addClass('grid gap-2'); // harmless; CSS kita target .dt-top/.dt-bottom
    });

    // Input filter & select length -> beri class util
    const $filter = $wrap.find('.dataTables_filter input');
    $filter.attr('placeholder', dtApi.settings()[0].oLanguage.sSearchPlaceholder || 'Cari…')
           .addClass('input input--sm');

    const $length = $wrap.find('.dataTables_length select');
    $length.addClass('select--sm');

    // Table body util
    $table.addClass('table-ui table-compact');
    // Kolom aksi (kalau belum)
    $table.find('th:last-child, td:last-child').addClass('cell-actions');

    // Perataan paginate
    $wrap.find('.dataTables_paginate').addClass('pagination-ui');

    // Sticky header fix pada container scroller (opsional)
    const $scroller = $table.closest('.dt-card, .table-wrap, .table-responsive');
    if ($scroller.length) {
      $scroller.css('overflow', 'hidden'); // biar sticky header bekerja
    }
  }

  function initDataTables(selector='[data-dt]', opts={}) {
    const finalOpts = { ...DEFAULTS, ...opts };
    $(selector).each(function(){
      if ($.fn.dataTable.isDataTable(this)) return;

      // Inisialisasi
      const dt = new DataTable(this, {
        ...finalOpts,
        // rapikan paginate ketika draw
        drawCallback: function() {
          const api = this.api();
          const $wrap = $(api.table().container());
          $wrap.find('.paginate_button').attr('aria-label','Halaman');
        },
        initComplete: function() {
          applyChrome(this.api());
        }
      });

      // Simpan api bila perlu
      $(this).data('dt-instance', dt);
    });
  }

  function bindExternalSearch({ searchSelector, buttonSelector=null, tableSelector='[data-dt]', delay=300 }) {
    const input = document.querySelector(searchSelector);
    if (!input) return;
    const dt = $(tableSelector).DataTable();
    const run = ()=> dt.search(input.value || '').draw();

    input.addEventListener('input', debounce(run, delay));
    if (buttonSelector) {
      const btn = document.querySelector(buttonSelector);
      if (btn) btn.addEventListener('click', e => { e.preventDefault(); run(); });
    }
  }

  function destroyDataTables(selector='[data-dt]') {
    $(selector).each(function(){
      if ($.fn.dataTable.isDataTable(this)) $(this).DataTable().destroy();
    });
  }

  return { initDataTables, bindExternalSearch, destroyDataTables };
})();

window.__DT_HELPERS__ = helpers;
export const { initDataTables, bindExternalSearch, destroyDataTables } = helpers;
export default helpers;
