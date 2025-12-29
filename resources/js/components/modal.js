export function openModal(selector) {
    document.querySelector(selector)?.classList.remove("hidden");
}
export function closeModal(selector) {
    document.querySelector(selector)?.classList.add("hidden");
}
