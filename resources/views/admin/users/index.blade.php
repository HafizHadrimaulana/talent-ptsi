@extends('layouts.app')
@section('title','User Management')

@section('content')
@php
  $rolesOptions = $roles ?? collect();
  $unitsOptions = $units ?? collect();
@endphp

<div class="u-card u-card--glass u-hover-lift u-mb-xl"
     data-store-url="{{ route('admin.users.store') }}"
     data-update-url-base="{{ url('/admin/settings/access/users') }}">
  
  <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
    <div>
        <h2 class="u-title">User Management</h2>
        <p class="u-text-sm u-muted">Directory & Access Control</p>
    </div>
    <div class="u-flex u-items-center u-gap-sm u-stack-mobile">
        @can('users.create')
            <button type="button" class="u-btn u-btn--brand" onclick="window.openCreateModal()">
                <i class="fas fa-plus u-mr-xs"></i> Add User
            </button>
        @endcan
    </div>
  </div>

  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm"><i class='fas fa-check-circle u-success-icon'></i><span>{{ session('ok') }}</span></div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm"><i class='fas fa-exclamation-circle u-error-icon'></i><span class="u-font-semibold">Action failed</span></div>
      <ul class="u-list">@foreach($errors->all() as $e)<li class="u-item">{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="dt-wrapper">
      <div class="u-scroll-x" data-table-url="{{ route('admin.users.index') }}">
        <table id="users-table" class="u-table" style="width:100%">
          <thead>
            <tr>
              <th>Identity</th>
              <th>Job / Unit</th>
              <th>Status</th>
              <th class="cell-actions">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
  </div>
</div>

{{-- MODALS --}}
<div id="empModal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div id="empPhoto" class="u-avatar u-avatar--lg u-avatar--brand"><span id="empInitial">?</span></div>
        <div>
          <div id="empName" class="u-title">Name</div>
          <div class="u-flex u-gap-sm u-text-sm u-muted">
            <span id="empId">ID: —</span> &bull; <span id="empCompany">Company: —</span>
          </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--icon" onclick="window.closeAllModals()"><i class='bx bx-x'></i></button>
    </div>
    
    <div class="u-modal__body">
        <div class="u-tabs-wrap">
            <div class="u-tabs" id="detailModalTabs">
                <button type="button" class="u-tab is-active" onclick="window.switchDetailTab('ov', this)">Overview</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('brevet', this)">Brevet</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('job', this)">History</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('task', this)">Taskforces</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('asg', this)">Assignments</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('edu', this)">Education</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('train', this)">Training</button>
                <button type="button" class="u-tab" onclick="window.switchDetailTab('doc', this)">Documents</button>
            </div>
        </div>
        <div class="u-panels" id="detailModalPanels">
            <div class="u-panel is-active" id="panel-ov">
                 <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div class="u-card">
                        <h4 class="u-font-semibold u-mb-md">Profile & Employment</h4>
                        <div class="u-space-y-sm" id="ov-left"></div>
                    </div>
                    <div class="u-card">
                        <h4 class="u-font-semibold u-mb-md">Contact & Location</h4>
                        <div class="u-space-y-sm" id="ov-right"></div>
                    </div>
                 </div>
            </div>
            <div class="u-panel" id="panel-brevet"><div id="brevet-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-job"><div id="job-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-task"><div id="task-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-asg"><div id="asg-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-edu"><div id="edu-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-train"><div id="train-list" class="u-list"></div></div>
            <div class="u-panel" id="panel-doc"><div id="doc-list" class="u-list"></div></div>
        </div>
    </div>
  </div>
</div>

<div id="editModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fa-solid fa-user-shield"></i></div>
        <div>
          <div id="editTitle" class="u-title">User Account</div>
          <div class="u-text-sm u-muted">Credentials & Access</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--icon" onclick="window.closeAllModals()"><i class='bx bx-x'></i></button>
    </div>

    <form id="editForm" method="post">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <input type="hidden" name="employee_id" id="f_employee_id">

      <div class="u-modal__body">
          <div class="u-tabs-wrap u-mb-md">
            <div class="u-tabs" id="editModalTabs">
                <button type="button" class="u-tab is-active" onclick="window.switchEditTab('identity', this)">Identity</button>
                <button type="button" class="u-tab" onclick="window.switchEditTab('roles', this)">Roles</button>
            </div>
          </div>

          <div class="u-panels" id="editModalPanels" style="padding:0">
              <div class="u-panel is-active" id="panel-identity">
                  <div class="u-grid-2 u-gap-md">
                      <div class="u-grid-col-span-2">
                        <label class="u-text-sm u-font-semibold">Full Name</label>
                        <input type="text" class="u-input" name="name" id="i_name" required>
                      </div>
                      <div class="u-grid-col-span-2">
                        <label class="u-text-sm u-font-semibold">Email</label>
                        <input type="email" class="u-input" name="email" id="i_email" required>
                      </div>
                      <div class="u-grid-col-span-2">
                        <label class="u-text-sm u-font-semibold">Unit Scope</label>
                        <select class="u-input" name="unit_id" id="i_unit">
                          <option value="">Select Unit...</option>
                          @foreach($unitsOptions as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="u-grid-col-span-2">
                          <label class="u-text-sm u-font-semibold">Password <span class="u-muted u-font-normal">(Optional)</span></label>
                          <input type="password" class="u-input" name="password" id="f_password">
                      </div>
                  </div>
              </div>

              <div class="u-panel" id="panel-roles">
                 <div class="u-flex u-items-center u-justify-between u-mb-sm">
                    <label class="u-text-sm u-font-semibold">Assign Roles</label>
                    <div class="u-flex u-gap-sm">
                       <button type="button" class="u-btn u-btn--xs u-btn--ghost" onclick="window.toggleAllRoles(true)">All</button>
                       <button type="button" class="u-btn u-btn--xs u-btn--ghost" onclick="window.toggleAllRoles(false)">None</button>
                    </div>
                 </div>
                 <div class="u-card u-p-sm" id="rolesChecklist" style="max-height:250px;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px">
                    @foreach($rolesOptions as $ro)
                      <label class="u-flex u-items-center u-gap-sm u-text-sm u-p-xs u-hover-lift" style="cursor:pointer">
                        <input type="checkbox" name="roles[]" value="{{ $ro->id }}" class="u-rounded">
                        <span>{{ $ro->name }}</span>
                      </label>
                    @endforeach
                 </div>
              </div>
          </div>
      </div>

      <div class="u-modal__foot">
        <button type="button" class="u-btn u-btn--ghost" onclick="window.closeAllModals()">Cancel</button>
        <button class="u-btn u-btn--brand" id="submitEdit">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const shell = document.querySelector('.u-card[data-store-url]');
    const STORE_URL = shell?.dataset.storeUrl || '';
    const UPDATE_BASE = shell?.dataset.updateUrlBase || '';

    window.closeAllModals = function() {
        document.querySelectorAll('.u-modal').forEach(m => m.hidden = true);
        document.body.classList.remove('modal-open');
    };

    window.switchDetailTab = function(name, btn) {
        document.querySelectorAll('#detailModalTabs .u-tab').forEach(t => t.classList.remove('is-active'));
        btn.classList.add('is-active');
        document.querySelectorAll('#empModal .u-panel').forEach(p => p.classList.remove('is-active'));
        const target = document.getElementById('panel-' + name);
        if(target) target.classList.add('is-active');
    };

    window.switchEditTab = function(name, btn) {
        document.querySelectorAll('#editModalTabs .u-tab').forEach(t => t.classList.remove('is-active'));
        btn.classList.add('is-active');
        document.querySelectorAll('#editModal .u-panel').forEach(p => p.classList.remove('is-active'));
        const target = document.getElementById('panel-' + name);
        if(target) target.classList.add('is-active');
    };

    window.toggleAllRoles = function(checked) {
        document.querySelectorAll('#rolesChecklist input').forEach(c => c.checked = checked);
    };

    window.openCreateModal = function() {
        const form = document.getElementById('editForm');
        form.reset();
        form.action = STORE_URL;
        form.querySelector('input[name="_method"]').value = 'POST';
        document.getElementById('editTitle').textContent = 'Create User';
        window.toggleAllRoles(false);
        const firstTab = document.querySelector('#editModalTabs .u-tab');
        if(firstTab) window.switchEditTab('identity', firstTab);
        document.getElementById('editModal').hidden = false;
        document.body.classList.add('modal-open');
    };

    window.openEditModal = function(data) {
        const form = document.getElementById('editForm');
        form.reset();
        document.getElementById('i_name').value = data.user?.name || data.full_name || '';
        document.getElementById('i_email').value = data.user?.email || data.email || '';
        document.getElementById('i_unit').value = data.user?.unit_id || '';
        document.getElementById('f_employee_id').value = data.employee_id || '';
        window.toggleAllRoles(false);
        (data.user?.roles_ids || []).forEach(id => {
            const cb = document.querySelector(`#rolesChecklist input[value="${id}"]`);
            if(cb) cb.checked = true;
        });
        if(data.user?.id) {
            form.action = `${UPDATE_BASE}/${data.user.id}`;
            form.querySelector('input[name="_method"]').value = 'PUT';
            document.getElementById('editTitle').textContent = 'Edit User';
        } else {
            form.action = STORE_URL;
            form.querySelector('input[name="_method"]').value = 'POST';
            document.getElementById('editTitle').textContent = 'Create User';
        }
        const firstTab = document.querySelector('#editModalTabs .u-tab');
        if(firstTab) window.switchEditTab('identity', firstTab);
        document.getElementById('editModal').hidden = false;
        document.body.classList.add('modal-open');
    };

    window.openDetailModal = function(d) {
        document.getElementById('empName').textContent = d.full_name;
        document.getElementById('empId').textContent = d.employee_id ? `ID: ${d.employee_id}` : 'External';
        document.getElementById('empCompany').textContent = d.company || '—';
        
        const av = document.getElementById('empPhoto');
        const init = document.getElementById('empInitial');
        if(d.photo_url){
            av.style.backgroundImage = `url(${d.photo_url})`;
            av.classList.remove('u-avatar--brand');
            init.style.display='none';
        } else {
            av.style.backgroundImage = '';
            av.classList.add('u-avatar--brand');
            init.style.display='block';
            init.textContent = (d.full_name||'?')[0];
        }

        const rowStyle = 'display:flex; justify-content:space-between; align-items:flex-start; gap:1.5rem; margin-bottom:0.75rem;';
        const lblStyle = 'flex-shrink:0; width:130px; font-size:0.875rem; color:var(--text-muted);';
        const valStyle = 'flex:1; text-align:right; font-weight:500; word-break:break-word;';

        document.getElementById('ov-left').innerHTML = `
            <div style="${rowStyle}"><span style="${lblStyle}">Job Title</span><span style="${valStyle}">${d.job_title||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Unit</span><span style="${valStyle}">${d.unit_name||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Directorate</span><span style="${valStyle}">${d.directorate||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Start Date</span><span style="${valStyle}">${_fmtDate(d.start_date)}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Status</span><span style="flex:1; text-align:right"><span class="u-badge u-badge--glass">${d.status||'—'}</span></span></div>
        `;
        document.getElementById('ov-right').innerHTML = `
            <div style="${rowStyle}"><span style="${lblStyle}">Email</span><span style="${valStyle}">${d.email||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Phone</span><span style="${valStyle}">${d.phone||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Location</span><span style="${valStyle}">${d.city||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Talent</span><span style="${valStyle}">${d.talent||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Company</span><span style="${valStyle}">${d.company||'—'}</span></div>
        `;

        const lists = ['brevet-list', 'job-list', 'task-list', 'asg-list', 'edu-list', 'train-list', 'doc-list'];
        lists.forEach(id => document.getElementById(id).innerHTML = '<div class="u-p-md u-text-center u-muted"><i class="bx bx-loader-alt bx-spin"></i> Loading...</div>');

        const firstTab = document.querySelector('#detailModalTabs .u-tab');
        if(firstTab) window.switchDetailTab('ov', firstTab);

        document.getElementById('empModal').hidden = false;
        document.body.classList.add('modal-open');

        if(d.id) {
            fetch(`${UPDATE_BASE}/${d.id}`, {headers: {'Accept': 'application/json'}})
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if(!data) throw new Error('No Data');
                    _renderList('brevet-list', data.brevet_list, (v)=>`
                        <div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${v.title || 'Brevet'}</h4><p class="u-text-xs u-muted">${v.organization || 'Organization'}</p></div><span class="u-badge u-badge--glass u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(v.start_date, v.end_date)}</span></div></div>`);
                    _renderList('job-list', data.job_histories, (j)=>`
                        <div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${j.title || 'Position'}</h4><p class="u-text-xs u-muted">${j.unit_name || j.organization || ''}</p></div><span class="u-badge u-badge--primary u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(j.start_date, j.end_date)}</span></div></div>`);
                    _renderList('task-list', data.taskforces, (t)=>`
                        <div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${t.title}</h4><p class="u-text-xs u-muted">${t.organization}</p></div><span class="u-badge u-badge--glass u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(t.start_date, t.end_date)}</span></div></div>`);
                    _renderList('asg-list', data.assignments, (a)=>`
                        <div class="u-item"><div class="u-flex u-justify-between u-items-start"><div><h4 class="u-font-semibold u-text-sm">${a.title}</h4><p class="u-text-xs u-muted">${a.description}</p></div></div></div>`);
                    _renderList('edu-list', data.educations, (e)=> {
                        let meta = {}; try { meta = typeof e.meta === 'string' ? JSON.parse(e.meta) : (e.meta || {}); } catch(_){}
                        return `<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${meta.level||e.level||'Education'} ${meta.major?'— '+meta.major:''}</h4><p class="u-text-xs u-muted">${e.organization || 'Institution'}</p></div><span class="u-badge u-badge--glass u-shrink-0">Grad: ${_year(e.start_date)}</span></div></div>`;
                    });
                    _renderList('train-list', data.trainings, (t)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${t.title}</h4><p class="u-text-xs u-muted">${t.organization||'Provider'}</p></div><span class="u-badge u-badge--primary u-shrink-0">${_fmtDate(t.start_date)}</span></div></div>`);
                    _renderList('doc-list', data.documents, (d)=>`<div class="u-item u-flex u-justify-between u-items-center"><div class="u-flex-1 u-mr-md u-min-w-0"><h4 class="u-font-semibold u-text-sm">${d.final_title || 'Untitled'}</h4><p class="u-text-xs u-muted">${d.doc_type}</p></div><div class="u-flex u-flex-col u-items-end u-gap-xs u-shrink-0">${d.meta_due_date ? `<div class="u-text-xs u-muted">Due: ${_fmtDate(d.meta_due_date)}</div>` : ''}${d.url?`<a href="${d.url}" target="_blank" class="u-btn u-btn--xs u-btn--outline">View</a>`:''}</div></div>`);
                })
                .catch(() => lists.forEach(id => document.getElementById(id).innerHTML = '<div class="u-empty u-text-xs">No data available</div>'));
        }
    };

    function _renderList(id, data, tpl){
        const el = document.getElementById(id);
        if(!data || !data.length) { el.innerHTML = '<div class="u-empty u-text-xs">No records found.</div>'; return; }
        el.innerHTML = data.map(tpl).join('');
    }
    
    function _fmtDate(s){ 
        if(!s) return '—'; 
        try{ 
            const d = new Date(s);
            if(isNaN(d.getTime())) return s;
            return d.toLocaleDateString('en-GB', {year:'numeric', month:'short', day:'numeric'});
        }catch(_){return s} 
    }
    
    function _year(s){ return s ? String(s).substring(0,4) : '—'; }
    function _yearRange(s, e) {
        let start = _fmtDate(s);
        let end = e ? _fmtDate(e) : '';
        if(s && s.endsWith('-01-01')) start = _year(s);
        if(e && e.endsWith('-12-31')) end = _year(e);
        return !end ? start : `${start} - ${end}`;
    }
});
</script>
@endsection