import { initDownloadTemplateHandler } from "./training-approval/handler/downloadTemplateHandler";
import { initGetDataTable } from "./training-approval/getData";
import { initInputHandler } from "./training-approval/handler/inputHandler";
import { initUpdateJenisPelatihanHandler } from "./training-approval/handler/updateJenisPelatihanHandler";
import { initDragDropUpload } from "./training-approval/handler/dragDropImport";
import { initModalHandler } from "../../utils/modal";
import { initInputLnaHandler } from "./training-approval/handler/inputLnaHandler";
// import { initAllApprovalHandler, initBulkApprovalHandler } from "./training-approval/handler/approveHandler";

const TRAINING_CONFIG = {
    tables: {
        selector: ".training-table",
        dataAttributes: {
            role: "data-role",
            unitId: "data-unit-id",
        },
    },
    tabs: {
        container: "#dhc-tabs",
        panelSelector: ".u-tabs__panel",
        activeClass: "is-active",
        hiddenClass: "hidden",
    },
    modals: {
        trainingInput: {
            openBtn: "#training-input-btn",
            modal: "#input-training-modal",
            closeBtn: "#training-close-modal",
        },
        trainingImport: {
            openBtn: "#training-import-btn",
            modal: "#lna-import-modal",
            closeBtn: "#lna-close-modal",
        },
        lnaImport: {
            openBtn: "#lna-import-btn",
            modal: "#lna-import-modal",
            closeBtn: "#lna-close-modal",
        },
        lnaInput: {
            openBtn: "#lna-input-btn",
            modal: "#lna-input-modal",
            closeBtn: "#lna-input-close-modal",
        }, 
    },
    buttons: {
        downloadTemplate: ".btn-download-template",
        bulkApprove: "#btn-bulk-approve",
        allApprove: "#btn-all-approve",
    },
};

const ROLES_REQUIRING_UNIT_ID = ["SDM Unit"];

const getGlobalVariable = (variableName, defaultValue = null) => {
    const value = window[variableName];
    if (value === undefined || value === null) {
        console.warn(
            `Global variable '${variableName}' is not defined, using default:`,
            defaultValue
        );
        return defaultValue;
    }
    return value;
};

const initializeTrainingTables = () => {
    const tables = document.querySelectorAll(TRAINING_CONFIG.tables.selector);

    if (tables.length === 0) {
        console.warn("No training tables found");
        return;
    }

    tables.forEach((table) => {
        const tableBody = table.querySelector("tbody");
        if (!tableBody) {
            console.warn("Training table without tbody found", table);
            return;
        }

        // Get configuration from data attributes or global variables
        const userRole =
            table.getAttribute(TRAINING_CONFIG.tables.dataAttributes.role) ||
            window.currentUserRole;
        const unitId =
            table.getAttribute(TRAINING_CONFIG.tables.dataAttributes.unitId) ||
            window.userUnitId;

        if (!userRole) {
            console.error("User role not defined for training table", table);
            return;
        }

        if (ROLES_REQUIRING_UNIT_ID.includes(userRole) && !unitId) {
            console.error(`Unit ID is required for role: ${userRole}`, table);
            tableBody.innerHTML = `<tr><td colspan="20" class="text-center text-red-500">Error: Unit ID is required for ${userRole} role</td></tr>`;
            return;
        }

        console.log(
            `Initializing training table for role: ${userRole}, unitId: ${
                unitId || "not required"
            }`
        );

        try {
            initGetDataTable(tableBody, userRole, unitId);

            // Initialize update handler for DHC role
            if (userRole === "DHC") {
                initUpdateJenisPelatihanHandler(tableBody);
            }
        } catch (error) {
            console.error("Error initializing training table:", error);
            tableBody.innerHTML = `<tr><td colspan="20" class="text-center text-red-500">Error initializing table</td></tr>`;
        }
    });
};

