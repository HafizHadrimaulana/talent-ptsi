import { initModalHandler } from "@/utils/modal";
import { initGetDataEvaluationTable } from "./handler/getDataEvaluationHandler";

document.addEventListener("DOMContentLoaded", () => {
    initModalHandler("#btn-input-evaluation", "#modal-input-evaluation", "#close-input-evaluation");
    initModalHandler("#btn-upload-certif", "#modal-upload-certif", "#close-upload-certif");

    if (document.querySelector("#dashboard-table tbody"))
        initGetDataEvaluationTable();
});
