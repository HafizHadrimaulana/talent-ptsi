import { postFormData } from "@/utils/fetch";

export const initUpdateRealisasiDateHandler = (tableBody) => {
    tableBody.addEventListener("change", async (e) => {
        if (e.target.classList.contains("realisasi-date-input")) {
            const id = e.target.dataset.id;
            const newValue = e.target.value;

            if (!newValue) {
                alert("Tanggal realisasi tidak boleh kosong!");
                return;
            }

            try {
                const formData = new FormData();
                formData.append("realisasi_date", newValue);
                console.log("target value", newValue);

                const res = await postFormData(
                    `dashboard/${id}/update-realisasi-date`,
                    formData
                );
                console.log("response", res);

                if (res.status === "success") {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || "Gagal memperbarui tanggal realisasi");
                }
            } catch (error) {
                console.error(error);
                alert("Terjadi kesalahan saat memperbarui tanggal realisasi");
            }
        }
    });
};
