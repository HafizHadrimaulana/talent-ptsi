// resources/js/dt-helpers.js
import $ from 'jquery';
window.$ = window.jQuery = $;

import DataTable from 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';

import 'datatables.net-responsive';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';

/**
 * DataTables Helpers — iOS LiquidGlass chrome
 * - Custom DOM (header/footer, gradient header, glass scroll)
 * - Custom search form (Search/Clear)
 * - Responsive details grid
 * - Mobile-friendly spacing
 * - Safe to call multiple times
 */
const helpers = window.__DT_HELPERS__ || (() => {
  // ---------- utils ----------
  const debounce = (fn, delay = 300) => {
    let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), delay); };
  };

  // ---------- DOM template ----------
  const DOM_CHROME =
    "<'u-dt-header u-flex u-flex-wrap u-items-center u-justify-between u-gap-lg u-mb-lg'\
       <'u-dt-toolbar u-flex u-items-center u-gap-md'l>\
       <'u-dt-search-wrapper'f>\
     >\
     t\
     <'u-dt-footer u-flex u-justify-between u-items-center u-mt-lg u-pt-lg u-border-t u-border-gray-200'\
       <'u-dt-info dataTables_info'i>\
       <'u-dt-pagination-wrapper dataTables_paginate'p>\
     >";

  // ---------- breakpoints ----------
  const BREAKPOINTS = { desktop: Infinity, lg: 1280, md: 1024, sm: 768, xs: 520 };

  // ---------- defaults ----------
  const DEFAULTS = {
    dom: DOM_CHROME,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [],
    orderMulti: false,
    autoWidth: false,
    stateSave: true,
    deferRender: true,
    processing: true,
    responsive: {
      breakpoints: [
        { name: 'desktop', width: BREAKPOINTS.desktop },
        { name: 'lg', width: BREAKPOINTS.lg },
        { name: 'md', width: BREAKPOINTS.md },
        { name: 'sm', width: BREAKPOINTS.sm },
        { name: 'xs', width: BREAKPOINTS.xs },
      ],
      details: {
        type: 'column',
        target: -1,
        renderer: function (api, rowIdx, columns) {
          const hidden = columns.filter(c => c.hidden);
          if (!hidden.length) return false;

          const items = hidden.map(col => `
            <div class="u-dt-detail-item">
              <div class="u-dt-detail-label">${col.title}</div>
              <div class="u-dt-detail-value">${col.data ?? '—'}</div>
            </div>
          `).join('');

          return `<div class="u-dt-details"><div class="u-dt-details-content">${items}</div></div>`;
        }
      }
    },
    language: {
      search: "", // hide default label
      searchPlaceholder: "Search Everything...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      infoEmpty: "No entries available",
      emptyTable: "No data available in table",
      zeroRecords: "No matching records found",
      paginate: { first: "«", last: "»", next: "›", previous: "‹" },
      processing: '<div class="u-dt-processing"><div class="u-dt-spinner"></div>Loading data…</div>',
    }
  };

  // ---------- custom search (liquid-glass) ----------
  const createCustomSearch = () => `
    <form class="u-search u-dt-search" role="search" aria-label="Table search" style="max-width:520px;">
      <svg class="u-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>
      <input type="search" class="u-search__input" placeholder="Search everything..." aria-label="Search table records" />
      <button class="u-btn u-btn--brand u-btn--sm" type="submit">Search</button>
      <button class="u-btn u-btn--outline u-btn--sm" type="button" title="Clear">Clear</button>
    </form>
  `;

  // ---------- styles injection (scoped to DT chrome only) ----------
  function addCustomStyles() {
    const id = 'u-dt-custom-styles';
    if (document.getElementById(id)) return;

    const css = `
      /* wrapper & chrome */
      .u-dt-wrapper{position:relative}
      .u-dt-container{border-radius:var(--radius-xl);overflow:hidden}
      .u-dt-header{padding:var(--space-lg);background:var(--surface-0);border-bottom:1px solid var(--border)}
      .u-dt-footer{padding:0 var(--space-lg) var(--space-lg);background:var(--surface-0)}

      /* glass scroll area */
      .u-dt-scroll{border-radius:var(--radius-lg);overflow:hidden;margin:0 var(--space-lg)}

      /* table look (uses your tokens) */
      .u-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--surface-0);font-size:.875rem;border-radius:var(--radius-lg);overflow:hidden}
      .u-table thead th{
        position:sticky;top:0;z-index:1;padding:1rem .8rem;font-weight:700;letter-spacing:.05em;color:#fff;
        background:linear-gradient(135deg,#1F337E 0%,#49D4A9 100%)!important;border:none;text-transform:uppercase;font-size:.8rem
      }
      .u-table tbody td{padding:.875rem .8rem;border-bottom:1px solid color-mix(in srgb,var(--border) 30%, transparent);background:var(--surface-0);transition:all .2s ease}
      .u-table tbody tr:hover td{background:var(--surface-1)}
      .u-dt-actions{text-align:right;white-space:nowrap}

      /* length/info/pagination */
      .u-dt-length{display:flex;align-items:center;gap:var(--space-sm)}
      .u-dt-length-label{margin:0;font-size:.875rem;color:var(--muted);font-weight:600}
      .u-dt-pagination{display:flex;gap:var(--space-xs)}
      .u-dt-pagination .u-btn{min-height:32px;min-width:32px;padding:0;display:flex;align-items:center;justify-content:center}
      .u-dt-pagination .current{background:var(--accent);color:#fff;border-color:var(--accent)}
      .u-dt-pagination .u-btn.disabled{opacity:.5;cursor:not-allowed}

      /* processing */
      .dataTables_processing{
        background:var(--surface-0)!important;color:var(--accent)!important;border-radius:var(--radius-xl)!important;
        backdrop-filter:blur(20px)!important;box-shadow:var(--shadow-lg)!important;padding:2rem!important;font-size:1rem!important;font-weight:600!important
      }
      .u-dt-processing{display:flex;align-items:center;gap:var(--space-md);justify-content:center}
      .u-dt-spinner{width:24px;height:24px;border:3px solid color-mix(in srgb,var(--accent) 20%, transparent);border-top:3px solid var(--accent);border-radius:50%;animation:u-dt-spin 1s linear infinite}
      @keyframes u-dt-spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}

      /* responsive details */
      .u-dt-details{background:var(--surface-1);border-radius:var(--radius-md);margin:var(--space-sm) 0;padding:var(--space-md)}
      .u-dt-detail-item{display:grid;grid-template-columns:120px 1fr;gap:var(--space-sm);align-items:start}
      .u-dt-detail-label{font-weight:600;font-size:.875rem;color:var(--muted)}
      .u-dt-detail-value{font-size:.875rem;overflow-wrap:anywhere}

      /* mobile */
      @media (max-width:768px){
        .u-dt-header{flex-direction:column;align-items:stretch;gap:var(--space-md);padding:var(--space-md)}
        .u-dt-scroll{margin:0 var(--space-md)}
        .u-dt-footer{flex-direction:column;gap:var(--space-md);text-align:center;padding:0 var(--space-md) var(--space-md)}
        .u-dt-detail-item{grid-template-columns:1fr;gap:var(--space-xs)}
        .u-table thead th,.u-table tbody td{padding:.75rem .5rem}
      }
      [data-theme="dark"] .u-dt-details{background:var(--surface-2)}
    `;
    const style = document.createElement('style');
    style.id = id; style.textContent = css;
    document.head.appendChild(style);
  }

  // ---------- apply chrome ----------
  function applyCustomChrome(dtApi) {
    const $table = $(dtApi.table().node());
    const $wrap  = $(dtApi.table().container());

    $wrap.addClass('u-dt-wrapper');
    $wrap.closest('.dt-wrapper, .u-card, .card-glass').addClass('u-dt-container');

    // replace default filter with custom form
    const $filterWrap = $wrap.find('.dataTables_filter');
    if ($filterWrap.length) {
      $filterWrap.hide();
      if (!$wrap.find('.u-dt-search').length) $filterWrap.before(createCustomSearch());
    }

    // wire custom search
    const $customSearch = $wrap.find('.u-dt-search');
    const $searchInput  = $customSearch.find('input[type="search"]');
    const $searchBtn    = $customSearch.find('.u-btn--brand');
    const $clearBtn     = $customSearch.find('.u-btn--outline');

    const performSearch = () => dtApi.search($searchInput.val()).draw();

    $customSearch.on('submit', (e) => { e.preventDefault(); performSearch(); });
    $searchInput.on('input', debounce(performSearch, 400));
    $clearBtn.on('click', (e) => { e.preventDefault(); $searchInput.val('').focus(); performSearch(); });

    // length
    const $length = $wrap.find('.dataTables_length');
    $length.addClass('u-dt-length');
    $length.find('select').addClass('u-input u-input--sm').attr({ 'aria-label': 'Rows per page' });
    $length.find('label').addClass('u-dt-length-label');

    // pagination
    const $paginate = $wrap.find('.dataTables_paginate');
    $paginate.addClass('u-dt-pagination');
    $paginate.find('a').each(function () {
      const $btn = $(this);
      $btn.addClass('u-btn u-btn--sm');
      if (!$btn.hasClass('current')) $btn.addClass('u-btn--outline');
    });

    // info
    $wrap.find('.dataTables_info').addClass('u-dt-info u-text-sm u-muted');

    // table base classes
    $table.addClass('u-table u-table-mobile');

    // actions column helper
    $table.find('th.cell-actions, td.cell-actions').addClass('u-dt-actions');

    // glass scroll container
    if (!$table.parent().hasClass('u-dt-scroll')) {
      $table.wrap('<div class="u-dt-scroll u-scroll-x"></div>');
    }

    addCustomStyles();
  }

  // ---------- helpers ----------
  function getDTFromElOrJq(tableSelector) {
    if ($.fn?.dataTable && $(tableSelector).length) {
      try { return $(tableSelector).DataTable(); } catch (_) {}
    }
    const el = typeof tableSelector === 'string' ? document.querySelector(tableSelector) : tableSelector;
    if (el?._dtInstance) return el._dtInstance;
    return null;
  }

  // ---------- init ----------
  function initDataTables(selector = '[data-dt]', opts = {}) {
    const finalOpts = {
      ...DEFAULTS,
      ...opts,
      columnDefs: [
        {
          targets: '_all',
          defaultContent: '—',
          createdCell: function (td, cellData, rowData, row, col) {
            // add data-label for mobile stacked view
            const api = new DataTable.Api(this);
            const header = api.column(col).header();
            if (header) {
              const label = header.textContent.trim();
              if (label) td.setAttribute('data-label', label);
            }
          }
        },
        ...(opts.columnDefs || [])
      ]
    };

    $(selector).each(function () {
      const $table = $(this);
      if ($.fn?.dataTable?.isDataTable?.(this)) return;

      try {
        const dt = new DataTable(this, {
          ...finalOpts,
          drawCallback: function () {
            const api = this.api();
            applyCustomChrome(api);

            // normalize paginate buttons after draw
            $table.closest('.dataTables_wrapper')
              .find('.dataTables_paginate a')
              .each(function () {
                const $btn = $(this);
                $btn.addClass('u-btn u-btn--sm');
                if ($btn.hasClass('current')) {
                  $btn.removeClass('u-btn--outline').addClass('u-btn--brand');
                } else {
                  $btn.addClass('u-btn--outline').removeClass('u-btn--brand');
                }
              });
          },
          initComplete: function () {
            const api = this.api();
            applyCustomChrome(api);
            this._dtInstance = api;
          }
        });

        this._dtInstance = dt;
      } catch (e) {
        console.error('DataTables init error:', e, this);
      }
    });
  }

  // ---------- external search binder ----------
  function bindExternalSearch({ searchSelector, buttonSelector = null, tableSelector = '[data-dt]', delay = 400 }) {
    const input = document.querySelector(searchSelector);
    if (!input) { console.warn('Search input not found:', searchSelector); return; }

    const dt = getDTFromElOrJq(tableSelector);
    if (!dt) { console.warn('DataTable not found for:', tableSelector); return; }

    const performSearch = () => {
      const term = input.value.trim();
      try {
        if (typeof dt.search === 'function') dt.search(term).draw();
        else if (dt.api) dt.api().search(term).draw();
        else { dt.search(term); dt.draw(); }
      } catch (e) { console.error('Search error:', e); }
    };

    input.addEventListener('input', debounce(performSearch, delay));

    if (buttonSelector) {
      const button = document.querySelector(buttonSelector);
      if (button) button.addEventListener('click', (e) => { e.preventDefault(); performSearch(); });
    }

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { input.value = ''; performSearch(); input.blur(); }
    });
  }

  // ---------- destroy ----------
  function destroyDataTables(selector = '[data-dt]') {
    $(selector).each(function () {
      try {
        if ($.fn?.dataTable?.isDataTable?.(this)) {
          $(this).DataTable().destroy();
          return;
        }
        if (this._dtInstance) {
          if (typeof this._dtInstance.destroy === 'function') this._dtInstance.destroy();
          this._dtInstance = null;
        }
      } catch (e) { console.error('Destroy DT error:', e, this); }
    });
  }

  // ---------- minimal modal helpers (optional) ----------
  function initModalHandling() {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.u-modal:not([hidden])');
        if (openModals.length) {
          const top = openModals[openModals.length - 1];
          top.hidden = true; document.body.classList.remove('modal-open');
        }
      }
    });

    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('u-modal')) {
        e.target.hidden = true; document.body.classList.remove('modal-open');
      }
      const closeBtn = e.target.closest('[data-modal-close]');
      if (closeBtn) {
        const modal = closeBtn.closest('.u-modal');
        if (modal) { modal.hidden = true; document.body.classList.remove('modal-open'); }
      }
    });

    document.addEventListener('click', (e) => {
      const openBtn = e.target.closest('[data-modal-open]');
      if (openBtn) {
        const id = openBtn.getAttribute('data-modal-open');
        const modal = document.getElementById(id);
        if (modal) {
          modal.hidden = false; document.body.classList.add('modal-open');
          const firstInput = modal.querySelector('input, select, textarea');
          if (firstInput) setTimeout(() => firstInput.focus(), 100);
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalHandling);
  } else {
    initModalHandling();
  }

  return { initDataTables, bindExternalSearch, destroyDataTables, getDTFromElOrJq, initModalHandling };
})();

window.__DT_HELPERS__ = helpers;
export const { initDataTables, bindExternalSearch, destroyDataTables, getDTFromElOrJq, initModalHandling } = helpers;
export default helpers;
