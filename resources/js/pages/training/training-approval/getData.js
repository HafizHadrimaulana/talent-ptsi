import { getJSON } from "@/utils/fetch";
import { initDeleteHandler } from "./handler/deleteHandler";
import { initEditHandler } from "./handler/editHandler";
import { initApproveHandler } from "./handler/approveHandler";
import { initRejectHandler } from "./handler/rejectHandler";
import { initDragDropUpload } from "./handler/dragDropImport";

const ROLES = {
    SDM_UNIT: "SDM Unit",
    DHC: "DHC",
    KEPALA_UNIT: "Kepala Unit",
    AVP: "AVP",
    VP_DHC: "VP DHC",
    DBS_UNIT: "DBS Unit",
};

const TABLE_CONFIGS = {
    'data-lna-table': {
        apiEndpoint: () => "/training/training-request/get-data-lna",
        columns: [
            'no','judul_sertifikasi','unit_kerja','penyelenggara',
            'jumlah_jam','waktu_pelaksanaan','biaya_pelatihan',
            'uhpd','biaya_akomodasi','estimasi_total_biaya',
            'nama_proyek','jenis_portofolio','fungsi', 'actions'
        ],
        dataMapper: (data) => data.data || [],
        actions: {
            default: ['details'],

            rules: [
                {
                    roles: ['DHC'],
                    allow: ['edit', 'delete']
                },
            ]
        }
    },

    'training-request-table': {
        apiEndpoint: (unitId) =>
            `/training/training-request/${unitId}/get-training-request-list`,
        columns: ['checkbox', 'no', 'judul_sertifikasi', 'peserta', 'tanggal_mulai', 'tanggal_berakhir', 'realisasi_biaya_pelatihan', 'estimasi_total_biaya', 'lampiran_penawaran', 'status_approval_training', 'actions'],
        dataMapper: (data) => {
            if (data.status !== "success") return [];
            return data.data.map((item) => ({
                id: item.id,
                judul_sertifikasi:
                    item.training_reference?.judul_sertifikasi || "-",
                nama_peserta: item.employee?.person?.full_name || "-",
                nik: item.employee?.employee_id || "-",
                tanggal_mulai: item.start_date,
                tanggal_berakhir: item.end_date,
                realisasi_biaya_pelatihan: item.realisasi_biaya_pelatihan,
                estimasi_total_biaya:
                    item.estimasi_total_biaya ||
                    item.training_reference?.estimasi_total_biaya ||
                    "0.00",
                lampiran_penawaran: item.lampiran_penawaran,
                status_approval_training: item.status_approval_training,
                training_reference: item.training_reference,
                employee: item.employee,
            }));
        },
        actions: {
            default: ['details'],

            rules: [
                {
                    roles: ['SDM Unit'],
                    when: status => status === 'in_review_gmvp',
                    allow: ['edit', 'delete']
                },
                {
                    roles: ['DHC'],
                    when: status => status === 'in_review_dhc',
                    allow: ['approve', 'reject']
                },
                {
                    roles: ['AVP'],
                    when: status => status === 'in_review_avpdhc',
                    allow: ['approve', 'reject']
                },
                {
                    roles: ['Kepala Unit'],
                    // when: status => status === 'in_review_vpdhc',
                    allow: ['approve', 'reject']
                },
            ]
        }
    }
}

const DEFAULT_CONFIG = {
    apiEndpoint: () => "/training/training-request/get-data-lna",
    columns: ['no', 'judul_sertifikasi', 'unit_kerja', 'penyelenggara', 'jumlah_jam', 'waktu_pelaksanaan', 'biaya_pelatihan', 'uhpd', 'biaya_akomodasi', 'estimasi_total_biaya', 'nama_proyek', 'jenis_portofolio', 'fungsi', 'actions'],
    dataMapper: (data) => data.data || [],
    actions: ['edit', 'delete']
};

const ACTION_BUTTONS = {
    edit: { class: "u-btn u-btn--brand u-hover-lift", text: "Edit" },
    delete: { class: "u-btn u-btn--brand u-hover-lift", text: "Hapus" },
    approve: { class: "u-btn u-btn--brand u-hover-lift", text: "Terima" },
    reject: { class: "u-btn u-btn--brand u-hover-lift", text: "Tolak" },
    details: { class: "u-btn u-btn--brand u-hover-lift", text: "Details" }
};

