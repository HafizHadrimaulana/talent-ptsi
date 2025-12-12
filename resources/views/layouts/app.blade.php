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

</head>
@php
  /** @var \App\Models\User|null $user */
  $user = auth()->user();
  $emp  = $user?->employee;

  // --- Ambil persons.full_name via relasi atau fallback query ---
  try {
      $person = $emp?->person ?: null;
      if (!$person && $emp?->person_id) {
          $person = \Illuminate\Support\Facades\DB::table('persons')
              ->select(['id','full_name','email','profile_photo_url'])
              ->where('id',$emp->person_id)
              ->first();
      }
  } catch (\Throwable $e) { $person = null; }

  // Display name: persons.full_name -> employees.full_name -> user.name -> 'User'
  $displayName = ($person->full_name ?? null)
      ?: ($emp->full_name ?? ($user?->name ?: 'User'));

  $displayEmail = $user?->email ?: ($emp?->email ?: ($person->email ?? '-'));
  $employeeCode = $user?->employee_id ?: ($emp?->employee_id ?? '-');
  $jobTitle     = $emp?->latest_jobs_title
      ?: ($emp?->job_title ?: ($user?->job_title ?? '-'));
  $unitName     = $emp?->latest_jobs_unit
      ?: ($emp?->unit_name ?? optional($user?->unit)->name ?? '-');

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
          try { return \Illuminate\Support\Facades\Storage::url($val); }
          catch (\Throwable $e) { return url($val); }
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
      <div class="liquid" aria-hidden="true">
        <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
      </div>
      <div class="loader-title">Loading…</div>
    </div>
  </div>

  <div class="overlay" id="overlay" hidden></div>

  {{-- SIDEBAR DIPISAH KE PARTIAL --}}
  @include('partials.sidebar')

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
        @auth
        {{-- Notifikasi Izin Prinsip --}}

        @if(isset($approvalNotifs) && $approvalNotifs->count() > 0)
          <button id="ipNotifBtn" class="top-btn" type="button" aria-expanded="false" title="Persetujuan Izin Prinsip" style="position: relative;">
            {{-- Ikon Clipboard/File --}}
            <i class="fa-solid fa-file-signature" style="font-size: 1.1rem; color: #4f46e5;"></i>
            {{-- jlh notif --}}
            <span class="badge" style="color: red;">{{ $approvalNotifs->count() }}</span>
          </button>

          {{-- Dropdown Content --}}
          <div id="ipNotifDropdown" class="dropdown" hidden style="width: 320px; right: 0; left: auto;">
            <div class="dropdown-header" style="display: flex; justify-content: space-between; align-items: center;">
              <span>Approval Izin Prinsip</span>
              <button class="close-btn" type="button" onclick="document.getElementById('ipNotifDropdown').hidden=true">✖</button>
            </div>
            
            <ul class="notif-list" style="max-height: 350px; overflow-y: auto;">
              @foreach($approvalNotifs as $notif)
                <li class="notif-item" style="padding: 0;">
                  <a href="{{ route('recruitment.principal-approval.index', ['open_ticket_id' => $notif->id]) }}" 
                    style="display: block; padding: 12px 16px; text-decoration: none; border-bottom: 1px solid #f3f4f6; transition: background 0.2s;">
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #4f46e5; text-transform: uppercase;">
                          {{ $notif->unit->name ?? 'Unit' }}
                        </span>
                        <span style="font-size: 0.7rem; color: #9ca3af;">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    
                    <div style="font-size: 0.9rem; font-weight: 600; color: #1f2937; margin-bottom: 4px; line-height: 1.3;">
                      {{ $notif->title }}
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                      <span class="u-badge u-badge--xs u-badge--subtle">{{ $notif->request_type }}</span>
                      <span class="u-badge u-badge--xs u-badge--subtle">{{ $notif->headcount }} Orang</span>
                    </div>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
          
          {{-- Script kecil inline untuk toggle dropdown --}}
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const btn = document.getElementById('ipNotifBtn');
              const dd = document.getElementById('ipNotifDropdown');
              if(btn && dd) {
                btn.addEventListener('click', (e) => {
                  e.stopPropagation();
                  // Tutup dropdown lain
                  document.querySelectorAll('.dropdown').forEach(d => d.hidden = true);
                  dd.hidden = !dd.hidden;
                });
              }
            });
          </script>
        @endif
      @endauth

      @auth
          @php
            $unreadCount = 0;
            $siteNotifs = collect();
            $hasNotificationsApi = method_exists(auth()->user() ?? new \stdClass(), 'notificationsSite');
            if ($hasNotificationsApi) {
                try {
                    $unreadCount = auth()->user()->notificationsSite()->whereNull('read_at')->count();
                    $siteNotifs = auth()->user()->notificationsSite()->latest()->limit(8)->get();
                } catch (\Throwable $e) {
                    $unreadCount = 0; $siteNotifs = collect();
                }
            }
          @endphp
          @if($hasNotificationsApi)
            <button id="notifBtn" class="top-btn" type="button" aria-expanded="false" aria-haspopup="true" title="Notifications">
              <i class="fa-solid fa-bell bell-icon"></i>
              @if($unreadCount)
                <span class="badge">{{ $unreadCount }}</span>
              @endif
            </button>
            <div id="notifDropdown" class="dropdown" hidden>
              <div class="dropdown-header">
                Notifications
                <button class="close-btn" type="button" data-close="#notifDropdown">✖</button>
              </div>
              @if($siteNotifs->isEmpty())
                <div class="muted text-sm">No notifications yet.</div>
              @else
                <ul class="notif-list">
                  @foreach($siteNotifs as $n)
                    <li class="notif-item">{{ $n->message ?? 'Notification' }}</li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endif
        @endauth
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
              @if($employeeCode && $employeeCode !== '-')
                <span class="muted text-sm"><strong>Employee ID:</strong> {{ $employeeCode }}</span>
              @endif
              <div class="muted text-sm" style="margin-top:.25rem">
                <div><strong>Role:</strong> {{ $roleBadge }}</div>
                <div><strong>Jabatan:</strong> {{ $jobTitle }}</div>
                <div><strong>Unit Kerja:</strong> {{ $unitName }}</div>
                @if($displayEmail && $displayEmail !== '-')
                  <div><strong>Email:</strong> {{ $displayEmail }}</div>
                @endif
              </div>
            </div>
          </div>

          <div class="menu-list">
            <button id="changePwBtn" type="button" class="menu-item"
                    data-open="pwModal"
                    onclick="window.__openModal && window.__openModal('pwModal')">
              <span>Change Password</span>
            </button>
          </div>

          <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <div id="poweroff" class="poweroff"
                 data-threshold="0.6"
                 role="slider"
                 aria-label="Swipe To Signout"
                 aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="0"
                 tabindex="0">
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
    document.addEventListener("click", function (e) {
      const btn = e.target.closest("[data-close]");
      if (!btn) return;
      const modalSelector = btn.getAttribute("data-close");
      const modal = document.querySelector(modalSelector);
      if (modal) modal.setAttribute("hidden", "");
    });
    const SHOULD_OPEN_PW = @json(session('modal')==='changePassword' || $errors->has('current_password') || $errors->has('password'));
    if (SHOULD_OPEN_PW) { document.addEventListener('DOMContentLoaded',()=>window.__openModal('pwModal')); }

    // AJAX fallback update password
    const pwForm=document.getElementById('pwForm');
    pwForm?.addEventListener('submit',async function(e){
      if(pwForm.getAttribute('action'))return;
      e.preventDefault();
      const btn=document.getElementById('pwSubmitBtn'); btn.disabled=true;
      const fd=new FormData(pwForm);
      const payload={
        current_password:String(fd.get('current_password')||''),
        password:String(fd.get('password')||''),
        password_confirmation:String(fd.get('password_confirmation')||'')
      };
      try{
        if(!payload.current_password||!payload.password||!payload.password_confirmation)
          throw new Error('Lengkapi semua kolom.');
        if(payload.password.length<8)
          throw new Error('Password minimal 8 karakter.');
        if(payload.password!==payload.password_confirmation)
          throw new Error('Konfirmasi tidak cocok.');
        const csrf=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
        const resp=await fetch("{{ url('/account/password') }}",{
          method:'PUT',
          headers:{
            'Accept':'application/json',
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':csrf
          },
          body:JSON.stringify(payload),
          credentials:'same-origin'
        });
        if(resp.status===204){
          toastOk('Berhasil','Password diperbarui.');
          window.__closeModal('pwModal');
          return;
        }
        if(resp.ok){
          let j={};try{j=await resp.json();}catch(_){}
          toastOk('Berhasil',j.message||'Password diperbarui.');
          window.__closeModal('pwModal');
          return;
        }
        if(resp.status===422){
          let j={};try{j=await resp.json();}catch(_){}
          throw new Error(
            (j.errors&&(j.errors.current_password?.[0]||j.errors.password?.[0]))
            || j.message || 'Validasi gagal'
          );
        }
        let j={};try{j=await resp.json();}catch(_){}
        throw new Error(j.message||'Gagal memperbarui password.');
      }catch(err){
        toastErr('Gagal',err?.message||'Terjadi kesalahan.');
      }finally{
        btn.disabled=false;
      }
    });
  })();
  </script>
  @stack('swal')
  @stack('scripts')
</body>
</html>
