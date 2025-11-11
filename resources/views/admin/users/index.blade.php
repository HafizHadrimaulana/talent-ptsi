@extends('layouts.app')
@section('title','User Management · Employee Directory')

@section('content')
@php
  /** @var \Illuminate\Support\Collection $roles */
  $rolesOptions = $roles ?? collect();
  /** @var \Illuminate\Support\Collection $units */
  $unitsOptions = $units ?? collect();
@endphp

<style>
/* --- kecilkan hanya yang perlu (kelas lain pakai tokens kamu) --- */
.table-hover-actions tbody tr .row-actions{opacity:.0;transform:translateY(2px);transition:all .18s ease}
.table-hover-actions tbody tr:hover .row-actions{opacity:1;transform:translateY(0)}
.icon-pill{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:var(--surface-0);cursor:pointer;transition:all .18s ease}
.icon-pill:hover{box-shadow:var(--shadow-sm);transform:translateY(-1px)}
.icon-pill .fa,.icon-pill .bx{font-size:.95rem;opacity:.9}
.meta-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--glass-stroke);background:var(--glass-bg);padding:.25rem .5rem;border-radius:999px;font-size:.75rem}
.meta-kv{display:flex;justify-content:space-between;gap:10px}
.meta-kv .k{color:var(--muted);font-size:.82rem}
.meta-kv .v{font-weight:600}
.list-tile{border:1px solid var(--border);border-radius:12px;padding:.8rem;background:var(--surface-0)}
.list-tile .title{font-weight:700;margin-bottom:.1rem}
.list-tile .sub{font-size:.85rem;color:var(--muted)}
.badge-year{display:inline-flex;align-items:center;border:1px solid var(--glass-stroke);background:var(--glass-bg);padding:.2rem .45rem;border-radius:999px;font-size:.72rem}
.u-avatar--sm2{width:36px;height:36px;border-radius:10px;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;font-weight:700}
</style>

