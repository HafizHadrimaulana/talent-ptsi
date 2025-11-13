import { getJSON, postFormData } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody
 * @param {Function} reloadCallback
 */

export function initUploadCertifHandler(tableBody) {
    const modal = document.querySelector("#modal-upload-certif");
    const form = document.querySelector("#form-upload-certif");
    const cancelBtn = document.querySelector("#close-upload-certif");

    // Tampilkan modal & isi data
    function fillEvaluationForm(data) {
        document.querySelector("#training_id_upload").value = data.training_id;
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
                `/training/dashboard/${id}/get-detail-evaluation`
            );

            const monthDiff = getMonthDifference(
                res.data.end_date,
                res.data.realisasi_date
            );
            console.log("monthDiff", monthDiff);

            if (monthDiff > 3) {
                modal.classList.add("hidden");
                Swal.fire({
                    icon: "warning",
                    title: "Waktu Unggah Terlambat",
                    text: "Pengunggahan sertifikat sudah melewati batas waktu 3 bulan setelah tanggal realisasi.",
                });
                return;
            }

            if (res.status === "success") {
                console.log('response data', res.data);
                fillEvaluationForm(res.data);
                modal.classList.remove("hidden");
            } else {
                modal.classList.remove("hidden");
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal mengambil data evaluasi.",
                });
            }
        } catch (error) {
            modal.classList.add("hidden");
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Terjadi Kesalahan",
                text: "Tidak dapat memuat data dari server.",
            });
        }

        cancelBtn.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            console.log("form data", form);

            const formData = new FormData(form);

            try {
                console.log("form data", formData);
                const res = await postFormData(
                    `/training/dashboard/upload-certif-evaluation`,
                    formData
                );

                console.log("response post form certif", res);

                modal.classList.add("hidden");
                Swal.fire({
                    title: "Menyimpan Data...",
                    text: "Sedang menyimpan evaluasi.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                console.log("response post form", res);
                if (res.status === "success") {
                    modal.classList.add("hidden");
                    await Swal.fire({
                        icon: "success",
                        title: "Berhasil",
                        text: res.message || "Evaluasi berhasil disimpan!",
                        timer: 2000,
                        showConfirmButton: false,
                    });
    
                    form.reset();
                    location.reload();
                } else {
                    modal.classList.add("hidden");
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: res.message || "Gagal menyimpan evaluasi.",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            } catch (error) {
                console.error("Error submit evaluasi:", error);
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Kesalahan Server",
                    text: "Terjadi kesalahan saat menyimpan evaluasi.",
                });
            }
        });
    });
}
