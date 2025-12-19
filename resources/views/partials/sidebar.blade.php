<!-- ===== Sidebar ===== -->
<aside class="sidebar glass" id="sidebar" aria-label="Primary navigation" data-scroll-area>
  @php
    $user = auth()->user();
    
    // Helper roles
    $roleNames = $user ? $user->getRoleNames() : collect([]);

    // Definisikan Variable $isPelamar
    $isPelamar = $user && $user->hasRole('Pelamar');
    
    // Cek Superadmin
    $isSuper = $roleNames->contains(fn($r)=> in_array(strtolower($r), ['superadmin','super-admin','admin','administrator']));

    // Logika Tampilan
    $showMain = !$isPelamar; // Sembunyikan dashboard utama jika pelamar

    // Tampilkan menu Recruitment jika Superadmin, Pelamar, atau punya permission
    $showRecruitment = $isSuper || $isPelamar || $user?->hasAnyPermission(['recruitment.view','contract.view']);
    
    $showTraining = !$isPelamar && ($isSuper || $user?->hasAnyPermission(['training.view']));
    $showSettings = !$isPelamar && ($user && ($user->can('users.view') || $user->can('rbac.view') || $user->can('employees.view')));
    $showMaster   = !$isPelamar && ($isSuper || ($user && $user->can('org.view')));

    $printedAnySection = false;
    
    // Deteksi Menu Aktif
    $recOpen = str_starts_with(request()->route()->getName() ?? '', 'recruitment.') || str_starts_with(request()->route()->getName() ?? '', 'careers.');
    $trOpen  = str_starts_with(request()->route()->getName() ?? '', 'training.');
    $acOpen  = request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.employees.*');
    $mdOpen  = request()->routeIs('admin.org.*');
  @endphp
  <div class="brand">
    <a href="{{ route('dashboard') }}" class="brand-link" aria-label="Dashboard">
      <img src="{{ Vite::asset('resources/images/sapahc.png') }}" alt="Logo" class="logo">
    </a>
  </div>

  @if($showMain)
    <nav class="nav-section">
      <div class="nav-title">Main</div>
      <div class="nav">
        <a class="nav-item {{ request()->routeIs('dashboard')?'active':'' }}"
           href="{{ route('dashboard') }}">
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
        <button type="button"
                class="nav-item js-accordion {{ $recOpen ? 'open' : '' }}"
                data-accordion="nav-recruitment"
                aria-expanded="{{ $recOpen ? 'true' : 'false' }}">
          <span class="icon">👥</span><span class="label">Recruitment</span><span class="chev">▾</span>
        </button>
        <div id="nav-recruitment"
             class="nav-children {{ $recOpen ? 'open' : '' }}"
             data-accordion-panel="nav-recruitment">
             @if($isPelamar || $isSuper)
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.applicant-data.*') ? 'active' : '' }}"
                    href="{{ route('recruitment.applicant-data.index') }}">
                  <span class="icon">👤</span><span class="label">Biodata & Status</span>
                </a>

                {{-- Link Cari Lowongan (Opsional untuk Superadmin, Wajib untuk Pelamar) --}}
                <a class="nav-item nav-child {{ request()->routeIs('careers.*') ? 'active' : '' }}"
                    href="{{ route('careers.index') }}">
                  <span class="icon">💼</span><span class="label">Cari Lowongan</span>
                </a>
              @endif
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
          {{-- [EXTERNAL RECRUITMENT] --}}
          @if($isSuper || $user?->can('recruitment.external.view'))
            <a class="nav-item nav-child {{ request()->routeIs('recruitment.external.*') ? 'active' : '' }}"
               href="{{ \Illuminate\Support\Facades\Route::has('recruitment.external.index') ? route('recruitment.external.index') : '#' }}">
              <span class="icon">➕</span>
              <span class="label">External Recruitment</span>
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
        <button type="button"
                class="nav-item js-accordion {{ $trOpen ? 'open' : '' }}"
                data-accordion="nav-training"
                aria-expanded="{{ $trOpen ? 'true' : 'false' }}">
          <span class="icon">🎓</span><span class="label">Training</span><span class="chev">▾</span>
        </button>
        <div id="nav-training"
             class="nav-children {{ $trOpen ? 'open' : '' }}"
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
          <button type="button"
                  class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}"
                  data-accordion="nav-access"
                  aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
            <span class="icon">🧭</span><span class="label">Access Management</span><span class="chev">▾</span>
          </button>
          <div id="nav-access"
               class="nav-children {{ $acOpen ? 'open' : '' }}"
               data-accordion-panel="nav-access">
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
    @php $printedAnySection = true; @endphp
  @endif

  {{-- ========== MASTER DATA GROUP ========== --}}
  @if($printedAnySection && $showMaster)
    <div class="nav-divider" aria-hidden="true"></div>
  @endif

  @if($showMaster)
    <nav class="nav-section">
      <div class="nav-title">Master Data</div>
      <div class="nav">
        <button type="button"
                class="nav-item js-accordion {{ $mdOpen ? 'open' : '' }}"
                data-accordion="nav-masterdata"
                aria-expanded="{{ $mdOpen ? 'true' : 'false' }}">
          <span class="icon">🗂️</span><span class="label">Master Data</span><span class="chev">▾</span>
        </button>
        <div id="nav-masterdata"
             class="nav-children {{ $mdOpen ? 'open' : '' }}"
             data-accordion-panel="nav-masterdata">
          <a class="nav-item nav-child {{ request()->routeIs('admin.org.index') ? 'active' : '' }}"
             href="{{ route('admin.org.index') }}">
            <span class="icon">🏷️</span><span class="label">Directorates & Units</span>
          </a>
        </div>
      </div>
    </nav>
    @php $printedAnySection = true; @endphp
  @endif
</aside>