<div class="u-card u-card--glass u-hover-lift"
     data-store-url="{{ route('admin.users.store') }}"
     data-update-url-base="{{ url('/admin/settings/access/users') }}">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Employee Directory + User Accounts</h2>
    <div class="u-flex u-items-center u-gap-sm">
      @can('users.create')
      <button type="button" class="u-btn u-btn--brand u-hover-lift" data-open="addUser">
        <i class="fas fa-user-plus u-mr-xs"></i> Add User
      </button>
      @endcan
    </div>
  </div>

  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='bx bx-check-circle u-success-icon'></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class='bx bx-error-circle u-error-icon'></i>
        <span class="u-font-semibold">Please fix the following errors:</span>
      </div>
      <ul class="u-list">
        @foreach($errors->all() as $e)
          <li class="u-item">{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="dt-wrapper">
    <div class="u-scroll-x">
      <table id="employees-table" class="u-table u-table-mobile table-hover-actions" data-dt>
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
            $employee_id = $r->employee_key ?? $r->employee_id ?? null;
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
              'email'         => $r->employee_email ?? null,
              'status'        => $status,
              'talent'        => $talent,
              'company'       => 'PT Surveyor Indonesia',
              'user' => [
                'id'        => $r->user_id,
                'name'      => $r->user_name ?? $full_name,
                'email'     => $r->user_email ?? ($r->employee_email ?? null),
                'unit_id'   => $r->user_unit_id,
                'roles_ids' => $assigned,
              ],
            ];
          @endphp
          <tr>
            <td>{{ $employee_id ?? '—' }}</td>
            <td><div class="u-flex u-items-center u-gap-sm"><span class="u-font-medium">{{ $full_name ?? '—' }}</span></div></td>
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
              <div class="row-actions" style="display:flex;gap:8px;justify-content:flex-end">
                <button class="icon-pill"
                        title="{{ $r->user_id ? 'Edit Password & Roles' : 'Create Account' }}"
                        data-open="rowEdit"
                        data-employee-id="{{ $employee_pk }}"
                        data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                  <i class="fa-solid fa-user-gear"></i>
                </button>
                <button class="icon-pill"
                        title="Details"
                        data-modal-open="empModal"
                        data-employee-id="{{ $employee_pk }}"
                        data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                  <i class="fa-regular fa-user"></i>
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
        <div id="empPhoto" class="u-avatar u-avatar--lg u-avatar--brand"><span id="empInitial">?</span></div>
        <div>
          <div id="empName" class="u-title">Employee Name</div>
          <div class="u-text-sm u-muted">
            <span id="empId" class="meta-chip">ID: —</span>
            <span id="empHire" class="meta-chip">Start: —</span>
            <span id="empCompany" class="meta-chip">Company: —</span>
          </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-modal-close aria-label="Close"><i class='bx bx-x'></i></button>
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
        <div class="u-panel is-active" id="tab-ov">
          <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Basic Information</h4>
              <div class="u-space-y-sm">
                <div class="meta-kv"><span class="k">Employee ID</span><span class="v" id="ovId">—</span></div>
                <div class="meta-kv"><span class="k">Full Name</span><span class="v" id="ovName">—</span></div>
                <div class="meta-kv"><span class="k">Job Title</span><span class="v" id="ovJob">—</span></div>
                <div class="meta-kv"><span class="k">Unit</span><span class="v" id="ovUnit">—</span></div>
              </div>
            </div>
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Status & Employment</h4>
              <div class="u-space-y-sm">
                <div class="meta-kv"><span class="k">Employment Status</span><span class="v"><span id="ovStatus" class="u-badge u-badge--primary">—</span></span></div>
                <div class="meta-kv"><span class="k">Talent Level</span><span class="v"><span id="ovTalent" class="u-badge u-badge--glass">—</span></span></div>
                <div class="meta-kv"><span class="k">Start Date</span><span class="v" id="ovStartDate">—</span></div>
                <div class="meta-kv"><span class="k">Company</span><span class="v" id="ovCompany">—</span></div>
              </div>
            </div>
            <div class="u-card u-grid-col-span-2">
              <h4 class="u-font-semibold u-mb-md">Contact</h4>
              <div class="u-grid-2 u-stack-mobile u-gap-md">
                <div class="u-space-y-sm">
                  <div class="meta-kv"><span class="k">Email</span><span class="v" id="ovEmail">—</span></div>
                  <div class="meta-kv"><span class="k">Phone</span><span class="v" id="ovPhone">—</span></div>
                </div>
                <div class="u-space-y-sm">
                  <div class="meta-kv"><span class="k">Location</span><span class="v"><span id="ovCity">—</span>, <span id="ovProvince">—</span></span></div>
                  <div class="meta-kv"><span class="k">Directorate</span><span class="v" id="ovDirectorate">—</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="u-panel" id="tab-brevet"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Brevet & Certifications</h4><div class="u-list" id="brevet-list"></div></div></div></div>
        <div class="u-panel" id="tab-job"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Job History</h4><div class="u-list" id="job-list"></div></div></div></div>
        <div class="u-panel" id="tab-task"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Taskforces</h4><div class="u-list" id="task-list"></div></div></div></div>
        <div class="u-panel" id="tab-asg"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Assignments</h4><div class="u-list" id="asg-list"></div></div></div></div>
        <div class="u-panel" id="tab-edu"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Education</h4><div class="u-list" id="edu-list"></div></div></div></div>
        <div class="u-panel" id="tab-train"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Training & Development</h4><div class="u-list" id="train-list"></div></div></div></div>
        <div class="u-panel" id="tab-doc"><div class="u-p-md"><div class="u-card"><h4 class="u-font-semibold u-mb-md">Documents</h4><div class="u-list" id="doc-list"></div></div></div></div>
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
        <div class="u-avatar u-avatar--sm2 u-avatar--brand"><i class="fa-solid fa-user-gear"></i></div>
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
          {{-- Identity --}}
          <div class="u-panel is-active" id="tab-identity">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
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

              <div class="u-grid-col-span-2 u-text-sm u-muted">
                <span class="u-badge u-badge--glass" id="autofillName">Name: —</span>
                <span class="u-badge u-badge--glass" id="autofillEmail">Email: —</span>
                <span class="u-badge u-badge--primary" id="autofillEmp">Employee: —</span>
              </div>
            </div>
          </div>

          {{-- Password --}}
          <div class="u-panel" id="tab-password">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-semibold u-block u-mb-xs">New Password</label>
                <input type="password" class="u-input" name="password" id="f_password" placeholder="Min 8 characters">
                <p class="u-text-xs u-muted u-mt-xs" id="passHint">Kosongkan untuk memakai default "password".</p>
              </div>

              <div class="u-grid-col-span-2 u-card u-p-md" style="background: var(--warning-bg, color-mix(in srgb,#f59e0b 8%,transparent)); border-color: var(--warning-border, color-mix(in srgb,#f59e0b 18%,transparent));">
                <div class="u-flex u-items-start u-gap-sm">
                  <i class='bx bx-info-circle' style="color: var(--warning-color, #f59e0b);"></i>
                  <div>
                    <p class="u-text-sm u-font-medium" style="color: var(--warning-text, #b45309);">Important</p>
                    <p class="u-text-xs" style="color: var(--warning-text, #b45309);" id="warnText">
                      Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Roles --}}
          <div class="u-panel" id="tab-roles">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md">
                <h4 class="u-font-semibold">Assign Roles (scoped by unit)</h4>
                <div class="u-flex u-gap-xs">
                  <button type="button" class="u-btn u-btn--outline u-btn--sm" data-check="all-roles">Select All</button>
                  <button type="button" class="u-btn u-btn--ghost u-btn--sm" data-check="none-roles">None</button>
                </div>
              </div>

              @if($rolesOptions->isEmpty())
                <div class="u-empty"><p class="u-muted">No roles available.</p></div>
              @else
              <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm" id="rolesChecklist">
                @foreach($rolesOptions as $ro)
                  <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
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
          <button class="u-btn u-btn--brand u-hover-lift" id="submitEdit"><i class='fas fa-save u-mr-xs'></i><span id="submitText">Create</span></button>
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

  // helpers
  const $  = (q,root=document)=>root.querySelector(q);
  const $$ = (q,root=document)=>Array.from(root.querySelectorAll(q));
  const empty = (icon,t,desc)=>`<div class="u-empty"><i class='bx ${icon} u-empty__icon'></i><p class="u-font-semibold">${t}</p><p class="u-text-sm u-muted">${desc}</p></div>`;

  let currentUserId = null;      // null = create mode
  let currentEmpId  = null;      // employee pk/json for prefill
  let lastContext   = 'create';  // 'create' | 'row'

  // ============= MODAL OPENERS =============
  document.addEventListener('click', async (e)=>{
    // 1) Add User (header button) => clean create
    const openCreate = e.target.closest('[data-open="addUser"]');
    if (openCreate) { openCreateModal(); return; }

    // 2) Row "Edit/Create" => from employee row
    const openRow = e.target.closest('[data-open="rowEdit"]');
    if (openRow) {
      const data = JSON.parse(openRow.dataset.emp || '{}');
      openFromRow(data);
      return;
    }
  });

  // ============= CREATE CLEAN =============
  function openCreateModal(){
    lastContext = 'create';
    currentUserId = null;
    currentEmpId  = null;

    // reset form + clear checks
    const form = $('#editForm'); form.reset();
    $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=false);

    // action & method
    form.action = STORE_URL;
    form.querySelector('input[name="_method"]').value = 'POST';

    // title/subtitle
    $('#editTitle').textContent    = 'Create Account';
    $('#editSubtitle').textContent = 'Set identity · password · roles · unit';
    $('#submitText').textContent   = 'Create';

    // chips & identity
    $('#i_name').value = '';
    $('#i_email').value = '';
    $('#i_unit').value = '';
    $('#f_employee_id').value = '';
    $('#autofillName').textContent  = 'Name: —';
    $('#autofillEmail').textContent = 'Email: —';
    $('#autofillEmp').textContent   = 'Employee: —';

    // security hints
    $('#f_password').value = '';
    $('#passHint').textContent = 'Kosongkan untuk memakai default "password".';
    $('#warnText').innerHTML   = 'Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.';

    openModal('#editModal');
    // focus tab identity
    activateTab('#editModal','[data-target="#tab-identity"]','#tab-identity');
  }

  // ============= OPEN FROM ROW (EMP) =============
  function openFromRow(basic){
    lastContext = 'row';
    const form = $('#editForm'); form.reset();

    // preset
    currentUserId = basic?.user?.id || null;
    currentEmpId  = basic?.id || null;

    // identity
    $('#i_name').value  = (basic?.user?.name ?? basic?.full_name ?? '') || '';
    $('#i_email').value = (basic?.user?.email ?? basic?.email ?? '') || '';
    $('#i_unit').value  = (basic?.user?.unit_id ?? '') || '';
    $('#f_employee_id').value = basic?.employee_id || '';

    // chips
    $('#autofillName').textContent  = 'Name: '  + ($('#i_name').value || '—');
    $('#autofillEmail').textContent = 'Email: ' + ($('#i_email').value || '—');
    $('#autofillEmp').textContent   = 'Employee: ' + (basic?.employee_id || '—');

    // roles
    $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=false);
    (basic?.user?.roles_ids || []).forEach(id=>{
      const el = document.querySelector(`#rolesChecklist input[value="${id}"]`);
      if (el) el.checked = true;
    });

    // mode create vs edit
    if (currentUserId) {
      form.action = `${UPDATE_BASE}/${currentUserId}`;
      form.querySelector('input[name="_method"]').value = 'PUT';
      $('#editTitle').textContent    = 'Edit Account';
      $('#editSubtitle').textContent = 'Update password · roles · unit';
      $('#submitText').textContent   = 'Update';
      $('#passHint').textContent = 'Biarkan kosong bila tidak ingin mengubah.';
      $('#warnText').innerHTML   = 'Perubahan password langsung aktif setelah disimpan.';
    } else {
      form.action = STORE_URL;
      form.querySelector('input[name="_method"]').value = 'POST';
      $('#editTitle').textContent    = 'Create Account';
      $('#editSubtitle').textContent = 'Set identity · password · roles · unit';
      $('#submitText').textContent   = 'Create';
      $('#passHint').textContent = 'Kosongkan untuk memakai default "password".';
      $('#warnText').innerHTML   = 'Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.';
    }

    openModal('#editModal');
    activateTab('#editModal','[data-target="#tab-identity"]','#tab-identity');
  }

  // ============= LIST MODAL (DETAILS) =============
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
      start_date: emp.latest_jobs_start_date ?? null,
      company:    basic.company ?? emp.company_name ?? 'PT Surveyor Indonesia',
      email:      basic.email ?? emp.email ?? null,
      phone:      emp.phone ?? null,
      city:       emp.location_city ?? null,
      province:   emp.location_province ?? null,
      directorate:emp.directorate_name ?? null,
      photo_url:  emp.person_photo ?? null,
      brevet_list: api?.brevet_list ?? [],
      job_histories: api?.job_histories ?? [],
      taskforces: api?.taskforces ?? [],
      assignments: api?.assignments ?? [],
      educations: api?.educations ?? [],
      trainings: api?.trainings ?? [],
      documents: api?.documents ?? [],
    };

    // header
    set('#empName', d.full_name || '—');
    set('#empId', 'ID: ' + (d.employee_id || '—'));
    set('#empHire','Start: ' + (fmt(d.start_date) || '—'));
    set('#empCompany','Company: ' + (d.company || '—'));

    const avatar=$('#empPhoto'), init=$('#empInitial');
    if(d.photo_url){ avatar.style.backgroundImage=`url(${d.photo_url})`; avatar.classList.remove('u-avatar--brand'); init.style.display='none'; }
    else{ avatar.style.backgroundImage=''; avatar.classList.add('u-avatar--brand'); init.style.display='flex'; init.textContent = (d.full_name||'?').toString().trim().charAt(0).toUpperCase(); }

    // overview
    set('#ovId', d.employee_id || '—');
    set('#ovName', d.full_name || '—');
    set('#ovJob', d.job_title || '—');
    set('#ovUnit', d.unit_name || '—');
    badge('#ovStatus', d.status);
    badge('#ovTalent', d.talent);
    set('#ovStartDate', fmt(d.start_date) || '—');
    set('#ovCompany', d.company || '—');
    set('#ovEmail', d.email || '—');
    set('#ovPhone', d.phone || '—');
    set('#ovCity', d.city || '—');
    set('#ovProvince', d.province || '—');
    set('#ovDirectorate', d.directorate || '—');

    // lists
    render('#brevet-list', d.brevet_list, (v)=>tile(`
      <div class="title">${v.title || 'Brevet Certification'}</div>
      <div class="sub">${v.organization || v.institution || 'Professional Institution'}</div>
      ${v.description?`<div class="sub u-mt-xs">${v.description}</div>`:''}
    `, v.start_date?`<span class="badge-year">${year(v.start_date)}</span>`:''), 'bx-award', 'No brevet information available','—');

    render('#job-list', d.job_histories, (j)=>{
      const sd=fmt(j.start_date), ed=fmt(j.end_date), range = sd&&ed ? `${sd} · ${ed}` : (sd || ed || '');
      return tile(`
        <div class="title">${j.title || 'Position'}</div>
        <div class="sub">${j.organization || j.company || 'Company'} ${j.unit_name?`· ${j.unit_name}`:''}</div>
        ${j.description?`<div class="sub u-mt-xs">${j.description}</div>`:''}
      `, range?`<span class="u-badge u-badge--glass">${range}</span>`:'');
    }, 'bx-briefcase','No job history available','—');

    render('#task-list', d.taskforces, (t)=>{
      const sd=year(t.start_date), ed=year(t.end_date), range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return tile(`
        <div class="title">${t.title || 'Taskforce'}</div>
        <div class="sub">Organization: ${t.organization || 'N/A'}</div>
        ${t.description?`<div class="sub u-mt-xs">${t.description}</div>`:''}
      `, range?`<span class="badge-year">${range}</span>`:'');
    }, 'bx-group','No taskforce information available','—');

    render('#asg-list', d.assignments, (a)=>{
      const sd=year(a.start_date), ed=year(a.end_date), range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return `
        <div class="list-tile">
          <div class="title">${a.title || 'Assignment'}</div>
          <div class="sub u-mb-sm">${a.description || 'No description available'}</div>
          <div class="u-flex u-justify-between u-items-center">
            <span class="u-text-xs u-muted">Organization: ${a.organization || 'N/A'}</span>
            ${range?`<span class="u-badge u-badge--primary">${range}</span>`:''}
          </div>
        </div>`;
    }, 'bx-task','No assignment information available','—');

    render('#edu-list', d.educations, (e)=>tile(`
      <div class="title">${(e.title||'Education')}</div>
      <div class="sub">${e.organization || 'Institution'}</div>
      ${e.description?`<div class="sub u-mt-xs">${e.description}</div>`:''}
    `, e.end_date?`<span class="badge-year">Graduate: ${year(e.end_date)}</span>`:''), 'bx-graduation','No education information available','—');

    render('#train-list', d.trainings, (t)=>tile(`
      <div class="title">${t.title || 'Training Course'}</div>
      <div class="sub">Provider: ${t.organization || 'Training Provider'}</div>
      ${t.description?`<div class="sub u-mt-xs">${t.description}</div>`:''}
    `, (year(t.start_date)||'')?`<div class="right"><span class="u-badge u-badge--primary">${year(t.start_date)}</span></div>`:''), 'bx-certification','No training information available','—');

    render('#doc-list', d.documents, (d)=>`
      <div class="list-tile u-flex u-justify-between u-items-center">
        <div class="u-flex u-items-center u-gap-sm">
          <i class='bx bx-file text-xl'></i>
          <div>
            <div class="title">${d.meta_title || d.doc_type || 'Document'}</div>
            <div class="sub">Type: ${d.doc_type || 'Document'}</div>
          </div>
        </div>
        <div>${d.url?`<a href="${d.url}" target="_blank" class="u-btn u-btn--sm u-btn--outline">View</a>`:''}</div>
      </div>
    `,'bx-file','No documents available','—');

    openModal('#empModal');
    // set default tab
    activateTab('#empModal','[data-tab="ov"]','#tab-ov');
  });

  // ============= CLOSE (reset semua state) =============
  document.addEventListener('click', (e)=>{
    const closeBtn = e.target.closest('[data-modal-close]');
    const backdrop = e.target.classList.contains('u-modal') ? e.target : null;

    if (closeBtn || backdrop) {
      const modal = closeBtn ? closeBtn.closest('.u-modal') : backdrop;
      if (!modal) return;
      if (modal.id === 'editModal') {
        resetEditModalState();
      }
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    }
  });
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') {
      $$('.u-modal').forEach(m=>{
        if(!m.hidden){
          if (m.id==='editModal') resetEditModalState();
          m.hidden=true;
        }
      });
      document.body.classList.remove('modal-open');
    }
  });

  function resetEditModalState(){
    const form = $('#editForm');
    form.reset();
    $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=false);
    $('#i_unit').value = '';
    $('#f_employee_id').value = '';
    $('#autofillName').textContent  = 'Name: —';
    $('#autofillEmail').textContent = 'Email: —';
    $('#autofillEmp').textContent   = 'Employee: —';
    currentUserId = null;
    currentEmpId  = null;
    lastContext   = 'create';
  }

  // ============= UTILITIES =============
  function openModal(sel){ const m=$(sel); if(m){ m.hidden=false; document.body.classList.add('modal-open'); } }
  function set(q,t){ const el=$(q); if(el) el.textContent=t; }
  function badge(q,val){ const el=$(q); if(!el) return; if(val){ el.style.display='inline-flex'; el.textContent=val; } else { el.style.display='none'; el.textContent='—'; } }
  function fmt(s){ if(!s) return ''; try{ const d=new Date(s); return isNaN(d)?String(s):d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});}catch(_){return String(s)} }
  function year(s){ if(!s) return ''; const m=String(s).match(/^(\d{4})/); return m?m[1]:''; }
  function tile(left,right){ return `<div class="list-tile u-flex u-justify-between u-items-start"><div>${left}</div><div class="right">${right||''}</div></div>`; }
  function render(containerId, arr, mapper, icon, title, desc){
    const c=$(containerId); if(!c) return;
    if(arr && arr.length) c.innerHTML = arr.map(mapper).join('');
    else c.innerHTML = empty(icon,title,desc);
  }

  // tabs generic
  function activateTab(scopeSel, tabBtnSel, panelSel){
    const scope = $(scopeSel); if(!scope) return;
    const tabs = scope.querySelectorAll('.u-tab');
    tabs.forEach(t=>t.classList.remove('is-active'));
    const btn  = scope.querySelector(tabBtnSel);
    if (btn) btn.classList.add('is-active');
    scope.querySelectorAll('.u-panels .u-panel').forEach(p=>p.classList.remove('is-active'));
    const panel = scope.querySelector(panelSel);
    if (panel) panel.classList.add('is-active');
  }
  document.addEventListener('click',(e)=>{
    const tabBtn = e.target.closest('.u-tab[data-target]');
    if(!tabBtn) return;
    const scope = tabBtn.closest('.u-modal__card');
    const sel   = tabBtn.getAttribute('data-target');
    activateTab(scope ? '#'+scope.parentElement.id : 'body', null, sel);
    // mark clicked
    const all = scope.querySelectorAll('.u-tab[data-target]');
    all.forEach(t=>t.classList.remove('is-active'));
    tabBtn.classList.add('is-active');
  });

  // roles select all/none
  document.addEventListener('click', (e)=>{
    const all=e.target.closest('[data-check="all-roles"]');
    const none=e.target.closest('[data-check="none-roles"]');
    if(all || none) $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=!!all);
  });

  // live chips
  $('#i_name')?.addEventListener('input', ()=> $('#autofillName').textContent = 'Name: ' + ($('#i_name').value||'—'));
  $('#i_email')?.addEventListener('input',()=> $('#autofillEmail').textContent = 'Email: ' + ($('#i_email').value||'—'));
});
</script>
@endsection
