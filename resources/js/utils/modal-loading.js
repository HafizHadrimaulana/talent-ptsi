/**
 * Global Modal Loading Overlay System
 * Beautiful iOS liquid glass loading overlay for modals
 * Matches alert.js design for consistent UX
 * 
 * Usage:
 *   import { showModalLoading, hideModalLoading } from '@/utils/modal-loading';
 *   
 *   showModalLoading('#myModal', 'Loading data...');
 *   hideModalLoading('#myModal');
 */

/**
 * Show loading overlay in modal
 * @param {string} modalSelector - CSS selector for modal (e.g., '#editContractModal')
 * @param {string} message - Loading message to display
 * @returns {HTMLElement|null} Loading overlay element
 */
export const showModalLoading = (modalSelector, message = 'Memuat data...') => {
    const modal = document.querySelector(modalSelector);
    if (!modal) {
        console.warn(`Modal not found: ${modalSelector}`);
        return null;
    }
    
    const modalBody = modal.querySelector('.u-modal__body');
    if (!modalBody) {
        console.warn('Modal body not found');
        return null;
    }
    
    // Remove existing loading if any
    hideModalLoading(modalSelector);
    
    // Reset scroll position
    modalBody.scrollTop = 0;
    modalBody.scrollLeft = 0;
    
    // Create loading overlay
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'modal-loading-overlay';
    loadingDiv.innerHTML = `
        <div class="modal-loading-container">
            <div class="modal-loading-spinner">
                <div class="spinner-drop"></div>
                <div class="spinner-drop"></div>
                <div class="spinner-drop"></div>
            </div>
            <div class="modal-loading-text">${message}</div>
        </div>
    `;
    
    modalBody.appendChild(loadingDiv);
    
    return loadingDiv;
};

/**
 * Hide loading overlay from modal
 * @param {string} modalSelector - CSS selector for modal
 * @returns {boolean} Success status
 */
export const hideModalLoading = (modalSelector) => {
    const modal = document.querySelector(modalSelector);
    if (!modal) return false;
    
    const modalBody = modal.querySelector('.u-modal__body');
    if (!modalBody) return false;
    
    const loadingDiv = modalBody.querySelector('.modal-loading-overlay');
    if (loadingDiv) {
        loadingDiv.remove();
        return true;
    }
    
    return false;
};

/**
 * Check if modal is currently loading
 * @param {string} modalSelector - CSS selector for modal
 * @returns {boolean} Loading status
 */
export const isModalLoading = (modalSelector) => {
    const modal = document.querySelector(modalSelector);
    if (!modal) return false;
    
    const modalBody = modal.querySelector('.u-modal__body');
    if (!modalBody) return false;
    
    return modalBody.querySelector('.modal-loading-overlay') !== null;
};

/**
 * Update loading message
 * @param {string} modalSelector - CSS selector for modal
 * @param {string} message - New loading message
 * @returns {boolean} Success status
 */
export const updateModalLoadingMessage = (modalSelector, message) => {
    const modal = document.querySelector(modalSelector);
    if (!modal) return false;
    
    const loadingText = modal.querySelector('.modal-loading-text');
    if (loadingText) {
        loadingText.textContent = message;
        return true;
    }
    
    return false;
};

// Expose globally for backward compatibility and Blade templates
if (typeof window !== 'undefined') {
    window.showModalLoading = showModalLoading;
    window.hideModalLoading = hideModalLoading;
    window.isModalLoading = isModalLoading;
    window.updateModalLoadingMessage = updateModalLoadingMessage;
}
