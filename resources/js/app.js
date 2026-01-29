import './bootstrap';
import $ from 'jquery';

window.$ = window.jQuery = $;

import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-responsive-dt';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';

import { initDataTables, bindExternalSearch, reloadTable } from './plugins/datatables';
import { initModalHandler, openModal, closeModal } from './utils/modal';
import { 
    showAlert, 
    showSuccess, 
    showError, 
    showWarning, 
    showInfo, 
    showConfirm, 
    showDeleteConfirm,
    showLoading,
    closeAlert,
    showToast
} from './utils/alert';
import { 
    showModalLoading, 
    hideModalLoading, 
    isModalLoading, 
    updateModalLoadingMessage 
} from './utils/modal-loading';

window.initDataTables = initDataTables;
window.bindExternalSearch = bindExternalSearch;
window.reloadTable = reloadTable;
window.openModal = openModal;
window.closeModal = closeModal;
window.initModalHandler = initModalHandler;

// Global alert functions (already exposed in alert.js but ensure they're available)
window.showAlert = showAlert;
window.showSuccess = showSuccess;
window.showError = showError;
window.showWarning = showWarning;
window.showInfo = showInfo;
window.showConfirm = showConfirm;
window.showDeleteConfirm = showDeleteConfirm;
window.showLoading = showLoading;
window.closeAlert = closeAlert;
window.showToast = showToast;

// Global modal loading functions
window.showModalLoading = showModalLoading;
window.hideModalLoading = hideModalLoading;
window.isModalLoading = isModalLoading;
window.updateModalLoadingMessage = updateModalLoadingMessage;
window.showDeleteConfirm = showDeleteConfirm;
window.showLoading = showLoading;
window.closeAlert = closeAlert;
window.showToast = showToast;

initModalHandler();