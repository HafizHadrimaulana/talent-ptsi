import { initImportHandler } from "./importHandler";

export function initDragDropUpload(modalSelector) {
    const area = document.getElementById("drag-drop-area");
    const input = document.getElementById("drag-drop-input");
    const fileInfo = document.getElementById("selected-file-info");
    const wrapper = document.getElementById("dragdrop-wrapper");
    const uploadForm = document.getElementById("import-form");

    const modal = document.querySelector(modalSelector);

    let selectedFile = null;

    if (!wrapper || !area || !input || !uploadForm) return;

    let suppressClickAfterDialog = false;
    area.addEventListener("click", (e) => {
        if (selectedFile) return;
        if (suppressClickAfterDialog) return;
        suppressClickAfterDialog = true;

        const onFocus = () => {
            suppressClickAfterDialog = false;
            window.removeEventListener("focus", onFocus);
        };
        window.addEventListener("focus", onFocus);

        input.click();
    });

    function resetFileSelection() {
        selectedFile = null;
        input.value = "";
        fileInfo.classList.add("hidden");
        wrapper.classList.remove("hidden");

        area.classList.remove("border-blue-500", "bg-blue-50");
        area.classList.add("border-gray-200");

        input.blur();
    }

    if (modal) {
        modal.addEventListener("modalClosed", resetFileSelection);
    }

    input.addEventListener("change", () => {
        selectedFile = input.files[0];
        if (!selectedFile) {
            console.log("tidak ada file dipilih");
        }

        wrapper.classList.add("hidden");
        fileInfo.classList.remove("hidden");

        fileInfo.innerHTML = `
            <div class="bg-green-50 border border-green-300 u-p-lg rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-green-700 font-medium">File Terpilih:</p>
                    <p class="text-green-800">${selectedFile.name}</p>
                </div>
                <div id="change-file-btn" class="u-btn u-btn--outline u-hover-lift">
                    Ganti File
                </div>
            </div>
        `;

        document
            .getElementById("change-file-btn")
            .addEventListener("click", (e) => {
                e.stopPropagation();
                resetFileSelection();
            });
    });

    area.addEventListener("dragover", (e) => {
        e.preventDefault();
        area.classList.remove("border-gray-200");
        area.classList.add("border-blue-500", "bg-blue-50");
    });

    area.addEventListener("dragleave", () => {
        area.classList.remove("border-blue-500", "bg-blue-50");
    });

    area.addEventListener("drop", (e) => {
        e.preventDefault();
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event("change"));
    });

    uploadForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!selectedFile) {
            return Swal.fire(
                "Peringatan",
                "Pilih file terlebih dahulu!",
                "warning"
            );
        }

        if (modal) modal.classList.add("hidden");

        Swal.fire({
            title: "Mengunggah Data...",
            text: "Harap tunggu, sedang memproses file.",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await initImportHandler(selectedFile, (percent) => {
                const b =
                    Swal.getHtmlContainer().querySelector("#swal-progress");
                if (b) b.textContent = `${percent}%`;
            });

            console.log("res data import", res.data);

            if (res.data.status === "error") {
                // Kita lempar agar ditangkap oleh blok catch(err) di bawah
                throw new Error(res.message || "Terjadi kesalahan sistem");
            }

            Swal.fire({
                icon: "success",
                title: "Berhasil!",
                text: `Import selesai! ${
                    res.processed_rows || 0
                } data berhasil diproses.`,
                timer: 2000,
                showConfirmButton: false,
            }).then((result) => {
                if (
                    result.isConfirmed ||
                    result.dismiss === Swal.DismissReason.timer
                ) {
                    // window.location.reload();
                }
            });
        } catch (err) {
            Swal.close();

            let errorTitle = "Terjadi Kesalahan";
            let errorMessage = "Gagal memproses permintaan.";

            // Jika error mengandung kata tertentu atau flag dari backend
            if (err.message.includes("sistem") || err.message.includes("500")) {
                errorTitle = "System Error";
                errorMessage =
                    "Mohon maaf, sistem sedang mengalami kendala teknis.";
            } else {
                // Jika error adalah masalah input (seperti kolom Excel salah)
                errorTitle = "Gagal Import";
                errorMessage = err.message;
            }

            Swal.fire({
                icon: "error",
                title: errorTitle,
                text: errorMessage,
                confirmButtonText: "Oke",
            });
        }
    });
}
