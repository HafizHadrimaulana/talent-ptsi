import { getJSON, postFormData } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody
 * @param {Function} reloadCallback
 */

export function initUploadCertifHandler(tableBody) {
    const modal = document.querySelector("#modal-upload-certif");
    const form = document.querySelector("#upload-certif-form");
    const cancelBtn = document.querySelector("#close-upload-certif");

    const trainingIdInput = document.querySelector("#training_id");
    const namaPelatihanInput = document.querySelector("#nama_pelatihan");
    const namaPesertaInput = document.querySelector("#nama_peserta");

    // Tampilkan modal & isi data
    function showModal(data) {
        trainingIdInput.value = data.id;
        namaPelatihanInput.value = data.nama_pelatihan ?? "-";
        namaPesertaInput.value = data.nama_peserta ?? "-";
        modal.classList.remove("hidden");
    }

    function getMonthDifference(endDate, realisasiDate) {
        if (!endDate || !realisasiDate) {
            console.warn("Tanggal tidak lengkap:", { endDate, realisasiDate });
            return null;
        }

        const d1 = new Date(endDate);
        const d2 = new Date(realisasiDate);

        if (isNaN(d1) || isNaN(d2)) {
            console.warn("Format tanggal tidak valid:", {
                endDate,
                realisasiDate,
            });
            return null;
        }

        return (
            (d2.getFullYear() - d1.getFullYear()) * 12 +
            (d2.getMonth() - d1.getMonth())
        );
    }

    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='upload-certif']");
        if (!button) return;

        const id = button.dataset.id;

        try {
            const res = await getJSON(
                `/training/dashboard/${id}/data-upload-certif`
            );

            console.log("pp", res);

            const monthDiff = getMonthDifference(
                res.data.end_date,
                res.data.realisasi_date
            );
            console.log("monthDiff", monthDiff);

            if (monthDiff > 3) {
                alert(
                    "Pengunggahan sertifikat sudah melewati batas waktu 3 bulan setelah tanggal realisasi."
                );
                return;
            }

            if (res.status === "success") {
                showModal(res.data);
            } else {
                alert("Gagal mengambil data sertifikat.");
            }
        } catch (error) {
            console.error(error);
            alert("Gagal memuat data");
        }

        cancelBtn.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            console.log("form data", form);

            const formData = new FormData(form);

            try {
                const res = await postFormData(
                    `/training/dashboard/upload-certif-evaluation`,
                    formData
                );
                console.log("response post form", res);
                if (res.status === "success") {
                    alert(res.message);
                    // modal.classList.add("hidden");
                    location.reload();
                } else {
                    alert("Gagal memperbarui data");
                }
            } catch (error) {
                alert("Gagal memperbarui data");
            }
        });
    });
}
