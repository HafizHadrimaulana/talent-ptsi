import { postFormData } from "@/utils/fetch";

/**
 * Handler untuk upload file besar dengan metode Chunking.
 * @param {File} file - Objek file dari input.
 * @param {string} type - Jenis import (contoh: 'lna').
 */
export async function initImportHandler(file) {
    const uploadUrl = "/training/training-management/import-lna"
    const chunkSize = 500 * 1024; // 500KB per chunk
    const totalChunks = Math.ceil(file.size / chunkSize);

    let lastResponse = null;

    // Loop pengiriman chunk secara berurutan (Sequential)
    for (let i = 0; i < totalChunks; i++) {
        const formData = new FormData();
        formData.append(
            "chunk",
            file.slice(i * chunkSize, Math.min((i + 1) * chunkSize, file.size))
        );
        formData.append("index", i);
        formData.append("total", totalChunks);
        formData.append("filename", file.name);

        const res = await postFormData(uploadUrl, formData);

        if (!res || res.status !== "success") {
            throw new Error(res?.message || "Gagal mengunggah file.");
        }

        lastResponse = res;
    }

    return lastResponse;
}
