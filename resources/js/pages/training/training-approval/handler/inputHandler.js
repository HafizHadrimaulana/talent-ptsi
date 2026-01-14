import { postFormData, getJSON } from "@/utils/fetch";

export function initInputHandler(modalSelector) {
    const modal = document.querySelector(modalSelector);
    const inputForm = document.querySelector("#add-form");
    if (!inputForm) return;

    // --- 1. SELEKTOR ELEMEN ---
    const judulSelect = inputForm.querySelector(
        "select[name='judul_sertifikasi']"
    );
    const penyelenggara = inputForm.querySelector(
        "input[name='penyelenggara']"
    );
    const jumlahJam = inputForm.querySelector("input[name='jumlah_jam']");
    const jenisPelatihan = inputForm.querySelector(
        "input[name='jenis_pelatihan']"
    );
    const jenisPortofolio = inputForm.querySelector(
        "input[name='jenis_portofolio']"
    );
    const waktuPelaksanaan = inputForm.querySelector(
        "input[name='waktu_pelaksanaan']"
    );
    const namaProyek = inputForm.querySelector("input[name='nama_proyek']");
    const biayaPelatihan = inputForm.querySelector(
        "input[name='biaya_pelatihan']"
    );
    const realisasiBiaya = inputForm.querySelector(
        "input[name='realisasi_biaya_pelatihan']"
    );
    const toggleRealisasi = inputForm.querySelector("#toggle-realisasi");
    const startDate = inputForm.querySelector("input[name='start_date']");
    const endDate = inputForm.querySelector("input[name='end_date']");

    // Selektor Peserta
    const searchInput = document.getElementById("peserta-search");
    const dropdown = document.getElementById("peserta-dropdown");
    const selectedContainer = document.getElementById("peserta-selected");
    const hiddenInput = document.getElementById("peserta-list-hidden");

    let sertifikasiList = [];
    let pesertaData = [];
    let selectedPeserta = [];

    // --- 2. CORE FUNCTIONS (BIAYA & FORMAT) ---
    function formatRupiah(value) {
        if (value == null || value === "" || value === "-") return "";
        let strValue = value.toString();

        // Hanya hapus desimal jika datang dari server (format 5000000.00)
        if (strValue.endsWith(".00") || strValue.endsWith(".0")) {
            strValue = strValue.split(".")[0];
        }

        // Ambil angka saja
        const cleanNumber = strValue.replace(/\D/g, "");
        if (!cleanNumber) return "";

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(parseInt(cleanNumber, 10));
    }

    function toNumber(value) {
        if (!value) return 0;
        const clean = value.toString().replace(/[^\d]/g, "");
        return clean ? parseInt(clean, 10) : 0;
    }

    // --- 3. PESERTA HANDLER (SEARCH & CHIPS) ---
    function getName(item) {
        if (!item) return "";
        const name = item.name || item.person_name || item.full_name || "";
        const empId = item.employee_id || "";
        return empId ? `${name} - ${empId}` : name;
    }

    function getId(item) {
        if (!item && item !== 0) return null;
        return item.id ?? item.person_id ?? null;
    }

    function updateSelectedChips() {
        selectedContainer.innerHTML = "";
        selectedPeserta.forEach((p) => {
            const chip = document.createElement("div");
            chip.classList.add("chip");
            chip.innerHTML = `
                ${p.name}
                <button type="button" class="chip-remove" data-id="${
                    p.id ?? ""
                }" data-name="${p.name}">x</button>
            `;
            selectedContainer.appendChild(chip);
        });

        selectedContainer.querySelectorAll(".chip-remove").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const id = e.currentTarget.getAttribute("data-id");
                const name = e.currentTarget.getAttribute("data-name");
                removePeserta(id, name);
            });
        });
        hiddenInput.value = JSON.stringify(selectedPeserta);
    }

    function selectPeserta(item) {
        const name = getName(item);
        const id = getId(item);
        const exists = id
            ? selectedPeserta.some((p) => p.id === id)
            : selectedPeserta.some((p) => p.name === name);

        if (!exists) {
            selectedPeserta.push({ id, name });
            updateSelectedChips();
        }
        searchInput.value = "";
        dropdown.style.display = "none";
    }

    function removePeserta(id, name) {
        if (id && id !== "null") {
            selectedPeserta = selectedPeserta.filter(
                (p) => String(p.id) !== String(id)
            );
        } else {
            selectedPeserta = selectedPeserta.filter((p) => p.name !== name);
        }
        updateSelectedChips();
    }

    // --- 4. EVENT LISTENERS ---
    function initEventListeners() {
        // Biaya Toggle Logic
        if (toggleRealisasi) {
            toggleRealisasi.addEventListener("change", function () {
                if (this.checked) {
                    realisasiBiaya.readOnly = false;
                    realisasiBiaya.classList.remove(
                        "u-bg-gray-100",
                        "u-text-gray-500",
                        "u-pointer-events-none"
                    );
                    realisasiBiaya.value = "";
                    realisasiBiaya.focus();
                } else {
                    realisasiBiaya.readOnly = true;
                    realisasiBiaya.classList.add(
                        "u-bg-gray-100",
                        "u-text-gray-500",
                        "u-pointer-events-none"
                    );
                    realisasiBiaya.value = biayaPelatihan.value;
                }
            });
        }

        // Input Formatting
        [biayaPelatihan, realisasiBiaya].forEach((el) => {
            el.addEventListener("input", (e) => {
                const formatted = formatRupiah(e.target.value);
                e.target.value = formatted;
                if (
                    el.name === "biaya_pelatihan" &&
                    toggleRealisasi &&
                    !toggleRealisasi.checked
                ) {
                    realisasiBiaya.value = formatted;
                }
            });
        });

        // Search Peserta Logic
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
                    : selectedPeserta.some((s) => s.name === getName(item));
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

        // Judul Select Change
        judulSelect.addEventListener("change", () => {
            const id = parseInt(judulSelect.value);
            const data = sertifikasiList.find((item) => item.id === id);

            if (!data) {
                [
                    penyelenggara,
                    jumlahJam,
                    jenisPelatihan,
                    jenisPortofolio,
                    waktuPelaksanaan,
                    namaProyek,
                    biayaPelatihan,
                    realisasiBiaya,
                    startDate,
                    endDate,
                ].forEach((input) => {
                    if (input) input.value = "-";
                });
                return;
            }

            penyelenggara.value = safeValue(data.penyelenggara);
            jumlahJam.value = safeValue(data.jumlah_jam);
            jenisPelatihan.value = safeValue(data.jenis_pelatihan);
            jenisPortofolio.value = safeValue(data.jenis_portofolio);
            waktuPelaksanaan.value = safeValue(data.waktu_pelaksanaan);
            namaProyek.value = safeValue(data.nama_proyek);

            const formatted = formatRupiah(data.biaya_pelatihan);
            biayaPelatihan.value = formatted;
            if (toggleRealisasi && !toggleRealisasi.checked) {
                realisasiBiaya.value = formatted;
            }

            startDate.value = safeValue(formatDate(data.start_date));
            endDate.value = safeValue(formatDate(data.end_date));
        });

        // Form Submit
        inputForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const raw = Object.fromEntries(new FormData(e.target));

            const biayaNormal = toNumber(raw.biaya_pelatihan);
            const realisasiInput = toNumber(raw.realisasi_biaya_pelatihan);
            const realisasiFinal =
                realisasiInput > 0 ? realisasiInput : biayaNormal;

            const payload = {
                judul_sertifikasi: parseInt(raw.judul_sertifikasi),
                penyelenggara: cleanValue(raw.penyelenggara),
                jumlah_jam: parseInt(raw.jumlah_jam) || 0,
                jenis_pelatihan: cleanValue(raw.jenis_pelatihan),
                jenis_portofolio: cleanValue(raw.jenis_portofolio),
                waktu_pelaksanaan: cleanValue(raw.waktu_pelaksanaan),
                nama_proyek: cleanValue(raw.nama_proyek),
                biaya_pelatihan: biayaNormal,
                realisasi_biaya_pelatihan: realisasiFinal,
                start_date: raw.start_date,
                end_date: raw.end_date,
                peserta_list: JSON.parse(raw.peserta_list || "[]"),
            };

            const fd = buildFormData(e.target, payload);
            try {
                modal.classList.add("hidden");
                Swal.fire({
                    title: "Menyimpan...",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
                const res = await postFormData(
                    "/training/training-request/input-training-request",
                    fd
                );
                Swal.close();
                if (res.status === "success") {
                    await Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    window.location.reload();
                } else {
                    Swal.fire("Gagal", res.message, "error");
                }
            } catch (error) {
                Swal.fire("Error", "Terjadi kesalahan server", "error");
            }
        });
    }

    // --- 5. DATA LOADERS ---
    async function loadTrainingReference() {
        try {
            const res = await getJSON(
                `/training/training-request/training-references/${window.userUnitId}`
            );
            if (res.status === "success") {
                sertifikasiList = res.data;
                judulSelect.innerHTML =
                    '<option value="">-- Pilih Judul Sertifikasi --</option>' +
                    sertifikasiList
                        .map(
                            (item) =>
                                `<option value="${item.id}">${item.judul_sertifikasi}</option>`
                        )
                        .join("");
            }
        } catch (e) {
            console.error("Error load reference:", e);
        }
    }

    async function loadPeserta() {
        try {
            const res = await getJSON(
                `/training/training-request/${window.userUnitId}/get-employee-by-unit`
            );
            if (res.status === "success") pesertaData = res.data;
        } catch (e) {
            console.error("Error load peserta:", e);
        }
    }

    // --- 6. OTHER HELPERS ---
    function safeValue(value) {
        return value == null || value === "" ? "-" : value;
    }
    function cleanValue(value) {
        return value === "-" || value === "" ? null : value;
    }
    function buildFormData(form, payload) {
        const fd = new FormData();
        fd.append("data", JSON.stringify(payload));
        const file = form.querySelector('input[name="lampiran_penawaran"]')
            .files[0];
        if (file) fd.append("lampiran_penawaran", file);
        return fd;
    }
    function formatDate(dateValue) {
        if (!dateValue) return "-";
        const date = new Date(dateValue);
        return isNaN(date)
            ? "-"
            : date.toLocaleDateString("id-ID", {
                  day: "2-digit",
                  month: "long",
                  year: "numeric",
              });
    }

    // --- INIT ---
    initEventListeners();
    loadPeserta();
    loadTrainingReference();
}
