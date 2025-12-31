import { postJSON } from "@/utils/fetch";

/**
 * Helper internal untuk menangani request post standar dengan Konfirmasi
 */
const processApproval = async (url, reloadCallback, note) => {
    $(".u-modal").fadeOut(150, function() {
        $(this).addClass("hidden").hide();
    });

    console.log('note abcd', note);

    try {
        Swal.fire({
            title: "Memproses...",
            text: "Mohon tunggu sebentar.",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const bodyData = new FormData();
        bodyData.append('note', note || '');

        const res = await postJSON(url, bodyData);
        
        if (res.status === "success" || res.ok) {
            await Swal.fire({
                icon: "success",
                title: "Berhasil!",
                text: res.message || `Data berhasil disetuui.`,
                timer: 1500,
                showConfirmButton: false,
            });

            // Tutup semua modal yang sedang terbuka
            $('.u-modal').fadeOut(200, function() {
                $(this).addClass('hidden');
            });

            // Refresh tabel
            if (typeof reloadCallback === "function") reloadCallback();
        } else {
            throw new Error(res.message || `Gagal menyetujui data.`);
        }
    } catch (error) {
        console.error("Approve Error:", error);
        Swal.fire({
            icon: "error",
            title: "Gagal",
            text: error.message || "Terjadi kesalahan sistem.",
        });
    }
};

/**
 * Approve Standar (Training Request)
 */
export const executeApprove = async (id, reloadCallback, note) => {
    const url = `/training/training-management/${id}/approve-training-submission`;
    console.log('1111note', note);
    await processApproval(url, reloadCallback, note);
};

/**
 * Approve Referensi (Pengajuan Training / LNA)
 */
export const executeApproveReference = async (id, reloadCallback, note) => {
    const url = `/training/training-management/${id}/approve-training-reference`;
    await processApproval(url, reloadCallback, "menerima pengajuan", note);
};