const formatRupiah = (value) => {
    if (value == null || value === "" || value === "-" || value === "null") {
        return "Rp 0";
    }
    
    const number = parseFloat(value);
    
    if (isNaN(number)) {
        return "Rp 0";
    }
    
    return new Intl.NumberFormat("id-ID", {
        style: "currency", 
        currency: "IDR", 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0
    }).format(number);
};

const formatDate = (dateString, options = { day: '2-digit', month: 'long', year: 'numeric' }) => {
    if (!dateString) return "-";
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return "-";
        
        return date.toLocaleDateString('id-ID', options);
    } catch (error) {
        console.error('Error formatting date:', error);
        return "-";
    }
};

const COLUMN_RENDERERS = {
    checkbox: (item, index, config) => `
        <td>
            <input type="checkbox" name="selected[]" value="${item.id}" class="row-checkbox">
        </td>
    `,
    
    no: (item, index, config) => `
        <td>
            <div class="u-flex u-items-center u-gap-sm">
                <div class="u-badge u-badge--primary">${index + 1}</div>
            </div>
        </td>
    `,
    
    jenis_pelatihan: (item, index, config) => `
        <td>${item.jenis_pelatihan ?? "-"}</td>
    `,
    
    jenis_pelatihan_editable: (item, index, config) => `
        <td>
            <select class="jenis-pelatihan-select border border-gray-300 rounded p-1 w-full" data-id="${item.id}">
                <option value="" ${!item.jenis_pelatihan ? "selected" : ""}>-- Pilih Jenis Pelatihan --</option>
                <option value="EDP - Sertifikat Profesi" ${item.jenis_pelatihan === "EDP - Sertifikat Profesi" ? "selected" : ""}>
                    EDP - Sertifikat Profesi
                </option>
                <option value="EDP - Sertifikat Industri" ${item.jenis_pelatihan === "EDP - Sertifikat Industri" ? "selected" : ""}>
                    EDP - Sertifikat Industri
                </option>
            </select>
        </td>
    `,

    // judul_sertifikasi: (item, index, config) => `<td>${item.judul_sertifikasi ?? "-"}</td>`,

    peserta: (item, index, config) => `
    <td>
        <div class="flex flex-col">
            <span class="font-semibold">${item.nama_peserta ?? "-"}</span>
            <span class="text-sm text-gray-500">${item.nik ?? ""}</span>
        </div>
    </td>
    `,

    tanggal_mulai: (item, index, config) => `<td>${formatDate(item.tanggal_mulai) ?? "-"}</td>`,
    tanggal_berakhir: (item, index, config) => `<td>${formatDate(item.tanggal_berakhir) ?? "-"}</td>`,
    realisasi_biaya_pelatihan: (item, index, config) => `<td>${formatRupiah(item.realisasi_biaya_pelatihan)}</td>`,
    estimasi_total_biaya: (item, index, config) => `<td>${formatRupiah(item.estimasi_total_biaya)}</td>`,

    lampiran_penawaran: (item, index, config) => {
        const hasLampiran = item.lampiran_penawaran && 
        item.lampiran_penawaran !== "null" && 
        item.lampiran_penawaran.trim() !== "";

        const status = hasLampiran ? "Tersedia" : "Tidak Tersedia";
        const badgeClass = hasLampiran 
            ? "u-badge u-badge--success" 
            : "u-badge u-badge--danger";

        return `
        <td>
            <div class="${badgeClass}">
                ${status}
            </div>
        </td>
        `;
    },

    nik: (item, index, config) => `<td>${item.nik ?? "-"}</td>`,
    nama_peserta: (item, index, config) => `<td>${item.nama_peserta ?? "-"}</td>`,
    status_pegawai: (item, index, config) => `<td>${item.status_pegawai ?? "-"}</td>`,
    jabatan_saat_ini: (item, index, config) => `<td>${item.jabatan_saat_ini ?? "-"}</td>`,
    unit_kerja: (item, index, config) => `<td>${item.unit_kerja ?? "-"}</td>`,
    // judul_sertifikasi: (item, index, config) => `<td>${item.judul_sertifikasi ?? "-"}</td>`,
    penyelenggara: (item, index, config) => `<td>${item.penyelenggara ?? "-"}</td>`,
    jumlah_jam: (item, index, config) => `<td>${item.jumlah_jam ?? "-"}</td>`,
    waktu_pelaksanaan: (item, index, config) => `<td>${item.waktu_pelaksanaan ?? "-"}</td>`,
    biaya_pelatihan: (item, index, config) => `<td>${formatRupiah(item.biaya_pelatihan)}</td>`,
    uhpd: (item, index, config) => `<td>${formatRupiah(item.uhpd)}</td>`,
    biaya_akomodasi: (item, index, config) => `<td>${formatRupiah(item.biaya_akomodasi)}</td>`,
    estimasi_total_biaya: (item, index, config) => `<td>${formatRupiah(item.estimasi_total_biaya)}</td>`,
    nama_proyek: (item, index, config) => `<td>${item.nama_proyek ?? "-"}</td>`,
    jenis_portofolio: (item, index, config) => `<td>${item.jenis_portofolio ?? "-"}</td>`,
    fungsi: (item, index, config) => `<td>${item.fungsi ?? "-"}</td>`,

    status_approval_training: (item, index, config) => {
        const status = item.status_approval_training;

        const STATUS_STYLE = {
            created: {
                label: "Created",
                style: "background:#E5E7EB;color:#374151;" // abu netral
            },

            in_review_gmvp: {
                label: "In Review GM / VP",
                style: "background:#FEF3C7;color:#92400E;" // kuning → awal approval
            },

            in_review_dhc: {
                label: "In Review DHC",
                style: "background:#DBEAFE;color:#1E40AF;" // biru → middle approval
            },

            in_review_avpdhc: {
                label: "In Review AVP DHC",
                style: "background:#EDE9FE;color:#5B21B6;" // ungu → senior approval
            },

            in_review_vpdhc: {
                label: "In Review VP DHC",
                style: "background:#FCE7F3;color:#9D174D;" // pink → final approval
            },

            approved: {
                label: "Approved",
                style: "background:#DCFCE7;color:#166534;" // hijau → selesai
            },

            rejected: {
                label: "Rejected",
                style: "background:#FEE2E2;color:#991B1B;" // merah → ditolak
            },
        };

        if (!status || !STATUS_STYLE[status]) {
            return `<td class="text-center">-</td>`;
        }

        const { label, style } = STATUS_STYLE[status];

        return `
            <td class="text-center">
                <span
                    style="
                        display:inline-block;
                        padding:4px 10px;
                        border-radius:9999px;
                        font-size:12px;
                        font-weight:600;
                        white-space:nowrap;
                        ${style}
                    "
                >
                    ${label}
                </span>
            </td>
        `;
    },

    actions: (item, index, config) => {
        const actions = resolveActions(config, item);

        const finalActions = actions.length ? actions : ['details'];
        
        const buttons = finalActions
            .map(action => {
                const buttonConfig = ACTION_BUTTONS[action];
                if (!buttonConfig) {
                    return null;
                }

                return `
                    <button
                        class="${buttonConfig.class}"
                        data-action="${action}"
                        data-id="${item.id}"
                    >
                        ${buttonConfig.text}
                    </button>
                `;
            })
            .join('');

        if (!buttons) {
            return `<td class="cell-actions text-center">-</td>`;
        }

        return `
            <td class="cell-actions text-center">
                <div class="u-flex u-justify-center u-gap-sm">
                    ${buttons}
                </div>
            </td>
        `;
    }

};

