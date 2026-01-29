/**
 * Contract Modals Module
 * Handles all modal interactions for contract management
 * 
 * RELOAD STRATEGY (for scalability & maintainability):
 * =====================================================
 * 1. GLOBAL REFRESH (window.location.reload):
 *    - Used for: Sign, Approve (workflows with camera/GPS/signature)
 *    - Why: Prevents memory leaks, ensures clean state, no dangling resources
 *    - Trade-off: Slower but more reliable for complex operations
 * 
 * 2. DATATABLE RELOAD (ajax.reload):
 *    - Used for: Create, Edit, Delete, Reject (simple CRUD)
 *    - Why: Faster UX, maintains scroll/filter state, no page flash
 *    - Trade-off: Requires careful cleanup but safe for simple operations
 * 
 * This hybrid approach balances UX speed with code reliability.
 */

import { select, selectAll, hide, show, showBlock, money, terbilang, safeJSON, addDays, bindCalc, handleLocationAutofill } from './utils.js';
import { initMap, maps } from './map.js';
import { openModal, closeModal } from '../../../utils/modal.js';
import { showAlert, showSuccess, showError, showConfirm, showDeleteConfirm, showLoading, closeAlert } from '../../../utils/alert.js';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

/**
 * Create Modal Handler
 * Manages contract creation with recruitment request integration
 */
