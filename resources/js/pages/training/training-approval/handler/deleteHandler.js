import { deleteJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol hapus
 */
export function initDeleteHandler(tableBody, reloadCallback) {
    // 1. Bersihkan listener lama untuk mencegah duplikasi (pola prevent duplicate)
    // Jika menggunakan Vanilla JS, kita harus memastikan listener tidak menumpuk.
    // Cara termudah adalah memastikan listener hanya dipasang SEKALI atau menggunakan jQuery .off()
    
    const handleDelete = async (e) => {
        const button = e.target.closest("button[data-action='delete']");
        if (!button) return;

        // Mengambil data dari atribut tombol
        const { id, table } = button.dataset;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin membatalkan data ini?",
            text: "Data yang sudah dibatalkan tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal",
            reverseButtons: true,
            confirmButtonColor: "#d33",
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Mohon Tunggu",
                text: "Sedang memproses penghapusan...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            let deleteUrl = "";
            if (table === "data-lna-table") {
                deleteUrl = `/training/training-request/${id}/delete-lna`;
            } else if (table === "training-request-table") {
                deleteUrl = `/training/training-request/${id}/delete-training-request`;
            }

            if (!deleteUrl) throw new Error("Route delete tidak ditemukan untuk tabel: " + table);

            const res = await deleteJSON(deleteUrl);
            Swal.close();

            const { ok, statusCode, data } = res;

            if (ok && data?.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false,
                });
                reloadCallback(); // Memanggil draw() pada DataTables
            } else {
                throw { statusCode, data }; // Lempar ke catch untuk handle error terpusat
            }

        } catch (error) {
            Swal.close();
            const message = error.data?.message || "Terjadi kesalahan sistem.";
            Swal.fire({
                icon: "error",
                title: error.statusCode === 404 ? "Tidak Ditemukan" : "Gagal",
                text: message,
            });
        }
    };

    // 2. Gunakan pola jQuery jika tableBody adalah jQuery object untuk kemudahan clean-up
    if (tableBody instanceof jQuery || typeof tableBody.off === "function") {
        $(tableBody).off("click", "button[data-action='delete']").on("click", "button[data-action='delete']", handleDelete);
    } else {
        // Jika Vanilla JS, pastikan init ini tidak dipanggil berulang-ulang di drawCallback
        // atau gunakan flag untuk menandai elemen sudah memiliki listener
        if (!tableBody.dataset.hasDeleteListener) {
            tableBody.addEventListener("click", handleDelete);
            tableBody.dataset.hasDeleteListener = "true";
        }
    }
}