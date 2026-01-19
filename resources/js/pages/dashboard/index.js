import { initModalHandler } from "@/utils/modal";
import { initGetDataEvaluationTable } from "./handler/getDataEvaluationHandler";
import { initUpdateRealisasiDateHandler } from "./handler/updateRealisasiDate";
import { getJSON } from "../../utils/fetch";

document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".btn-detail-anggaran").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const unitId = btn.dataset.unitId;

            // Buka Modal
            openAnggaranModal();

            const tbody = document.getElementById("modal-detail-body");
            if (tbody)
                tbody.innerHTML = `<tr><td colspan="4" class="u-text-center u-muted">Memuat data...</td></tr>`;

            try {
                // Menggunakan helper getJSON yang Anda miliki
                const data = await getJSON(
                    `/training/dashboard/${unitId}/get-detail-anggaran`
                );

                // Update Header & Summary
                // Gunakan optional chaining atau check null untuk keamanan
                const elUnitName = document.getElementById("modal-unit-name");
                const elLimit = document.getElementById("modal-limit");
                const elUsed = document.getElementById("modal-used");
                const elRem = document.getElementById("modal-remaining");
                const elPerc = document.getElementById("modal-percent");

                if (elUnitName)
                    elUnitName.innerText = data.unit_name || data.unit;
                if (elLimit)
                    elLimit.innerText = formatRupiah(data.summary.limit);
                if (elUsed) elUsed.innerText = formatRupiah(data.summary.used);
                if (elRem)
                    elRem.innerText = formatRupiah(data.summary.remaining);
                if (elPerc) elPerc.innerText = data.summary.percentage + "%";

                // Update Tabel
                if (tbody) {
                    tbody.innerHTML = "";
                    if (!data.details || data.details.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" class="u-text-center u-muted">Tidak ada riwayat penggunaan anggaran</td></tr>`;
                        return;
                    }

                    data.details.forEach((row) => {
                        tbody.innerHTML += `
                        <tr>
                            <td>
                                <div class="u-font-medium">${row.training}</div>
                            </td>
                            <td>
                                <div class="u-font-bold">${row.peserta}</div>
                                <div class="u-text-xs u-muted">${row.nik}</div> </td>
                            <td>
                                <div class="u-font-bold">${formatRupiah(row.biaya)}</div>
                            </td>
                            <td>
                                <div class="u-text-sm">${row.tanggal}</div>
                            </td>
                        </tr>
                    `;
                    });
                }
            } catch (error) {
                console.error("Fetch Error:", error);
                if (tbody)
                    tbody.innerHTML = `<tr><td colspan="4" class="u-text-center text-red-500">Gagal memuat data.</td></tr>`;
            }
        });
    });

    document
        .getElementById("close-anggaran-modal")
        ?.addEventListener("click", closeAnggaranModal);
});

const modal = document.getElementById("modal-anggaran");

const openAnggaranModal = () => {
    if (modal) {
        modal.style.display = "flex";
        modal.classList.add("u-modal--open");
    }
};

const closeAnggaranModal = () => {
    if (modal) {
        modal.style.display = "none";
        modal.classList.remove("u-modal--open");
    }
};

function formatRupiah(val) {
    if (val === null) return "-";
    return "Rp " + Number(val).toLocaleString("id-ID");
}
