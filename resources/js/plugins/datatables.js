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
    "<'u-dt-header u-flex u-flex-wrap u-items-center u-justify-between u-gap-lg u-mb-lg'" +
      "<'u-dt-toolbar u-flex u-items-center u-gap-md'l>" +
      "<'u-dt-search-wrapper'f>" +
    ">" +
    "t" +
    "<'u-dt-footer u-flex u-justify-between u-items-center u-mt-lg u-pt-lg u-border-t u-border-gray-200'" +
      "<'u-dt-info dataTables_info'i>" +
      "<'u-dt-pagination-wrapper dataTables_paginate'p>" +
    ">";

  const BREAKPOINTS = { 
    desktop: Infinity, 
    lg: 1280, 
    md: 1024, 
    sm: 768, 
    xs: 520 
  };

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
          
          const data = hidden.map(col => `
            <div class="u-dt-detail-item">
              <div class="u-dt-detail-label">${col.title}</div>
              <div class="u-dt-detail-value">${col.data || '—'}</div>
            </div>
          `).join('');
          
          return `
            <div class="u-dt-details">
              <div class="u-dt-details-content">
                ${data}
              </div>
            </div>
          `;
        }
      }
    },
    language: {
      search: "",
      searchPlaceholder: "Search records...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      infoEmpty: "No entries available",
      emptyTable: "No data available in table",
      zeroRecords: "No matching records found",
      paginate: { 
        first: "«", 
        last: "»", 
        next: "›", 
        previous: "‹" 
      },
      processing: '<div class="u-dt-processing"><div class="u-dt-spinner"></div>Loading data...</div>',
    }
  };

  function createCustomSearch() {
    return `
      <form class="u-search u-dt-search" style="max-width: 520px;">
        <svg class="u-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        <input type="search" class="u-search__input" placeholder="Search everything..." />
        <button class="u-btn u-btn--brand u-btn--sm" type="submit">Search</button>
        <button class="u-btn u-btn--outline u-btn--sm" type="button" title="Clear">Clear</button>
      </form>
    `;
  }

  function applyCustomChrome(dtApi) {
    const $table = $(dtApi.table().node());
    const $wrap = $(dtApi.table().container());

    // Add wrapper classes
    $wrap.addClass('u-dt-wrapper');
    $wrap.closest('.dt-wrapper, .u-card, .card-glass').addClass('u-dt-container');
    
    // Replace default search with custom search
    const $filterWrap = $wrap.find('.dataTables_filter');
    $filterWrap.hide().before(createCustomSearch());
    
    const $customSearch = $wrap.find('.u-dt-search');
    const $searchInput = $customSearch.find('input');
    const $searchBtn = $customSearch.find('.u-btn--brand');
    const $clearBtn = $customSearch.find('.u-btn--outline');

    // Connect search functionality
    const performSearch = () => {
      dtApi.search($searchInput.val()).draw();
    };

    $searchInput.on('input', debounce(performSearch, 400));
    $searchBtn.on('click', (e) => {
      e.preventDefault();
      performSearch();
    });
    
    $clearBtn.on('click', (e) => {
      e.preventDefault();
      $searchInput.val('').focus();
      performSearch();
    });

    // Style length menu
    const $length = $wrap.find('.dataTables_length');
    $length.addClass('u-dt-length');
    $length.find('select').addClass('u-input u-input--sm');
    $length.find('label').addClass('u-dt-length-label');

    // Style pagination
    const $paginate = $wrap.find('.dataTables_paginate');
    $paginate.addClass('u-dt-pagination');
    $paginate.find('.paginate_button')
      .addClass('u-btn u-btn--sm')
      .not('.current')
      .addClass('u-btn--outline');

    $paginate.find('.current').addClass('u-btn--brand');

    // Style info
    const $info = $wrap.find('.dataTables_info');
    $info.addClass('u-dt-info u-text-sm u-muted');

    // Add table styling
    $table.addClass('u-table u-table-mobile');
    
    // Mark action columns
    $table.find('th.cell-actions, td.cell-actions').addClass('u-dt-actions');

    // Ensure scroll container
    if (!$table.parent().hasClass('u-dt-scroll')) {
      $table.wrap('<div class="u-dt-scroll u-scroll-x"></div>');
    }

    // Add custom styles
    addCustomStyles();
  }

  function addCustomStyles() {
    const styleId = 'u-dt-custom-styles';
    if (!document.getElementById(styleId)) {
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = `
        /* DataTables Wrapper */
        .u-dt-wrapper {
          position: relative;
        }
        
        .u-dt-container {
          border-radius: var(--radius-xl);
          overflow: hidden;
        }
        
        /* Header */
        .u-dt-header {
          padding: var(--space-lg);
          background: var(--surface-0);
          border-bottom: 1px solid var(--border);
        }
        
        /* Custom Search */
        .u-dt-search {
          display: flex;
          align-items: center;
          gap: var(--space-sm);
          max-width: 520px;
        }
        
        .u-dt-search .u-search__input {
          flex: 1;
        }
        
        /* Toolbar */
        .u-dt-toolbar {
          flex-shrink: 0;
        }
        
        .u-dt-length {
          display: flex;
          align-items: center;
          gap: var(--space-sm);
        }
        
        .u-dt-length-label {
          margin: 0;
          font-size: 0.875rem;
          color: var(--muted);
          font-weight: 600;
        }
        
        /* Footer */
        .u-dt-footer {
          padding: 0 var(--space-lg) var(--space-lg);
          background: var(--surface-0);
        }
        
        /* Pagination */
        .u-dt-pagination {
          display: flex;
          gap: var(--space-xs);
        }
        
        .u-dt-pagination .u-btn {
          min-height: 32px;
          min-width: 32px;
          padding: 0;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        .u-dt-pagination .u-btn--brand {
          background: var(--accent);
          color: white;
          border-color: var(--accent);
        }
        
        .u-dt-pagination .u-btn.disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }
        
        /* Table Headers - Surveyor Color Palette */
        .u-table thead th {
          background: linear-gradient(135deg, #1F337E 0%, #49D4A9 100%) !important;
          color: white !important;
          font-weight: 700;
          font-size: 0.8rem;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          border: none;
          padding: 1rem 0.8rem;
          position: relative;
        }
        
        .u-table thead th:after {
          content: '';
          position: absolute;
          bottom: 0;
          left: 0.8rem;
          right: 0.8rem;
          height: 2px;
          background: rgba(255,255,255,0.3);
        }
        
        .u-table tbody td {
          padding: 0.875rem 0.8rem;
          border-bottom: 1px solid color-mix(in srgb, var(--border) 30%, transparent);
          background: var(--surface-0);
          transition: all 0.2s ease;
        }
        
        .u-table tbody tr:hover td {
          background: var(--surface-1);
        }
        
        /* Scroll Container */
        .u-dt-scroll {
          border-radius: var(--radius-lg);
          overflow: hidden;
          margin: 0 var(--space-lg);
        }
        
        /* Action Cells */
        .u-dt-actions {
          text-align: right;
          white-space: nowrap;
        }
        
        /* Enhanced Loading Animation */
        .dataTables_processing {
          background: var(--surface-0) !important;
          color: var(--accent) !important;
          border: 1px solid var(--accent-border) !important;
          border-radius: var(--radius-xl) !important;
          backdrop-filter: blur(20px) !important;
          box-shadow: var(--shadow-lg) !important;
          border: none !important;
          padding: 2rem !important;
          font-size: 1rem !important;
          font-weight: 600 !important;
        }
        
        .u-dt-processing {
          display: flex;
          align-items: center;
          gap: var(--space-md);
          justify-content: center;
        }
        
        .u-dt-spinner {
          width: 24px;
          height: 24px;
          border: 3px solid color-mix(in srgb, var(--accent) 20%, transparent);
          border-top: 3px solid var(--accent);
          border-radius: 50%;
          animation: u-dt-spin 1s linear infinite;
        }
        
        @keyframes u-dt-spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
        
        /* Responsive Details */
        .u-dt-details {
          background: var(--surface-1);
          border-radius: var(--radius-md);
          margin: var(--space-sm) 0;
          padding: var(--space-md);
        }
        
        .u-dt-detail-item {
          display: grid;
          grid-template-columns: 120px 1fr;
          gap: var(--space-sm);
          align-items: start;
        }
        
        .u-dt-detail-label {
          font-weight: 600;
          font-size: 0.875rem;
          color: var(--muted);
        }
        
        .u-dt-detail-value {
          font-size: 0.875rem;
          overflow-wrap: anywhere;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
          .u-dt-header {
            flex-direction: column;
            align-items: stretch;
            gap: var(--space-md);
            padding: var(--space-md);
          }
          
          .u-dt-search {
            max-width: 100%;
            flex-direction: column;
            align-items: stretch;
          }
          
          .u-dt-search .u-btn {
            flex: 1;
          }
          
          .u-dt-scroll {
            margin: 0 var(--space-md);
          }
          
          .u-dt-footer {
            flex-direction: column;
            gap: var(--space-md);
            text-align: center;
            padding: 0 var(--space-md) var(--space-md);
          }
          
          .u-dt-detail-item {
            grid-template-columns: 1fr;
            gap: var(--space-xs);
          }
          
          .u-dt-detail-label {
            font-size: 0.8rem;
            opacity: 0.8;
          }
        }
        
        @media (max-width: 520px) {
          .u-dt-toolbar {
            width: 100%;
            justify-content: space-between;
          }
          
          .u-dt-length {
            flex: 1;
          }
          
          .u-dt-pagination {
            flex-wrap: wrap;
            justify-content: center;
          }
          
          .u-table thead th,
          .u-table tbody td {
            padding: 0.75rem 0.5rem;
          }
        }
        
        /* Dark theme adjustments */
        [data-theme="dark"] .u-dt-details {
          background: var(--surface-2);
        }
        
        [data-theme="dark"] .dataTables_processing {
          background: var(--surface-1) !important;
          border: 1px solid var(--accent-border) !important;
        }
      `;
      document.head.appendChild(style);
    }
  }

  function getDTFromElOrJq(tableSelector) {
    if (window.$.fn?.dataTable && $(tableSelector).length) {
      try {
        return $(tableSelector).DataTable();
      } catch (e) {
        console.warn('jQuery DataTable not found:', e);
      }
    }
    
    const el = typeof tableSelector === 'string' 
      ? document.querySelector(tableSelector)
      : tableSelector;
      
    if (el?._dtInstance) {
      return el._dtInstance;
    }
    
    return null;
  }

  function initDataTables(selector = '[data-dt]', opts = {}) {
    const finalOpts = { 
      ...DEFAULTS, 
      ...opts,
      columnDefs: [
        { 
          targets: '_all', 
          defaultContent: '—',
          createdCell: function (td, cellData, rowData, row, col) {
            const api = new DataTable.Api(this);
            const header = api.column(col).header();
            if (header) {
              const label = header.textContent.trim();
              if (label) {
                td.setAttribute('data-label', label);
              }
            }
          }
        },
        ...(opts.columnDefs || [])
      ]
    };

    $(selector).each(function() {
      const $table = $(this);
      
      if ($.fn?.dataTable?.isDataTable?.(this)) {
        console.warn('DataTable already initialized on:', this);
        return;
      }

      try {
        const dt = new DataTable(this, {
          ...finalOpts,
          drawCallback: function(settings) {
            try {
              const api = this.api();
              applyCustomChrome(api);
              
              // Update pagination buttons
              $table.closest('.dataTables_wrapper')
                .find('.paginate_button')
                .not('.current')
                .addClass('u-btn u-btn--sm u-btn--outline')
                .removeClass('paginate_button');
                
            } catch (e) {
              console.warn('Error in drawCallback:', e);
            }
          },
          initComplete: function(settings, json) {
            try {
              const api = this.api();
              applyCustomChrome(api);
              
              this._dtInstance = api;
              
            } catch (e) {
              console.warn('Error in initComplete:', e);
            }
          }
        });

        this._dtInstance = dt;
        
      } catch (error) {
        console.error('Error initializing DataTable:', error, this);
      }
    });
  }

  function bindExternalSearch({ 
    searchSelector, 
    buttonSelector = null, 
    tableSelector = '[data-dt]', 
    delay = 400 
  }) {
    const input = document.querySelector(searchSelector);
    if (!input) {
      console.warn('Search input not found:', searchSelector);
      return;
    }

    const dt = getDTFromElOrJq(tableSelector);
    if (!dt) {
      console.warn('DataTable not found for:', tableSelector);
      return;
    }

    const performSearch = () => {
      const searchTerm = input.value.trim();
      
      try {
        if (typeof dt.search === 'function') {
          dt.search(searchTerm).draw();
        } else if (dt.api) {
          dt.api().search(searchTerm).draw();
        } else if (dt.search) {
          dt.search(searchTerm);
          dt.draw();
        }
      } catch (error) {
        console.error('Error performing search:', error);
      }
    };

    input.addEventListener('input', debounce(performSearch, delay));
    
    if (buttonSelector) {
      const button = document.querySelector(buttonSelector);
      if (button) {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          performSearch();
        });
      }
    }

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        input.value = '';
        performSearch();
        input.blur();
      }
    });
  }

  function destroyDataTables(selector = '[data-dt]') {
    $(selector).each(function() {
      try {
        if ($.fn?.dataTable?.isDataTable?.(this)) {
          $(this).DataTable().destroy();
          return;
        }
        
        if (this._dtInstance) {
          if (typeof this._dtInstance.destroy === 'function') {
            this._dtInstance.destroy();
          }
          this._dtInstance = null;
        }
      } catch (error) {
        console.error('Error destroying DataTable:', error, this);
      }
    });
  }

  // Enhanced modal handling
  function initModalHandling() {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.u-modal:not([hidden])');
        if (openModals.length > 0) {
          const topModal = openModals[openModals.length - 1];
          topModal.hidden = true;
          document.body.classList.remove('modal-open');
        }
      }
    });

    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('u-modal')) {
        e.target.hidden = true;
        document.body.classList.remove('modal-open');
      }
      
      const closeBtn = e.target.closest('[data-modal-close]');
      if (closeBtn) {
        const modal = closeBtn.closest('.u-modal');
        if (modal) {
          modal.hidden = true;
          document.body.classList.remove('modal-open');
        }
      }
    });

    document.addEventListener('click', (e) => {
      const openBtn = e.target.closest('[data-modal-open]');
      if (openBtn) {
        const modalId = openBtn.getAttribute('data-modal-open');
        const modal = document.getElementById(modalId);
        if (modal) {
          modal.hidden = false;
          document.body.classList.add('modal-open');
          
          const firstInput = modal.querySelector('input, select, textarea');
          if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
          }
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalHandling);
  } else {
    initModalHandling();
  }

  return { 
    initDataTables, 
    bindExternalSearch, 
    destroyDataTables,
    getDTFromElOrJq,
    initModalHandling
  };
})();

window.__DT_HELPERS__ = helpers;
export const { 
  initDataTables, 
  bindExternalSearch, 
  destroyDataTables,
  getDTFromElOrJq,
  initModalHandling
} = helpers;

export default helpers;