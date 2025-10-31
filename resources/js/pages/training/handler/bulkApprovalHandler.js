import { postFormData } from "@/utils/fetch";

export function initBulkApprovalHandler() {
    const bulkApproveBtn = document.querySelector("#btn-bulk-approve");
    const selectAllCheckbox = document.querySelector("#select-all");

    if (!bulkApproveBtn) return;

    selectAllCheckbox?.addEventListener("change", (e) => {
        const checked = e.target.checked;
        document.querySelectorAll("input[name='selected[]']").forEach((cb) => {
            cb.checked = checked;
        });
    });

    // Event handler untuk tombol approve
    bulkApproveBtn.addEventListener("click", async () => {
        const checkboxes = document.querySelectorAll(
            "input[name='selected[]']:checked"
        );
        const selected = Array.from(checkboxes).map((c) => c.value);

        if (selected.length === 0) {
            alert("Pilih minimal satu data untuk disetujui.");
            return;
        }

        if (!confirm("Yakin ingin menyetujui data yang dipilih?")) {
            return;
        }

        try {
            const formData = new FormData();
            selected.forEach((id) => formData.append("selected[]", id));

            console.log("FormData entries:", Array.from(formData.entries()));

            const res = await postFormData("/training/bulk-approve", formData);
            console.log("res in js", res);

            if (res.status === "success") {
                alert(res.message);
                location.reload();
            } else {
                alert(res.message || "Gagal melakukan approve.");
            }
        } catch (err) {
            console.error("Error saat bulk approve:", err);
            alert("Terjadi kesalahan saat melakukan approve.");
        }
    });
}
