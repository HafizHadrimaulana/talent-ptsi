export function openModal(modalOrId) {
    let modal = modalOrId;
    
    if (typeof modalOrId === 'string') {
        const id = modalOrId.startsWith('#') ? modalOrId.substring(1) : modalOrId;
        modal = document.getElementById(id);
    }

    if (!modal) return;

    // Show with smooth animation
    modal.classList.remove('hidden', 'is-hidden');
    modal.hidden = false;
    modal.style.display = 'flex';
    
    // Force reflow untuk trigger CSS transition
    void modal.offsetHeight;
    
    document.body.classList.add('overflow-hidden', 'modal-open');
    document.body.style.overflow = 'hidden';
}

export function closeModal(modalOrId) {
    let modal = modalOrId;

    if (typeof modalOrId === 'string') {
        const id = modalOrId.startsWith('#') ? modalOrId.substring(1) : modalOrId;
        modal = document.getElementById(id);
    }

    if (!modal) {
        modal = document.querySelector('.u-modal:not(.hidden):not([hidden])');
    }

    if (!modal) return;

    // Trigger fade out animation dengan addClass dulu
    modal.classList.add('is-hidden');
    
    // Tunggu animation selesai baru hide
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.hidden = true;
        modal.style.display = 'none';
    }, 280); // Match CSS transition duration

    const otherOpen = document.querySelectorAll('.u-modal:not(.hidden):not([hidden])');
    const isAnyOtherOpen = Array.from(otherOpen).some(el => el !== modal && el.style.display !== 'none');

    if (!isAnyOtherOpen) {
        document.body.classList.remove('overflow-hidden', 'modal-open');
        document.body.style.overflow = ''; 
    }
}

export function initModalHandler() {
    document.addEventListener('click', (e) => {
        const target = e.target;

        if (
            target.classList.contains('u-modal') || 
            target.classList.contains('u-modal__backdrop') ||
            target.closest('.js-close-modal') ||
            target.closest('[data-modal-dismiss]')
        ) {
            const modal = target.closest('.u-modal');
            if (modal) {
                e.preventDefault();
                closeModal(modal);
            }
        }

        const openBtn = target.closest('[data-modal-target]');
        if (openBtn) {
            e.preventDefault();
            const targetId = openBtn.dataset.modalTarget;
            if (targetId) openModal(targetId);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
}