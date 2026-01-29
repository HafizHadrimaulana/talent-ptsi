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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
  @vite([
    'resources/css/app.css', 
    'resources/css/app-layout.css', 
    'resources/css/app-ui.css',
    'resources/css/alert.css',
    'resources/js/app-layout.js', 
    'resources/js/app.js'
  ])
</head>
@php
  $user = auth()->user();
  $emp  = $user?->employee;
  try {
      $person = $emp?->person ?: null;
      if (!$person && $emp?->person_id) {
          $person = \Illuminate\Support\Facades\DB::table('persons')
              ->select(['id','full_name','email','profile_photo_url'])
              ->where('id',$emp->person_id)
              ->first();
      }
  } catch (\Throwable $e) { $person = null; }

  $displayName = ($person->full_name ?? null) ?: ($emp->full_name ?? ($user?->name ?: 'User'));
  $displayEmail = $user?->email ?: ($emp?->email ?: ($person->email ?? '-'));
  $employeeCode = $user?->employee_id ?: ($emp?->employee_id ?? '-');
  $jobTitle     = $emp?->latest_jobs_title ?: ($emp?->job_title ?: ($user?->job_title ?? '-'));
  $unitName     = $emp?->latest_jobs_unit ?: ($emp?->unit_name ?? optional($user?->unit)->name ?? '-');
  $roleNames    = collect($user?->getRoleNames() ?? [])->values();
  $roleBadge    = $roleNames->isEmpty() ? '-' : e($roleNames->implode(', '));
  $isPelamar    = $roleNames->contains('Pelamar');

  $initials = function(string $name) {
      $name = trim($name);
      if ($name === '') return 'U';
      $parts = preg_split('/\s+/', $name);
      $a = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
      $b = mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
      return $a.($b ?: '');
  };

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
      $photoUrl = "https://www.gravatar.com/avatar/{$hash}?s=160&d=mp";
  }
@endphp

