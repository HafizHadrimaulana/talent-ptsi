@extends('layouts.app')
@section('title','User Management')

@section('content')
@php
  $rolesOptions = $roles ?? collect();
  $unitsOptions = $units ?? collect();
  $user = auth()->user();
  
  $isSuper = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->where('roles.name', 'SuperAdmin')
            ->exists();
            
  $canCreate = $isSuper || $user->can('users.create');
  $canUpdate = $isSuper || $user->can('users.update');
  $canDelete = $isSuper || $user->can('users.delete');
@endphp

@if(session('ok'))
    <div class="u-card u-p-md u-mb-lg u-success u-flex u-gap-md u-items-start">
        <div class="u-text-success u-text-xl"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="u-font-semibold u-mb-xs">Success!</div>
            <p class="u-text-sm">{{ session('ok') }}</p>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="u-card u-p-md u-mb-lg u-error u-flex u-gap-md u-items-start">
        <div class="u-text-danger u-text-xl"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="u-font-semibold u-mb-xs">Action Failed</div>
            <ul class="u-text-sm u-ml-md" style="list-style-type: disc;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif

<div class="u-card u-card--glass u-p-0 u-overflow-hidden u-mb-xl"
     data-store-url="{{ route('admin.users.store') }}"
     data-update-url-base="{{ url('/admin/settings/access/users') }}">
    <div class="u-p-lg u-border-b u-flex u-justify-between u-items-center u-stack-mobile u-gap-md u-bg-surface">
        <div>
            <h2 class="u-title u-text-lg">User Management</h2>
            <p class="u-text-sm u-muted u-mt-xs">Directory & Access Control</p>
        </div>
        @if($canCreate)
        <button type="button" class="u-btn u-btn--brand u-shadow-sm u-hover-lift" onclick="window.openCreateModal()" style="border-radius: 999px;">
            <i class="fas fa-plus"></i> <span>Add User</span>
        </button>
        @endif
    </div>
    <div class="dt-wrapper">
        <div class="u-scroll-x">
            <table id="users-table" class="u-table nowrap" style="width:100%; margin: 0 !important; border: none;">
                <thead>
                    <tr>
                        <th data-priority="1">Identity</th>
                        <th data-priority="3">Job / Unit</th>
                        <th data-priority="4">Status</th>
                        <th class="cell-actions" width="120" data-priority="2">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div id="empModal" class="u-modal" hidden>
  <div class="u-modal__backdrop" data-modal-dismiss></div>
  <div class="u-modal__card u-modal__card--xl">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div id="empPhotoWrapper"></div>
        <div>
          <div id="empName" class="u-title">Name</div>
          <div class="u-flex u-gap-sm u-text-sm u-muted">
            <span id="empId">ID: —</span> &bull; <span id="empCompany">Company: —</span>
          </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--icon" data-modal-dismiss><i class='bx bx-x'>X</i></button>
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
  <div class="u-modal__backdrop" data-modal-dismiss></div>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fa-solid fa-user-shield"></i></div>
        <div>
          <div id="editTitle" class="u-title">User Account</div>
          <div class="u-text-sm u-muted">Credentials & Access</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--icon" data-modal-dismiss><i class='bx bx-x'>X</i></button>
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
                 @if($rolesOptions->isEmpty())
                    <div class="u-card u-p-md u-muted u-text-center">No roles available to assign.</div>
                 @else
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
                 @endif
              </div>
          </div>
      </div>

      <div class="u-modal__foot">
        <button type="button" class="u-btn u-btn--ghost" data-modal-dismiss>Cancel</button>
        <button class="u-btn u-btn--brand" id="submitEdit">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
const CAN_UPDATE = {{ $canUpdate ? 'true' : 'false' }};
const CAN_DELETE = {{ $canDelete ? 'true' : 'false' }};

