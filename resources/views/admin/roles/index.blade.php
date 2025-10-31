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
                  <i class='fas fa-user-shield u-text-xs u-mr-xs'></i>
                  Role
                </div>
                <span class="u-font-medium">{{ $r->name }}</span>
              </div>
            </td>
            <td>
              <span class="u-badge u-badge--glass">
                {{ $r->users_count }} users
              </span>
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" 
                        data-modal-open="editRoleModal"
                        data-role='@json(["id"=>$r->id,"name"=>$r->name,"perms"=>$r->permissions()->pluck("name")->all()])'>
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

  <div class="u-flex u-items-center u-justify-between u-mt-lg">
    <div class="u-text-sm u-muted">
      Showing {{ $roles->count() }} of {{ $roles->total() }} roles
    </div>
    <div class="u-hidden">{{ $roles->links() }}</div>
  </div>
</div>

<!-- Create Role Modal -->
<div id="createRoleModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-user-shield'></i>
        </div>
        <div>
          <div class="u-title">Create New Role</div>
          <div class="u-muted u-text-sm">Add a new role to the system</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="createRoleTabs">
          <button class="u-tab is-active" data-tab="basic">Basic Info</button>
          <button class="u-tab" data-tab="permissions">Permissions</button>
        </div>
      </div>

      <div class="u-panels">
        <div class="u-panel is-active" id="tab-basic">
          <form method="post" action="{{ route('admin.roles.store') }}" class="u-grid-2 u-stack-mobile u-gap-md u-p-md" id="createRoleForm">
            @csrf
            <div class="u-grid-col-span-2">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Role Name</label>
              <input name="name" required class="u-input" placeholder="Enter role name">
              <p class="u-text-xs u-muted u-mt-xs">Use lowercase with dots as separators (e.g., admin.users, content.editor)</p>
            </div>
          </form>
        </div>

        <div class="u-panel" id="tab-permissions">
          <div class="u-p-md">
            <div class="u-flex u-items-center u-justify-between u-mb-md">
              <h4 class="u-font-semibold">Assign Permissions</h4>
              <div class="u-text-sm u-muted">{{ $groupedPerms->flatten()->count() }} permissions available</div>
            </div>
            
            <div class="u-space-y-md u-max-h-96 u-overflow-y-auto">
              @foreach($groupedPerms as $group => $items)
              <div class="u-card u-p-md">
                <div class="u-font-semibold u-mb-sm u-text-sm u-uppercase u-tracking-wide u-muted">{{ $group }}</div>
                <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm">
                  @foreach($items as $perm)
                  <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="u-rounded">
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
        <button form="createRoleForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Create Role
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-edit'></i>
        </div>
        <div>
          <div id="editRoleName" class="u-title">Edit Role</div>
          <div class="u-muted u-text-sm" id="editRoleId">ID: </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="editRoleTabs">
          <button class="u-tab is-active" data-tab="edit-basic">Basic Info</button>
          <button class="u-tab" data-tab="edit-permissions">Permissions</button>
        </div>
      </div>

      <div class="u-panels">
        <div class="u-panel is-active" id="tab-edit-basic">
          <form id="editRoleForm" method="post" class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
            @csrf @method('put')
            <div class="u-grid-col-span-2">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Role Name</label>
              <input name="name" required class="u-input" placeholder="Enter role name">
            </div>
          </form>
        </div>

        <div class="u-panel" id="tab-edit-permissions">
          <div class="u-p-md">
            <div class="u-flex u-items-center u-justify-between u-mb-md">
              <h4 class="u-font-semibold">Assigned Permissions</h4>
              <div class="u-text-sm u-muted">{{ $groupedPerms->flatten()->count() }} permissions available</div>
            </div>
            
            <div class="u-space-y-md u-max-h-96 u-overflow-y-auto" id="editRolePermsWrap">
              @foreach($groupedPerms as $group => $items)
              <div class="u-card u-p-md">
                <div class="u-font-semibold u-mb-sm u-text-sm u-uppercase u-tracking-wide u-muted">{{ $group }}</div>
                <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm">
                  @foreach($items as $perm)
                  <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="u-rounded">
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
        <button form="editRoleForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Update Role
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Roles JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const roleManager = {
    init: function() {
      this.bindModalEvents();
      this.bindTabs();
    },
    
    bindModalEvents: function() {
      // Open edit modal
      document.addEventListener('click', function(e) {
        const opener = e.target.closest('[data-modal-open="editRoleModal"]');
        if (!opener) return;
        
        const roleData = JSON.parse(opener.dataset.role);
        this.openEditModal(roleData);
      }.bind(this));
    },
    
    openEditModal: function(roleData) {
      const form = document.getElementById('editRoleForm');
      form.action = "{{ url('admin/settings/access/roles') }}/" + roleData.id;
      form.querySelector('input[name=name]').value = roleData.name;

      // Update modal header
      document.getElementById('editRoleName').textContent = roleData.name;
      document.getElementById('editRoleId').textContent = 'ID: ' + roleData.id;

      // Update permissions checkboxes
      const current = new Set(roleData.perms || []);
      form.querySelectorAll('#editRolePermsWrap input[type=checkbox]').forEach(cb => {
        cb.checked = current.has(cb.value);
      });

      document.getElementById('editRoleModal').hidden = false;
      document.body.classList.add('modal-open');
    },
    
    bindTabs: function() {
      // Initialize tabs for both modals
      this.initTabs('createRoleTabs');
      this.initTabs('editRoleTabs');
    },
    
    initTabs: function(containerId) {
      const tabs = document.querySelectorAll(`#${containerId} .u-tab`);
      const panels = document.querySelectorAll(`#${containerId} ~ .u-panels .u-panel`);
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const targetTab = this.dataset.tab;
          
          // Update active tab
          tabs.forEach(t => t.classList.remove('is-active'));
          this.classList.add('is-active');
          
          // Show target panel
          panels.forEach(panel => {
            panel.classList.remove('is-active');
            if (panel.id === 'tab-' + targetTab) {
              panel.classList.add('is-active');
            }
          });
        });
      });
    }
  };
  
  roleManager.init();
});
</script>
@endsection