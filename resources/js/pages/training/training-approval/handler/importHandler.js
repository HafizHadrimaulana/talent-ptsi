import { postFormData } from "@/utils/fetch";

export async function initImportHandler(file, role) {
    const endpoints = {
        lna: "/training/training-request/import-lna",
        training: "/training/training-request/import-training",
    };

    const uploadUrl = endpoints[role] || endpoints["training"];
    const chunkSize = 500 * 1024;
    const totalChunks = Math.ceil(file.size / chunkSize);

    let lastResponse = null;

    for (let i = 0; i < totalChunks; i++) {
        const start = i * chunkSize;
        const end = start + chunkSize;

        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append("chunk", chunk);
        formData.append("index", i);
        formData.append("total", totalChunks);
        formData.append("filename", file.name);

        const res = await postFormData(uploadUrl, formData);

        if (!res || res.status !== "success") {
            throw new Error(
                res?.message || `Upload gagal di chunk ${i + 1}`
            );
        }

        lastResponse = res;
    }

    return lastResponse;
}
