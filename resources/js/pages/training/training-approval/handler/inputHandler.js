import { postFormData, getJSON } from "@/utils/fetch";

export function initInputHandler(modalSelector) {
    const modal = document.querySelector(modalSelector);
    const inputForm = document.querySelector("#add-form");

    const biayaHelper = initBiayaHandler(inputForm);
    const judulSelect = inputForm.querySelector( "select[name='judul_sertifikasi']");
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
    // const alasan = inputForm.querySelector("input[name='alasan']");
    const startDate = inputForm.querySelector("input[name='start_date']");
    const endDate = inputForm.querySelector("input[name='end_date']");

    const searchInput = document.getElementById("peserta-search");
    const dropdown = document.getElementById("peserta-dropdown");
    const selectedContainer = document.getElementById("peserta-selected");
    const hiddenInput = document.getElementById("peserta-list-hidden");

    initBiayaHandler(inputForm);

    let sertifikasiList = [];
    let pesertaData = [];


    let selectedPeserta = [];

    function getName(item) {
        if (!item) return "";

        const name =
            item.name ||
            item.person_name ||
            item.full_name ||
            "";

        const empId = item.employee_id || "";

        // Jika tidak ada employee_id, cukup tampilkan nama saja
        return empId ? `${name} - ${empId}` : name;
    }

    /** Helper: ambil id dari item jika ada */
    function getId(item) {
        if (!item && item !== 0) return null;
        if (typeof item === "string") return null;
        return item.id ?? item.person_id ?? null;
    }

    async function loadPeserta() {
        try {
            const unitId = window.userUnitId;
            const res = await getJSON(
                `/training/training-request/${unitId}/get-employee-by-unit`
            );

            console.log('peserta', res);

            if (res.status === "success") {
                pesertaData = res.data;
            }
        } catch (error) {
            console.error("Gagal load peserta:", error);
        }
    }

    loadPeserta();

    searchInput.addEventListener("keyup", function () {
        const keyword = this.value.toLowerCase();

        if (keyword.length === 0) {
            dropdown.style.display = "none";
            return;
        }

        const filtered = pesertaData.filter((item) => {
            const name = getName(item).toLowerCase();
            const id = getId(item);
            const alreadySelected = id
                ? selectedPeserta.some((s) => s.id === id)
                : selectedPeserta.some((s) => s.name === name);
            return name.includes(keyword) && !alreadySelected;
        });

        dropdown.innerHTML = "";

        if (filtered.length === 0) {
            dropdown.style.display = "none";
            return;
        }

        filtered.forEach((item) => {
            const div = document.createElement("div");
            div.className = "dropdown-item";
            div.textContent = getName(item);
            div.addEventListener("click", () => selectPeserta(item));
            dropdown.appendChild(div);
        });

        dropdown.style.display = "block";
    });

    // ==== FUNGSI MEMILIH PESERTA ====
    function selectPeserta(item) {
        const name = getName(item);
        const id = getId(item);

        // cegah duplikat
        const exists = id
            ? selectedPeserta.some((p) => p.id === id)
            : selectedPeserta.some((p) => p.name === name);
        if (!exists) {
            selectedPeserta.push({ id: id, name: name });
            updateSelectedChips();
        }

        searchInput.value = "";
        dropdown.style.display = "none";
    }

    // UPDATE CHIP & HIDDEN INPUT
    function updateSelectedChips() {
        selectedContainer.innerHTML = "";

        selectedPeserta.forEach((p) => {
            const chip = document.createElement("div");
            chip.classList.add("chip");
            chip.innerHTML = `
                ${p.name}
                <button type="button" class="chip-remove" data-id="${
                    p.id ?? ""
                }">x</button>
            `;
            selectedContainer.appendChild(chip);
        });

        selectedContainer.querySelectorAll(".chip-remove").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const id = e.currentTarget.getAttribute("data-id");
                removePeserta(id);
            });
        });

        hiddenInput.value = JSON.stringify(selectedPeserta);
    }

    function removePeserta(nameOrId) {
        // coba hapus berdasarkan id dulu (numeric/ulid), kalau tidak cocok hapus berdasarkan name
        const byId = selectedPeserta.some(
            (p) => String(p.id) === String(nameOrId)
        );
        if (byId) {
            selectedPeserta = selectedPeserta.filter(
                (p) => String(p.id) !== String(nameOrId)
            );
        } else {
            selectedPeserta = selectedPeserta.filter(
                (p) => p.name !== nameOrId
            );
        }
        updateSelectedChips();
    }
    // ==== END FUNGSI MEMILIH PESERTA ====

    async function loadTrainingReference() {
        const unitId = window.userUnitId;

        if (!unitId) {
            console.error("Unit ID tidak ditemukan.");
            return;
        }

        try {
            const res = await getJSON(
                `/training/training-request/training-references/${unitId}`
            );

            if (res.status !== "success") {
                console.error("Gagal mengambil data training.");
                return;
            }

            sertifikasiList = res.data;

            judulSelect.innerHTML = `
                <option value="">-- Pilih Judul Sertifikasi --</option>
            `;

            sertifikasiList.forEach((item) => {
                judulSelect.innerHTML += `
                    <option value="${item.id}">
                        ${item.judul_sertifikasi}
                    </option>
                `;
            });
        } catch (err) {
            console.error("Error fetch training reference:", err);
        }
    }

    loadTrainingReference();

    judulSelect.addEventListener("change", () => {
        const id = parseInt(judulSelect.value);
        const data = sertifikasiList.find((item) => item.id === id);

        if (!data) {
            [
                penyelenggara,
                jumlahJam,
                // alasan,
                jenisPelatihan,
                jenisPortofolio,
                waktuPelaksanaan,
                namaProyek,
                biayaPelatihan,
                uhpd,
                biayaAkomodasi,
                estimasiTotalBiaya,
                startDate,
                endDate,
            ].forEach((input) => (input.value = "-"));
            return;
        }

        penyelenggara.value = safeValue(data.penyelenggara);
        jumlahJam.value = safeValue(data.jumlah_jam);
        // alasan.value = safeValue(data.alasan);
        jenisPelatihan.value = safeValue(data.jenis_pelatihan);
        jenisPortofolio.value = safeValue(data.jenis_portofolio);
        waktuPelaksanaan.value = safeValue(data.waktu_pelaksanaan);
        namaProyek.value = safeValue(data.nama_proyek);
        biayaPelatihan.value = safeValue(formatRupiah(data.biaya_pelatihan));
        uhpd.value = safeValue(formatRupiah(data.uhpd));
        biayaAkomodasi.value = safeValue(formatRupiah(data.biaya_akomodasi));
        startDate.value = safeValue(formatDate(data.start_date));
        endDate.value = safeValue(formatDate(data.end_date));

        biayaHelper.hitungTotal();
    });

    inputForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const form = e.target;

        const raw = Object.fromEntries(new FormData(form));

        if (!raw.judul_sertifikasi) {
            Swal.fire(
                "Peringatan",
                "Judul sertifikasi harus dipilih",
                "warning"
            );
            return;
        }

        const payload = {
            judul_sertifikasi: parseInt(raw.judul_sertifikasi),
            penyelenggara: cleanValue(raw.penyelenggara),
            jumlah_jam: parseInt(raw.jumlah_jam),
            jenis_pelatihan: cleanValue(raw.jenis_pelatihan),
            jenis_portofolio: cleanValue(raw.jenis_portofolio),
            waktu_pelaksanaan: cleanValue(raw.waktu_pelaksanaan),
            nama_proyek: cleanValue(raw.nama_proyek),

            biaya_pelatihan: toNumber(raw.biaya_pelatihan),
            uhpd: toNumber(raw.uhpd),
            biaya_akomodasi: toNumber(raw.biaya_akomodasi),
            estimasi_total_biaya: toNumber(raw.estimasi_total_biaya),

            realisasi_biaya_pelatihan: toNumber(raw.realisasi_biaya_pelatihan),

            start_date: raw.start_date,
            end_date: raw.end_date,

            peserta_list: JSON.parse(raw.peserta_list || "[]"),
        };

        const fd = buildFormData(form, payload);

        try {
            modal.classList.add("hidden");
            Swal.fire({
                title: "Menyimpan Data...",
                text: "Harap tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            for (let [key, value] of fd.entries()) {
                console.log(key + ":", value);
            }

            const res = await postFormData(
                "/training/training-request/input-training-request",
                fd
            );

            console.log("res input training", res);
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

                // inputForm.reset();
                // window.location.reload();
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

    /** ===============================
     *   HELPERS
     * =============================== */

    function safeValue(value) {
        return value === null || value === undefined || value === ""
            ? "-"
            : value;
    }

    function cleanValue(value) {
        if (value === "-" || value === "" || value == null) return null;
        return value;
    }

    function buildFormData(form, payload) {
        const fd = new FormData();

        fd.append("data", JSON.stringify(payload));

        const file = form.querySelector('input[name="lampiran_penawaran"]')
            .files[0];
        if (file) fd.append("lampiran_penawaran", file);

        return fd;
    }

    function formatRupiah(value) {
        if (value == null || value === "" || value === "-") return "-";

        const clean = value.toString().replace(/[^\d]/g, "");
        if (!clean) return "-";

        const number = parseInt(clean, 10);

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(number);
    }

    function formatDate(dateValue) {
        if (!dateValue) return "-";

        let date = new Date(dateValue);

        if (isNaN(date)) {
            const parts = dateValue.split(/[-/]/);
            if (parts.length === 3) {
                const [day, month, year] = parts;
                date = new Date(`${year}-${month}-${day}`);
            }
        }

        if (isNaN(date)) return "-";

        return date.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        });
    }

    function toNumber(rupiah) {
        if (!rupiah) return 0;
        return parseInt(rupiah.replace(/[^\d]/g, "")) || 0;
    }

    function toIsoDate(dateString) {
        if (!dateString) return null;
        const d = new Date(dateString);
        if (isNaN(d)) return "";
        return d.toISOString().slice(0, 10);
    }

    function initBiayaHandler(inputForm) {
        const biayaPelatihan = inputForm.querySelector("input[name='biaya_pelatihan']");
        const toggleRealisasi = inputForm.querySelector("#toggle-realisasi");
        const realisasiBiaya = inputForm.querySelector("input[name='realisasi_biaya_pelatihan']");
        const uhpd = inputForm.querySelector("input[name='uhpd']");
        const akomodasi = inputForm.querySelector("input[name='biaya_akomodasi']");
        const total = inputForm.querySelector("input[name='estimasi_total_biaya']");

        realisasiBiaya.readOnly = true;
        realisasiBiaya.classList.add("u-input--disabled");

        total.value = "";

        if (!biayaPelatihan || !uhpd || !akomodasi || !total) {
            console.warn("Biaya handler: element tidak ditemukan di form");
            return;
        }

        // Toggle realisasi biaya pelatihan
        if (toggleRealisasi) {
            toggleRealisasi.addEventListener("change", function () {
                if (this.checked) {
                    // Aktifkan input
                    realisasiBiaya.readOnly = false;
                    realisasiBiaya.placeholder = "Masukkan realisasi biaya...";
                    realisasiBiaya.classList.remove(
                        "u-bg-gray-100",
                        "u-text-gray-500",
                        "u-pointer-events-none"
                    );
                    total.value = "";
                    realisasiBiaya.value = "";
                    realisasiBiaya.focus();
                } else {
                    // Kunci lagi input
                    realisasiBiaya.readOnly = true;
                    realisasiBiaya.value = "";
                    realisasiBiaya.placeholder = "";
                    realisasiBiaya.classList.add(
                        "u-bg-gray-100",
                        "u-text-gray-500",
                        "u-pointer-events-none"
                    );
                    total.value = "";
                    hitungTotal();
                }
            });
        }

        function formatInputRupiah(element) {
            element.addEventListener("input", () => {
                const val = element.value.replace(/[^\d]/g, "");
                element.value = formatRupiah(val);
                hitungTotal(); // hitung ulang ketika angka berubah
            });
        }

        // Terapkan auto format rupiah
        [uhpd, akomodasi, realisasiBiaya].forEach((el) => {
            if (el) formatInputRupiah(el);
        });

        biayaPelatihan.addEventListener("input", () => {
            const val = biayaPelatihan.value.replace(/[^\d]/g, "");
            biayaPelatihan.value = formatRupiah(val);
            hitungTotal();
        });

        // ============ HITUNG TOTAL =============
        function hitungTotal() {
            const u = parseInt(uhpd.value.replace(/\D/g, "")) || 0;
            const a = parseInt(akomodasi.value.replace(/\D/g, "")) || 0;

            let biaya = 0;

            if (toggleRealisasi.checked) {
                // pakai realisasi
                biaya = parseInt(realisasiBiaya.value.replace(/\D/g, "")) || 0;
            } else {
                // pakai biaya rencana
                biaya = parseInt(biayaPelatihan.value.replace(/\D/g, "")) || 0;
            }

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
}
