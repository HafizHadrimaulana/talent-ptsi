import { getJSON, postJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody - elemen tbody dari tabel
 * @param {Function} reloadCallback - fungsi untuk reload data setelah update
 */

export function initEditHandler(tableBody) {
    const modal = document.querySelector("#edit-modal");
    const form = document.querySelector("#edit-form");
    const cancelBtn = document.querySelector("#btn-cancel");
    
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='edit']");
        if (!button) return;

        const id = button.dataset.id;

        try {
            const res = await getJSON(`/training/edit/${id}/get-data`);

            if (res.status === "success") {
                fillEditForm(res.data);
                modal.classList.remove("hidden");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal mengambil data untuk edit.",
                });
            }

        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Terjadi Kesalahan",
                text: "Tidak dapat memuat data dari server.",
            });
        }

        cancelBtn.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            modal.classList.add("hidden");

            const confirm = await Swal.fire({
                title: "Simpan Perubahan?",
                text: "Pastikan semua data sudah benar.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Simpan",
                cancelButtonText: "Batal",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({
                title: "Menyimpan Data...",
                text: "Sedang memperbarui data.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const res = await postJSON(`/training/edit/${id}`, formData);
                Swal.close();

                console.log('res edit', res)
                if (res.status === "success") {
                    

                    await Swal.fire({
                        icon: "success",
                        title: "Berhasil",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });

                    window.location.reload();
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
                    text: "Terjadi kesalahan saat memperbarui data.",
                });
            }
        });
    });

    function fillEditForm(data) {
        document.querySelector("#edit-id").value = data.id;
        document.querySelector("#edit-nik").value = data.nik;
        document.querySelector("#edit-nama_peserta").value = data.nama_peserta;
        document.querySelector("#edit-status_pegawai").value = data.status_pegawai;
        document.querySelector("#edit-jabatan_saat_ini").value = data.jabatan_saat_ini;
        document.querySelector("#edit-unit_kerja").value = data.unit_kerja;
        document.querySelector("#edit-judul_sertifikasi").value = data.judul_sertifikasi;
        document.querySelector("#edit-penyelenggara").value = data.penyelenggara;
        document.querySelector("#edit-jumlah_jam").value = data.jumlah_jam;
        document.querySelector("#edit-waktu_pelaksanaan").value = data.waktu_pelaksanaan;
        document.querySelector("#edit-nama_proyek").value = data.nama_proyek;
        document.querySelector("#edit-biaya_pelatihan").value = data.biaya_pelatihan;
        document.querySelector("#edit-uhpd").value = data.uhpd;
        document.querySelector("#edit-biaya_akomodasi").value = data.biaya_akomodasi;
        document.querySelector("#edit-estimasi_total_biaya").value = data.estimasi_total_biaya;
        document.querySelector("#edit-jenis_portofolio").value = data.jenis_portofolio;
        document.querySelector("#edit-start_date").value = data.start_date;
        document.querySelector("#edit-end_date").value = data.end_date;
    }

}
