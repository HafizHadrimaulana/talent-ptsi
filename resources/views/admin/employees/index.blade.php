@extends('layouts.app')
@section('title', 'Employee Directory')

@section('content')
<div class="u-card u-card--glass u-mb-lg u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Employee Directory</h2>
    <!-- <form id="empSearchForm" class="u-search" style="max-width: 520px;">
      <svg class="u-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>
      <input id="empSearchInput" type="search" class="u-search__input" name="q" value="{{ $q }}" placeholder="Search everything…" />
      <button class="u-btn u-btn--brand u-btn--sm" type="submit" id="empSearchBtn">Search</button>
      <button class="u-btn u-btn--outline u-btn--sm" type="button" id="empSearchClear" title="Clear">Clear</button>
    </form> -->
  </div>

  <div class="dt-wrapper">
    <div class="u-scroll-x">
      <table id="employees-table" class="u-table u-table-mobile" data-dt>
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Job Title</th>
            <th>Unit</th>
            <th>Status</th>
            <th>Talent</th>
            <th class="cell-actions">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
          @php
            $employee_id = $r->employee_key ?? $r->employee_id ?? null;
            $full_name   = $r->full_name ?? null;
            $unit_name   = $r->unit_name ?? null;
            $job_title   = $r->job_title ?? null;
            $status      = $r->employee_status ?? null;
            $talent      = $r->talent_class_level ?? null;
            
            $basicData = [
              'id' => $r->id ?? null,
              'employee_id' => $employee_id,
              'full_name' => $full_name,
              'unit_name' => $unit_name,
              'job_title' => $job_title,
              'email' => $r->email ?? null,
              'phone' => $r->phone ?? null,
              'photo_url' => $r->person_photo ?? null,
              'status' => $status,
              'talent' => $talent,
              'directorate' => $r->directorate_name ?? null,
              'city' => $r->location_city ?? null,
              'province' => $r->location_province ?? null,
              'company' => $r->company_name ?? null,
              'start_date' => $r->latest_jobs_start_date ?? null,
            ];
          @endphp

          <tr>
            <td>{{ $employee_id ?? '—' }}</td>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <span>{{ $full_name ?? '—' }}</span>
              </div>
            </td>
            <td>{{ $job_title ?? '—' }}</td>
            <td>{{ $unit_name ?? '—' }}</td>
            <td>
              @if($status)
                <span class="u-badge u-badge--primary">{{ $status }}</span>
              @else
                —
              @endif
            </td>
            <td>
              @if($talent)
                <span class="u-badge u-badge--glass">{{ $talent }}</span>
              @else
                —
              @endif
            </td>
            <td class="cell-actions">
              <button class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                data-modal-open="empModal"
                data-employee-id="{{ $r->id }}"
                data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                Details
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Employee Modal -->
<div id="empModal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div id="empPhoto" class="u-avatar u-avatar--lg u-avatar--brand">
          <span id="empInitial">?</span>
        </div>
        <div>
          <div id="empName" class="u-title">Employee Name</div>
          <div class="u-muted u-text-sm" id="empId">ID: —</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-modal-close aria-label="Close">
        <i class='bx bx-x'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="empTabs">
          <button class="u-tab is-active" data-tab="ov">Overview</button>
          <button class="u-tab" data-tab="brevet">Brevet</button>
          <button class="u-tab" data-tab="job">Job History</button>
          <button class="u-tab u-hide-mobile" data-tab="task">Taskforces</button>
          <button class="u-tab u-hide-mobile" data-tab="asg">Assignments</button>
          <button class="u-tab" data-tab="edu">Education</button>
          <button class="u-tab" data-tab="train">Training</button>
          <button class="u-tab u-hide-tablet" data-tab="doc">Documents</button>
        </div>
      </div>

      <div class="u-panels">
        <!-- Overview Tab -->
        <div class="u-panel is-active" id="tab-ov">
          <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Basic Information</h4>
              <div class="u-space-y-sm">
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Employee ID:</span>
                  <span class="u-font-medium" id="ovId">—</span>
                </div>
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Full Name:</span>
                  <span class="u-font-medium" id="ovName">—</span>
                </div>
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Job Title:</span>
                  <span class="u-font-medium" id="ovJob">—</span>
                </div>
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Unit:</span>
                  <span class="u-font-medium" id="ovUnit">—</span>
                </div>
              </div>
            </div>
            
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Status & Employment</h4>
              <div class="u-space-y-sm">
                <div class="u-flex u-justify-between u-items-center">
                  <span class="u-text-sm u-muted">Employment Status:</span>
                  <span id="ovStatus" class="u-badge u-badge--primary">—</span>
                </div>
                <div class="u-flex u-justify-between u-items-center">
                  <span class="u-text-sm u-muted">Talent Level:</span>
                  <span id="ovTalent" class="u-badge u-badge--glass">—</span>
                </div>
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Start Date:</span>
                  <span class="u-font-medium" id="ovStartDate">—</span>
                </div>
                <div class="u-flex u-justify-between">
                  <span class="u-text-sm u-muted">Company:</span>
                  <span class="u-font-medium" id="ovCompany">—</span>
                </div>
              </div>
            </div>

            <div class="u-card u-grid-col-span-2">
              <h4 class="u-font-semibold u-mb-md">Contact Information</h4>
              <div class="u-grid-2 u-stack-mobile u-gap-md">
                <div class="u-space-y-sm">
                  <div>
                    <label class="u-text-sm u-muted u-block u-mb-xs">Email Address</label>
                    <div class="u-font-medium" id="ovEmail">—</div>
                  </div>
                  <div>
                    <label class="u-text-sm u-muted u-block u-mb-xs">Phone Number</label>
                    <div class="u-font-medium" id="ovPhone">—</div>
                  </div>
                </div>
                <div class="u-space-y-sm">
                  <div>
                    <label class="u-text-sm u-muted u-block u-mb-xs">Location</label>
                    <div class="u-font-medium">
                      <span id="ovCity">—</span>, <span id="ovProvince">—</span>
                    </div>
                  </div>
                  <div>
                    <label class="u-text-sm u-muted u-block u-mb-xs">Directorate</label>
                    <div class="u-font-medium" id="ovDirectorate">—</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Brevet Tab -->
        <div class="u-panel" id="tab-brevet">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Brevet & Certifications</h4>
              <div class="u-list" id="brevet-list">
                <div class="u-empty">
                  <i class='bx bx-award u-empty__icon'></i>
                  <p class="u-font-semibold">No brevet information available</p>
                  <p class="u-text-sm u-muted">Brevet and certification data will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Job History Tab -->
        <div class="u-panel" id="tab-job">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Job History</h4>
              <div class="u-list" id="job-list">
                <div class="u-empty">
                  <i class='bx bx-briefcase u-empty__icon'></i>
                  <p class="u-font-semibold">No job history available</p>
                  <p class="u-text-sm u-muted">Previous employment positions will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Taskforces Tab -->
        <div class="u-panel" id="tab-task">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Taskforces</h4>
              <div class="u-list" id="task-list">
                <div class="u-empty">
                  <i class='bx bx-group u-empty__icon'></i>
                  <p class="u-font-semibold">No taskforce information available</p>
                  <p class="u-text-sm u-muted">Taskforce assignments will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Assignments Tab -->
        <div class="u-panel" id="tab-asg">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Assignments</h4>
              <div class="u-list" id="asg-list">
                <div class="u-empty">
                  <i class='bx bx-task u-empty__icon'></i>
                  <p class="u-font-semibold">No assignment information available</p>
                  <p class="u-text-sm u-muted">Special assignments will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Education Tab -->
        <div class="u-panel" id="tab-edu">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Education</h4>
              <div class="u-list" id="edu-list">
                <div class="u-empty">
                  <i class='bx bx-graduation u-empty__icon'></i>
                  <p class="u-font-semibold">No education information available</p>
                  <p class="u-text-sm u-muted">Educational background will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Training Tab -->
        <div class="u-panel" id="tab-train">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Training & Development</h4>
              <div class="u-list" id="train-list">
                <div class="u-empty">
                  <i class='bx bx-certification u-empty__icon'></i>
                  <p class="u-font-semibold">No training information available</p>
                  <p class="u-text-sm u-muted">Training records will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents Tab -->
        <div class="u-panel" id="tab-doc">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Documents</h4>
              <div class="u-list" id="doc-list">
                <div class="u-empty">
                  <i class='bx bx-file u-empty__icon'></i>
                  <p class="u-font-semibold">No documents available</p>
                  <p class="u-text-sm u-muted">Employee documents will appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <button class="u-btn u-btn--ghost" id="empCloseBottom">Close</button>
    </div>
  </div>
