import { initImportHandler } from "./importHandler";

export function initDragDropUpload() {
    const importButton = document.querySelector(".btn-import");
    const modal = document.querySelector("#import-modal");
    const closeModal = document.querySelector("#close-modal");

    const area = document.getElementById("drag-drop-area");
    const input = document.getElementById("drag-drop-input");
    const fileInfo = document.getElementById("selected-file-info");
    const wrapper = document.getElementById("dragdrop-wrapper");
    const uploadForm = document.getElementById("import-form");

    let selectedFile = null;

    if (!wrapper || !area || !input || !uploadForm) return;

    if (importButton && modal && closeModal) {
        importButton.addEventListener("click", () => {
            modal.classList.remove("hidden");
        });
        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    area.addEventListener("click", () => input.click());

    function resetFileSelection() {
        selectedFile = null;
        input.value = "";
        fileInfo.classList.add("hidden");
        wrapper.classList.remove("hidden");

        area.classList.remove("border-blue-500", "bg-blue-50");
        area.classList.add("border-gray-200");
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
                <div id="change-file-btn" class="u-btn u-btn--brand u-hover-lift">
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

        modal.classList.add("hidden");

        Swal.fire({
            title: "Mengunggah Data...",
            text: "Harap tunggu, sedang memproses file.",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        console.log("selected file in dragdop", selectedFile);

        try {
            const res = await initImportHandler(selectedFile);

            console.log("res1", res);

            Swal.fire({
                icon: "success",
                title: "Berhasil!",
                text: res.message ?? "Upload selesai!",
                timer: 2000,
                timerProgressBar: true,
            }).then(() => {
                console.log("reload");
                // window.location.reload();
            });
        } catch (err) {
            console.error(err);
            Swal.fire("Error", "Gagal mengupload file!", "error");
        }
    });
}
