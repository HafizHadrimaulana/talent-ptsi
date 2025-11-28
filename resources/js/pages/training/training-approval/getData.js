import { getJSON } from "@/utils/fetch";
import { initDeleteHandler } from "./handler/deleteHandler";
import { initEditHandler } from "./handler/editHandler";
import { initApproveHandler } from "./handler/approveHandler";
import { initRejectHandler } from "./handler/rejectHandler";
import { initDragDropUpload } from "./handler/dragDropImport";

const TABLE_CONFIGS = {
    "SDM Unit": {
        apiEndpoint: (unitId) => `/training/training-request/${unitId}/get-training-request-list`,
        columns: ['checkbox', 'no', 'judul_sertifikasi', 'peserta', 'tanggal_mulai', 'tanggal_berakhir', 'realisasi_biaya_pelatihan', 'estimasi_total_biaya', 'lampiran_penawaran', 'status_approval_training', 'actions'],
        dataMapper: (data) => {
            if (data.status !== "success") return [];
            
            return data.data.map(item => ({
                id: item.id,
                judul_sertifikasi: item.training_reference?.judul_sertifikasi || "-",
                nama_peserta: item.employee?.person?.full_name || "-",
                nik: item.employee?.employee_id || "-",
                tanggal_mulai: item.start_date,
                tanggal_berakhir: item.end_date,
                realisasi_biaya_pelatihan: item.realisasi_biaya_pelatihan,
                estimasi_total_biaya: item.estimasi_total_biaya || item.training_reference?.estimasi_total_biaya || "0.00",
                lampiran_penawaran: item.lampiran_penawaran,
                status_approval_training: item.status_approval_training,
                training_reference: item.training_reference,
                employee: item.employee
            }));
        },
        actions: ['details']
    },
    "DHC": {
        apiEndpoint: () => "/training/list",
        columns: ['checkbox', 'no', 'jenis_pelatihan_editable', 'nik', 'nama_peserta', 'status_pegawai', 'jabatan_saat_ini', 'unit_kerja', 'judul_sertifikasi', 'penyelenggara', 'jumlah_jam', 'waktu_pelaksanaan', 'biaya_pelatihan', 'uhpd', 'biaya_akomodasi', 'estimasi_total_biaya', 'nama_proyek', 'jenis_portofolio', 'fungsi', 'status_approval_training', 'actions'],
        dataMapper: (data) => data.data?.data || [],
        actions: ['edit', 'delete']
    },
    "Kepala Unit": {
        apiEndpoint: (unitId) => `/training/training-request/${unitId}/get-training-request-list`,
        columns: ['checkbox', 'no', 'jenis_pelatihan', 'nik', 'nama_peserta', 'unit_kerja', 'judul_sertifikasi', 'penyelenggara', 'jumlah_jam', 'waktu_pelaksanaan', 'estimasi_total_biaya', 'status_approval_training', 'actions'],
        dataMapper: (data) => data.data || [],
        actions: ['approve', 'reject']
    },
    "VP DHC": {
        apiEndpoint: () => "/training/list-approval", 
        columns: ['checkbox', 'no', 'jenis_pelatihan', 'nik', 'nama_peserta', 'unit_kerja', 'judul_sertifikasi', 'estimasi_total_biaya', 'status_approval', 'actions'],
        dataMapper: (data) => data.data || [],
        actions: ['approve', 'reject']
    },
    "DBS Unit": {
        apiEndpoint: () => "/training/list-approval",
        columns: ['checkbox', 'no', 'jenis_pelatihan', 'nik', 'nama_peserta', 'unit_kerja', 'judul_sertifikasi', 'penyelenggara', 'estimasi_total_biaya', 'status_approval', 'actions'],
        dataMapper: (data) => data.data || [],
        actions: ['approve', 'reject']
    }
};

const DEFAULT_CONFIG = {
    apiEndpoint: () => "/training/list",
    columns: ['checkbox', 'no', 'jenis_pelatihan', 'nik', 'nama_peserta', 'status_approval', 'actions'],
    dataMapper: (data) => data.data || [],
    actions: ['details']
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

    judul_sertifikasi: (item, index, config) => `<td>${item.judul_sertifikasi ?? "-"}</td>`,

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

    status_lampiran_penawaran: (item, index, config) => {
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
                ${status}s
            </div>
        </td>
        `;
    },

    status_approval: (item, index, config) => {
        const status = item.status_approval?.status_approval;
        let badgeClass = "u-badge";
        
        if (status === "Disetujui") badgeClass = "u-badge u-badge--success";
        if (status === "Ditolak") badgeClass = "u-badge u-badge--danger";
        if (status === "Menunggu") badgeClass = "u-badge u-badge--warning";
        if (status === "Draft") badgeClass = "u-badge u-badge--secondary";
        
        return `
            <td>
                <div class="${badgeClass}">
                    ${status ?? "-"}
                </div>
            </td>
        `;
    },

    
    nik: (item, index, config) => `<td>${item.nik ?? "-"}</td>`,
    nama_peserta: (item, index, config) => `<td>${item.nama_peserta ?? "-"}</td>`,
    status_pegawai: (item, index, config) => `<td>${item.status_pegawai ?? "-"}</td>`,
    jabatan_saat_ini: (item, index, config) => `<td>${item.jabatan_saat_ini ?? "-"}</td>`,
    unit_kerja: (item, index, config) => `<td>${item.unit_kerja ?? "-"}</td>`,
    judul_sertifikasi: (item, index, config) => `<td>${item.judul_sertifikasi ?? "-"}</td>`,
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
    status_approval: (item, index, config) => `<td>${item.status_approval?.status_approval ?? "-"}</td>`,
    
    actions: (item, index, config) => {
        const buttons = config.actions.map(action => {
            const buttonConfig = ACTION_BUTTONS[action];
            return buttonConfig ? 
                `<button class="${buttonConfig.class}" data-action="${action}" data-id="${item.id}">
                    ${buttonConfig.text}
                </button>` : '';
        }).filter(Boolean).join('');
        
        return `<td class="cell-actions text-center">${buttons}</td>`;
    }
};

const getTableConfig = (userRole, unitId) => {
    const config = TABLE_CONFIGS[userRole] || DEFAULT_CONFIG;
    return {
        ...config,
        apiUrl: config.apiEndpoint(unitId)
    };
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

const loadTableData = async (config, tableBody) => {
    try {
        const data = await getJSON(config.apiUrl);
        console.log("data", data.data);
        const processedData = config.dataMapper(data);
        
        if (!processedData?.length) {
            tableBody.innerHTML = renderEmptyState(config.columns.length);
            return;
        }

        tableBody.innerHTML = processedData
            .map((item, index) => renderTableRow(item, index, config))
            .join("");
            
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
    if (!tableBody) {
        console.error("Table body element is required");
        return;
    }

    const userRole = options.userRole || window.currentUserRole;
    const unitId = options.unitId || window.userUnitId;
    
    const config = getTableConfig(userRole, unitId);
    
    const reloadData = () => loadTableData(config, tableBody);
    
    // Initial load
    reloadData();
    
    // Initialize event handlers
    initializeEventHandlers(tableBody, reloadData);
}

export function initSDMUnitTable(tableBody) {
    return initGetDataTable(tableBody, {
        userRole: "SDM Unit",
        unitId: window.userUnitId
    });
}

export function initDHCTable(tableBody) {
    return initGetDataTable(tableBody, {
        userRole: "DHC"
    });
}
