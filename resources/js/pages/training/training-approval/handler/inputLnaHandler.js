import { postFormData, getJSON } from "@/utils/fetch";

export function initInputLnaHandler(modalSelector) {
    const modal = document.querySelector(modalSelector);
    const inputForm = document.querySelector("#lna-input-form");

    loadUnits();
    initBiayaHandler(inputForm);

    // ==== HANDLE SUBMIT ====
    inputForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const fd = new FormData(inputForm);

        Swal.fire({
            title: "Menyimpan...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            modal.classList.add("hidden");

            Swal.fire({
                title: "Menyimpan Data...",
                text: "Harap tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postFormData(
                "/training/training-request/input-lna",
                fd
            );

            Swal.close();

            if (res.status === "success") {
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

            Swal.fire({
                icon: "error",
                title: "Gagal Menyimpan",
                text: res.message || "Terjadi kesalahan saat menyimpan data.",
            });
        } catch (err) {
            Swal.close();
            console.error("Error:", err);

            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan pada server. Silakan coba lagi.",
            });
        }
    });

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
}

async function loadUnits() {
    const select = document.getElementById("select-unit");
    if (!select) return;

    const role = window.currentUserRole;
    const userUnitId = window.currentUnitId;
    const userUnitName = window.currentUnitName;

    // ==============================
    // SDM UNIT → AUTO SET UNIT
    // ==============================
    if (role === "SDM Unit" && userUnitId) {
        select.innerHTML = `
            <option value="${userUnitId}" selected>
                ${userUnitName ?? "Tidak ada unit"}
            </option>
        `;

        select.disabled = true;
        return;
    }

    try {
        const response = await getJSON(
            "/training/training-request/get-data-units"
        );
        console.log("units", response);

        if (response.status === "success") {
            renderUnitOptions(select, response.data);
        } else {
            console.warn("Gagal mendapatkan data unit");
        }
    } catch (error) {
        console.error("Error fetch units:", error);
    }
}

function renderUnitOptions(selectElement, units) {
    // Hapus semua option kecuali "-- Pilih Unit --"
    selectElement.innerHTML = `<option value="">-- Pilih Unit --</option>`;

    units.forEach((unit) => {
        const opt = document.createElement("option");
        opt.value = unit.id;
        opt.textContent = unit.name;
        selectElement.appendChild(opt);
    });
}
