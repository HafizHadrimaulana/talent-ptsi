<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
    <title>@yield('title','Talent PTSI')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Flash Swal lewat META (aman untuk parser/linter) --}}
    @if(session('swal'))
      <meta name="swal" content='@json(session('swal'))'>
    @endif

    <!-- Seed theme dari localStorage (hindari FOUC) -->
    <script>
    (function() {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-theme', t);
                document.documentElement.classList.toggle('dark', t === 'dark');
            }
        } catch (_) {}
    })();
    </script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Vite assets -->
    @vite('resources/css/app.css')
    @vite('resources/css/app-layout.css')
    @vite('resources/css/app-ui.css')
    @vite('resources/js/app-layout.js')
    @vite('resources/js/app.js')
</head>

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
    <!-- UNIVERSAL iOS LIQUID GLASS LOADER -->
    <div id="appLoader" aria-live="polite" aria-busy="true">
        <div class="loader-card glass">
            <div class="liquid" aria-hidden="true">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>
            <div class="loader-title">Loading…</div>
        </div>
    </div>

    <div class="overlay" id="overlay" hidden></div>

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar glass" id="sidebar" aria-label="Primary navigation" data-scroll-area>
        <div class="brand">
            <a href="{{ route('dashboard') }}" class="brand-link" aria-label="Dashboard">
                <img src="{{ Vite::asset('resources/images/sapahc.png') }}" alt="Logo" class="logo">
            </a>
        </div>

        @php
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $roleNames = collect($user?->getRoleNames() ?? [])->map(fn($r)=> strtolower(trim($r)));
        $isSuper = $roleNames->contains(fn($r)=> in_array($r, ['superadmin','super-admin','admin','administrator']));
        $emp = $user?->employee ?? null;
        $jobTitle = $emp?->job_title ?: '-';
        $unitName = $emp?->unit_name ?: optional($emp?->unit)->name ?: '-';
        $showMain = true;
        $showRecruitment = $isSuper || $user?->hasAnyPermission(['recruitment.view','contract.view']);
        $showTraining = $isSuper || $user?->hasAnyPermission(['training.view']);
        $showSettings = $user && ($user->can('users.view') || $user->can('rbac.view') || $user->can('employees.view'));
        $printedAnySection = false;
        $recOpen = str_starts_with(request()->route()->getName() ?? '', 'recruitment.');
        $trOpen = str_starts_with(request()->route()->getName() ?? '', 'training.');
        $acOpen = request()->routeIs('admin.users.*')
            || request()->routeIs('admin.roles.*')
            || request()->routeIs('admin.permissions.*')
            || request()->routeIs('admin.employees.*');
        @endphp

        @if($showMain)
        <nav class="nav-section">
            <div class="nav-title">Main</div>
            <div class="nav">
                <a class="nav-item {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">
                    <span class="icon">🏠</span><span class="label">Dashboard</span>
                </a>
            </div>
        </nav>
        @php $printedAnySection = true; @endphp
        @endif

        @if($printedAnySection && $showRecruitment)
        <div class="nav-divider" aria-hidden="true"></div>
        @endif

        @if($showRecruitment)
        <nav class="nav-section">
            <div class="nav-title">Recruitment</div>
            <div class="nav">
                <button type="button" class="nav-item js-accordion {{ $recOpen ? 'open' : '' }}"
                        data-accordion="nav-recruitment" aria-expanded="{{ $recOpen ? 'true' : 'false' }}">
                    <span class="icon">👥</span>
                    <span class="label">Recruitment</span>
                    <span class="chev">▾</span>
                </button>

                <div id="nav-recruitment" class="nav-children {{ $recOpen ? 'open' : '' }}"
                     data-accordion-panel="nav-recruitment">
                    @if($isSuper || $user?->can('recruitment.view'))
                    <a class="nav-item nav-child {{ request()->routeIs('recruitment.monitoring')?'active':'' }}"
                       href="{{ \Illuminate\Support\Facades\Route::has('recruitment.monitoring') ? route('recruitment.monitoring') : '#' }}">
                        <span class="icon">📊</span><span class="label">Monitoring</span>
                    </a>
                    @endif

                    @if($isSuper || $user?->can('recruitment.view'))
                    <a class="nav-item nav-child {{ request()->routeIs('recruitment.principal-approval*')?'active':'' }}"
                       href="{{ \Illuminate\Support\Facades\Route::has('recruitment.principal-approval.index') ? route('recruitment.principal-approval.index') : '#' }}">
                        <span class="icon">✅</span><span class="label">Principal Approval</span>
                    </a>
                    @endif

                    @if($isSuper || $user?->can('contract.view'))
                    <a class="nav-item nav-child {{ request()->routeIs('recruitment.contracts*')?'active':'' }}"
                       href="{{ \Illuminate\Support\Facades\Route::has('recruitment.contracts.index') ? route('recruitment.contracts.index') : '#' }}">
                        <span class="icon">📝</span><span class="label">Contracts</span>
                    </a>
                    @endif
                </div>
            </div>
        </nav>
        @php $printedAnySection = true; @endphp
        @endif

        @if($printedAnySection && $showTraining)
        <div class="nav-divider" aria-hidden="true"></div>
        @endif

        @if($showTraining)
        <nav class="nav-section">
            <div class="nav-title">Training</div>
            <div class="nav">
                <button type="button" class="nav-item js-accordion {{ $trOpen ? 'open' : '' }}"
                        data-accordion="nav-training" aria-expanded="{{ $trOpen ? 'true' : 'false' }}">
                    <span class="icon">🎓</span>
                    <span class="label">Training</span>
                    <span class="chev">▾</span>
                </button>

                <div id="nav-training" class="nav-children {{ $trOpen ? 'open' : '' }}"
                     data-accordion-panel="nav-training">
                    @if($isSuper || $user?->can('training.view'))
                    <a class="nav-item nav-child {{ request()->routeIs('training.monitoring')?'active':'' }}"
                       href="{{ \Illuminate\Support\Facades\Route::has('training.monitoring') ? route('training.monitoring') : '#' }}">
                        <span class="icon">📈</span><span class="label">Monitoring</span>
                    </a>
                    @endif

                    @if($isSuper || $user?->can('training.view'))
                    <a class="nav-item nav-child {{ request()->routeIs('training.principal-approval')?'active':'' }}"
                       href="{{ \Illuminate\Support\Facades\Route::has('training.principal-approval') ? route('training.principal-approval') : '#' }}">
                        <span class="icon">🗂️</span><span class="label">Principal Approval</span>
                    </a>
                    @endif
                </div>
            </div>
        </nav>
        @php $printedAnySection = true; @endphp
        @endif

        @if($printedAnySection && $showSettings)
        <div class="nav-divider" aria-hidden="true"></div>
        @endif

        @if($showSettings)
        <nav class="nav-section">
            <div class="nav-title">Settings</div>
            <div class="nav">
                @canany(['users.view','rbac.view','employees.view'])
                <button type="button" class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}"
                        data-accordion="nav-access" aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
                    <span class="icon">🧭</span>
                    <span class="label">Access Management</span>
                    <span class="chev">▾</span>
                </button>

                <div id="nav-access" class="nav-children {{ $acOpen ? 'open' : '' }}" data-accordion-panel="nav-access">
                    @can('users.view')
                    <a class="nav-item nav-child {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <span class="icon">👤</span><span class="label">User Management</span>
                    </a>
                    @endcan

                    @can('rbac.view')
                    <a class="nav-item nav-child {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                       href="{{ route('admin.roles.index') }}">
                        <span class="icon">🛡️</span><span class="label">Role Management</span>
                    </a>
                    <a class="nav-item nav-child {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"
                       href="{{ route('admin.permissions.index') }}">
                        <span class="icon">🔐</span><span class="label">Permission Management</span>
                    </a>
                    @endcan

                    @can('employees.view')
                    <a class="nav-item nav-child {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}"
                       href="{{ route('admin.employees.index') }}">
                        <span class="icon">🗃️</span><span class="label">Employee Directory</span>
                    </a>
                    @endcan
                </div>
                @endcanany
            </div>
        </nav>
        @endif
    </aside>

    <!-- ===== Topbar ===== -->
    <header class="topbar glass floating" id="topbar">
        <button id="hamburgerBtn" class="hamburger" aria-label="Toggle sidebar" aria-expanded="false">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span>
        </button>

        <div class="search">
            <span class="search-icon">🔎</span>
            <!-- Penting: name="q" agar modul datatables.js bisa bind -->
            <input type="search" name="q" id="globalSearch"
                   placeholder="Search Everything…" aria-label="Search Everything">
        </div>

        <div class="top-actions">
            <div class="dropdown-wrap">
                <button id="notifBtn" class="top-btn" type="button" aria-expanded="false" aria-haspopup="true" title="Notifications">
                    <i class="fa-solid fa-bell bell-icon"></i>
                </button>
                <div id="notifDropdown" class="dropdown" hidden>
                    <div class="dropdown-header">Notifications
                        <button class="close-btn" type="button" data-close="#notifDropdown">✖</button>
                    </div>
                    <div class="muted text-sm">No notifications yet.</div>
                </div>
            </div>

            <div class="dropdown-wrap user-area">
                @php $roleBadge = $user?->getRoleNames()->first() ?? '-'; @endphp
                <button id="userBtn" class="user-chip" type="button" aria-haspopup="true" aria-expanded="false" title="User menu">
                    <span class="avatar">PT</span>
                    <span class="user-meta">
                        <span class="user-name text-ellipsis">{{ $user->name ?? 'Guest' }}</span>
                        <span class="user-role muted">
                            {{ $roleBadge }}
                            @php
                                $jab = $user?->job_title ?? $user?->employee?->job_title ?? $user?->employee?->position_name;
                                $unit= $user?->employee?->unit_name ?? optional($user?->unit)->name;
                            @endphp
                            @if($jab) • {{ $jab }} @endif
                            @if($unit) • {{ $unit }} @endif
                        </span>
                    </span>
                    <span class="chev">▾</span>
                </button>

                <div id="userDropdown" class="dropdown user-dropdown" hidden>
                    <div class="user-card">
                        <div class="avatar lg">PT</div>
                        <div class="user-info">
                            <strong>{{ $user->name ?? 'Guest' }}</strong>
                            <span class="muted text-sm">{{ $user->email ?? '-' }}</span>
                            <div class="muted text-sm">
                                <div><strong>Role:</strong> {{ $roleBadge }}</div>
                                <div><strong>Jabatan:</strong> {{ $jobTitle }}</div>
                                <div><strong>Unit Kerja:</strong> {{ $unitName }}</div>
                                <div><strong>Employee ID:</strong> {{ $user->employee_id ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

          <div class="menu-list">
            <button id="changePwBtn" type="button" class="menu-item cursor-pointer" onclick="openPwModal()"><span>Change Password</span></button>
          </div>
                    <div class="menu-list">
                        <button id="changePwBtn" type="button" class="menu-item" onclick="openPwModal()">
                            <span>Change Password</span>
                        </button>
                    </div>

          <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <div id="poweroff" class="poweroff cursor-pointer" data-threshold="0.6" role="slider"
                 aria-label="Swipe To Signout" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
              <span class="power-icon">⏻</span>
              <span class="power-text">Swipe To Sign out</span>
              <div class="power-knob" id="powerKnob"></div>
            </div>
            <noscript><button class="btn btn-outline w-full mt-2">Logout</button></noscript>
          </form>
        </div>
      </div>
    </div>
  </header>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== Main ===== -->
    <main class="main" id="main">
        @yield('content')
    </main>

    <!-- FAB -->
    <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>

    <!-- ===== Password Modal (u-modal) ===== -->
    {{-- (isi modal ganti password tetap sama) --}}

    <!-- ===== Loader Controller ===== -->
    <script>
    (function () {
        var root = document.getElementById('appLoader');
        if (!root) return;

        var hide = function(){ root.classList.add('is-hidden'); root.setAttribute('aria-busy','false'); };
        var show = function(){ root.classList.remove('is-hidden'); root.setAttribute('aria-busy','true'); };
        var doneOnce = false;
        var done = function(){ if (doneOnce) return; doneOnce = true; hide(); };

        window.appLoader = { show: show, hide: hide, done: done };
        window.addEventListener('load', function(){ setTimeout(done, 120); });

        document.addEventListener('DOMContentLoaded', function(){
            try{
                if (window.jQuery){
                    var $ = window.jQuery;
                    $(document).on('init.dt', function(){ setTimeout(done, 60); });
                }
            }catch(_){}
        });

        window.addEventListener('app:ready', done, { once: true });
        window.addEventListener('beforeunload', function(){ show(); });
    })();
    </script>

    <!-- ===== SweetAlert2 Universal (iOS Liquid Glass) ===== -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function () {
      // 1) Mixin iOS glass
      window.iosSwal = Swal.mixin({
        background: 'rgba(255,255,255,0.35)',
        backdrop: 'rgba(15,23,42,.35)',
        color: '#0f172a',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#94a3b8',
        customClass: {
          popup: 'ios-glass',
          title: 'font-semibold',
          confirmButton: 'u-btn u-btn--brand rounded-xl',
          cancelButton: 'u-btn u-btn--ghost rounded-xl'
        }
      });

      // 2) Toast helpers (glass)
      const toastBase = {
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 2200, timerProgressBar: true,
        customClass: { popup: 'swal2-toast ios-glass' }
      };
      window.toastOk  = (title='Berhasil', text='') => Swal.fire({ ...toastBase, icon:'success', title, text });
      window.toastErr = (title='Gagal', text='')    => Swal.fire({ ...toastBase, icon:'error',   title, text });

      // 3) Konfirmasi universal → Promise<boolean>
      window.iosConfirm = function (opts = {}) {
        const base = {
          icon: 'warning',
          title: 'Yakin?',
          text: 'Aksi ini tidak bisa dibatalkan.',
          showCancelButton: true,
          confirmButtonText: 'Ya, lanjut',
          cancelButtonText: 'Batal',
          reverseButtons: true
        };
        return window.iosSwal.fire({ ...base, ...opts }).then(r => !!r.isConfirmed);
      };

      // 4) Tembak swal dari META "swal" (opsi modal)
      const meta = document.querySelector('meta[name="swal"]');
      if (meta) {
        try {
          const payload = JSON.parse(meta.getAttribute('content') || '{}');
          if (payload && typeof payload === 'object') window.iosSwal.fire(payload);
        } catch (e) {}
      }

      // 5) Interceptor form konfirmasi (class="js-confirm")
      document.addEventListener('submit', async function(e){
        const f = e.target;
        if (!f.classList || !f.classList.contains('js-confirm')) return;
        e.preventDefault();
        const ok = await window.iosConfirm({
          title: f.getAttribute('data-confirm-title') || 'Yakin?',
          text:  f.getAttribute('data-confirm-text')  || 'Lanjutkan aksi ini?',
          icon:  f.getAttribute('data-confirm-icon')  || 'question'
        });
        if (ok) f.submit();
      });
    })();
    </script>
    <style>
      .ios-glass{
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border-radius: 16px !important;
        border: 1px solid rgba(255,255,255,.4);
        box-shadow: 0 8px 32px rgba(31,38,135,.15), inset 0 0 0 1px rgba(255,255,255,.06);
      }
      .swal2-toast.ios-glass{
        border-radius: 14px !important;
        background: var(--glass-bg) !important;
        border: var(--glass-brd) !important;
        backdrop-filter: blur(12px) saturate(160%) !important;
        -webkit-backdrop-filter: blur(12px) saturate(160%) !important;
        box-shadow: var(--shadow-lg) !important;
      }
    </style>

    @stack('swal')
</body>
</html>
