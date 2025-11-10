@extends('layouts.app')
@section('title','User Management · Employee Directory')

@section('content')
@php
  /** @var \Illuminate\Support\Collection $roles */
  $rolesOptions = $roles ?? collect();
@endphp

<style>
/* Hover-reveal action icons */
.table-hover-actions tbody tr .row-actions {opacity:.0; transform: translateY(2px); transition: all .18s ease;}
.table-hover-actions tbody tr:hover .row-actions {opacity:1; transform: translateY(0);}

/* Icon-only pills */
.icon-pill{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:10px;border:1px solid var(--border);
  background:var(--surface-0);cursor:pointer;transition:all .18s ease;
}
.icon-pill:hover{box-shadow:var(--shadow-sm); transform: translateY(-1px);}
.icon-pill .fa, .icon-pill .bx{font-size:.95rem;opacity:.9}

/* Subtle chips for meta */
.meta-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--glass-stroke);background:var(--glass-bg);
  padding:.25rem .5rem;border-radius:999px;font-size:.75rem}
.meta-kv{display:flex;justify-content:space-between;gap:10px}
.meta-kv .k{color:var(--muted);font-size:.82rem}
.meta-kv .v{font-weight:600}

/* Details list item layout */
.list-tile{border:1px solid var(--border);border-radius:12px;padding:.8rem;background:var(--surface-0)}
.list-tile .title{font-weight:700;margin-bottom:.1rem}
.list-tile .sub{font-size:.85rem;color:var(--muted)}
.list-tile .right{white-space:nowrap}

/* Compact badges in lists */
.badge-year{display:inline-flex;align-items:center;border:1px solid var(--glass-stroke);background:var(--glass-bg);
  padding:.2rem .45rem;border-radius:999px;font-size:.72rem}

/* Small avatar */
.u-avatar--sm2{width:36px;height:36px;border-radius:10px;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;font-weight:700}
</style>

<div class="u-card u-card--glass u-hover-lift"
     data-store-url="{{ route('admin.users.store') }}"
     data-update-url-base="{{ url('/admin/settings/access/users') }}">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Employee Directory + User Accounts</h2>
    <div class="u-text-sm u-muted">Manage passwords & roles directly from the directory.</div>
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
              'phone'         => null,
              'photo_url'     => null,
              'status'        => $status,
              'talent'        => $talent,
              'directorate'   => null,
              'city'          => null,
              'province'      => null,
              'company'       => 'PT Surveyor Indonesia',
              'start_date'    => null,
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
              <div class="row-actions" style="display:flex;gap:8px;justify-content:flex-end">
                <!-- Edit (password & roles) -->
                <button class="icon-pill"
                        title="{{ $r->user_id ? 'Edit Password & Roles' : 'Create Account' }}"
                        data-modal-open="editModal"
                        data-employee-id="{{ $employee_pk }}"
                        data-emp='@json($basicData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                  <i class="fa-solid fa-user-gear"></i>
                </button>
                <!-- Details -->
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

<!-- ========== DETAILS MODAL (Info only) ========== -->
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
        <!-- Overview -->
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

        <!-- Brevet -->
        <div class="u-panel" id="tab-brevet">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Brevet & Certifications</h4>
              <div class="u-list" id="brevet-list"></div>
            </div>
          </div>
        </div>

        <!-- Job History -->
        <div class="u-panel" id="tab-job">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Job History</h4>
              <div class="u-list" id="job-list"></div>
            </div>
          </div>
        </div>

        <!-- Taskforces -->
        <div class="u-panel" id="tab-task">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Taskforces</h4>
              <div class="u-list" id="task-list"></div>
            </div>
          </div>
        </div>

        <!-- Assignments -->
        <div class="u-panel" id="tab-asg">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Assignments</h4>
              <div class="u-list" id="asg-list"></div>
            </div>
          </div>
        </div>

        <!-- Education -->
        <div class="u-panel" id="tab-edu">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Education</h4>
              <div class="u-list" id="edu-list"></div>
            </div>
          </div>
        </div>

        <!-- Training -->
        <div class="u-panel" id="tab-train">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Training & Development</h4>
              <div class="u-list" id="train-list"></div>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="u-panel" id="tab-doc">
          <div class="u-p-md">
            <div class="u-card">
              <h4 class="u-font-semibold u-mb-md">Documents</h4>
              <div class="u-list" id="doc-list"></div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <button class="u-btn u-btn--ghost" data-modal-close>Close</button>
    </div>
  </div>
