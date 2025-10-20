@extends('layouts.app')
@section('title','Settings · Users')
<!-- Font Awesome 6 (preferred) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


@section('content')
<div class="card glass p-4" data-roles-url="{{ route('admin.roles.options') }}">

  {{-- ====== HEADER ====== --}}
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">Manajemen User</h2>
    @can('users.create')
    <button type="button" class="btn btn-brand hover-lift" data-modal-open="createUserModal">
      <i class="fa fa-plus mr-1"></i> Tambah User
    </button>
    @endcan
  </div>

  {{-- ====== ALERTS ====== --}}
  @if(session('ok'))
    <div class="alert success mb-3">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="alert danger mb-3">
      <ul class="ml-4 list-disc">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- ====== DATATABLE CARD (Unified Layer) ====== --}}
  <div class="dt-wrapper bg-white/70 dark:bg-slate-900/60 rounded-2xl shadow-md p-4 space-y-3 ios-glass">
    <table id="users-table" class="display w-full" data-dt>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Roles</th>
          <th class="cell-actions">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $u)
        <tr>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>{{ $u->roles->pluck('name')->join(', ') ?: '-' }}</td>
          <td class="cell-actions flex items-center gap-2">
            @can('users.update')
            <button
              type="button"
              class="btn-sm hover-lift text-blue-500"
              data-modal-open="editUserModal"
              data-id="{{ $u->id }}"
              data-name="{{ $u->name }}"
              data-email="{{ $u->email }}"
              data-roles="{{ $u->roles->pluck('id')->implode(',') }}"
              title="Edit User"
              style="cursor:pointer;"
            >
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            @endcan

            @can('users.delete')
            <form method="post" action="{{ route('admin.users.destroy', $u) }}" class="inline">
              @csrf @method('delete')
              <button
                type="submit"
                class="btn-sm hover-lift text-red-500 "
                onclick="return confirm('Hapus user?')"
                title="Hapus User"
                style="cursor:pointer;"
              >
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
            @endcan
          </td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted text-center py-3">Belum ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Hidden Laravel pagination fallback --}}
  <div class="mt-3 hidden">{{ $users->links() }}</div>
</div>

{{-- ====== CREATE MODAL ====== --}}
<div id="createUserModal" class="modal" hidden>
  <div class="modal-card">
    <div class="modal-header">
      <h3>Tambah User</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form method="post" action="{{ route('admin.users.store') }}" class="modal-body grid gap-3" id="createUserForm">
      @csrf
      <label>Nama <input name="name" required class="input"></label>
      <label>Email <input type="email" name="email" required class="input"></label>
      <label>Password <input type="password" name="password" required class="input"></label>

      <div>
        <div class="font-semibold mb-1">Roles</div>
        <div class="grid grid-cols-2 gap-2" id="createUserRoles"></div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- ====== EDIT MODAL ====== --}}
<div id="editUserModal" class="modal" hidden>
  <div class="modal-card">
    <div class="modal-header">
      <h3>Edit User</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form id="editUserForm" method="post" class="modal-body grid gap-3">
      @csrf @method('put')
      <label>Nama <input name="name" required class="input"></label>
      <label>Email <input type="email" name="email" required class="input"></label>
      <label>Password (kosongkan bila tidak ganti) <input type="password" name="password" class="input"></label>

      <div>
        <div class="font-semibold mb-1">Roles</div>
        <div class="grid grid-cols-2 gap-2" id="editUserRoles"></div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Update</button>
      </div>
    </form>
  </div>
</div>

{{-- ====== INITIAL ROLE DATA ====== --}}
<script>
  window.__INITIAL_ROLES__ = @json(($roles ?? collect())->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values());
</script>

{{-- ====== MODAL + ROLE LOADER SCRIPT ====== --}}
<script>
(() => {
  const body = document.body;
  const rolesURL = document.querySelector('.card.glass.p-4')?.dataset.rolesUrl || null;

  const tplRole = (r, checked=false) => `
    <label class="chip">
      <input type="checkbox" name="roles[]" value="${r.id}" ${checked?'checked':''}>
      ${r.name}
    </label>
  `;

  const renderRoles = (container, roles, assignedIds=[]) => {
    const picked = (assignedIds || []).map(String);
    container.innerHTML = roles.map(r => tplRole(r, picked.includes(String(r.id)))).join('');
  };

  const fetchRoles = async (userId=null) => {
    if (!rolesURL) {
      return { roles: Array.isArray(window.__INITIAL_ROLES__) ? window.__INITIAL_ROLES__ : [], assigned: [] };
    }
    const url = new URL(rolesURL, window.location.origin);
    if (userId) url.searchParams.set('user_id', userId);
    const res = await fetch(url.toString(), { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if (!res.ok) throw new Error('Failed to load roles');
    return res.json(); // {roles:[{id,name}], assigned:[ids]}
  };

  const openModal = (modal) => {
    if (!modal) return;
    modal.hidden = false;
    body.style.overflow = 'hidden';
    const first = modal.querySelector('input,select,textarea,button:not([data-modal-close])');
    first?.focus({preventScroll:true});
  };
  const closeModal = (modal) => {
    if (!modal) return;
    modal.hidden = true;
    body.style.overflow = '';
  };

  // OPEN
  document.addEventListener('click', async (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (!opener) return;

    e.preventDefault();
    e.stopPropagation();

    const id = opener.getAttribute('data-modal-open');
    const modal = document.getElementById(id);
    if (!modal) return;

    if (id === 'createUserModal') {
      const container = document.getElementById('createUserRoles');
      if (container) {
        try {
          const { roles } = await fetchRoles();
          renderRoles(container, roles, []);
        } catch {
          renderRoles(container, (window.__INITIAL_ROLES__||[]), []);
        }
      }
    }

    if (id === 'editUserModal') {
      const form = document.getElementById('editUserForm');
      const uid   = opener.getAttribute('data-id') || '';
      const name  = opener.getAttribute('data-name') || '';
      const email = opener.getAttribute('data-email') || '';
      const rolesCsv = opener.getAttribute('data-roles') || '';
      const rolesArr = rolesCsv.split(',').map(s => s.trim()).filter(Boolean);

      if (form) {
        form.action = "{{ url('admin/settings/access/users') }}/" + uid;
        form.querySelector('input[name=name]').value = name;
        form.querySelector('input[name=email]').value = email;
        form.querySelector('input[name=password]').value = '';
      }

      const container = document.getElementById('editUserRoles');
      if (container) {
        try {
          const { roles, assigned } = await fetchRoles(uid);
          const assignedIds = (assigned && assigned.length) ? assigned : rolesArr;
          renderRoles(container, roles, assignedIds);
        } catch {
          renderRoles(container, (window.__INITIAL_ROLES__||[]), rolesArr);
        }
      }
    }

    openModal(modal);
  }, true);

  // CLOSE
  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-modal-close]');
    if (!closer) return;
    e.preventDefault();
    e.stopPropagation();
    closeModal(closer.closest('.modal'));
  }, true);

  // BACKDROP
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
  });

  // ESC
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const openModals = Array.from(document.querySelectorAll('.modal')).filter(m => !m.hidden);
    const top = openModals.pop();
    if (top) closeModal(top);
  });
})();
</script>
@endsection
