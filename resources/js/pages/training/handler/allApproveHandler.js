import { postJSON } from "@/utils/fetch";

export function initAllApprovalHandler() {
    const approveAllButton = document.querySelector("#btn-all-approve");

    approveAllButton.addEventListener("click", async () => {
        if (!confirm("Yakin ingin meng-approve semua data?")) return;

        try {
            const res = await postJSON("/training/all-approve");
            console.log("res in js", res);

            if (res.status === "success") {
                alert(res.message);
                location.reload();
            } else {
                alert(
                    res.message || "Terjadi kesalahan saat approve semua data."
                );
            }
        } catch (error) {
            console.error(error);
            alert("Gagal meng-approve semua data.");
        }
    });
}
