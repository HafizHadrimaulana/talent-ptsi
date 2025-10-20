@extends('layouts.app')
@section('title','Settings · Roles')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<div class="card glass p-4">
  <div class="flex justify-between items-center mb-3">
    <h2 class="font-semibold">Role Management</h2>
    @can('rbac.assign')
    <button class="btn btn-brand hover-lift" data-modal-open="createRoleModal">+ Role</button>
    @endcan
  </div>

  @if(session('ok')) <div class="alert success mb-3">{{ session('ok') }}</div> @endif
  @if($errors->any())
    <div class="alert danger mb-3"><ul class="ml-4 list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="table-wrap">
    <table id="roles-table" class="display table-ui table-compact table-sticky" data-dt>
      <thead><tr><th>Role</th><th>Users</th><th class="cell-actions">Aksi</th></tr></thead>
      <tbody>
        @foreach($roles as $r)
        <tr>
          <td>{{ $r->name }}</td>
          <td>{{ $r->users_count }}</td>
          <td class="cell-actions">
            <button class="btn-sm hover-lift" data-modal-open="editRoleModal"
              data-role='@json(["id"=>$r->id,"name"=>$r->name,"perms"=>$r->permissions()->pluck("name")->all()])' style="cursor:pointer;  color:#3b82f6">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <form method="post" action="{{ route('admin.roles.destroy',$r) }}" class="inline">
              @csrf @method('delete')
              <button class="btn-sm danger hover-lift" onclick="return confirm('Hapus role?')"  style="cursor:pointer; color:#ef4444"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-3 hidden">{{ $roles->links() }}</div>
</div>

{{-- CREATE ROLE MODAL --}}
<div id="createRoleModal" class="modal" hidden>
  <div class="modal-card max-w-3xl">
    <div class="modal-header">
      <h3>Tambah Role</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form method="post" action="{{ route('admin.roles.store') }}" class="modal-body grid gap-3">
      @csrf
      <label>Nama Role <input name="name" required class="input"></label>

      <div class="grid gap-4">
        @foreach($groupedPerms as $group => $items)
          <div>
            <div class="font-semibold mb-1">{{ strtoupper($group) }}</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              @foreach($items as $perm)
              <label class="chip"><input type="checkbox" name="permissions[]" value="{{ $perm->name }}"> {{ $perm->name }}</label>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT ROLE MODAL --}}
<div id="editRoleModal" class="modal" hidden>
  <div class="modal-card max-w-3xl">
    <div class="modal-header">
      <h3>Edit Role</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form id="editRoleForm" method="post" class="modal-body grid gap-3">
      @csrf @method('put')
      <label>Nama Role <input name="name" required class="input"></label>

      <div class="grid gap-4" id="editRolePermsWrap">
        @foreach($groupedPerms as $group => $items)
          <div>
            <div class="font-semibold mb-1">{{ strtoupper($group) }}</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              @foreach($items as $perm)
              <label class="chip">
                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"> {{ $perm->name }}
              </label>
              @endforeach
            </div>
          </div>
        @endforeach
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
  const editBtnSel = '[data-modal-open="editRoleModal"]';
  document.querySelectorAll(editBtnSel).forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const data = JSON.parse(btn.dataset.role);
      const form = document.getElementById('editRoleForm');
      form.action = "{{ url('settings/roles') }}/" + data.id;
      form.querySelector('input[name=name]').value = data.name;

      const current = new Set(data.perms || []);
      form.querySelectorAll('#editRolePermsWrap input[type=checkbox]').forEach(cb=>{
        cb.checked = current.has(cb.value);
      });

      document.getElementById('editRoleModal').hidden = false;
      document.body.style.overflow = 'hidden';
    });
  });

  const opens = document.querySelectorAll('[data-modal-open]');
  const closes = document.querySelectorAll('[data-modal-close]');
  opens.forEach(o=>o.addEventListener('click', ()=>{
    const id = o.getAttribute('data-modal-open');
    const m = document.getElementById(id);
    if(m){ m.hidden = false; document.body.style.overflow='hidden';}
  }));
  closes.forEach(c=>c.addEventListener('click', ()=>{
    const m = c.closest('.modal'); if(m){ m.hidden = true; document.body.style.overflow='';}
  }));
  document.querySelectorAll('.modal').forEach(m=>{
    m.addEventListener('click', e=>{ if(e.target===m){ m.hidden = true; document.body.style.overflow=''; }});
  });
})();
</script>
@endsection
