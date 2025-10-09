// resources/js/plugins/datatables.js
import $ from 'jquery';
window.$ = window.jQuery = $;

// Core & base CSS (DT v2)
import DataTable from 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';

// Responsive extension (DT v2) + CSS (bukan @datatables.net/*)
import 'datatables.net-responsive';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';

// ===== Helpers (idempotent) =====
const helpers = window.__DT_HELPERS__ || (() => {
  const debounce = (fn, delay = 300) => { let t; return (...a)=>{clearTimeout(t); t=setTimeout(()=>fn(...a),delay)} };

  // Toolbar atas/bawah
  const DOM_CHROME =
  "<'dt-toolbar grid gap-2 mb-3'<'dt-length-row flex justify-start items-center'l><'dt-control-row flex justify-between items-center gap-3'f>>" +
  "t" +
  "<'dt-footer flex justify-between items-center mt-3'<'dt-left dataTables_info'i><'dt-right dataTables_paginate'p>>";

  // Breakpoints
  const BREAKPOINTS = { desktop: Infinity, lg: 1280, md: 1024, sm: 768, xs: 520 };

  const DEFAULTS = {
    dom: DOM_CHROME,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [],
    orderMulti: false,
    autoWidth: false,
    stateSave: true,
    // Responsive v2 (tanpa DataTable.use / tanpa display.*)
    responsive: {
      breakpoints: [
        { name: 'desktop', width: BREAKPOINTS.desktop },
        { name: 'lg',      width: BREAKPOINTS.lg      },
        { name: 'md',      width: BREAKPOINTS.md      },
        { name: 'sm',      width: BREAKPOINTS.sm      },
        { name: 'xs',      width: BREAKPOINTS.xs      },
      ],
      details: {
        type: 'inline',
        target: 'tr',
        renderer: function ( api, rowIdx, columns ) {
          const hidden = columns.filter(c => c.hidden);
          if (!hidden.length) return false;
          const html = hidden.map(c => `
            <div class="dt-kv">
              <div class="k">${c.title}</div>
              <div class="v">${c.data}</div>
            </div>
          `).join('');
          return `<div class="dt-details">${html}</div>`;
        }
      }
    },
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
  // TO DO : CARI GIMANA BISA NAMBAHIN IMPORT + EXPORT BUTTON, TAMBAHIN ICON MAGNIFIER 
  

  function applyChrome(dtApi) {
    const $table = $(dtApi.table().node());
    const $wrap  = $(dtApi.table().container());

    $wrap.addClass('dataTables_wrapper');
    $wrap.closest('.table-responsive, .card, .glass, .dt-card').addClass('dt-card');

    $wrap.find('.dt-top, .dt-bottom').each(function(){ $(this).addClass('grid gap-2'); });

   // ===== Modify the default search box =====
    const $filterWrap = $wrap.find('.dataTables_filter');
    const $input = $filterWrap.find('input');

    // Create a wrapper with embedded search icon
    const $searchGroup = $(`
      <div class="relative flex items-center w-full">
        <i class="fa fa-search absolute left-3 text-gray-400 pointer-events-none"></i>
      </div>
    `);

    $input
      .attr('placeholder', 'Pencarian…')
      .addClass('input input--sm pl-10 w-full') // Add left padding for icon
      .appendTo($searchGroup);

    // Replace default filter wrapper content with our layout
    $filterWrap.empty().append($searchGroup);


    // Wrap both buttons and search in a single toolbar
    const $toolbar = $('<div class="flex flex-wrap items-center justify-between gap-3 w-full"></div>');
    $toolbar.append(
      $('<div class="flex-1"></div>').append($filterWrap)
    );

    // Insert the unified toolbar after the “Tampilkan” select
    $wrap.find('.dataTables_length').after($toolbar);
    // Hook up search button click
    $btnSearch.on('click', () => {
      const api = dtApi;
      api.search($input.val()).draw();
    });


    const $length = $wrap.find('.dataTables_length select');
    $length.addClass('select--sm');

    $table.addClass('table-ui table-compact nowrap');
    $table.find('th:last-child, td:last-child').addClass('cell-actions');

    $wrap.find('.dataTables_paginate').addClass('pagination-ui');

    // Scroll-X fallback
    if (!$table.parent().hasClass('dt-scroll')) {
      $table.wrap('<div class="dt-scroll" style="overflow-x:auto; -webkit-overflow-scrolling:touch;"></div>');
    }

    // Styling child-row minimal
    const styleId = 'dt-inline-style';
    if (!document.getElementById(styleId)) {
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = `
        table.dataTable tbody tr.child td.child { padding: 12px 14px; background: rgba(2,8,23,.03); }
        [data-theme="dark"] table.dataTable tbody tr.child td.child { background: rgba(255,255,255,.03); }
        .dt-details { display:grid; gap:8px; }
        .dt-kv { display:grid; grid-template-columns: 140px 1fr; gap:8px; align-items:baseline; }
        .dt-kv .k { font-weight:700; color:#64748b; }
        .dt-kv .v { overflow-wrap:anywhere; }
        @media (max-width: ${BREAKPOINTS.xs}px){
          .dt-kv{ grid-template-columns: 1fr; }
          .dt-kv .k{ opacity:.8 }
        }
        .dataTables_wrapper .dt-top, .dataTables_wrapper .dt-bottom { padding: 6px 0; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 2px 8px; }
      `;
      document.head.appendChild(style);
    }
  }

  function getDTFromElOrJq(tableSelector) {
    // 1) coba jQuery bridge
    if ($.fn && $.fn.dataTable && $(tableSelector).length && $(tableSelector).DataTable) {
      try { return $(tableSelector).DataTable(); } catch {}
    }
    // 2) fallback ke instance vanilla yang disimpan di element
    const el = document.querySelector(tableSelector);
    if (el && el._dtInstance) return el._dtInstance;
    return null;
  }

  function initDataTables(selector='[data-dt]', opts={}) {
    const finalOpts = { ...DEFAULTS, ...opts };
    $(selector).each(function(){
      // Kalau jQuery bridge aktif, hindari double-init
      if ($.fn && $.fn.dataTable && $.fn.dataTable.isDataTable && $.fn.dataTable.isDataTable(this)) return;

      const dt = new DataTable(this, {
        ...finalOpts,
        drawCallback: function() {
          const api = this.api();
          const $wrap = $(api.table().container());
          $wrap.find('.paginate_button').attr('aria-label','Halaman');
        },
        initComplete: function() {
          applyChrome(this.api());
          const api = this.api();
          const headers = $(api.table().header()).find('th').map(function(){ return $(this).text().trim(); }).get();
          api.rows().every(function(){
            $(this.node()).find('td').each(function(i){
              const label = headers[i] || '';
              if (label) this.setAttribute('data-label', label);
            });
          });
        }
      });

      // Simpan instance di element (fallback non-jQuery)
      this._dtInstance = dt;

      // Simpan juga via jQuery data (kalau jembatan tersedia)
      try { $(this).data('dt-instance', dt); } catch {}
    });
  }

  function bindExternalSearch({ searchSelector, buttonSelector=null, tableSelector='[data-dt]', delay=300 }) {
    const input = document.querySelector(searchSelector);
    if (!input) return;

    const dt = getDTFromElOrJq(tableSelector);
    if (!dt) return;

    const run = ()=> {
      // Support vanilla & jQuery API
      if (typeof dt.search === 'function' && typeof dt.draw === 'function') {
        dt.search(input.value || '');
        dt.draw();
      } else if (dt.api) {
        const api = dt.api();
        api.search(input.value || '').draw();
      }
    };

    input.addEventListener('input', debounce(run, delay));
    if (buttonSelector) {
      const btn = document.querySelector(buttonSelector);
      if (btn) btn.addEventListener('click', e => { e.preventDefault(); run(); });
    }
  }

  function destroyDataTables(selector='[data-dt]') {
    $(selector).each(function(){
      // jQuery bridge
      if ($.fn && $.fn.dataTable && $.fn.dataTable.isDataTable && $.fn.dataTable.isDataTable(this)) {
        $(this).DataTable().destroy();
        return;
      }
      // vanilla fallback
      if (this._dtInstance && typeof this._dtInstance.destroy === 'function') {
        this._dtInstance.destroy();
        this._dtInstance = null;
      }
    });
  }

  return { initDataTables, bindExternalSearch, destroyDataTables };
})();

window.__DT_HELPERS__ = helpers;
export const { initDataTables, bindExternalSearch, destroyDataTables } = helpers;
export default helpers;
