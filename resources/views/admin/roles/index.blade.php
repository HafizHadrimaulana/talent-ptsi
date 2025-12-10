@extends('layouts.app')
@section('title','Settings · Roles')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Role Management</h2>
    @can('rbac.assign')
    <button class="u-btn u-btn--brand u-hover-lift" data-modal-open="createRoleModal">
      <i class="fas fa-plus u-mr-xs"></i> Add Role
    </button>
    @endcan
  </div>

  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='fas fa-check-circle u-success-icon'></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class='fas fa-exclamation-circle u-error-icon'></i>
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
      <table id="roles-table" class="u-table">
        <thead>
          <tr>
            <th>Role Name</th>
            <th>Users</th>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($roles as $r)
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-badge u-badge--primary">
                  <i class='fas fa-user-shield u-text-xs u-mr-xs'></i> Role
                </div>
                <span class="u-font-medium">{{ $r->name }}</span>
                @if(!is_null($r->unit_id))
                  <span class="u-badge u-badge--glass">unit: {{ $r->unit_id }}</span>
                @else
                  <span class="u-badge u-badge--glass">global</span>
                @endif
              </div>
            </td>
            <td><span class="u-badge u-badge--glass">{{ $r->users_count }} users</span></td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                        data-modal-open="editRoleModal"
                        data-role='@json([
                          "id"=>$r->id,
                          "name"=>$r->name,
                          "perms"=>$r->permissions()->pluck("name")->all()
                        ])'>
                  <i class="fas fa-edit u-mr-xs"></i> Edit
                </button>
                @can('rbac.delete')
                <form method="post" action="{{ route('admin.roles.destroy',$r) }}" class="u-inline">
                  @csrf @method('delete')
                  <button class="u-btn u-btn--outline u-btn--sm u-hover-lift u-error"
                          onclick="return confirm('Delete this role?')">
                    <i class="fas fa-trash u-mr-xs"></i> Delete
                  </button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Role Modal -->
