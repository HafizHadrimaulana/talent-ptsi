import { postFormData } from "@/utils/fetch";

export function initUpdateJenisPelatihanHandler(tableBody) {
    tableBody.addEventListener("change", async (e) => {
        if (e.target.classList.contains("jenis-pelatihan-select")) {
            const id = e.target.dataset.id;

            const newValue = e.target.value;

            const confirmResult = await Swal.fire({
                title: "Konfirmasi Perubahan",
                text: `Yakin ingin mengubah jenis pelatihan menjadi "${newValue}"?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Ubah",
                cancelButtonText: "Batal",
                reverseButtons: true,
            });

            if (!confirmResult.isConfirmed) return;

            const formData = new FormData();
            formData.append("jenis_pelatihan", e.target.value);
            console.log("target value", e.target.value);

            try {
                Swal.fire({
                    title: "Memproses...",
                    text: "Mohon tunggu sebentar.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                const res = await postFormData(
                    `dashboard/${id}/update-jenis-pelatihan`,
                    formData
                );

                Swal.close();
                console.log("response", res);

                if (res.status === "success") {
                    await Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: res.message,
                        timer: 1000,
                        showConfirmButton: false,
                    });

                    location.reload();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: res.message || "Gagal memperbarui data.",
                    });
                }
            } catch (error) {
                Swal.close();
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Kesalahan Server",
                    text: "Terjadi kesalahan saat memproses perubahan.",
                });
            }
        }
    });
}
