@extends('layouts.app')
@section('title','Settings · Permissions')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Permission Management</h2>
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
                <div class="u-badge u-badge--glass"><i class='bx bx-lock-alt u-text-xs u-mr-xs'></i> Permission</div>
                <span class="font-medium">{{ $p->name }}</span>
              </div>
            </td>
            <td class="u-hide-mobile u-text-sm u-muted">{{ $p->created_at->format('M j, Y') }}</td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                        data-modal-open="editPermModal"
                        data-perm='@json(["id"=>$p->id,"name"=>$p->name,"roles"=>$p->roles()->pluck("name")->all()])'>
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
    <div class="u-text-sm u-muted">Showing {{ $permissions->count() }} of {{ $permissions->total() }} permissions</div>
    <div class="u-hidden">{{ $permissions->links() }}</div>
  </div>
</div>

<!-- Edit Permission Modal -->
<div id="editPermModal" class="u-modal" hidden>
  <div class="u-modal__card">
    <div class="u-modal__head">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class='bx bx-lock-alt'></i></div>
        <div>
          <div id="permName" class="u-title">Permission Name</div>
          <div class="u-muted u-text-sm" id="permId">ID: </div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close"><i class='bx bx-x'></i></button>
    </div>

    <form id="editPermForm" method="post">
      @csrf @method('put')
      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#perm-edit">Edit Permission</button>
            <button class="u-tab" type="button" data-target="#perm-roles">Assigned Roles</button>
            <button class="u-tab u-hide-mobile" type="button" data-target="#perm-users">Users with Access</button>
          </div>
        </div>

        <div class="u-panels">
          <div class="u-panel is-active" id="perm-edit">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">Permission Name</label>
                <input name="name" required class="u-input" placeholder="Enter permission name">
                <p class="u-text-xs u-muted u-mt-xs">Use lowercase with dots (e.g., users.create)</p>
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
            </div>
          </div>

          <div class="u-panel" id="perm-roles">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md">
                <h4 class="u-font-semibold">Assign to Roles</h4>
                <div class="u-flex u-gap-xs">
                  <button class="u-btn u-btn--outline u-btn--sm" type="button" data-check="all-roles">Select All</button>
                  <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-check="none-roles">None</button>
                </div>
              </div>

              @php($allRoles = \Spatie\Permission\Models\Role::orderBy('name')->get())
              @if($allRoles->isEmpty())
                <div class="u-empty"><p class="u-muted">No roles available.</p></div>
              @else
              <div class="u-grid u-grid-cols-1 md:u-grid-cols-2 u-gap-sm" id="permRolesWrap">
                @foreach($allRoles as $r)
                  <label class="u-flex u-items-center u-gap-sm u-p-sm u-rounded-lg u-border u-border-transparent u-hover:border-gray-200">
                    <input type="checkbox" name="roles[]" value="{{ $r->name }}" class="u-rounded">
                    <span class="u-text-sm">{{ $r->name }}</span>
                  </label>
                @endforeach
              </div>
              @endif
            </div>
          </div>

          <div class="u-panel" id="perm-users">
            <div class="u-p-md">
              <div class="u-empty">
                <i class='bx bx-user u-empty__icon'></i>
                <p class="u-text-sm u-muted">Users inherit via roles.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="u-modal__foot">
        <div class="u-muted u-text-sm">Press <kbd>Esc</kbd> to close</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift"><i class='fas fa-save u-mr-xs'></i> Update Permission</button>
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

  // open/close + preload
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open="editPermModal"]');
    if (opener) {
      const data = JSON.parse(opener.dataset.perm);
      const modal = $('#editPermModal');
      const form  = $('#editPermForm');
      form.action = "{{ url('admin/settings/access/permissions') }}/" + data.id;
      form.querySelector('input[name=name]').value = data.name;
      $('#permName').textContent = data.name;
      $('#permId').textContent   = 'ID: ' + data.id;

      const current = new Set((data.roles||[]));
      $$('#permRolesWrap input[type=checkbox][name="roles[]"]').forEach(cb=>cb.checked = current.has(cb.value));

      modal.hidden = false; document.body.classList.add('modal-open');
    }
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) { closeBtn.closest('.u-modal').hidden = true; document.body.classList.remove('modal-open'); }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { $('#editPermModal').hidden = true; document.body.classList.remove('modal-open'); }
  });

  document.addEventListener('click', (e) => {
    const all = e.target.closest('[data-check="all-roles"]');
    const none = e.target.closest('[data-check="none-roles"]');
    if (all || none) {
      $$('#permRolesWrap input[type=checkbox][name="roles[]"]').forEach(cb=>cb.checked = !!all);
    }
  });

  wireTabs(document.getElementById('editPermModal'));
});
</script>
@endsection
