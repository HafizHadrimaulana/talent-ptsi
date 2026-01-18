import './bootstrap';
import { initGetDataTable } from './pages/training/training-approval/getData';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

document.addEventListener('DOMContentLoaded', () => {

    bindExternalSearch({
        searchSelector: '#globalSearch',
        tableSelector: '#users-table'
    });

    // --- LOGIKA USERS TABLE (Biarkan jika dipakai di halaman lain) ---
    if (document.querySelector('#users-table')) {
        // Pastikan elemen [data-table-url] ada sebelum mengakses dataset
        const urlEl = document.querySelector('[data-table-url]');
        if (urlEl) {
            const tableUrl = urlEl.dataset.tableUrl;
            initDataTables('#users-table', {
                serverSide: true,
                ajax: { url: tableUrl, type: 'GET' },
                columns: [
                    // ... columns config ...
                    { 
                        data: 'full_name',
                        title: 'Identity',
                        className: 'all',
                        render: (data, type, row) => {
                            const sub = row.employee_id || row.user_email || 'Ext';
                            return `<div><div class="u-font-semibold u-text-sm text-truncate" style="max-width:200px" title="${data}">${data}</div><div class="u-text-xs u-muted">${sub}</div></div>`;
                        }
                    },
                    { 
                        data: 'job_title',
                        title: 'Job / Unit',
                        className: 'min-tablet',
                        render: (data, type, row) => {
                            const unit = row.unit_name || '—';
                            const job = data || '—';
                            return `<div><div class="u-font-medium u-text-sm text-truncate" style="max-width:220px" title="${job}">${job}</div><div class="u-text-xs u-muted text-truncate" style="max-width:220px" title="${unit}">${unit}</div></div>`;
                        }
                    },
                    { 
                        data: 'employee_status',
                        title: 'Status',
                        className: 'min-tablet',
                        render: (data, type, row) => {
                            if (data) return `<span class="u-badge u-badge--primary">${data}</span>`;
                            if (row.user_id) return `<span class="u-badge u-badge--success">Active User</span>`;
                            return `<span class="u-badge u-badge--glass" style="opacity:0.6">No Status</span>`;
                        }
                    },
                    { 
                        data: null,
                        title: 'Actions',
                        orderable: false, 
                        searchable: false,
                        className: 'all text-end', 
                        width: '80px',
                        render: (data, type, row) => {
                            const json = encodeURIComponent(JSON.stringify(row));
                            return `<div class="cell-actions__group">
                                    <button class="u-btn u-btn--sm u-btn--outline" onclick="window.triggerEdit('${json}')"><i class="fa-solid fa-user-gear"></i></button>
                                    <button class="u-btn u-btn--sm u-btn--ghost" onclick="window.triggerDetail('${json}')"><i class="fas fa-eye"></i></button>
                                </div>`;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });
        }
    }

    if (document.querySelector('#roles-table')) {
        initDataTables('#roles-table', {
            columnDefs: [
                { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140 },
                { targets: 0, responsivePriority: 1 },
                { targets: 1, responsivePriority: 2 },
            ],
        });
    }

    // --- HAPUS BAGIAN INI (PENYEBAB ERROR) ---
    // if (document.querySelector('#contracts-table')) {
    //     initDataTables('#contracts-table', {
    //         columnDefs: [
    //             { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 160 },
    //             { targets: 0, responsivePriority: 1 },
    //         ],
    //     });
    // }
    // ------------------------------------------

    if (document.querySelector('#ip-table')) initDataTables('#ip-table', { columnDefs: [{ targets: -1, orderable: false }] });
    if (document.querySelector('#ext-table')) initDataTables('#ext-table', { columnDefs: [{ targets: -1, orderable: false }] });
    if (document.querySelector('#perms-table')) initDataTables('#perms-table', { columnDefs: [{ targets: -1, orderable: false }] });
    if (document.querySelector('#izin-table')) initDataTables('#izin-table', { columnDefs: [{ targets: -1, orderable: false }] });
    
    if (document.querySelector('#monitor-table')) {
        initDataTables('#monitor-table', {
            columnDefs: [{ targets: -1, orderable: false, searchable: false, width: 120 }],
        });
    }

    if (document.querySelector('#employees-table')) {
        initDataTables('#employees-table', {
            deferRender: true,
            pageLength: 25,
            columnDefs: [
                { targets: 4, visible: false, searchable: true }, 
                { targets: 5, visible: false, searchable: true },
                { targets: -1, orderable: false, className: 'cell-actions', width: 120 }
            ],
        });
        bindExternalSearch({
            searchSelector: '#empSearchInput',
            buttonSelector: '#empSearchForm [type="submit"]',
            tableSelector: '#employees-table'
        });
        const $input = document.getElementById('empSearchInput');
        document.getElementById('empSearchClear')?.addEventListener('click', () => {
            if ($input) { $input.value = ''; $input.dispatchEvent(new Event('input')); }
        });
    }

    const trainingTables = document.querySelectorAll('.training-table');
    if (trainingTables.length > 0) {
        trainingTables.forEach(table => {
            const tableBody = table.querySelector('tbody');
            initGetDataTable(tableBody, { 
                role: table.dataset.role, 
                unitId: table.dataset.unitId 
            });
        });
    }

    const contractFilterForm = document.getElementById('contractFilterForm');
    if (contractFilterForm) {
        contractFilterForm.querySelectorAll('select, input[type="date"]').forEach(el => {
            el.addEventListener('change', () => contractFilterForm.requestSubmit ? contractFilterForm.requestSubmit() : contractFilterForm.submit());
        });
    }

    (function initContractSignaturePad() {
        const canvas = document.getElementById('contractSignatureCanvas') || document.querySelector('[data-signature-canvas]');
        if (!canvas || !window.SignaturePad) return;

        const pad = new window.SignaturePad(canvas, { minWidth: 0.6, maxWidth: 1.8, penColor: '#0f172a' });
        window.ContractSignaturePad = pad;

        const clearBtn = document.getElementById('contractSignatureClear');
        const dataInput = document.getElementById('contractSignatureData');
        
        if (clearBtn) clearBtn.addEventListener('click', (e) => { e.preventDefault(); pad.clear(); if(dataInput) dataInput.value = ''; });
        if (dataInput && canvas.closest('form')) {
            canvas.closest('form').addEventListener('submit', () => {
                if (!pad.isEmpty()) dataInput.value = pad.toDataURL('image/png');
            });
        }
    })();

    window.triggerEdit = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const basicData = {
            id: row.employee_pk,
            employee_id: row.employee_id,
            full_name: row.full_name,
            email: row.user_email || row.employee_email,
            user: {
                id: row.user_id,
                name: row.user_name,
                email: row.user_email,
                unit_id: row.user_unit_id,
                roles_ids: row.role_ids || []
            }
        };
        if(window.openEditModal) window.openEditModal(basicData);
    };

    window.triggerDetail = function(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const basicData = {
            id: row.employee_pk,
            full_name: row.full_name,
            employee_id: row.employee_id,
            job_title: row.job_title,
            unit_name: row.unit_name,
            directorate: row.directorate_name,
            start_date: row.latest_jobs_start_date,
            status: row.employee_status,
            email: row.employee_email || row.user_email,
            phone: row.phone,
            city: row.location_city,
            talent: row.talent_class_level,
            company: row.company_name,
            photo_url: row.person_photo
        };
        if(window.openDetailModal) window.openDetailModal(basicData);
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-modal-target]');
        if (btn) {
            const modal = document.querySelector(btn.getAttribute('data-modal-target'));
            if (modal) {
                modal.hidden = false;
                document.body.classList.add('modal-open');
            }
        }
        
        const closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            const modal = closeBtn.closest('.u-modal');
            if (modal) {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
            }
        }

        if(e.target.classList.contains('u-modal')) {
            e.target.hidden = true;
            document.body.classList.remove('modal-open');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.u-modal:not([hidden])').forEach(m => {
                m.hidden = true;
                document.body.classList.remove('modal-open');
            });
        }
    });
});