document.addEventListener('DOMContentLoaded', function () {
    
    function initDynamicLightbox() {
        if(document.getElementById('qv-lightbox-style')) return;
        const style = document.createElement('style');
        style.id = 'qv-lightbox-style';
        style.innerHTML = `
            .qv-overlay {
                position: fixed !important; top: 0; left: 0; width: 100vw; height: 100vh;
                background-color: rgba(0,0,0,0.92); z-index: 2147483647 !important;
                display: none; align-items: center; justify-content: center;
                backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
                opacity: 0; transition: opacity 0.3s ease;
            }
            .qv-overlay.is-visible { display: flex !important; opacity: 1; }
            .qv-img {
                max-width: 90vw; max-height: 85vh; border-radius: 8px;
                box-shadow: 0 0 40px rgba(0,0,0,0.8); object-fit: contain;
                transform: scale(0.9); transition: transform 0.3s ease;
            }
            .qv-overlay.is-visible .qv-img { transform: scale(1); }
            .qv-close {
                position: absolute; top: 20px; right: 20px; color: #fff; font-size: 40px;
                background: none; border: none; cursor: pointer; line-height: 1; padding: 10px;
            }
            .qv-close:hover { transform: scale(1.1); color: #ef4444; }
            .qv-caption {
                position: absolute; bottom: 30px; color: #fff; font-weight: 600;
                font-size: 1.1rem; text-align: center; width: 100%; text-shadow: 0 2px 4px #000;
            }
        `;
        document.head.appendChild(style);
        const div = document.createElement('div');
        div.id = 'qv-lightbox';
        div.className = 'qv-overlay';
        div.innerHTML = `<button class="qv-close" title="Close">&times;</button><img class="qv-img" src=""><div class="qv-caption"></div>`;
        document.body.appendChild(div);
        const img = div.querySelector('.qv-img');
        const caption = div.querySelector('.qv-caption');
        const close = () => { div.classList.remove('is-visible'); document.body.style.overflow = ''; setTimeout(() => { div.style.display = 'none'; img.src = ''; }, 300); };
        div.addEventListener('click', close);
        div.querySelector('.qv-close').addEventListener('click', close);
        img.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('keydown', e => { if(e.key === "Escape" && div.classList.contains('is-visible')) close(); });
        window.viewPhoto = function(url, name) { if(!url) return; img.src = url; caption.innerText = name || ''; div.style.display = 'flex'; void div.offsetWidth; div.classList.add('is-visible'); document.body.style.overflow = 'hidden'; };
    }
    initDynamicLightbox();

    const usersTable = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        dom: "<'u-dt-wrapper'<'u-dt-header'<'u-dt-len'l><'u-dt-search'f>><'u-dt-tbl'tr><'u-dt-footer'<'u-dt-info'i><'u-dt-pg'p>>>",
        responsive: {
            details: {
                renderer: function (api, rowIdx, columns) {
                    let data = $.map(columns, function (col, i) {
                        return col.hidden ? `<li class="u-dt-child-item" data-dtr-index="${col.columnIndex}"><span class="u-dt-child-title">${col.title}</span><span class="u-dt-child-data">${col.data}</span></li>` : '';
                    }).join('');
                    return data ? `<ul class="u-dt-child-row">${data}</ul>` : false;
                }
            }
        },
        ajax: { url: "{{ route('admin.users.index') }}" },
        columns: [
            { 
                data: 'full_name',
                name: 'full_name',
                orderable: true,
                render: function(data, type, row) {
                    let imgHtml = '';
                    let name = row.full_name || 'Unknown';
                    let initials = name.replace(/[^a-zA-Z\s]/g, '').match(/\b\w/g) || [];
                    initials = ((initials.shift() || '') + (initials.pop() || '')).toUpperCase();
                    if(!initials) initials = name.substring(0, 2).toUpperCase();
                    if(row.person_photo) {
                        imgHtml = `<div class="u-avatar u-avatar--md is-interactive" onclick="event.stopPropagation(); window.viewPhoto('${row.person_photo}', '${name}')" title="Zoom" style="background-image: url('${row.person_photo}')"></div>`;
                    } else {
                        imgHtml = `<div class="u-avatar u-avatar--md"><span class="u-avatar-initial">${initials}</span></div>`;
                    }
                    let sub = row.employee_email || row.user_email || '-';
                    let idInfo = row.employee_id ? `<span class="u-badge u-badge--glass u-text-xxs">${row.employee_id}</span>` : '';
                    return `<div class="u-flex u-items-center u-gap-md">${imgHtml}<div class="u-min-w-0"><div class="u-font-bold u-text-md u-truncate">${name}</div><div class="u-text-xs u-muted u-truncate">${sub}</div><div class="u-mt-xxs">${idInfo}</div></div></div>`;
                }
            },
            { 
                data: 'job_title',
                name: 'job_title',
                orderable: true,
                render: function(data, type, row) {
                    return `<div><div class="u-font-semibold u-text-sm">${row.job_title || '-'}</div><div class="u-text-xs u-muted">${row.unit_name || '-'}</div><div class="u-text-xs u-text-brand u-mt-xxs">${row.company_name || ''}</div></div>`;
                }
            },
            { 
                data: 'employee_status',
                name: 'employee_status',
                orderable: true,
                render: function(data, type, row) {
                    if(row.user_id) return `<span class="u-badge u-bg-success-light u-text-success"><i class="fas fa-check-circle u-mr-xs"></i> Active User</span>`;
                    return `<span class="u-badge u-badge--glass">${row.employee_status || 'Employee'}</span>`;
                }
            },
            { 
                data: null,
                orderable: false,
                className: "text-center",
                render: function(data, type, row) {
                    const rowData = JSON.stringify(row).replace(/"/g, '&quot;');
                    let btns = `<div class="u-flex u-justify-end u-gap-sm">`;
                    if(row.employee_pk) {
                         btns += `<button class="u-btn u-btn--xs u-btn--ghost" onclick="window.openDetailModal(${rowData})" title="Detail"><i class="fas fa-eye"></i></button>`;
                    }
                    if(CAN_UPDATE && (row.user_id || {{ $canCreate ? 'true' : 'false' }})) {
                         btns += `<button class="u-btn u-btn--xs u-btn--outline" onclick="window.openEditModal(${rowData})" title="Edit Access"><i class="fas fa-user-lock"></i></button>`;
                    }
                    if(CAN_DELETE && row.user_id) {
                         btns += `<button class="u-btn u-btn--xs u-btn--danger-outline" onclick="window.deleteUser(${row.user_id})" title="Delete User"><i class="fas fa-trash"></i></button>`;
                    }
                    btns += `</div>`;
                    return btns;
                }
            }
        ],
        order: [[0, 'asc']],
        language: { search: "", searchPlaceholder: "Search users...", lengthMenu: "_MENU_", paginate: { first: "«", last: "»", next: "›", previous: "‹" } },
        drawCallback: function() {
            const wrapper = $(this.api().table().container());
            wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
            wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
            const p = wrapper.find('.dataTables_paginate .paginate_button');
            p.addClass('u-btn u-btn--sm u-btn--ghost');
            p.filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
            p.filter('.disabled').addClass('u-disabled').css('opacity', '0.5');
        }
    });

    // Auto-responsive: adjust table on window resize
    let resizeTimer;
    const handleResize = () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (usersTable && usersTable.columns) {
                usersTable.columns.adjust();
                if (usersTable.responsive) usersTable.responsive.recalc();
            }
        }, 150);
    };
    window.addEventListener('resize', handleResize);

    const shell = document.querySelector('.u-card[data-store-url]');
    const STORE_URL = shell?.dataset.storeUrl || '';
    const UPDATE_BASE = shell?.dataset.updateUrlBase || '';

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
        if(window.toggleAllRoles) window.toggleAllRoles(false);
        const firstTab = document.querySelector('#editModalTabs .u-tab');
        if(firstTab) window.switchEditTab('identity', firstTab);
        window.openModal('#editModal');
    };

    window.openEditModal = function(data) {
        const form = document.getElementById('editForm');
        form.reset();
        document.getElementById('i_name').value = data.user_name || data.full_name || '';
        document.getElementById('i_email').value = data.user_email || data.employee_email || '';
        document.getElementById('i_unit').value = data.user_unit_id || '';
        document.getElementById('f_employee_id').value = data.employee_id || '';
        if(window.toggleAllRoles) window.toggleAllRoles(false);
        (data.role_ids || []).forEach(id => {
            const cb = document.querySelector(`#rolesChecklist input[value="${id}"]`);
            if(cb) cb.checked = true;
        });
        if(data.user_id) {
            form.action = `${UPDATE_BASE}/${data.user_id}`;
            form.querySelector('input[name="_method"]').value = 'PUT';
            document.getElementById('editTitle').textContent = 'Edit User';
        } else {
            form.action = STORE_URL;
            form.querySelector('input[name="_method"]').value = 'POST';
            document.getElementById('editTitle').textContent = 'Create User';
        }
        const firstTab = document.querySelector('#editModalTabs .u-tab');
        if(firstTab) window.switchEditTab('identity', firstTab);
        window.openModal('#editModal');
    };

    // Intercept form submit for AJAX + SweetAlert2
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = editForm.querySelector('#submitEdit');
            const isUpdate = editForm.querySelector('input[name="_method"]').value === 'PUT';
            
            // Disable form
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
            editForm.style.pointerEvents = 'none';
            
            try {
                const formData = new FormData(editForm);
                
                // Collect selected roles
                const roleInputs = editForm.querySelectorAll('#rolesChecklist input:checked');
                roleInputs.forEach((input, idx) => {
                    formData.append(`roles[${idx}]`, input.value);
                });
                
                const response = await fetch(editForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const result = await response.json().catch(() => ({}));
                
                if (response.ok) {
                    showSuccess(
                        isUpdate ? 'User berhasil diupdate' : 'User berhasil dibuat',
                        'Berhasil'
                    );
                    window.closeModal('#editModal');
                    editForm.reset();
                    // DT reload - no page refresh
                    usersTable.ajax.reload(null, false);
                } else {
                    throw new Error(result.message || 'Gagal menyimpan user');
                }
            } catch (err) {
                showError(
                    err.message || 'Terjadi kesalahan saat menyimpan user',
                    'Gagal'
                );
            } finally {
                // Re-enable form
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save';
                editForm.style.pointerEvents = '';
            }
        });
    }

    window.deleteUser = function(id) {
        showDeleteConfirm(
            'User account akan dihapus permanen',
            'Hapus User?'
        ).then((result) => {
            if (!result.isConfirmed) return;
            
            // Show loading toast
            showLoading('Menghapus user...');
            
            fetch(`${UPDATE_BASE}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({_method: 'DELETE'})
            })
            .then(res => {
                if(!res.ok) throw new Error(res.statusText);
                return res.json();
            })
            .then(() => {
                showSuccess('User berhasil dihapus', 'Berhasil');
                // DT reload - no page refresh
                usersTable.ajax.reload(null, false);
            })
            .catch(err => {
                showError('Gagal menghapus user: ' + err.message, 'Gagal');
            });
        });
    };

    window.openDetailModal = function(d) {
        if(!d.employee_pk) return;
        document.getElementById('empName').textContent = d.full_name;
        document.getElementById('empId').textContent = d.employee_id ? `ID: ${d.employee_id}` : 'External';
        document.getElementById('empCompany').textContent = d.company_name || '—';
        const wrapper = document.getElementById('empPhotoWrapper');
        let name = d.full_name || '?';
        let initials = name.replace(/[^a-zA-Z\s]/g, '').match(/\b\w/g) || [];
        initials = ((initials.shift() || '') + (initials.pop() || '')).toUpperCase();
        if(!initials) initials = name.substring(0, 2).toUpperCase();
        if(d.person_photo){
            wrapper.innerHTML = `<div id="empPhoto" class="u-avatar u-avatar--lg is-interactive" onclick="window.viewPhoto('${d.person_photo}', '${name}')" style="background-image: url('${d.person_photo}')"></div>`;
        } else {
            wrapper.innerHTML = `<div id="empPhoto" class="u-avatar u-avatar--lg"><span class="u-avatar-initial" id="empInitial">${initials}</span></div>`;
        }
        
        const rowStyle = 'display:flex; justify-content:space-between; align-items:flex-start; gap:1.5rem; margin-bottom:0.75rem;';
        const lblStyle = 'flex-shrink:0; width:130px; font-size:0.875rem; color:var(--text-muted);';
        const valStyle = 'flex:1; text-align:right; font-weight:500; word-break:break-word;';
        document.getElementById('ov-left').innerHTML = `
            <div style="${rowStyle}"><span style="${lblStyle}">Job Title</span><span style="${valStyle}">${d.job_title||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Unit</span><span style="${valStyle}">${d.unit_name||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Directorate</span><span style="${valStyle}">${d.directorate_name||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Start Date</span><span style="${valStyle}">${_fmtDate(d.latest_jobs_start_date)}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Status</span><span style="flex:1; text-align:right"><span class="u-badge u-badge--glass">${d.employee_status||'—'}</span></span></div>
        `;
        document.getElementById('ov-right').innerHTML = `
            <div style="${rowStyle}"><span style="${lblStyle}">Email</span><span style="${valStyle}">${d.employee_email||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Phone</span><span style="${valStyle}">${d.phone||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Location</span><span style="${valStyle}">${d.location_city||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Talent</span><span style="${valStyle}">${d.talent_class_level||'—'}</span></div>
            <div style="${rowStyle}"><span style="${lblStyle}">Company</span><span style="${valStyle}">${d.company_name||'—'}</span></div>
        `;
        const lists = ['brevet-list', 'job-list', 'task-list', 'asg-list', 'edu-list', 'train-list', 'doc-list'];
        lists.forEach(id => document.getElementById(id).innerHTML = '<div class="u-p-md u-text-center u-muted u-flex u-flex-col u-items-center u-gap-sm"><div class="u-dt-liquid-spinner" style="width: 40px; height: 40px;"><div class="drop"></div><div class="drop"></div><div class="drop"></div></div><div class="u-text-xs">Memuat data...</div></div>');
        const firstTab = document.querySelector('#detailModalTabs .u-tab');
        if(firstTab) window.switchDetailTab('ov', firstTab);
        window.openModal('#empModal');

        fetch(`${UPDATE_BASE}/${d.employee_pk}`, {headers: {'Accept': 'application/json'}})
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if(!data) throw new Error('No Data');
                _renderList('brevet-list', data.brevet_list, (v)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${v.title || 'Brevet'}</h4><p class="u-text-xs u-muted">${v.organization || 'Organization'}</p></div><span class="u-badge u-badge--glass u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(v.start_date, v.end_date)}</span></div></div>`);
                _renderList('job-list', data.job_histories, (j)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${j.title || 'Position'}</h4><p class="u-text-xs u-muted">${j.unit_name || j.organization || ''}</p></div><span class="u-badge u-badge--primary u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(j.start_date, j.end_date)}</span></div></div>`);
                _renderList('task-list', data.taskforces, (t)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${t.title}</h4><p class="u-text-xs u-muted">${t.organization}</p></div><span class="u-badge u-badge--glass u-shrink-0" style="white-space:nowrap; width:fit-content">${_yearRange(t.start_date, t.end_date)}</span></div></div>`);
                _renderList('asg-list', data.assignments, (a)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start"><div><h4 class="u-font-semibold u-text-sm">${a.title}</h4><p class="u-text-xs u-muted">${a.description}</p></div></div></div>`);
                _renderList('edu-list', data.educations, (e)=> { let meta = {}; try { meta = typeof e.meta === 'string' ? JSON.parse(e.meta) : (e.meta || {}); } catch(_){} return `<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${meta.level||e.level||'Education'} ${meta.major?'— '+meta.major:''}</h4><p class="u-text-xs u-muted">${e.organization || 'Institution'}</p></div><span class="u-badge u-badge--glass u-shrink-0">Grad: ${_year(e.start_date)}</span></div></div>`; });
                _renderList('train-list', data.trainings, (t)=>`<div class="u-item"><div class="u-flex u-justify-between u-items-start u-gap-md"><div class="u-flex-1 u-min-w-0"><h4 class="u-font-semibold u-text-sm">${t.title}</h4><p class="u-text-xs u-muted">${t.organization||'Provider'}</p></div><span class="u-badge u-badge--primary u-shrink-0">${_fmtDate(t.start_date)}</span></div></div>`);
                _renderList('doc-list', data.documents, (d)=>`<div class="u-item u-flex u-justify-between u-items-center"><div class="u-flex-1 u-mr-md u-min-w-0"><h4 class="u-font-semibold u-text-sm">${d.final_title || 'Untitled'}</h4><p class="u-text-xs u-muted">${d.doc_type}</p></div><div class="u-flex u-flex-col u-items-end u-gap-xs u-shrink-0">${d.meta_due_date ? `<div class="u-text-xs u-muted">Due: ${_fmtDate(d.meta_due_date)}</div>` : ''}${d.url?`<a href="${d.url}" target="_blank" class="u-btn u-btn--xs u-btn--outline">View</a>`:''}</div></div>`);
            })
            .catch(() => lists.forEach(id => document.getElementById(id).innerHTML = '<div class="u-empty u-text-xs">No data available</div>'));
    };

    function _renderList(id, data, tpl){ const el = document.getElementById(id); if(!data || !data.length) { el.innerHTML = '<div class="u-empty u-text-xs">No records found.</div>'; return; } el.innerHTML = data.map(tpl).join(''); }
    function _fmtDate(s){ if(!s) return '—'; try{ const d = new Date(s); if(isNaN(d.getTime())) return s; return d.toLocaleDateString('en-GB', {year:'numeric', month:'short', day:'numeric'}); }catch(_){return s} }
    function _year(s){ return s ? String(s).substring(0,4) : '—'; }
    function _yearRange(s, e) { let start = _fmtDate(s); let end = e ? _fmtDate(e) : ''; if(s && s.endsWith('-01-01')) start = _year(s); if(e && e.endsWith('-12-31')) end = _year(e); return !end ? start : `${start} - ${end}`; }
});
</script>
@endpush