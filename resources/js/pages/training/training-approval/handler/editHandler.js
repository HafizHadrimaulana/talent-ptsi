import { getJSON, postJSON } from "@/utils/fetch";

/**
 * Inisialisasi event handler untuk tombol Edit
 * @param {Element} tableBody - elemen tbody dari tabel
 * @param {Function} reloadCallback - fungsi untuk reload data setelah update
 */

export function initEditHandler(tableBody, reloadCallback) {
    const modal = document.querySelector("#edit-lna-modal");
    const form = document.querySelector("#edit-lna-form");
    const closeBtn = document.querySelector("#lna-edit-close-modal");

    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='edit']");
        if (!button) return;

        initBiayaHandler(form);

        const id = button.dataset.id;

        try {
            const res = await getJSON(`/training/training-request/${id}/get-lna-by-id`);

            console.log("res get edit data", res);

            if (res.status === "success") {
                fillEditForm(res.data);
                modal.classList.remove("hidden");
                modal.hidden = false;
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

        closeBtn.addEventListener("click", () => {
            modal.classList.add("hidden");
            modal.hidden = true;
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
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({
                title: "Menyimpan Data...",
                text: "Sedang memperbarui data.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const res = await postJSON(`/training/training-request/${id}/edit-data-lna`, formData);
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

                    reloadCallback();
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
        document.querySelector("#edit-judul_sertifikasi").value = data.judul_sertifikasi ?? "-";

        loadUnits(data.unit_id);

        document.querySelector("#edit-penyelenggara").value = data.penyelenggara ?? "-";
        document.querySelector("#edit-jumlah_jam").value = data.jumlah_jam ?? "-";
        document.querySelector("#edit-waktu_pelaksanaan").value = data.waktu_pelaksanaan ?? "-";

        document.querySelector("#edit-biaya_pelatihan").value =
            formatRupiah(data.biaya_pelatihan);

        document.querySelector("#edit-uhpd").value =
            formatRupiah(data.uhpd);

        document.querySelector("#edit-biaya_akomodasi").value =
            formatRupiah(data.biaya_akomodasi);

        document.querySelector("#edit-estimasi_total_biaya").value =
            formatRupiah(data.estimasi_total_biaya);
        document.querySelector("#edit-nama_proyek").value = data.nama_proyek ?? "-";
        document.querySelector("#edit-jenis_portofolio").value = data.jenis_portofolio ?? "-";
        document.querySelector("#edit-fungsi").value = data.fungsi ?? "-";
    }

}

/** ===============================
 *   HELPERS
 * =============================== */

async function loadUnits(selectedUnitId = null) {
    const select = document.getElementById("edit-unit_kerja");
    if (!select) return;

    select.innerHTML = `<option value="">-- Pilih Unit --</option>`;

    try {
        const res = await getJSON(
            "/training/training-request/get-data-units"
        );
        console.log("units", res);

        if (res.status !== "success") return;

        res.data.forEach(unit => {
            const option = document.createElement("option");
            option.value = unit.id;
            option.textContent = unit.name;

            if (String(unit.id) === String(selectedUnitId)) {
                option.selected = true;
            }

            select.appendChild(option);
        });

    } catch (error) {
        console.error("Error fetch units:", error);
    }
}

function initBiayaHandler(inputForm) {
    const biayaPelatihan = inputForm.querySelector("input[name='biaya_pelatihan']");
    const uhpd = inputForm.querySelector("input[name='uhpd']");
    const akomodasi = inputForm.querySelector("input[name='biaya_akomodasi']");
    const total = inputForm.querySelector("input[name='estimasi_total_biaya']");

    total.value = "";

    if (!biayaPelatihan || !uhpd || !akomodasi || !total) {
        console.warn("Biaya handler: element tidak ditemukan di form");
        return;
    }

    function formatInputRupiah(element) {
        element.addEventListener("input", () => {
            const val = element.value.replace(/[^\d]/g, "");
            element.value = formatRupiah(val);
            hitungTotal(); // hitung ulang ketika angka berubah
        });
    }

    // Terapkan auto format rupiah
    [uhpd, akomodasi].forEach((el) => {
        if (el) formatInputRupiah(el);
    });

    biayaPelatihan.addEventListener("input", () => {
        const val = biayaPelatihan.value.replace(/[^\d]/g, "");
        biayaPelatihan.value = formatRupiah(val);
        hitungTotal();
    });

    // ============ HITUNG TOTAL =============
    function hitungTotal() {
        const biaya = parseInt(biayaPelatihan.value.replace(/\D/g, "")) || 0;
        const u = parseInt(uhpd.value.replace(/\D/g, "")) || 0;
        const a = parseInt(akomodasi.value.replace(/\D/g, "")) || 0;

        if (u === 0 && a === 0 && biaya === 0) {
            total.value = "";
            return;
        }

        const jumlah = biaya + u + a;
        total.value = formatRupiah(jumlah);
    }

    // ============ FORMAT RUPIAH =============
    function formatRupiah(value) {
        if (!value) return "";
        const number = parseInt(value, 10);

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(number);
    }

    return { hitungTotal };
}

const formatRupiah = (value) => {
    if (value == null || value === "" || value === "-" || value === "null") {
        return "Rp 0";
    }
    
    const number = parseFloat(value);
    
    if (isNaN(number)) {
        return "Rp 0";
    }
    
    return new Intl.NumberFormat("id-ID", {
        style: "currency", 
        currency: "IDR", 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0
    }).format(number);
};

const formatDate = (dateString, options = { day: '2-digit', month: 'long', year: 'numeric' }) => {
    if (!dateString) return "-";
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return "-";
        
        return date.toLocaleDateString('id-ID', options);
    } catch (error) {
        console.error('Error formatting date:', error);
        return "-";
    }
};
