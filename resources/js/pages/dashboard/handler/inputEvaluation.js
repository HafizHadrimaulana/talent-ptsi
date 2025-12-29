import { postFormData, getJSON } from "@/utils/fetch";

/**
 * Inisialisasi tombol input evaluasi di tabel
 * @param {HTMLElement} tableBody
 * @param {Function} reloadCallback
 */
export function initInputEvaluationHandler(tableBody, reloadCallback) {
    const modal = document.querySelector("#modal-input-evaluation");
    const form = document.querySelector("#form-input-evaluation");
    const cancelBtn = document.querySelector("#close-input-evaluation");

    if (!modal || !form) {
        console.error("Modal atau form input evaluasi tidak ditemukan!");
        return;
    }

    function fillEvaluationForm(data) {
        document.querySelector("#nama_pelatihan").value = data.nama_pelatihan ?? "-";
        document.querySelector("#nama_peserta").value = data.nama_peserta ?? "-";
        document.querySelector("#training_id").value = data.training_id;
    }

    tableBody.addEventListener("click", async (e) => {
        const btn = e.target.closest("button[data-action='input-evaluation']");
        if (!btn) return;

        const id = btn.dataset.id;
        console.log("id", id);

        try {
            const res = await getJSON(
                `/training/dashboard/${id}/get-detail-evaluation`
            );
            console.log("as");
            if (res.status === "success") {
                console.log("res", res);
                fillEvaluationForm(res.data);
                modal.classList.remove("hidden");
            } else {
                modal.classList.remove("hidden");
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal mengambil data evaluasi.",
                });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Terjadi Kesalahan",
                text: "Tidak dapat memuat data dari server.",
            });
        }

        modal.classList.remove("hidden");

        document.querySelector("#close-input-evaluation").onclick = () => {
            modal.classList.add("hidden");
        };
    });

    cancelBtn.addEventListener("click", () => {
        modal.classList.add("hidden");
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        console.log("training_id value:", document.querySelector("#training_id").value);

        const formData = new FormData(form);
        console.log("form data", formData);

        modal.classList.add("hidden");

        try {
            const res = await postFormData(
                "/training/dashboard/input-evaluation",
                formData
            );

            Swal.fire({
                title: "Menyimpan Data...",
                text: "Sedang menyimpan evaluasi.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil",
                    text: res.message || "Evaluasi berhasil disimpan!",
                    timer: 2000,
                    showConfirmButton: false,
                });

                form.reset();

                if (reloadCallback) reloadCallback();
                else window.location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menyimpan evaluasi.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error("Error submit evaluasi:", error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menyimpan evaluasi.",
            });
        }
    });
}
