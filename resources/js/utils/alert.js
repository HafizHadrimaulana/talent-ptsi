/**
 * Modern Toast Notification System
 * Lightweight, non-blocking, mobile-friendly alerts
 * No backdrop conflicts, perfect for all devices
 */

/**
 * Base Toast Configuration - Responsive & Dynamic
 */
const getToastPosition = () => {
    const width = window.innerWidth;
    if (width < 640) return 'top-center'; // Mobile
    return 'top-end'; // Desktop
};

const baseToastConfig = {
    toast: true,
    position: getToastPosition(),
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'swal2-toast-glass',
        title: 'swal2-toast-title',
        icon: 'swal2-toast-icon'
    },
    showClass: { popup: 'swal2-toast-show' },
    hideClass: { popup: 'swal2-toast-hide' },
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', () => window.Swal?.stopTimer());
        toast.addEventListener('mouseleave', () => window.Swal?.resumeTimer());
    }
};

export const showSuccess = (message, title = 'Berhasil!') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        icon: 'success',
        title: title,
        text: message,
        timer: 2500
    });
};

export const showError = (message, title = 'Gagal!') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        icon: 'error',
        title: title,
        text: message,
        timer: 4000
    });
};

export const showWarning = (message, title = 'Perhatian!') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        icon: 'warning',
        title: title,
        text: message,
        timer: 3500
    });
};

export const showInfo = (message, title = 'Info') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        icon: 'info',
        title: title,
        text: message
    });
};

export const showLoading = (message = 'Memproses...') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        title: message,
        timer: 0,
        timerProgressBar: false,
        allowOutsideClick: true,
        didOpen: () => window.Swal?.showLoading()
    });
};

export const showConfirm = async (message, options = {}) => {
    const result = await window.Swal?.fire({
        title: options.title || 'Konfirmasi',
        text: message,
        icon: options.icon || 'question',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Ya',
        cancelButtonText: options.cancelText || 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'swal2-confirm-popup',
            confirmButton: 'swal2-confirm-btn',
            cancelButton: 'swal2-cancel-btn'
        },
        buttonsStyling: false
    });
    return result?.isConfirmed === true;
};

export const showDeleteConfirm = async (itemName = 'item ini') => {
    return showConfirm(
        `Data ${itemName} akan dihapus permanen dan tidak dapat dikembalikan.`,
        {
            title: 'Hapus Data?',
            icon: 'warning',
            confirmText: 'Hapus',
            cancelText: 'Batal'
        }
    );
};

export const showAlert = (options = {}) => {
    if (!window.Swal) {
        console.error('SweetAlert2 not loaded');
        return Promise.reject(new Error('SweetAlert2 not loaded'));
    }
    if (!options.hasOwnProperty('toast')) {
        return window.Swal.fire({ ...baseToastConfig, ...options });
    }
    return window.Swal.fire(options);
};

export const closeAlert = () => window.Swal?.close();

export const showToast = (message, type = 'success') => {
    return window.Swal?.fire({
        ...baseToastConfig,
        icon: type,
        title: message
    });
};

// Expose globally
if (typeof window !== 'undefined') {
    window.showAlert = showAlert;
    window.showSuccess = showSuccess;
    window.showError = showError;
    window.showWarning = showWarning;
    window.showInfo = showInfo;
    window.showConfirm = showConfirm;
    window.showDeleteConfirm = showDeleteConfirm;
    window.showLoading = showLoading;
    window.closeAlert = closeAlert;
    window.showToast = showToast;
    
    // Re-calculate position on resize
    window.addEventListener('resize', () => {
        if (window.Swal?.isVisible()) {
            const currentToast = document.querySelector('.swal2-toast-glass');
            if (currentToast) {
                currentToast.style.transition = 'all 0.3s ease';
            }
        }
    });
}
