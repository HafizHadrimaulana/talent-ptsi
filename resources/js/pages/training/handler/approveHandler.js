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

        if (confirm("Apakah Anda yakin ingin approve data ini?")) {
            try {
                const res = await postJSON(
                    `/training/monitoring/${id}/approve`
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
