import { initAllApprovalHandler } from "./training-approval/handler/allApproveHandler";
import { initBulkApprovalHandler } from "./training-approval/handler/bulkApprovalHandler";
import { initDownloadTemplateHandler } from "./training-approval/handler/downloadTemplateHandler";
import { initGetDataTable } from "./training-approval/handler/getData";
import { initImportHandler } from "./training-approval/handler/importHandler";
import { initInputHandler } from "./training-approval/handler/inputHandler";
import { initUpdateJenisPelatihanHandler } from "./training-approval/handler/updateJenisPelatihanHandler";

document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector(".training-table tbody");
    // const paginationContainer = document.getElementById("pagination");
    // const pageSizeSelect = document.getElementById("pageSizeSelect");

    // let currentPage = 1;
    // let perPage = parseInt(pageSizeSelect.value, 10);

    if (tableBody) {
        initGetDataTable(tableBody);
        initUpdateJenisPelatihanHandler(tableBody);
    }

    if (document.querySelector(".btn-import")) initImportHandler();

    if (document.querySelector(".btn-add")) initInputHandler();

    if (document.querySelector(".btn-download-template"))
        initDownloadTemplateHandler();

    if (document.querySelector("#btn-bulk-approve")) initBulkApprovalHandler();

    if (document.querySelector("#btn-all-approve")) initAllApprovalHandler();
});
