import { getJSON } from "@/utils/fetch";
import { initDeleteHandler } from "./handler/deleteHandler";
import { initEditHandler } from "./handler/editHandler";
import { initApproveHandler } from "./handler/approveHandler";
import { initRejectHandler } from "./handler/rejectHandler";
import { initDragDropUpload } from "./handler/dragDropImport";

export function initGetDataTable(tableBody) {
    const userRole = window.currentUserRole;

    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="17" class="text-center">Tidak ada data</td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = data
            .map((item, index) => {
                let actionButtons = "";
                let jenisPelatihanCell = "";

                if (userRole === "DHC") {
                    jenisPelatihanCell = `
                        <select class="jenis-pelatihan-select border border-gray-300 rounded p-1 w-full" data-id="${
                            item.id
                        }">
                            <option value="" ${
                                !item.jenis_pelatihan ? "selected" : ""
                            }>-- Pilih Jenis Pelatihan --</option>
                            <option value="EDP - Sertifikat Profesi" ${
                                item.jenis_pelatihan ===
                                "EDP - Sertifikat Profesi"
                                    ? "selected"
                                    : ""
                            }>
                                EDP - Sertifikat Profesi
                            </option>
                            <option value="EDP - Sertifikat Industri" ${
                                item.jenis_pelatihan ===
                                "EDP - Sertifikat Industri"
                                    ? "selected"
                                    : ""
                            }>
                                EDP - Sertifikat Industri
                            </option>
                        </select>
                    `;
                } else {
                    jenisPelatihanCell = `${item.jenis_pelatihan ?? "-"}`;
                }

                if (userRole === "DHC") {
                    actionButtons = `
                    <button class="u-btn u-btn--brand u-hover-lift" data-action="edit" data-id="${item.id}">
                        Edit
                    </button>
                    <button class="u-btn u-btn--brand u-hover-lift" data-action="delete" data-id="${item.id}">
                        Hapus
                    </button>
                `;
                } else if (
                    ["GM/VP Unit", "VP DHC", "DBS Unit"].includes(userRole)
                ) {
                    actionButtons = `
                    <button class="u-btn u-btn--brand u-hover-lift" data-action="approve" data-id="${item.id}">
                        Terima
                    </button>
                    <button class="u-btn u-btn--brand u-hover-lift" data-action="reject" data-id="${item.id}">
                        Tolak
                    </button>
                `;
                } else {
                    actionButtons = `
                    <button class="u-btn u-btn--brand u-hover-lift" data-action="details" data-id="${item.id}">
                        Details
                    </button>`;
                }

                return `
                <tr>
                    <td>
                        <input type="checkbox" name="selected[]" value="${
                            item.id
                        }" class="row-checkbox">
                    </td>
                    <td>
                        <div class="u-flex u-items-center u-gap-sm">
                            <div class="u-badge u-badge--primary">
                            ${index + 1}
                            </div>
                        </div>
                    </td>
                    <td>${jenisPelatihanCell}</td>
                    <td>${item.nik ?? "-"}</td>
                    <td>${item.nama_peserta ?? "-"}</td>
                    <td>${item.status_pegawai ?? "-"}</td>
                    <td>${item.jabatan_saat_ini ?? "-"}</td>
                    <td>${item.unit_kerja ?? "-"}</td>
                    <td>${item.judul_sertifikasi ?? "-"}</td>
                    <td>${item.penyelenggara ?? "-"}</td>
                    <td>${item.jumlah_jam ?? "-"}</td>
                    <td>${item.waktu_pelaksanaan ?? "-"}</td>
                    <td>${formatRupiah(item.biaya_pelatihan)}</td>
                    <td>${formatRupiah(item.uhpd)}</td>
                    <td>${formatRupiah(item.biaya_akomodasi)}</td>
                    <td>${formatRupiah(item.estimasi_total_biaya)}</td>
                    <td>${item.nama_proyek ?? "-"}</td>
                    <td>${item.jenis_portofolio ?? "-"}</td>
                    <td>${item.fungsi ?? "-"}</td>
                    <td>${item.status_approval.status_approval ?? "-"}</td>
                    <td class="cell-actions text-center">
                        ${actionButtons}
                    </td>
                </tr>
            `;
            })
            .join("");
    }

    async function loadTrainings() {
        try {
            const data = await getJSON("/training/list");
            if (data.status === "success") renderTable(data.data.data);
        } catch (error) {
            console.error("Gagal memuat data:", error);
            tableBody.innerHTML = `<tr><td colspan="17" class="text-center">Gagal memuat data</td></tr>`;
        }
    }

    function formatRupiah(value) {
        if (value == null || value === "" || value === "-") return "-";

        const number = parseFloat(value);

        if (isNaN(number)) return value;

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(number);
    }

    function formatDate(
        dateValue,
        options = { day: "2-digit", month: "long", year: "numeric" }
    ) {
        if (!dateValue) return "-";

        try {
            const date = new Date(dateValue);
            if (isNaN(date)) return "-";

            return date.toLocaleDateString("id-ID", options);
        } catch (error) {
            console.error("Gagal memformat tanggal:", error);
            return "-";
        }
    }

    loadTrainings();

    initEditHandler(tableBody, loadTrainings);
    initDeleteHandler(tableBody, loadTrainings);
    initApproveHandler(tableBody);
    initRejectHandler(tableBody);
    initDragDropUpload();
}
