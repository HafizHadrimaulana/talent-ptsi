export function initModalHandler(
    openBtnSelector,
    modalSelector,
    closeBtnSelector
) {
    const openBtn = document.querySelector(openBtnSelector);
    const modal = document.querySelector(modalSelector);
    const closeBtn = document.querySelector(closeBtnSelector);

    if (!openBtn || !modal || !closeBtn) return;

    openBtn.addEventListener("click", () => {
        modal.classList.remove("hidden");
        modal.hidden = false;
    });

    closeBtn.addEventListener("click", () => {
        modal.hidden = true;
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.hidden = false;
        }
    });
}
