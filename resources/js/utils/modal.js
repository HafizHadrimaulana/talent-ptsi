export function initModalHandler(
    openBtnSelector,
    modalSelector,
    closeBtnSelector
) {
    const openBtn = document.querySelector(openBtnSelector);
    const modal = document.querySelector(modalSelector);
    const closeBtn = document.querySelector(closeBtnSelector);

    if (!openBtn || !modal || !closeBtn) return;

    // FUNGSI BUKA
    openBtn.addEventListener("click", () => {
        modal.classList.remove("hidden");
        modal.style.display = "flex";
        modal.hidden = false;
    });

    // FUNGSI TUTUP
    const closeModal = () => {
        modal.classList.add("hidden");
        modal.style.display = "none";
        modal.hidden = true;
    };

    closeBtn.addEventListener("click", closeModal);

    // FUNGSI KLIK LUAR (Overlay)
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
}
