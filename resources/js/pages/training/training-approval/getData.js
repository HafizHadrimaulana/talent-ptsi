import { getJSON } from "@/utils/fetch";
import { initDeleteHandler } from "./handler/deleteHandler";
import { initEditHandler } from "./handler/editHandler";
import { initApproveHandler, initApproveReferenceHandler } from "./handler/approveHandler";
import { initRejectHandler, rejectTrainingPengajuanHandler } from "./handler/rejectHandler";
import { initDragDropUpload } from "./handler/dragDropImport";
import { initDetailHandler } from "./handler/initDetailHandler";
import { initDataTables } from "../../../plugins/datatables";

const TABLE_CONFIGS = {
    'data-lna-table': {
        tableId: "data-lna-table",
        apiEndpoint: () => "/training/training-request/get-data-lna",
        columns: [
            'no','judul_sertifikasi','unit_kerja','penyelenggara',
            'jumlah_jam','waktu_pelaksanaan','biaya_pelatihan',
            'uhpd','biaya_akomodasi','estimasi_total_biaya',
            'nama_proyek','jenis_portofolio','fungsi', 'status_training_reference', 'actions'
        ],
        dataMapper: (res) => res.data || [],
        actions: { default: ['details'] }
    },

    'approval-pengajuan-training-table': {
        tableId: "approval-pengajuan-training-table",
        apiEndpoint: () => `/training/training-request/get-approval-pengajuan-training`,
        columns: [
            'no','judul_sertifikasi','unit_kerja','penyelenggara',
            'jumlah_jam','waktu_pelaksanaan','biaya_pelatihan',
            'uhpd','biaya_akomodasi','estimasi_total_biaya',
            'nama_proyek','jenis_portofolio','fungsi', 'status_training_reference', 'actions'
        ],
        dataMapper: (res) => res.data || [],
        actions: {
            default: ['details'],
            rules: [
                { roles: ['DBS Unit', 'DHC'], allow: ['approve_training_pengajuan', 'reject_training_pengajuan'] }
            ]
        }
    },

    'training-request-table': {
        tableId: "training-request-table",
        apiEndpoint: (unitId) => `/training/training-request/${unitId}/get-training-request-list`,
        columns: ['no', 'judul_sertifikasi', 'peserta', 'tanggal_mulai', 'tanggal_berakhir', 'realisasi_biaya_pelatihan', 'estimasi_total_biaya', 'lampiran_penawaran', 'status_approval_training', 'actions'],
        dataMapper: (data) => {
            if (!data || data.status !== "success") return [];
            return data.data.map((item) => ({
                id: item.id,
                judul_sertifikasi: item.training_reference?.judul_sertifikasi || "-",
                nama_peserta: item.employee?.person?.full_name || "-",
                nik: item.employee?.employee_id || "-",
                tanggal_mulai: item.start_date,
                tanggal_berakhir: item.end_date,
                realisasi_biaya_pelatihan: item.realisasi_biaya_pelatihan,
                estimasi_total_biaya: item.estimasi_total_biaya || item.training_reference?.estimasi_total_biaya || "0",
                lampiran_penawaran: item.lampiran_penawaran,
                status_approval_training: item.status_approval_training,
            }));
        },
        actions: {
            default: ['details'],
            rules: [
                {
                    roles: ["SDM Unit"],
                    when: (s) => s === "in_review_gmvp",
                    allow: ["delete"],
                },
                {
                    roles: ["DHC"],
                    when: (s) => s === "in_review_dhc",
                    allow: ["approve", "reject"],
                },
                {
                    roles: ["AVP"],
                    when: (s) => s === "in_review_avpdhc",
                    allow: ["approve", "reject"],
                },
                { roles: ["Kepala Unit"], allow: ["approve", "reject"] },
            ],
        }
    }
};

