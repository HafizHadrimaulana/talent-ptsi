import { deleteJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol hapus data training
 * @param {Element} tableBody - elemen <tbody> tempat tombol berada
 * @param {Function} reloadCallback - fungsi untuk me-refresh data tabel
 */
export function initDeleteHandler(tableBody, reloadCallback) {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='delete']");
        if (!button) return;

        const id = button.dataset.id;

        if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
            try {
                const res = await deleteJSON(`/training/delete/${id}`);

                if (res.status === "success") {
                    alert(res.message);
                    reloadCallback();
                } else {
                    alert("Gagal menghapus data");
                }
            } catch (error) {
                console.error("Error hapus:", error);
                alert("Terjadi kesalahan saat menghapus data");
            }
        }
    });
}
