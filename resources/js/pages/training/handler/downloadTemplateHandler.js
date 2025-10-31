export function initDownloadTemplateHandler() {
    const downloadButton = document.querySelector(".btn-download-template");

    if (downloadButton) {
        downloadButton.addEventListener("click", async (e) => {
            e.preventDefault();

            if (confirm("Apakah Anda yakin ingin download template?")) {
                try {
                    window.location.href = "/training/download-template";
                } catch (error) {
                    console.error("Error hapus:", error);
                    alert("Terjadi kesalahan saat menghapus data");
                }
            }
        });
    }
}
