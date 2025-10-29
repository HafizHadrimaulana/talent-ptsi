import { postFormData } from "@/utils/fetch";

export async function initApprovalHandler(loadTrainings) {
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
            loadTrainings?.();
        } else {
            alert(res.message || "Gagal melakukan approve.");
        }
    } catch (err) {
        console.error("Error saat bulk approve:", err);
        alert("Terjadi kesalahan saat melakukan approve.");
    }
}