export const initCreateModal = () => {
    const btnCreate = select('#btnOpenCreate');
    const formCreate = select('#createContractForm');
    
    if (!btnCreate || !formCreate) return;

    bindCalc(formCreate);
    
    // Intercept form submit for AJAX
    formCreate.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitBtn = e.submitter || formCreate.querySelector('[type="submit"]:focus');
        const isDraft = submitBtn?.value === 'draft';
        const btnText = submitBtn?.innerHTML || 'Submit';
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            }
            
            const formData = new FormData(formCreate);
            // Add submit_action from button value
            if (submitBtn && submitBtn.name) {
                formData.append(submitBtn.name, submitBtn.value);
            }
            
            // Show loading alert saat submit
            showLoading('Menyimpan dokumen...');
            
            const response = await fetch(formCreate.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json().catch(() => ({}));
            
            closeAlert(); // Close loading
            
            if (response.ok && (result.success ?? true)) {
                showSuccess(isDraft ? 'Draft berhasil disimpan' : 'Dokumen berhasil dibuat');
                closeModal('createContractModal');
                formCreate.reset();
                // Reload table di background
                if (window.contractsTable) {
                    window.contractsTable.ajax.reload(null, false);
                }
            } else {
                throw new Error(result.message || 'Gagal menyimpan dokumen');
            }
        } catch (error) {
            closeAlert(); // Close any loading
            showError(error.message || 'Terjadi kesalahan saat menyimpan');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnText;
            }
        }
    });

    // DOM elements
    const famSel = select('#createFamilySelect');
    const subSel = select('#createSubtypeSelect');
    const srcSel = select('#createSourceSelect');
    const filterUnit = select('#filterSourceUnit');
    const appSel = select('#createApplicantSelect');
    const rrSel = select('#createRecruitmentRequestSelect');
    const inpType = select('#createTypeInput');
    const inpMode = select('#createModeInput');
    const secSubtype = select('#createSubtypeWrap');
    const secMain = select('#createMainSection');
    const secPkwtSpk = select('#sectionPkwtSpk');
    const secPb = select('#sectionPb');
    const secRemun = select('#sectionRemun');
    const secNew = select('[data-mode-section="new"]');
    const secExist = select('[data-mode-section="existing"]');
    const prevTicket = select('#prevTicket');

    let existingSource = null;
    let selectedRecruitmentData = null;

    // Load recruitment requests
    const loadRecruitmentRequests = async (unitId = null) => {
        if (!rrSel) return;
        try {
            const currentValue = rrSel.value;
            const url = new URL(window.contractsBaseUrl + '/api/recruitment-requests', window.location.origin);
            if (unitId) url.searchParams.set('unit_id', unitId);
            
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await res.json();
            
            rrSel.innerHTML = '<option value="">-- Tanpa Izin Prinsip / Pilih Manual --</option>';
            data.forEach(rr => {
                const opt = document.createElement('option');
                opt.value = rr.id;
                opt.textContent = `${rr.ticket_number} - ${rr.title} (${rr.position_text})`;
                opt.dataset.json = JSON.stringify(rr);
                rrSel.appendChild(opt);
            });
            
            if (currentValue && Array.from(rrSel.options).some(opt => opt.value === currentValue)) {
                rrSel.value = currentValue;
            }
        } catch (e) {
            console.error('Failed to load recruitment requests:', e);
        }
    };

    // Load applicants from RR
    const loadApplicantsFromRR = async (rrId) => {
        if (!appSel || !rrId) return;
        try {
            const url = `${window.contractsBaseUrl}/api/recruitment-requests/${rrId}/applicants`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const applicants = await res.json();
            
            appSel.innerHTML = '<option value="">-- Pilih Pelamar dari Izin Prinsip --</option>';
            applicants.forEach(app => {
                const opt = document.createElement('option');
                opt.value = app.id;
                opt.dataset.personId = app.person_id || '';
                opt.dataset.fullname = app.name;
                opt.dataset.pos = app.position_name;
                opt.dataset.unit = selectedRecruitmentData?.unit_name || '';
                opt.dataset.unitId = selectedRecruitmentData?.unit_id || '';
                opt.textContent = `${app.name} - ${app.position_name}`;
                appSel.appendChild(opt);
            });
        } catch (e) {
            console.error('Failed to load applicants:', e);
        }
    };

    // Auto-fill from RR
    const autoFillFromRR = (rrData) => {
        if (!rrData) return;

        // Unit
        const unitSelect = select('#createUnitSelectNew');
        if (unitSelect && rrData.unit_id) {
            unitSelect.value = rrData.unit_id;
            unitSelect.dispatchEvent(new Event('change'));
        }

        // Position
        const positionInput = select('input[name="position_name"]');
        const positionIdInput = select('#createPositionIdInput');
        if (positionInput && rrData.position_text) positionInput.value = rrData.position_text;
        if (positionIdInput && rrData.position_id) positionIdInput.value = rrData.position_id;

        // Employment type
        const empTypeSelect = select('#createEmpType');
        if (empTypeSelect && rrData.employment_type) {
            let mappedType = rrData.employment_type;
            if (mappedType === 'Organik') mappedType = 'Kontrak Organik';
            else if (mappedType === 'Project Based') mappedType = 'Kontrak-Project Based';
            
            if (mappedType !== 'Tetap' && !mappedType.includes('Alih Daya')) {
                if (Array.from(empTypeSelect.options).some(opt => opt.value === mappedType)) {
                    empTypeSelect.value = mappedType;
                }
            }
        }

        // Dates
        const startDateInput = select('input[name="start_date"]');
        const endDateInput = select('input[name="end_date"]');
        if (startDateInput && rrData.start_date) startDateInput.value = rrData.start_date;
        if (endDateInput && rrData.end_date) endDateInput.value = rrData.end_date;

        // Location
        const locationInput = select('#createLocation');
        if (locationInput && rrData.location) locationInput.value = rrData.location;

        // Remuneration
        const salaryInput = select('input[name="salary_amount"]');
        if (salaryInput && rrData.salary) {
            const cleanSalary = rrData.salary.toString().replace(/\D/g, '');
            salaryInput.value = cleanSalary;
            salaryInput.dispatchEvent(new Event('input'));
        }

        // Allowances (correct mapping)
        const lunchInput = select('input[name="lunch_allowance_daily"]');
        if (lunchInput && rrData.allowanceL) {
            lunchInput.value = rrData.allowanceL.toString().replace(/\D/g, '');
            lunchInput.dispatchEvent(new Event('input'));
        }

        const posAllowInput = select('input[name="allowance_position_amount"]');
        if (posAllowInput && rrData.allowanceP) {
            posAllowInput.value = rrData.allowanceP.toString().replace(/\D/g, '');
            posAllowInput.dispatchEvent(new Event('input'));
        }

        const commAllowInput = select('input[name="allowance_communication_amount"]');
        if (commAllowInput && rrData.allowanceK) {
            commAllowInput.value = rrData.allowanceK.toString().replace(/\D/g, '');
            commAllowInput.dispatchEvent(new Event('input'));
        }

        const specialAllowInput = select('input[name="allowance_special_amount"]');
        if (specialAllowInput && rrData.allowanceJ) {
            specialAllowInput.value = rrData.allowanceJ.toString().replace(/\D/g, '');
            specialAllowInput.dispatchEvent(new Event('input'));
        }

        // Benefits description
        const benefitsDesc = select('input[name="other_benefits_desc"]');
        if (benefitsDesc) {
            let descParts = [];
            if (rrData.bpjs_kes) descParts.push(`BPJS Kesehatan: Rp ${money(rrData.bpjs_kes)}`);
            if (rrData.bpjs_tk) descParts.push(`BPJS Ketenagakerjaan: Rp ${money(rrData.bpjs_tk)}`);
            if (rrData.pph21) descParts.push(`PPh21: Rp ${money(rrData.pph21)}`);
            if (rrData.thr) descParts.push(`THR: Rp ${money(rrData.thr)}`);
            if (descParts.length > 0) benefitsDesc.value = descParts.join(' | ');
        }
    };

    // Update preview card
    const updatePreviewCard = () => {
        const prevPosField = select('#prevPosField');
        const prevDateSection = select('#prevDateSection');
        const contractType = inpType.value;
        const isNewContract = (contractType === 'SPK' || contractType === 'PKWT_BARU');
        const isExtendOrTerminate = (contractType === 'PKWT_PERPANJANGAN' || contractType === 'PB_PENGAKHIRAN');
        
        if (prevPosField) prevPosField.style.display = isNewContract ? 'none' : 'block';
        if (prevDateSection) prevDateSection.style.display = isExtendOrTerminate ? 'block' : 'none';
    };

    // Toggle section inputs
    const toggleInputs = (container, enable) => {
        if (!container) return;
        container.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !enable);
    };

    // Apply auto-fill for existing contract
    const applyAutoFill = () => {
        const type = inpType.value;
        if (!existingSource) return;

        const nextDay = addDays(existingSource.end, 1);
        if (type === 'PB_PENGAKHIRAN') {
            const inpPbEnd = select('#createPbEnd');
            if (inpPbEnd) inpPbEnd.value = nextDay;
        } else if (type === 'PKWT_PERPANJANGAN') {
            const inpStart = select('#createStartDate');
            if (inpStart) inpStart.value = nextDay;
        }

        const inpPos = select('#createPosName');
        const inpEmpType = select('#createEmpType');
        const inpUnitExisting = select('#createUnitSelectExisting');
        
        if (inpPos) inpPos.value = existingSource.pos || '';
        if (inpEmpType && existingSource.empType) inpEmpType.value = existingSource.empType;
        if (inpUnitExisting) {
            inpUnitExisting.value = existingSource.unitId || '';
            inpUnitExisting.dispatchEvent(new Event('change'));
        }
    };

    // Update UI based on contract type
    const updateUI = () => {
        const mode = inpMode.value;
        const isNew = (mode === 'new');
        const type = inpType.value;
        
        showBlock(secMain);

        if (isNew) {
            showBlock(secNew);
            hide(secExist);
            toggleInputs(secNew, true);
            toggleInputs(secExist, false);
            const unitNewWrap = select('#createUnitNewWrap');
            if (type === 'SPK') hide(unitNewWrap);
            else showBlock(unitNewWrap);
        } else {
            hide(secNew);
            showBlock(secExist);
            toggleInputs(secNew, false);
            toggleInputs(secExist, true);
            const lbl = select('#labelSourceExisting');
            if (lbl) lbl.textContent = (type === 'PB_PENGAKHIRAN') ? 'Pilih Kontrak yang Diakhiri' : 'Pilih Kontrak Dasar';
            const unitExistWrap = select('#unitWrapperForExisting');
            if (type === 'PB_PENGAKHIRAN') hide(unitExistWrap);
            else showBlock(unitExistWrap);
        }

        const isPb = (type === 'PB_PENGAKHIRAN');
        if (isPb) {
            hide(secPkwtSpk);
            hide(secRemun);
            showBlock(secPb);
        } else {
            showBlock(secPkwtSpk);
            showBlock(secRemun);
            hide(secPb);
        }

        const isPKWT = type && type.includes('PKWT');
        const locSection = select('#createLocationSection');
        if (locSection) {
            if (isPKWT) showBlock(locSection);
            else hide(locSection);
        }

        applyAutoFill();
    };

    // Reset UI
    const resetCreateUI = () => {
        try {
            formCreate.reset();
            if (famSel) famSel.value = "";
            if (subSel) subSel.value = "";
            if (srcSel) srcSel.value = "";
            if (appSel) appSel.value = "";
            if (rrSel) rrSel.value = "";
            existingSource = null;
            selectedRecruitmentData = null;
            hide(secSubtype);
            hide(secMain);
            hide(secPkwtSpk);
            hide(secPb);
            hide(secRemun);
            hide(secNew);
            hide(secExist);
            const preview = select('#createPersonPreview');
            if (preview) hide(preview);
            if (prevTicket) prevTicket.textContent = '';
            const prevPhoto = select('#prevPhoto');
            const prevPhotoPlaceholder = select('#prevPhotoPlaceholder');
            const prevNik = select('#prevNik');
            if (prevPhoto) prevPhoto.style.display = 'none';
            if (prevPhotoPlaceholder) prevPhotoPlaceholder.style.display = 'flex';
            if (prevNik) prevNik.style.display = 'none';
            handleLocationAutofill();
        } catch (e) {
            console.error(e);
        }
    };

    // Event: Open create modal
    btnCreate.addEventListener('click', (e) => {
        e.preventDefault();
        resetCreateUI();
        openModal('createContractModal');
        const currentUnitId = window.currentUserUnit;
        loadRecruitmentRequests(currentUnitId);
    });

    // Event: RR selection
    if (rrSel) {
        rrSel.addEventListener('change', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const rrId = rrSel.value;
            
            if (rrId) {
                try {
                    const url = `${window.contractsBaseUrl}/api/recruitment-requests/${rrId}/detail`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
                    const data = await res.json();
                    selectedRecruitmentData = data;
                    
                    if (prevTicket && data.ticket_number) {
                        prevTicket.textContent = `Ticket: ${data.ticket_number}`;
                    }
                    
                    await loadApplicantsFromRR(rrId);
                } catch (e) {
                    console.error('Failed to load RR:', e);
                }
            } else {
                selectedRecruitmentData = null;
                if (prevTicket) prevTicket.textContent = '';
            }
        });
    }

    // Event: Unit change (reload RR)
    const unitSelectNew = select('#createUnitSelectNew');
    if (unitSelectNew && rrSel) {
        unitSelectNew.addEventListener('change', () => {
            const unitId = unitSelectNew.value;
            if (unitId) loadRecruitmentRequests(unitId);
        });
    }

    // Event: Family select
    if (famSel) {
        famSel.addEventListener('change', () => {
            const val = famSel.value;
            existingSource = null;
            hide(secMain);
            hide(secSubtype);
            const preview = select('#createPersonPreview');
            if (preview) hide(preview);
            if (appSel) appSel.value = '';
            
            if (!val) return;
            
            if (val === 'PKWT') {
                showBlock(secSubtype);
                inpType.value = '';
                inpMode.value = '';
            } else {
                const opt = famSel.options[famSel.selectedIndex];
                inpType.value = (val === 'SPK') ? 'SPK' : ((val === 'PB') ? 'PB_PENGAKHIRAN' : '');
                inpMode.value = opt.dataset.mode || '';
                updateUI();
                updatePreviewCard();
            }
        });
    }

    // Event: Subtype select
    if (subSel) {
        subSel.addEventListener('change', () => {
            const val = subSel.value;
            const preview = select('#createPersonPreview');
            if (preview) hide(preview);
            if (appSel) appSel.value = '';
            if (srcSel) srcSel.value = '';
            existingSource = null;
            
            if (!val) {
                hide(secMain);
                return;
            }
            
            const opt = subSel.options[subSel.selectedIndex];
            inpType.value = val;
            inpMode.value = opt.dataset.mode;
            updateUI();
            updatePreviewCard();
        });
    }

    // Event: Applicant select
    if (appSel) {
        appSel.addEventListener('change', async () => {
            const o = appSel.options[appSel.selectedIndex];
            const hidPerson = select('#createPersonIdInput');
            const hidEmp = select('#createEmployeeIdInput');
            
            if (appSel.value) {
                hidPerson.value = o.dataset.personId || '';
                hidEmp.value = '';
                
                // Auto-fill from RR when applicant selected
                if (selectedRecruitmentData) {
                    autoFillFromRR(selectedRecruitmentData);
                }
                
                // Update preview
                const applicantName = o.dataset.fullname || '-';
                const applicantPos = o.dataset.pos || '-';
                const applicantUnit = selectedRecruitmentData ? selectedRecruitmentData.unit_name : (o.dataset.unit || '-');
                
                select('#prevName').textContent = applicantName;
                select('#prevPos').textContent = applicantPos;
                select('#prevUnit').textContent = applicantUnit;
                
                updatePreviewCard();
                
                // Handle photo and NIK for new contracts
                const contractType = inpType.value;
                const isNewContract = (contractType === 'SPK' || contractType === 'PKWT_BARU');
                const prevNik = select('#prevNik');
                
                if (isNewContract && o.dataset.personId) {
                    try {
                        const personRes = await fetch(`${window.contractsBaseUrl}/api/persons/${o.dataset.personId}`, {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });
                        
                        if (personRes.ok) {
                            const personData = await personRes.json();
                            if (personData.nik) {
                                prevNik.textContent = `NIK: ${personData.nik}`;
                                prevNik.style.display = 'block';
                            }
                            
                            const prevPhoto = select('#prevPhoto');
                            const prevPhotoPlaceholder = select('#prevPhotoPlaceholder');
                            if (personData.photo_path) {
                                prevPhoto.src = `/storage/${personData.photo_path}`;
                                prevPhoto.style.display = 'block';
                                prevPhotoPlaceholder.style.display = 'none';
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch person data:', e);
                    }
                }
                
                // Handle period display
                const prevDateSection = select('#prevDateSection');
                const prevDateLabel = select('#prevDateLabel');
                const prevDate = select('#prevDate');
                
                if (contractType === 'PKWT_PERPANJANGAN' || contractType === 'PB_PENGAKHIRAN') {
                    if (existingSource && existingSource.end) {
                        prevDateLabel.textContent = 'Periode Lama';
                        prevDate.textContent = `Berakhir: ${existingSource.endHuman}`;
                        prevDateSection.style.display = 'block';
                    }
                } else if (selectedRecruitmentData && selectedRecruitmentData.start_date && selectedRecruitmentData.end_date) {
                    const startDate = new Date(selectedRecruitmentData.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const endDate = new Date(selectedRecruitmentData.end_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    prevDateLabel.textContent = 'Periode Kontrak';
                    prevDate.textContent = `${startDate} - ${endDate}`;
                    prevDateSection.style.display = 'block';
                }
                
                showBlock(select('#createPersonPreview'));
            } else {
                hide(select('#createPersonPreview'));
            }
        });
    }

    // Event: Source contract filter and select (existing contracts)
    if (filterUnit && srcSel) {
        filterUnit.addEventListener('change', () => {
            const uId = filterUnit.value;
            [...srcSel.options].forEach(o => {
                if (!o.value) return;
                if (!uId || o.dataset.unitId == uId) {
                    o.hidden = false;
                    o.disabled = false;
                } else {
                    o.hidden = true;
                    o.disabled = true;
                }
            });
            srcSel.value = "";
            existingSource = null;
            hide(select('#createPersonPreview'));
        });

        srcSel.addEventListener('change', () => {
            const o = srcSel.options[srcSel.selectedIndex];
            const hidSrc = select('#createSourceIdInput');
            const hidPerson = select('#createPersonIdInput');
            const hidEmp = select('#createEmployeeIdInput');
            
            if (!srcSel.value) {
                existingSource = null;
                hide(select('#createPersonPreview'));
                hidSrc.value = '';
                hidPerson.value = '';
                hidEmp.value = '';
                return;
            }
            
            existingSource = {
                id: srcSel.value,
                unitId: o.dataset.unitId,
                unitName: o.dataset.unitName,
                personId: o.dataset.personId,
                employeeId: o.dataset.employeeId,
                person: o.dataset.person,
                pos: o.dataset.pos,
                start: o.dataset.start,
                end: o.dataset.end,
                endHuman: o.dataset.endHuman,
                nik: o.dataset.nik,
                empType: o.dataset.empType
            };
            
            hidSrc.value = existingSource.id;
            hidPerson.value = existingSource.personId;
            hidEmp.value = existingSource.employeeId;
            
            const uExist = select('#createUnitSelectExisting');
            if (uExist) {
                uExist.value = existingSource.unitId;
                uExist.dispatchEvent(new Event('change'));
            }
            
            // Update preview
            select('#prevName').textContent = existingSource.person || '-';
            select('#prevPos').textContent = existingSource.pos || '-';
            select('#prevUnit').textContent = existingSource.unitName || '-';
            select('#prevNik').textContent = existingSource.nik || '-';
            
            const prevDateSection = select('#prevDateSection');
            const prevDateLabel = select('#prevDateLabel');
            const prevDate = select('#prevDate');
            prevDateLabel.textContent = 'Periode Lama';
            prevDate.textContent = 'Berakhir: ' + existingSource.endHuman;
            prevDateSection.style.display = 'block';
            
            if (prevTicket) prevTicket.textContent = '';
            showBlock(select('#createPersonPreview'));
            updatePreviewCard();
            applyAutoFill();
        });
    }
};

/**
 * Edit Modal Handler
 */
export const initEditModal = () => {
    $(document).on('click', '.js-btn-edit', async function(e) {
        e.preventDefault();
        const btnEdit = this;
        
        // Buka modal dulu (instant)
        openModal('editContractModal');
        
        // Tampilkan loading overlay di modal body
        const modal = select('#editContractModal');
        const modalBody = modal?.querySelector('.u-modal__body');
        if (modalBody) {
            // Reset scroll position
            modalBody.scrollTop = 0;
            modalBody.scrollLeft = 0;
            
            // Tambahkan loading
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'modal-loading-overlay';
            loadingDiv.innerHTML = `
                <div class="spinner-container">
                    <div class="u-dt-liquid-spinner"><div class="drop"></div><div class="drop"></div><div class="drop"></div></div>
                    <div class="spinner-text">Memuat data kontrak...</div>
                </div>
            `;
            modalBody.appendChild(loadingDiv);
        }
        
        try {
            const res = await fetch(btnEdit.dataset.showUrl, { 
                headers: { 'Accept': 'application/json' } 
            }).then(r => r.json());
            
            // Hapus loading
            const loadingDiv = modalBody?.querySelector('.modal-loading-overlay');
            if (loadingDiv) loadingDiv.remove();
            
            if (!res.success) throw new Error(res.message);
            
            const d = res.data;
            const m = safeJSON(d.remuneration_json);
            const form = select('#editContractForm');
            
            bindCalc(form);
            form.action = btnEdit.dataset.updateUrl;
            
            // Set form fields
            select('#editTypeInput').value = d.contract_type;
            select('#editSourceIdInput').value = d.parent_contract_id || '';
            select('#editEmployeeId').value = d.employee_id || '';
            select('#editPersonId').value = d.person_id || '';
            select('#editApplicantId').value = d.applicant_id || '';
            select('#editDisplayPerson').textContent = d.person_name;
            select('#editDisplayType').textContent = d.contract_type_label;
            select('#editPos').value = d.position_name || '';
            select('#editRemarks').value = d.remarks || '';
            
            // Handle location for PKWT
            const isPKWT = d.contract_type.includes('PKWT');
            const editLocSection = select('#editLocationSection');
            if (editLocSection) {
                if (isPKWT) {
                    showBlock(editLocSection);
                    select('#editLocation').value = m.work_location || '';
                } else {
                    hide(editLocSection);
                }
            }
            
            // Unit selection
            if (select('#editUnitSelect')) {
                select('#editUnitSelect').value = d.unit_id;
            } else if (select('#editUnitIdHidden')) {
                select('#editUnitIdHidden').value = d.unit_id;
                select('#editUnitDisplay').value = d.unit?.name || '';
            }
            
            // Handle PB vs PKWT/SPK sections
            const isPb = (d.contract_type === 'PB_PENGAKHIRAN');
            if (isPb) {
                hide(select('#editSectionPkwtSpk'));
                showBlock(select('#editSectionPb'));
                select('#editPbEnd').value = m.pb_effective_end || '';
                const el = select('#editPbComp');
                el.value = money(m.pb_compensation_amount);
                el.dispatchEvent(new Event('input'));
            } else {
                showBlock(select('#editSectionPkwtSpk'));
                hide(select('#editSectionPb'));
                select('#editStart').value = d.start_date_raw || '';
                select('#editEnd').value = d.end_date_raw || '';
                
                const setM = (sel, val) => {
                    const el = select(sel);
                    if (el) {
                        el.value = money(val);
                        el.dispatchEvent(new Event('input'));
                    }
                };
                
                setM('#editSalary', m.salary_amount);
                setM('#editLunch', m.lunch_allowance_daily);
                setM('#editAP', m.allowance_position_amount);
                setM('#editAC', m.allowance_communication_amount);
                setM('#editAS', m.allowance_special_amount);
                setM('#editAO', m.allowance_other_amount);
                setM('#editTravelStay', m.travel_allowance_stay || 150000);
                setM('#editTravelNonStay', m.travel_allowance_non_stay || 75000);
                
                select('#editOB').value = m.other_benefits_desc || '';
                select('#editWorkDays').value = m.work_days || 'Senin s/d hari Jumat';
                select('#editWorkHours').value = m.work_hours || 'Jam 07.30 WIB s/d 16.30 WIB';
                select('#editBreakHours').value = m.break_hours || 'Jam 12.00 WIB s/d 13.00 WIB';
            }
            
            // Handle unit transfer for PKWT_PERPANJANGAN
            const boxNew = select('#editNewUnitWrapper');
            if (d.contract_type === 'PKWT_PERPANJANGAN') {
                showBlock(boxNew);
                if (m.new_unit_id) select('#editNewUnitId').value = m.new_unit_id;
            } else {
                hide(boxNew);
            }
            
            // Modal sudah dibuka di awal, tidak perlu openModal lagi
        } catch (err) {
            console.error(err);
            const loadingDiv = modalBody?.querySelector('.modal-loading-overlay');
            if (loadingDiv) loadingDiv.remove();
            closeModal('editContractModal');
            showAlert({
                icon: 'error',
                title: 'Gagal!',
                text: err.message || 'Gagal memuat data dokumen'
            });
        }
    });
    
    // Intercept edit form submit
    const formEdit = select('#editContractForm');
    if (formEdit) {
        formEdit.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = e.submitter || formEdit.querySelector('[type="submit"]:focus');
            const isDraft = submitBtn?.value === 'draft';
            const btnText = submitBtn?.innerHTML || 'Submit';
            
            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                }
                
                const formData = new FormData(formEdit);
                // Add submit_action from button value
                if (submitBtn && submitBtn.name) {
                    formData.append(submitBtn.name, submitBtn.value);
                }
                
                // Show loading alert
                showLoading('Menyimpan perubahan...');
                
                const response = await fetch(formEdit.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const result = await response.json().catch(() => ({}));
                
                closeAlert(); // Close loading
                
                if (response.ok && (result.success ?? true)) {
                    showSuccess(isDraft ? 'Draft berhasil disimpan' : 'Dokumen berhasil diperbarui');
                    closeModal('editContractModal');
                    // Reload table di background tanpa wait
                    setTimeout(() => {
                        if (window.contractsTable) {
                            window.contractsTable.ajax.reload(null, false);
                        }
                    }, 100);
                } else {
                    throw new Error(result.message || 'Gagal menyimpan dokumen');
                }
            } catch (error) {
                closeAlert(); // Close any loading
                showError(error.message || 'Terjadi kesalahan saat menyimpan');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = btnText;
                }
            }
        });
    }
};

