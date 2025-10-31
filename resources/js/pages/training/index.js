import { initAllApprovalHandler } from "./handler/allApproveHandler";
import { initBulkApprovalHandler } from "./handler/bulkApprovalHandler";
import { initDownloadTemplateHandler } from "./handler/downloadTemplateHandler";
import { initGetDataTable } from "./handler/getData";
import { initImportHandler } from "./handler/importHandler";
import { initInputHandler } from "./handler/inputHandler";

document.addEventListener("DOMContentLoaded", () => {

    if (document.querySelector("#training-table tbody"))
        initGetDataTable();
    
    if (document.querySelector(".btn-import")) 
        initImportHandler();

    if (document.querySelector(".btn-add")) 
        initInputHandler();

    if (document.querySelector(".btn-download-template"))
        initDownloadTemplateHandler();

    if (document.querySelector("#btn-bulk-approve"))
        initBulkApprovalHandler();

    if (document.querySelector("#btn-all-approve"))
        initAllApprovalHandler();
});
