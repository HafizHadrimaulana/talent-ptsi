@extends('layouts.app')
@section('title','Settings · Permissions')

@section('content')
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<div class="card glass p-4">
  <h2 class="font-semibold mb-3">Permission List</h2>

  @if(session('ok')) <div class="alert success mb-3">{{ session('ok') }}</div> @endif
  @if($errors->any())
    <div class="alert danger mb-3"><ul class="ml-4 list-disc">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <div class="table-wrap">
    <table id="perms-table" class="display table-ui table-compact table-sticky" data-dt>
      <thead><tr><th>Permission</th><th class="cell-actions">Aksi</th></tr></thead>
      <tbody>
        @foreach($permissions as $p)
        <tr>
          <td>{{ $p->name }}</td>
          <td class="cell-actions">
            <button class="btn-sm hover-lift" data-modal-open="editPermModal" style="cursor: pointer;"
                    data-perm='@json(["id"=>$p->id,"name"=>$p->name])'>
              <i class='bx bx-rename' style="font-size: 1.5rem;"></i>
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-3 hidden">{{ $permissions->links() }}</div>
</div>

{{-- EDIT PERMISSION MODAL --}}
<div id="editPermModal" class="modal" hidden>
  <div class="modal-card max-w-lg">
    <div class="modal-header">
      <h3>Rename Permission</h3>
      <button class="close-btn" data-modal-close>&times;</button>
    </div>
    <form id="editPermForm" method="post" class="modal-body grid gap-3">
      @csrf @method('put')
      <label>Permission <input name="name" required class="input"></label>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button class="btn btn-brand hover-lift">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const opens = document.querySelectorAll('[data-modal-open="editPermModal"]');
  opens.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const data = JSON.parse(btn.dataset.perm);
      const form = document.getElementById('editPermForm');
      form.action = "{{ url('settings/permissions') }}/" + data.id;
      form.querySelector('input[name=name]').value = data.name;

      const modal = document.getElementById('editPermModal');
      modal.hidden = false; document.body.style.overflow = 'hidden';
    });
  });

  const closes = document.querySelectorAll('[data-modal-close]');
  closes.forEach(c=>c.addEventListener('click', ()=>{
    const m = c.closest('.modal'); if(m){ m.hidden = true; document.body.style.overflow='';}
  }));
  document.querySelectorAll('.modal').forEach(m=>{
    m.addEventListener('click', e=>{ if(e.target===m){ m.hidden = true; document.body.style.overflow=''; }});
  });
})();
</script>
@endsection
