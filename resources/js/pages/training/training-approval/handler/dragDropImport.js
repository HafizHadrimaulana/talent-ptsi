import { initImportHandler } from "./importHandler";

/**
 * @param {string} modalSelector
 * @param {Function} reloadCallback
 */
export function initDragDropUpload(modal) {
    if (!modal) return;
    const area = document.getElementById("drag-drop-area");
    const input = document.getElementById("drag-drop-input");
    const fileInfo = document.getElementById("selected-file-info");
    const wrapper = document.getElementById("dragdrop-wrapper");
    const uploadForm = document.getElementById("import-form");

    if (!uploadForm || uploadForm.dataset.initialized === "true") return;
    uploadForm.dataset.initialized = "true";

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
            alert("tidak ada file dipilih");
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

        const closeMyModal = () => {
            if (modal) {
                $(modal).fadeOut(150, function () {
                    $(this).addClass("hidden").hide();
                    modal.classList.add("hidden");
                });
            }
        };

        if (!selectedFile) {
            closeMyModal(); // Tutup modal dulu

            return Swal.fire({
                icon: "warning",
                title: "Peringatan",
                text: "Pilih file terlebih dahulu!",
                confirmButtonText: "Oke",
            });
        }

        closeMyModal();

        Swal.fire({
            title: "Mengunggah Data...",
            text: "Harap tunggu, sedang memproses file.",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await initImportHandler(selectedFile);

            console.log("res data import", res);

            if (res.status === "error") {
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
                    reloadCallback();
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