/**
 * Detail Modal Handler
 */
export const initDetailModal = () => {
    $(document).on('click', '.js-btn-detail', async function(e) {
        e.preventDefault();
        const btnDet = this;
        
        // Buka modal dulu (instant)
        openModal('detailContractModal');
        
        // Tampilkan loading overlay di modal body
        const modal = select('#detailContractModal');
        const modalBody = modal?.querySelector('.u-modal__body');
        if (modalBody) {
            // Reset scroll position
            modalBody.scrollTop = 0;
            modalBody.scrollLeft = 0;
            
            // Tambahkan loading
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'modal-loading-overlay';
            loadingDiv.innerHTML = `
                <div class="spinner-container">
                    <div class="u-dt-liquid-spinner"><div class="drop"></div><div class="drop"></div><div class="drop"></div></div>
                    <div class="spinner-text">Memuat detail kontrak...</div>
                </div>
            `;
            modalBody.appendChild(loadingDiv);
        }
        
        try {
            const res = await fetch(btnDet.dataset.showUrl, { 
                headers: { 'Accept': 'application/json' } 
            }).then(r => r.json());
            
            // Hapus loading
            const loadingDiv = modalBody?.querySelector('.modal-loading-overlay');
            if (loadingDiv) loadingDiv.remove();
            
            if (!res.success) throw new Error(res.message);
            
            const d = res.data;
            const m = safeJSON(d.remuneration_json);
            const isPb = (d.contract_type === 'PB_PENGAKHIRAN');
            
            const setText = (id, val) => {
                const el = select(id);
                if (el) el.textContent = val;
            };
            
            // Basic info
            setText('#detNo', d.contract_no || '-');
            setText('#detType', d.contract_type_label || d.contract_type || '-');
            setText('#detStatus', d.status || '-');
            setText('#detUnit', d.unit?.name || '-');
            setText('#detName', d.person_name || '-');
            setText('#detNik', d.ui_employee_id || '-');
            setText('#detNikReal', d.ui_nik_ktp || '-');
            setText('#detPos', d.position_name || '-');
            setText('#detEmpType', d.employment_type || '-');
            setText('#detTicket', d.ticket_number || '-');
            
            // Rejection box
            const detRejectBox = select('#detRejectBox');
            const rejNote = (d.rejection_note || '').toString().trim();
            if ((d.status === 'draft' || d.status === 'rejected') && rejNote) {
                if (detRejectBox) {
                    showBlock(detRejectBox);
                    setText('#detRejectNote', rejNote);
                }
            } else {
                hide(detRejectBox);
            }
            
            // Location for PKWT
            const detLocRow = select('#detLocationRow');
            if (detLocRow) {
                if (d.contract_type?.includes('PKWT')) {
                    showBlock(detLocRow);
                    setText('#detLocation', m.work_location || '-');
                } else {
                    hide(detLocRow);
                }
            }
            
            // Tracker
            if (d.tracker) {
                const h = d.tracker.head;
                const c = d.tracker.candidate;
                
                setText('#nameHead', h.name);
                setText('#posHead', h.position || 'Kepala Unit');
                setText('#dateHead', h.date);
                
                const bHead = select('#badgeHead');
                if (bHead) {
                    bHead.textContent = h.status;
                    bHead.className = `u-badge ${h.css}`;
                }
                
                const iHead = select('#iconHead');
                if (iHead) {
                    const approved = h.status === 'Signed' || h.status === 'Approved';
                    const rejected = h.status === 'Rejected';
                    iHead.className = `u-avatar u-avatar--md ${approved ? 'u-bg-success-light u-text-success' : (rejected ? 'u-bg-danger-light u-text-danger' : 'u-bg-light u-text-muted')}`;
                    iHead.innerHTML = approved ? '<i class="fas fa-check"></i>' : (rejected ? '<i class="fas fa-times"></i>' : '<i class="fas fa-user-tie"></i>');
                }
                
                setText('#nameCand', c.name);
                setText('#dateCand', c.date);
                if (d.target_role_label) setText('#labelCand', d.target_role_label);
                
                const bCand = select('#badgeCand');
                if (bCand) {
                    bCand.textContent = c.status;
                    bCand.className = `u-badge ${c.css}`;
                }
                
                const iCand = select('#iconCand');
                if (iCand) {
                    iCand.className = `u-avatar u-avatar--md ${c.status === 'Signed' ? 'u-bg-success-light u-text-success' : 'u-bg-light u-text-muted'}`;
                    iCand.innerHTML = c.status === 'Signed' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-user"></i>';
                }
            }
            
            // Geolocation and maps
            const geo = d.geolocation || {};
            const mapSec = select('#detMapSection');
            const wHead = select('#wrapperMapHead');
            const wCand = select('#wrapperMapCand');
            
            const iHead = select('#img-head');
            const niHead = select('#no-img-head');
            const iCand = select('#img-cand');
            const niCand = select('#no-img-cand');
            
            if (iHead) iHead.style.display = 'none';
            if (niHead) niHead.style.display = 'none';
            if (iCand) iCand.style.display = 'none';
            if (niCand) niCand.style.display = 'none';
            
            if (geo.head || geo.candidate) showBlock(mapSec);
            else hide(mapSec);
            
            if (geo.head) {
                showBlock(wHead);
                setText('#ts-head', `Signed: ${geo.head.ts}`);
                initMap('map-head', geo.head.lat, geo.head.lng, geo.head.acc || 0, { 
                    initialZoom: 19, 
                    circleVisible: true 
                });
                if (geo.head.image_url) {
                    if (iHead) {
                        iHead.src = geo.head.image_url;
                        showBlock(iHead);
                    }
                } else {
                    showBlock(niHead);
                }
            } else {
                hide(wHead);
            }
            
            if (geo.candidate) {
                showBlock(wCand);
                setText('#ts-cand', `Signed: ${geo.candidate.ts}`);
                initMap('map-cand', geo.candidate.lat, geo.candidate.lng, geo.candidate.acc || 0, { 
                    initialZoom: 19, 
                    circleVisible: true 
                });
                if (geo.candidate.image_url) {
                    if (iCand) {
                        iCand.src = geo.candidate.image_url;
                        showBlock(iCand);
                    }
                } else {
                    showBlock(niCand);
                }
            } else {
                hide(wCand);
            }
            
            // Remuneration or PB compensation
            if (isPb) {
                hide(select('#detRemunBox'));
                hide(select('#detPeriodRow'));
                showBlock(select('#detPbBox'));
                setText('#detPbEff', m.pb_effective_end || '-');
                setText('#detPbVal', 'Rp ' + money(m.pb_compensation_amount));
                setText('#detPbValW', (m.pb_compensation_amount_words || '').toString());
            } else {
                showBlock(select('#detRemunBox'));
                showBlock(select('#detPeriodRow'));
                hide(select('#detPbBox'));
                setText('#detPeriod', `${d.start_date || '-'} s/d ${d.end_date || '-'}`);
                setText('#detSalary', 'Rp ' + money(m.salary_amount));
                setText('#detLunch', 'Rp ' + money(m.lunch_allowance_daily));
                setText('#detWorkDays', m.work_days || '-');
                setText('#detWorkHours', m.work_hours || '-');
                
                const allws = [];
                if (m.allowance_position_amount) allws.push(['Tunjangan Jabatan', m.allowance_position_amount]);
                if (m.allowance_communication_amount) allws.push(['Tunjangan Kinerja', m.allowance_communication_amount]);
                if (m.allowance_special_amount) allws.push(['Tunjangan Project', m.allowance_special_amount]);
                if (m.allowance_other_amount) allws.push(['Tunjangan Lainnya', m.allowance_other_amount]);
                
                const elAllw = select('#detAllowances');
                if (elAllw) {
                    elAllw.innerHTML = allws.length > 0 
                        ? allws.map(x => `<div class="u-flex u-justify-between u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">${x[0]}</span><strong class="u-text-sm u-font-semibold">Rp ${money(x[1])}</strong></div>`).join('')
                        : '<div class="u-text-sm u-muted u-text-center u-py-md">Tidak ada tunjangan</div>';
                }
            }
            
            // Unit transfer
            const boxNew = select('#detNewUnitBox');
            const prevId = (m.prev_unit_id ?? '').toString();
            const newId = (m.new_unit_id ?? '').toString();
            if (d.contract_type === 'PKWT_PERPANJANGAN' && prevId && newId && prevId !== newId) {
                showBlock(boxNew);
                setText('#detNewUnit', m.new_unit_name || '-');
            } else {
                hide(boxNew);
            }
            
            // Action buttons
            const bPrev = select('#btnPreviewDoc');
            if (d.doc_url) {
                show(bPrev);
                bPrev.style.display = 'inline-flex';
                bPrev.href = d.doc_url;
            } else {
                hide(bPrev);
            }
            
            const bApp = select('#btnApprove');
            if (d.can_approve) {
                show(bApp);
                bApp.onclick = () => window.handleSign(d.approve_url);
            } else {
                hide(bApp);
            }
            
            const bSign = select('#btnSign');
            if (d.can_sign) {
                show(bSign);
                bSign.onclick = () => window.handleSign(d.sign_url);
            } else {
                hide(bSign);
            }
            
            const bRej = select('#btnReject');
            if (d.can_approve && d.reject_url) {
                show(bRej);
                bRej.onclick = () => window.openReject(d.reject_url, `${d.contract_no || '-'} • ${d.person_name || '-'}`);
            } else {
                hide(bRej);
            }
            
            // Approval logs
            const logSection = select('#detLogSection');
            const logList = select('#detLogList');
            if (d.can_see_logs && d.approval_logs) {
                showBlock(logSection);
                const logs = d.approval_logs.map(log => {
                    let icon = '<i class="fas fa-check"></i>';
                    let bgClass = 'u-bg-success-light';
                    let textClass = 'u-text-success';
                    
                    if (log.status === 'rejected') {
                        icon = '<i class="fas fa-times"></i>';
                        bgClass = 'u-bg-danger-light';
                        textClass = 'u-text-danger';
                    } else if (log.status === 'pending') {
                        icon = '<i class="fas fa-clock"></i>';
                        bgClass = 'u-bg-light';
                        textClass = 'u-text-muted';
                    }
                    
                    return `<div class="u-flex u-gap-md u-mb-md u-items-start">
                        <div class="u-avatar u-avatar--sm ${textClass}" style="background: var(--surface-2); border: 1px solid var(--border-color); flex-shrink: 0;">${icon}</div>
                        <div class="u-flex-1" style="min-width: 0;">
                            <div class="u-flex u-justify-between u-items-start u-w-full">
                                <div class="u-pr-sm"><div class="u-font-semibold u-text-sm">${log.name}</div><div class="u-text-xs u-muted">${log.role}</div></div>
                                <div class="u-text-xs u-muted u-flex-shrink-0" style="margin-left: auto !important; white-space: nowrap; text-align: right;">${log.time_ago}</div>
                            </div>
                            <div class="u-text-sm u-p-sm u-rounded ${bgClass} ${textClass} u-mt-xs"><strong>${log.status.toUpperCase()}</strong>${log.note ? ': ' + log.note : ''}<div class="u-text-xs u-mt-xxs u-muted">${log.date_formatted}</div></div>
                        </div>
                    </div>`;
                }).join('');
                
                const createdLog = `<div class="u-flex u-gap-md u-mb-md u-items-start">
                    <div class="u-avatar u-avatar--sm u-text-brand" style="background: var(--surface-2); border: 1px solid var(--border-color); flex-shrink: 0;"><i class="fas fa-plus"></i></div>
                    <div class="u-flex-1" style="min-width: 0;">
                        <div class="u-flex u-justify-between u-items-start u-w-full">
                            <div class="u-pr-sm"><div class="u-font-semibold u-text-sm">${d.creator_name || 'System'}</div><div class="u-text-xs u-muted">Document Created</div></div>
                            <div class="u-text-xs u-muted u-flex-shrink-0" style="margin-left: auto !important; white-space: nowrap; text-align: right;">${d.created_at_human || ''}</div>
                        </div>
                        <div class="u-text-sm u-p-sm u-rounded u-bg-light u-text-muted u-mt-xs"><strong>CREATED</strong><div class="u-text-xs u-mt-xxs u-muted">${d.created_at_formatted || ''}</div></div>
                    </div>
                </div>`;
                
                logList.innerHTML = logs + createdLog;
            } else {
                hide(logSection);
            }
            
            // Modal sudah dibuka di awal, tidak perlu openModal lagi
            
            // Invalidate maps after data loaded
            setTimeout(() => {
                ['map-head', 'map-cand'].forEach(id => {
                    if (maps[id] && typeof maps[id].invalidateSize === 'function') {
                        try {
                            maps[id].invalidateSize(true);
                        } catch (e) {
                            console.error('Map invalidate error:', e);
                        }
                    }
                });
            }, 250);
        } catch (err) {
            console.error(err);
            const loadingDiv = modalBody?.querySelector('.modal-loading-overlay');
            if (loadingDiv) loadingDiv.remove();
            closeModal('detailContractModal');
            showAlert({
                icon: 'error',
                title: 'Gagal!',
                text: err.message || 'Gagal memuat data dokumen'
            });
        }
    });
};

