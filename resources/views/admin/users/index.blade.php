@extends('layouts.app')
@section('title','Settings · Users')

@section('content')
<div class="u-card u-card--glass u-hover-lift" data-roles-url="{{ route('admin.roles.options') }}">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">User Management</h2>
    @can('users.create')
    <button type="button" class="u-btn u-btn--brand u-hover-lift" data-modal-open="createUserModal">
      <i class="fas fa-plus u-mr-xs"></i> Add User
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
      <table id="users-table" class="u-table">
        <thead>
          <tr>
            <th>Name</th>
            <th class="u-hide-mobile">Email</th>
            <th>Roles</th>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-avatar u-avatar--sm u-avatar--brand">
                  {{ substr($u->name, 0, 1) }}
                </div>
                <span class="u-font-medium">{{ $u->name }}</span>
              </div>
            </td>
            <td class="u-hide-mobile">{{ $u->email }}</td>
            <td>
              <div class="u-flex u-flex-wrap u-gap-xs">
                @foreach($u->roles as $role)
                <span class="u-badge u-badge--primary">
                  {{ $role->name }}
                </span>
                @endforeach
                @if($u->roles->isEmpty())
                <span class="u-text-sm u-muted">—</span>
                @endif
              </div>
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                @can('users.update')
                <button
                  type="button"
                  class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                  data-modal-open="editUserModal"
                  data-id="{{ $u->id }}"
                  data-name="{{ $u->name }}"
                  data-email="{{ $u->email }}"
                  data-roles="{{ $u->roles->pluck('id')->implode(',') }}"
                  title="Edit User"
                >
                  <i class="fas fa-edit u-mr-xs"></i> Edit
                </button>
                @endcan

                @can('users.delete')
                <form method="post" action="{{ route('admin.users.destroy', $u) }}" class="u-inline">
                  @csrf @method('delete')
                  <button
                    type="submit"
                    class="u-btn u-btn--outline u-btn--sm u-hover-lift u-error"
                    onclick="return confirm('Delete this user?')"
                    title="Delete User"
                  >
                    <i class="fas fa-trash u-mr-xs"></i> Delete
                  </button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="u-text-center u-py-xl u-muted">
              <i class="fas fa-users u-empty__icon"></i>
              <p>No users found</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="u-flex u-items-center u-justify-between u-mt-lg">
    <div class="u-text-sm u-muted">
      Showing {{ $users->count() }} of {{ $users->total() }} users
    </div>
    <div class="u-hidden">{{ $users->links() }}</div>
  </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-user-plus'></i>
        </div>
        <div>
          <div class="u-title">Create New User</div>
          <div class="u-muted u-text-sm">Add a new user to the system</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <form method="post" action="{{ route('admin.users.store') }}" class="u-modal__body u-grid u-gap-md u-p-md" id="createUserForm">
      @csrf
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Full Name</label>
        <input name="name" required class="u-input" placeholder="Enter user's full name">
      </div>
      
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Email Address</label>
        <input type="email" name="email" required class="u-input" placeholder="Enter email address">
      </div>
      
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Password</label>
        <input type="password" name="password" required class="u-input" placeholder="Enter password">
        <p class="u-text-xs u-muted u-mt-xs">Minimum 8 characters</p>
      </div>

      <div class="u-grid-col-span-2">
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Assign Roles</label>
        <div class="u-grid u-grid-cols-1 u-gap-sm u-max-h-48 u-overflow-y-auto u-p-sm u-border u-rounded-lg" id="createUserRoles">
          <!-- Roles will be loaded here -->
        </div>
      </div>
    </form>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
        <button form="createUserForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Create User
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand">
          <i class='fas fa-user-edit'></i>
        </div>
        <div>
          <div id="editUserName" class="u-title">Edit User</div>
          <div class="u-muted u-text-sm" id="editUserId">ID: </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <form id="editUserForm" method="post" class="u-modal__body u-grid u-gap-md u-p-md">
      @csrf @method('put')
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Full Name</label>
        <input name="name" required class="u-input" placeholder="Enter user's full name">
      </div>
      
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Email Address</label>
        <input type="email" name="email" required class="u-input" placeholder="Enter email address">
      </div>
      
      <div>
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Password</label>
        <input type="password" name="password" class="u-input" placeholder="Leave blank to keep current password">
        <p class="u-text-xs u-muted u-mt-xs">Only enter if you want to change the password</p>
      </div>

      <div class="u-grid-col-span-2">
        <label class="u-block u-text-sm u-font-medium u-mb-sm">Assign Roles</label>
        <div class="u-grid u-grid-cols-1 u-gap-sm u-max-h-48 u-overflow-y-auto u-p-sm u-border u-rounded-lg" id="editUserRoles">
          <!-- Roles will be loaded here -->
        </div>
      </div>
    </form>

    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
        <button form="editUserForm" class="u-btn u-btn--brand u-hover-lift">
          <i class='fas fa-save u-mr-xs'></i> Update User
        </button>
      </div>
    </div>
  </div>
