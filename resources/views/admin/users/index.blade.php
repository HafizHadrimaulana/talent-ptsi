@extends('layouts.app')
@section('title','User Management · Employee Directory')

@section('content')
@php
  $rolesOptions = $roles ?? collect();
  $unitsOptions = $units ?? collect();
@endphp

<div class="u-card u-card--glass u-hover-lift"
     data-store-url="{{ route('admin.users.store') }}"
     data-update-url-base="{{ url('/admin/settings/access/users') }}">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Employee Directory + User Accounts</h2>
    @can('users.create')
      <button type="button" class="u-btn u-btn--brand u-hover-lift" data-open="addUser">
        <i class="fas fa-user-plus u-mr-xs"></i> Add User
      </button>
    @endcan
  </div>

  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='fas fa-check-circle u-success-icon'></i><span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class='fas fa-exclamation-circle u-error-icon'></i>
        <span class="u-font-semibold">Please fix the errors:</span>
      </div>
      <ul class="u-list">
        @foreach($errors->all() as $e)<li class="u-item">{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="dt-wrapper">
    <div class="u-scroll-x">
      <table id="employees-table" class="u-table u-table-mobile" data-dt>
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th class="u-hide-mobile">Job Title</th>
            <th>Unit</th>
            <th>Status</th>
            <th class="u-hide-mobile">Talent</th>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
          @php
            $employee_pk = $r->employee_pk ?? null;
            $employee_id = $r->employee_id ?? $r->id_sitms ?? $r->employee_key ?? null;
            $full_name   = $r->full_name ?? null;
            $unit_name   = $r->unit_name ?? null;
            $job_title   = $r->job_title ?? null;
            $status      = $r->employee_status ?? null;
            $talent      = $r->talent_class_level ?? null;

            $assigned = ($r->user_id && isset($userRolesMap[$r->user_id])) ? $userRolesMap[$r->user_id]['ids'] : [];

            $basicData = [
              'id'            => $employee_pk,
              'employee_id'   => $employee_id,
              'full_name'     => $full_name,
              'unit_name'     => $unit_name,
              'job_title'     => $job_title,
              'email'         => $r->employee_email ?? $r->email ?? null,
              'phone'         => $r->phone ?? null,
              'photo_url'     => $r->person_photo ?? null,
              'status'        => $status,
              'talent'        => $talent,
              'directorate'   => $r->directorate_name ?? null,
              'city'          => $r->location_city ?? null,
              'province'      => $r->location_province ?? null,
              'company'       => $r->company_name ?? 'PT Surveyor Indonesia',
              'start_date'    => $r->latest_jobs_start_date ?? null,
              'user' => [
                'id'        => $r->user_id ?? null,
                'name'      => $r->user_name ?? $full_name,
                'email'     => $r->user_email ?? ($r->employee_email ?? null),
                'unit_id'   => $r->user_unit_id ?? null,
                'roles_ids' => $assigned,
              ],
            ];
          @endphp

          <tr>
            <td>{{ $employee_id ?? '—' }}</td>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <span class="u-font-medium">{{ $full_name ?? '—' }}</span>
              </div>
            </td>
            <td class="u-hide-mobile">{{ $job_title ?? '—' }}</td>
            <td>{{ $unit_name ?? '—' }}</td>
            <td>
              @if($status)
                <span class="u-badge u-badge--primary">{{ $status }}</span>
              @else
                — 
              @endif
            </td>
            <td class="u-hide-mobile">
              @if($talent)
                <span class="u-badge u-badge--glass">{{ $talent }}</span>
              @else
                — 
              @endif
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button class="u-btn u-btn--outline u-btn--sm"
                        title="{{ $r->user_id ? 'Edit Password & Roles' : 'Create Account' }}"
                        data-open="rowEdit"
                        data-employee-id="{{ $employee_pk }}"
                        data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                  <i class="fa-solid fa-user-gear u-mr-xs"></i>{{ $r->user_id ? 'Manage' : 'Create' }}
                </button>
                <button class="u-btn u-btn--sm"
                        data-modal-open="empModal"
                        data-employee-id="{{ $employee_pk }}"
                        data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                  <i class="fa-regular fa-user u-mr-xs"></i>Details
                </button>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ================= DETAILS MODAL ================= --}}