const ACTION_BUTTONS = {
    edit: { class: "u-btn u-btn--brand u-hover-lift", text: "Edit" },
    delete: { class: "u-btn u-btn--danger u-hover-lift", text: "Hapus" },
    approve: { class: "u-btn u-btn--success u-hover-lift", text: "Terima" },
    reject: { class: "u-btn u-btn--danger u-hover-lift", text: "Tolak" },
    approve_training_pengajuan: { class: "u-btn u-btn--success u-hover-lift", text: "Terima" },
    reject_training_pengajuan: { class: "u-btn u-btn--danger u-hover-lift", text: "Tolak" },
    details: { class: "u-btn u-btn--info u-hover-lift", text: "Details" }
};

const COLUMN_RENDERERS = {
    no: (d, t, r, meta) => `<div class="text-center"><div class="u-badge u-badge--primary">${meta.row + 1}</div></div>`,
    
    judul_sertifikasi: (d, t, r) => `<div class="u-font-medium">${r.judul_sertifikasi ?? "-"}</div>`,

    peserta: (d, t, r) => `
        <div class="flex flex-col">
            <span class="font-semibold">${r.nama_peserta}</span>
            <small class="text-gray-500">${r.nik}</small>
        </div>`,

    tanggal_mulai: (d) => `<div>${formatDate(d)}</div>`,
    tanggal_berakhir: (d) => `<div>${formatDate(d)}</div>`,

    biaya_pelatihan: (d) => `<div class="u-text-right font-semibold u-text-primary">${formatRupiah(d)}</div>`,
    realisasi_biaya_pelatihan: (d) => `<div class="u-text-right font-semibold">${formatRupiah(d)}</div>`,
    estimasi_total_biaya: (d) => `<div class="u-text-right font-bold u-text-primary">${formatRupiah(d)}</div>`,

    lampiran_penawaran: (d) => {
        const hasFile = d && d !== "null" && d !== "";
        return `<div class="text-center"><span class="u-badge ${hasFile ? 'u-badge--success' : 'u-badge--danger'}">${hasFile ? 'Tersedia' : 'Kosong'}</span></div>`;
    },

    status_approval_training: (d) => {
        // 1. Definisikan mapping style
        const STATUS_MAP = {
            created: {
                label: "Created",
                class: "u-badge--secondary"
            },
            in_review_gmvp: {
                label: "In Review GM/VP",
                class: "u-badge--warning"
            },
            in_review_dhc: {
                label: "In Review DHC",
                class: "u-badge--info"
            },
            in_review_avpdhc: {
                label: "In Review AVP DHC",
                class: "u-badge--purple"
            },
            in_review_vpdhc: {
                label: "In Review VP DHC",
                class: "u-badge--primary"
            },
            approved: {
                label: "Approved",
                class: "u-badge--success"
            },
            rejected: {
                label: "Rejected",
                class: "u-badge--danger"
            }
        };

        // 2. Ambil config berdasarkan key status (d), jika tidak ada gunakan default
        const config = STATUS_MAP[d] || { 
            label: d?.replace(/_/g, ' ').toUpperCase() || '-', 
            class: "u-badge--secondary" 
        };

        // 3. Render HTML
        return `
            <div class="text-center">
                <span class="u-badge ${config.class}">
                    ${config.label}
                </span>
            </div>`;
    },

    status_training_reference: (d) => {
        const MAP = {
            active: { label: "Aktif", class: "u-badge--success" },
            pending: { label: "Pending", class: "u-badge--warning" },
            rejected: { label: "Ditolak", class: "u-badge--danger" },
        };
        const cfg = MAP[d] || { label: d, class: "u-badge--secondary" };
        return `<div class="text-center"><span class="u-badge ${cfg.class}">${cfg.label}</span></div>`;
    },

    actions: (data, type, row, meta, baseConfig) => {
        const actions = resolveActions(baseConfig, row);
        
        const finalActions = actions && actions.length ? actions : ['details'];
        
        const tableId = baseConfig.tableId;

        const buttons = finalActions.map(act => {
            const btn = ACTION_BUTTONS[act];
            if (!btn) return "";
            
            return `<button 
                        class="${btn.class} btn-action" 
                        data-action="${act}" 
                        data-id="${row.id}" 
                        data-table="${tableId}">
                        ${btn.text}
                    </button>`;
        }).join('');

        return `
            <div class="u-flex u-justify-center u-gap-sm">
                ${buttons || "-"}
            </div>
        `;
    }
};

