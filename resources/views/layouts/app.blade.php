<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>@yield('title','Talent PTSI')</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    .bell-icon { font-size: 1.5rem; }
    .nav-divider { height:1px; background:var(--divider,rgba(0,0,0,.08)); margin:.5rem 1rem; }
  </style>
  @vite([
    'resources/css/app.css',
    'resources/css/app-layout.css',
    'resources/css/app-ui.css',
    'resources/js/app-layout.js',
    'resources/js/app.js'
  ])
@php use Illuminate\Support\Facades\Route as Rt; @endphp

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <script>
    (function(){
      try{
        var saved = localStorage.getItem('sidebar-collapsed');
        var shouldCollapse = (saved === null) ? true : (saved === '1');
        if (shouldCollapse && window.matchMedia('(min-width:1025px)').matches) {
          document.body.classList.add('sidebar-collapsed');
        }
      }catch(_){}
    })();
  </script>

  <div class="overlay" id="overlay" hidden></div>

  <!-- Sidebar -->
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

      // --- SECTION VISIBILITY ---
      $showMain = true;
      $showRecruitment = $isSuper || $user?->hasAnyPermission([
        'recruitment.view','contract.view'
      ]);
      $showTraining = $isSuper || $user?->hasAnyPermission([
        'training.view'
      ]);
      $showSettings = $user && ($user->can('users.view') || $user->can('rbac.view'));

      // Divider logic
      $printedAnySection = false;

      // Open states
      $recOpen = str_starts_with(request()->route()->getName() ?? '', 'recruitment.');
      $trOpen  = str_starts_with(request()->route()->getName() ?? '', 'training.');
      $acOpen  = request()->routeIs('settings.users.*')
                || request()->routeIs('settings.roles.*')
                || request()->routeIs('settings.permissions.*');
    @endphp

    <!-- MAIN -->
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

    {{-- Divider before Recruitment --}}
    @if($printedAnySection && $showRecruitment)
      <div class="nav-divider" aria-hidden="true"></div>
    @endif

    <!-- RECRUITMENT -->
    @if($showRecruitment)
    <nav class="nav-section">
      <div class="nav-title">Recruitment</div>
      <div class="nav">
        <button type="button"
                class="nav-item js-accordion {{ $recOpen ? 'open' : '' }}"
                data-accordion="nav-recruitment"
                aria-expanded="{{ $recOpen ? 'true' : 'false' }}">
          <span class="icon">👥</span>
          <span class="label">Recruitment</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-recruitment"
             class="nav-children {{ $recOpen ? 'open' : '' }}"
             data-accordion-panel="nav-recruitment">

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

    {{-- Divider before Training --}}
    @if($printedAnySection && $showTraining)
      <div class="nav-divider" aria-hidden="true"></div>
    @endif

    <!-- TRAINING -->
    @if($showTraining)
    <nav class="nav-section">
      <div class="nav-title">Training</div>
      <div class="nav">
        <button type="button"
                class="nav-item js-accordion {{ $trOpen ? 'open' : '' }}"
                data-accordion="nav-training"
                aria-expanded="{{ $trOpen ? 'true' : 'false' }}">
          <span class="icon">🎓</span>
          <span class="label">Training</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-training"
             class="nav-children {{ $trOpen ? 'open' : '' }}"
             data-accordion-panel="nav-training">

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

    {{-- Divider before Settings --}}
    @if($printedAnySection && $showSettings)
      <div class="nav-divider" aria-hidden="true"></div>
    @endif

    <!-- SETTINGS -->
    @if($showSettings)
    <nav class="nav-section">
      <div class="nav-title">Settings</div>
      <div class="nav">
        @canany(['users.view','rbac.view'])
          <button type="button"
                  class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}"
                  data-accordion="nav-access"
                  aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
            <span class="icon">🧭</span>
            <span class="label">Access Management</span>
            <span class="chev">▾</span>
          </button>

          <div id="nav-access"
               class="nav-children {{ $acOpen ? 'open' : '' }}"
               data-accordion-panel="nav-access">
            @can('users.view')
            <a class="nav-item nav-child {{ request()->routeIs('settings.users.*') ? 'active' : '' }}"
               href="{{ route('settings.users.index') }}">
              <span class="icon">👤</span><span class="label">User Management</span>
            </a>
            @endcan

            @can('rbac.view')
            <a class="nav-item nav-child {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}"
               href="{{ route('settings.roles.index') }}">
              <span class="icon">🛡️</span><span class="label">Role Management</span>
            </a>
            <a class="nav-item nav-child {{ request()->routeIs('settings.permissions.*') ? 'active' : '' }}"
               href="{{ route('settings.permissions.index') }}">
              <span class="icon">🔐</span><span class="label">Permission Management</span>
            </a>
            @endcan
          </div>
        @endcanany
      </div>
    </nav>
    @endif
  </aside>

  <!-- Topbar -->
  <header class="topbar glass">
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
            <button class="close-btn" type="button" onclick="closeNotifDropdown()">✖</button>
          </div>
          <div class="muted text-sm">No notifications yet.</div>
        </div>
      </div>

      <div class="dropdown-wrap user-area">
        <button id="userBtn" class="user-chip" type="button" aria-haspopup="true" aria-expanded="false" title="User menu">
          <span class="avatar">PT</span>
          <span class="user-meta">
            <span class="user-name text-ellipsis">{{ auth()->user()->name ?? 'Guest' }}</span>
            <span class="user-role muted">{{ auth()->user()?->getRoleNames()->first() ?? '-' }}</span>
          </span>
          <span class="chev">▾</span>
        </button>

        <div id="userDropdown" class="dropdown user-dropdown" hidden>
          <div class="user-card">
            <div class="avatar lg">PT</div>
            <div class="user-info">
              <strong>{{ auth()->user()->name ?? 'Guest' }}</strong>
              <span class="muted text-sm">{{ auth()->user()->email ?? '-' }}</span>
            </div>
          </div>
          <div class="menu-list">
            <a class="menu-item" href="#"><span>Change Password</span></a>
          </div>

          <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <div id="poweroff" class="poweroff" data-threshold="0.6" role="slider"
                 aria-label="Swipe To Signout" aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="0" tabindex="0">
              <span class="power-icon">⏻</span>
              <span class="power-text">Swipe To Sign out</span>
              <div id="powerKnob" class="power-knob"></div>
            </div>
            <noscript><button class="btn btn-outline w-full mt-2">Logout</button></noscript>
          </form>
        </div>
      </div>
    </div>
  </header>

  <main class="main">
    @yield('content')
  </main>

  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>
  <script>
    function closeNotifDropdown() {
      const notifDropdown = document.getElementById('notifDropdown');
      const notifBtn = document.getElementById('notifBtn');
      if (!notifDropdown) return;
      notifDropdown.setAttribute('hidden','');
      if (notifBtn) notifBtn.setAttribute('aria-expanded','false');
    }
  </script>
</body>
</html>
