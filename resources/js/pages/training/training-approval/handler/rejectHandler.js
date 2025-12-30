import { postJSON } from "@/utils/fetch";

/**
 * Helper internal untuk menangani request reject dengan alasan
 */
const processRejection = async (url, title, reloadCallback, note) => {
    $(".u-modal").fadeOut(150, function () {
        $(this).addClass("hidden").hide();
    });

    try {
        // 2. Tampilkan Loading
        Swal.fire({
            title: "Memproses Penolakan...",
            text: "Mohon tunggu sebentar.",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const bodyData = new FormData();
        bodyData.append('note', note || '');

        // 3. Kirim Request (Body berisi alasan)
        const res = await postJSON(url, bodyData);

        Swal.close();

        // 4. Handle Response
        if (res.status === "success" || res.ok) {
            await Swal.fire({
                icon: "success",
                title: "Ditolak",
                text: res.message || "Data berhasil ditolak.",
                timer: 1500,
                showConfirmButton: false,
            });

            if (typeof reloadCallback === "function") reloadCallback();
        } else {
            throw new Error(res.message || "Gagal menolak data.");
        }
    } catch (error) {
        Swal.close();
        console.error("Reject Error:", error);
        Swal.fire({
            icon: "error",
            title: "Gagal",
            text: error.message || "Terjadi kesalahan sistem.",
        });
    }
};

export const executeReject = async (id, reloadCallback, note) => {
    const url = `/training/training-management/${id}/reject-training-submission`;
    await processRejection(url, "Tolak Permintaan Training?", reloadCallback, note);
};

export const executeRejectPengajuan = async (id, reloadCallback, note) => {
    const url = `/training/training-management/${id}/reject-training-pengajuan`;
    await processRejection(url, "Tolak Pengajuan Training?", reloadCallback, note);
};