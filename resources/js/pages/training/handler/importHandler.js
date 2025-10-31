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
            const res = await postFormData(url, formData);
            console.log("res import", res);

            if (res.status === "success") {
                alert(res.message);
                window.location.reload();
            }

            document.dispatchEvent(new CustomEvent("training:imported"));
        } catch (error) {
            alert("Gagal import data");
            console.error(error);
        }
    });
}
