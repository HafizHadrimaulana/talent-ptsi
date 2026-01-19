import $ from 'jquery';
import DataTable from 'datatables.net';

const DEFAULTS = {
    dom: "<'u-dt-wrapper'<'u-dt-header'<'u-dt-len'l><'u-dt-search'f>><'u-dt-tbl'tr><'u-dt-footer'<'u-dt-info'i><'u-dt-pg'p>>>",
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    processing: true,
    serverSide: true,
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
        lengthMenu: "_MENU_ per page",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "Showing 0 to 0 of 0 entries",
        infoFiltered: "(filtered from _MAX_ total entries)",
        zeroRecords: "No matching records found",
        emptyTable: "No data available in table",
        processing: '<div class="u-dt-loader-container"><div class="u-dt-liquid-spinner"><div class="drop"></div><div class="drop"></div><div class="drop"></div></div><div class="u-dt-loading-text">Loading...</div></div>',
        paginate: { first: "«", last: "»", next: "›", previous: "‹" }
    }
};

export const initDataTables = (selector, opts = {}) => {
    const el = document.querySelector(selector);
    if (!el) return;

    const $table = $(selector);

    if ($.fn.DataTable.isDataTable(selector)) {
        $table.DataTable().destroy();
    }

    const config = { ...DEFAULTS, ...opts };

    if (config.ajax) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        let ajaxConfig = typeof config.ajax === 'string' ? { url: config.ajax } : config.ajax;
        
        ajaxConfig.headers = {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            ...ajaxConfig.headers
        };
        config.ajax = ajaxConfig;
    }

    const dt = new DataTable(selector, {
        ...config,
        initComplete: function () {
            const api = this.api();
            if (opts.initComplete) opts.initComplete.call(this);

            const wrapper = $(api.table().container());
            wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
            wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
            
            setTimeout(() => { 
                api.columns.adjust(); 
                if(api.responsive) api.responsive.recalc(); 
            }, 300);
        },
        drawCallback: function () {
            const wrapper = $(this.api().table().container());
            const p = wrapper.find('.dataTables_paginate .paginate_button');
            p.addClass('u-btn u-btn--sm u-btn--ghost')
             .filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
             
            if (opts.drawCallback) opts.drawCallback.call(this);
        }
    });

    dt.on('processing.dt', function (e, settings, processing) {
        const wrapper = $(dt.table().container());
        processing ? wrapper.addClass('is-loading') : wrapper.removeClass('is-loading');
    });

    return dt;
};

export const bindExternalSearch = ({ searchSelector, tableSelector, delay = 400 }) => {
    const input = document.querySelector(searchSelector);
    if (!input) return;

    const performSearch = () => {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().search(input.value.trim()).draw();
        }
    };

    let t;
    input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(performSearch, delay);
    });
};