<div id="createRoleModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class='fas fa-user-shield'></i></div>
        <div>
          <div class="u-title">Create New Role</div>
          <div class="u-muted u-text-sm">Add a new role to the system</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class='fas fa-times'></i></button>
    </div>

    <form method="post" action="{{ route('admin.roles.store') }}" id="createRoleForm">
      @csrf
      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#role-create-basic">Basic Info</button>
            <button class="u-tab" type="button" data-target="#role-create-perms">Permissions</button>
          </div>
        </div>

        <div class="u-panels">
          <div class="u-panel is-active" id="role-create-basic">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Role Name</label>
                <input name="name" required class="u-input" placeholder="Enter role name">
                <p class="u-text-xs u-muted u-mt-xs">Use lowercase with dots (e.g., access.manager)</p>
              </div>
            </div>
          </div>

          <div class="u-panel" id="role-create-perms">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md">
                <h4 class="u-font-semibold">Assign Permissions</h4>
                <div class="u-flex u-gap-xs">
                  <button class="u-btn u-btn--outline u-btn--sm" type="button" data-check="all">Select All</button>
                  <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-check="none">None</button>
                </div>
              </div>

              @if($groupedPerms->isEmpty())
                <div class="u-empty"><p class="u-muted">No permissions found.</p></div>
              @else
              <div class="u-space-y-md u-max-h-96 u-overflow-y-auto">
                @foreach($groupedPerms as $group => $items)
                <div class="u-card u-p-md">
                  <div class="u-flex u-items-center u-justify-between u-mb-sm">
                    <div class="u-font-semibold u-text-sm u-uppercase u-tracking-wide u-muted">{{ $group }}</div>
                    <div class="u-flex u-gap-xs">
                      <button class="u-btn u-btn--outline u-btn--xs" type="button" data-group-check="{{ $group }}" data-mode="all">All</button>
                      <button class="u-btn u-btn--ghost u-btn--xs" type="button" data-group-check="{{ $group }}" data-mode="none">None</button>
                    </div>
                  </div>
                  <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm">
                    @foreach($items as $perm)
                    <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
                      <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="u-rounded" data-group="{{ $group }}">
                      <span class="u-text-sm">{{ $perm->name }}</span>
                    </label>
                    @endforeach
                  </div>
                </div>
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
          <button class="u-btn u-btn--brand u-hover-lift"><i class='fas fa-save u-mr-xs'></i> Create Role</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class='fas fa-edit'></i></div>
        <div>
          <div id="editRoleName" class="u-title">Edit Role</div>
          <div class="u-muted u-text-sm" id="editRoleId">ID: </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class='fas fa-times'></i></button>
    </div>

    <form id="editRoleForm" method="post">
      @csrf @method('put')
      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#role-edit-basic">Basic Info</button>
            <button class="u-tab" type="button" data-target="#role-edit-perms">Permissions</button>
          </div>
        </div>

        <div class="u-panels">
          <div class="u-panel is-active" id="role-edit-basic">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Role Name</label>
                <input name="name" required class="u-input" placeholder="Enter role name">
              </div>
            </div>
          </div>

          <div class="u-panel" id="role-edit-perms">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md">
                <h4 class="u-font-semibold">Assigned Permissions</h4>
                <div class="u-flex u-gap-xs">
                  <button class="u-btn u-btn--outline u-btn--sm" type="button" data-check="all">Select All</button>
                  <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-check="none">None</button>
                </div>
              </div>

              <div class="u-space-y-md u-max-h-96 u-overflow-y-auto" id="editRolePermsWrap">
                @foreach($groupedPerms as $group => $items)
                <div class="u-card u-p-md">
                  <div class="u-flex u-items-center u-justify-between u-mb-sm">
                    <div class="u-font-semibold u-text-sm u-uppercase u-tracking-wide u-muted">{{ $group }}</div>
                    <div class="u-flex u-gap-xs">
                      <button class="u-btn u-btn--outline u-btn--xs" type="button" data-group-check="{{ $group }}" data-mode="all">All</button>
                      <button class="u-btn u-btn--ghost u-btn--xs" type="button" data-group-check="{{ $group }}" data-mode="none">None</button>
                    </div>
                  </div>
                  <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm">
                    @foreach($items as $perm)
                    <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
                      <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="u-rounded" data-group="{{ $group }}">
                      <span class="u-text-sm">{{ $perm->name }}</span>
                    </label>
                    @endforeach
                  </div>
                </div>
                @endforeach
              </div>

            </div>
          </div>
        </div>
      </div>

      <div class="u-modal__foot">
        <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift"><i class='fas fa-save u-mr-xs'></i> Update Role</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const $ = (q, root=document) => root.querySelector(q);
  const $$ = (q, root=document) => [...root.querySelectorAll(q)];

  function wireTabs(modalEl){
    const tabs = $$('.u-tab[data-target]', modalEl);
    if(!tabs.length) return;
    tabs.forEach(tab=>{
      tab.addEventListener('click', ()=>{
        const targetSel = tab.getAttribute('data-target');
        const targetEl  = $(targetSel, modalEl);
        if(!targetEl) return;

        tabs.forEach(t=>t.classList.remove('is-active'));
        tab.classList.add('is-active');

        $$('.u-panels .u-panel', modalEl).forEach(p=>p.classList.remove('is-active'));
        targetEl.classList.add('is-active');
      });
    });
  }

  function bindModalOpenClose() {
    document.addEventListener('click', (e) => {
      const openCreate = e.target.closest('[data-modal-open="createRoleModal"]');
      if (openCreate) { $('#createRoleModal').hidden = false; document.body.classList.add('modal-open'); }

      const openEdit = e.target.closest('[data-modal-open="editRoleModal"]');
      if (openEdit) {
        const data = JSON.parse(openEdit.dataset.role);
        openEditModal(data);
      }

      const closeBtn = e.target.closest('[data-modal-close]');
      if (closeBtn) {
        const modal = closeBtn.closest('.u-modal');
        modal.hidden = true; document.body.classList.remove('modal-open');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        $$('.u-modal').forEach(m=>m.hidden = true);
        document.body.classList.remove('modal-open');
      }
    });
  }

  function openEditModal(roleData){
    const modal = $('#editRoleModal');
    const form  = $('#editRoleForm');
    form.action = "{{ url('admin/settings/access/roles') }}/" + roleData.id;
    form.querySelector('input[name=name]').value = roleData.name;

    $('#editRoleName').textContent = roleData.name;
    $('#editRoleId').textContent   = 'ID: ' + roleData.id;

    const current = new Set(roleData.perms || []);
    $$('#editRolePermsWrap input[type=checkbox]').forEach(cb => cb.checked = current.has(cb.value));

    modal.hidden = false; document.body.classList.add('modal-open');
  }

  function bindPermSelectHelpers(root=document) {
    root.addEventListener('click', (e) => {
      const allBtn  = e.target.closest('[data-check="all"]');
      const noneBtn = e.target.closest('[data-check="none"]');
      if (allBtn || noneBtn) {
        const panel = e.target.closest('.u-panel');
        if (!panel) return;
        const check = !!allBtn;
        panel.querySelectorAll('input[type=checkbox][name="permissions[]"]').forEach(cb => cb.checked = check);
      }

      const grpBtn = e.target.closest('[data-group-check]');
      if (grpBtn) {
        const group = grpBtn.getAttribute('data-group-check');
        const mode  = grpBtn.getAttribute('data-mode'); // all|none
        const panel = e.target.closest('.u-panel');
        if (!panel) return;
        panel.querySelectorAll(`input[type=checkbox][data-group="${group}"]`).forEach(cb => cb.checked = (mode==='all'));
      }
    });
  }

  wireTabs(document.getElementById('createRoleModal'));
  wireTabs(document.getElementById('editRoleModal'));
  bindModalOpenClose();
  bindPermSelectHelpers(document);
});
</script>
@endsection