const resolveActions = (config, item) => {
    console.log('aaa', config);
    const role = window.currentUserRole;
    let allowed = config.actions?.default || ['details'];

    if (config.actions?.rules) {
        for (const rule of config.actions.rules) {
            const roleMatch = !rule.roles || rule.roles.includes(role);
            const statusMatch = !rule.when || rule.when(item.status_approval_training);
            if (roleMatch && statusMatch) {
                allowed = rule.allow;
                break;
            }
        }
    }
    return allowed;
};

export function initGetDataTable(tableBody, options = {}) {
    const $tableBody = $(tableBody);
    const $tableEl = $tableBody.closest('table');
    const tableId = $tableEl.attr('id');

    const baseConfig = TABLE_CONFIGS[tableId];
    if (!baseConfig) return;

    // Destroy existing instance if any
    if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
        $(`#${tableId}`).DataTable().destroy();
    }

    const reloadTable = () => $(`#${tableId}`).DataTable().ajax.reload(null, false);
    initializeEventHandlers($tableBody, reloadTable);

    initDataTables(`#${tableId}`, {
        serverSide: true,
        ajax: async (data, callback) => {
            const page = (data.start / data.length) + 1;
            const search = data.search.value;

            const orderColumnIndex = data.order[0]?.column; // Indeks kolom yang di-klik
            const orderDir = data.order[0]?.dir; // 'asc' atau 'desc'
            const orderColumnName = data.columns[orderColumnIndex]?.data; // Nama field kolom

            // Tambahkan ke URL
            let url = baseConfig.apiEndpoint(options.unitId) + 
                    `?page=${page}` +
                    `&per_page=${data.length}` +
                    `&search=${encodeURIComponent(search)}` +
                    `&order_by=${orderColumnName || ''}` + // Field yang diurutkan
                    `&order_dir=${orderDir || ''}`;
            
            try {
                const response = await getJSON(url);
                callback({
                    draw: data.draw,
                    recordsTotal: parseInt(response.pagination?.total) || 0,
                    recordsFiltered: parseInt(response.pagination?.total) || 0,
                    data: baseConfig.dataMapper(response)
                });
            } catch (e) {
                console.error("DataTable Error:", e);
                callback({ 
                    draw: data.draw,
                    data: [], 
                    recordsTotal: 0, 
                    recordsFiltered: 0 
                });
            }
        },
        columns: baseConfig.columns.map(col => ({
            data: (col === 'no' || col === 'actions' || col === 'peserta') ? null : col,
            className: col === 'actions' ? 'cell-actions' : '',
            render: (data, type, row, meta) => {
                const renderer = COLUMN_RENDERERS[col];
                return renderer ? renderer(data, type, row, meta, baseConfig) : (data ?? "-");
            }
        })),
        // drawCallback: function() {
        //     initializeEventHandlers(tableBody, () => this.api().draw(false));
        // }
    });
}

const initializeEventHandlers = (tableBody, reloadFunction) => {
    initEditHandler(tableBody, reloadFunction);
    initDetailHandler(tableBody);
    initDeleteHandler(tableBody, reloadFunction);
    initApproveHandler(tableBody, reloadFunction);
    initRejectHandler(tableBody, reloadFunction);
    rejectTrainingPengajuanHandler(tableBody, reloadFunction);
    initApproveReferenceHandler(tableBody, reloadFunction);
    initDragDropUpload();
};

// HELPERS //
const formatRupiah = (value) => {
    const number = parseFloat(value);
    if (isNaN(number)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency", currency: "IDR", minimumFractionDigits: 0
    }).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? "-" : date.toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric'
    });
};