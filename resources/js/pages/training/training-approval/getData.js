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
            "fungsi",
            "status_training_reference",
            "actions",
        ],
        dataMapper: (res) => res.data || [],
    },

    "training-request-table": {
        tableId: "training-request-table",
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
        return `<div class="text-center"><span class="u-badge ${
            hasFile ? "u-badge--success" : "u-badge--danger"
        }">${hasFile ? "Tersedia" : "Kosong"}</span></div>`;
    },

    status_approval_training: (d) => renderStatusBadge(d),
    status_training_reference: (d) => renderStatusBadge(d),

    actions: (data, type, row, meta, config) => `
        <div class="u-flex u-justify-center">
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

            const rowData = $(`#${tableId}`)
                .DataTable()
                .row($btn.closest("tr"))
                .data();
            const $modal = $(config.modalId);

            console.log("row data aaa", rowData);

            if ($modal.length && rowData) {
                toggleEditMode($modal, false);
                populateModalData($modal, rowData);
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
        const isEditMode = $modal.hasClass("is-editing-active");
        const id = $modal.attr("data-current-id");

        if (isEditMode) {
            // LOGIKA UPDATE (AJAX)
            const formData = $modal.find("#lna-detail-form").serialize();
            handleUpdateAction(id, formData, $modal);
        } else {
            // LOGIKA HAPUS (EX: Approve/Decline lama anda)
            handleDeleteAction(id);
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

        const reloadTable = () =>
            $(".dataTable").DataTable().ajax.reload(null, false);

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

        const reloadTable = () =>
            $(".dataTable").DataTable().ajax.reload(null, false);

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
    $modal.find(".detail-tanggal_mulai").text(data.tanggal_mulai || "-");
    $modal.find(".detail-tanggal_berakhir").text(data.tanggal_berakhir || "-");

    // Proyek & Fungsi
    $modal
        .find(".detail-proyek, .detail-nama_proyek")
        .text(data.nama_proyek || "-");
    $modal.find(".detail-fungsi").text(data.fungsi || "-");
    $modal
        .find(".detail-portofolio, .detail-jenis_portofolio")
        .text(data.jenis_portofolio || "-");

    // Informasi Biaya
    $modal
        .find(".detail-biaya_pelatihan, .detail-biaya_pelatihan")
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

    // Handle Lampiran (Khusus untuk Training Request)
    if (data.lampiran_penawaran) {
        $modal.find(".detail-lampiran_penawaran").html(`
            <a href="${data.lampiran_penawaran}" target="_blank" class="u-btn u-btn--sm u-btn--light">
                <i class="fas fa-download u-mr-xs"></i> Lihat Lampiran
            </a>
        `);
    } else {
        $modal
            .find(".detail-lampiran_penawaran")
            .html(
                '<span class="u-muted u-text-sm italic">Tidak ada lampiran</span>'
            );
    }
};

const toggleEditMode = ($modal, isEditing) => {
    if (isEditing) {
        $modal.addClass("is-editing-active");
        $modal.find(".view-mode").addClass("hidden");
        $modal.find(".edit-mode").removeClass("hidden");

        // Sync data dari view ke input
        // $modal
        //     .find('input[name="judul_sertifikasi"]')
        //     .val($modal.find(".judul_sertifikasi").text().trim());
        // $modal
        //     .find('input[name="unit"]')
        //     .val($modal.find(".detail-unit").text().trim());
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
        $modal
            .find('input[name="jenis_portofolio"]')
            .val($modal.find(".detail-jenis_portofolio").text().trim());
        $modal.find('input[name="biaya_pelatihan"]').val(
            $modal
                .find(".detail-biaya-pelatihan")
                .text()
                .replace(/[^0-9]/g, "")
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

export function initGetDataTable(tableBody, options = {}) {
    const tableId = $(tableBody).closest("table").attr("id");
    const baseConfig = TABLE_CONFIGS[tableId];
    if (!baseConfig) return;

    // Inisialisasi sistem modal (hanya sekali)
    if (!isGlobalInitialized) {
        initModalSystem();
        isGlobalInitialized = true;
    }

    return initDataTables(`#${tableId}`, {
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
                    `${baseConfig.apiEndpoint(options.unitId)}?${params}`
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
                    : d ?? "-",
        })),
    });
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
                    '<i class="fas fa-spinner fa-spin u-mr-xs"></i> Menyimpan...'
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

const renderApprovalTimeline = (approvals) => {
    const $container = $("#approval-timeline-container");
    $container.empty();

    if (!approvals?.length) {
        $container.append(
            '<div class="u-text-center u-py-md u-muted u-text-xs italic">Belum ada riwayat.</div>'
        );
        return;
    }

    approvals.forEach((item) => {
        const isApprove = item.action === "approve";
        const colorClass = isApprove ? "text-green-600" : "text-red-600";
        const bgClass = isApprove ? "bg-green-50" : "bg-red-50";

        // Format tanggal sederhana (bisa disesuaikan)
        const date = item.created_at
            ? new Date(item.created_at).toLocaleString("id-ID", {
                  day: "2-digit",
                  month: "short",
                  hour: "2-digit",
                  minute: "2-digit",
              })
            : "-";

        $container.append(`
            <div class="u-mb-md u-pl-md border-l-2 border-gray-200 u-relative">
                <div class="u-absolute" style="left: -7px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: white; border: 2px solid ${
                    isApprove ? "#10b981" : "#ef4444"
                }"></div>
                <div class="u-flex u-justify-between">
                    <span class="u-text-xs u-font-bold u-uppercase text-gray-800">${
                        item.role
                    }</span>
                    <span class="u-text-xs u-muted">${date}</span>
                </div>
                <div class="u-mt-xs u-p-xs u-rounded ${bgClass} border border-gray-100">
                    <p class="u-text-xs italic text-gray-600">"${
                        item.note || "Tanpa catatan"
                    }"</p>
                </div>
            </div>
        `);
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
const renderStatusBadge = (s) => {
    const MAP = {
        approved: "bg-blue-100 text-blue-700",
        rejected: "u-badge--danger",
        in_review_gmvp: "u-badge--info",
    };
    return `<div class="text-center"><span class="u-badge ${
        MAP[s] || "u-badge--secondary"
    }">${s?.toUpperCase() || "-"}</span></div>`;
};
