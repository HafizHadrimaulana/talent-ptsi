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
            const res = await postFormData(url, formData);

            console.log("res", res);

            if (res.status === "success") {
                alert(res.message);
                window.location.reload();
            }

            // document.dispatchEvent(new CustomEvent("training:imported"));
        } catch (error) {
            alert("Gagal import data");
            console.error(error);
        }
    });
}
