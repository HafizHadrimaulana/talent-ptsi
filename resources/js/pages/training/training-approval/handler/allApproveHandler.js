import { postJSON } from "@/utils/fetch";

export function initAllApprovalHandler() {
    const approveAllButton = document.querySelector("#btn-all-approve");

    if (approveAllButton) {
        approveAllButton.addEventListener("click", async () => {
            // Tampilkan konfirmasi menggunakan Swal
            const result = await Swal.fire({
                title: "Approve Semua Data?",
                text: "Semua data yang memenuhi kriteria akan di-approve. Lanjutkan?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Approve Semua",
                cancelButtonText: "Batal",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
            });

            if (!result.isConfirmed) return;

            // Tampilkan loading state
            Swal.fire({
                title: "Memproses...",
                text: "Mohon tunggu, sedang meng-approve semua data.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            try {
                const res = await postJSON("/training/all-approve");
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

                    // reload halaman setelah sukses
                    location.reload();
                } else {
                    await Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text:
                            res.message ||
                            "Terjadi kesalahan saat approve semua data.",
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Kesalahan Sistem",
                    text: "Gagal meng-approve semua data.",
                });
            }
        });
    }
}