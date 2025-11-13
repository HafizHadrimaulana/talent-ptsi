import { postFormData } from "@/utils/fetch";

export function initInputHandler() {
    const inputButton = document.querySelector(".btn-add");
    const modal = document.querySelector("#add-modal");
    const closeModal = document.querySelector("#close-input-modal");
    const inputForm = document.querySelector("#add-form");

    if (inputButton && modal && closeModal) {
        inputButton.addEventListener("click", () => {
            modal.classList.remove("hidden");
        });

        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    inputForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(inputForm);
        const url = "/training/input";

        try {
            modal.classList.add("hidden");
            Swal.fire({
                title: "Menyimpan Data...",
                text: "Harap tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postFormData(url, formData);

            console.log("res", res);
            Swal.close();

            if (res.status === "success") {
                modal.classList.add("hidden");
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Data berhasil disimpan.",
                    timer: 2000,
                    showConfirmButton: false,
                });

                inputForm.reset();
                window.location.reload();
                return;
            }
            await Swal.fire({
                icon: "error",
                title: "Gagal Menyimpan",
                text: res.message || "Terjadi kesalahan saat menyimpan data.",
                confirmButtonText: "Tutup",
            });
        } catch (error) {
            Swal.close();
            console.error("Error saat input:", error);

            await Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan pada server. Silakan coba lagi.",
                confirmButtonText: "OK",
            });
        }
    });
}
