import { getJSON, postFormData } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody
 * @param {Function} reloadCallback 
 */

export function initUploadCertifHandler(tableBody, reloadCallback) {
    const modal = document.querySelector("#modal-upload-certif");
    const form = document.querySelector("#upload-certif-form");
    const cancelBtn = document.querySelector("#close-upload-certif");

    const trainingIdInput = document.querySelector("#evaluation_id");
    const namaPelatihanInput = document.querySelector("#nama_pelatihan");
    const namaPesertaInput = document.querySelector("#nama_peserta");

    // Tampilkan modal & isi data
    function showModal(data) {
        trainingIdInput.value = data.id;
        namaPelatihanInput.value = data.nama_pelatihan ?? "-";
        namaPesertaInput.value = data.nama_peserta ?? "-";
        modal.classList.remove("hidden");
    }

    // Tutup modal
    function hideModal() {
        modal.classList.add("hidden");
        form.reset();
    }

    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='upload-certif']");
        if (!button) return;

        const id = button.dataset.id;

        try {
            const res = await getJSON(
                `/training/dashboard/${id}/data-upload-certif`
            );

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
                const res = await postFormData(`/training/dashboard/upload-certif-evaluation`, formData);
                console.log("response post form", res);
                if (res.status === "success") {
                    alert(res.message);
                    // modal.classList.add("hidden");
                    // reloadCallback();
                } else {
                    alert("Gagal memperbarui data");
                }
            } catch (error) {
                alert("Gagal memperbarui data");
            }
        });
    });
}