<div id="empModal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div id="empPhoto" class="u-avatar u-avatar--lg u-avatar--brand">
          <span id="empInitial">?</span>
        </div>
        <div>
          <div id="empName" class="u-title">Employee Name</div>
          <div class="u-muted u-text-sm" style="display:flex;gap:8px;flex-wrap:wrap">
            <span id="empId">ID: —</span>
            <span id="empHire" class="u-text-sm u-muted">Start: —</span>
            <span id="empCompany" class="u-text-sm u-muted">Company: —</span>
          </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-modal-close aria-label="Close"><i class='bx bx-x'></i></button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="empTabs">
          <button type="button" class="u-tab is-active" data-tab="ov">Overview</button>
          <button type="button" class="u-tab" data-tab="brevet">Brevet</button>
          <button type="button" class="u-tab" data-tab="job">Job History</button>
          <button type="button" class="u-tab u-hide-mobile" data-tab="task">Taskforces</button>
          <button type="button" class="u-tab u-hide-mobile" data-tab="asg">Assignments</button>
          <button type="button" class="u-tab" data-tab="edu">Education</button>
          <button type="button" class="u-tab" data-tab="train">Training</button>
          <button type="button" class="u-tab u-hide-tablet" data-tab="doc">Documents</button>
        </div>
      </div>

      <div class="u-panels">
        <div class="u-panel is-active" id="tab-ov">
          <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Basic Information</h4>
              <div class="u-space-y-sm">
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Employee ID:</span><span class="u-font-medium" id="ovId">—</span></div>
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Full Name:</span><span class="u-font-medium" id="ovName">—</span></div>
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Job Title:</span><span class="u-font-medium" id="ovJob">—</span></div>
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Unit:</span><span class="u-font-medium" id="ovUnit">—</span></div>
              </div>
            </div>
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Status & Employment</h4>
              <div class="u-space-y-sm">
                <div class="u-flex u-justify-between u-items-center"><span class="u-text-sm u-muted">Employment Status:</span><span id="ovStatus" class="u-badge u-badge--primary">—</span></div>
                <div class="u-flex u-justify-between u-items-center"><span class="u-text-sm u-muted">Talent Level:</span><span id="ovTalent" class="u-badge u-badge--glass">—</span></div>
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Latest Start Date:</span><span class="u-font-medium" id="ovStartDate">—</span></div>
                <div class="u-flex u-justify-between"><span class="u-text-sm u-muted">Company:</span><span class="u-font-medium" id="ovCompany">—</span></div>
              </div>
            </div>
            <div class="u-card u-grid-col-span-2">
              <h4 class="u-font-semibold u-mb-md">Contact Information</h4>
              <div class="u-grid-2 u-stack-mobile u-gap-md">
                <div class="u-space-y-sm">
                  <div><label class="u-text-sm u-muted u-block u-mb-xs">Email Address</label><div class="u-font-medium" id="ovEmail">—</div></div>
                  <div><label class="u-text-sm u-muted u-block u-mb-xs">Phone Number</label><div class="u-font-medium" id="ovPhone">—</div></div>
                </div>
                <div class="u-space-y-sm">
                  <div><label class="u-text-sm u-muted u-block u-mb-xs">Location</label><div class="u-font-medium"><span id="ovCity">—</span>, <span id="ovProvince">—</span></div></div>
                  <div><label class="u-text-sm u-muted u-block u-mb-xs">Directorate</label><div class="u-font-medium" id="ovDirectorate">—</div></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        @foreach(['brevet'=>'Brevet & Certifications','job'=>'Job History','task'=>'Taskforces','asg'=>'Assignments','edu'=>'Education','train'=>'Training & Development','doc'=>'Documents'] as $k=>$title)
          <div class="u-panel" id="tab-{{ $k }}">
            <div class="u-p-md">
              <div class="u-card">
                <h4 class="u-font-semibold u-mb-md">{{ $title }}</h4>
                <div class="u-list" id="{{ $k }}-list">
                  <div class="u-empty">
                    <i class='bx {{ $k==="brevet"?"bx-award":($k==="job"?"bx-briefcase":($k==="task"?"bx-group":($k==="asg"?"bx-task":($k==="edu"?"bx-graduation":($k==="train"?"bx-certification":"bx-file"))))) }} u-empty__icon'></i>
                    <p class="u-font-semibold">No {{ $k }} information available</p>
                    <p class="u-text-sm u-muted">Data will appear here</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <button class="u-btn u-btn--ghost" data-modal-close>Close</button>
    </div>
  </div>
