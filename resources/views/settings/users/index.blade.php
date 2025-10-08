@extends('layouts.app')
@section('title','Settings · Users')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

@section('content')
{{-- NOTE: data-roles-url dipakai JS untuk load roles secara dinamis --}}
<div class="card glass p-4" data-roles-url="{{ route('settings.roles.options') }}">
  <div class="flex justify-between items-center gap-2 mb-3">
    <form method="get" class="flex gap-2" id="searchForm">
      <input name="q" value="{{ $q }}" placeholder="Cari nama/email" class="input" />
      <button class="btn btn-outline hover-lift" type="submit" id="btnSearch">Cari</button>
    </form>
    @can('users.create')
    <button type="button" class="btn btn-brand hover-lift" data-modal-open="createUserModal">+ Tambah User</button>
    @endcan
  </div>

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

  <div class="dt-card ios-glass">
     <button type="button" class="btn btn-brand hover-lift" data-modal-open="createUserModal">Import</button>
     <button type="button" class="btn btn-brand hover-lift" data-modal-open="createUserModal">Export</button>
    <table id="users-table" class="display" data-dt>
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
          <td class="cell-actions">
          @can('users.update')
          <button
            type="button"
            class="btn-icon hover-lift text-blue-500"
            data-modal-open="editUserModal"
            data-id="{{ $u->id }}"
            data-name="{{ $u->name }}"
            data-email="{{ $u->email }}"
            data-roles="{{ $u->roles->pluck('id')->implode(',') }}"
            title="Edit User"
          >
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
          @endcan

          @can('users.delete')
          <form method="post" action="{{ route('settings.users.destroy', $u) }}" class="inline">
            @csrf @method('delete')
            <button
              type="submit"
              class="btn-icon hover-lift text-red-500"
              onclick="return confirm('Hapus user?')"
              title="Hapus User"
            >
              <i class="fa-solid fa-trash"></i>
            </button>
          </form>
          @endcan
          </td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted">Belum ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3 hidden">{{ $users->links() }}</div>
</div>

{{-- CREATE MODAL --}}
<div id="createUserModal" class="modal" hidden>
  <div class="modal-card">
    <div class="modal-header">
      <h3>Tambah User</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form method="post" action="{{ route('settings.users.store') }}" class="modal-body grid gap-3" id="createUserForm">
      @csrf
      <label>Nama <input name="name" required class="input"></label>
      <label>Email <input type="email" name="email" required class="input"></label>
      <label>Password <input type="password" name="password" required class="input"></label>

      <div>
        <div class="font-semibold mb-1">Roles</div>
        {{-- akan diisi dinamis via JS --}}
        <div class="grid grid-cols-2 gap-2" id="createUserRoles"></div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT MODAL --}}
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
        {{-- akan diisi dinamis via JS --}}
        <div class="grid grid-cols-2 gap-2" id="editUserRoles"></div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Update</button>
      </div>
    </form>
  </div>
</div>

{{-- Fallback data untuk render awal bila endpoint JSON belum tersedia --}}
<script>
  window.__INITIAL_ROLES__ = @json(($roles ?? collect())->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values());
</script>

<script>
// Modal + Roles loader (dinamis, dengan fallback)
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

  // OPEN (delegation, capture)
  document.addEventListener('click', async (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (!opener) return;

    e.preventDefault();
    e.stopPropagation();

    const id    = opener.getAttribute('data-modal-open');
    const modal = document.getElementById(id);
    if (!modal) return;

    if (id === 'createUserModal') {
      const container = document.getElementById('createUserRoles');
      if (container) {
        try {
          const { roles } = await fetchRoles();
          renderRoles(container, roles, []);
        } catch (err) {
          console.error(err);
          renderRoles(container, (window.__INITIAL_ROLES__||[]), []);
        }
      }
    }

    if (id === 'editUserModal') {
      const form = document.getElementById('editUserForm');

      // Ambil dari atribut sederhana (aman dari escape/JSON)
      const uid   = opener.getAttribute('data-id') || '';
      const name  = opener.getAttribute('data-name')  || '';
      const email = opener.getAttribute('data-email') || '';
      const rolesCsv = opener.getAttribute('data-roles') || '';
      const rolesArr = rolesCsv.split(',').map(s => s.trim()).filter(Boolean); // ["1","3",...]

      if (form) {
        form.action = "{{ url('settings/users') }}/" + uid;
        form.querySelector('input[name=name]').value  = name;
        form.querySelector('input[name=email]').value = email;
        form.querySelector('input[name=password]').value = '';
      }

      const container = document.getElementById('editUserRoles');
      if (container) {
        try {
          const { roles, assigned } = await fetchRoles(uid);
          const assignedIds = (assigned && assigned.length) ? assigned : rolesArr;
          renderRoles(container, roles, assignedIds);
        } catch (err) {
          console.error(err);
          renderRoles(container, (window.__INITIAL_ROLES__||[]), rolesArr);
        }
      }
    }

    openModal(modal);
  }, true);

  // CLOSE (button)
  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-modal-close]');
    if (!closer) return;
    e.preventDefault();
    e.stopPropagation();
    closeModal(closer.closest('.modal'));
  }, true);

  // Backdrop close
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
  });

  // ESC close
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const openModals = Array.from(document.querySelectorAll('.modal')).filter(m => !m.hidden);
    const top = openModals.pop();
    if (top) closeModal(top);
  });

  // Search safety
  const f = document.getElementById('searchForm');
  const btn = document.getElementById('btnSearch');
  btn?.addEventListener('click', (ev) => { ev.preventDefault(); f?.submit(); });
})();
</script>

@endsection
