@extends('layouts.app')
@section('title','Settings · Permissions')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Permission Management</h2>
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
          @php
            $permPayload = json_encode(
              [
                'id'    => $p->id,
                'name'  => $p->name,
                'roles' => $p->roles()->pluck('name')->all(),
              ],
              JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            );
          @endphp
          <tr>
            <td>
              <div class="u-flex u-items-center u-gap-sm">
                <div class="u-badge u-badge--primary">
                  <i class='bx bx-lock-alt u-text-xs u-mr-xs'></i> Permission
                </div>
                <span class="u-font-medium">{{ $p->name }}</span>
              </div>
            </td>
            <td class="u-hide-mobile u-text-sm u-muted">
              {{ $p->created_at?->format('M j, Y') }}
            </td>
            <td class="cell-actions">
              <div class="cell-actions__group">
                <button
                  class="u-btn u-btn--outline u-btn--sm u-hover-lift"
                  data-modal-open="editPermModal"
                  data-perm="{{ $permPayload }}"
                >
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
          <div id="permTitle" class="u-title">Edit Permission</div>
          <div class="u-muted u-text-sm" id="permMeta">ID: -</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class='fas fa-times'></i>
      </button>
    </div>

    <form id="editPermForm" method="post">
      @csrf
      @method('put')

      <div class="u-modal__body">
        <div class="u-tabs-wrap">
          <div class="u-tabs">
            <button class="u-tab is-active" type="button" data-target="#perm-edit-basic">Basic Info</button>
            <button class="u-tab" type="button" data-target="#perm-edit-roles">Assigned Roles</button>
          </div>
        </div>

        <div class="u-panels">
          {{-- TAB: BASIC INFO --}}
          <div class="u-panel is-active" id="perm-edit-basic">
            <div class="u-grid-2 u-stack-mobile u-gap-md u-p-md">
              <div class="u-grid-col-span-2">
                <label class="u-block u-text-sm u-font-medium u-mb-sm">
                  Permission Name
                </label>
                <input
                  name="name"
                  required
                  class="u-input"
                  placeholder="Enter permission name"
                >
                <p class="u-text-xs u-muted u-mt-xs">
                  Use lowercase with dots (e.g., users.create)
                </p>
              </div>

              <div class="u-grid-col-span-2 u-card u-p-md"
                   style="background:color-mix(in srgb,#f59e0b 6%,transparent);
                          border-color:color-mix(in srgb,#f59e0b 18%,transparent);
                          color:#92400e;">
                <div class="u-flex u-items-start u-gap-sm">
                  <i class='bx bx-info-circle'></i>
                  <div>
                    <p class="u-text-sm u-font-medium">Important</p>
                    <p class="u-text-xs">
                      Changing permission names may affect system functionality.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- TAB: ASSIGNED ROLES --}}
          <div class="u-panel" id="perm-edit-roles">
            <div class="u-p-md">
              <div class="u-flex u-items-center u-justify-between u-mb-md u-stack-mobile">
                <h4 class="u-font-semibold">Assign to Roles</h4>
                <div class="u-flex u-gap-xs">
                  <button class="u-btn u-btn--outline u-btn--sm" type="button" data-check="roles-all">
                    Select All
                  </button>
                  <button class="u-btn u-btn--ghost u-btn--sm" type="button" data-check="roles-none">
                    None
                  </button>
                </div>
              </div>

              @php
                $unitId = auth()->user()?->unit_id;
                $allRoles = \App\Models\Role::query()
                  ->where('guard_name','web')
                  ->where(function($q) use ($unitId){
                    $q->whereNull('unit_id')
                      ->orWhere('unit_id',$unitId);
                  })
                  ->orderBy('name')
                  ->get();
              @endphp

              @if($allRoles->isEmpty())
                <div class="u-empty">
                  <p class="u-muted">No roles available.</p>
                </div>
              @else
                <div class="u-grid-2 u-stack-mobile u-gap-sm u-max-h-96 u-overflow-y-auto" id="permRolesWrap">
                  @foreach($allRoles as $r)
                    <label class="u-item u-flex u-items-center u-gap-sm u-p-sm">
                      <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $r->name }}"
                      >
                      <span class="u-text-sm">
                        {{ $r->name }}
                        @if(!is_null($r->unit_id))
                          <span class="u-badge u-badge--glass">unit: {{ $r->unit_id }}</span>
                        @else
                          <span class="u-badge u-badge--glass">global</span>
                        @endif
                      </span>
                    </label>
                  @endforeach
                </div>
              @endif

            </div>
          </div>
        </div>
      </div>

      <div class="u-modal__foot">
        <div class="u-muted u-text-sm">
          Press <kbd>Esc</kbd> to close
        </div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift">
            <i class='fas fa-save u-mr-xs'></i> Update Permission
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const $  = (q, root = document) => root.querySelector(q);
  const $$ = (q, root = document) => [...root.querySelectorAll(q)];

  function wireTabs(modalEl){
    if (!modalEl) return;
    const tabs = $$('.u-tab[data-target]', modalEl);
    if(!tabs.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetSel = tab.getAttribute('data-target');
        const targetEl  = $(targetSel, modalEl);
        if (!targetEl) return;

        tabs.forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');

        $$('.u-panels .u-panel', modalEl).forEach(p => p.classList.remove('is-active'));
        targetEl.classList.add('is-active');
      });
    });
  }

  function resetPermModalTabs() {
    const modal = $('#editPermModal');
    if (!modal) return;
    const firstTab  = modal.querySelector('.u-tab[data-target="#perm-edit-basic"]');
    const firstPane = $('#perm-edit-basic', modal);
    const otherPanes = $$('.u-panels .u-panel', modal);

    $$('.u-tab[data-target]', modal).forEach(t => t.classList.remove('is-active'));
    otherPanes.forEach(p => p.classList.remove('is-active'));

    if (firstTab) firstTab.classList.add('is-active');
    if (firstPane) firstPane.classList.add('is-active');
  }

  // open/close + preload
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open="editPermModal"]');
    if (opener) {
      const payload = opener.getAttribute('data-perm') || '{}';
      let data = {};
      try { data = JSON.parse(payload); } catch (_) {}

      const modal = $('#editPermModal');
      const form  = $('#editPermForm');

      resetPermModalTabs();

      form.action = "{{ url('admin/settings/access/permissions') }}/" + (data.id || '');
      form.querySelector('input[name=name]').value = data.name || '';

      $('#permTitle').textContent = data.name || 'Edit Permission';
      $('#permMeta').textContent  = 'ID: ' + (data.id || '-');

      const current = new Set(data.roles || []);
      $$('#permRolesWrap input[type=checkbox][name="roles[]"]').forEach(cb => {
        cb.checked = current.has(cb.value);
      });

      modal.hidden = false;
      document.body.classList.add('modal-open');
    }

    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const modal = closeBtn.closest('.u-modal');
      if (modal) modal.hidden = true;
      document.body.classList.remove('modal-open');
    }

    // Select all / none roles
    const allRolesBtn  = e.target.closest('[data-check="roles-all"]');
    const noneRolesBtn = e.target.closest('[data-check="roles-none"]');
    if (allRolesBtn || noneRolesBtn) {
      const checked = !!allRolesBtn;
      $$('#permRolesWrap input[type=checkbox][name="roles[]"]').forEach(cb => {
        cb.checked = checked;
      });
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modal = $('#editPermModal');
      if (modal && !modal.hidden) {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
      }
    }
  });

  wireTabs(document.getElementById('editPermModal'));
});
</script>
@endsection