let currentPage = 1;
let perPage = 12;

// let lastUsedConfig = null;
// let lastUsedTableBody = null;

// const getTableConfig = (userRole, unitId) => {
//     const config = TABLE_CONFIGS[userRole] || DEFAULT_CONFIG;
//     return {
//         ...config,
//         apiUrl: (currentPage, perPage) => config.apiEndpoint(unitId) + `?page=${currentPage}&per_page=${perPage}`
//     };
// };

const resolveActions = (config, item) => {
    const role = window.currentUserRole;

    // legacy
    if (Array.isArray(config.actions)) {
        return config.actions;
    }

    if (typeof config.actions === 'object') {
        let actions = config.actions.default || [];

        if (Array.isArray(config.actions.rules)) {
            for (const rule of config.actions.rules) {

                // === CHECK ROLE (jika ada) ===
                if (Array.isArray(rule.roles) && !rule.roles.includes(role)) {
                    continue;
                }

                // === CHECK STATUS (jika ada) ===
                if (typeof rule.when === 'function') {
                    if (!rule.when(item.status_approval_training)) {
                        continue;
                    }
                }

                // RULE MATCH → OVERRIDE
                actions = rule.allow;
                break;
            }
        }

        return actions;
    }

    return [];
};

const renderEmptyState = (colspan) => {
    return `<tr><td colspan="${colspan}" class="text-center">Tidak ada data</td></tr>`;
};

