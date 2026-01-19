@extends('layouts.app')
@section('title','Dashboard')

@section('content')

<div class="u-card u-card--glass u-hover-lift u-space-y-2xl">
    <div class="u-py-sm">
        <h2 class="u-title u-mb-sm u-text-2xl u-font-semibold u-tracking-wide u-uppercase ">
            Selamat Datang, {{ auth()->user()->name ?? 'Nama User' }}
        </h2>
        <p class="u-muted u-font-medium u-text-lg">
            <span class="u-flex u-items-center u-gap-md">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                {{ now()->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}
            </span>
        </p>
    </div>
  
    <div style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0, 0, 0, 0.08); padding: 1.5rem; border-radius: 18px; backdrop-filter: blur(10px);">

        <div class="u-mb-sm u-p-md">
            <h3 class="u-title" style="font-size: 1.25rem; color: #1D446F;">
                Rekap Rekrutmen
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            @isset($recruitmentApproved)
            <div class="u-card u-card--glass u-hover-lift u-flex u-items-center u-p-lg u-gap-lg"
                style="border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div class="u-flex u-items-center justify-center" 
                    style="width: 58px; height: 58px; background: var(--accent-ghost); border-radius: 12px; color: var(--accent); flex-shrink: 0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                </div>

                <div class="u-flex u-flex-col">
                    <p class="u-text-xs u-uppercase u-font-semibold u-tracking-wide u-mb-1" 
                        style="color: #64748b;">
                        Total Rekrutmen Approved
                    </p>
                    <div class="u-flex u-items-center u-gap-md">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $recruitmentApproved }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Rekrutmen
                        </span>
                    </div>
                </div>
            </div>
            @endisset

            @isset($recruitmentInReview)
            <div class="u-card u-card--glass u-hover-lift u-flex u-items-center u-p-lg u-gap-lg"
                style="border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div class="u-flex u-items-center justify-center" 
                    style="width: 58px; height: 58px; background: var(--accent-ghost); border-radius: 12px; color: var(--accent); flex-shrink: 0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>

                <div class="u-flex u-flex-col">
                    <p class="u-text-xs u-uppercase u-font-semibold u-tracking-wide u-mb-1" 
                        style="color: #64748b;">
                        Total Rekrutmen In Review
                    </p>
                    <div class="u-flex u-items-center u-gap-md">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $recruitmentInReview }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Rekrutmen
                        </span>
                    </div>
                </div>
            </div>
            @endisset

        </div>
    </div>
    
    <div style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0, 0, 0, 0.08); padding: 1.5rem; border-radius: 18px; backdrop-filter: blur(10px);">

        <div class="u-mb-sm u-p-md">
            <h3 class="u-title" style="font-size: 1.25rem; color: #1D446F;">
                Rekap Training
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="u-card u-card--glass u-hover-lift u-flex u-items-center u-p-lg u-gap-lg"
                style="border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div class="u-flex u-items-center justify-center" 
                    style="width: 58px; height: 58px; background: var(--accent-ghost); border-radius: 12px; color: var(--accent); flex-shrink: 0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                </div>

                <div class="u-flex u-flex-col">
                    <p class="u-text-xs u-uppercase u-font-semibold u-tracking-wide u-mb-1" style="color: #64748b;">
                        Total Pelatihan Diikuti
                    </p>
                    <div class="u-flex u-items-center u-gap-md">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $totalPelatihan }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Pelatihan
                        </span>
                    </div>
                </div>
            </div>

            <div class="u-card u-card--glass u-hover-lift u-flex u-items-center u-p-lg u-gap-lg"
                style="border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div class="u-flex u-items-center justify-center" 
                    style="width: 58px; height: 58px; background: var(--accent-ghost); border-radius: 12px; color: var(--accent); flex-shrink: 0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>

                <div class="u-flex u-flex-col">
                    <p class="u-text-xs u-uppercase u-font-semibold u-tracking-wide u-mb-1" 
                        style="color: #64748b;">
                        Selesai
                    </p>
                    <div class="u-flex u-items-center u-gap-md">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $pelatihanSelesai }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Selesai
                        </span>
                    </div>
                </div>
            </div>

            @isset($totalTrainingUnit)
            <div class="u-card u-card--glass u-hover-lift u-flex u-items-center u-p-lg u-gap-lg"
                style="border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div class="u-flex u-items-center justify-center" 
                    style="width: 58px; height: 58px; background: var(--accent-ghost); border-radius: 12px; color: var(--accent); flex-shrink: 0;">
                    <svg width="32" height="32" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M7 14l4-4 4 4 4-6"/>
                    </svg>
                </div>

                <div class="u-flex u-flex-col">
                    <p class="u-text-xs u-uppercase u-font-semibold u-tracking-wide u-mb-1" 
                        style="color: #64748b;">
                        Total Pelatihan Unit
                    </p>
                    <div class="u-flex u-items-center u-gap-md">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $totalTrainingUnit }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Pelatihan
                        </span>
                    </div>
                </div>
            </div>
            @endisset

        </div>
    </div>

</div>

@endsection