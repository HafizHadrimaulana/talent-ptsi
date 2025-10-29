import { postJSON } from "@/utils/fetch";

document.addEventListener("DOMContentLoaded", () => {
    const approveAllButton = document.querySelector("#btn-all-approve");

    approveAllButton.addEventListener("click", async () => {
        if (!confirm("Yakin ingin meng-approve semua data?")) return;

        try {
            const res = await postJSON("/training/all-approve");
            console.log("res in js", res);

            if (res.status === "success") {
                alert(res.message);
                location.reload(); // refresh tabel agar data terupdate
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
});
