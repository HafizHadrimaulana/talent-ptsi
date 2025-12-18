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
            title: "Yakin ingin membatalkan data ini?",
            text: "Data yang sudah dibatalkan tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya",
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
            Swal.close();

            const { ok, statusCode, data } = res;

            if (ok && data?.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false,
                });

                reloadCallback();

            } else if (statusCode === 409 && data?.status === "warning") {
                await Swal.fire({
                    icon: "info",
                    title: "Informasi",
                    text: data.message || "Data sudah tidak aktif.",
                    timer: 2500,
                    showConfirmButton: false,
                });

                reloadCallback();

            } else if (statusCode === 404) {
                await Swal.fire({
                    icon: "error",
                    title: "Data Tidak Ditemukan",
                    text: data?.message || "Data tidak ditemukan.",
                });

            } else {
                await Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: data?.message || "Terjadi kesalahan sistem.",
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