const renderErrorState = (colspan) => {
    return `<tr><td colspan="${colspan}" class="text-center">Gagal memuat data</td></tr>`;
};

const renderTableRow = (item, index, config) => {
    const cells = config.columns.map(column => {
        const renderer = COLUMN_RENDERERS[column];
        return renderer ? renderer(item, index, config) : `<td>${item[column] ?? "-"}</td>`;
    }).join('');
    
    return `<tr>${cells}</tr>`;
};

function renderPagination(p) {
    const container = document.getElementById("pagination");
    if (!container) return;

    let html = `<ul class="u-pagination u-flex u-gap-sm u-justify-center">`;

    html += `
        <li class="u-page-item ${p.current_page === 1 ? 'disabled' : ''}">
            <a href="#" data-page="${p.current_page - 1}" class="u-page-link">‹ Prev</a>
        </li>
    `;

    function pageItem(i, active = false) {
        return `
            <li class="u-page-item ${active ? 'active' : ''}">
                <a href="#" data-page="${i}" class="u-page-link">${i}</a>
            </li>
        `;
    }

    // Generate pagination range with ellipsis
    let start = Math.max(1, p.current_page - 2);
    let end = Math.min(p.last_page, p.current_page + 2);

    if (start > 1) {
        html += pageItem(1);
        if (start > 2) html += `<li class="ellipsis">...</li>`;
    }

    for (let i = start; i <= end; i++) {
        html += pageItem(i, i === p.current_page);
    }

    if (end < p.last_page) {
        if (end < p.last_page - 1) html += `<li class="ellipsis">...</li>`;
        html += pageItem(p.last_page);
    }

    // Next Button
    html += `
        <li class="u-page-item ${p.current_page === p.last_page ? 'disabled' : ''}">
            <a href="#" data-page="${p.current_page + 1}" class="u-page-link">Next ›</a>
        </li>
    `;

    html += `</ul>`;
    container.innerHTML = html;

    container.querySelectorAll("a[data-page]").forEach(a => {
        a.addEventListener("click", e => {
            e.preventDefault();
            const page = parseInt(a.dataset.page);
            if (page >=1 && page <= p.last_page) {
                window.currentPage = page;
                loadTableData(window.lastUsedConfig, window.lastUsedTableBody);
            }
        });
    });
}

const loadTableData = async (config, tableBody) => {
    try {
        window.lastUsedConfig = config;
        window.lastUsedTableBody = tableBody;
        
        const data = await getJSON(config.apiUrl(currentPage, perPage));
        
        console.log("data", data.data);
        const processedData = config.dataMapper(data);
        
        if (!processedData?.length) {
            tableBody.innerHTML = renderEmptyState(config.columns.length);
            renderPagination(data.pagination)
            return;
        }

        tableBody.innerHTML = processedData
            .map((item, index) => renderTableRow(item, index, config))
            .join("");

        if (data.pagination) {
            renderPagination(data.pagination);
        }
            
    } catch (error) {
        console.error("Gagal memuat data:", error);
        tableBody.innerHTML = renderErrorState(config.columns.length);
    }
};

const initializeEventHandlers = (tableBody, reloadFunction) => {
    initEditHandler(tableBody, reloadFunction);
    initDeleteHandler(tableBody, reloadFunction);
    initApproveHandler(tableBody);
    initRejectHandler(tableBody);
    initDragDropUpload();
};

export function initGetDataTable(tableBody, options = {}) {
    const tableId = options.tableId || tableBody.closest('table')?.id;
    
    if (!tableId || !TABLE_CONFIGS[tableId]) {
        console.error("Table config not found for:", tableId);
        return;
    }

    const unitId = options.unitId || window.currentUnitId;

    const baseConfig = TABLE_CONFIGS[tableId];

    const config = {
        ...baseConfig,
        apiUrl: (page, perPage) =>
            baseConfig.apiEndpoint(unitId) + `?page=${page}&per_page=${perPage}`
    };

    const reloadData = () => loadTableData(config, tableBody);
    
    // Initial load
    reloadData();
    
    // Initialize event handlers
    initializeEventHandlers(tableBody, reloadData);
}
