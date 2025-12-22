import { postJSON, postFormData } from "@/utils/fetch";

/**
 * @param {Element} tableBody
 * @param {Function} reloadCallback
 */

const initApproveHandler = (tableBody) => {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='approve']");
        if (!button) return;

        const id = button.dataset.id;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menyetujui data ini?",
            text: "Data akan disetujui dan tidak dapat dibatalkan.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Setujui",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Memproses...",
                text: "Mohon tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postJSON(
                `/training/training-request/${id}/approve-training-request`
            );
            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Disetujui",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menyetujui data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menyetujui data.",
            });
        }
    });
};

const initApproveReferenceHandler = (tableBody) => {
    tableBody.addEventListener("click", async (e) => {
        const button = e.target.closest("button[data-action='approve_training_pengajuan']");
        if (!button) return;

        const id = button.dataset.id;

        const confirmResult = await Swal.fire({
            title: "Yakin ingin menyetujui data referece ini?",
            text: "Data akan disetujui dan tidak dapat dibatalkan.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Setujui",
            cancelButtonText: "Batal",
            reverseButtons: true,
        });

        if (!confirmResult.isConfirmed) return;

        try {
            Swal.fire({
                title: "Memproses...",
                text: "Mohon tunggu sebentar.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const res = await postJSON(
                `/training/training-request/${id}/approve-training-reference`
            );
            console.log("res", res);

            Swal.close();

            if (res.status === "success") {
                await Swal.fire({
                    icon: "success",
                    title: "Disetujui",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message || "Gagal menyetujui data.",
                });
            }
        } catch (error) {
            Swal.close();
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Kesalahan Server",
                text: "Terjadi kesalahan saat menyetujui data.",
            });
        }
    });
};

// const initAllApprovalHandler = () => {
//     const approveAllButton = document.querySelector("#btn-all-approve");

//     if (approveAllButton) {
//         approveAllButton.addEventListener("click", async () => {
//             // Tampilkan konfirmasi menggunakan Swal
//             const result = await Swal.fire({
//                 title: "Approve Semua Data?",
//                 text: "Semua data yang memenuhi kriteria akan di-approve. Lanjutkan?",
//                 icon: "question",
//                 showCancelButton: true,
//                 confirmButtonText: "Ya, Approve Semua",
//                 cancelButtonText: "Batal",
//                 confirmButtonColor: "#3085d6",
//                 cancelButtonColor: "#d33",
//             });

//             if (!result.isConfirmed) return;

//             // Tampilkan loading state
//             Swal.fire({
//                 title: "Memproses...",
//                 text: "Mohon tunggu, sedang meng-approve semua data.",
//                 allowOutsideClick: false,
//                 didOpen: () => {
//                     Swal.showLoading();
//                 },
//             });

//             try {
//                 const res = await postJSON("/training/all-approve");
//                 console.log("res in js", res);

//                 Swal.close();

//                 if (res.status === "success") {
//                     await Swal.fire({
//                         icon: "success",
//                         title: "Berhasil!",
//                         text: res.message,
//                         timer: 2000,
//                         showConfirmButton: false,
//                     });

//                     // reload halaman setelah sukses
//                     location.reload();
//                 } else {
//                     await Swal.fire({
//                         icon: "error",
//                         title: "Gagal!",
//                         text:
//                             res.message ||
//                             "Terjadi kesalahan saat approve semua data.",
//                     });
//                 }
//             } catch (error) {
//                 console.error(error);
//                 Swal.close();
//                 Swal.fire({
//                     icon: "error",
//                     title: "Kesalahan Sistem",
//                     text: "Gagal meng-approve semua data.",
//                 });
//             }
//         });
//     }
// };

// const initBulkApprovalHandler = () => {
//     const bulkApproveBtn = document.querySelector("#btn-bulk-approve");
//     const selectAllCheckbox = document.querySelector("#select-all");

//     if (!bulkApproveBtn) return;

//     selectAllCheckbox?.addEventListener("change", (e) => {
//         const checked = e.target.checked;
//         document.querySelectorAll("input[name='selected[]']").forEach((cb) => {
//             cb.checked = checked;
//         });
//     });

//     // --- Event handler tombol Bulk Approve ---
//     bulkApproveBtn.addEventListener("click", async () => {
//         const checkboxes = document.querySelectorAll(
//             "input[name='selected[]']:checked"
//         );
//         const selected = Array.from(checkboxes).map((c) => c.value);

//         if (selected.length === 0) {
//             Swal.fire({
//                 icon: "warning",
//                 title: "Tidak ada data terpilih",
//                 text: "Pilih minimal satu data untuk disetujui.",
//             });
//             return;
//         }

//         const result = await Swal.fire({
//             title: "Setujui Data Terpilih?",
//             text: `Sebanyak ${selected.length} data akan di-approve.`,
//             icon: "question",
//             showCancelButton: true,
//             confirmButtonText: "Ya, Setujui",
//             cancelButtonText: "Batal",
//             confirmButtonColor: "#3085d6",
//             cancelButtonColor: "#d33",
//         });

//         if (!result.isConfirmed) return;

//         Swal.fire({
//             title: "Memproses...",
//             text: "Sedang melakukan approve data terpilih.",
//             allowOutsideClick: false,
//             didOpen: () => {
//                 Swal.showLoading();
//             },
//         });

//         try {
//             const formData = new FormData();
//             selected.forEach((id) => formData.append("selected[]", id));

//             console.log("FormData entries:", Array.from(formData.entries()));

//             const res = await postFormData("/training/bulk-approve", formData);
//             console.log("res in js", res);

//             Swal.close();

//             if (res.status === "success") {
//                 await Swal.fire({
//                     icon: "success",
//                     title: "Berhasil!",
//                     text: res.message,
//                     timer: 2000,
//                     showConfirmButton: false,
//                 });
//                 location.reload();
//             } else {
//                 Swal.fire({
//                     icon: "error",
//                     title: "Gagal",
//                     text: res.message || "Gagal melakukan approve.",
//                 });
//             }
//         } catch (err) {
//             console.error("Error saat bulk approve:", err);
//             Swal.close();
//             Swal.fire({
//                 icon: "error",
//                 title: "Kesalahan Sistem",
//                 text: "Terjadi kesalahan saat melakukan approve.",
//             });
//         }
//     });
// };

export {
    initApproveHandler,
    initApproveReferenceHandler,
    // initAllApprovalHandler,
    // initBulkApprovalHandler
};
