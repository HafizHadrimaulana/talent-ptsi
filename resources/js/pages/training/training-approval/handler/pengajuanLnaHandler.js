import { postFormData } from "@/utils/fetch";

export function initPengajuanLnaHandler(modal) {
    if (!modal) return;

    const inputForm = modal.querySelector("#lna-pengajuan-form");
    const unitSelect = modal.querySelector(".js-select-unit");

    if (!inputForm || !unitSelect) return;

    autoSetUnit(unitSelect);
    initBiayaHandler(inputForm);

    inputForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const fd = new FormData(inputForm);

        Swal.fire({
            title: "Menyimpan Data...",
            text: "Harap tunggu sebentar",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await postFormData(
                "/training/training-request/input-lna",
                fd,
            );

            Swal.close();

            if (res.status !== "success") {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Menyimpan",
                    text:
                        res.message || "Terjadi kesalahan saat menyimpan data.",
                });
                return;
            }

            await Swal.fire({
                icon: "success",
                title: "Berhasil!",
                text: res.message || "Data berhasil disimpan.",
                timer: 1500,
                showConfirmButton: false,
            });

            inputForm.reset();

            modal.classList.add("hidden");

            window.location.reload();

        } catch (err) {
            Swal.close();
            console.error(err);

            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan pada server. Silakan coba lagi.",
            });
        }
    });
}

function autoSetUnit(select) {
    if (!select) return;

    const userUnitId = window.currentUnitId;
    const userUnitName = window.currentUnitName;

    if (!userUnitId) {
        console.warn("autoSetUnit: User tidak memiliki unit");
        return;
    }

    // Reset option agar tidak dobel
    select.innerHTML = "";

    const option = document.createElement("option");
    option.value = userUnitId;
    option.textContent = userUnitName || "Unit tidak ditemukan";
    option.selected = true;

    select.appendChild(option);

    // Lock agar user tidak bisa ganti unit
    select.disabled = true;
}

function initBiayaHandler(inputForm) {
    const biayaPelatihan = inputForm.querySelector(
        "input[name='biaya_pelatihan']",
    );

    if (!biayaPelatihan) return;

    biayaPelatihan.addEventListener("input", () => {
        const raw = biayaPelatihan.value.replace(/[^\d]/g, "");
        biayaPelatihan.value = formatRupiah(raw);
    });

    function formatRupiah(value) {
        if (!value) return "";
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(Number(value));
    }
}