/**
 * Reject Modal Handler
 */
export const initRejectModal = () => {
    let rejectCtx = { url: '', meta: '' };
    
    // Global function to open reject modal
    window.openReject = (url, meta) => {
        rejectCtx.url = url || '';
        rejectCtx.meta = meta || '';
        
        const metaEl = select('#rejectMeta');
        const noteEl = select('#rejectNote');
        const btn = select('#btnSubmitReject');
        
        if (metaEl) metaEl.textContent = rejectCtx.meta || '-';
        if (noteEl) noteEl.value = '';
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Tolak Dokumen';
        }
        
        openModal('rejectModal');
        setTimeout(() => {
            if (noteEl) noteEl.focus();
        }, 80);
    };
    
    // Form submit handler
    const rejectForm = select('#rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const noteEl = select('#rejectNote');
            const btn = select('#btnSubmitReject');
            const note = (noteEl?.value || '').trim();
            
            if (!rejectCtx.url) {
                showError('URL tidak ditemukan', 'Gagal');
                return;
            }
            
            if (note.length < 5) {
                showError('Alasan penolakan wajib diisi minimal 5 karakter', 'Validasi Gagal');
                noteEl?.focus();
                return;
            }
            
            btn.disabled = true;
            
            // Show loading alert
            showLoading('Memproses reject...');
            
            try {
                const r = await fetch(rejectCtx.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ rejection_note: note })
                });
                
                const j = await r.json().catch(() => ({}));
                
                closeAlert(); // Close loading
                
                if (r.ok && (j.success ?? true)) {
                    showSuccess('Dokumen berhasil ditolak');
                    closeModal('rejectModal');
                    closeModal('detailContractModal');
                    
                    // Reload table di background tanpa wait
                    setTimeout(() => {
                        if (window.contractsTable) {
                            window.contractsTable.ajax.reload(null, false);
                        }
                    }, 100);
                    
                    // Fix scroll lock after double modal close
                    setTimeout(() => {
                        document.body.style.overflow = '';
                        document.body.classList.remove('overflow-hidden', 'modal-open');
                    }, 350);
                } else {
                    throw new Error(j.message || 'Gagal menolak dokumen');
                }
            } catch (err) {
                closeAlert(); // Close any loading
                showError(err.message || 'Terjadi kesalahan saat menolak dokumen');
                btn.disabled = false;
            }
        });
    }
};

