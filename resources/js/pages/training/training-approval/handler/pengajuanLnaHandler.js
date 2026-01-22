import { postFormData, getJSON } from "@/utils/fetch";

export function initPengajuanLnaHandler(modal) {
    if (!modal) return;
    const inputForm = document.querySelector("#lna-pengajuan-form");
    const unitSelect = modal.querySelector(".js-select-unit");

    if (!inputForm || !unitSelect) return;

    autoSetUnit(unitSelect);
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

        if (!biayaPelatihan) {
            alert("Biaya handler: element tidak ditemukan di form");
            return;
        }

        biayaPelatihan.addEventListener("input", () => {
            const val = biayaPelatihan.value.replace(/[^\d]/g, "");
            biayaPelatihan.value = formatRupiah(val);
        });

        function formatRupiah(value) {
            if (!value) return "";
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(parseInt(value, 10));
        }
    }
}

function autoSetUnit(select) {
    const userUnitId = window.currentUnitId;
    const userUnitName = window.currentUnitName;

    if (!userUnitId) {
        console.warn("User tidak memiliki unit");
        return;
    }

    select.innerHTML = `
        <option value="${userUnitId}" selected>
            ${userUnitName || "Unit tidak ditemukan"}
        </option>
    `;
    select.disabled = true;
}
