@extends('layouts.app')
@section('title','Settings · Permissions')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Permission Management</h2>
    <div class="u-flex u-items-center u-gap-md">
    </div>
  </div>

  @if(session('ok')) 
    <div class="u-card u-mb-md" style="background: var(--success-bg); border-color: var(--success-border);">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='bx bx-check-circle' style="color: var(--success-color);"></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif
  
  @if($errors->any())
    <div class="u-card u-mb-md" style="background: var(--error-bg); border-color: var(--error-border);">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class='bx bx-error-circle' style="color: var(--error-color);"></i>
        <span class="font-semibold">Please fix the following errors:</span>
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
      <table id="perms-table" class="u-table">
        <thead>
          <tr>
            <th>Permission Name</th>
            <th class="u-hide-mobile">Created</th>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($permissions as $p)
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-badge u-badge--glass">
                  <i class='bx bx-lock-alt u-text-xs u-mr-xs'></i>
                  Permission
                </div>
                <span class="font-medium">{{ $p->name }}</span>
              </div>
            </td>
            <td class="u-hide-mobile u-text-sm u-muted">{{ $p->created_at->format('M j, Y') }}</td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button class="u-btn u-btn--outline u-btn--sm u-hover-lift" 
                        data-modal-open="editPermModal" 
                        data-perm='@json(["id"=>$p->id,"name"=>$p->name])'>
                  <i class='fas fa-edit u-mr-xs'></i> Edit
                </button>
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
      Showing {{ $permissions->count() }} of {{ $permissions->total() }} permissions
    </div>
    <div class="u-hidden">{{ $permissions->links() }}</div>
  </div>
</div>

<!-- Edit Permission Modal -->
<div id="editPermModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='bx bx-lock-alt'></i>
        </div>
        <div>
          <div id="permName" class="u-title">Permission Name</div>
          <div class="u-muted u-text-sm" id="permId">ID: </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='bx bx-x'></i>
      </button>
    </div>

    <div class="u-modal__body">
      <div class="u-tabs-wrap">
        <div class="u-tabs" id="permTabs">
          <button class="u-tab is-active" data-tab="edit">Edit Permission</button>
          <button class="u-tab" data-tab="roles">Assigned Roles</button>
          <button class="u-tab u-hide-mobile" data-tab="users">Users with Access</button>
        </div>
      </div>

      <div class="u-panels">
        <div class="u-panel is-active" id="tab-edit">
          <form id="editPermForm" method="post" class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
            @csrf @method('put')
            <div class="u-grid-col-span-2">
              <label class="u-block u-text-sm u-font-medium u-mb-sm">Permission Name</label>
              <input name="name" required class="u-input" placeholder="Enter permission name">
              <p class="u-text-xs u-muted u-mt-xs">Use lowercase with dots as separators (e.g., users.create, settings.update)</p>
            </div>
            <div class="u-grid-col-span-2 u-card u-p-md" style="background: var(--warning-bg); border-color: var(--warning-border);">
              <div class="u-flex u-items-start u-gap-sm">
                <i class='bx bx-info-circle' style="color: var(--warning-color);"></i>
                <div>
                  <p class="u-text-sm u-font-medium" style="color: var(--warning-text);">Important</p>
                  <p class="u-text-xs" style="color: var(--warning-text);">Changing permission names may affect system functionality.</p>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="u-panel" id="tab-roles">
          <div class="u-p-md">
            <div class="u-flex u-items-center u-justify-between u-mb-md">
              <h4 class="u-font-semibold">Roles with this Permission</h4>
              <button class="u-btn u-btn--outline u-btn--sm">
                <i class='bx bx-plus u-mr-xs'></i> Assign to Role
              </button>
            </div>
            <div class="u-list" id="roles-list">
              <div class="u-empty">
                <i class='bx bx-group u-empty__icon'></i>
                <p>No roles assigned to this permission</p>
                <p class="u-text-sm u-muted">Assign this permission to roles to control access</p>
              </div>
            </div>
          </div>
        </div>

        <div class="u-panel" id="tab-users">
          <div class="u-p-md">
            <div class="u-flex u-items-center u-justify-between u-mb-md">
              <h4 class="u-font-semibold">Users with this Permission</h4>
              <div class="u-text-sm u-muted">Through role assignments</div>
            </div>
            <div class="u-list" id="users-list">
              <div class="u-empty">
                <i class='bx bx-user u-empty__icon'></i>
                <p>No users have this permission</p>
                <p class="u-text-sm u-muted">Users will inherit this permission through their assigned roles</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
        <button form="editPermForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Update Permission
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Permissions JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const permissionManager = {
    init: function() {
      this.bindModalEvents();
      this.bindSearch();
      this.bindTabs();
    },
    
    bindModalEvents: function() {
      // Open edit modal
      document.addEventListener('click', function(e) {
        const opener = e.target.closest('[data-modal-open="editPermModal"]');
        if (!opener) return;
        
        const permData = JSON.parse(opener.dataset.perm);
        this.openEditModal(permData);
      }.bind(this));
    },
    
    openEditModal: function(permData) {
      const form = document.getElementById('editPermForm');
      form.action = "{{ url('admin/settings/access/permissions') }}/" + permData.id;
      form.querySelector('input[name=name]').value = permData.name;

      // Update modal header
      document.getElementById('permName').textContent = permData.name;
      document.getElementById('permId').textContent = 'ID: ' + permData.id;

      document.getElementById('editPermModal').hidden = false;
      document.body.classList.add('modal-open');
    },
    
    bindSearch: function() {
      const searchInput = document.getElementById('permSearchInput');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          const tableRows = document.querySelectorAll('#perms-table tbody tr');
          
          tableRows.forEach(row => {
            const permissionName = row.querySelector('td:first-child').textContent.toLowerCase();
            row.style.display = permissionName.includes(searchTerm) ? '' : 'none';
          });
        });
      }
    },
    
    bindTabs: function() {
      const tabs = document.querySelectorAll('#permTabs .u-tab');
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const targetTab = this.dataset.tab;
          
          // Update active tab
          tabs.forEach(t => t.classList.remove('is-active'));
          this.classList.add('is-active');
          
          // Show target panel
          document.querySelectorAll('.u-panel').forEach(panel => {
            panel.classList.remove('is-active');
            if (panel.id === 'tab-' + targetTab) {
              panel.classList.add('is-active');
            }
          });
        });
      });
    }
  };
  
  permissionManager.init();
});
</script>
@endsection