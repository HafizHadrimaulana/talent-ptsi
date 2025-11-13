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

    // --- Event handler tombol Bulk Approve ---
    bulkApproveBtn.addEventListener("click", async () => {
        const checkboxes = document.querySelectorAll(
            "input[name='selected[]']:checked"
        );
        const selected = Array.from(checkboxes).map((c) => c.value);

        if (selected.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "Tidak ada data terpilih",
                text: "Pilih minimal satu data untuk disetujui.",
            });
            return;
        }

        const result = await Swal.fire({
            title: "Setujui Data Terpilih?",
            text: `Sebanyak ${selected.length} data akan di-approve.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Setujui",
            cancelButtonText: "Batal",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: "Memproses...",
            text: "Sedang melakukan approve data terpilih.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        try {
            const formData = new FormData();
            selected.forEach((id) => formData.append("selected[]", id));

            console.log("FormData entries:", Array.from(formData.entries()));

            const res = await postFormData("/training/bulk-approve", formData);
            console.log("res in js", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal melakukan approve.",
                });
            }
        } catch (err) {
            console.error("Error saat bulk approve:", err);
            Swal.close();
            Swal.fire({
                icon: "error",
                title: "Kesalahan Sistem",
                text: "Terjadi kesalahan saat melakukan approve.",
            });
        }
    });
}