</div>

{{-- ================= CREATE/EDIT MODAL ================= --}}
<div id="editModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fa-solid fa-user-gear"></i></div>
        <div>
          <div id="editTitle" class="u-title">Create Account</div>
          <div class="u-text-sm u-muted" id="editSubtitle">Set identity · password · roles · unit</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class='bx bx-x'></i></button>
    </div>

    <form id="editForm" method="post">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <input type="hidden" name="employee_id" id="f_employee_id">

      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#tab-identity">Identity</button>
            <button class="u-tab" type="button" data-target="#tab-password">Security</button>
            <button class="u-tab" type="button" data-target="#tab-roles">Access</button>
          </div>
        </div>

        <div class="u-panels">
          <div class="u-panel is-active" id="tab-identity">
            <div class="u-grid-2 u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-semibold u-block u-mb-xs">Full Name</label>
                <input type="text" class="u-input" name="name" id="i_name" required placeholder="Full name">
              </div>
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-semibold u-block u-mb-xs">Email</label>
                <input type="email" class="u-input" name="email" id="i_email" required placeholder="email@domain.com">
              </div>
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-semibold u-block u-mb-xs">Unit Kerja</label>
                <select class="u-input" name="unit_id" id="i_unit">
                  <option value="">— Select Unit —</option>
                  @foreach($unitsOptions as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="u-grid-col-span-2 u-text-sm u-muted" style="display:flex;gap:8px;flex-wrap:wrap">
                <span class="u-badge u-badge--glass" id="autofillName">Name: —</span>
                <span class="u-badge u-badge--glass" id="autofillEmail">Email: —</span>
                <span class="u-badge u-badge--glass" id="autofillEmp">Employee: —</span>
              </div>
            </div>
          </div>

          <div class="u-panel" id="tab-password">
            <div class="u-grid-2 u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-semibold u-block u-mb-xs">New Password</label>
                <input type="password" class="u-input" name="password" id="f_password" placeholder="Min 8 characters">
                <p class="u-text-xs u-muted u-mt-xs" id="passHint">Kosongkan untuk memakai default "password".</p>
              </div>
              <div class="u-grid-col-span-2 u-card" style="background:color-mix(in srgb,#f59e0b 6%,transparent);
                          border-color:color-mix(in srgb,#f59e0b 18%,transparent);
                          color:#92400e;">
                <div class="u-flex u-items-start u-gap-sm">
                  <i class='bx bx-info-circle'></i>
                  <div>
                    <p class="u-text-sm u-font-medium">Important</p>
                    <p class="u-text-xs" id="warnText">Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="u-panel" id="tab-roles">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md">
                <h4 class="u-font-semibold">Assign Roles (scoped by unit)</h4>
                <div class="u-flex u-gap-sm">
                  <button type="button" class="u-btn u-btn--outline u-btn--sm" data-check="all-roles">Select All</button>
                  <button type="button" class="u-btn u-btn--ghost u-btn--sm" data-check="none-roles">None</button>
                </div>
              </div>
              @if($rolesOptions->isEmpty())
                <div class="u-empty"><p class="u-muted">No roles available.</p></div>
              @else
                <div class="u-list" id="rolesChecklist">
                  @foreach($rolesOptions as $ro)
                    <label class="u-item u-flex u-items-center u-gap-sm">
                      <input type="checkbox" name="roles[]" value="{{ $ro->id }}" class="u-rounded">
                      <span class="u-text-sm">{{ $ro->name }}</span>
                    </label>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="u-modal__foot">
        <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift" id="submitEdit">
            <i class='fas fa-save u-mr-xs'></i><span id="submitText">Create</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const shell       = document.querySelector('.u-card[data-store-url]');
  const STORE_URL   = shell?.dataset.storeUrl || '';
  const UPDATE_BASE = shell?.dataset.updateUrlBase || '';

  const $  = (q,root=document)=>root.querySelector(q);
  const $$ = (q,root=document)=>Array.from(root.querySelectorAll(q));
  const empty = (icon,t,desc)=>`<div class="u-empty"><i class='bx ${icon} u-empty__icon'></i><p class="u-font-semibold">${t}</p><p class="u-text-sm u-muted">${desc}</p></div>`;

  let currentUserId = null;

  // OPENERS
  document.addEventListener('click', (e)=>{
    const openCreate = e.target.closest('[data-open="addUser"]');
    if (openCreate){ openCreateModal(); }
    const openRow = e.target.closest('[data-open="rowEdit"]');
    if (openRow){
      const data = JSON.parse(openRow.dataset.emp || '{}');
      openFromRow(data);
    }
  });

  function openCreateModal(){
    currentUserId = null;
    const form = $('#editForm'); form.reset();
    $$('#rolesChecklist input[type="checkbox"]').forEach(cb=>cb.checked=false);
    form.action = STORE_URL; form.querySelector('input[name="_method"]').value = 'POST';
    $('#editTitle').textContent='Create Account';
    $('#editSubtitle').textContent='Set identity · password · roles · unit';
    $('#submitText').textContent='Create';
    $('#i_name').value=''; $('#i_email').value=''; $('#i_unit').value='';
    $('#f_employee_id').value='';
    $('#autofillName').textContent='Name: —';
    $('#autofillEmail').textContent='Email: —';
    $('#autofillEmp').textContent='Employee: —';
    $('#f_password').value='';
    $('#passHint').textContent='Kosongkan untuk memakai default "password".';
    $('#warnText').innerHTML='Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.';
    openModal('#editModal'); activateEditTab('#tab-identity');
  }

  function openFromRow(basic){
    const form = $('#editForm'); form.reset();
    currentUserId = basic?.user?.id || null;

    $('#i_name').value  = (basic?.user?.name ?? basic?.full_name ?? '') || '';
    $('#i_email').value = (basic?.user?.email ?? basic?.email ?? '') || '';
    $('#i_unit').value  = (basic?.user?.unit_id ?? '') || '';
    $('#f_employee_id').value = basic?.employee_id || '';

    $('#autofillName').textContent  = 'Name: ' + ($('#i_name').value||'—');
    $('#autofillEmail').textContent = 'Email: ' + ($('#i_email').value||'—');
    $('#autofillEmp').textContent   = 'Employee: ' + (basic?.employee_id || '—');

    $$('#rolesChecklist input[type="checkbox"]').forEach(cb=>cb.checked=false);
    (basic?.user?.roles_ids || []).forEach(id=>{
      const el = document.querySelector(`#rolesChecklist input[value="${id}"]`);
      if(el) el.checked=true;
    });

    if (currentUserId){
      form.action = `${UPDATE_BASE}/${currentUserId}`;
      form.querySelector('input[name="_method"]').value = 'PUT';
      $('#editTitle').textContent='Edit Account';
      $('#editSubtitle').textContent='Update password · roles · unit';
      $('#submitText').textContent='Update';
      $('#passHint').textContent='Biarkan kosong bila tidak ingin mengubah.';
      $('#warnText').innerHTML='Perubahan password langsung aktif setelah disimpan.';
    } else {
      form.action = STORE_URL;
      form.querySelector('input[name="_method"]').value = 'POST';
      $('#editTitle').textContent='Create Account';
      $('#editSubtitle').textContent='Set identity · password · roles · unit';
      $('#submitText').textContent='Create';
      $('#passHint').textContent='Kosongkan untuk memakai default "password".';
      $('#warnText').innerHTML='Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.';
    }
    openModal('#editModal'); activateEditTab('#tab-identity');
  }

  // DETAILS (FETCH + RENDER)
  document.addEventListener('click', async (e)=>{
    const opener = e.target.closest('[data-modal-open="empModal"]');
    if(!opener) return;

    const basic = JSON.parse(opener.dataset.emp || '{}');
    const empPk = opener.dataset.employeeId;

    const loaders = ['brevet-list','job-list','task-list','asg-list','edu-list','train-list','doc-list'];
    loaders.forEach(id=>{
      const el=document.getElementById(id);
      if(el) el.innerHTML = `<div class="u-empty"><i class='bx bx-loader-alt bx-spin u-empty__icon'></i><p class="u-font-semibold">Loading…</p></div>`;
    });

    let api={};
    try{
      const resp=await fetch(`/admin/employees/${empPk}`,{headers:{'Accept':'application/json'}});
      if(resp.ok) api=await resp.json();
    }catch(_){}

    const emp=(api&&api.employee)?api.employee:{};
    const d = {
      full_name:  basic.full_name ?? emp.full_name ?? null,
      employee_id:basic.employee_id ?? emp.employee_key ?? emp.employee_id ?? null,
      job_title:  basic.job_title ?? emp.job_title ?? null,
      unit_name:  basic.unit_name ?? emp.unit_name ?? null,
      status:     basic.status ?? emp.employee_status ?? null,
      talent:     basic.talent ?? emp.talent_class_level ?? null,
      start_date: basic.start_date ?? emp.latest_jobs_start_date ?? null,
      company:    basic.company ?? emp.company_name ?? 'PT Surveyor Indonesia',
      email:      basic.email ?? emp.email ?? null,
      phone:      basic.phone ?? emp.phone ?? null,
      city:       basic.city ?? emp.location_city ?? null,
      province:   basic.province ?? emp.location_province ?? null,
      directorate:basic.directorate ?? emp.directorate_name ?? null,
      photo_url:  basic.photo_url ?? emp.person_photo ?? null,
      brevet_list: api?.brevet_list ?? [],
      job_histories: api?.job_histories ?? [],
      taskforces: api?.taskforces ?? [],
      assignments: api?.assignments ?? [],
      educations: api?.educations ?? [],
      trainings: api?.trainings ?? [],
      documents: api?.documents ?? [],
    };

    set('#empName', d.full_name || '—');
    set('#empId', 'ID: ' + (d.employee_id || '—'));
    set('#empHire','Latest Start: ' + (fmt(d.start_date) || '—'));
    set('#empCompany','Company: ' + (d.company || '—'));

    const avatar=$('#empPhoto'), init=$('#empInitial');
    if(d.photo_url){
      avatar.style.backgroundImage=`url(${d.photo_url})`;
      avatar.classList.remove('u-avatar--brand');
      init.style.display='none';
    } else {
      avatar.style.backgroundImage='';
      avatar.classList.add('u-avatar--brand');
      init.style.display='flex';
      init.textContent = (d.full_name||'?').toString().trim().charAt(0).toUpperCase();
    }

    set('#ovId', d.employee_id || '—'); set('#ovName', d.full_name || '—'); set('#ovJob', d.job_title || '—'); set('#ovUnit', d.unit_name || '—');
    badge('#ovStatus', d.status); badge('#ovTalent', d.talent);
    set('#ovStartDate', fmt(d.start_date) || '—'); set('#ovCompany', d.company || '—');
    set('#ovEmail', d.email || '—'); set('#ovPhone', d.phone || '—');
    set('#ovCity', d.city || '—'); set('#ovProvince', d.province || '—'); set('#ovDirectorate', d.directorate || '—');

    const tile=(left,right)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start"><div>${left}</div><div>${right||''}</div></div></div>`;
    const render=(containerId, arr, mapper, icon, title, desc)=>{
      const c=$(containerId); if(!c) return;
      if(arr && arr.length) c.innerHTML = arr.map(mapper).join('');
      else c.innerHTML = empty(icon, title, desc);
    };

    render('#brevet-list', d.brevet_list, (v)=>tile(`
      <div>
        <h4 class="u-font-semibold u-mb-xs">${v.title || 'Brevet Certification'}</h4>
        <p class="u-text-sm u-muted">${v.organization || v.institution || 'Professional Institution'}</p>
        ${v.description?`<p class="u-text-sm u-muted u-mt-xs">${v.description}</p>`:''}
      </div>
    `, v.start_date?`<span class="u-badge u-badge--glass">${year(v.start_date)}</span>`:''), 'bx-award', 'No brevet information available','—');

    render('#job-list', d.job_histories, (j)=>{
      const sd=fmt(j.start_date), ed=fmt(j.end_date), range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return tile(
        `<div>
           <h4 class="u-font-semibold u-mb-xs">${j.title || 'Position'}</h4>
           <p class="u-text-sm u-muted">${j.organization || j.company || 'Company'}${j.unit_name?` - ${j.unit_name}`:''}</p>
           ${j.description?`<p class="u-text-sm u-muted u-mt-xs">${j.description}</p>`:''}
         </div>`,
        range?`<span class="u-badge u-badge--primary">${range}</span>`:''
      );
    }, 'bx-briefcase','No job history available','—');

    render('#task-list', d.taskforces, (t)=>{
      const sd=year(t.start_date), ed=year(t.end_date), range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return tile(
        `<div>
           <h4 class="u-font-semibold u-mb-xs">${t.title || 'Taskforce'}</h4>
           <p class="u-text-sm u-muted">Organization: ${t.organization || 'N/A'}</p>
           ${t.description?`<p class="u-text-sm u-muted u-mt-xs">${t.description}</p>`:''}
         </div>`,
        range?`<span class="u-badge u-badge--glass">${range}</span>`:''
      );
    }, 'bx-group','No taskforce information available','—');

    render('#asg-list', d.assignments, (a)=>{
      const sd=year(a.start_date), ed=year(a.end_date), range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return `
        <div class="u-item">
          <h4 class="u-font-semibold u-mb-xs">${a.title || 'Assignment'}</h4>
          <p class="u-text-sm u-muted u-mb-sm">${a.description || 'No description available'}</p>
          <div class="u-flex u-justify-between u-items-center">
            <span class="u-text-xs u-muted">Organization: ${a.organization || 'N/A'}</span>
            ${range?`<span class="u-badge u-badge--primary">${range}</span>`:''}
          </div>
        </div>
      `;
    }, 'bx-task','No assignment information available','—');

    // ===== EDUCATION (META-AWARE) =====
    render('#edu-list', d.educations, (e)=>{
      const meta = parseMeta(e.meta);
      const level = meta.level || e.level || extractDegreeLevel(e.title, e.description);
      const major = meta.major || e.major || extractMajor(e.description);
      const org   = e.organization || e.institution || e.school || 'Institution';
      const gradYear = meta.graduation_year || firstYear(e.graduation_year, e.graduate_year, e.end_date, e.year, e.start_date);

      return tile(
        `<div>
           <h4 class="u-font-semibold u-mb-xs">${level || (e.title || 'Education')}</h4>
           <p class="u-text-sm u-muted">${org}${major ? ' - ' + major : ''}</p>
           ${e.description?`<p class="u-text-sm u-muted u-mt-xs">${e.description}</p>`:''}
         </div>`,
        gradYear ? `<span class="u-badge u-badge--glass">Graduate : ${gradYear}</span>` : ''
      );
    }, 'bx-graduation','No education information available','—');

    render('#train-list', d.trainings, (t)=>tile(`
      <div>
        <h4 class="u-font-semibold u-mb-xs">${t.title || 'Training Course'}</h4>
        <p class="u-text-sm u-muted">Provider: ${t.organization || 'Training Provider'}</p>
        ${t.description?`<p class="u-text-sm u-muted u-mt-xs">${t.description}</p>`:''}
      </div>
    `, (year(t.start_date)||year(t.end_date))?`<span class="u-badge u-badge--primary">${year(t.start_date)||year(t.end_date)}</span>`:''), 'bx-certification','No training information available','—');

    render('#doc-list', d.documents, (dd)=>`
      <div class="u-item">
        <div class="u-flex u-justify-between u-items-center">
          <div class="u-flex u-items-center u-gap-sm">
            <i class='bx bx-file'></i>
            <div>
              <h4 class="u-font-semibold u-mb-xs">${dd.meta_title || dd.doc_type || 'Document'}</h4>
              <p class="u-text-sm u-muted">Type: ${dd.doc_type || 'Document'}</p>
              ${dd.meta_due_date ? `<p class="u-text-xs u-muted">Due: ${fmt(dd.meta_due_date)}</p>` : ''}
            </div>
          </div>
          <div>${dd.url?`<a href="${dd.url}" target="_blank" class="u-btn u-btn--sm u-btn--outline">View</a>`:''}</div>
        </div>
      </div>
    `,'bx-file','No documents available','—');

    openModal('#empModal');
    setActiveDetailTab('ov');
  });

  // CLOSE
  document.addEventListener('click', (e)=>{
    const closeBtn = e.target.closest('[data-modal-close]');
    const backdrop = e.target.classList.contains('u-modal') ? e.target : null;
    if (closeBtn || backdrop) {
      const modal = closeBtn ? closeBtn.closest('.u-modal') : backdrop;
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    }
  });
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') { $$('.u-modal:not([hidden])').forEach(m=>m.hidden=true); document.body.classList.remove('modal-open'); }});

  // UTILS
  function openModal(sel){ const m=$(sel); if(m){ m.hidden=false; document.body.classList.add('modal-open'); } }
  function set(q,t){ const el=$(q); if(el) el.textContent=t; }
  function badge(q,val){ const el=$(q); if(!el) return; if(val){ el.style.display='inline-flex'; el.textContent=val; } else { el.style.display='none'; el.textContent='—'; } }
  function fmt(s){ if(!s) return ''; try{ const d=new Date(s); return isNaN(d)?String(s):d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});}catch(_){return String(s)} }
  function year(s){ if(!s) return ''; const m=String(s).match(/^(\d{4})/); return m?m[1]:''; }
  function firstYear(...vals){
    for (const v of vals){
      if (!v) continue;
      const y = year(v);
      if (y) return y;
    }
    return '';
  }
  function safeJsonParse(x){
    if (!x) return null;
    if (typeof x === 'object') return x;
    try{ return JSON.parse(x); }catch(_){ return null; }
  }
  function parseMeta(meta){
    const m = safeJsonParse(meta) || {};
    // normalisasi key yang sering muncul beda-beda
    const norm = {
      level: m.level ?? m.degree ?? m.tingkat ?? null,
      major: m.major ?? m.jurusan ?? m.study ?? m.program ?? null,
      graduation_year: m.graduation_year ?? m.graduate_year ?? m.year ?? null,
    };
    return norm;
  }
  function extractDegreeLevel(title, description){
    const text = ((title||'') + ' ' + (description||'')).toLowerCase();
    if (/(s3|doktor|doctor|ph\.?d)/.test(text)) return 'S3';
    if (/(s2|magister|master|m\.?(\w+)?)/.test(text)) return 'S2';
    if (/(s1|sarjana|bachelor|b\.?(\w+)?)/.test(text)) return 'S1';
    if (/(d3|diploma)/.test(text)) return 'D3';
    if (/(sma|smk|slta)/.test(text)) return 'SMA/SMK';
    return title || 'Education';
  }
  function extractMajor(description){
    if (!description) return null;
    const majorKeywords = ['jurusan','major','program studi','prodi','bidang','konsentrasi','magister','sarjana'];
    const txt = String(description);
    for (const k of majorKeywords){
      const re = new RegExp(`${k}[\\s:–-]*([^.,;\\n]+)`, 'i');
      const m = txt.match(re);
      if (m) return m[1].trim();
    }
    return null;
  }

  // EDIT TABS
  function activateEditTab(idSel){
    const scope = $('#editModal');
    const allBtns = scope.querySelectorAll('.u-tab[data-target]');
    allBtns.forEach(t=>t.classList.remove('is-active'));
    const theBtn = Array.from(allBtns).find(btn=>btn.getAttribute('data-target')===idSel);
    if(theBtn) theBtn.classList.add('is-active');
    scope.querySelectorAll('.u-panels .u-panel').forEach(p=>p.classList.remove('is-active'));
    const panel = scope.querySelector(idSel); if(panel) panel.classList.add('is-active');
  }
  document.addEventListener('click',(e)=>{
    const tabBtn = e.target.closest('#editModal .u-tab[data-target]');
    if(!tabBtn) return;
    activateEditTab(tabBtn.getAttribute('data-target'));
  });

  // DETAIL TABS
  const empTabs = document.getElementById('empTabs');
  function setActiveDetailTab(name){
    const scope = $('#empModal'); if(!scope) return;
    scope.querySelectorAll('#empTabs .u-tab').forEach(t=>t.classList.remove('is-active'));
    const btn = scope.querySelector(`#empTabs .u-tab[data-tab="${name}"]`);
    if (btn) btn.classList.add('is-active');
    scope.querySelectorAll('.u-panels .u-panel').forEach(p=>p.classList.remove('is-active'));
    const panel = scope.querySelector(`#tab-${name}`); if(panel) panel.classList.add('is-active');
  }
  if (empTabs){
    empTabs.addEventListener('click', (e)=>{
      const btn = e.target.closest('.u-tab[data-tab]');
      if(!btn) return;
      setActiveDetailTab(btn.dataset.tab);
    });
  }

  // roles select all/none
  document.addEventListener('click', (e)=>{
    const all=e.target.closest('[data-check="all-roles"]');
    const none=e.target.closest('[data-check="none-roles"]');
    if(all || none) $$('#rolesChecklist input[type="checkbox"]').forEach(cb=>cb.checked=!!all);
  });

  // live chips
  $('#i_name')?.addEventListener('input', ()=> $('#autofillName').textContent = 'Name: ' + ($('#i_name').value||'—'));
  $('#i_email')?.addEventListener('input',()=> $('#autofillEmail').textContent = 'Email: ' + ($('#i_email').value||'—'));
});

document.addEventListener("DOMContentLoaded", () => {

    // Select all close buttons
    const closeButtons = document.querySelectorAll("[data-modal-close]");

    closeButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const modal = btn.closest(".u-modal"); // find the parent modal
            if (modal) {
                modal.classList.add("hidden"); // or modal.style.display = "none";
            }
        });
    });

});
</script>

@endsection
