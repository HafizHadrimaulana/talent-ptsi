@extends('layouts.app')
@section('title','Back Office · Directorates & Units')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  {{-- HEADER --}}
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <div>
      <h2 class="u-title">Directorates & Units</h2>
      <p class="u-text-sm u-muted">
        Kelola struktur direktorat dan unit kerja, termasuk re-assign antar direktorat dengan drag &amp; drop.
      </p>
    </div>
    @canany(['org.create','org.update','org.delete'])
      <div class="u-flex u-gap-sm">
        {{-- Add Unit (icon only, kecil & compact) --}}
        <button type="button"
                class="u-btn u-btn--brand u-btn--sm u-hover-lift"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-open="modalCreateUnit"
                title="Add Unit">+
          <i class="fa fa-sitemap u-text-xs"></i>
        </button>
        {{-- Add Directorate (icon only, kecil & compact) --}}
        <button type="button"
                class="u-btn u-btn--brand u-btn--sm u-hover-lift"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-open="modalCreateDir"
                title="Add Directorate">+
          <i class="fa fa-building u-text-xs"></i>
        </button>
      </div>
    @endcanany
  </div>

  {{-- FLASH MESSAGE --}}
  @if(session('ok'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class="fas fa-check-circle u-success-icon"></i>
        <span>{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="u-card u-mb-md u-error">
      <div class="u-flex u-items-center u-gap-sm u-mb-sm">
        <i class="fas fa-exclamation-circle u-error-icon"></i>
        <span class="u-font-semibold">Please fix the errors:</span>
      </div>
      <ul class="u-list">
        @foreach($errors->all() as $e)
          <li class="u-item">{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- ================== TREE (3 CARD PER BARIS, FLEX-WRAP) ================== --}}
  <div class="card-glass u-p-md">
    <div class="u-flex u-items-center u-justify-between u-mb-sm">
      <h3 class="u-text-base u-font-semibold">Tree: Directorate → Units</h3>
      <p class="u-text-xs u-muted u-hide-mobile">
        Drag &amp; drop unit ke direktorat lain untuk re-assign.
      </p>
    </div>

    @php
      $byDir      = $units->groupBy('directorate_id');
      $unassigned = $byDir[null] ?? collect();
    @endphp

    {{-- FLEX WRAP: 3 kolom kira-kira di desktop --}}
    <div id="treeWrap"
         class="u-flex"
         style="flex-wrap:wrap;gap:var(--space-md);max-height:60vh;overflow-y:auto;padding-bottom:.25rem;">

      {{-- Unassigned units column (jadi card pertama) --}}
      @if($unassigned->count())
        <div class="u-item ios-glass"
             data-drop-dir=""
             style="flex:1 1 calc(33.333% - var(--space-md));min-width:260px;">
          <div class="u-flex u-justify-between u-items-center u-mb-xs">
            <div class="u-font-semibold u-text-sm md:u-text-base">
              Unassigned Units
            </div>
          </div>
          <ul class="u-list u-mt-xs">
            @foreach($unassigned as $u)
              <li class="u-item u-flex u-justify-between u-items-center u-py-sm u-px-sm u-hover-lift"
                  draggable="true"
                  data-unit-id="{{ $u->id }}"
                  data-unit-code="{{ $u->code }}"
                  data-unit-name="{{ $u->name }}">
                <div class="u-flex u-flex-col">
                  <span class="u-text-sm">
                    @if($u->code)
                      <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                    @endif
                    {{ $u->name }}
                  </span>
                </div>
                @canany(['org.create','org.update','org.delete'])
                  <div class="u-flex" style="gap:4px;">
                    {{-- Edit --}}
                    <button type="button"
                            class="u-btn u-btn--sm u-btn--outline"
                            style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                            data-modal-open="modalEditUnit"
                            data-unit-id="{{ $u->id }}"
                            data-unit-code="{{ $u->code }}"
                            data-unit-name="{{ $u->name }}"
                            data-unit-dir=""
                            title="Edit Unit">
                      <i class="fas fa-pen u-text-xs"></i>
                    </button>
                    {{-- Reassign --}}
                    <button type="button"
                            class="u-btn u-btn--sm u-btn--ghost"
                            style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                            data-reassign-unit="{{ $u->id }}"
                            data-unit-code="{{ $u->code }}"
                            data-unit-name="{{ $u->name }}"
                            data-unit-dir=""
                            title="Reassign Unit">
                      <i class="fas fa-random u-text-xs"></i>
                    </button>
                    {{-- Delete --}}
                    <form method="post"
                          action="{{ route('admin.org.units.destroy',$u->id) }}"
                          class="u-inline"
                          onsubmit="return confirm('Delete this unit?')">
                      @csrf @method('delete')
                      <button class="u-btn u-btn--sm u-btn--ghost u-danger"
                              style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                              type="submit"
                              title="Delete Unit">
                        <i class="fas fa-trash u-text-xs"></i>
                      </button>
                    </form>
                  </div>
                @endcanany
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Direktorat sebagai card grid (akan wrap 3-per-row) --}}
      @forelse($dirs as $d)
        <div class="u-item ios-glass"
             data-drop-dir="{{ $d->id }}"
             style="flex:1 1 calc(33.333% - var(--space-md));min-width:260px;">
          <div class="u-flex u-justify-between u-items-center u-mb-xs">
            <div class="u-font-semibold u-text-sm md:u-text-base">
              @if($d->code)
                <span class="u-badge u-badge--glass u-mr-xs">{{ $d->code }}</span>
              @endif
              {{ $d->name }}
            </div>
            @canany(['org.create','org.update','org.delete'])
              <div class="u-flex" style="gap:4px;">
                {{-- Edit dir (icon kecil & compact) --}}
                <button type="button"
                        class="u-btn u-btn--sm u-btn--outline"
                        style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                        data-modal-open="modalEditDir"
                        data-dir-id="{{ $d->id }}"
                        data-dir-code="{{ $d->code }}"
                        data-dir-name="{{ $d->name }}"
                        title="Edit Directorate">
                  <i class="fas fa-pen u-text-xs"></i>
                </button>
                {{-- Delete dir (icon kecil & compact) --}}
                <form method="post"
                      action="{{ route('admin.org.directorates.destroy',$d->id) }}"
                      class="u-inline"
                      onsubmit="return confirm('Delete this directorate?')">
                  @csrf @method('delete')
                  <button class="u-btn u-btn--sm u-btn--ghost u-danger"
                          style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                          type="submit"
                          title="Delete Directorate">
                    <i class="fas fa-trash u-text-xs"></i>
                  </button>
                </form>
              </div>
            @endcanany
          </div>

          @php $rows = $byDir[$d->id] ?? collect(); @endphp

          @if($rows->isEmpty())
            <div class="u-text-xs u-muted u-mb-sm">— no units —</div>
          @else
            <ul class="u-list u-mt-xs">
              @foreach($rows as $u)
                <li class="u-item u-flex u-justify-between u-items-center u-py-sm u-px-sm u-hover-lift"
                    draggable="true"
                    data-unit-id="{{ $u->id }}"
                    data-unit-code="{{ $u->code }}"
                    data-unit-name="{{ $u->name }}">
                  <div class="u-flex u-flex-col">
                    <span class="u-text-sm">
                      @if($u->code)
                        <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                      @endif
                      {{ $u->name }}
                    </span>
                  </div>
                  @canany(['org.create','org.update','org.delete'])
                    <div class="u-flex" style="gap:4px;">
                      {{-- Edit unit --}}
                      <button type="button"
                              class="u-btn u-btn--sm u-btn--outline"
                              style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                              data-modal-open="modalEditUnit"
                              data-unit-id="{{ $u->id }}"
                              data-unit-code="{{ $u->code }}"
                              data-unit-name="{{ $u->name }}"
                              data-unit-dir="{{ $d->id }}"
                              title="Edit Unit">
                        <i class="fas fa-pen u-text-xs"></i>
                      </button>
                      {{-- Reassign --}}
                      <button type="button"
                              class="u-btn u-btn--sm u-btn--ghost"
                              style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                              data-reassign-unit="{{ $u->id }}"
                              data-unit-code="{{ $u->code }}"
                              data-unit-name="{{ $u->name }}"
                              data-unit-dir="{{ $d->id }}"
                              title="Reassign Unit">
                        <i class="fas fa-random u-text-xs"></i>
                      </button>
                      {{-- Delete --}}
                      <form method="post"
                            action="{{ route('admin.org.units.destroy',$u->id) }}"
                            class="u-inline"
                            onsubmit="return confirm('Delete this unit?')">
                        @csrf @method('delete')
                        <button class="u-btn u-btn--sm u-btn--ghost u-danger"
                                style="min-height:26px;padding:.15rem .45rem;border-radius:999px;"
                                type="submit"
                                title="Delete Unit">
                          <i class="fas fa-trash u-text-xs"></i>
                        </button>
                      </form>
                    </div>
                  @endcanany
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @empty
        <div class="u-empty" style="flex:1 1 100%;">
          <div class="u-empty__icon">📭</div>
          <p class="u-font-semibold">No directorates yet.</p>
          <p class="u-text-sm u-muted">Tambah direktorat baru untuk mulai membuat struktur.</p>
        </div>
      @endforelse
    </div>
  </div>
