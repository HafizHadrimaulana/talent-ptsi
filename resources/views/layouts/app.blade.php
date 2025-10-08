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

  <!-- ====== SIDEBAR POLISH (CSS-only) ====== -->
  <style>
    :root{
      --sbw:256px; --sbw-collapsed:84px; --side-pad:16px; --topbar-h:64px;
      --tree: rgba(2,8,23,.16);
      --tree-dark: rgba(255,255,255,.22);
    }

    /* ===== General align & sizing ===== */
    .sidebar .nav, .sidebar .nav *{ text-align:left !important }
    .sidebar .nav-item{
      display:flex; align-items:center; justify-content:flex-start !important;
      gap:10px; width:100%; min-width:0; text-decoration:none;
      padding:9px 12px;
      font-size:13px;   /* smaller so labels fit */
      border-radius:12px; border:var(--border); background:var(--card);
    }
    .sidebar .nav-item:hover{
      background: color-mix(in srgb, var(--card) 90%, var(--accent-ghost));
      transform: translateY(-1px);
      transition: background .15s, transform .12s;
    }
    .sidebar .nav-item.active{ background:var(--accent-ghost); border-color:rgba(79,70,229,.22) }
    .sidebar .nav-title{ font-size:11px; letter-spacing:.06em; padding:6px 8px }
    .sidebar .icon{ width:20px; flex:0 0 20px; text-align:center; opacity:.95; margin:auto;}
    .sidebar .label{ flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:10px; }
    .sidebar .chev{ margin-left:-0.65rem; opacity:.78; transition:transform .18s ease; margin-right:0.25rem;}
    .sidebar .js-accordion.open .chev{ transform:rotate(-180deg) }

    /* ===== Children (submenu) → flush-left + tree ===== */
    .nav-children{
      display:grid; gap:6px;
      margin:6px 0 8px 0 !important; /* no extra left gap */
      padding:0 !important;          /* no inner left padding */
      max-height:0; overflow:hidden; opacity:0;
      transition:max-height .22s ease, opacity .18s ease;
      position:relative; border-left:none !important;
    }
    .nav-children.open{ max-height:420px; opacity:1 }

    /* tree spine sangat kiri (tidak menggeser card) */
    .nav-children::before{
      content:""; position:absolute; left:8px; top:0; bottom:0; width:2px;
      background:var(--tree);
    }
    [data-theme="dark"] .nav-children::before{ background:var(--tree-dark) }

    /* Submenu cards — MENTOK KIRI, rapi */
    .nav-child{
      position:relative;
      display:flex; align-items:center; justify-content:flex-start !important;
      gap:10px; width:100%;
      padding:8px 10px 8px 1.6rem;  /* kecil: card tetap mentok kiri; ruang sedikit utk branch */
      border-radius:10px; border:var(--border); background:var(--card);
      font-size:12.25px;
      box-shadow: none;
    }
    .nav-child:hover{
      background: color-mix(in srgb, var(--card) 94%, var(--accent-ghost));
    }
    [data-theme="dark"] .nav-child:hover{ background: rgba(255,255,255,.06) }
    .nav-child.active{ background:var(--accent-ghost); border-color:rgba(79,70,229,.22) }

    /* branch horizontal + node, ditempatkan di paling kiri supaya card nggak terlihat “geser” */
    .nav-child::before{
      content:""; position:absolute; left:8px; top:50%; transform:translateY(-50%);
      width:12px; height:2px; background:var(--tree);
    }
    .nav-child::after{
      content:""; position:absolute; left:8px; top:50%; transform:translate(-50%,-50%);
      width:6px; height:6px; border-radius:999px; background:var(--tree);
    }
    [data-theme="dark"] .nav-child::before,
    [data-theme="dark"] .nav-child::after{ background:var(--tree-dark) }

    /* icon di submenu sedikit lebih kecil biar rapi */
    .nav-children .nav-child .icon{ width:18px; flex:0 0 18px; opacity:.9 }

    /* ===== Scroll bug when collapsed ===== */
    body.sidebar-collapsed .sidebar{ overflow:visible !important } /* jangan perangkap scroll */
    .sidebar{ overflow-x:hidden } /* no horizontal scroll bleed */

    /* collapsed: padding lebih rapat, label bisa disembunyikan oleh tema — biarkan JS kamu yang urus */
    body.sidebar-collapsed .nav-item{ padding:9px 10px }
  </style>
</head>

@php use Illuminate\Support\Facades\Route as Rt; @endphp

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <!-- first-paint collapsed state (tetap) -->
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
          <div class="dropdown-header">Notifikasi <button class="close-btn" type="button">✖</button></div>
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

  <!-- Dark mode floating FAB -->
  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>
</body>
</html>
