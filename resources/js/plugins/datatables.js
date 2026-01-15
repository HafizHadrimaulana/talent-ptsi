import $ from 'jquery';
window.$ = window.jQuery = $;

import DataTable from 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-responsive';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';

const helpers = window.__DT_HELPERS__ || (() => {
  const debounce = (fn, delay = 300) => {
    let t;
    return (...a) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...a), delay);
    };
  };

  const DOM_CHROME =
    "<'u-dt-header u-flex u-flex-wrap u-items-center u-justify-between u-gap-lg u-mb-lg'\
        <'u-dt-toolbar u-flex u-items-center u-gap-md'l>\
        <'u-dt-search-wrapper'f>\
     >\
     <'u-dt-table-container'tr>\
     <'u-dt-footer u-flex u-justify-between u-items-center u-mt-lg u-pt-lg u-border-t u-border-gray-200'\
        <'u-dt-info dataTables_info'i>\
        <'u-dt-pagination-wrapper dataTables_paginate'p>\
     >";

  const SPINNER_HTML = `
    <div class="u-dt-spinner-card custom-spinner-visible">
      <div class="u-dt-loader-ring"></div>
      <div class="u-dt-loading-text">Loading Data...</div>
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
        target: 'td:not(:last-child)',
        renderer: function (api, rowIdx, columns) {
          const hidden = columns.filter(c => c.hidden);
          if (!hidden.length) return false;

          const items = hidden.map(col => {
            let val = col.data;
            if (typeof val === 'object' || val === null || val === undefined) {
              val = api.cell(rowIdx, col.columnIndex).render('display');
            }
            return `
              <div class="u-dt-detail-item">
                <div class="u-dt-detail-label">${col.title}</div>
                <div class="u-dt-detail-value">${val}</div>
              </div>
            `;
          }).join('');

          return `<div class="u-dt-details"><div class="u-dt-details-content">${items}</div></div>`;
        }
      }
    },
    language: {
      search: "",
      searchPlaceholder: "Search...",
      processing: SPINNER_HTML,
      loadingRecords: "",
      emptyTable: "Tidak ada data tersedia",
      zeroRecords: "Pencarian tidak ditemukan",
      paginate: { first: "«", last: "»", next: "›", previous: "‹" }
    }
  };

  const createCustomSearch = () => `
    <div class="u-search u-dt-search">
      <i class='bx bx-search u-search__icon'></i>
      <input type="search" class="u-search__input" placeholder="Cari data..." aria-label="Search records" />
    </div>
  `;

  function addCustomStyles() {
    if (document.getElementById('u-dt-styles')) return;

    const css = `
      .u-dt-wrapper{position:relative;container-type:inline-size;width:100%}
      .dataTables_wrapper{width:100%!important;max-width:100%!important}
      .dataTables_wrapper .dataTables_length,.dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_paginate{float:none!important}
      .dataTables_wrapper .dataTables_filter{text-align:left!important;margin:0!important}
      .dataTables_wrapper .dataTables_length{text-align:left!important;margin:0!important}

      .u-dt-header{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;width:100%}
      .u-dt-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%}
      .u-dt-toolbar{display:flex;align-items:center;gap:10px}
      .u-dt-search-wrapper{display:flex;justify-content:flex-end;flex:1;min-width:220px}
      .u-dt-search{width:100%;max-width:360px}

      .u-dt-table-container{position:relative;min-height:300px;border-radius:var(--radius-lg,16px);background:var(--surface-0,#fff);width:100%;overflow:hidden}
      .u-dt-scroll{display:block;width:100%;max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}
      .u-dt-scroll::-webkit-scrollbar{height:10px}
      .u-dt-scroll::-webkit-scrollbar-thumb{background:color-mix(in srgb,var(--muted,#64748b) 22%,transparent);border-radius:999px}
      .u-dt-scroll::-webkit-scrollbar-track{background:transparent}

      table.dataTable{width:100%!important;max-width:100%!important;margin:0!important;border-collapse:separate!important;border-spacing:0!important;table-layout:auto!important}
      table.dataTable thead th,table.dataTable tfoot th{white-space:nowrap}
      table.dataTable tbody td{vertical-align:middle}
      table.dataTable.no-footer{border-bottom:0!important}

      .dataTables_wrapper .dataTables_processing,
      .dataTables_wrapper .dt-processing{
        position:absolute!important;
        inset:0!important;
        width:100%!important;height:100%!important;
        margin:0!important;padding:0!important;
        transform:none!important;
        background:var(--glass-bg,rgba(255,255,255,.75))!important;
        backdrop-filter:blur(6px) saturate(160%)!important;
        -webkit-backdrop-filter:blur(6px) saturate(160%)!important;
        display:flex!important;
        align-items:center!important;
        justify-content:center!important;
        z-index:1000!important;
        border:none!important;
        box-shadow:none!important;
        opacity:1!important;
        visibility:visible!important;
      }
      .dataTables_wrapper .dataTables_processing::after,
      .dataTables_wrapper .dataTables_processing::before,
      .dataTables_wrapper .dt-processing::after,
      .dataTables_wrapper .dt-processing::before{display:none!important;content:none!important}
      .dataTables_wrapper .dataTables_processing > div:not(.u-dt-spinner-card),
      .dataTables_wrapper .dt-processing > div:not(.u-dt-spinner-card){display:none!important}

      .u-dt-spinner-card{
        display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;
        padding:22px 28px;border-radius:20px;
        background:var(--card,#fff);
        border:1px solid var(--border,rgba(0,0,0,.06));
        box-shadow:0 24px 48px -14px rgba(0,0,0,.18);
        animation:u-dt-pop .28s cubic-bezier(.22,1,.36,1);
        z-index:1001;
      }
      .u-dt-loader-ring{
        width:32px;height:32px;border-radius:50%;
        border:3.5px solid color-mix(in srgb,var(--accent,#4f46e5) 16%,transparent);
        border-top-color:var(--accent,#4f46e5);
        animation:u-dt-spin .78s linear infinite;
      }
      .u-dt-loading-text{
        font-size:.75rem;font-weight:800;letter-spacing:.08em;
        color:var(--muted,#64748b);text-transform:uppercase;
      }
      @keyframes u-dt-spin{to{transform:rotate(360deg)}}
      @keyframes u-dt-pop{from{transform:scale(.94);opacity:0}to{transform:scale(1);opacity:1}}

      .u-dt-wrapper.is-loading table.dataTable>tbody{opacity:0!important;pointer-events:none}
      .u-dt-wrapper.is-loading table.dataTable>tbody>tr>td.dataTables_empty,
      .u-dt-wrapper.is-loading table.dataTable>tbody>tr>td.dt-empty{display:none!important}

      .dataTables_length label{display:flex;align-items:center;gap:10px;white-space:nowrap}
      .dataTables_length select{
        appearance:none;-webkit-appearance:none;
        padding:10px 34px 10px 12px;
        border-radius:14px;
        border:1px solid color-mix(in srgb,var(--text,#0f172a) 10%,transparent);
        background:color-mix(in srgb,var(--surface-0,#fff) 78%,transparent);
        color:var(--text,#0f172a);
        outline:none;
        line-height:1.1;
      }
      .dataTables_length select:focus{
        border-color:color-mix(in srgb,var(--accent,#4f46e5) 55%,transparent);
        box-shadow:0 0 0 4px color-mix(in srgb,var(--accent,#4f46e5) 16%,transparent);
      }
      .dataTables_length label{position:relative}
      .dataTables_length label:after{
        content:'';
        position:absolute;
        right:10px;
        width:16px;height:16px;
        pointer-events:none;
        background:currentColor;
        opacity:.55;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M5.5 7.5l4.5 4.8 4.5-4.8' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M5.5 7.5l4.5 4.8 4.5-4.8' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;
        top:50%;
        transform:translateY(-50%);
      }

      .u-dt-details{background:color-mix(in srgb,var(--surface-1,#f8fafc) 60%,transparent);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px;margin:12px 0}
      .u-dt-detail-item{display:flex;justify-content:space-between;align-items:start;border-bottom:1px dashed var(--border,#e2e8f0);padding:8px 0;gap:16px}
      .u-dt-detail-item:last-child{border-bottom:none}
      .u-dt-detail-label{font-weight:700;color:var(--muted,#64748b);font-size:.82rem;flex-shrink:0;width:40%}
      .u-dt-detail-value{text-align:right;font-size:.9rem;color:var(--text,#0f172a);word-break:break-word;font-weight:600}

      .dataTables_paginate{display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap}
      .dataTables_paginate .u-btn.current{background:var(--accent,#4f46e5);color:#fff;border-color:var(--accent,#4f46e5);box-shadow:0 4px 12px var(--accent-ghost,rgba(79,70,229,.2))}

      table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before{
        content:'+';
        background-color:var(--accent,#4f46e5);
        border:none;
        box-shadow:0 2px 4px rgba(0,0,0,.12);
        top:50%;
        transform:translateY(-50%);
        line-height:16px;width:18px;height:18px;border-radius:7px;
        display:inline-flex;align-items:center;justify-content:center;
      }
      table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control:before{content:'-';background-color:var(--danger,#ef4444)}

      @media (max-width:768px){
        .u-dt-header{flex-direction:column;align-items:stretch}
        .u-dt-search-wrapper{justify-content:stretch;min-width:unset;width:100%}
        .u-dt-search{max-width:100%!important}
        .u-dt-toolbar{width:100%;justify-content:space-between}
        .dataTables_length{width:100%}
        .dataTables_length label{width:100%;justify-content:space-between}
        .dataTables_length select{min-width:120px}
        .u-dt-footer{flex-direction:column;align-items:stretch;text-align:center}
        .dataTables_paginate{justify-content:center;width:100%}
        .u-dt-wrapper table.dataTable{width:100%!important}
      }
    `;

    const s = document.createElement('style');
    s.id = 'u-dt-styles';
    s.textContent = css;
    document.head.appendChild(s);
  }

  function applyCustomChrome(api) {
    const $wrap = $(api.table().container());
    $wrap.addClass('u-dt-wrapper');

    const $filter = $wrap.find('.dataTables_filter');
    if (!$wrap.find('.u-dt-search').length) $filter.html(createCustomSearch());

    const $input = $wrap.find('.u-dt-search input');
    $filter.find('input').off();
    $input.on('input', debounce(() => api.search($input.val()).draw(), 400));

    const $paginate = $wrap.find('.dataTables_paginate');
    $paginate.find('a').addClass('u-btn u-btn--sm u-btn--outline u-mx-xs');

    addCustomStyles();
  }

  function initDataTables(selector, opts = {}) {
    $(selector).each(function () {
      const $table = $(this);
      if ($.fn.dataTable.isDataTable(this)) $table.DataTable().destroy();
      if (!$table.parent().hasClass('u-scroll-x')) $table.wrap('<div class="u-scroll-x u-dt-scroll"></div>');

      const config = { ...DEFAULTS, ...opts };
      if (config.serverSide) config.deferRender = false;

      $table.off('preInit.dt u-dt-preinit');
      $table.on('preInit.dt u-dt-preinit', function () {
        const $wrap = $(this).closest('.dataTables_wrapper');
        if ($wrap.length) $wrap.addClass('is-loading');
      });

      const dt = new DataTable(this, {
        ...config,
        initComplete: function () {
          const api = this.api();
          applyCustomChrome(api);
          if (opts.initComplete) opts.initComplete.call(this);
          $(api.table().container()).removeClass('is-loading');
          setTimeout(() => {
            try { api.columns.adjust(); } catch (e) {}
            try { api.responsive && api.responsive.recalc && api.responsive.recalc(); } catch (e) {}
          }, 0);
        },
        drawCallback: function () {
          const api = this.api();
          applyCustomChrome(api);
          const $p = $(api.table().container()).find('.dataTables_paginate a');
          $p.addClass('u-btn u-btn--sm u-btn--outline').filter('.current').removeClass('u-btn--outline').addClass('u-btn--brand');
        }
      });

      dt.on('processing.dt', function (e, settings, processing) {
        const $wrap = $(dt.table().container());
        if (processing) $wrap.addClass('is-loading');
        else $wrap.removeClass('is-loading');
      });

      dt.on('preXhr.dt', () => $(dt.table().container()).addClass('is-loading'));
      dt.on('xhr.dt error.dt', () => $(dt.table().container()).removeClass('is-loading'));
    });
  }

  function bindExternalSearch({ searchSelector, buttonSelector = null, tableSelector = '[data-dt]', delay = 400 }) {
    const input = document.querySelector(searchSelector);
    if (!input) return;

    let api;
    try {
      if ($.fn.dataTable.isDataTable(tableSelector)) api = $(tableSelector).DataTable();
    } catch (e) {}

    if (!api) return;

    const performSearch = () => api.search(input.value.trim()).draw();

    input.addEventListener('input', debounce(performSearch, delay));
    if (buttonSelector) {
      const btn = document.querySelector(buttonSelector);
      if (btn) btn.addEventListener('click', (e) => {
        e.preventDefault();
        performSearch();
      });
    }
  }

  return { initDataTables, bindExternalSearch };
})();

export const { initDataTables, bindExternalSearch } = helpers;
