import './bootstrap';
import $ from 'jquery';

window.$ = window.jQuery = $;

import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-responsive-dt';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';

import { initDataTables, bindExternalSearch } from './plugins/datatables';
import { initModalHandler, openModal, closeModal } from './utils/modal';

window.initDataTables = initDataTables;
window.bindExternalSearch = bindExternalSearch;
window.openModal = openModal;
window.closeModal = closeModal;
window.initModalHandler = initModalHandler;

initModalHandler();