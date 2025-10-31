<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>@yield('title','Talent PTSI')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Seed theme dari localStorage (hindari FOUC; set data-theme + class dark) -->
  <script>
    (function(){
      try{
        var t = localStorage.getItem('theme');
        if (t === 'dark' || t === 'light') {
          document.documentElement.setAttribute('data-theme', t);
          document.documentElement.classList.toggle('dark', t === 'dark');
        }
      }catch(_){}
    })();
  </script>

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

  <!-- Styles & Scripts via Vite -->
  @vite([
    'resources/css/app.css',
    'resources/css/app-layout.css',
    'resources/css/app-ui.css',
    'resources/js/app-layout.js', /* all behavior is here */
    'resources/js/app.js'         /* (opsional, jika ada file lain) */
  ])
  @php use Illuminate\Support\Facades\Route as Rt; @endphp

  <style>.dm-fab{z-index:9999!important}</style>
</head>

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
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
      $trOpen  = str_starts_with(request()->route()->getName() ?? '', 'training.');
      $acOpen  = request()->routeIs('admin.users.*')
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
                data-accordion="nav-recruitment"
                aria-expanded="{{ $recOpen ? 'true' : 'false' }}">
          <span class="icon">👥</span>
          <span class="label">Recruitment</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-recruitment" class="nav-children {{ $recOpen ? 'open' : '' }}" data-accordion-panel="nav-recruitment">
          @if($isSuper || $user?->can('recruitment.view'))
          <a class="nav-item nav-child {{ request()->routeIs('recruitment.monitoring')?'active':'' }}"
             href="{{ Rt::has('recruitment.monitoring') ? route('recruitment.monitoring') : '#' }}">
            <span class="icon">📊</span><span class="label">Monitoring</span>
          </a>
          @endif

          @if($isSuper || $user?->can('recruitment.view'))
          <a class="nav-item nav-child {{ request()->routeIs('recruitment.principal-approval*')?'active':'' }}"
             href="{{ Rt::has('recruitment.principal-approval.index') ? route('recruitment.principal-approval.index') : '#' }}">
            <span class="icon">✅</span><span class="label">Principal Approval</span>
          </a>
          @endif

          @if($isSuper || $user?->can('contract.view'))
          <a class="nav-item nav-child {{ request()->routeIs('recruitment.contracts*')?'active':'' }}"
             href="{{ Rt::has('recruitment.contracts.index') ? route('recruitment.contracts.index') : '#' }}">
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
                data-accordion="nav-training"
                aria-expanded="{{ $trOpen ? 'true' : 'false' }}">
          <span class="icon">🎓</span>
          <span class="label">Training</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-training" class="nav-children {{ $trOpen ? 'open' : '' }}" data-accordion-panel="nav-training">
          @if($isSuper || $user?->can('training.view'))
          <a class="nav-item nav-child {{ request()->routeIs('training.monitoring')?'active':'' }}"
             href="{{ Rt::has('training.monitoring') ? route('training.monitoring') : '#' }}">
            <span class="icon">📈</span><span class="label">Monitoring</span>
          </a>
          @endif

          @if($isSuper || $user?->can('training.view'))
          <a class="nav-item nav-child {{ request()->routeIs('training.principal-approval')?'active':'' }}"
             href="{{ Rt::has('training.principal-approval') ? route('training.principal-approval') : '#' }}">
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
                  data-accordion="nav-access"
                  aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
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
      <input type="search" placeholder="Search…" aria-label="Search">
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
            <button id="changePwBtn" type="button" class="menu-item" onclick="openPwModal()"><span>Change Password</span></button>
          </div>

          <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <div id="poweroff" class="poweroff" data-threshold="0.6" role="slider"
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

  <!-- ===== Main ===== -->
  <main class="main" id="main">
    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @yield('content')
  </main>

  <!-- FAB -->
  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>

  <!-- ===== Password Modal (u-modal) ===== -->
  <div id="changePasswordModal" class="u-modal" hidden>
    <div class="u-modal__card">
      <div class="u-modal__head">
        <div class="u-flex u-items-center u-gap-md">
          <div class="u-avatar u-avatar--lg u-avatar--brand">
            <i class="fas fa-key"></i>
          </div>
          <div>
            <div class="u-title">Ganti Password</div>
            <div class="u-muted u-text-sm">Perbarui kata sandi akun Anda</div>
          </div>
        </div>
        <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Tutup">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('account.password.update') }}" class="u-modal__body u-p-md" id="changePwForm">
        @csrf
        <div class="u-grid-2 u-stack-mobile u-gap-md">
          <div class="u-grid-col-span-2 u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Password Saat Ini</label>
            <input name="current_password" type="password" class="u-input" required>
          </div>

          <div class="u-grid-col-span-2 u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Password Baru</label>
            <input name="password" type="password" class="u-input" required minlength="8">
            <p class="u-text-xs u-muted u-mt-xs">Minimal 8 karakter</p>
          </div>

          <div class="u-grid-col-span-2 u-space-y-sm">
            <label class="u-block u-text-sm u-font-medium u-mb-sm">Konfirmasi Password Baru</label>
            <input name="password_confirmation" type="password" class="u-input" required minlength="8">
          </div>
        </div>
      </form>

      <div class="u-modal__foot">
        <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
        <div class="u-flex u-gap-sm">
          <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
          <button form="changePwForm" class="u-btn u-btn--brand u-hover-lift">
            <i class="fas fa-save u-mr-xs"></i> Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
