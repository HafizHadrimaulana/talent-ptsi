<aside class="sidebar glass" id="sidebar" aria-label="Primary navigation" data-scroll-area> @php $user = auth()->user();

    $roleNames = $user ? $user->getRoleNames() : collect([]);
    $isPelamar = $user && $user->hasRole('Pelamar');
    $isSuper = $roleNames->contains(fn($r)=> in_array(strtolower($r),
    ['superadmin','super-admin','admin','administrator']));

    $showMain = !$isPelamar;
    $showRecruitment = $isSuper || $isPelamar || $user?->hasAnyPermission(['recruitment.view','contract.view']);
    $showTraining = !$isPelamar && ($isSuper || $user?->hasAnyPermission(['training.view']));
    $showSettings = !$isPelamar && ($user && ($user->can('users.view') || $user->can('rbac.view') ||
    $user->can('employees.view')));
    $showMaster = !$isPelamar && ($isSuper || ($user && $user->can('org.view')));

    $printedAnySection = false;

    $recOpen = str_starts_with(request()->route()->getName() ?? '', 'recruitment.') ||
    str_starts_with(request()->route()->getName() ?? '', 'careers.');
    $trOpen = str_starts_with(request()->route()->getName() ?? '', 'training.');

    $acOpen = request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ||
    request()->routeIs('admin.permissions.*') || request()->routeIs('admin.employees.*');

    $mdOpen = request()->routeIs('admin.org.*') || request()->routeIs('admin.contract-templates.*');
    @endphp

    <div class="brand">
        <a href="{{ route('dashboard') }}" class="brand-link" aria-label="Dashboard">
            <img src="{{ asset('images/sapahc.png') }}" alt="Logo" class="logo hover-lift">
        </a>
    </div>

    @if($showMain)
    <nav class="nav-section">
        <div class="nav-title">Main</div>
        <div class="nav">
            <a class="nav-item {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                <span class="label">Dashboard</span>
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
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                <span class="label">Recruitment</span>
                <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div id="nav-recruitment" class="nav-children {{ $recOpen ? 'open' : '' }}" data-accordion-panel="nav-recruitment">
                @php
                    $hideMenuForNonHcAvp = false; // Default: jangan sembunyikan

                    // Jalankan pengecekan hanya jika user BUKAN Superadmin dan Memiliki Role 'AVP'
                    if (!$isSuper && $user && $user->hasRole('AVP')) {
                        $jobTitle = \Illuminate\Support\Facades\DB::table('employees')
                            ->join('positions', 'employees.position_id', '=', 'positions.id')
                            ->where('employees.person_id', $user->person_id)
                            ->value('positions.name');
                        
                        // Jika jabatannya TIDAK SAMA dengan 'AVP Human Capital Operation', aktifkan flag sembunyi
                        if ($jobTitle !== 'AVP Human Capital Operation') {
                            $hideMenuForNonHcAvp = true;
                        }
                    }
                @endphp
                @if($isPelamar || $isSuper)
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.applicant-data.*') ? 'active' : '' }}"
                    href="{{ route('recruitment.applicant-data.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span class="label">Biodata & Status</span>
                </a>
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.external.*') ? 'active' : '' }}"
                    href="{{ route('recruitment.external.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="7" rx="2" ry="2" />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                    </svg>
                    <span class="label">Careers</span>
                </a>
                @endif
                @if( ($isSuper || $user?->can('recruitment.view')) && !$hideMenuForNonHcAvp )
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.monitoring')?'active':'' }}"
                    href="{{ \Illuminate\Support\Facades\Route::has('recruitment.monitoring') ? route('recruitment.monitoring') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="M18 17V9" />
                        <path d="M13 17V5" />
                        <path d="M8 17v-3" />
                    </svg>
                    <span class="label">Monitoring</span>
                </a>
                @endif
                @if( ($isSuper || $user?->can('recruitment.view')) && !$hideMenuForNonHcAvp )
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.principal-approval*')?'active':'' }}"
                    href="{{ \Illuminate\Support\Facades\Route::has('recruitment.principal-approval.index') ? route('recruitment.principal-approval.index') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    <span class="label">Principal Approval</span>
                </a>
                @endif
                @if(!$isPelamar && ($isSuper || $user?->can('recruitment.external.view')))
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.external.*') ? 'active' : '' }}"
                    href="{{ \Illuminate\Support\Facades\Route::has('recruitment.external.index') ? route('recruitment.external.index') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M2 12h20" />
                        <path
                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                    <span class="label">External Recruitment</span>
                </a>
                @endif
                @if($isSuper || $user?->can('contract.view'))
                <a class="nav-item nav-child {{ request()->routeIs('recruitment.contracts*')?'active':'' }}"
                    href="{{ \Illuminate\Support\Facades\Route::has('recruitment.contracts.index') ? route('recruitment.contracts.index') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" x2="8" y1="13" y2="13" />
                        <line x1="16" x2="8" y1="17" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                    <span class="label">Contracts</span>
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
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                </svg>
                <span class="label">Training</span>
                <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div id="nav-training" class="nav-children {{ $trOpen ? 'open' : '' }}" data-accordion-panel="nav-training">
                @if($isSuper || $user?->can('training.dashboard.view'))
                <a class="nav-item nav-child {{ request()->routeIs('training.dashboard')?'active':'' }}"
                    href="{{ Route::has('training.dashboard') ? route('training.dashboard') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18" />
                        <path d="m19 9-5 5-4-4-3 3" />
                    </svg>
                    <span class="label">Dashboard</span>
                </a>
                @endif
                @if($isSuper || $user?->can('training.view'))
                <a class="nav-item nav-child {{ request()->routeIs('training.training-request')?'active':'' }}"
                    href="{{ Route::has('training.training-request') ? route('training.training-request') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                    </svg>
                    <span class="label">Training</span>
                </a>
                @endif
                @if($isSuper || $user?->can('training.view'))
                <a class="nav-item nav-child {{ request()->routeIs('training.self-learning')?'active':'' }}"
                    href="{{ Route::has('training.self-learning') ? route('training.self-learning') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M15 14c.2-1 .7-1.7 1.5-2.5 1-1 1.5-2 1.5-3.5a6 6 0 0 0-11 0c0 1.5.5 2.5 1.5 3.5 2.5 2.4 2.9 2.5 3 4" />
                        <path d="M9 18h6" />
                        <path d="M10 22h4" />
                    </svg>
                    <span class="label">Self Learning</span>
                </a>
                @endif
                @if($isSuper || $user?->can('training.management.view'))
                <a class="nav-item nav-child {{ request()->routeIs('training.training-management')?'active':'' }}"
                    href="{{ Route::has('training.training-management') ? route('training.training-management') : '#' }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span class="label">Management</span>
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
            <button type="button" class="nav-item js-accordion {{ $acOpen ? 'open' : '' }}" data-accordion="nav-access"
                aria-expanded="{{ $acOpen ? 'true' : 'false' }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <span class="label">Configuration</span>
                <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div id="nav-access" class="nav-children {{ $acOpen ? 'open' : '' }}" data-accordion-panel="nav-access">
                @can('users.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    href="{{ route('admin.users.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span class="label">User Management</span>
                </a>
                @endcan
                @can('rbac.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                    href="{{ route('admin.roles.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    <span class="label">Role Management</span>
                </a>
                <a class="nav-item nav-child {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"
                    href="{{ route('admin.permissions.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="7.5" cy="15.5" r="5.5" />
                        <path d="m21 2-9.6 9.6" />
                        <path d="m15.5 7.5 3 3L22 7l-3-3" />
                    </svg>
                    <span class="label">Permissions</span>
                </a>
                @endcan
                @can('employees.view')
                <a class="nav-item nav-child {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}"
                    href="{{ route('admin.employees.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="16" x="2" y="4" rx="2" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                    <span class="label">Employee Directory</span>
                </a>
                @endcan
            </div>
            @endcanany
        </div>
    </nav>
    @php $printedAnySection = true; @endphp
    @endif

    @if($printedAnySection && $showMaster)
    <div class="nav-divider" aria-hidden="true"></div>
    @endif

    @if($showMaster)
    <nav class="nav-section">
        <div class="nav-title">Master Data</div>
        <div class="nav">
            <button type="button" class="nav-item js-accordion {{ $mdOpen ? 'open' : '' }}"
                data-accordion="nav-masterdata" aria-expanded="{{ $mdOpen ? 'true' : 'false' }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3" />
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                </svg>
                <span class="label">Master Data</span>
                <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div id="nav-masterdata" class="nav-children {{ $mdOpen ? 'open' : '' }}"
                data-accordion-panel="nav-masterdata">
                <a class="nav-item nav-child {{ request()->routeIs('admin.org.index') ? 'active' : '' }}"
                    href="{{ route('admin.org.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                        <path d="M9 22v-4h6v4" />
                        <path d="M8 6h.01" />
                        <path d="M16 6h.01" />
                        <path d="M8 10h.01" />
                        <path d="M16 10h.01" />
                        <path d="M8 14h.01" />
                        <path d="M16 14h.01" />
                    </svg>
                    <span class="label">Directorates & Units</span>
                </a>
                @if($isSuper || $user->hasRole('DHC'))
                <a class="nav-item nav-child {{ request()->routeIs('admin.contract-templates.*') ? 'active' : '' }}"
                    href="{{ route('admin.contract-templates.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                        <path d="M9 3v18" />
                        <path d="m14 9 3 3-3 3" />
                        <path d="M9 12h8" />
                    </svg>
                    <span class="label">Document Templates</span>
                </a>
                @endif
            </div>
        </div>
    </nav>
    @php $printedAnySection = true; @endphp
    @endif
</aside>