import { postFormData } from "@/utils/fetch";

export function initImportHandler() {
    const importButton = document.querySelector(".btn-import");
    const modal = document.querySelector("#import-modal");
    const closeModal = document.querySelector("#close-modal");
    const importForm = document.querySelector("#import-form");

    if (importButton && modal && closeModal) {
        importButton.addEventListener("click", () => {
            modal.classList.remove("hidden");
        });

        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    importForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(importForm);
        const url = "/training/import";

        try {
            modal.classList.add("hidden");
            Swal.fire({
                title: "Mengunggah Data...",
                text: "Harap tunggu, sedang memproses file import.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            const res = await postFormData(url, formData);
            console.log("res import", res);

            if (res.status === "success") {
                modal.classList.add("hidden");
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Data pelatihan berhasil diimport.",
                    confirmButtonText: "OK",
                    timer: 2000,
                    timerProgressBar: true,
                }).then(() => {
                    window.location.reload();
                });
            } else {
                modal.classList.add("hidden");
                Swal.fire({
                    icon: "error",
                    title: "Gagal!",
                    text: res.message || "Gagal mengimpor data pelatihan.",
                    confirmButtonText: "Coba Lagi",
                });
            }

            document.dispatchEvent(new CustomEvent("training:imported"));
        } catch (error) {
            console.error("error import", error);
            Swal.fire({
                icon: "error",
                title: "Terjadi Kesalahan!",
                text: "Gagal import data. Silakan coba lagi.",
                confirmButtonText: "OK",
            });
        }
    });
}
