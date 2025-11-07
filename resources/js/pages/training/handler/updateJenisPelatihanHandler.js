import { postFormData } from "@/utils/fetch";

export function initUpdateJenisPelatihanHandler(tableBody) {
    tableBody.addEventListener("change", async (e) => {
        if (e.target.classList.contains("jenis-pelatihan-select")) {
            const id = e.target.dataset.id;

            const formData = new FormData();
            formData.append("jenis_pelatihan", e.target.value);
            console.log('target value', e.target.value);

            try {
                const res = await postFormData(
                    `training/dashboard/${id}/update-jenis-pelatihan`, formData);
                console.log("response", res);

                if (res.status === "success") {
                    alert(res.message);
                    location.reload();
                } else {
                    alert("Gagal mengambil data untuk edit");
                }
            } catch (error) {
                console.error(error);
                alert("Gagal memuat data");
            }
        }
    });
}
