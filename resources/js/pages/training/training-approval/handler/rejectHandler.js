import { postJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody - elemen tbody dari tabel
 * @param {Function} reloadCallback - fungsi untuk reload data setelah update
 */

export function initRejectHandler(tableBody) {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='reject']");
        if (!button) return;

        const id = button.dataset.id;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menolak data ini?",
            text: "Tindakan ini tidak dapat dibatalkan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Tolak",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Memproses...",
                text: "Mohon tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postJSON(
                `/training/training-request/${id}/reject-training-request`
            );
            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Ditolak",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menolak data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menolak data.",
            });
        }
    });
}

export function rejectTrainingPengajuanHandler(tableBody) {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='reject_training_pengajuan']");
        if (!button) return;

        const id = button.dataset.id;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menolak data pengajuan ini?",
            text: "Tindakan ini tidak dapat dibatalkan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Tolak",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Memproses...",
                text: "Mohon tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postJSON(
                `/training/training-request/${id}/reject-training-pengajuan`
            );
            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Ditolak",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menolak data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menolak data.",
            });
        }
    });
}
