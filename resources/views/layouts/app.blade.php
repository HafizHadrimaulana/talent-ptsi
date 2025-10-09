<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>@yield('title','Talent PTSI')</title>
  @vite([
    'resources/css/app.css',
    'resources/css/app-layout.css',
    'resources/css/app-ui.css',
    'resources/js/app-layout.js',
    'resources/js/app.js'
  ])
@php use Illuminate\Support\Facades\Route as Rt; @endphp

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <!-- keep first-paint collapsed helper -->
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

    <!-- MAIN -->
    <nav class="nav-section">
      <div class="nav-title">Main</div>
      <div class="nav">
        <a class="nav-item {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">
          <span class="icon">🏠</span><span class="label">Dashboard</span>
        </a>
      </div>
    </nav>

    <!-- REKRUTMEN -->
    <nav class="nav-section">
      <div class="nav-title">Rekrutmen</div>
      <div class="nav">
        @php $rkOpen = str_starts_with(request()->route()->getName() ?? '', 'rekrutmen.'); @endphp

        <button type="button"
                class="nav-item js-accordion {{ $rkOpen ? 'open' : '' }}"
                data-accordion="nav-rekrutmen"
                aria-expanded="{{ $rkOpen ? 'true' : 'false' }}">
          <span class="icon">👥</span>
          <span class="label">Rekrutmen</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-rekrutmen"
             class="nav-children {{ $rkOpen ? 'open' : '' }}"
             data-accordion-panel="nav-rekrutmen">
          <a class="nav-item nav-child {{ request()->routeIs('rekrutmen.monitoring')?'active':'' }}"
             href="{{ Rt::has('rekrutmen.monitoring') ? route('rekrutmen.monitoring') : '#' }}">
            <span class="icon">📊</span><span class="label">Monitoring</span>
          </a>
          <a class="nav-item nav-child {{ request()->routeIs('rekrutmen.izin-prinsip*')?'active':'' }}"
             href="{{ Rt::has('rekrutmen.izin-prinsip.index') ? route('rekrutmen.izin-prinsip.index') : '#' }}">
            <span class="icon">✅</span><span class="label">Izin Prinsip</span>
          </a>
          <a class="nav-item nav-child {{ request()->routeIs('rekrutmen.kontrak*')?'active':'' }}"
             href="{{ Rt::has('rekrutmen.kontrak.index') ? route('rekrutmen.kontrak.index') : '#' }}">
            <span class="icon">📝</span><span class="label">Penerbitan Kontrak</span>
          </a>
        </div>
      </div>
    </nav>

    <!-- PELATIHAN -->
    <nav class="nav-section">
      <div class="nav-title">Pelatihan</div>
      <div class="nav">
        @php $plOpen = str_starts_with(request()->route()->getName() ?? '', 'pelatihan.'); @endphp

        <button type="button"
                class="nav-item js-accordion {{ $plOpen ? 'open' : '' }}"
                data-accordion="nav-pelatihan"
                aria-expanded="{{ $plOpen ? 'true' : 'false' }}">
          <span class="icon">🎓</span>
          <span class="label">Pelatihan</span>
          <span class="chev">▾</span>
        </button>

        <div id="nav-pelatihan"
             class="nav-children {{ $plOpen ? 'open' : '' }}"
             data-accordion-panel="nav-pelatihan">
          <a class="nav-item nav-child {{ request()->routeIs('pelatihan.monitoring')?'active':'' }}"
             href="{{ Rt::has('pelatihan.monitoring') ? route('pelatihan.monitoring') : '#' }}">
            <span class="icon">📈</span><span class="label">Monitoring</span>
          </a>
          <a class="nav-item nav-child {{ request()->routeIs('pelatihan.izin-prinsip')?'active':'' }}"
             href="{{ Rt::has('pelatihan.izin-prinsip') ? route('pelatihan.izin-prinsip') : '#' }}">
            <span class="icon">🗂️</span><span class="label">Izin Prinsip</span>
          </a>
        </div>
      </div>
    </nav>

    <!-- SETTINGS -->
    <nav class="nav-section">
      <div class="nav-title">Settings</div>
      <div class="nav">
        @canany(['users.view','rbac.view'])
          @php
            $acOpen = request()->routeIs('settings.users.*')
                   || request()->routeIs('settings.roles.*')
                   || request()->routeIs('settings.permissions.*');
          @endphp

          <button type="button"
                  class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}"
                  data-accordion="nav-access"
                  aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
            <span class="icon">🧭</span>
            <span class="label">Manajemen Akses</span>
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
        <button id="notifBtn" class="top-btn" type="button" aria-expanded="false" aria-haspopup="true" title="Notifications">🔔</button>
        <div id="notifDropdown" class="dropdown" hidden>
          <div class="dropdown-header">Notifikasi <button class="close-btn" type="button"  onclick="closeNotifDropdown()">✖</button></div>
          <div class="muted text-sm">Belum ada notifikasi.</div>
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
                 aria-label="Swipe To Signout" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
              <span class="power-icon" aria-hidden="true">⏻</span>
              <span class="power-text">Swipe To Sign out</span>
              <div id="powerKnob" class="power-knob" aria-hidden="true"></div>
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

    notifDropdown.setAttribute('hidden', '');
    if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
  }
  </script>

</body>
</html>
