import { getJSON, postJSON } from "@/utils/fetch";

export async function getDetailLnaHandler(id) {
    if (!id) {
        throw new Error("ID LNA tidak valid");
    }

    const res = await getJSON(`/training/training-request/${id}/get-lna-by-id`);

    if (res.status !== "success") {
        throw new Error(res.message || "Gagal mengambil data LNA");
    }

    return res.data;
}
