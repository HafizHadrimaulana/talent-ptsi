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
      <table id="users-table" class="u-table u-table-mobile">
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
                <span class="u-font-medium">{{ $u->name }}</span>
              </div>
            </td>
            <td class="u-hide-mobile">{{ $u->email }}</td>
            <td>
              <div class="u-flex u-gap-sm" style="flex-wrap: wrap">
                @foreach($u->roles as $role)
                  <span class="u-badge u-badge--primary">{{ $role->name }}</span>
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
            <td colspan="4">
              <div class="u-empty">
                <i class="fas fa-users u-empty__icon"></i>
                <div>No users found</div>
              </div>
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
    <div>{{ $users->links() }}</div>
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

    <form method="post" action="{{ route('admin.users.store') }}" class="u-modal__body u-p-md" id="createUserForm">
      @csrf
      <div class="u-grid-2 u-stack-mobile u-gap-md">
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Full Name</label>
          <input name="name" required class="u-input" placeholder="Enter user's full name">
        </div>
        
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Email Address</label>
          <input type="email" name="email" required class="u-input" placeholder="Enter email address">
        </div>
        
        <div class="u-grid-col-span-2 u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Password</label>
          <input type="password" name="password" required class="u-input" placeholder="Enter password">
          <p class="u-text-xs u-muted u-mt-xs">Minimum 8 characters</p>
        </div>

        <div class="u-grid-col-span-2 u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Assign Roles</label>
          <div class="u-list u-max-h-96 u-overflow-y-auto u-p-sm" id="createUserRoles">
            <!-- Roles will be loaded here -->
          </div>
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

    <form id="editUserForm" method="post" class="u-modal__body u-p-md">
      @csrf @method('put')
      <div class="u-grid-2 u-stack-mobile u-gap-md">
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Full Name</label>
          <input name="name" required class="u-input" placeholder="Enter user's full name">
        </div>
        
        <div class="u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Email Address</label>
          <input type="email" name="email" required class="u-input" placeholder="Enter email address">
        </div>
        
        <div class="u-grid-col-span-2 u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Password</label>
          <input type="password" name="password" class="u-input" placeholder="Leave blank to keep current password">
          <p class="u-text-xs u-muted u-mt-xs">Only enter if you want to change the password</p>
        </div>

        <div class="u-grid-col-span-2 u-space-y-sm">
          <label class="u-block u-text-sm u-font-medium u-mb-sm">Assign Roles</label>
          <div class="u-list u-max-h-96 u-overflow-y-auto u-p-sm" id="editUserRoles">
            <!-- Roles will be loaded here -->
          </div>
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

{{-- Safer JSON injection: roles payload --}}
<script type="application/json" id="rolesData">
{!! json_encode(($roles ?? collect())->map(function($r){ return ['id'=>$r->id,'name'=>$r->name]; })->values()->all(), JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // parse roles safely from embedded JSON
  var __INITIAL_ROLES__ = [];
  (function() {
    var el = document.getElementById('rolesData');
    if (el) {
      try { __INITIAL_ROLES__ = JSON.parse(el.textContent || '[]'); } catch(e) { __INITIAL_ROLES__ = []; }
    }
  })();

  var userManager = {
    init: function () {
      this.bindModalEvents();
      this.initDT();
    },

    // ===== DataTables (opsional) =====
    initDT: function () {
      if (typeof DataTable !== 'undefined') {
        new DataTable('#users-table', {
          responsive: true,
          paging: false,
          info: false,
          language: {
            search: "Cari:",
            zeroRecords: "Tidak ada data pengguna",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)"
          }
        });
      }
    },

    // ===== Modal handlers =====
    bindModalEvents: function () {
      document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-modal-open]');
        if (opener) {
          e.preventDefault();
          this.openModal(opener.getAttribute('data-modal-open'), opener);
        }
        var closer = e.target.closest('[data-modal-close]');
        if (closer) {
          e.preventDefault();
          this.closeModal(closer.closest('.u-modal'));
        }
      }.bind(this));

      // backdrop click
      Array.prototype.forEach.call(document.querySelectorAll('.u-modal'), function (modal) {
        modal.addEventListener('click', function (e) {
          if (e.target === modal) userManager.closeModal(modal);
        });
      });

      // ESC
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          var open = Array.prototype.find.call(document.querySelectorAll('.u-modal'), function (m) { return !m.hidden; });
          if (open) this.closeModal(open);
        }
      }.bind(this));
    },

    openModal: function (modalId, opener) {
      var modal = document.getElementById(modalId);
      if (!modal) return;

      if (modalId === 'editUserModal') {
        this.loadEditUserData(opener);
      } else if (modalId === 'createUserModal') {
        this.loadUserRoles('createUserRoles', null, '');
      }

      modal.hidden = false;
      document.body.classList.add('modal-open');
    },

    closeModal: function (modal) {
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    },

    // ===== Roles renderer (DOM API, tanpa template literal) =====
    loadEditUserData: function (opener) {
      var form = document.getElementById('editUserForm');
      var userId = opener.getAttribute('data-id') || '';
      var userName = opener.getAttribute('data-name') || '';
      var userEmail = opener.getAttribute('data-email') || '';
      var userRoles = opener.getAttribute('data-roles') || '';

      if (form) {
        form.action = "{{ url('admin/settings/access/users') }}/" + userId;
        form.querySelector('input[name=name]').value = userName;
        form.querySelector('input[name=email]').value = userEmail;
        form.querySelector('input[name=password]').value = '';
      }

      var titleEl = document.getElementById('editUserName');
      var idEl = document.getElementById('editUserId');
      if (titleEl) titleEl.textContent = userName || 'Edit User';
      if (idEl) idEl.textContent = 'ID: ' + userId;

      this.loadUserRoles('editUserRoles', userId, userRoles);
    },

    loadUserRoles: function (containerId, userId, currentRoles) {
      var container = document.getElementById(containerId);
      if (!container) return;

      // clear
      container.innerHTML = '';

      var picked = [];
      if (typeof currentRoles === 'string' && currentRoles.length) {
        picked = currentRoles.split(',').map(function (s) { return String(s).trim(); }).filter(Boolean);
      }

      (__INITIAL_ROLES__ || []).forEach(function (role) {
        var idStr = String(role.id);

        var label = document.createElement('label');
        label.className = 'u-item u-flex u-items-center u-gap-md';

        var input = document.createElement('input');
        input.type = 'checkbox';
        input.name = 'roles[]';
        input.value = idStr;
        if (picked.indexOf(idStr) !== -1) input.checked = true;

        var span = document.createElement('span');
        span.className = 'u-text-sm u-font-medium';
        span.textContent = role.name;

        label.appendChild(input);
        label.appendChild(span);
        container.appendChild(label);
      });
    }
  };

  userManager.init();
});
</script>
@endsection