</div>

<script>
// Employee directory specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
  // Modal functionality
  const modalHandler = {
    currentEmployeeId: null,
    
    init: function() {
      this.bindEvents();
      this.bindTabs();
    },
    
    bindEvents: function() {
      // Open modal
      document.addEventListener('click', async (e) => {
        const opener = e.target.closest('[data-modal-open="empModal"]');
        if (!opener) return;
        
        const empData = JSON.parse(opener.dataset.emp);
        this.currentEmployeeId = opener.dataset.employeeId;
        
        // Show loading state
        this.showLoadingState();
        
        // Load detailed employee data
        try {
          const detailedData = await this.fetchEmployeeDetails(this.currentEmployeeId);
          this.openEmployeeModal({...empData, ...detailedData});
        } catch (error) {
          console.error('Error fetching employee details:', error);
          this.openEmployeeModal(empData); // Fallback to basic data
        }
      });
      
      // Close modal - Fixed close button handlers
      document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('[data-modal-close], #empCloseBottom');
        if (closeBtn) {
          e.preventDefault();
          this.closeModal();
        }
      });
      
      // Backdrop click
      document.addEventListener('click', (e) => {
        if (e.target.classList.contains('u-modal')) {
          this.closeModal();
        }
      });
      
      // Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          this.closeModal();
        }
      });
    },
    
    bindTabs: function() {
      const tabs = document.querySelectorAll('#empTabs .u-tab');
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          const targetTab = tab.dataset.tab;
          
          // Update active tab
          tabs.forEach(t => t.classList.remove('is-active'));
          tab.classList.add('is-active');
          
          // Show target panel
          document.querySelectorAll('.u-panel').forEach(panel => {
            panel.classList.remove('is-active');
            if (panel.id === 'tab-' + targetTab) {
              panel.classList.add('is-active');
            }
          });
        });
      });
    },
    
    async fetchEmployeeDetails(employeeId) {
      try {
        const response = await fetch(`/admin/employees/${employeeId}`);
        if (!response.ok) throw new Error('Failed to fetch employee details');
        return await response.json();
      } catch (error) {
        console.error('Error fetching employee details:', error);
        throw error;
      }
    },
    
    openEmployeeModal: function(empData) {
      console.log('Opening modal with data:', empData); // Debug log
      
      // Update modal header
      document.getElementById('empName').textContent = empData.full_name || '—';
      document.getElementById('empId').textContent = `ID: ${empData.employee_id || '—'}`;
      
      // Update avatar
      const empPhoto = document.getElementById('empPhoto');
      const empInitial = document.getElementById('empInitial');
      
      if (empData.photo_url) {
        empPhoto.style.backgroundImage = `url(${empData.photo_url})`;
        empPhoto.classList.remove('u-avatar--brand');
        empInitial.style.display = 'none';
      } else {
        empPhoto.style.backgroundImage = '';
        empPhoto.classList.add('u-avatar--brand');
        empInitial.style.display = 'flex';
        empInitial.textContent = empData.full_name ? empData.full_name.charAt(0).toUpperCase() : '?';
      }
      
      // Update overview tab
      document.getElementById('ovId').textContent = empData.employee_id || '—';
      document.getElementById('ovName').textContent = empData.full_name || '—';
      document.getElementById('ovJob').textContent = empData.job_title || '—';
      document.getElementById('ovUnit').textContent = empData.unit_name || '—';
      this.updateBadge('ovStatus', empData.status);
      this.updateBadge('ovTalent', empData.talent);
      document.getElementById('ovStartDate').textContent = this.formatDateSimple(empData.start_date) || '—';
      document.getElementById('ovCompany').textContent = empData.company || '—';
      document.getElementById('ovEmail').textContent = empData.email || '—';
      document.getElementById('ovPhone').textContent = empData.phone || '—';
      document.getElementById('ovCity').textContent = empData.city || '—';
      document.getElementById('ovProvince').textContent = empData.province || '—';
      document.getElementById('ovDirectorate').textContent = empData.directorate || '—';
      
      // Load data for all tabs from API response
      if (empData.employee) {
        // If data comes from detailed API call
        this.loadBrevetData(empData.brevet_list || []);
        this.loadJobHistory(empData.job_histories || []);
        this.loadTaskforces(empData.taskforces || []);
        this.loadAssignments(empData.assignments || []);
        this.loadEducation(empData.educations || []);
        this.loadTraining(empData.trainings || []);
        this.loadDocuments(empData.documents || []);
      } else {
        // If only basic data is available
        this.loadBrevetData([]);
        this.loadJobHistory([]);
        this.loadTaskforces([]);
        this.loadAssignments([]);
        this.loadEducation([]);
        this.loadTraining([]);
        this.loadDocuments([]);
      }
      
      // Show modal
      document.getElementById('empModal').hidden = false;
      document.body.classList.add('modal-open');
    },
    
    showLoadingState: function() {
      // Set all tabs to loading state
      const loaders = [
        { id: 'brevet-list', icon: 'bx-award', title: 'Loading brevet data...' },
        { id: 'job-list', icon: 'bx-briefcase', title: 'Loading job history...' },
        { id: 'task-list', icon: 'bx-group', title: 'Loading taskforces...' },
        { id: 'asg-list', icon: 'bx-task', title: 'Loading assignments...' },
        { id: 'edu-list', icon: 'bx-graduation', title: 'Loading education...' },
        { id: 'train-list', icon: 'bx-certification', title: 'Loading training...' },
        { id: 'doc-list', icon: 'bx-file', title: 'Loading documents...' }
      ];
      
      loaders.forEach(loader => {
        const container = document.getElementById(loader.id);
        if (container) {
          container.innerHTML = `
            <div class="u-empty">
              <i class='bx ${loader.icon} bx-spin u-empty__icon'></i>
              <p class="u-font-semibold">${loader.title}</p>
            </div>
          `;
        }
      });
    },
    
    updateBadge: function(elementId, value) {
      const element = document.getElementById(elementId);
      if (value) {
        element.textContent = value;
        element.style.display = 'inline-flex';
      } else {
        element.textContent = '—';
        element.style.display = 'none';
      }
    },
    
    loadBrevetData: function(brevetData) {
      const container = document.getElementById('brevet-list');
      if (brevetData && brevetData.length > 0) {
        container.innerHTML = brevetData.map(item => `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-start">
              <div>
                <h4 class="u-font-semibold u-mb-xs">${item.title || 'Brevet Certification'}</h4>
                <p class="u-text-sm u-muted">${item.organization || item.institution || 'Professional Institution'}</p>
                ${item.description ? `<p class="u-text-sm u-muted u-mt-xs">${item.description}</p>` : ''}
              </div>
              <div class="u-text-right">
                ${item.start_date ? `<span class="u-badge u-badge--glass">${this.formatYearOnly(item.start_date)}</span>` : ''}
                ${item.end_date ? `<div class="u-text-xs u-muted u-mt-xs">Expired ${this.formatYearOnly(item.end_date)}</div>` : ''}
              </div>
            </div>
          </div>
        `).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-award', 'No brevet information available', 'Brevet and certification data will appear here');
      }
    },
    
    loadJobHistory: function(jobHistory) {
      const container = document.getElementById('job-list');
      if (jobHistory && jobHistory.length > 0) {
        container.innerHTML = jobHistory.map(job => {
          const startDate = this.formatDateSimple(job.start_date);
          const endDate = this.formatDateSimple(job.end_date);
          const dateRange = startDate && endDate ? `${startDate} - ${endDate}` : 
                           startDate && !endDate ? `${startDate}` :
                           !startDate && endDate ? `${endDate}` : '';
          
          return `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-start">
              <div>
                <h4 class="u-font-semibold u-mb-xs">${job.title || 'Position'}</h4>
                <p class="u-text-sm u-muted">${job.organization || job.company || 'Company'} ${job.unit_name ? `- ${job.unit_name}` : ''}</p>
                ${job.description ? `<p class="u-text-sm u-muted u-mt-xs">${job.description}</p>` : ''}
              </div>
              ${dateRange ? `
              <div class="u-text-right">
                <span class="u-badge u-badge--primary">${dateRange}</span>
              </div>
              ` : ''}
            </div>
          </div>
        `}).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-briefcase', 'No job history available', 'Previous employment positions will appear here');
      }
    },
    
    loadTaskforces: function(taskforces) {
      const container = document.getElementById('task-list');
      if (taskforces && taskforces.length > 0) {
        container.innerHTML = taskforces.map(task => {
          const startDate = this.formatYearOnly(task.start_date);
          const endDate = this.formatYearOnly(task.end_date);
          const dateRange = startDate && endDate ? `${startDate} - ${endDate}` : 
                           startDate && !endDate ? `${startDate}` :
                           !startDate && endDate ? `${endDate}` : '';
          
          return `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-start">
              <div>
                <h4 class="u-font-semibold u-mb-xs">${task.title || 'Taskforce'}</h4>
                <p class="u-text-sm u-muted">Organization: ${task.organization || 'N/A'}</p>
                ${task.description ? `<p class="u-text-sm u-muted u-mt-xs">${task.description}</p>` : ''}
              </div>
              ${dateRange ? `
              <div class="u-text-right">
                <span class="u-badge u-badge--glass">${dateRange}</span>
              </div>
              ` : ''}
            </div>
          </div>
        `}).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-group', 'No taskforce information available', 'Taskforce assignments will appear here');
      }
    },
    
    loadAssignments: function(assignments) {
      const container = document.getElementById('asg-list');
      if (assignments && assignments.length > 0) {
        container.innerHTML = assignments.map(assignment => {
          const startDate = this.formatYearOnly(assignment.start_date);
          const endDate = this.formatYearOnly(assignment.end_date);
          const dateRange = startDate && endDate ? `${startDate} - ${endDate}` : 
                           startDate && !endDate ? `${startDate}` :
                           !startDate && endDate ? `${endDate}` : '';
          
          return `
          <div class="u-item">
            <h4 class="u-font-semibold u-mb-xs">${assignment.title || 'Assignment'}</h4>
            <p class="u-text-sm u-muted u-mb-sm">${assignment.description || 'No description available'}</p>
            <div class="u-flex u-justify-between u-items-center">
              <span class="u-text-xs u-muted">Organization: ${assignment.organization || 'N/A'}</span>
              ${dateRange ? `<span class="u-badge u-badge--primary">${dateRange}</span>` : ''}
            </div>
          </div>
        `}).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-task', 'No assignment information available', 'Special assignments will appear here');
      }
    },
    
    loadEducation: function(education) {
      const container = document.getElementById('edu-list');
      if (education && education.length > 0) {
        container.innerHTML = education.map(edu => {
          // Extract meta data from portfolio_histories
          const meta = edu.meta ? (typeof edu.meta === 'string' ? JSON.parse(edu.meta) : edu.meta) : {};
          const level = meta.level || this.extractDegreeLevel(edu.title, edu.description);
          const major = meta.major || this.extractMajor(edu.description);
          const graduationYear = meta.graduation_year || this.formatYearOnly(edu.end_date) || this.formatYearOnly(edu.start_date);
          const institution = edu.organization || 'Institution';
          
          return `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-start">
              <div>
                <h4 class="u-font-semibold u-mb-xs">${level || 'Education'}</h4>
                <p class="u-text-sm u-muted">${institution}${major ? ` - ${major}` : ''}</p>
                ${edu.description ? `<p class="u-text-sm u-muted u-mt-xs">${edu.description}</p>` : ''}
                <div class="u-flex u-gap-md u-mt-xs">
                </div>
              </div>
              ${graduationYear ? `
              <div class="u-text-right">
                <span class="u-badge u-badge--glass">${graduationYear}</span>
              </div>
              ` : ''}
            </div>
          </div>
        `}).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-graduation', 'No education information available', 'Educational background will appear here');
      }
    },
    
    loadTraining: function(training) {
      const container = document.getElementById('train-list');
      if (training && training.length > 0) {
        container.innerHTML = training.map(train => {
          const year = this.formatYearOnly(train.start_date) || this.formatYearOnly(train.end_date);
          
          return `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-start">
              <div>
                <h4 class="u-font-semibold u-mb-xs">${train.title || 'Training Course'}</h4>
                <p class="u-text-sm u-muted">Provider: ${train.organization || 'Training Provider'}</p>
                ${train.description ? `<p class="u-text-sm u-muted u-mt-xs">${train.description}</p>` : ''}
              </div>
              ${year ? `
              <div class="u-text-right">
                <span class="u-badge u-badge--primary">${year}</span>
              </div>
              ` : ''}
            </div>
          </div>
        `}).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-certification', 'No training information available', 'Training records will appear here');
      }
    },
    
    loadDocuments: function(documents) {
      const container = document.getElementById('doc-list');
      if (documents && documents.length > 0) {
        container.innerHTML = documents.map(doc => `
          <div class="u-item">
            <div class="u-flex u-justify-between u-items-center">
              <div class="u-flex u-items-center u-gap-sm">
                <i class='bx bx-file text-xl text-blue-500'></i>
                <div>
                  <h4 class="u-font-semibold u-mb-xs">${doc.meta_title || doc.doc_type || 'Document'}</h4>
                  <p class="u-text-sm u-muted">Type: ${doc.doc_type || 'Document'}</p>
                  ${doc.meta_due_date ? `<p class="u-text-xs u-muted">Due: ${this.formatDateSimple(doc.meta_due_date)}</p>` : ''}
                </div>
              </div>
              <div class="u-text-right">
                ${doc.url ? `<a href="${doc.url}" target="_blank" class="u-btn u-btn--sm u-btn--outline u-mt-xs">View</a>` : ''}
              </div>
            </div>
          </div>
        `).join('');
      } else {
        container.innerHTML = this.getEmptyState('bx-file', 'No documents available', 'Employee documents will appear here');
      }
    },
    
    formatYearOnly: function(dateString) {
      if (!dateString) return null;
      
      try {
        // Extract year from date string (supports YYYY, YYYY-MM, YYYY-MM-DD)
        const yearMatch = dateString.toString().match(/^(\d{4})/);
        return yearMatch ? yearMatch[1] : null;
      } catch (e) {
        return null;
      }
    },
    
    formatDateSimple: function(dateString) {
      if (!dateString) return null;
      
      try {
        // For overview tab - show as is or extract year if it's just a year
        if (/^\d{4}$/.test(dateString)) {
          return dateString;
        }
        
        // Try to parse as date and return in simple format
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
          return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          });
        }
        
        // Return original if parsing fails
        return dateString;
      } catch (e) {
        return dateString;
      }
    },
    
    extractDegreeLevel: function(title, description) {
      if (!title && !description) return 'Pendidikan';
      
      const text = (title + ' ' + (description || '')).toLowerCase();
      
      if (text.includes('s3') || text.includes('doktor') || text.includes('doctor')) return 'Doktor';
      if (text.includes('s2') || text.includes('magister') || text.includes('master')) return 'Magister';
      if (text.includes('s1') || text.includes('sarjana') || text.includes('bachelor')) return 'Sarjana';
      if (text.includes('d3') || text.includes('diploma')) return 'Diploma';
      if (text.includes('sma') || text.includes('smk') || text.includes('slta')) return 'SMA/SMK';
      
      return title || 'Pendidikan';
    },
    
    extractMajor: function(description) {
      if (!description) return null;
      
      // Simple extraction of major/jurusan from description
      const majorKeywords = ['jurusan', 'major', 'program studi', 'prodi', 'bidang'];
      for (const keyword of majorKeywords) {
        const regex = new RegExp(`${keyword}[\\s:]?\\s*([^.,]+)`, 'i');
        const match = description.match(regex);
        if (match) return match[1].trim();
      }
      
      return null;
    },
    
    getEmptyState: function(icon, title, description) {
      return `
        <div class="u-empty">
          <i class='bx ${icon} u-empty__icon'></i>
          <p class="u-font-semibold">${title}</p>
          <p class="u-text-sm u-muted">${description}</p>
        </div>
      `;
    },
    
    closeModal: function() {
      document.getElementById('empModal').hidden = true;
      document.body.classList.remove('modal-open');
      this.currentEmployeeId = null;
    }
  };
  
  // Search functionality
  const searchHandler = {
    init: function() {
      this.bindSearch();
    },
    
    bindSearch: function() {
      const searchForm = document.getElementById('empSearchForm');
      const searchInput = document.getElementById('empSearchInput');
      const searchBtn = document.getElementById('empSearchBtn');
      const clearBtn = document.getElementById('empSearchClear');
      
      if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
          e.preventDefault();
          this.performSearch();
        });
      }
      
      if (searchInput) {
        searchInput.addEventListener('input', this.debounce(() => {
          this.performSearch();
        }, 300));
      }
      
      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          searchInput.value = '';
          this.performSearch();
        });
      }
    },
    
    performSearch: function() {
      const searchTerm = document.getElementById('empSearchInput').value.toLowerCase();
      const tableRows = document.querySelectorAll('#employees-table tbody tr');
      
      tableRows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchTerm)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    },
    
    debounce: function(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
  };
  
  // Initialize everything
  modalHandler.init();
  searchHandler.init();
});
</script>
@endsection