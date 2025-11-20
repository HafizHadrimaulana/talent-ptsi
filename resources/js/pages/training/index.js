import { initAllApprovalHandler } from "./training-approval/handler/allApproveHandler";
import { initBulkApprovalHandler } from "./training-approval/handler/bulkApprovalHandler";
import { initDownloadTemplateHandler } from "./training-approval/handler/downloadTemplateHandler";
import { initGetDataTable } from "./training-approval/getData";
import { initImportHandler } from "./training-approval/handler/importHandler";
import { initInputHandler } from "./training-approval/handler/inputHandler";
import { initUpdateJenisPelatihanHandler } from "./training-approval/handler/updateJenisPelatihanHandler";
import { initDragDropUpload } from "./training-approval/handler/dragDropImport";

document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector(".training-table tbody");

    if (tableBody) {
        initGetDataTable(tableBody);
        initUpdateJenisPelatihanHandler(tableBody);
    }

    if (document.querySelector(".btn-add")) initInputHandler();

    if (document.querySelector("#import-modal")) {
        initDragDropUpload();
    }

    if (document.querySelector(".btn-download-template"))
        initDownloadTemplateHandler();

    if (document.querySelector("#btn-bulk-approve")) initBulkApprovalHandler();

    if (document.querySelector("#btn-all-approve")) initAllApprovalHandler();
});
