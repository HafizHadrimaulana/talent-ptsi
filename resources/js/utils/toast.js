export function showToast(message, type = "success", duration = 5000) {
    const toast = document.getElementById("app-toast");
    if (!toast) return;

    const icons = {
        success: '<i class="fas fa-check-circle text-green-500"></i>',
        error: '<i class="fas fa-exclamation-circle text-red-500"></i>',
        info: '<i class="fas fa-info-circle text-blue-500"></i>',
    };

    toast.innerHTML = `
        <div style="font-size: 1.2rem;">${icons[type] || icons.info}</div>
        <div style="flex: 1;">
            <div style="font-weight: 700; font-size: 14px; margin-bottom: 2px;">${type.toUpperCase()}</div>
            <div style="font-size: 13px; opacity: 0.9;">${message}</div>
        </div>
    `;

    toast.className = `app-toast ${type} show`;

    setTimeout(() => {
        toast.classList.replace("show", "hidden");
    }, duration);
}

export function confirmToast({
    title = "Konfirmasi Tindakan",
    text = "Apakah Anda yakin ingin melanjutkan?",
    confirmText = "Ya, Lanjutkan",
    cancelText = "Batalkan",
} = {}) {
    return new Promise((resolve) => {
        const root = document.getElementById("confirm-toast-root");
        if (!root) return resolve(false);

        const overlay = document.createElement("div");
        overlay.className = "confirm-toast-overlay";

        const toast = document.createElement("div");
        toast.className = "confirm-toast";
        toast.innerHTML = `
            <div style="font-size: 4.5rem; margin-bottom: 20px; color: #f59e0b; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                <i class="fas fa-warning"></i>
            </div>
            <div class="confirm-toast__title">${title}</div>
            <div class="confirm-toast__text">${text}</div>
            <div class="u-flex u-gap-md justify-center">
                <button class="u-btn u-btn--accent cancel" style="width: 100px">${cancelText}</button>
                <button class="u-btn confirm" style="width: 130px; background: #641313; color: white;">${confirmText}</button>
            </div>
        `;

        root.appendChild(overlay);
        root.appendChild(toast);

        setTimeout(() => {
            toast.style.transform = "translate(-50%, -50%) scale(1)";
        }, 10);

        const cleanup = (result) => {
            toast.style.transform = "translate(-50%, -50%) scale(0.9)";
            toast.style.opacity = "0";
            setTimeout(() => {
                toast.remove();
                overlay.remove();
                resolve(result);
            }, 200);
        };

        toast.querySelector(".cancel").onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            cleanup(false);
        };

        toast.querySelector(".confirm").onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            cleanup(true);
        };
    });
}
