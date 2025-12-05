import { postFormData } from "@/utils/fetch";

export async function initImportHandler(file) {
    console.log("file in chunk", file);

    const chunkSize = 500 * 1024; // 500KB
    const totalChunks = Math.ceil(file.size / chunkSize);

    try {
        for (let i = 0; i < totalChunks; i++) {
            const start = i * chunkSize;
            const end = start + chunkSize;

            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append("chunk", chunk);
            formData.append("index", i);
            formData.append("total", totalChunks);
            formData.append("filename", file.name);

            console.log("chunk", chunk);
            console.log("index", i);
            const res = await postFormData("/training/training-request/import-lna", formData);

            console.log("response import", res);

            if (!res || res.status !== "success") {
                console.error("Chunk gagal:", res);
                throw new Error(`Upload gagal pada chunk ${i}`);
            }

            console.log(`Chunk ${i + 1}/${totalChunks} berhasil`);
        }

        return { status: "success" };

    } catch (err) {
        console.error("Error upload:", err);
        return { status: "error", message: err.message };
    }
}