const initializeTabs = () => {
    const container = document.querySelector("#dhc-tabs");
    if (!container) return; // bukan DHC atau tabs belum dirender

    const buttons = container.querySelectorAll(".u-tabs__item");
    const panels = document.querySelectorAll(".u-tabs__panel");

    if (!buttons.length || !panels.length) return;

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const target = btn.dataset.tab; // 'training' atau 'lna'
            if (!target) return;

            // Toggle active di tabs__list
            buttons.forEach((b) => {
                b.classList.remove(TRAINING_CONFIG.tabs.activeClass);

                b.classList.remove(
                    "border-blue-600",
                    "text-slate-900",
                    "font-semibold"
                );

                b.classList.add(
                    "border-transparent",
                    "text-slate-500",
                );
            });
            
            btn.classList.remove(
                "border-transparent",
                "text-slate-500"
            );

            btn.classList.add(
                "border-blue-600",
                "text-slate-900",
                "font-semibold"
            );

            // Toggle panel
            panels.forEach((panel) => {
                if (panel.id === `tab-${target}`) {
                    panel.classList.add(TRAINING_CONFIG.tabs.activeClass);
                    panel.classList.remove(TRAINING_CONFIG.tabs.hiddenClass);
                } else {
                    panel.classList.remove(TRAINING_CONFIG.tabs.activeClass);
                    panel.classList.add(TRAINING_CONFIG.tabs.hiddenClass);
                }
            });
        });
    });
};

const initializeModals = () => {
    // Training Input Modal
    if (document.querySelector(TRAINING_CONFIG.modals.trainingInput.openBtn)) {
        console.log("Initializing training input modal");
        const { openBtn, modal, closeBtn } =
            TRAINING_CONFIG.modals.trainingInput;
        initModalHandler(openBtn, modal, closeBtn);
        initInputHandler(modal);
    }

    // LNA Import Modal
    if (document.querySelector(TRAINING_CONFIG.modals.lnaImport.modal)) {
        console.log("Initializing LNA import modal");
        const { openBtn, modal, closeBtn } = TRAINING_CONFIG.modals.lnaImport;
        initModalHandler(openBtn, modal, closeBtn);
        initDragDropUpload(modal);
    }

    if (document.querySelector(TRAINING_CONFIG.modals.lnaInput.modal)) {
        console.log("Initializing LNA input modal");
        const { openBtn, modal, closeBtn } = TRAINING_CONFIG.modals.lnaInput;
        initModalHandler(openBtn, modal, closeBtn);
        initInputLnaHandler(modal);
    }

    if (document.querySelector(TRAINING_CONFIG.modals.trainingImport.modal)) {
        console.log("Initializing training import modal");
        const { openBtn, modal, closeBtn } = TRAINING_CONFIG.modals.trainingImport;

        const role = document.querySelector(openBtn)?.dataset?.role || "training";
        
        initModalHandler(openBtn, modal, closeBtn);
        initDragDropUpload(modal, role);
    }
};

const initializeButtonHandlers = () => {
    // Download Template
    if (document.querySelector(TRAINING_CONFIG.buttons.downloadTemplate)) {
        console.log("Initializing download template handler");
        initDownloadTemplateHandler();
    }

    // // Bulk Approval
    // if (document.querySelector(TRAINING_CONFIG.buttons.bulkApprove)) {
    //     console.log("Initializing bulk approval handler");
    //     initBulkApprovalHandler();
    // }

    // // All Approval
    // if (document.querySelector(TRAINING_CONFIG.buttons.allApprove)) {
    //     console.log("Initializing all approval handler");
    //     initAllApprovalHandler();
    // }
};

const initializeGlobalEventHandlers = () => {
    // Global modal handlers
    document.addEventListener("click", (e) => {
        // Open modal
        if (e.target.matches("[data-modal-open]")) {
            const id = e.target.getAttribute("data-modal-open");
            toggleModal(id, true);
        }

        // Close by button
        if (e.target.matches("[data-modal-close]")) {
            const id = e.target.getAttribute("data-modal-close");
            toggleModal(id, false);
        }

        // Click outside
        document.querySelectorAll(".u-modal").forEach((modal) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
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
    console.log("Training page initialization started");
    
    try {
        const currentUserRole = getGlobalVariable("currentUserRole");

        if (!currentUserRole) {
            console.warn(
                "window.currentUserRole is not defined. Tables must have data-role attribute."
            );
        }
        // Initialize core components in order
        initializeTabs();
        initializeTrainingTables();
        initializeModals();
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
    TrainingPage.init();
});

// Optional: Export for manual initialization
export default TrainingPage;
