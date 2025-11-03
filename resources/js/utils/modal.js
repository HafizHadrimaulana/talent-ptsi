export function initModalHandler(openBtnSelector, modalSelector, closeBtnSelector) {
    const openBtn = document.querySelector(openBtnSelector);
    const modal = document.querySelector(modalSelector);
    const closeBtn = document.querySelector(closeBtnSelector);

    if (!openBtn || !modal || !closeBtn) return; 

    openBtn.addEventListener("click", () => modal.classList.remove("hidden"));
    closeBtn.addEventListener("click", () => modal.classList.add("hidden"));

    console.log('asd')

    // Tutup modal kalau klik di luar konten modal
    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.classList.add("hidden");
    });

}