/**
 * Sign Modal Handler
 * Handles signature, camera, and GPS verification
 */
export const initSignModal = () => {
    // Global function to handle sign
    window.handleSign = (url) => {
        const m = select('#signModal');
        const f = select('#signForm');
        const cvs = select('#signCanvas');
        const vid = select('#cameraStream');
        const camSec = select('#cameraSection');
        const btnSubmit = select('#btnSubmitSign');
        const geoStat = select('#geoStatus');
        const btnCap = select('#btnCapture');
        const btnRet = select('#btnRetake');
        const snapPrev = select('#snapshotPreview');
        const mapSignDiv = select('#map-sign');
        
        let captured = false;
        let streamObj = null;
        let hasSigned = false;
        let isDown = false;
        let watchId = null; // Declare at outer scope
        
        // Store event listeners untuk cleanup
        const eventListeners = {
            mouseup: null,
            touchend: null,
            scroll: null,
            resize: null
        };
        
        // Reset form
        f.reset();
        select('[name="signature_image"]').value = '';
        select('[name="geo_lat"]').value = '';
        select('[name="geo_lng"]').value = '';
        select('[name="snapshot_image"]').value = '';
        captured = false;
        snapPrev.style.display = 'none';
        vid.style.display = 'block';
        hide(btnRet);
        if (btnCap) {
            showBlock(btnCap);
            btnCap.disabled = false;
        }
        if (mapSignDiv) {
            mapSignDiv.style.display = 'none';
            mapSignDiv.innerHTML = '';
        }
        btnSubmit.disabled = true;
        
        openModal('signModal');
        
        // Setup canvas
        setTimeout(() => {
            cvs.width = cvs.offsetWidth;
            cvs.height = 200;
            const ctx = cvs.getContext('2d');
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            ctx.clearRect(0, 0, cvs.width, cvs.height);
        }, 150);
        
        // Geolocation
        const getGeo = () => {
            const geoStat = select('#geoStatus');
            const geoProg = select('#geoProgress');
            const geoIcon = select('#geoIcon');
            const mapWrap = select('#map-sign-wrapper');
            const mapSignDiv = select('#map-sign');
            const btnForce = select('#btnForceLoc');
            
            // watchId already declared at outer scope
            let quickWatchId = null;
            let firstLock = false;
            let hasQuickLock = false;
            let positionHistory = [];
            
            if (btnForce) hide(btnForce);
            select('[name="geo_lat"]').value = '';
            
            if (!window.isSecureContext && location.hostname !== 'localhost') {
                geoStat.innerHTML = '<span class="u-text-danger">HTTPS required</span>';
                return;
            }
            
            geoStat.innerHTML = '<span class="u-text-info"><i class="fas fa-spinner fa-spin"></i> Ultra fast acquiring...</span>';
            geoIcon.className = 'fas fa-satellite-dish u-text-info';
            if (mapWrap) mapWrap.style.display = 'block';
            if (mapSignDiv) mapSignDiv.style.display = 'block';
            
            const updateLocation = (pos, isHighPrecision = false) => {
                if (!pos?.coords || pos.coords.latitude === 0 || pos.coords.longitude === 0) return;
                
                const { latitude: lat, longitude: lng, accuracy: acc } = pos.coords;
                select('[name="geo_lat"]').value = lat;
                select('[name="geo_lng"]').value = lng;
                select('[name="geo_accuracy"]').value = acc;
                
                const tierThresholds = {
                    10: [100, 'high'],
                    25: [90, 'high'],
                    50: [75, 'med'],
                    100: [50, 'low'],
                    200: [30, 'low']
                };
                
                let [pct, cls] = [15, 'low'];
                for (const [threshold, [p, c]] of Object.entries(tierThresholds)) {
                    if (acc <= threshold) {
                        [pct, cls] = [p, c];
                        break;
                    }
                }
                
                geoProg.style.width = `${pct}%`;
                geoProg.className = `geo-precision-fill ${cls}`;
                
                if (!firstLock) {
                    firstLock = true;
                    hasQuickLock = true;
                    
                    const accTiers = [
                        [10, '🎯 Excellent'],
                        [25, '✅ High'],
                        [50, '👍 Good'],
                        [100, '⚠️ Fair'],
                        [300, '⚠️ Low'],
                        ['⚠️ Very Low']
                    ];
                    const accText = (accTiers.find(([t]) => acc <= t) || accTiers[5])[1];
                    const prefix = isHighPrecision ? '📍' : '⚡';
                    
                    geoStat.innerHTML = `<span class="${acc <= 150 ? 'u-text-success' : 'u-text-warning'}">${prefix} ${accText} ±${Math.round(acc)}m${isHighPrecision ? '' : ' • Refining...'}</span>`;
                    geoIcon.className = `fas fa-location-dot ${acc <= 150 ? 'u-text-success' : 'u-text-warning'}`;
                    
                    initMap('map-sign', lat, lng, acc, {
                        isRealTime: true,
                        initialZoom: acc <= 50 ? 19 : (acc <= 150 ? 17 : 15)
                    });
                    
                    if (btnForce) hide(btnForce);
                    checkReady();
                } else {
                    if (maps['map-sign']?._updatePosition) {
                        maps['map-sign']._updatePosition(lat, lng, acc);
                    }
                    const prefix = isHighPrecision ? '📍' : '⚡';
                    geoStat.innerHTML = `<span class="${acc <= 150 ? 'u-text-success' : 'u-text-warning'}">${prefix} ${acc <= 50 ? 'Precision' : 'Tracking'} ±${Math.round(acc)}m${isHighPrecision ? '' : ' • Improving...'}</span>`;
                    geoIcon.className = `fas fa-location-dot ${acc <= 150 ? 'u-text-success' : 'u-text-warning'}`;
                }
            };
            
            const onSuccess = (pos, isHighPrecision = false) => {
                const acc = pos.coords.accuracy;
                positionHistory.push({
                    lat: pos.coords.latitude,
                    lon: pos.coords.longitude,
                    acc: acc,
                    ts: Date.now()
                });
                if (positionHistory.length > 3) positionHistory.shift();
                updateLocation(pos, isHighPrecision);
            };
            
            const onError = (err) => {
                const errorMessages = { 1: 'Access denied', 2: 'GPS unavailable', 3: 'Timeout' };
                if (!hasQuickLock) {
                    geoStat.innerHTML = `<span class="u-text-danger">⚠️ ${errorMessages[err.code] || 'Location error'}</span>`;
                    geoProg.className = 'geo-precision-fill low';
                    geoProg.style.width = '0%';
                    geoIcon.className = 'fas fa-exclamation-circle u-text-danger';
                    if (btnForce) show(btnForce);
                }
            };
            
            const preciseOpts = { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 };
            watchId = navigator.geolocation.watchPosition((pos) => onSuccess(pos, true), onError, preciseOpts);
            
            setTimeout(() => {
                const quickOpts = { enableHighAccuracy: false, timeout: 8000, maximumAge: 5000 };
                quickWatchId = navigator.geolocation.watchPosition(
                    (pos) => onSuccess(pos, false),
                    (err) => console.log('Quick:', err.message),
                    quickOpts
                );
            }, 100);
            
            setTimeout(() => {
                if (quickWatchId) navigator.geolocation.clearWatch(quickWatchId);
            }, 10000);
        };
        
        getGeo();
        
        // Camera
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && (window.isSecureContext || location.hostname === 'localhost')) {
            showBlock(camSec);
            select('#cameraPlaceholder').hidden = false;
            
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
                .then((stream) => {
                    streamObj = stream;
                    vid.srcObject = stream;
                    select('#cameraPlaceholder').hidden = true;
                })
                .catch(() => {
                    select('#cameraPlaceholder').textContent = "Izin Kamera Ditolak / Tidak Ada";
                });
        } else {
            hide(camSec);
        }
        
        // Capture button
        if (btnCap) {
            btnCap.onclick = () => {
                if (vid.videoWidth > 0) {
                    const snapCanvas = document.createElement('canvas');
                    snapCanvas.width = 640;
                    snapCanvas.height = 480;
                    snapCanvas.getContext('2d').drawImage(vid, 0, 0, snapCanvas.width, snapCanvas.height);
                    const dataUrl = snapCanvas.toDataURL('image/jpeg', 0.8);
                    select('[name="snapshot_image"]').value = dataUrl;
                    snapPrev.src = dataUrl;
                    vid.style.display = 'none';
                    snapPrev.style.display = 'block';
                    hide(btnCap);
                    showBlock(btnRet);
                    captured = true;
                    checkReady();
                }
            };
        }
        
        // Retake button
        if (btnRet) {
            btnRet.onclick = () => {
                select('[name="snapshot_image"]').value = '';
                snapPrev.style.display = 'none';
                vid.style.display = 'block';
                showBlock(btnCap);
                hide(btnRet);
                captured = false;
                checkReady();
            };
        }
        
        // Signature canvas
        const ctx = cvs.getContext('2d');
        let rect = cvs.getBoundingClientRect();
        
        const updateRect = () => {
            rect = cvs.getBoundingClientRect();
        };
        
        // Store and add event listeners
        eventListeners.scroll = updateRect;
        eventListeners.resize = updateRect;
        window.addEventListener('scroll', updateRect);
        window.addEventListener('resize', updateRect);
        
        const getXY = (e) => {
            const cX = e.touches ? e.touches[0].clientX : e.clientX;
            const cY = e.touches ? e.touches[0].clientY : e.clientY;
            rect = cvs.getBoundingClientRect();
            return { x: cX - rect.left, y: cY - rect.top };
        };
        
        const drawMove = (e) => {
            if (!isDown) return;
            e.preventDefault();
            const p = getXY(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        };
        
        cvs.onmousedown = (e) => {
            isDown = true;
            ctx.beginPath();
            const p = getXY(e);
            ctx.moveTo(p.x, p.y);
        };
        cvs.onmousemove = drawMove;
        cvs.ontouchstart = (e) => {
            isDown = true;
            ctx.beginPath();
            const p = getXY(e);
            ctx.moveTo(p.x, p.y);
        };
        cvs.ontouchmove = drawMove;
        
        // Store event listeners untuk cleanup
        eventListeners.mouseup = () => {
            if (isDown) {
                isDown = false;
                hasSigned = true;
                checkReady();
            }
        };
        eventListeners.touchend = () => {
            if (isDown) {
                isDown = false;
                hasSigned = true;
                checkReady();
            }
        };
        
        window.addEventListener('mouseup', eventListeners.mouseup);
        window.addEventListener('touchend', eventListeners.touchend);
        
        select('#clearSign').onclick = () => {
            ctx.clearRect(0, 0, cvs.width, cvs.height);
            hasSigned = false;
            btnSubmit.disabled = true;
        };
        
        const checkReady = () => {
            const locOk = select('[name="geo_lat"]').value !== '';
            const camOk = !camSec.classList.contains('is-hidden') ? captured : true;
            btnSubmit.disabled = !(locOk && hasSigned && camOk);
        };
        
        // Form submit - prevent double submission strictly
        let isSubmitting = false;
        
        f.onsubmit = async (e) => {
            e.preventDefault();
            e.stopImmediatePropagation(); // Stop all other handlers
            
            // Prevent double submit - STRICT CHECK
            if (isSubmitting) {
                console.warn('[SIGN] Submit already in progress, blocked');
                return false;
            }
            
            // Lock form immediately
            isSubmitting = true;
            f.style.pointerEvents = 'none'; // Disable ALL interactions first
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            
            select('[name="signature_image"]').value = cvs.toDataURL('image/png');
            const fd = new FormData(f);
            
            if (streamObj) streamObj.getTracks().forEach(track => track.stop());
            
            try {
                
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: fd
                });
                
                const j = await r.json().catch(() => ({}));
                
                if (r.ok && (j.success ?? true)) {
                    // Cleanup ALL resources and event listeners
                    if (streamObj) {
                        streamObj.getTracks().forEach(track => track.stop());
                        streamObj = null;
                    }
                    if (window.navigator.geolocation && watchId) {
                        window.navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    
                    // Remove all event listeners
                    if (eventListeners.scroll) {
                        window.removeEventListener('scroll', eventListeners.scroll);
                    }
                    if (eventListeners.resize) {
                        window.removeEventListener('resize', eventListeners.resize);
                    }
                    if (eventListeners.mouseup) {
                        window.removeEventListener('mouseup', eventListeners.mouseup);
                    }
                    if (eventListeners.touchend) {
                        window.removeEventListener('touchend', eventListeners.touchend);
                    }
                    
                    closeAlert(); // Close loading
                    
                    showSuccess('Dokumen berhasil ditandatangani').then(() => {
                        // STRATEGY: Use global refresh for complex workflows (camera/GPS/signature)
                        // - Prevents memory leaks from event listeners
                        // - Ensures clean state (no dangling streams/watchers)
                        // - More reliable than manual cleanup + DT reload
                        // Trade-off: Slower but safer for approval/sign operations
                        window.location.reload();
                    });
                } else {
                    throw new Error(j.message || 'Gagal memproses tanda tangan');
                }
            } catch (err) {
                closeAlert(); // Close loading
                showError(err.message || 'Terjadi kesalahan saat memproses tanda tangan');
                // Reset all states on error
                isSubmitting = false;
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'Simpan & Tanda Tangan';
                f.style.pointerEvents = ''; // Re-enable form
            }
        };
        
        // Close modal cleanup - CRITICAL: cleanup all resources and event listeners
        let isCleaned = false; // Flag to prevent double cleanup
        const cleanupModal = () => {
            if (isCleaned) return; // Already cleaned
            isCleaned = true;
            
            // Stop camera stream
            if (streamObj) {
                streamObj.getTracks().forEach(track => track.stop());
                streamObj = null;
            }
            
            // Stop geolocation watch
            if (window.navigator.geolocation && watchId) {
                window.navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            
            // Remove all event listeners
            if (eventListeners.scroll) {
                window.removeEventListener('scroll', eventListeners.scroll);
            }
            if (eventListeners.resize) {
                window.removeEventListener('resize', eventListeners.resize);
            }
            if (eventListeners.mouseup) {
                window.removeEventListener('mouseup', eventListeners.mouseup);
            }
            if (eventListeners.touchend) {
                window.removeEventListener('touchend', eventListeners.touchend);
            }
        };
        
        // Only attach cleanup once to close buttons
        const closeButtons = m.querySelectorAll('.js-close-modal');
        closeButtons.forEach(b => {
            const existingHandler = b._signCleanup;
            if (existingHandler) {
                b.removeEventListener('click', existingHandler);
            }
            b._signCleanup = cleanupModal;
            b.addEventListener('click', cleanupModal, { once: true }); // Auto-remove after first call
        });
    };
};
