import { postFormData } from "@/utils/fetch";

export const initUpdateRealisasiDateHandler = (tableBody) => {
    tableBody.addEventListener("change", async (e) => {
        if (e.target.classList.contains("realisasi-date-input")) {
            const id = e.target.dataset.id;
            const newValue = e.target.value;

            if (!newValue) {
                Swal.fire({
                    icon: "warning",
                    title: "Tanggal Kosong!",
                    text: "Tanggal realisasi tidak boleh kosong.",
                    confirmButtonText: "OK",
                });
                return;
            }

            const confirmResult = await Swal.fire({
                title: "Perbarui Tanggal Realisasi?",
                text: "Apakah Anda yakin ingin menyimpan tanggal ini?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Simpan",
                cancelButtonText: "Batal",
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const formData = new FormData();
                formData.append("realisasi_date", newValue);
                console.log("target value", newValue);

                Swal.fire({
                    title: "Menyimpan Perubahan...",
                    text: "Harap tunggu, sedang memperbarui tanggal realisasi.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                const res = await postFormData(
                    `dashboard/${id}/update-realisasi-date`,
                    formData
                );

                Swal.close();
                console.log("response", res);

                if (res.status === "success") {
                    await Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text:
                            res.message ||
                            "Tanggal realisasi berhasil diperbarui.",
                        timer: 2000,
                        showConfirmButton: false,
                    });

                    location.reload();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text:
                            res.message ||
                            "Gagal memperbarui tanggal realisasi.",
                        confirmButtonText: "OK",
                    });
                }
            } catch (error) {
                Swal.close();
                console.error("Error update tanggal realisasi:", error);

                Swal.fire({
                    icon: "error",
                    title: "Kesalahan Server!",
                    text: "Terjadi kesalahan saat memperbarui tanggal realisasi.",
                    confirmButtonText: "OK",
                });
            }
        }
    });
};
