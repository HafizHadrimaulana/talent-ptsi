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

        if (confirm("Apakah Anda yakin ingin reject data ini?")) {
            try {
                const res = await postJSON(
                    `/training/monitoring/${id}/reject`
                );
                console.log("res", res);

                if (res.status === "success") {
                    alert(res.message);
                    location.reload();
                } else {
                    alert("Gagal mengambil data untuk edit");
                }
            } catch (error) {
                console.error(error);
                alert("Gagal memuat data");
            }
        }
    });
}
