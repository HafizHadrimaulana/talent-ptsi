import { postFormData } from "@/utils/fetch";

export function initInputHandler() {
    const modal = document.querySelector("#input-training-modal");
    const inputButton = document.querySelector(".btn-add");
    const closeModal = document.querySelector("#close-input-modal");
    const inputForm = document.querySelector("#add-form");

    const judulSelect = inputForm.querySelector("select[name='judul_sertifikasi']");
    const penyelenggara = inputForm.querySelector("input[name='penyelenggara']");
    const jumlahJam = inputForm.querySelector("input[name='jumlah_jam']");
    const jenisPelatihan = inputForm.querySelector("input[name='jenis_pelatihan']");
    const jenisPortofolio = inputForm.querySelector("input[name='jenis_portofolio']");
    const waktuPelaksanaan = inputForm.querySelector("input[name='waktu_pelaksanaan']");
    const namaProyek = inputForm.querySelector("input[name='nama_proyek']");

    const biayaPelatihan = inputForm.querySelector("input[name='biaya_pelatihan']");
    const uhpd = inputForm.querySelector("input[name='uhpd']");
    const biayaAkomodasi = inputForm.querySelector("input[name='biaya_akomodasi']");
    const estimasiTotalBiaya = inputForm.querySelector("input[name='estimasi_total_biaya']");

    const alasan = inputForm.querySelector("input[name='alasan']");
    const startDate = inputForm.querySelector("input[name='start_date']");
    const endDate = inputForm.querySelector("input[name='end_date']");

    const dummySertifikasi = [
        {
            id: 1,
            judul: "Sampling, Sample Preparation and Testing of Coal",
            penyelenggara: "LSP PTSI",
            jumlah_jam: 40,
            jenis_pelatihan: "EDP - Sertifikat Profesi",
            jenis_portofolio: "Oil, Gas & Renewable Energy",
            waktu_pelaksanaan: "Mar/ Juli/ Nov",
            nama_proyek: "QnQ CNM",
            biaya_pelatihan: 1000000,
            uhpd: 1000000,
            biaya_akomodasi: 1000000,
            estimasi_total_biaya: 1000000,
            alasan: "Meningkatkan kompetensi",
            start_date: "2023-01-01",
            end_date: "2023-01-01",
        },
        {
            id: 2,
            judul: "Verifikasi, Validasi Metode dan Ketidakpastian Pengujian",
            penyelenggara: "BNSP",
            jumlah_jam: 32,
            jenis_pelatihan: "EDP - Sertifikat Industri",
            jenis_portofolio: "Oil, Gas & Renewable Energy",
            waktu_pelaksanaan: "Mar/ Juli/ Nov",
            nama_proyek: "QnQ CNM",
            biaya_pelatihan: 1000000,
            uhpd: 1000000,
            biaya_akomodasi: 1000000,
            estimasi_total_biaya: 1000000,
            alasan: "Meningkatkan kompetensi",
            start_date: "2023-01-01",
            end_date: "2023-01-01",
        },
        {
            id: 3,
            judul: "Sistem Manajemen Keselamatan dan Kesehatan Kerja (SMK3)",
            penyelenggara: "BSN",
            jumlah_jam: 24,
            jenis_pelatihan: "EDP - Sertifikat Profesi",
            jenis_portofolio: "Oil, Gas & Renewable Energy",
            waktu_pelaksanaan: "Mar/ Juli/ Nov",
            nama_proyek: "QnQ CNM",
            biaya_pelatihan: 1000000,
            uhpd: 1000000,
            biaya_akomodasi: 1000000,
            estimasi_total_biaya: 1000000,
            alasan: "Meningkatkan kompetensi",
            start_date: "2023-01-01",
            end_date: "2023-01-01",
        },
    ];

    /// START ADD PESERTA
    const pesertaData = [
        "Islahihya Muhammad",
        "Ardiansyah Putra",
        "Siti Rahmawati",
        "Dewi Kartika",
        "Rizky Maulana",
        "Andi Nugraha",
        "Fadillah Saputra",
        "Rizky Maulana 2",
        "Andi Nugraha 2",
        "Fadillah Saputra 2",
        "Rizky Maulana 3",
        "Andi Nugraha 3",
        "Fadillah Saputra 3",
    ];

    const searchInput = document.getElementById("peserta-search");
    const dropdown = document.getElementById("peserta-dropdown");
    const selectedContainer = document.getElementById("peserta-selected");
    const hiddenInput = document.getElementById("peserta-list-hidden");

    let selectedPeserta = [];

    searchInput.addEventListener("keyup", function () {
        const keyword = this.value.toLowerCase();

        if (keyword.length === 0) {
            dropdown.style.display = "none";
            return;
        }

        const filtered = pesertaData.filter(
            (item) =>
                item.toLowerCase().includes(keyword) &&
                !selectedPeserta.includes(item)
        );

        dropdown.innerHTML = "";

        if (filtered.length === 0) {
            dropdown.style.display = "none";
            return;
        }

        filtered.forEach((item) => {
            const div = document.createElement("div");
            div.textContent = item;
            div.addEventListener("click", () => selectPeserta(item));
            dropdown.appendChild(div);
        });

        dropdown.style.display = "block";
    });

    // ==== FUNGSI MEMILIH PESERTA ====
    function selectPeserta(name) {
        if (!selectedPeserta.includes(name)) {
            selectedPeserta.push(name);
            updateSelectedChips();
        }

        searchInput.value = "";
        dropdown.style.display = "none";
    }

    // ==== UPDATE CHIP & HIDDEN INPUT ====
    function updateSelectedChips() {
        selectedContainer.innerHTML = "";

        selectedPeserta.forEach((name) => {
            const chip = document.createElement("div");
            chip.classList.add("chip");
            chip.innerHTML = `${name} <button onclick="removePeserta('${name}')">x</button>`;
            selectedContainer.appendChild(chip);
        });

        hiddenInput.value = JSON.stringify(selectedPeserta);
    }

    function removePeserta(name) {
        selectedPeserta = selectedPeserta.filter((p) => p !== name);
        updateSelectedChips();
    }

    /// END ADD PESERTA

    function loadDropdownOptions() {
        judulSelect.innerHTML = `
            <option value="">-- Pilih Judul Sertifikasi --</option>
        `;

        dummySertifikasi.forEach((item) => {
            judulSelect.innerHTML += `
                <option value="${item.id}">
                    ${item.judul}
                </option>
            `;
        });
    }

    loadDropdownOptions();

    judulSelect.addEventListener("change", () => {
        const id = parseInt(judulSelect.value);
        const data = dummySertifikasi.find((item) => item.id === id);

        if (!data) {
            penyelenggara.value = "";
            jumlahJam.value = "";
            alasan.value = "";
            jenisPelatihan.value = "";
            jenisPortofolio.value = "";
            waktuPelaksanaan.value = "";
            namaProyek.value = "";
            biayaPelatihan.value = "";
            uhpd.value = "";
            biayaAkomodasi.value = "";
            estimasiTotalBiaya.value = "";
            startDate.value = "";
            endDate.value = "";
            return;
        }

        penyelenggara.value = data.penyelenggara;
        jumlahJam.value = data.jumlah_jam;
        alasan.value = data.alasan;
        jenisPelatihan.value = data.jenis_pelatihan;
        jenisPortofolio.value = data.jenis_portofolio;
        waktuPelaksanaan.value = data.waktu_pelaksanaan;
        namaProyek.value = data.nama_proyek;
        biayaPelatihan.value = data.biaya_pelatihan;
        uhpd.value = data.uhpd;
        biayaAkomodasi.value = data.biaya_akomodasi;
        estimasiTotalBiaya.value = data.estimasi_total_biaya;
        startDate.value = data.start_date;
        endDate.value = data.end_date;
    });

    if (inputButton && modal && closeModal) {
        inputButton.addEventListener("click", () => {
            modal.classList.remove("hidden");
            inputButton.classList.add("hidden");
            closeModal.classList.remove("hidden");
        });

        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
            closeModal.classList.add("hidden");
            inputButton.classList.remove("hidden");
        });
    }

    inputForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(inputForm);
        const url = "/training/input";

        try {
            modal.classList.add("hidden");
            Swal.fire({
                title: "Menyimpan Data...",
                text: "Harap tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postFormData(url, formData);

            console.log("res", res);
            Swal.close();

            if (res.status === "success") {
                modal.classList.add("hidden");
                await Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: res.message || "Data berhasil disimpan.",
                    timer: 2000,
                    showConfirmButton: false,
                });

                inputForm.reset();
                window.location.reload();
                return;
            }
            await Swal.fire({
                icon: "error",
                title: "Gagal Menyimpan",
                text: res.message || "Terjadi kesalahan saat menyimpan data.",
                confirmButtonText: "Tutup",
            });
        } catch (error) {
            Swal.close();
            console.error("Error saat input:", error);

            await Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan pada server. Silakan coba lagi.",
                confirmButtonText: "OK",
            });
        }
    });
}
