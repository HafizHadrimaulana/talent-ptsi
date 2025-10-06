@extends('layouts.app')
@section('title','Settings · Users')

@section('content')
<div class="card glass p-4">
  <div class="flex justify-between items-center gap-2 mb-3">
    <form method="get" class="flex gap-2">
      <input name="q" value="{{ $q }}" placeholder="Cari nama/email" class="input" />
      <button class="btn btn-outline hover-lift" type="button" id="btnSearch">Cari</button>
    </form>
    @can('users.create')
    <button class="btn btn-brand hover-lift" data-modal-open="createUserModal">+ Tambah User</button>
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
            <button class="btn-sm hover-lift"
                    data-modal-open="editUserModal"
                    data-user="{{ e(json_encode([
                      'id'    => $u->id,
                      'name'  => $u->name,
                      'email' => $u->email,
                      'roles' => $u->roles->pluck('id')->toArray(),
                    ], JSON_UNESCAPED_UNICODE)) }}">
              Edit
            </button>
            @endcan

            @can('users.delete')
            <form method="post" action="{{ route('settings.users.destroy',$u) }}" class="inline">
              @csrf @method('delete')
              <button class="btn-sm danger hover-lift" onclick="return confirm('Hapus user?')">Hapus</button>
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
    <form method="post" action="{{ route('settings.users.store') }}" class="modal-body grid gap-3">
      @csrf
      <label>Nama <input name="name" required class="input"></label>
      <label>Email <input type="email" name="email" required class="input"></label>
      <label>Password <input type="password" name="password" required class="input"></label>

      <div>
        <div class="font-semibold mb-1">Roles</div>
        <div class="grid grid-cols-2 gap-2">
          @foreach($roles as $r)
          <label class="chip"><input type="checkbox" name="roles[]" value="{{ $r->id }}"> {{ $r->name }}</label>
          @endforeach
        </div>
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
        <div class="grid grid-cols-2 gap-2" id="editUserRoles">
          @foreach($roles as $r)
          <label class="chip">
            <input type="checkbox" name="roles[]" value="{{ $r->id }}"> {{ $r->name }}
          </label>
          @endforeach
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const openers = document.querySelectorAll('[data-modal-open]');
  const closers = document.querySelectorAll('[data-modal-close]');

  openers.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.getAttribute('data-modal-open');
      const modal = document.getElementById(id);
      if(!modal) return;

      if(id==='editUserModal' && btn.dataset.user){
        const data = JSON.parse(btn.dataset.user);
        const form = document.getElementById('editUserForm');
        form.action = "{{ url('settings/users') }}/" + data.id;
        form.querySelector('input[name=name]').value = data.name;
        form.querySelector('input[name=email]').value = data.email;

        form.querySelectorAll('#editUserRoles input[type=checkbox]').forEach(cb=>{
          cb.checked = Array.isArray(data.roles) && data.roles.includes(parseInt(cb.value));
        });
        form.querySelector('input[name=password]').value = '';
      }

      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    });
  });

  closers.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const modal = btn.closest('.modal');
      if(!modal) return;
      modal.hidden = true;
      document.body.style.overflow = '';
    });
  });

  document.querySelectorAll('.modal').forEach(modal=>{
    modal.addEventListener('click', (e)=>{
      if(e.target === modal){
        modal.hidden = true;
        document.body.style.overflow = '';
      }
    });
  });
})();
</script>
@endsection
