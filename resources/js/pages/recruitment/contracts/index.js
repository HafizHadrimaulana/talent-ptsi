import { initCreateModal, initEditModal, initDetailModal, initRejectModal, initSignModal } from './modals.js';
import { initMap, maps } from './map.js';
import { select, selectAll, hide, show, showBlock, money, terbilang, safeJSON, addDays, bindCalc, handleLocationAutofill } from './utils.js';

// Expose utilities globally for inline Blade usage
window.initMap = initMap;
window.maps = maps;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Leaflet icons
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    // Initialize DataTables
    const table = window.initDataTables('#contracts-table', {
        ajax: {
            url: window.contractsIndexUrl,
            data: function (d) {
                d.unit_id = document.getElementById('filterUnit')?.value;
                d.status = document.getElementById('filterStatus')?.value;
            }
        },
        order: [[5, 'desc']],
        columns: [
            { data: 0, orderable: true },
            { data: 1, orderable: true },
            { data: 2, orderable: false },
            { data: 3, orderable: true },
            { data: 4, orderable: true },
            { data: 5, orderable: true },
            { data: 6, orderable: false, className: "text-center" }
        ],
        drawCallback: function() {
            const wrapper = $(this.api().table().container());
            wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
            wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
            const p = wrapper.find('.dataTables_paginate .paginate_button');
            p.addClass('u-btn u-btn--sm u-btn--ghost');
            p.filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
            p.filter('.disabled').addClass('u-disabled').css('opacity', '0.5');
        }
    });

    // Store table reference globally for reload
    window.contractsTable = table;

    $('#filterUnit, #filterStatus').on('change', () => table.draw());

    // Trigger functions for DataTables actions
    window.triggerEdit = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const btnEdit = document.createElement('button');
        btnEdit.dataset.showUrl = `${window.contractsBaseUrl}/${row.id}`;
        btnEdit.dataset.updateUrl = `${window.contractsBaseUrl}/${row.id}`;
        btnEdit.classList.add('js-btn-edit');
        document.body.appendChild(btnEdit);
        btnEdit.click();
        document.body.removeChild(btnEdit);
    };

    window.triggerDetail = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const btnDet = document.createElement('button');
        btnDet.dataset.showUrl = `${window.contractsBaseUrl}/${row.id}`;
        btnDet.classList.add('js-btn-detail');
        document.body.appendChild(btnDet);
        btnDet.click();
        document.body.removeChild(btnDet);
    };

    // Initialize utilities
    handleLocationAutofill();

    // Initialize all modals
    initCreateModal();
    initEditModal();
    initDetailModal();
    initRejectModal();
    initSignModal();

    // Delete handler
    $(document).on('click', '.js-btn-delete', async function(e) {
        e.preventDefault();
        const btnDelete = this;
        const csrf = select('meta[name="csrf-token"]')?.content;
        
        const confirmed = await window.showDeleteConfirm('dokumen ini');
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = btnDelete.dataset.url;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
});
