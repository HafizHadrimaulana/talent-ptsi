<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>@yield('title','Talent PTSI')</title>
  @vite(['resources/css/app-layout.css','resources/js/app-layout.js'])
</head>
<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <div class="overlay" id="overlay" hidden></div>

  <!-- Sidebar -->
  <aside class="sidebar glass" id="sidebar" aria-label="Primary navigation" data-scroll-area>
    <div class="brand">
      <a href="{{ route('dashboard') }}" class="brand-link" aria-label="Dashboard">
        <img src="{{ Vite::asset('resources/images/sapahc.png') }}" alt="Logo" class="logo">
      </a>
    </div>

    <nav class="nav-section">
      <div class="nav-title">Main</div>
      <div class="nav">
        <a class="nav-item {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">
          <span class="icon">🏠</span><span class="label">Dashboard</span>
        </a>
        @can('users.view')
        <a class="nav-item {{ request()->routeIs('users.*')?'active':'' }}" href="{{ route('users.index') }}">
          <span class="icon">👤</span><span class="label">Users</span>
        </a>
        @endcan
        @can('reports.view')
        <a class="nav-item" href="{{ route('reports.export') }}">
          <span class="icon">📈</span><span class="label">Reports</span>
        </a>
        @endcan
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
<!-- Notifikasi -->
<div class="dropdown-wrap">
  <button id="notifBtn" class="top-btn" type="button" aria-expanded="false" aria-haspopup="true" title="Notifications">🔔</button>
  <div id="notifDropdown" class="dropdown" hidden>
    <div class="dropdown-header">Notifikasi <button class="close-btn" type="button">✖</button></div>
    <div class="muted text-sm">Belum ada notifikasi.</div>
  </div>
</div>



<!-- User -->
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
      <a class="menu-item" href="#" data-action="toggle-theme">🌓 <span>Toggle Theme</span></a>
      <a class="menu-item" href="{{ route('dashboard') }}">🏠 <span>Dashboard</span></a>
    </div>

    <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
      @csrf
      <div id="poweroff" class="poweroff">
        <span class="power-icon">⏻</span>
        <span class="power-text">Swipe to Logout</span>
        <div id="powerKnob" class="power-knob"></div>
      </div>
    </form>
  </div>
</div>

    </div>
  </header>

  <main class="main">
    @yield('content')
  </main>

  <!-- Dark mode floating FAB -->
  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>
</body>
</html>
