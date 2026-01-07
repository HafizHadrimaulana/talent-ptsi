import { postFormData } from "@/utils/fetch";

/**
 * Handler untuk upload file besar dengan metode Chunking.
 * @param {File} file - Objek file dari input.
 * @param {string} type - Jenis import (contoh: 'lna').
 * @param {Function} onProgress - Callback untuk mengupdate UI progress (optional).
 */
export async function initImportHandler(file, type = "lna", onProgress = null) {
    const uploadUrl = "/training/training-management/import-lna"
    const chunkSize = 1024 * 1024; // 500KB per chunk
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

        // Kirim update ke UI tanpa memicu notifikasi baru
        if (typeof onProgress === "function") {
            const percent = Math.round(((i + 1) / totalChunks) * 100);
            onProgress(percent);
        }
    }

    return lastResponse;
}
