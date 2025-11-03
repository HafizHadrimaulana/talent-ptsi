import { getJSON } from "@/utils/fetch";
import { initDeleteHandler } from "./deleteHandler";
import { initEditHandler } from "./editHandler";
import { initApproveHandler } from "./approveHandler";
import { initRejectHandler } from "./rejectHandler";

export function initGetDataTable() {
    
    const tableBody = document.querySelector("#training-table tbody");
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

                if (userRole === "SDM Unit") {
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
                    <td>${item.nik ?? "-"}</td>
                    <td>${item.nama_peserta ?? "-"}</td>
                    <td>${item.status_pegawai ?? "-"}</td>
                    <td>${item.jabatan_saat_ini ?? "-"}</td>
                    <td>${item.unit_kerja ?? "-"}</td>
                    <td>${item.judul_sertifikasi ?? "-"}</td>
                    <td>${item.penyelenggara ?? "-"}</td>
                    <td>${item.jumlah_jam ?? "-"}</td>
                    <td>${item.waktu_pelaksanaan ?? "-"}</td>
                    <td>${item.nama_proyek ?? "-"}</td>
                    <td>${item.biaya_pelatihan ?? "-"}</td>
                    <td>${item.uhpd ?? "-"}</td>
                    <td>${item.biaya_akomodasi ?? "-"}</td>
                    <td>${item.estimasi_total_biaya ?? "-"}</td>
                    <td>${item.jenis_portofolio ?? "-"}</td>
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

    loadTrainings();

    initEditHandler(tableBody, loadTrainings);
    initDeleteHandler(tableBody, loadTrainings);
    initApproveHandler(tableBody);
    initRejectHandler(tableBody);

    document.addEventListener("training:imported", loadTrainings);
}
