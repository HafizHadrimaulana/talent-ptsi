import { getJSON } from "@/utils/fetch";
import { initUploadCertifHandler } from "./uploadCertifEvaluation";

export function initGetDataEvaluationTable() {
    const tableBody = document.querySelector("#dashboard-table tbody");

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
                return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.nama_pelatihan ?? '-'}</td>
                    <td>${item.nama_peserta ?? '-'}</td>
                    <td>
                        ${
                            item.realisasi_date
                                ? `${item.realisasi_date}`
                                : `<input 
                                        type="date" 
                                        class="realisasi-date-input border border-gray-300 rounded p-1 w-full" 
                                        data-id="${item.id}" 
                                        value=""
                                        placeholder="Pilih tanggal realisasi"
                                    >`
                        }
                    </td>
                    <td>${item.certificate_document ? 'Tersedia' : 'Belum Ada'}</td>
                    <td class="cell-actions text-center">
                        <button class="u-btn u-btn--brand" data-action="upload-certif" data-id="${item.id}">
                            Upload Sertifikat
                        </button>
                        <button class="u-btn u-btn--brand" data-action="input-evaluation" data-id="${item.id}">
                            Input Evaluasi
                        </button>
                        <button class="u-btn u-btn--brand" data-action="edit" data-id="${item.id}">
                            Edit
                        </button>
                    </td>
                </tr>
            `;
            })
            .join("");
    }

    async function loadTrainings() {
        try {
            const data = await getJSON("/training/dashboard/data-evaluation");
            console.log('data', data)
            if (data.status === "success") renderTable(data.data);
        } catch (error) {
            console.error("Gagal memuat data:", error);
            tableBody.innerHTML = `<tr><td colspan="17" class="text-center">Gagal memuat data</td></tr>`;
        }
    }

    loadTrainings();

    initUploadCertifHandler(tableBody, loadTrainings);
}
