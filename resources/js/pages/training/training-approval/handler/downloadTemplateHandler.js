export function initDownloadTemplateHandler() {
    const downloadButton = document.querySelector(".btn-download-template");

    if (downloadButton) {
        downloadButton.addEventListener("click", async (e) => {
            e.preventDefault();

            Swal.fire({
                title: "Download Template?",
                text: "Apakah Anda yakin ingin mengunduh template Excel pelatihan?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, download sekarang",
                cancelButtonText: "Batal",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
            }).then((result) => {
                if (result.isConfirmed) {
                    // tampilkan loading dulu biar lebih interaktif
                    Swal.fire({
                        title: "Sedang mengunduh...",
                        text: "Mohon tunggu sebentar.",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    try {
                        // Mulai download
                        window.location.href = "/training/download-template";

                        // setelah 2 detik tutup swal
                        setTimeout(() => {
                            Swal.close();
                            Swal.fire({
                                icon: "success",
                                title: "Berhasil!",
                                text: "Template Excel berhasil diunduh.",
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        }, 2000);
                    } catch (error) {
                        console.error("Error download:", error);
                        Swal.fire({
                            icon: "error",
                            title: "Gagal!",
                            text: "Terjadi kesalahan saat mengunduh template.",
                        });
                    }
                }
            });
        });
    }
}
