<!doctype html>
<html lang="id" data-theme="{{ session('theme','light') }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>@yield('title','Talent PTSI')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @if(session('swal')) <meta name="swal" content='@json(session('swal'))'> @endif
  <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);document.documentElement.classList.toggle('dark',t==='dark');}}catch(_){}})();</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  @vite('resources/css/app.css')
  @vite('resources/css/app-layout.css')
  @vite('resources/css/app-ui.css')
  @vite('resources/js/app-layout.js')
  @vite('resources/js/app.js')
  <style>
    .u-modal{z-index:2000}
    .avatar-img{width:32px;height:32px;border-radius:9999px;object-fit:cover;display:inline-block}
    .user-chip .avatar-img{margin-right:.5rem}
    .avatar.lg{width:56px;height:56px;border-radius:9999px;display:inline-flex;align-items:center;justify-content:center;font-weight:700}
    .avatar-lg-img{width:56px;height:56px;border-radius:9999px;object-fit:cover;display:inline-block}
    .text-ellipsis{max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .muted{opacity:.7}
  </style>
</head>
@php
  /** @var \App\Models\User|null $user */
  $user = auth()->user();
  $emp  = $user?->employee;

  // --- Ambil persons.full_name via relasi atau fallback query ---
  try {
      $person = $emp?->person ?: null;
      if (!$person && $emp?->person_id) {
          $person = \Illuminate\Support\Facades\DB::table('persons')->select(['id','full_name','email','profile_photo_url'])->where('id',$emp->person_id)->first();
      }
  } catch (\Throwable $e) { $person = null; }

  // Display name: persons.full_name -> employees.full_name -> user.name -> 'User'
  $displayName = ($person->full_name ?? null) ?: ($emp->full_name ?? ($user?->name ?: 'User'));

  $displayEmail = $user?->email ?: ($emp?->email ?: ($person->email ?? '-'));
  $employeeCode = $user?->employee_id ?: ($emp?->employee_id ?? '-');
  $jobTitle     = $emp?->latest_jobs_title ?: ($emp?->job_title ?: ($user?->job_title ?? '-'));
  $unitName     = $emp?->latest_jobs_unit  ?: ($emp?->unit_name ?? optional($user?->unit)->name ?? '-');

  // Roles: gabungkan, lalu format badge teks sederhana
  $roleNames   = collect($user?->getRoleNames() ?? [])->values();
  $roleBadge   = $roleNames->isEmpty() ? '-' : e($roleNames->implode(', '));

  // Inisial avatar (2 huruf)
  $initials = function(string $name) {
      $name = trim($name);
      if ($name === '') return 'U';
      $parts = preg_split('/\s+/', $name);
      $a = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
      $b = mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
      return $a.($b ?: '');
  };

  // FOTO: person → employee → user → gravatar(404)
  $urlFrom = function($obj, array $keys){
      foreach($keys as $k){
          if(!$obj) continue;
          $val = data_get($obj, $k);
          if(!$val) continue;
          if (is_string($val) && preg_match('~^https?://|^data:~i', $val)) return $val;
          try { return \Illuminate\Support\Facades\Storage::url($val); } catch (\Throwable $e) { return url($val); }
      }
      return null;
  };
  $photoUrl = $urlFrom($person,['profile_photo_url','photo_url','avatar_url','photo','avatar','profile_photo_path'])
          ?: $urlFrom($emp,   ['profile_photo_url','photo_url','avatar_url','photo_path','avatar_path','photo','avatar'])
          ?: $urlFrom($user,  ['profile_photo_url','profile_photo_path','avatar_url','avatar_path']);

  if (!$photoUrl && $displayEmail && $displayEmail !== '-') {
      $hash = md5(strtolower(trim($displayEmail)));
      $photoUrl = "https://www.gravatar.com/avatar/{$hash}?s=160&d=404";
  }
@endphp

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <!-- UNIVERSAL iOS LIQUID GLASS LOADER -->
  <div id="appLoader" aria-live="polite" aria-busy="true">
    <div class="loader-card glass">
      <div class="liquid" aria-hidden="true"><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>
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
      $isSuper = $roleNames->contains(fn($r)=> in_array(strtolower($r), ['superadmin','super-admin','admin','administrator']));
      $showMain = true;
      $showRecruitment = $isSuper || $user?->hasAnyPermission(['recruitment.view','contract.view']);
      $showTraining = $isSuper || $user?->hasAnyPermission(['training.view']);
      $showSettings = $user && ($user->can('users.view') || $user->can('rbac.view') || $user->can('employees.view'));

      // NEW: Master Data visibility (org.*)
      $showMaster = $isSuper || ($user && $user->can('org.view'));

      $printedAnySection = false;
      $recOpen = str_starts_with(request()->route()->getName() ?? '', 'recruitment.');
      $trOpen  = str_starts_with(request()->route()->getName() ?? '', 'training.');
      $acOpen  = request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.employees.*');

      // NEW: open state for Master Data
      $mdOpen = request()->routeIs('admin.org.*');
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

    @if($printedAnySection && $showRecruitment)<div class="nav-divider" aria-hidden="true"></div>@endif

    @if($showRecruitment)
      <nav class="nav-section">
        <div class="nav-title">Recruitment</div>
        <div class="nav">
          <button type="button" class="nav-item js-accordion {{ $recOpen ? 'open' : '' }}" data-accordion="nav-recruitment" aria-expanded="{{ $recOpen ? 'true' : 'false' }}">
            <span class="icon">👥</span><span class="label">Recruitment</span><span class="chev">▾</span>
          </button>
          <div id="nav-recruitment" class="nav-children {{ $recOpen ? 'open' : '' }}" data-accordion-panel="nav-recruitment">
            @if($isSuper || $user?->can('recruitment.view'))
              <a class="nav-item nav-child {{ request()->routeIs('recruitment.monitoring')?'active':'' }}" href="{{ \Illuminate\Support\Facades\Route::has('recruitment.monitoring') ? route('recruitment.monitoring') : '#' }}"><span class="icon">📊</span><span class="label">Monitoring</span></a>
            @endif
            @if($isSuper || $user?->can('recruitment.view'))
              <a class="nav-item nav-child {{ request()->routeIs('recruitment.principal-approval*')?'active':'' }}" href="{{ \Illuminate\Support\Facades\Route::has('recruitment.principal-approval.index') ? route('recruitment.principal-approval.index') : '#' }}"><span class="icon">✅</span><span class="label">Principal Approval</span></a>
            @endif
            @if($isSuper || $user?->can('contract.view'))
              <a class="nav-item nav-child {{ request()->routeIs('recruitment.contracts*')?'active':'' }}" href="{{ \Illuminate\Support\Facades\Route::has('recruitment.contracts.index') ? route('recruitment.contracts.index') : '#' }}"><span class="icon">📝</span><span class="label">Contracts</span></a>
            @endif
          </div>
        </div>
      </nav>
      @php $printedAnySection = true; @endphp
    @endif

    @if($printedAnySection && $showTraining)<div class="nav-divider" aria-hidden="true"></div>@endif

    @if($showTraining)
      <nav class="nav-section">
        <div class="nav-title">Training</div>
        <div class="nav">
          <button type="button" class="nav-item js-accordion {{ $trOpen ? 'open' : '' }}" data-accordion="nav-training" aria-expanded="{{ $trOpen ? 'true' : 'false' }}">
            <span class="icon">🎓</span><span class="label">Training</span><span class="chev">▾</span>
          </button>
          <div id="nav-training" class="nav-children {{ $trOpen ? 'open' : '' }}" data-accordion-panel="nav-training">
            @if($isSuper || $user?->can('training.view'))
              <a class="nav-item nav-child {{ request()->routeIs('training.monitoring')?'active':'' }}" href="{{ \Illuminate\Support\Facades\Route::has('training.monitoring') ? route('training.monitoring') : '#' }}"><span class="icon">📈</span><span class="label">Monitoring</span></a>
            @endif
            @if($isSuper || $user?->can('training.view'))
              <a class="nav-item nav-child {{ request()->routeIs('training.principal-approval')?'active':'' }}" href="{{ \Illuminate\Support\Facades\Route::has('training.principal-approval') ? route('training.principal-approval') : '#' }}"><span class="icon">🗂️</span><span class="label">Principal Approval</span></a>
            @endif
          </div>
        </div>
      </nav>
      @php $printedAnySection = true; @endphp
    @endif

    @if($printedAnySection && $showSettings)<div class="nav-divider" aria-hidden="true"></div>@endif

    @if($showSettings)
      <nav class="nav-section">
        <div class="nav-title">Settings</div>
        <div class="nav">
          @canany(['users.view','rbac.view','employees.view'])
            <button type="button" class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}" data-accordion="nav-access" aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
              <span class="icon">🧭</span><span class="label">Access Management</span><span class="chev">▾</span>
            </button>
            <div id="nav-access" class="nav-children {{ $acOpen ? 'open' : '' }}" data-accordion-panel="nav-access">
              @can('users.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="icon">👤</span><span class="label">User Management</span></a>
              @endcan
              @can('rbac.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}"><span class="icon">🛡️</span><span class="label">Role Management</span></a>
                <a class="nav-item nav-child {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}"><span class="icon">🔐</span><span class="label">Permission Management</span></a>
              @endcan
              @can('employees.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}"><span class="icon">🗃️</span><span class="label">Employee Directory</span></a>
              @endcan
            </div>
          @endcanany
        </div>
      </nav>
      @php $printedAnySection = true; @endphp
    @endif

    {{-- ========== MASTER DATA GROUP ========== --}}
    @if($printedAnySection && $showMaster)<div class="nav-divider" aria-hidden="true"></div>@endif

    @if($showMaster)
      <nav class="nav-section">
        <div class="nav-title">Master Data</div>
        <div class="nav">
          <button type="button" class="nav-item js-accordion {{ $mdOpen ? 'open' : '' }}" data-accordion="nav-masterdata" aria-expanded="{{ $mdOpen ? 'true' : 'false' }}">
            <span class="icon">🗂️</span><span class="label">Master Data</span><span class="chev">▾</span>
          </button>
          <div id="nav-masterdata" class="nav-children {{ $mdOpen ? 'open' : '' }}" data-accordion-panel="nav-masterdata">
            <a class="nav-item nav-child {{ request()->routeIs('admin.org.index') ? 'active' : '' }}" href="{{ route('admin.org.index') }}">
              <span class="icon">🏷️</span><span class="label">Directorates & Units</span>
            </a>
          </div>
        </div>
      </nav>
      @php $printedAnySection = true; @endphp
    @endif
  </aside>

  <!-- ===== Topbar ===== -->
  <header class="topbar glass floating" id="topbar">
    <button id="hamburgerBtn" class="hamburger" aria-label="Toggle sidebar" aria-expanded="false">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>

    <div class="search">
      <span class="search-icon">🔎</span>
      <input type="search" name="q" id="globalSearch" placeholder="Search Everything…" aria-label="Search Everything">
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
        <button id="userBtn" class="user-chip" type="button" aria-haspopup="true" aria-expanded="false" title="User menu">
          @if($photoUrl)
            <img src="{{ $photoUrl }}" alt="Foto {{ $displayName }}" class="avatar-img"
                 onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
            <span class="avatar hidden">{{ $initials($displayName) }}</span>
          @else
            <span class="avatar">{{ $initials($displayName) }}</span>
          @endif
          <span class="user-meta">
            <span class="user-name text-ellipsis">{{ $displayName }}</span>
            <span class="user-role muted text-ellipsis">
              {{ $roleBadge }}
              @if($jobTitle && $jobTitle !== '-') • {{ $jobTitle }} @endif
              @if($unitName && $unitName !== '-') • {{ $unitName }} @endif
            </span>
          </span>
          <span class="chev">▾</span>
        </button>

        <div id="userDropdown" class="dropdown user-dropdown" hidden>
          <div class="user-card" style="display:flex;gap:.75rem;align-items:center">
            @if($photoUrl)
              <img src="{{ $photoUrl }}" alt="Foto {{ $displayName }}" class="avatar-lg-img"
                   onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden')">
              <div class="avatar lg hidden">{{ $initials($displayName) }}</div>
            @else
              <div class="avatar lg">{{ $initials($displayName) }}</div>
            @endif
            <div class="user-info">
              <strong>{{ $displayName }}</strong>
              @if($employeeCode && $employeeCode !== '-') <span class="muted text-sm"><strong>Employee ID:</strong> {{ $employeeCode }}</span> @endif
              <div class="muted text-sm" style="margin-top:.25rem">
                <div><strong>Role:</strong> {{ $roleBadge }}</div>
                <div><strong>Jabatan:</strong> {{ $jobTitle }}</div>
                <div><strong>Unit Kerja:</strong> {{ $unitName }}</div>
                @if($displayEmail && $displayEmail !== '-') <div><strong>Email:</strong> {{ $displayEmail }}</div> @endif
              </div>
            </div>
          </div>

          <div class="menu-list">
            <button id="changePwBtn" type="button" class="menu-item" data-open="pwModal" onclick="window.__openModal && window.__openModal('pwModal')">
              <span>Change Password</span>
            </button>
          </div>

          <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <div id="poweroff" class="poweroff" data-threshold="0.6" role="slider" aria-label="Swipe To Signout" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
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
    @yield('content')
  </main>

  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>

  @php
    $pwAction = \Illuminate\Support\Facades\Route::has('password.update') ? route('password.update') : null;
  @endphp

  <!-- ===== Password Modal ===== -->
  <div id="pwModal" class="u-modal" hidden role="dialog" aria-modal="true" aria-labelledby="pwTitle">
    <div class="u-modal__card" role="document">
      <div class="u-modal__head">
        <h3 id="pwTitle" class="u-title">Change Password</h3>
        <button type="button" class="u-btn u-btn--sm u-btn--ghost" data-close="#pwModal" aria-label="Close">✖</button>
      </div>

      <form id="pwForm" method="POST" @if($pwAction) action="{{ $pwAction }}" @endif autocomplete="off" novalidate>
        @csrf
        @if($pwAction) @method('PUT') @endif

        <div class="u-modal__body u-p-lg u-flex u-flex-col u-gap-md">
          <div>
            <label class="u-text-sm u-font-medium">Current Password</label>
            <input name="current_password" type="password" class="u-input" required minlength="8" autocomplete="current-password">
          </div>
          <div>
            <label class="u-text-sm u-font-medium">New Password</label>
            <input name="password" type="password" class="u-input" required minlength="8" autocomplete="new-password">
          </div>
          <div>
            <label class="u-text-sm u-font-medium">Confirm New Password</label>
            <input name="password_confirmation" type="password" class="u-input" required minlength="8" autocomplete="new-password">
          </div>
          <p class="u-text-xs u-muted">Minimal 8 karakter. Gunakan kombinasi huruf, angka, dan simbol.</p>
        </div>

        <div class="u-modal__foot">
          <button type="button" class="u-btn u-btn--ghost" data-close="#pwModal">Batal</button>
          <button id="pwSubmitBtn" type="submit" class="u-btn u-btn--brand">Update Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== Loader & Utilities ===== -->
  <script>
  (function () {
    var root = document.getElementById('appLoader');
    if (!root) return;
    var hide=function(){root.classList.add('is-hidden');root.setAttribute('aria-busy','false');};
    var show=function(){root.classList.remove('is-hidden');root.setAttribute('aria-busy','true');};
    var doneOnce=false; var done=function(){if(doneOnce) return; doneOnce=true; hide();};
    window.appLoader={show:show,hide:hide,done:done};
    window.addEventListener('load',function(){setTimeout(done,120);});
    document.addEventListener('DOMContentLoaded',function(){try{if(window.jQuery){$(document).on('init.dt',function(){setTimeout(done,60);});}}catch(_){}})
    window.addEventListener('app:ready',done,{once:true});
    window.addEventListener('beforeunload',function(){show();});
  })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  (function () {
    window.iosSwal = Swal.mixin({
      background:'rgba(255,255,255,0.35)', backdrop:'rgba(15,23,42,.35)', color:'#0f172a',
      confirmButtonColor:'#2563eb', cancelButtonColor:'#94a3b8',
      customClass:{popup:'ios-glass', title:'font-semibold', confirmButton:'u-btn u-btn--brand rounded-xl', cancelButton:'u-btn u-btn--ghost rounded-xl'}
    });
    const toastBase={toast:true,position:'top-end',showConfirmButton:false,timer:2200,timerProgressBar:true,customClass:{popup:'swal2-toast ios-glass'}};
    window.toastOk=(t='Berhasil',x='')=>Swal.fire({...toastBase,icon:'success',title:t,text:x});
    window.toastErr=(t='Gagal',x='')=>Swal.fire({...toastBase,icon:'error',title:t,text:x});
    const meta=document.querySelector('meta[name="swal"]'); if(meta){try{const p=JSON.parse(meta.getAttribute('content')||'{}'); if(p&&typeof p==='object') window.iosSwal.fire(p);}catch(e){}}

    window.__openModal=function(id){const m=typeof id==='string'?document.getElementById(id):id;if(!m)return;m.hidden=false;document.body.classList.add('modal-open');const f=m.querySelector('input,button,select,textarea,[tabindex]:not([tabindex="-1"])');f&&f.focus();};
    window.__closeModal=function(id){const m=typeof id==='string'?document.getElementById(id):id;if(!m)return;m.hidden=true;document.body.classList.remove('modal-open');const f=m.querySelector('form');if(f)f.reset();};
    document.addEventListener('click',function(e){const op=e.target.closest('[data-open]');if(op){e.preventDefault();window.__openModal(op.getAttribute('data-open'));return;}const cl=e.target.closest('[data-close]');if(cl){e.preventDefault();window.__closeModal(cl.getAttribute('data-close'));return;}});
    document.getElementById('pwModal')?.addEventListener('click',function(e){if(e.target===this)window.__closeModal(this);});
    document.addEventListener('keydown',function(e){if(e.key==='Escape')window.__closeModal('pwModal');});

    const SHOULD_OPEN_PW = @json(session('modal')==='changePassword' || $errors->has('current_password') || $errors->has('password'));
    if (SHOULD_OPEN_PW) { document.addEventListener('DOMContentLoaded',()=>window.__openModal('pwModal')); }

    // AJAX fallback update password
    const pwForm=document.getElementById('pwForm');
    pwForm?.addEventListener('submit',async function(e){
      if(pwForm.getAttribute('action'))return;
      e.preventDefault();
      const btn=document.getElementById('pwSubmitBtn'); btn.disabled=true;
      const fd=new FormData(pwForm);
      const payload={current_password:String(fd.get('current_password')||''),password:String(fd.get('password')||''),password_confirmation:String(fd.get('password_confirmation')||'')};
      try{
        if(!payload.current_password||!payload.password||!payload.password_confirmation) throw new Error('Lengkapi semua kolom.');
        if(payload.password.length<8) throw new Error('Password minimal 8 karakter.');
        if(payload.password!==payload.password_confirmation) throw new Error('Konfirmasi tidak cocok.');
        const csrf=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
        const resp=await fetch("{{ url('/account/password') }}",{method:'PUT',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(payload),credentials:'same-origin'});
        if(resp.status===204){toastOk('Berhasil','Password diperbarui.');window.__closeModal('pwModal');return;}
        if(resp.ok){let j={};try{j=await resp.json();}catch(_){ }toastOk('Berhasil',j.message||'Password diperbarui.');window.__closeModal('pwModal');return;}
        if(resp.status===422){let j={};try{j=await resp.json();}catch(_){ }throw new Error((j.errors&&(j.errors.current_password?.[0]||j.errors.password?.[0]))||j.message||'Validasi gagal');}
        let j={};try{j=await resp.json();}catch(_){ }throw new Error(j.message||'Gagal memperbarui password.');
      }catch(err){toastErr('Gagal',err?.message||'Terjadi kesalahan.');}
      finally{btn.disabled=false;}
    });
  })();
  </script>
  @stack('swal')
</body>
</html>