</div>

<script>
window.__INITIAL_ROLES__ = @json(($roles ?? collect())->map(function($r) {
  return ['id' => $r->id, 'name' => $r->name];
})->values());

// Users JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const userManager = {
    init: function() {
      this.bindModalEvents();
      this.bindRoleLoading();
    },
    
    bindModalEvents: function() {
      // Generic modal handlers
      document.addEventListener('click', function(e) {
        const opener = e.target.closest('[data-modal-open]');
        if (opener) {
          e.preventDefault();
          this.openModal(opener.getAttribute('data-modal-open'), opener);
        }
        
        const closer = e.target.closest('[data-modal-close]');
        if (closer) {
          e.preventDefault();
          this.closeModal(closer.closest('.u-modal'));
        }
      }.bind(this));
      
      // Backdrop click
      document.querySelectorAll('.u-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
          if (e.target === this) {
            this.closeModal(this);
          }
        }.bind(this));
      });
      
      // Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const openModals = Array.from(document.querySelectorAll('.u-modal')).filter(m => !m.hidden);
          const top = openModals.pop();
          if (top) this.closeModal(top);
        }
      }.bind(this));
    },
    
    openModal: function(modalId, opener) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      
      if (modalId === 'editUserModal') {
        this.loadEditUserData(opener);
      } else if (modalId === 'createUserModal') {
        this.loadCreateUserRoles();
      }
      
      modal.hidden = false;
      document.body.classList.add('modal-open');
    },
    
    closeModal: function(modal) {
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    },
    
    bindRoleLoading: function() {
      // Role loading logic remains the same as previous implementation
      // but now uses our universal classes
    },
    
    loadEditUserData: function(opener) {
      const form = document.getElementById('editUserForm');
      const userId = opener.getAttribute('data-id') || '';
      const userName = opener.getAttribute('data-name') || '';
      const userEmail = opener.getAttribute('data-email') || '';
      const userRoles = opener.getAttribute('data-roles') || '';

      if (form) {
        form.action = "{{ url('admin/settings/access/users') }}/" + userId;
        form.querySelector('input[name=name]').value = userName;
        form.querySelector('input[name=email]').value = userEmail;
        form.querySelector('input[name=password]').value = '';
      }

      // Update modal header
      document.getElementById('editUserName').textContent = userName;
      document.getElementById('editUserId').textContent = 'ID: ' + userId;

      // Load roles
      this.loadUserRoles('editUserRoles', userId, userRoles);
    },
    
    loadCreateUserRoles: function() {
      this.loadUserRoles('createUserRoles');
    },
    
    loadUserRoles: function(containerId, userId, currentRoles = '') {
      const container = document.getElementById(containerId);
      if (!container) return;
      
      const rolesArray = currentRoles.split(',').map(s => s.trim()).filter(Boolean);
      const roles = Array.isArray(window.__INITIAL_ROLES__) ? window.__INITIAL_ROLES__ : [];
      
      this.renderRoles(container, roles, rolesArray);
    },
    
    renderRoles: function(container, roles, assignedIds) {
      const picked = (assignedIds || []).map(String);
      container.innerHTML = roles.map(role => `
        <label class="u-flex u-items-center u-gap-md u-p-sm u-rounded-lg u-border u-border-gray-200 u-hover:border-blue-300 u-transition-colors">
          <input type="checkbox" name="roles[]" value="${role.id}" ${picked.includes(String(role.id)) ? 'checked' : ''} 
                 class="u-rounded u-border-gray-300 u-text-blue-600 u-focus:ring-blue-500">
          <span class="u-text-sm u-font-medium">${role.name}</span>
        </label>
      `).join('');
    }
  };
  
  userManager.init();
});
</script>
@endsection