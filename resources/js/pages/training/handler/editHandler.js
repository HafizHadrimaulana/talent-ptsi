import { getJSON, postJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody - elemen tbody dari tabel
 * @param {Function} reloadCallback - fungsi untuk reload data setelah update
 */

export function initEditHandler(tableBody, reloadCallback) {
    const modal = document.querySelector("#edit-modal");
    const form = document.querySelector("#edit-form");
    const cancelBtn = document.querySelector("#btn-cancel");
    const saveBtn = document.querySelector("#btn-save");
    
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
                alert("Gagal mengambil data untuk edit");
            }

        } catch (error) {
            console.error(error);
            alert("Gagal memuat data");
        }

        cancelBtn.addEventListener("click", () => {
            modal.classList.add("hidden");
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            console.log(id)

            try {
                const res = await postJSON(`/training/edit/${id}`, formData);
                console.log('res edit', res)
                if (res.status === "success") {
                    alert(res.message);
                    modal.classList.add("hidden");
                    window.location.reload();
                    reloadCallback();
                } else {
                    alert("Gagal memperbarui data");
                }
            } catch (error) {
                alert("Gagal memperbarui data");
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
