import { deleteJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol hapus data training
 * @param {Element} tableBody
 * @param {Function} reloadCallback
 */
export function initDeleteHandler(tableBody, reloadCallback) {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='delete']");
        if (!button) return;

        const id = button.dataset.id;
        const table = button.dataset.table;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menghapus data ini?",
            text: "Data yang sudah dihapus tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Menghapus...",
                text: "Mohon tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            let deleteUrl = ""

            console.log('table aaa', table);

            if (table === "data-lna-table") {
                deleteUrl = `/training/training-request/${id}/delete-lna`;
            }

            if (table === "training-request-table") {
                deleteUrl = `/training/training-request/${id}/delete-training-request`;
            }

            if (!deleteUrl) {
                console.error("Delete route not defined");
                return;
            }

            const res = await deleteJSON(deleteUrl);
            console.log('res delete lna', res);
            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });

                reloadCallback();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Menghapus",
                    text: res.message || "Gagal menghapus data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error("Error hapus:", error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menghapus data.",
            });
        }
    });
}
