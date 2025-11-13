import { postJSON } from "@/utils/fetch";

/**
 * @param {Element} tableBody
 * @param {Function} reloadCallback
 */

export function initApproveHandler(tableBody) {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='approve']");
        if (!button) return;

        const id = button.dataset.id;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menyetujui data ini?",
            text: "Data akan disetujui dan tidak dapat dibatalkan.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Setujui",
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

            const res = await postJSON(`/training/monitoring/${id}/approve`);
            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Disetujui",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menyetujui data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menyetujui data.",
            });
        }
    });
}
