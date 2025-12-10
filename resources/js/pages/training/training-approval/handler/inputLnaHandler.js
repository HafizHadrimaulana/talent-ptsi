import { postFormData, getJSON } from "@/utils/fetch";

export function initInputLnaHandler(modalSelector) {
    console.log("input lna", modalSelector);

    const modal = document.querySelector(modalSelector);
    const inputForm = document.querySelector("#lna-input-form");

    inputForm.addEventListener("submit", (e) => {
        e.preventDefault();
    });

    // ==== HANDLE SUBMIT ====
    inputForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const fd = new FormData(inputForm);

        console.log('fd', fd);

        Swal.fire({
            title: "Menyimpan...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            modal.classList.add("hidden");

            Swal.fire({
                title: "Menyimpan Data...",
                text: "Harap tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postFormData("/training/training-request/lna/store", fd);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Data berhasil disimpan.",
                    timer: 2000,
                    showConfirmButton: false,
                });

                // form.reset();
                // window.location.reload();
                return;
            }

            Swal.fire({
                icon: "error",
                title: "Gagal Menyimpan",
                text: res.message || "Terjadi kesalahan saat menyimpan data.",
            });
        } catch (err) {
            Swal.close();
            console.error("Error:", err);

            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan pada server. Silakan coba lagi.",
            });
        }
    });
}
