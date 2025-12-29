import { initModalHandler } from "@/utils/modal";
import { initGetDataEvaluationTable } from "./handler/getDataEvaluationHandler";
import { initUpdateRealisasiDateHandler } from "./handler/updateRealisasiDate";

document.addEventListener("DOMContentLoaded", () => {
    initModalHandler("#btn-input-evaluation", "#modal-input-evaluation", "#close-input-evaluation");
    initModalHandler("#btn-upload-certif", "#modal-upload-certif", "#close-upload-certif");

    const tableBody = document.querySelector("#dashboard-table tbody");
    if (tableBody)
        initGetDataEvaluationTable();
        initUpdateRealisasiDateHandler(tableBody)
});