</div>

<!-- ========== EDIT MODAL (Password & Roles) ========== -->
<div id="editModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--sm2 u-avatar--brand"><i class="fa-solid fa-user-gear"></i></div>
        <div>
          <div id="editTitle" class="u-title">Edit Account</div>
          <div class="u-text-sm u-muted" id="editSubtitle">Password & Roles</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class='bx bx-x'></i></button>
    </div>

    <form id="editForm" method="post">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <!-- sync with controller validator -->
      <input type="hidden" name="name" id="f_name">
      <input type="hidden" name="email" id="f_email">
      <input type="hidden" name="employee_id" id="f_employee_id">

      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#tab-password"><i class='fas fa-key u-mr-xs'></i>Password</button>
            <button class="u-tab" type="button" data-target="#tab-roles"><i class='bx bx-id-card u-mr-xs'></i>Roles</button>
          </div>
        </div>

        <div class="u-panels">
          <!-- Password -->
          <div class="u-panel is-active" id="tab-password">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-text-sm u-font-medium u-block u-mb-sm">New Password</label>
                <input type="password" class="u-input" name="password" id="f_password" placeholder="Min 8 chars">
                <p class="u-text-xs u-muted u-mt-xs" id="passHint">Biarkan kosong bila tidak ingin mengubah.</p>
              </div>

              <div class="u-grid-col-span-2 u-card u-p-md" style="background: var(--warning-bg); border-color: var(--warning-border);">
                <div class="u-flex u-items-start u-gap-sm">
                  <i class='bx bx-info-circle' style="color: var(--warning-color);"></i>
                  <div>
                    <p class="u-text-sm u-font-medium" style="color: var(--warning-text);">Important</p>
                    <p class="u-text-xs" style="color: var(--warning-text);" id="warnText">
                      Pada mode <b>Create</b>, jika password dikosongkan maka sistem memakai default <code>password</code>.
                    </p>
                  </div>
                </div>
              </div>

              <div class="u-grid-col-span-2 u-text-sm u-muted">
                <span class="u-badge u-badge--glass" id="autofillName">Name: —</span>
                <span class="u-badge u-badge--glass" id="autofillEmail">Email: —</span>
                <span class="u-badge u-badge--primary" id="autofillEmp">Employee: —</span>
              </div>
            </div>
          </div>

          <!-- Roles -->
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
          <button class="u-btn u-btn--brand u-hover-lift" id="submitEdit"><i class='fas fa-save u-mr-xs'></i><span id="submitText">Save</span></button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const shell      = document.querySelector('.u-card[data-store-url]');
  const STORE_URL  = shell?.dataset.storeUrl || '';
  const UPDATE_BASE= shell?.dataset.updateUrlBase || '';

  // ---------- Helpers ----------
  const $  = (q,root=document)=>root.querySelector(q);
  const $$ = (q,root=document)=>Array.from(root.querySelectorAll(q));
  const yOnly=(s)=>{ if(!s) return null; const m=String(s).match(/^(\d{4})/); return m?m[1]:null; }
  const fmt=(s)=>{ if(!s) return '—'; try{ if(/^\d{4}$/.test(s)) return s; const d=new Date(s); return isNaN(d)?s:d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});}catch(e){return s} }
  const empty=(icon,t,desc)=>`<div class="u-empty"><i class='bx ${icon} u-empty__icon'></i><p class="u-font-semibold">${t}</p><p class="u-text-sm u-muted">${desc}</p></div>`;

  function setText(id, val){ const el=$(id.startsWith('#')?id:'#'+id); if(el) el.textContent = val ?? '—'; }
  function setBadge(id, val){ const el=$(id.startsWith('#')?id:'#'+id); if(!el) return; if(val){ el.style.display='inline-flex'; el.textContent = val; } else { el.style.display='none'; el.textContent='—'; } }

  function renderList(containerId, arr, mapper, icon, title, desc){
    const c=$(containerId);
    if(!c) return;
    if(arr && arr.length) c.innerHTML = arr.map(mapper).join('');
    else c.innerHTML = empty(icon,title,desc);
  }

  // ---------- DETAILS OPEN ----------
  document.addEventListener('click', async (e)=>{
    const opener = e.target.closest('[data-modal-open="empModal"]');
    if(!opener) return;

    const basic = JSON.parse(opener.dataset.emp || '{}');
    const empPk = opener.dataset.employeeId;

    // loaders
    ['brevet-list','job-list','task-list','asg-list','edu-list','train-list','doc-list'].forEach(id=>{
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

    // Header
    setText('#empName', d.full_name || '—');
    setText('#empId', 'ID: ' + (d.employee_id || '—'));
    setText('#empHire', 'Start: ' + (fmt(d.start_date) || '—'));
    setText('#empCompany', 'Company: ' + (d.company || '—'));

    const avatar=$('#empPhoto'), init=$('#empInitial');
    if(d.photo_url){ avatar.style.backgroundImage=`url(${d.photo_url})`; avatar.classList.remove('u-avatar--brand'); init.style.display='none'; }
    else{ avatar.style.backgroundImage=''; avatar.classList.add('u-avatar--brand'); init.style.display='flex'; init.textContent = (d.full_name||'?').toString().trim().charAt(0).toUpperCase(); }

    // Overview
    setText('#ovId', d.employee_id || '—');
    setText('#ovName', d.full_name || '—');
    setText('#ovJob', d.job_title || '—');
    setText('#ovUnit', d.unit_name || '—');
    setBadge('#ovStatus', d.status);
    setBadge('#ovTalent', d.talent);
    setText('#ovStartDate', fmt(d.start_date) || '—');
    setText('#ovCompany', d.company || '—');
    setText('#ovEmail', d.email || '—');
    setText('#ovPhone', d.phone || '—');
    setText('#ovCity', d.city || '—');
    setText('#ovProvince', d.province || '—');
    setText('#ovDirectorate', d.directorate || '—');

    // Lists
    renderList('#brevet-list', d.brevet_list, (v)=>`
      <div class="list-tile u-flex u-justify-between u-items-start">
        <div>
          <div class="title">${v.title || 'Brevet Certification'}</div>
          <div class="sub">${v.organization || v.institution || 'Professional Institution'}</div>
          ${v.description?`<div class="sub u-mt-xs">${v.description}</div>`:''}
        </div>
        <div class="right">
          ${v.start_date?`<span class="badge-year">${yOnly(v.start_date)}</span>`:''}
          ${v.end_date?`<div class="u-text-xs u-muted u-mt-xs">Expired ${yOnly(v.end_date)}</div>`:''}
        </div>
      </div>
    `,'bx-award','No brevet information available','Brevet and certification data will appear here');

    renderList('#job-list', d.job_histories, (job)=>{
      const sd=fmt(job.start_date), ed=fmt(job.end_date);
      const range = sd&&ed ? `${sd} · ${ed}` : (sd || ed || '');
      return `
        <div class="list-tile u-flex u-justify-between u-items-start">
          <div>
            <div class="title">${job.title || 'Position'}</div>
            <div class="sub">${job.organization || job.company || 'Company'} ${job.unit_name?`· ${job.unit_name}`:''}</div>
            ${job.description?`<div class="sub u-mt-xs">${job.description}</div>`:''}
          </div>
          <div class="right">${range?`<span class="u-badge u-badge--glass">${range}</span>`:''}</div>
        </div>`;
    },'bx-briefcase','No job history available','Previous employment positions will appear here');

    renderList('#task-list', d.taskforces, (t)=>{
      const sd=yOnly(t.start_date), ed=yOnly(t.end_date);
      const range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return `
        <div class="list-tile u-flex u-justify-between u-items-start">
          <div>
            <div class="title">${t.title || 'Taskforce'}</div>
            <div class="sub">Organization: ${t.organization || 'N/A'}</div>
            ${t.description?`<div class="sub u-mt-xs">${t.description}</div>`:''}
          </div>
          <div class="right">${range?`<span class="badge-year">${range}</span>`:''}</div>
        </div>`;
    },'bx-group','No taskforce information available','Taskforce assignments will appear here');

    renderList('#asg-list', d.assignments, (a)=>{
      const sd=yOnly(a.start_date), ed=yOnly(a.end_date);
      const range = sd&&ed ? `${sd} - ${ed}` : (sd || ed || '');
      return `
        <div class="list-tile">
          <div class="title">${a.title || 'Assignment'}</div>
          <div class="sub u-mb-sm">${a.description || 'No description available'}</div>
          <div class="u-flex u-justify-between u-items-center">
            <span class="u-text-xs u-muted">Organization: ${a.organization || 'N/A'}</span>
            ${range?`<span class="u-badge u-badge--primary">${range}</span>`:''}
          </div>
        </div>`;
    },'bx-task','No assignment information available','Special assignments will appear here');

    renderList('#edu-list', d.educations, (edu)=>{
      const meta = edu.meta ? (typeof edu.meta==='string' ? (function(){try{return JSON.parse(edu.meta)}catch(_){return {}}})() : edu.meta) : {};
      const level = meta.level || (function(t,desc){const s=(t+' '+(desc||'')).toLowerCase(); if(s.includes('s3')||s.includes('doktor')||s.includes('doctor')) return 'Doktor'; if(s.includes('s2')||s.includes('magister')||s.includes('master')) return 'Magister'; if(s.includes('s1')||s.includes('sarjana')||s.includes('bachelor')) return 'Sarjana'; if(s.includes('d3')||s.includes('diploma')) return 'Diploma'; if(s.includes('sma')||s.includes('smk')||s.includes('slta')) return 'SMA/SMK'; return t||'Pendidikan';})(edu.title, edu.description);
      const major = meta.major || (function(d){ if(!d) return null; const keys=['jurusan','major','program studi','prodi','bidang']; for(const k of keys){ const re=new RegExp(`${k}[\\s:]?\\s*([^.,]+)`,'i'); const m=d.match(re); if(m) return m[1].trim(); } return null; })(edu.description);
      const grad  = meta.graduation_year || yOnly(edu.end_date) || yOnly(edu.start_date);
      const inst  = edu.organization || 'Institution';
      return `
        <div class="list-tile u-flex u-justify-between u-items-start">
          <div>
            <div class="title">${level || 'Education'}</div>
            <div class="sub">${inst}${major?` · ${major}`:''}</div>
            ${edu.description?`<div class="sub u-mt-xs">${edu.description}</div>`:''}
          </div>
          ${grad?`<div class="right"><span class="badge-year">Graduate: ${grad}</span></div>`:''}
        </div>`;
    },'bx-graduation','No education information available','Educational background will appear here');

    renderList('#train-list', d.trainings, (t)=>{
      const y = yOnly(t.start_date) || yOnly(t.end_date);
      return `
        <div class="list-tile u-flex u-justify-between u-items-start">
          <div>
            <div class="title">${t.title || 'Training Course'}</div>
            <div class="sub">Provider: ${t.organization || 'Training Provider'}</div>
            ${t.description?`<div class="sub u-mt-xs">${t.description}</div>`:''}
          </div>
          ${y?`<div class="right"><span class="u-badge u-badge--primary">${y}</span></div>`:''}
        </div>`;
    },'bx-certification','No training information available','Training records will appear here');

    renderList('#doc-list', d.documents, (doc)=>`
      <div class="list-tile u-flex u-justify-between u-items-center">
        <div class="u-flex u-items-center u-gap-sm">
          <i class='bx bx-file text-xl'></i>
          <div>
            <div class="title">${doc.meta_title || doc.doc_type || 'Document'}</div>
            <div class="sub">Type: ${doc.doc_type || 'Document'}</div>
            ${doc.meta_due_date?`<div class="u-text-xs u-muted">Due: ${fmt(doc.meta_due_date)}</div>`:''}
          </div>
        </div>
        <div>${doc.url?`<a href="${doc.url}" target="_blank" class="u-btn u-btn--sm u-btn--outline">View</a>`:''}</div>
      </div>
    `,'bx-file','No documents available','Employee documents will appear here');

    // show
    const m=$('#empModal'); m.hidden=false; document.body.classList.add('modal-open');
  });

  // Tabs (details)
  (function bindTabs(){
    const tabs = $$('#empTabs .u-tab');
    tabs.forEach(tab=>{
      tab.addEventListener('click', ()=>{
        const target=tab.dataset.tab;
        tabs.forEach(t=>t.classList.remove('is-active'));
        tab.classList.add('is-active');
        document.querySelectorAll('.u-panel').forEach(p=>{
          p.classList.remove('is-active');
          if(p.id==='tab-'+target) p.classList.add('is-active');
        });
      });
    });
  })();

  // Close any modal
  document.addEventListener('click', (e)=>{
    const close = e.target.closest('[data-modal-close]');
    if(close){ const modal=close.closest('.u-modal'); if(modal){ modal.hidden=true; document.body.classList.remove('modal-open'); const f=modal.querySelector('form'); if(f) f.reset(); } }
    const backdrop = e.target.classList.contains('u-modal') ? e.target : null;
    if(backdrop){ backdrop.hidden=true; document.body.classList.remove('modal-open'); const f=backdrop.querySelector('form'); if(f) f.reset(); }
  });
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape'){ $$('.u-modal').forEach(m=>{ if(!m.hidden){ m.hidden=true; document.body.classList.remove('modal-open'); const f=m.querySelector('form'); if(f) f.reset(); } }); }});

  // ---------- EDIT (Password & Roles) ----------
  const editForm = $('#editForm'); let currentUserId = null;

  // open edit
  document.addEventListener('click', (e)=>{
    const opener = e.target.closest('[data-modal-open="editModal"]');
    if(!opener) return;
    const data = JSON.parse(opener.dataset.emp || '{}');

    currentUserId = data?.user?.id || null;

    // set hidden fields
    $('#f_name').value  = (data?.user?.name ?? data?.full_name ?? '') || '';
    $('#f_email').value = (data?.user?.email ?? data?.email ?? '') || '';
    $('#f_employee_id').value = data?.employee_id || '';

    // autofill chips
    $('#autofillName').textContent  = 'Name: '  + ($('#f_name').value || '—');
    $('#autofillEmail').textContent = 'Email: ' + ($('#f_email').value || '—');
    $('#autofillEmp').textContent   = 'Employee: ' + (data?.employee_id || '—');

    // roles preset
    $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=false);
    (data?.user?.roles_ids || []).forEach(id=>{
      const el=document.querySelector(`#rolesChecklist input[value="${id}"]`); if(el) el.checked=true;
    });

    // modes
    if(currentUserId){
      editForm.action = `${UPDATE_BASE}/${currentUserId}`;
      editForm.querySelector('input[name="_method"]').value = 'PUT';
      $('#editTitle').textContent = 'Edit Account';
      $('#editSubtitle').textContent = 'Update password & roles';
      $('#passHint').textContent = 'Biarkan kosong bila tidak ingin mengubah.';
      $('#warnText').innerHTML = 'Perubahan password langsung aktif setelah disimpan.';
      $('#submitText').textContent = 'Update';
    }else{
      editForm.action = `${STORE_URL}`;
      editForm.querySelector('input[name="_method"]').value = 'POST';
      $('#editTitle').textContent = 'Create Account';
      $('#editSubtitle').textContent = 'Set initial password & roles';
      $('#passHint').textContent = 'Kosongkan untuk memakai default "password".';
      $('#warnText').innerHTML = 'Jika password dikosongkan, sistem memakai default <code>password</code>.';
      $('#submitText').textContent = 'Create';
    }

    // open
    const m=$('#editModal'); m.hidden=false; document.body.classList.add('modal-open');
  });

  // roles select all/none
  document.addEventListener('click', (e)=>{
    const all=e.target.closest('[data-check="all-roles"]');
    const none=e.target.closest('[data-check="none-roles"]');
    if(all || none) $$('#rolesChecklist input[type="checkbox"][name="roles[]"]').forEach(cb=>cb.checked=!!all);
  });

  // tabs in edit modal
  (function wireEditTabs(){
    const tabs = $$('#editModal .u-tab[data-target]');
    tabs.forEach(tab=>{
      tab.addEventListener('click', ()=>{
        const target = tab.getAttribute('data-target');
        tabs.forEach(t=>t.classList.remove('is-active'));
        tab.classList.add('is-active');
        $$('#editModal .u-panels .u-panel').forEach(p=>{
          if('#'+p.id === target) p.classList.add('is-active'); else p.classList.remove('is-active');
        });
      });
    });
  })();
});
</script>
@endsection