<body class="{{ session('sidebar','expanded') === 'collapsed' ? 'sidebar-collapsed' : '' }}">
  <div id="appLoader" aria-live="polite" aria-busy="true">
    <div class="loader-card glass">
      <div class="liquid" aria-hidden="true">
        <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
      </div>
      <div class="loader-title">Loading…</div>
    </div>
  </div>

  <div class="overlay" id="overlay" hidden></div>

  @include('partials.sidebar')

  <header class="topbar glass floating" id="topbar">
    <button id="hamburgerBtn" class="hamburger" aria-label="Toggle sidebar" aria-expanded="false">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>

    <div class="top-actions">
      <div class="dropdown-wrap">
        @auth
          @php
             $appNotifs = $globalNotifications ?? collect();
             $sysNotifs = collect();
             $unreadSysCount = 0;
             if (method_exists(auth()->user(), 'notificationsSite')) {
                 try {
                     $unreadSysCount = auth()->user()->notificationsSite()->whereNull('read_at')->count();
                     $rawSys = auth()->user()->notificationsSite()->latest()->limit(5)->get();
                     foreach($rawSys as $n) {
                         $sysNotifs->push((object)[
                             'type' => 'system',
                             'title' => 'System Notification',
                             'subtitle' => '',
                             'desc' => $n->message,
                             'status' => 'info',
                             'url' => '#', 
                             'time' => $n->created_at,
                             'icon' => 'fa-bell',
                             'color_class' => 'text-gray-500'
                         ]);
                     }
                 } catch (\Throwable $e) {}
             }
             $allNotifs = $appNotifs->merge($sysNotifs)->sortByDesc('time');
             $totalCount = $appNotifs->count() + $unreadSysCount;
          @endphp

          <button id="globalNotifBtn" class="top-btn" type="button" aria-expanded="false" title="Notifications" style="position: relative;">
            <i class="fa-solid fa-bell" style="font-size: 1.1rem; color: #5951f3ff;"></i>
            @if($totalCount > 0)
              <span class="badge" style="color: red;">{{ $totalCount }}</span>
            @endif
          </button>

          <div id="globalNotifDropdown" class="dropdown" hidden>
            <div class="dropdown-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f3f4f6; padding: 12px 16px;">
               <span style="font-weight: 600;">Notifikasi</span>
               <button class="close-btn" type="button" style="background:none; border:none; cursor:pointer;" onclick="document.getElementById('globalNotifDropdown').hidden=true">✖</button>
            </div>
            
            <ul class="notif-list" style="max-height: 400px; overflow-y: auto; padding: 0; margin: 0; list-style: none;">
              @if($allNotifs->isEmpty())
                 <li style="padding: 20px; text-align: center; color: #6b7280; font-size: 0.875rem;">
                    Tidak ada notifikasi.
                 </li>
              @else
                 @foreach($allNotifs as $notif)
                   @php
                       $bgItem = '#fff';
                       $borderLeft = '4px solid transparent';
                       if ($notif->type === 'izin_prinsip') {
                           $borderLeft = '4px solid #4f46e5'; 
                       } elseif ($notif->type === 'training') {
                           $borderLeft = '4px solid #10b981';
                       } else {
                           $borderLeft = '4px solid #9ca3af';
                       }
                   @endphp

                   <li class="notif-item" style="padding: 0;">
                     <a href="{{ $notif->url }}" 
                        style="display: block; padding: 12px 16px; text-decoration: none; border-bottom: 1px solid #f3f4f6; background-color: {{ $bgItem }}; border-left: {{ $borderLeft }}; transition: background-color 0.2s;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; align-items: center;">
                           <div style="display: flex; align-items: center; gap: 6px;">
                               <i class="fa-solid {{ $notif->icon }}" style="font-size: 0.7rem; color: #6b7280;"></i>
                               <span style="font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                                 {{ str_replace('_', ' ', $notif->type) }}
                               </span>
                           </div>
                        </div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: #1f2937; margin-bottom: 4px; line-height: 1.3;">
                          {{ $notif->title }}
                        </div>
                        @if($notif->subtitle || $notif->desc)
                        <div style="display: flex; gap: 8px; align-items: center; font-size: 0.75rem; color: #4b5563;">
                          @if($notif->subtitle) <span>{{ $notif->subtitle }}</span> @endif
                          @if($notif->subtitle && $notif->desc) <span>•</span> @endif
                          @if($notif->desc) <span>{{ $notif->desc }}</span> @endif
                        </div>
                        @endif
                     </a>
                   </li>
                 @endforeach
              @endif
            </ul>
          </div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const btn = document.getElementById('globalNotifBtn');
              const dd = document.getElementById('globalNotifDropdown');
              if(btn && dd) {
                btn.addEventListener('click', (e) => {
                  e.stopPropagation();
                  document.querySelectorAll('.dropdown').forEach(d => {
                      if(d !== dd) d.hidden = true;
                  });
                  dd.hidden = !dd.hidden;
                });
                document.addEventListener('click', (e) => {
                  if (!btn.contains(e.target) && !dd.contains(e.target)) {
                    dd.hidden = true;
                  }
                });
              }
            });
          </script>
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
                @if(!$isPelamar)
                  <div><strong>Role:</strong> {{ $roleBadge }}</div>
                  <div><strong>Jabatan:</strong> {{ $jobTitle }}</div>
                  <div><strong>Unit Kerja:</strong> {{ $unitName }}</div>
                @endif
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
                 data-threshold="0.9"
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

  <main class="main" id="main">
    @yield('content')
  </main>

  <button id="dmFab" class="dm-fab" type="button" title="Toggle theme" aria-pressed="false">🌞</button>

  @php
    $pwAction = \Illuminate\Support\Facades\Route::has('password.update') ? route('password.update') : null;
  @endphp

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
            <div style="position: relative;">
              <input id="pw_current" name="current_password" type="password" class="u-input" 
                     style="padding-right: 2.5rem;" required minlength="8" autocomplete="current-password">
              <button type="button" onclick="togglePw('pw_current', this)" 
                      style="position: absolute; right: 0; top: 0; height: 100%; padding: 0 0.8rem; background: none; border: none; cursor: pointer; color: #6b7280;"
                      tabindex="-1" title="Toggle password visibility">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <div>
            <label class="u-text-sm u-font-medium">New Password</label>
            <div style="position: relative;">
              <input id="pw_new" name="password" type="password" class="u-input" 
                     style="padding-right: 2.5rem;" required minlength="8" autocomplete="new-password">
              <button type="button" onclick="togglePw('pw_new', this)" 
                      style="position: absolute; right: 0; top: 0; height: 100%; padding: 0 0.8rem; background: none; border: none; cursor: pointer; color: #6b7280;"
                      tabindex="-1" title="Toggle password visibility">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <div>
            <label class="u-text-sm u-font-medium">Confirm New Password</label>
            <div style="position: relative;">
              <input id="pw_confirm" name="password_confirmation" type="password" class="u-input" 
                     style="padding-right: 2.5rem;" required minlength="8" autocomplete="new-password">
              <button type="button" onclick="togglePw('pw_confirm', this)" 
                      style="position: absolute; right: 0; top: 0; height: 100%; padding: 0 0.8rem; background: none; border: none; cursor: pointer; color: #6b7280;"
                      tabindex="-1" title="Toggle password visibility">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
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

  <script>
    window.togglePw = function(inputId, btn) {
      const input = document.getElementById(inputId);
      const icon = btn.querySelector('i');
      
      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    };
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
    // Flash message handler - uses global alert.js functions
    const meta=document.querySelector('meta[name="swal"]');
    if(meta){
      try{
        const content = meta.getAttribute('content') || '{}';
        const p = JSON.parse(content);
        if(p && typeof p === 'object' && Object.keys(p).length > 0){
          // Wait for global showAlert to be available
          const showFlash = () => {
            if(typeof window.showAlert === 'function'){
              window.showAlert(p);
              meta.remove(); // Remove meta after showing
            }
          };
          
          if(typeof window.showAlert === 'function'){
            showFlash();
          }else{
            window.addEventListener('load', showFlash);
          }
        }
      }catch(e){
        console.error('Flash message parse error:', e.message);
        meta.remove(); // Remove problematic meta
      }
    }

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

    const pwForm = document.getElementById('pwForm');
    pwForm?.addEventListener('submit', async function(e) {
      if (pwForm.getAttribute('action')) return;
      e.preventDefault();
      const btn = document.getElementById('pwSubmitBtn'); 
      btn.disabled = true;
      const fd = new FormData(pwForm);
      const payload = {
        current_password: String(fd.get('current_password') || ''),
        password: String(fd.get('password') || ''),
        password_confirmation: String(fd.get('password_confirmation') || '')
      };

      try {
        if (!payload.current_password || !payload.password || !payload.password_confirmation) {
            throw new Error('Harap lengkapi semua kolom.');
        }
        if (payload.password !== payload.password_confirmation) {
            throw new Error('Konfirmasi password baru tidak cocok.');
        }
        if (payload.password.length < 8) {
            throw new Error('Password baru minimal 8 karakter.');
        }
        const isComplex = /[a-zA-Z]/.test(payload.password) && 
                          /\d/.test(payload.password) && 
                          /[\W_]/.test(payload.password);

        if (!isComplex) {
            throw new Error('Password baru harus mengandung kombinasi Huruf, Angka, dan Simbol.');
        }
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const resp = await fetch("{{ url('/account/password') }}", {
          method: 'POST', 
          headers: { 
            'Accept': 'application/json', 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': csrf 
          },
          body: JSON.stringify(payload),
          credentials: 'same-origin'
        });
        if (resp.status === 204 || resp.ok) { 
            let msg = 'Password berhasil diperbarui.';
            if (resp.status !== 204) {
                try { const j = await resp.json(); msg = j.message || msg; } catch (_) {}
            }
            toastOk('Berhasil', msg); 
            window.__closeModal('pwModal');
            pwForm.reset();
            return; 
        }
        if (resp.status === 422) { 
            let j = {}; try { j = await resp.json(); } catch (_) {} 
            const errorMsg = (j.errors && (j.errors.current_password?.[0] || j.errors.password?.[0])) 
                             || j.message 
                             || 'Validasi data gagal.';
            throw new Error(errorMsg); 
        }
        let j = {}; try { j = await resp.json(); } catch (_) {} 
        throw new Error(j.message || 'Gagal memperbarui password. Silakan coba lagi.');
      } catch (err) {
        toastErr('Gagal', err?.message || 'Terjadi kesalahan sistem.');
      } finally {
        btn.disabled = false;
      }
    });

    (function(){
        const slider = document.getElementById('poweroff');
        const knob = document.getElementById('powerKnob');
        const form = document.getElementById('logoutForm');
        if(!slider || !knob || !form) return;

        let isDragging = false;
        let startX = 0;
        let maxDist = 0;
        let currentX = 0;

        function initDrag(e) {
            isDragging = true;
            startX = (e.pageX || e.touches[0].pageX);
            maxDist = slider.offsetWidth - knob.offsetWidth - 6; 
            slider.classList.add('active');
        }

        function onDrag(e) {
            if(!isDragging) return;
            const x = (e.pageX || e.touches[0].pageX);
            let moveX = x - startX;
            if(moveX < 0) moveX = 0;
            if(moveX > maxDist) moveX = maxDist;
            
            knob.style.transform = `translateX(${moveX}px)`;
            
            const opacity = 1 - (moveX / maxDist);
            const text = slider.querySelector('.power-text');
            if(text) text.style.opacity = Math.max(0, opacity);

            if(moveX > maxDist * 0.9) {
                isDragging = false;
                knob.style.transform = `translateX(${maxDist}px)`;
                form.submit();
            }
        }

        function endDrag() {
            if(!isDragging) return;
            isDragging = false;
            slider.classList.remove('active');
            knob.style.transform = 'translateX(0)';
            const text = slider.querySelector('.power-text');
            if(text) text.style.opacity = 1;
        }

        knob.addEventListener('mousedown', initDrag);
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', endDrag);

        knob.addEventListener('touchstart', function(e){
            initDrag(e);
            e.preventDefault(); 
        }, {passive: false});
        
        document.addEventListener('touchmove', function(e){
            if(isDragging) e.preventDefault(); 
            onDrag(e);
        }, {passive: false});
        
        document.addEventListener('touchend', endDrag);
    })();
  })();
  </script>
  @stack('swal')
  @stack('scripts')
</body>
</html>