</div>

{{-- Dummy DataTable supaya plugins/datatables.js nggak error --}}
<div style="display:none">
  <table id="users-table" class="u-table" data-dt>
    <thead><tr><th>_</th><th>_</th><th>_</th><th>_</th></tr></thead>
    <tbody><tr><td></td><td></td><td></td><td></td></tr></tbody>
  </table>
</div>

{{-- ===================== MODALS ===================== --}}
@canany(['org.create','org.update','org.delete'])
  {{-- Create Directorate --}}
  <div id="modalCreateDir" class="u-modal" hidden>
    <div class="u-modal__card">
      <div class="u-modal__head">
        <div class="u-title">Add Directorate</div>
        <button type="button"
                class="u-btn u-btn--sm u-btn--ghost"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-close
                aria-label="Close">
          <i class="fas fa-times u-text-xs"></i>
        </button>
      </div>
      <form id="formCreateDir" method="post" action="{{ route('admin.org.directorates.store') }}" autocomplete="off" novalidate>
        @csrf
        <div class="u-modal__body u-p-md u-flex u-flex-col u-gap-sm">
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Code (optional)</label>
            <input name="code" class="u-input" maxlength="40">
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Name</label>
            <input name="name" class="u-input" maxlength="200" required>
          </div>
        </div>
        <div class="u-modal__foot">
          <button class="u-btn u-btn--ghost" type="button" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift" type="submit">
            <i class="fas fa-save u-mr-xs u-text-xs"></i>Save
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit Directorate --}}
  <div id="modalEditDir" class="u-modal" hidden>
    <div class="u-modal__card">
      <div class="u-modal__head">
        <div class="u-title">Edit Directorate</div>
        <button type="button"
                class="u-btn u-btn--sm u-btn--ghost"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-close
                aria-label="Close">
          <i class="fas fa-times u-text-xs"></i>
        </button>
      </div>
      <form id="formEditDir" method="post" action="" autocomplete="off" novalidate>
        @csrf @method('put')
        <input type="hidden" name="id">
        <div class="u-modal__body u-p-md u-flex u-flex-col u-gap-sm">
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Code (optional)</label>
            <input name="code" class="u-input" maxlength="40">
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Name</label>
            <input name="name" class="u-input" maxlength="200" required>
          </div>
        </div>
        <div class="u-modal__foot">
          <button class="u-btn u-btn--ghost" type="button" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift" type="submit">
            <i class="fas fa-save u-mr-xs u-text-xs"></i>Update
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Create Unit --}}
  <div id="modalCreateUnit" class="u-modal" hidden>
    <div class="u-modal__card">
      <div class="u-modal__head">
        <div class="u-title">Add Unit</div>
        <button type="button"
                class="u-btn u-btn--sm u-btn--ghost"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-close
                aria-label="Close">
          <i class="fas fa-times u-text-xs"></i>
        </button>
      </div>
      <form id="formCreateUnit" method="post" action="{{ route('admin.org.units.store') }}" autocomplete="off" novalidate>
        @csrf
        <div class="u-modal__body u-p-md u-flex u-flex-col u-gap-sm">
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Code (optional)</label>
            <input name="code" class="u-input" maxlength="60">
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Name</label>
            <input name="name" class="u-input" maxlength="200" required>
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Directorate</label>
            <select name="directorate_id" class="u-input" id="createUnitDirSelect">
              <option value="">— Unassigned —</option>
              @foreach($dirs as $d)
                <option value="{{ $d->id }}">@if($d->code)[{{ $d->code }}] @endif{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="u-modal__foot">
          <button class="u-btn u-btn--ghost" type="button" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift" type="submit">
            <i class="fas fa-save u-mr-xs u-text-xs"></i>Save
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit Unit --}}
  <div id="modalEditUnit" class="u-modal" hidden>
    <div class="u-modal__card">
      <div class="u-modal__head">
        <div class="u-title">Edit Unit</div>
        <button type="button"
                class="u-btn u-btn--sm u-btn--ghost"
                style="min-height:30px;padding:.25rem .5rem;border-radius:999px;"
                data-modal-close
                aria-label="Close">
          <i class="fas fa-times u-text-xs"></i>
        </button>
      </div>
      <form id="formEditUnit" method="post" action="" autocomplete="off" novalidate>
        @csrf @method('put')
        <input type="hidden" name="id">
        <div class="u-modal__body u-p-md u-flex u-flex-col u-gap-sm">
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Code (optional)</label>
            <input name="code" class="u-input" maxlength="60">
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Name</label>
            <input name="name" class="u-input" maxlength="200" required>
          </div>
          <div>
            <label class="u-text-sm u-font-medium u-block u-mb-xs">Directorate</label>
            <select name="directorate_id" class="u-input" id="editUnitDirSelect">
              <option value="">— Unassigned —</option>
              @foreach($dirs as $d)
                <option value="{{ $d->id }}">@if($d->code)[{{ $d->code }}] @endif{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="u-modal__foot">
          <button class="u-btn u-btn--ghost" type="button" data-modal-close>Cancel</button>
          <button class="u-btn u-btn--brand u-hover-lift" type="submit">
            <i class="fas fa-save u-mr-xs u-text-xs"></i>Update
          </button>
        </div>
      </form>
    </div>
  </div>
@endcanany

<script>
document.addEventListener('DOMContentLoaded', function () {
  const $  = (q, root=document) => root.querySelector(q);
  const $$ = (q, root=document) => Array.from(root.querySelectorAll(q));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // ---------- Modal open / close ----------
  document.addEventListener('click', function (e) {
    const openBtn = e.target.closest('[data-modal-open]');
    if (openBtn) {
      const targetId = openBtn.getAttribute('data-modal-open');
      const modal    = document.getElementById(targetId);
      if (!modal) return;

      if (targetId === 'modalEditDir') {
        const id   = openBtn.getAttribute('data-dir-id')   || '';
        const code = openBtn.getAttribute('data-dir-code') || '';
        const name = openBtn.getAttribute('data-dir-name') || '';
        const form = $('#formEditDir');
        if (form) {
          form.action = "{{ url('/admin/org/directorates') }}/" + id;
          form.querySelector('input[name=id]').value   = id;
          form.querySelector('input[name=code]').value = code;
          form.querySelector('input[name=name]').value = name;
        }
      }

      if (targetId === 'modalEditUnit') {
        const id   = openBtn.getAttribute('data-unit-id')   || '';
        const code = openBtn.getAttribute('data-unit-code') || '';
        const name = openBtn.getAttribute('data-unit-name') || '';
        const dir  = openBtn.getAttribute('data-unit-dir')  || '';
        const form = $('#formEditUnit');
        if (form) {
          form.action = "{{ url('/admin/org/units') }}/" + id;
          form.querySelector('input[name=id]').value   = id;
          form.querySelector('input[name=code]').value = code;
          form.querySelector('input[name=name]').value = name;
          const sel = $('#editUnitDirSelect');
          if (sel) sel.value = dir || '';
        }
      }

      modal.hidden = false;
      document.body.classList.add('modal-open');
      return;
    }

    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const modal = closeBtn.closest('.u-modal');
      if (modal) modal.hidden = true;
      if (!$$('.u-modal').some(m => !m.hidden)) {
        document.body.classList.remove('modal-open');
      }
      return;
    }

    const reasBtn = e.target.closest('[data-reassign-unit]');
    if (reasBtn) {
      const id   = reasBtn.getAttribute('data-reassign-unit') || '';
      const code = reasBtn.getAttribute('data-unit-code')      || '';
      const name = reasBtn.getAttribute('data-unit-name')      || '';
      const dir  = reasBtn.getAttribute('data-unit-dir')       || '';
      const form = $('#formEditUnit');
      if (form) {
        form.action = "{{ url('/admin/org/units') }}/" + id;
        form.querySelector('input[name=id]').value   = id;
        form.querySelector('input[name=code]').value = code;
        form.querySelector('input[name=name]').value = name;
        const sel = $('#editUnitDirSelect');
        if (sel) sel.value = dir || '';
      }
      const modal = document.getElementById('modalEditUnit');
      if (modal) {
        modal.hidden = false;
        document.body.classList.add('modal-open');
      }
      return;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      $$('.u-modal').forEach(m => m.hidden = true);
      document.body.classList.remove('modal-open');
    }
  });

  // ---------- Drag & Drop reassign ----------
  async function moveUnit(unitId, dirId, code, name) {
    const url = "{{ url('/admin/org/units') }}/" + unitId;
    const fd  = new FormData();
    fd.append('_token', csrf);
    fd.append('_method', 'PUT');
    fd.append('code', code || '');
    fd.append('name', name || '');
    fd.append('directorate_id', dirId || '');
    const res = await fetch(url, { method:'POST', body: fd });
    if (!res.ok) throw new Error('HTTP ' + res.status);
  }

  let dragUnitId = null;

  document.addEventListener('dragstart', function (e) {
    const li = e.target.closest('[data-unit-id]');
    if (!li) return;
    dragUnitId = li.getAttribute('data-unit-id');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', dragUnitId);
  });

  document.addEventListener('dragover', function (e) {
    const zone = e.target.closest('[data-drop-dir]');
    if (!zone) return;
    e.preventDefault();
  });

  document.addEventListener('drop', function (e) {
    const zone = e.target.closest('[data-drop-dir]');
    if (!zone) return;
    e.preventDefault();
    const unitId = dragUnitId || e.dataTransfer.getData('text/plain');
    dragUnitId   = null;
    if (!unitId) return;

    const dirId = zone.getAttribute('data-drop-dir') || '';
    const li    = document.querySelector('[data-unit-id="'+unitId+'"]');
    const code  = li?.getAttribute('data-unit-code') || '';
    const name  = li?.getAttribute('data-unit-name') || '';
    if (!name) return;

    moveUnit(unitId, dirId, code, name)
      .then(() => window.location.reload())
      .catch(err => {
        console.error(err);
        alert('Gagal memindahkan unit');
      });
  });

  document.addEventListener('dragend', function () {
    dragUnitId = null;
  });
});
</script>
@endsection
