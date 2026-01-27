import { getJSON } from "@/utils/fetch";
import { initDataTables } from "../../../plugins/datatables";
import {
    executeApprove,
    executeApproveReference,
} from "./handler/approveHandler";
import { executeReject, executeRejectPengajuan } from "./handler/rejectHandler";

const TABLE_CONFIGS = {
    "data-lna-table": {
        tableId: "data-lna-table",
        modalId: "#lna-modal",
        apiEndpoint: () => "/training/training-request/get-data-lna",
        columns: [
            "no",
            "judul_sertifikasi",
            "unit_kerja",
            "penyelenggara",
            "jumlah_jam",
            "waktu_pelaksanaan",
            "biaya_pelatihan",
            "nama_proyek",
            "jenis_portofolio",
            "jenis_pelatihan",
            "fungsi",
            "status_training_reference",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },

    "approval-pengajuan-training-table": {
        tableId: "approval-pengajuan-training-table",
        modalId: "#pengajuan-training-modal",
        apiEndpoint: () =>
            `/training/training-request/get-approval-pengajuan-training`,
        columns: [
            "no",
            "judul_sertifikasi",
            "unit_kerja",
            "penyelenggara",
            "jumlah_jam",
            "waktu_pelaksanaan",
            "biaya_pelatihan",
            "nama_proyek",
            "jenis_portofolio",
            "jenis_pelatihan",
            "fungsi",
            "status_training_reference",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },

    "approval-training-request-table": {
        tableId: "approval-training-request-table",
        modalId: "#training-peserta-modal",
        apiEndpoint: () =>
            `/training/training-request/get-training-request-list`,
        columns: [
            "no",
            "judul_sertifikasi",
            "peserta",
            "tanggal_mulai",
            "tanggal_berakhir",
            "realisasi_biaya_pelatihan",
            "lampiran_penawaran",
            "status_approval_training",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },

    "pengajuan-training-peserta-table": {
        tableId: "pengajuan-training-peserta-table",
        modalId: "#training-peserta-modal",
        apiEndpoint: (unitId) =>
            `/training/training-management/${unitId}/get-pengajuan-training-peserta`,
        columns: [
            "no",
            "judul_sertifikasi",
            "peserta",
            "tanggal_mulai",
            "tanggal_berakhir",
            "realisasi_biaya_pelatihan",
            "lampiran_penawaran",
            "status_approval_training",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },

    "pengajuan-data-lna-table": {
        tableId: "pengajuan-data-lna-table",
        modalId: "#lna-modal",
        apiEndpoint: () =>
            `/training/training-management/get-data-pengajuan-lna`,
        columns: [
            "no",
            "judul_sertifikasi",
            "unit_kerja",
            "penyelenggara",
            "jumlah_jam",
            "waktu_pelaksanaan",
            "biaya_pelatihan",
            "nama_proyek",
            "jenis_portofolio",
            "jenis_pelatihan",
            "fungsi",
            "status_training_reference",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },
};

// RENDERER UNTUK KOLOM TABEL
const COLUMN_RENDERERS = {
    no: (d, t, r, meta) =>
        `<div class="text-center"><div class="u-badge u-badge--primary">${
            meta.row + 1
        }</div></div>`,

    judul_sertifikasi: (d, t, r) =>
        `<div class="u-font-medium">${r.judul_sertifikasi ?? "-"}</div>`,

    peserta: (d, t, r) => `
        <div class="flex flex-col">
            <span class="font-semibold">${r.peserta}</span>
            <small class="text-gray-500">${r.nik}</small>
        </div>`,

    tanggal_mulai: (d) => `<div>${formatDate(d)}</div>`,
    tanggal_berakhir: (d) => `<div>${formatDate(d)}</div>`,
    biaya_pelatihan: (d) => `<div>${formatRupiah(d)}</div>`,
    realisasi_biaya_pelatihan: (d) => `<div>${formatRupiah(d)}</div>`,
    estimasi_total_biaya: (d) =>
        `<div class="font-bold u-text-sm">${formatRupiah(d)}</div>`,

    lampiran_penawaran: (d) => {
        const hasFile = d && d !== "null" && d !== "";
        return `<div><span class="u-badge ${
            hasFile ? "u-badge--success" : "u-badge--danger"
        }">${hasFile ? "Tersedia" : "Kosong"}</span></div>`;
    },

    status_approval_training: (d) => renderStatusBadge(d),
    status_training_reference: (d) => renderStatusBadge(d),

    actions: (data, type, row, meta, config) => `
        <div class="">
            <button type="button" class="u-btn u-btn--xs u-btn--outline btn-trigger-modal" 
                data-table="${config.tableId}">
                <i class="fas fa-eye"></i> Detail
            </button>
        </div>`,
};

// --- CORE HANDLER ---
const initModalSystem = () => {
    const $body = $("body");

    // 1. Handler Buka Modal
    $body
        .off("click.modalSystem")
        .on("click.modalSystem", ".btn-trigger-modal", function () {
            const $btn = $(this);
            const tableId = $btn.data("table");
            const config = TABLE_CONFIGS[tableId];

            if (!config) return;

            const dt = DATA_TABLE_INSTANCES[tableId];
            if (!dt) return;

            const row = dt.row($btn.parents("tr"));
            const rowData = row.data();
            console.log("row data aa", rowData);

            const $modal = $(config.modalId);

            $modal.attr("data-table-id", tableId);

            if ($modal.length && rowData) {
                toggleEditMode($modal, false);
                populateModalData($modal, rowData);
                if (rowData.status_training_reference === "cancelled") {
                    $modal.find("#btn-toggle-edit").addClass("hidden");
                    $modal.find("#btn-submit-action").addClass("hidden");

                    $modal
                        .find(".edit-indicator")
                        .text("Data tidak aktif (Read Only)")
                        .addClass("text-red-600");
                } else {
                    $modal.find("#btn-toggle-edit").removeClass("hidden");
                    $modal.find("#btn-submit-action").removeClass("hidden");

                    $modal
                        .find(".edit-indicator")
                        .text("Pratinjau Data")
                        .removeClass("text-red-600");
                }

                $modal.removeClass("hidden").show();
            }
        });

    $body.on("click", "#btn-toggle-edit", function () {
        const $modal = $(this).closest(".u-modal");
        const isEditing = $modal.hasClass("is-editing-active");
        toggleEditMode($modal, !isEditing);
    });

    $body.on("click", "#btn-submit-action", function () {
        const $modal = $(this).closest(".u-modal");

        if (isCancelled($modal)) {
            Swal.fire("Info", "Data sudah tidak aktif", "info");
            return;
        }

        const isEditMode = $modal.hasClass("is-editing-active");
        const id = $modal.attr("data-current-id");

        if (isEditMode) {
            // LOGIKA UPDATE (AJAX)
            const formData = $modal.find("#lna-detail-form").serialize();
            handleUpdateAction(id, formData, $modal);
        } else {
            // LOGIKA HAPUS (EX: Approve/Decline lama anda)
            handleDeleteAction(id, $modal);
        }
    });

    // 2. Handler Tutup Modal (Tombol dengan atribut data-modal-close atau tombol Batal)
    $body.on("click", "[data-modal-close], .u-btn--ghost", function () {
        $(this)
            .closest(".u-modal")
            .fadeOut(150, function () {
                $(this).addClass("hidden").hide();
            });
    });

    $body.on("click", "#btn-approve-request", function () {
        const $modal = $(this).closest(".u-modal");
        const id = $modal.attr("data-current-id");
        const modalId = $modal.attr("id");
        const note = $modal.find("#catatan").val();

        const reloadTable = () => {
            const tableId = $modal.attr("data-table-id");

            if (tableId) {
                DataTableManager.reload(tableId);
            }
        };

        if (modalId === "training-peserta-modal") {
            console.log("id data", id);
            executeApprove(id, reloadTable, note);
        } else {
            executeApproveReference(id, reloadTable, note);
        }
    });

    $body.on("click", "#btn-decline-request", function () {
        const $modal = $(this).closest(".u-modal");
        const id = $modal.attr("data-current-id");
        const modalId = $modal.attr("id");
        const note = $modal.find("#catatan").val();

        const reloadTable = () => {
            const tableId = $modal.attr("data-table-id");

            if (tableId) {
                DataTableManager.reload(tableId);
            }
        };

        if (modalId === "training-peserta-modal") {
            executeReject(id, reloadTable, note);
        } else {
            executeRejectPengajuan(id, reloadTable, note);
        }
    });
};

const populateModalData = ($modal, data) => {
    // Mapping ID Modal (Opsional: untuk logika spesifik berdasarkan modal)
    $modal.attr("data-current-id", data.id);
    $modal.attr("data-status", data.status_training_reference);
    $modal.find("#edit-id").val(data.id);

    // Informasi Dasar (Menggunakan selector lama & baru agar kompatibel)
    $modal
        .find(".detail-judul-text, .detail-judul_sertifikasi")
        .text(data.judul_sertifikasi || "-");
    $modal
        .find(".detail-unit, .detail-unit_kerja")
        .text(data.unit_kerja || "-");
    $modal.find(".detail-penyelenggara").text(data.penyelenggara || "-");
    $modal.find(".detail-jumlah_jam").text(data.jumlah_jam || "-");
    $modal.find(".detail-no").text(data.no || "-");

    // Status (Handle dua versi status)
    $modal
        .find(".detail-status_training_reference")
        .text(`Status: ${data.status_training_reference || "-"}`);
    $modal
        .find(".detail-status_approval_training")
        .text(`Status: ${data.status_approval_training || "-"}`);

    // Penanganan Waktu (Satu tanggal vs Range)
    $modal
        .find(".detail-waktu_pelaksanaan")
        .text(data.waktu_pelaksanaan || "-");
    $modal
        .find(".detail-tanggal_mulai")
        .text(formatDate(data.tanggal_mulai) || "-");
    $modal
        .find(".detail-tanggal_berakhir")
        .text(formatDate(data.tanggal_berakhir) || "-");

    // Proyek & Fungsi
    $modal
        .find(".detail-proyek, .detail-nama_proyek")
        .text(data.nama_proyek || "-");
    $modal.find(".detail-fungsi").text(data.fungsi || "-");
    $modal
        .find(".detail-portofolio, .detail-jenis_portofolio")
        .text(data.jenis_portofolio || "-");
    $modal
        .find(".detail-jenis_pelatihan, .detail-jenis_pelatihan")
        .text(data.jenis_pelatihan || "-");

    // Informasi Biaya
    $modal
        .find(".detail-biaya_pelatihan")
        .text(formatRupiah(data.biaya_pelatihan || 0));
    $modal
        .find(".detail-realisasi_biaya_pelatihan")
        .text(formatRupiah(data.realisasi_biaya_pelatihan || 0));

    $modal.find("#catatan").val("");

    renderApprovalTimeline(data.approvals || []);

    // Handle Peserta (Mapping data.peserta dari kolom baru)
    const pesertaData = data.peserta || data.nama_peserta;
    if (pesertaData) {
        $modal.find(".section-peserta").show();
        $modal.find(".detail-peserta").text(pesertaData);
    } else {
        $modal.find(".section-peserta").hide();
    }

    if (data.lampiran_penawaran) {
        const filename = data.lampiran_penawaran;
        const extension = filename.split(".").pop().toLowerCase();
        const viewUrl = `/training/training-request/${filename}`;

        let icon = "fa-file-alt"; // default
        if (extension === "pdf") icon = "fa-file-pdf text-red-500";
        else if (["jpg", "jpeg", "png"].includes(extension))
            icon = "fa-file-image text-blue-500";

        $modal.find(".detail-lampiran_penawaran").html(`
        <a href="${viewUrl}" target="_blank" class="u-btn u-btn--sm u-btn--light border u-hover-lift">
            <i class="fas ${icon} u-mr-xs"></i> Lihat Lampiran (${extension.toUpperCase()})
        </a>
    `);
    } else {
        $modal
            .find(".detail-lampiran_penawaran")
            .html(
                '<span class="u-muted u-text-sm italic">Tidak ada lampiran</span>',
            );
    }
};

const toggleEditMode = ($modal, isEditing) => {
    if (isCancelled($modal)) {
        return;
    }

    if (isEditing) {
        $modal.addClass("is-editing-active");
        $modal.find(".view-mode").addClass("hidden");
        $modal.find(".edit-mode").removeClass("hidden");

        $modal
            .find('input[name="penyelenggara"]')
            .val($modal.find(".detail-penyelenggara").text().trim());
        $modal
            .find('input[name="jumlah_jam"]')
            .val($modal.find(".detail-jumlah_jam").text().trim());
        $modal
            .find('input[name="waktu_pelaksanaan"]')
            .val($modal.find(".detail-waktu_pelaksanaan").text().trim());
        $modal
            .find('input[name="nama_proyek"]')
            .val($modal.find(".detail-nama_proyek").text().trim());
        $modal
            .find('input[name="fungsi"]')
            .val($modal.find(".detail-fungsi").text().trim());

        const jenisPortofolio = $modal.find(".detail-jenis_portofolio").text().trim();
        $modal.find('select[name="jenis_portofolio"]').val(jenisPortofolio);

        const jenisPelatihan = $modal.find(".detail-jenis_pelatihan").text().trim();
        $modal.find('select[name="jenis_pelatihan"]').val(jenisPelatihan);

        $modal.find('input[name="biaya_pelatihan"]').val(
            $modal
                .find(".detail-biaya_pelatihan")
                .text()
                .replace(/[^0-9]/g, ""),
        );

        // Ubah UI Tombol
        $modal.find("#btn-toggle-edit span").text("Batal");
        $modal.find("#btn-submit-action span").text("Simpan Perubahan");
        $modal
            .find("#btn-submit-action")
            .addClass("u-btn--brand u-btn--fill")
            .removeClass("u-btn--outline");
    } else {
        $modal.removeClass("is-editing-active");
        $modal.find(".view-mode").removeClass("hidden");
        $modal.find(".edit-mode").addClass("hidden");

        // Reset UI Tombol
        $modal.find("#btn-toggle-edit span").text("Edit");
        $modal.find("#btn-submit-action span").text("Hapus");
        $modal
            .find("#btn-submit-action")
            .removeClass("u-btn--brand u-btn--fill")
            .addClass("u-btn--outline");
    }
};

// --- DATATABLE INITIALIZATION ---

let isGlobalInitialized = false;
const DATA_TABLE_INSTANCES = {};

export const DataTableManager = {
    get(tableId) {
        return DATA_TABLE_INSTANCES[tableId] || null;
    },

    reload(tableId) {
        const dt = DATA_TABLE_INSTANCES[tableId];
        if (dt) {
            dt.ajax.reload(null, false);
        }
    },

    reloadAll() {
        Object.values(DATA_TABLE_INSTANCES).forEach((dt) => {
            dt.ajax.reload(null, false);
        });
    },
};

export function initGetDataTable(tableSelector, options = {}) {
    const $table = $(tableSelector);
    if (!$table.length) return;

    const tableId = $table.attr("id");
    const baseConfig = TABLE_CONFIGS[tableId];
    if (!baseConfig) return;

    if (DATA_TABLE_INSTANCES[tableId]) {
        return DATA_TABLE_INSTANCES[tableId];
    }

    // Inisialisasi sistem modal (hanya sekali)
    if (!isGlobalInitialized) {
        initModalSystem();
        isGlobalInitialized = true;
    }

    const dt = initDataTables(`#${tableId}`, {
        serverSide: true,
        ajax: async (data, callback) => {
            const params = new URLSearchParams({
                page: data.start / data.length + 1,
                per_page: data.length,
                search: data.search.value,
                order_by: data.columns[data.order[0]?.column]?.data || "",
                order_dir: data.order[0]?.dir || "",
            });

            try {
                const response = await getJSON(
                    `${baseConfig.apiEndpoint(options.unitId)}?${params}`,
                );
                callback({
                    draw: data.draw,
                    recordsTotal: response.pagination?.total || 0,
                    recordsFiltered: response.pagination?.total || 0,
                    data: baseConfig.dataMapper(response),
                });
            } catch (e) {
                callback({
                    draw: data.draw,
                    data: [],
                    recordsTotal: 0,
                    recordsFiltered: 0,
                });
            }
        },
        columns: baseConfig.columns.map((col) => ({
            data: ["no", "actions", "peserta"].includes(col) ? null : col,
            render: (d, t, r, m) =>
                COLUMN_RENDERERS[col]
                    ? COLUMN_RENDERERS[col](d, t, r, m, baseConfig)
                    : (d ?? "-"),
        })),
    });

    DATA_TABLE_INSTANCES[tableId] = dt;
    return dt;
}

// --- HELPERS ---
const handleUpdateAction = (id, formData, $modal) => {
    console.log("handleUpdateAction", id, formData, $modal);

    const $submitBtn = $modal.find("#btn-submit-action"); // Pastikan ID tombol sesuai
    const originalContent = $submitBtn.html();

    $.ajax({
        url: `/training/training-request/${id}/edit-data-lna`,
        method: "POST",
        data: formData,
        beforeSend: () => {
            $submitBtn
                .prop("disabled", true)
                .html(
                    '<i class="fas fa-spinner fa-spin u-mr-xs"></i> Menyimpan...',
                );
        },
        success: (res) => {
            $modal.fadeOut(200, function () {
                $(this).addClass("hidden").hide();
                toggleEditMode($modal, false);
            });

            Swal.fire("Berhasil", res.message, "success");

            // Reload table agar data terbaru muncul
            if ($.fn.DataTable.isDataTable(".dataTable")) {
                $(".dataTable").DataTable().ajax.reload(null, false);
            }
        },
        error: (xhr) => {
            const errorMsg =
                xhr.responseJSON?.message || "Terjadi kesalahan sistem";
            Swal.fire("Gagal", errorMsg, "error");
        },
        complete: () => {
            $submitBtn.prop("disabled", false).html(originalContent);
        },
    });
};

const renderApprovalTimeline = (approvals = []) => {
    const $container = $("#approval-timeline-container");
    $container.empty();

    if (!approvals.length) {
        $container.append(`
            <div class="u-text-center u-py-md u-muted u-text-xs italic">
                Belum ada riwayat
            </div>
        `);
        return;
    }

    approvals.forEach((item) => {
        const status = item.to_status || item.action || "-";
        const approverName = item.user_name ?? "System";

        const isApprove = status === "approved";
        const isReject = status === "rejected";

        const colorClass = isApprove
            ? "text-green-600"
            : isReject
              ? "text-red-600"
              : "text-sky-600";

        const dotColor = isApprove
            ? "#16a34a"
            : isReject
              ? "#dc2626"
              : "#0284c7";

        const date = item.created_at
            ? new Date(item.created_at).toLocaleString("id-ID", {
                  day: "2-digit",
                  month: "short",
                  year: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
              })
            : "-";

        $container.append(`
            <div class="u-mb-md u-pl-md u-relative border-l-2 border-gray-200">
                <span class="u-absolute"
                    style="
                        left:-7px;
                        top:4px;
                        width:12px;
                        height:12px;
                        border-radius:50%;
                        background:#fff;
                        border:2px solid ${dotColor};
                    ">
                </span>

                <div class="u-flex u-justify-between">
                    <span class="u-text-xs u-font-semibold ${colorClass}">
                        ${approverName} (${item.role})
                    </span>
                    <span class="u-text-xs u-muted">${date}</span>
                </div>

                <div class="u-text-xs u-mt-[2px]">
                    Ke Status:
                    <span class="u-font-semibold ${colorClass}">
                        ${status.replace(/_/g, " ")}
                    </span>
                </div>

                ${
                    item.note
                        ? `<div class="u-text-xs italic text-gray-600 u-mt-[2px]">
                            “${item.note}”
                           </div>`
                        : ""
                }
            </div>
        `);
    });
};

const handleDeleteAction = (id, $modal) => {
    console.log("handleDeleteAction", id);

    Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data yang dihapus akan dinonaktifkan.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal",
        reverseButtons: true,
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `/training/training-request/${id}/delete-lna`,
            method: "POST",
            data: {
                _method: "DELETE",
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            beforeSend: () => {
                Swal.fire({
                    title: "Menghapus...",
                    text: "Mohon tunggu",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
            },
            success: (res) => {
                $modal.fadeOut(200, function () {
                    $(this).addClass("hidden").hide();
                });

                Swal.fire("Berhasil", res.message, "success");

                // Reload table
                if ($.fn.DataTable.isDataTable(".dataTable")) {
                    $(".dataTable").DataTable().ajax.reload(null, false);
                }
            },
            error: (xhr) => {
                const status = xhr.status;
                const message =
                    xhr.responseJSON?.message || "Terjadi kesalahan sistem";

                if (status === 409) {
                    Swal.fire("Perhatian", message, "warning");
                } else if (status === 404) {
                    Swal.fire("Tidak ditemukan", message, "error");
                } else {
                    Swal.fire("Gagal", message, "error");
                }
            },
        });
    });
};

const formatRupiah = (v) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(v || 0);

const formatDate = (d) =>
    d
        ? new Date(d).toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "long",
              year: "numeric",
          })
        : "-";

const isCancelled = ($modal) => $modal.attr("data-status") === "cancelled";

const renderStatusBadge = (status) => {
    const STATUS_MAP = {
        approved: {
            label: "Approved",
            class: "bg-emerald-100 text-emerald-700 border border-emerald-200",
        },
        rejected: {
            label: "Rejected",
            class: "bg-red-100 text-red-700 border border-red-200",
        },
        in_review_gmvp: {
            label: "In Review GM/VP",
            class: "bg-sky-100 text-sky-700 border border-sky-200",
        },
        in_review_dhc: {
            label: "In Review DHC",
            class: "bg-sky-100 text-sky-700 border border-sky-200",
        },
        in_review_avpdhc: {
            label: "In Review AVP DHC",
            class: "bg-sky-100 text-sky-700 border border-sky-200",
        },
        in_review_vpdhc: {
            label: "In Review VP DHC",
            class: "bg-sky-100 text-sky-700 border border-sky-200",
        },
        active: {
            label: "Aktif",
            class: "bg-green-100 text-green-700 border border-green-200",
        },
        pending: {
            label: "Pending",
            class: "bg-yellow-100 text-yellow-700 border border-yellow-200",
        },
        cancelled: {
            label: "Cancelled",
            class: "bg-slate-100 text-slate-600 border border-slate-200",
        },
    };

    const config = STATUS_MAP[status] || {
        label: status || "-",
        class: "bg-gray-100 text-gray-600 border border-gray-200",
    };

    return `
        <span class="
            inline-flex items-center gap-1.5
            px-3 py-1
            rounded-full text-xs font-semibold
            whitespace-nowrap
            ${config.class}
        ">
            ${config.label}
        </span>
    `;
};
