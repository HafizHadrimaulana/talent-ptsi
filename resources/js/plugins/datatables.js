import $ from 'jquery';
window.$ = window.jQuery = $;
import DataTable from 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-responsive';

const helpers = window.__DT_HELPERS__ || (() => {
  const debounce = (fn, delay = 300) => {
    let t;
    return (...a) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...a), delay);
    };
  };

  const DOM_CHROME = "<'u-dt-wrapper'<'u-dt-header'<'u-dt-len'l><'u-dt-search'f>><'u-dt-tbl'tr><'u-dt-footer'<'u-dt-info'i><'u-dt-pg'p>>>";

  const SPINNER_HTML = `
    <div class="u-dt-loader-container">
      <div class="u-dt-liquid-spinner">
        <div class="drop"></div>
        <div class="drop"></div>
        <div class="drop"></div>
      </div>
      <div class="u-dt-loading-text">Loading...</div>
    </div>
  `;

  const DEFAULTS = {
    dom: DOM_CHROME,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    processing: true,
    serverSide: false,
    autoWidth: false,
    responsive: {
        details: {
            type: 'column',
            target: 'tr',
            renderer: function (api, rowIdx, columns) {
                let data = $.map(columns, function (col, i) {
                    return col.hidden ?
                        `<li class="u-dt-child-item" data-dtr-index="${col.columnIndex}">
                            <span class="u-dt-child-title">${col.title}</span>
                            <span class="u-dt-child-data">${col.data}</span>
                         </li>` : '';
                }).join('');
                return data ? `<ul class="u-dt-child-row">${data}</ul>` : false;
            }
        }
    },
    language: {
      search: "",
      searchPlaceholder: "Search records...",
      processing: SPINNER_HTML,
      lengthMenu: "_MENU_ per page",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      infoEmpty: "Showing 0 to 0 of 0 entries",
      infoFiltered: "(filtered from _MAX_ total entries)",
      zeroRecords: "No matching records found",
      emptyTable: "No data available in table",
      paginate: { first: "«", last: "»", next: "›", previous: "‹" }
    }
  };

  function initDataTables(selector, opts = {}) {
    $(selector).each(function() {
      const $table = $(this);
      
      if ($.fn.dataTable.isDataTable(this)) {
        $table.DataTable().destroy();
      }
      
      // FIX ERROR JSON: Hanya tambahkan header CSRF jika user menggunakan opsi AJAX
      if (opts.ajax) {
          const token = document.querySelector('meta[name="csrf-token"]')?.content;
          let ajaxConfig = opts.ajax;
          if (typeof ajaxConfig === 'string') ajaxConfig = { url: ajaxConfig };
          
          ajaxConfig.headers = {
              'X-CSRF-TOKEN': token,
              'X-Requested-With': 'XMLHttpRequest',
              ...ajaxConfig.headers
          };
          opts.ajax = ajaxConfig;
      }

      const config = { ...DEFAULTS, ...opts };
      // Pastikan serverSide false jika tidak diminta (default aman)
      if (config.serverSide) config.deferRender = false;

      const dt = new DataTable(this, {
        ...config,
        initComplete: function() {
          const api = this.api();
          if (opts.initComplete) opts.initComplete.call(this);
          
          const wrapper = $(api.table().container());
          wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
          wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
          
          // Re-adjust columns untuk menghindari header kepotong saat init
          setTimeout(() => {
             api.columns.adjust();
             if(api.responsive) api.responsive.recalc();
          }, 300);
        },
        drawCallback: function() {
          const wrapper = $(this.api().table().container());
          wrapper.find('.dataTables_paginate .paginate_button')
                 .addClass('u-btn u-btn--sm u-btn--ghost')
                 .filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
        }
      });

      dt.on('processing.dt', function(e, settings, processing) {
        const wrapper = $(dt.table().container());
        if (processing) wrapper.addClass('is-loading');
        else wrapper.removeClass('is-loading');
      });
    });
  }

  function bindExternalSearch({ searchSelector, buttonSelector = null, tableSelector = '[data-dt]', delay = 400 }) {
    const input = document.querySelector(searchSelector);
    if (!input) return;
    let api;
    try { if ($.fn.dataTable.isDataTable(tableSelector)) api = $(tableSelector).DataTable(); } catch (e) {}
    if (!api) return;
    const performSearch = () => api.search(input.value.trim()).draw();
    input.addEventListener('input', debounce(performSearch, delay));
    if (buttonSelector) {
      const btn = document.querySelector(buttonSelector);
      if (btn) btn.addEventListener('click', (e) => { e.preventDefault(); performSearch(); });
    }
  }

  return { initDataTables, bindExternalSearch };
})();

export const { initDataTables, bindExternalSearch } = helpers;