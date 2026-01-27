@extends('layouts.app')
@section('title','Back Office · Directorates & Units')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  {{-- HEADER --}}
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <div>
      <h2 class="u-title">Directorates & Units</h2>
      <p class="u-text-sm u-muted">
        Kelola struktur direktorat dan unit kerja, termasuk drag &amp; drop antar direktorat atau antar grup unit (Enabler / Operasi / Cabang).
      </p>
    </div>
    @canany(['org.create','org.update','org.delete'])
      <div class="u-flex u-gap-sm u-items-center">
        {{-- Edit mode toggle --}}
        <button type="button"
                id="btnEditMode"
                class="u-btn u-btn--ghost u-btn--sm u-btn--chip u-hover-lift">
          <i class="fas fa-edit u-text-xs u-mr-xs"></i> Edit Layout
        </button>

        {{-- Add Unit --}}
        <button type="button"
                class="u-btn u-btn--brand u-btn--sm u-btn--chip u-hover-lift"
                data-modal-open="modalCreateUnit"
                title="Add Unit">
          <i class="fa fa-sitemap u-text-xs"></i>
        </button>
        {{-- Add Directorate --}}
        <button type="button"
                class="u-btn u-btn--brand u-btn--sm u-btn--chip u-hover-lift"
                data-modal-open="modalCreateDir"
                title="Add Directorate">
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

  <div class="card-glass u-p-md">

    {{-- Edit mode bar --}}
    @canany(['org.create','org.update','org.delete'])
      <div id="treeEditBar"
           class="u-flex u-items-center u-justify-between u-mb-sm u-gap-sm"
           style="display:none;">
        <div class="u-text-xs u-muted">
          <strong>Edit Mode aktif.</strong> Gunakan drag &amp; drop pada tab aktif. Setelah selesai, klik <strong>Save Changes</strong> atau <strong>Cancel</strong>.
        </div>
        <div class="u-flex u-gap-sm">
          <button type="button"
                  id="btnCancelLayout"
                  class="u-btn u-btn--ghost u-btn--sm u-btn--chip">
            Cancel
          </button>
          <button type="button"
                  id="btnSaveLayout"
                  class="u-btn u-btn--brand u-btn--sm u-btn--chip u-hover-lift">
            <i class="fas fa-save u-text-xs u-mr-xs"></i> Save Changes
          </button>
        </div>
      </div>
    @endcanany

    {{-- MAIN TABS: By Directorate / By Unit Group --}}
    <div class="u-tabs-wrap">
      <div class="u-tabs">
        <button type="button"
                class="u-tab is-active"
                data-org-main-tab="by-dir">
          By Directorate
        </button>
        <button type="button"
                class="u-tab"
                data-org-main-tab="by-unit">
          By Unit Group
        </button>
      </div>
    </div>

    <div class="u-panels">

      {{-- PANEL 1: TREE BY DIRECTORATE --}}
      <div class="u-panel is-active" id="panelByDir">
        <div class="u-flex u-items-center u-justify-between u-mb-sm">
          <h3 class="u-text-base u-font-semibold">Tree: Directorate → Units</h3>
          <p class="u-text-xs u-muted u-hide-mobile">
            Drag &amp; drop unit antar direktorat saat Edit Layout aktif.
          </p>
        </div>

        @php
          $byDir      = $units->groupBy('directorate_id');
          $unassigned = $byDir[null] ?? collect();
        @endphp

        <div id="treeWrap" class="u-tree-wrap" data-editing="0">

          {{-- Unassigned units column --}}
          @if($unassigned->count())
            <div class="u-item ios-glass u-tree-col" data-drop-dir="">
              <div class="u-tree-head">
                <div class="u-tree-title">
                  Unassigned Units
                </div>
              </div>

              <ul class="u-list u-mt-xs">
                @foreach($unassigned as $u)
                  <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                      draggable="true"
                      data-unit-id="{{ $u->id }}"
                      data-unit-code="{{ $u->code }}"
                      data-unit-name="{{ $u->name }}"
                      data-unit-dir=""
                      data-unit-dir-original=""
                      data-unit-group="{{ $u->category ?? '' }}"
                      data-unit-group-original="{{ $u->category ?? '' }}">
                    <div class="u-flex u-flex-col">
                      <span class="u-text-sm">
                        @if($u->code)
                          <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                        @endif
                        {{ $u->name }}
                      </span>
                      @if($u->category)
                        <span class="u-text-xs u-muted">Group : {{ ucfirst($u->category) }}</span>
                      @endif
                    </div>
                    @canany(['org.create','org.update','org.delete'])
                      <div class="u-tree-actions js-edit-only">
                        {{-- Edit --}}
                        <button type="button"
                                class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                data-modal-open="modalEditUnit"
                                data-unit-id="{{ $u->id }}"
                                data-unit-code="{{ $u->code }}"
                                data-unit-name="{{ $u->name }}"
                                data-unit-dir=""
                                data-unit-category="{{ $u->category ?? '' }}"
                                title="Edit Unit">
                          <i class="fas fa-pen u-text-xs"></i>
                        </button>
                        {{-- Delete --}}
                        <form method="post"
                              action="{{ route('admin.org.units.destroy',$u->id) }}"
                              class="u-inline"
                              onsubmit="return confirm('Delete this unit?')">
                          @csrf @method('delete')
                          <button class="u-btn u-btn--sm u-btn--ghost u-btn--chip u-danger"
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

          {{-- Direktorat sebagai card grid (wrap 3-per-row) --}}
          @forelse($dirs as $d)
            <div class="u-item ios-glass u-tree-col" data-drop-dir="{{ $d->id }}">
              <div class="u-tree-head">
                <div class="u-tree-title">
                  @if($d->code)
                    <span class="u-badge u-badge--glass u-mr-xs">{{ $d->code }}</span>
                  @endif
                  {{ $d->name }}
                </div>
                @canany(['org.create','org.update','org.delete'])
                  <div class="u-tree-actions js-edit-only">
                    {{-- Edit dir --}}
                    <button type="button"
                            class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                            data-modal-open="modalEditDir"
                            data-dir-id="{{ $d->id }}"
                            data-dir-code="{{ $d->code }}"
                            data-dir-name="{{ $d->name }}"
                            title="Edit Directorate">
                      <i class="fas fa-pen u-text-xs"></i>
                    </button>
                    {{-- Delete dir --}}
                    <form method="post"
                          action="{{ route('admin.org.directorates.destroy',$d->id) }}"
                          class="u-inline"
                          onsubmit="return confirm('Delete this directorate?')">
                      @csrf @method('delete')
                      <button class="u-btn u-btn--sm u-btn--ghost u-btn--chip u-danger"
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
                <div class="u-text-xs u-muted u-mb-sm u-tree-empty">— no units —</div>
              @else
                <ul class="u-list u-mt-xs">
                  @foreach($rows as $u)
                    <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                        draggable="true"
                        data-unit-id="{{ $u->id }}"
                        data-unit-code="{{ $u->code }}"
                        data-unit-name="{{ $u->name }}"
                        data-unit-dir="{{ $d->id }}"
                        data-unit-dir-original="{{ $d->id }}"
                        data-unit-group="{{ $u->category ?? '' }}"
                        data-unit-group-original="{{ $u->category ?? '' }}">
                      <div class="u-flex u-flex-col">
                        <span class="u-text-sm">
                          @if($u->code)
                            <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                          @endif
                          {{ $u->name }}
                        </span>
                        @if($u->category)
                          <span class="u-text-xs u-muted">Group : {{ ucfirst($u->category) }}</span>
                        @endif
                      </div>
                      @canany(['org.create','org.update','org.delete'])
                        <div class="u-tree-actions js-edit-only">
                          {{-- Edit unit --}}
                          <button type="button"
                                  class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                  data-modal-open="modalEditUnit"
                                  data-unit-id="{{ $u->id }}"
                                  data-unit-code="{{ $u->code }}"
                                  data-unit-name="{{ $u->name }}"
                                  data-unit-dir="{{ $d->id }}"
                                  data-unit-category="{{ $u->category ?? '' }}"
                                  title="Edit Unit">
                            <i class="fas fa-pen u-text-xs"></i>
                          </button>
                          {{-- Delete --}}
                          <form method="post"
                                action="{{ route('admin.org.units.destroy',$u->id) }}"
                                class="u-inline"
                                onsubmit="return confirm('Delete this unit?')">
                            @csrf @method('delete')
                            <button class="u-btn u-btn--sm u-btn--ghost u-btn--chip u-danger"
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

      {{-- PANEL 2: BOARD BY UNIT GROUP (ENABLER / OPERASI / CABANG) --}}
      <div class="u-panel" id="panelByUnit">
        <div class="u-flex u-items-center u-justify-between u-mb-sm">
          <h3 class="u-text-base u-font-semibold">Board: Unit Groups</h3>
          <p class="u-text-xs u-muted u-hide-mobile">
            Drag &amp; drop unit antar grup saat Edit Layout aktif.
          </p>
        </div>

        @php
          $enablers    = $units->where('category','enabler');
          $operasis    = $units->where('category','operasi');
          $cabangs     = $units->where('category','cabang');
          $uncatGroups = $units->whereNull('category');
        @endphp

        <div id="unitWrap" class="u-tree-wrap" data-editing="0">
          {{-- Uncategorized --}}
          @if($uncatGroups->count())
            <div class="u-item ios-glass u-tree-col" data-drop-group="">
              <div class="u-tree-head">
                <div class="u-tree-title">
                  Uncategorized
                  <span class="u-badge u-badge--warn u-mr-xs">{{ $uncatGroups->count() }}</span>
                </div>
              </div>
              <ul class="u-list u-mt-xs">
                @foreach($uncatGroups as $u)
                  <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                      draggable="true"
                      data-unit-id="{{ $u->id }}"
                      data-unit-code="{{ $u->code }}"
                      data-unit-name="{{ $u->name }}"
                      data-unit-dir="{{ $u->directorate_id ?? '' }}"
                      data-unit-dir-original="{{ $u->directorate_id ?? '' }}"
                      data-unit-group=""
                      data-unit-group-original="">
                    <div class="u-flex u-flex-col">
                      <span class="u-text-sm">
                        @if($u->code)
                          <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                        @endif
                        {{ $u->name }}
                      </span>
                    </div>
                    @canany(['org.create','org.update','org.delete'])
                      <div class="u-tree-actions js-edit-only">
                        <button type="button"
                                class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                data-modal-open="modalEditUnit"
                                data-unit-id="{{ $u->id }}"
                                data-unit-code="{{ $u->code }}"
                                data-unit-name="{{ $u->name }}"
                                data-unit-dir="{{ $u->directorate_id ?? '' }}"
                                data-unit-category=""
                                title="Edit Unit">
                          <i class="fas fa-pen u-text-xs"></i>
                        </button>
                      </div>
                    @endcanany
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Enabler --}}
          <div class="u-item ios-glass u-tree-col" data-drop-group="enabler">
            <div class="u-tree-head">
              <div class="u-tree-title">
                Unit Enabler
                @if($enablers->count())
                  <span class="u-badge u-badge--glass u-mr-xs">{{ $enablers->count() }}</span>
                @endif
              </div>
            </div>
            @if($enablers->isEmpty())
              <div class="u-text-xs u-muted u-mb-sm u-tree-empty">— no units —</div>
            @else
              <ul class="u-list u-mt-xs">
                @foreach($enablers as $u)
                  <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                      draggable="true"
                      data-unit-id="{{ $u->id }}"
                      data-unit-code="{{ $u->code }}"
                      data-unit-name="{{ $u->name }}"
                      data-unit-dir="{{ $u->directorate_id ?? '' }}"
                      data-unit-dir-original="{{ $u->directorate_id ?? '' }}"
                      data-unit-group="enabler"
                      data-unit-group-original="enabler">
                    <div class="u-flex u-flex-col">
                      <span class="u-text-sm">
                        @if($u->code)
                          <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                        @endif
                        {{ $u->name }}
                      </span>
                    </div>
                    @canany(['org.create','org.update','org.delete'])
                      <div class="u-tree-actions js-edit-only">
                        <button type="button"
                                class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                data-modal-open="modalEditUnit"
                                data-unit-id="{{ $u->id }}"
                                data-unit-code="{{ $u->code }}"
                                data-unit-name="{{ $u->name }}"
                                data-unit-dir="{{ $u->directorate_id ?? '' }}"
                                data-unit-category="enabler"
                                title="Edit Unit">
                          <i class="fas fa-pen u-text-xs"></i>
                        </button>
                      </div>
                    @endcanany
                  </li>
                @endforeach
              </ul>
            @endif
          </div>

          {{-- Operasi --}}
          <div class="u-item ios-glass u-tree-col" data-drop-group="operasi">
            <div class="u-tree-head">
              <div class="u-tree-title">
                Unit Operasi
                @if($operasis->count())
                  <span class="u-badge u-badge--glass u-mr-xs">{{ $operasis->count() }}</span>
                @endif
              </div>
            </div>
            @if($operasis->isEmpty())
              <div class="u-text-xs u-muted u-mb-sm u-tree-empty">— no units —</div>
            @else
              <ul class="u-list u-mt-xs">
                @foreach($operasis as $u)
                  <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                      draggable="true"
                      data-unit-id="{{ $u->id }}"
                      data-unit-code="{{ $u->code }}"
                      data-unit-name="{{ $u->name }}"
                      data-unit-dir="{{ $u->directorate_id ?? '' }}"
                      data-unit-dir-original="{{ $u->directorate_id ?? '' }}"
                      data-unit-group="operasi"
                      data-unit-group-original="operasi">
                    <div class="u-flex u-flex-col">
                      <span class="u-text-sm">
                        @if($u->code)
                          <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                        @endif
                        {{ $u->name }}
                      </span>
                    </div>
                    @canany(['org.create','org.update','org.delete'])
                      <div class="u-tree-actions js-edit-only">
                        <button type="button"
                                class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                data-modal-open="modalEditUnit"
                                data-unit-id="{{ $u->id }}"
                                data-unit-code="{{ $u->code }}"
                                data-unit-name="{{ $u->name }}"
                                data-unit-dir="{{ $u->directorate_id ?? '' }}"
                                data-unit-category="operasi"
                                title="Edit Unit">
                          <i class="fas fa-pen u-text-xs"></i>
                        </button>
                      </div>
                    @endcanany
                  </li>
                @endforeach
              </ul>
            @endif
          </div>

          {{-- Cabang --}}
          <div class="u-item ios-glass u-tree-col" data-drop-group="cabang">
            <div class="u-tree-head">
              <div class="u-tree-title">
                Kantor Cabang
                @if($cabangs->count())
                  <span class="u-badge u-badge--glass u-mr-xs">{{ $cabangs->count() }}</span>
                @endif
              </div>
            </div>
            @if($cabangs->isEmpty())
              <div class="u-text-xs u-muted u-mb-sm u-tree-empty">— no units —</div>
            @else
              <ul class="u-list u-mt-xs">
                @foreach($cabangs as $u)
                  <li class="u-item u-tree-unit u-flex u-justify-between u-items-center u-hover-lift"
                      draggable="true"
                      data-unit-id="{{ $u->id }}"
                      data-unit-code="{{ $u->code }}"
                      data-unit-name="{{ $u->name }}"
                      data-unit-dir="{{ $u->directorate_id ?? '' }}"
                      data-unit-dir-original="{{ $u->directorate_id ?? '' }}"
                      data-unit-group="cabang"
                      data-unit-group-original="cabang">
                    <div class="u-flex u-flex-col">
                      <span class="u-text-sm">
                        @if($u->code)
                          <span class="u-badge u-badge--glass u-mr-xs">{{ $u->code }}</span>
                        @endif
                        {{ $u->name }}
                      </span>
                    </div>
                    @canany(['org.create','org.update','org.delete'])
                      <div class="u-tree-actions js-edit-only">
                        <button type="button"
                                class="u-btn u-btn--sm u-btn--outline u-btn--chip"
                                data-modal-open="modalEditUnit"
                                data-unit-id="{{ $u->id }}"
                                data-unit-code="{{ $u->code }}"
                                data-unit-name="{{ $u->name }}"
                                data-unit-dir="{{ $u->directorate_id ?? '' }}"
                                data-unit-category="cabang"
                                title="Edit Unit">
                          <i class="fas fa-pen u-text-xs"></i>
                        </button>
                      </div>
                    @endcanany
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        </div>
      </div>
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
                class="u-btn u-btn--sm u-btn--ghost u-btn--chip"
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
                class="u-btn u-btn--sm u-btn--ghost u-btn--chip"
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
                class="u-btn u-btn--sm u-btn--ghost u-btn--chip"
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
                class="u-btn u-btn--sm u-btn--ghost u-btn--chip"
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

  let editing = false;

  function setEditing(on) {
    editing = !!on;
    const bar = $('#treeEditBar');
    const btn = $('#btnEditMode');

    if (bar) bar.style.display = editing ? 'flex' : 'none';
    if (btn) {
      btn.classList.toggle('is-active', editing);
      btn.innerHTML = editing
        ? '<i class="fas fa-ban u-text-xs u-mr-xs"></i> Exit Edit'
        : '<i class="fas fa-edit u-text-xs u-mr-xs"></i> Edit Layout';
    }

    $$('.js-edit-only').forEach(el => {
      el.style.display = editing ? '' : 'none';
    });

    $$('#treeWrap, #unitWrap').forEach(w => {
      if (w) w.dataset.editing = editing ? '1' : '0';
    });

    // set draggable attribute only in edit mode
    $$('.u-tree-unit').forEach(li => {
      li.setAttribute('draggable', editing ? 'true' : 'false');
    });
  }

  setEditing(false); // default: off

  const btnEditMode   = $('#btnEditMode');
  const btnCancel     = $('#btnCancelLayout');
  const btnSaveLayout = $('#btnSaveLayout');

  if (btnEditMode) {
    btnEditMode.addEventListener('click', function () {
      setEditing(!editing);
    });
  }

  if (btnCancel) {
    btnCancel.addEventListener('click', function () {
      showConfirm(
        'Batalkan semua perubahan layout yang belum disimpan?',
        'Konfirmasi'
      ).then((result) => {
        if (result.isConfirmed) {
          window.location.reload();
        }
      });
    });
  }

  // ---------- Tabs By Directorate / By Unit Group ----------
  $$('.u-tab[data-org-main-tab]').forEach(tab => {
    tab.addEventListener('click', function () {
      const target = this.getAttribute('data-org-main-tab');
      $$('.u-tab[data-org-main-tab]').forEach(t => t.classList.remove('is-active'));
      this.classList.add('is-active');

      $('#panelByDir')?.classList.remove('is-active');
      $('#panelByUnit')?.classList.remove('is-active');

      if (target === 'by-dir') {
        $('#panelByDir')?.classList.add('is-active');
      } else {
        $('#panelByUnit')?.classList.add('is-active');
      }
    });
  });

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
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      $$('.u-modal').forEach(m => m.hidden = true);
      document.body.classList.remove('modal-open');
    }
  });

  // ---------- DRAG & DROP (dua board, tapi hanya saat editing) ----------
  function findLiInSamePanel(zone, unitId) {
    const panel = zone.closest('.u-panel');
    if (!panel) return null;
    return panel.querySelector('.u-tree-unit[data-unit-id="' + unitId + '"]');
  }

  document.addEventListener('dragstart', function (e) {
    if (!editing) return;
    const li = e.target.closest('.u-tree-unit');
    if (!li) return;
    const id = li.getAttribute('data-unit-id');
    if (!id) return;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', id);
  });

  document.addEventListener('dragover', function (e) {
    if (!editing) return;
    const zone = e.target.closest('[data-drop-dir],[data-drop-group]');
    if (!zone) return;
    e.preventDefault();
  });

  document.addEventListener('drop', function (e) {
    if (!editing) return;
    const zone = e.target.closest('[data-drop-dir],[data-drop-group]');
    if (!zone) return;
    e.preventDefault();

    const unitId = e.dataTransfer.getData('text/plain');
    if (!unitId) return;

    const li = findLiInSamePanel(zone, unitId);
    if (!li) return;

    let ul = zone.querySelector('ul.u-list');
    if (!ul) {
      ul = document.createElement('ul');
      ul.className = 'u-list u-mt-xs';
      zone.appendChild(ul);
    }
    const empty = zone.querySelector('.u-tree-empty');
    if (empty) empty.remove();

    ul.appendChild(li);

    if (zone.hasAttribute('data-drop-dir')) {
      const newDir = zone.getAttribute('data-drop-dir') || '';
      li.dataset.unitDir = newDir;
    }
    if (zone.hasAttribute('data-drop-group')) {
      const newGroup = zone.getAttribute('data-drop-group') || '';
      li.dataset.unitGroup = newGroup;
    }
  });

  // ---------- SAVE LAYOUT (bulk hit ke /admin/org/units/{id}) ----------
  async function saveLayout() {
    const all = {};
    $$('.u-tree-unit').forEach(li => {
      const id = li.dataset.unitId;
      if (!id) return;

      if (!all[id]) {
        all[id] = {
          id,
          name: li.dataset.unitName || '',
          code: li.dataset.unitCode || '',
          dir: li.dataset.unitDir || '',
          group: li.dataset.unitGroup || '',
          origDir: li.dataset.unitDirOriginal || '',
          origGroup: li.dataset.unitGroupOriginal || ''
        };
      } else {
        // merge perubahan dari panel lain (kalau ada)
        if (li.dataset.unitDir)   all[id].dir   = li.dataset.unitDir;
        if (li.dataset.unitGroup) all[id].group = li.dataset.unitGroup;
      }
    });

    const changed = Object.values(all).filter(it => {
      return (it.dir !== it.origDir) || (it.group !== it.origGroup);
    });

    if (changed.length === 0) {
      showInfo('Tidak ada perubahan layout', 'Info');
      return;
    }

    const confirmResult = await showConfirm(
      'Simpan perubahan layout untuk ' + changed.length + ' unit?',
      'Konfirmasi Simpan'
    );
    
    if (!confirmResult.isConfirmed) {
      return;
    }

    if (!csrf) {
      showError('CSRF token tidak ditemukan', 'Error');
      return;
    }

    if (btnSaveLayout) {
      btnSaveLayout.disabled = true;
      btnSaveLayout.innerHTML = '<i class="fas fa-spinner fa-spin u-text-xs u-mr-xs"></i> Saving...';
    }

    showLoading('Menyimpan perubahan layout...');

    try {
      for (const item of changed) {
        const url = "{{ url('/admin/org/units') }}/" + item.id;
        const fd  = new FormData();
        fd.append('_token', csrf);
        fd.append('_method', 'PUT');
        fd.append('code', item.code || '');
        fd.append('name', item.name || '');
        fd.append('directorate_id', item.dir || '');
        fd.append('category', item.group || '');

        const res = await fetch(url, { method:'POST', body: fd });
        if (!res.ok) {
          throw new Error('HTTP ' + res.status + ' on unit ' + item.id);
        }
      }

      showSuccess('Layout berhasil disimpan', 'Berhasil');
      setTimeout(() => window.location.reload(), 1500);
    } catch (err) {
      console.error(err);
      showError('Gagal menyimpan layout: ' + err.message, 'Error');
      if (btnSaveLayout) {
        btnSaveLayout.disabled = false;
        btnSaveLayout.innerHTML = '<i class="fas fa-save u-text-xs u-mr-xs"></i> Save Changes';
      }
    }
  }

  if (btnSaveLayout) {
    btnSaveLayout.addEventListener('click', function () {
      if (!editing) {
        showWarning('Aktifkan Edit Layout terlebih dahulu', 'Peringatan');
        return;
      }
      saveLayout();
    });
  }
});
</script>
@endsection
