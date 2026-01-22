import { initGetDataTable } from "./training-approval/getData";
import { initModalHandler } from "../../utils/modal";
import { initInputHandler } from "./training-approval/handler/inputHandler";
import { initInputLnaHandler } from "./training-approval/handler/inputLnaHandler";
import { initDragDropUpload } from "./training-approval/handler/dragDropImport";
import { initDownloadTemplateHandler } from "./training-approval/handler/downloadTemplateHandler";
import { initPengajuanLnaHandler } from "./training-approval/handler/pengajuanLnaHandler";

const TRAINING_CONFIG = {
    tables: {
        selector: ".training-table",
        dataAttributes: {
            role: "data-role",
            unitId: "data-unit-id",
        },
    },
    tabs: {
        container: "#training-tabs",
        panelSelector: ".u-tabs__panel",
        activeClass: "is-active",
        hiddenClass: "hidden",
    },
    buttons: {
        downloadTemplate: ".btn-download-template",
        bulkApprove: "#btn-bulk-approve",
        allApprove: "#btn-all-approve",
    },
};

const getGlobalVariable = (variableName, defaultValue = null) => {
    const value = window[variableName];
    if (value === undefined || value === null) {
        console.warn(
            `Global variable '${variableName}' is not defined, using default:`,
            defaultValue,
        );
        return defaultValue;
    }
    return value;
};

const initializeTrainingTables = () => {
    const activePanel = document.querySelector(".u-tabs__panel:not(.hidden)");
    if (!activePanel) return;

    activePanel
        .querySelectorAll(TRAINING_CONFIG.tables.selector)
        .forEach((table) => {
            initGetDataTable(`#${table.id}`, {
                unitId: window.currentUnitId,
            });
        });
};

const initializeTabs = () => {
    const container = document.querySelector("#training-tabs");
    if (!container) return;

    const buttons = container.querySelectorAll(".u-tabs__item");
    const panels = document.querySelectorAll(".u-tabs__panel");

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const target = btn.dataset.tab;
            if (!target) return;

            buttons.forEach((b) => {
                b.classList.remove(
                    "border-blue-600",
                    "text-slate-900",
                    "u-font-semibold",
                );
                b.classList.add("border-transparent", "text-slate-400");
            });

            btn.classList.remove("border-transparent", "text-slate-400");
            btn.classList.add(
                "border-blue-600",
                "text-slate-900",
                "u-font-semibold",
            );

            // Toggle Panel
            panels.forEach((p) => {
                p.classList.toggle("hidden", p.id !== `tab-${target}`);
            });

            setTimeout(() => {
                const panel = document.getElementById(`tab-${target}`);
                if (!panel) return;

                panel
                    .querySelectorAll(TRAINING_CONFIG.tables.selector)
                    .forEach((table) => {
                        initGetDataTable(`#${table.id}`, {
                            unitId: window.currentUnitId,
                        });
                    });
            }, 50);
        });
    });
};

function initModalFeatures() {
    const modalHandlers = [
        {
            id: "#input-training-modal",
            init: initInputHandler,
        },
        {
            id: "#lna-import-modal",
            init: initDragDropUpload,
        },
        {
            id: "#lna-input-modal",
            init: initInputLnaHandler,
        },
        {
            id: "#lna-pengajuan-modal",
            init: initPengajuanLnaHandler,
        },
    ];

    modalHandlers.forEach(({ id, init }) => {
        const modal = document.querySelector(id);
        if (!modal) return;

        if (modal.dataset.initialized) return;

        init(modal); 
        modal.dataset.initialized = "true";
    });
}

const initializeButtonHandlers = () => {
    // Download Template
    if (document.querySelector(TRAINING_CONFIG.buttons.downloadTemplate)) {
        console.log("Initializing download template handler");
        initDownloadTemplateHandler();
    }
};

const initializeGlobalEventHandlers = () => {
    // Global modal handlers
    document.addEventListener("click", (e) => {
        // Open modal
        if (e.target.matches("[data-modal-open]")) {
            const id = e.target.getAttribute("data-modal-open");
            toggleModal(id, true);
            return;
        }

        // Close by button
        if (e.target.matches("[data-modal-close]")) {
            const id = e.target.getAttribute("data-modal-close");
            toggleModal(id, false);
            return;
        }

        // Click outside
        document.querySelectorAll(".u-modal").forEach((modal) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
            return;
        });
    });

    // Add global error handler
    window.addEventListener("error", (event) => {
        console.error("Global error:", event.error);
    });
};

const toggleModal = (modalId, show) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle("hidden", !show);
    }
};

const validateEnvironment = () => {
    const requiredGlobals = ["currentUserRole", "userUnitId"];
    const missingGlobals = requiredGlobals.filter((global) => !window[global]);

    if (missingGlobals.length > 0) {
        console.warn("Missing global variables:", missingGlobals);
        return false;
    }

    return true;
};

const initializeTrainingPage = () => {
    try {
        const currentUserRole = getGlobalVariable("currentUserRole");

        if (!currentUserRole) {
            console.warn(
                "window.currentUserRole is not defined. Tables must have data-role attribute.",
            );
        }
        // Initialize core components in order
        initializeTabs();
        initializeTrainingTables();
        initModalFeatures();
        initializeButtonHandlers();
        initializeGlobalEventHandlers();

        console.log("Training page initialization completed successfully");
    } catch (error) {
        console.error("Error during training page initialization:", error);
    }
};

// Public API
export const TrainingPage = {
    init: initializeTrainingPage,
    config: TRAINING_CONFIG,
    utils: {
        toggleModal,
        validateEnvironment,
    },
};

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    console.log("Training page initialized");
    TrainingPage.init();
});

// Optional: Export for manual initialization
export default TrainingPage;
