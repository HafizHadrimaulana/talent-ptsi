@extends('layouts.app')
@section('title','Dashboard')

@section('content')

<div class="u-card u-card--glass u-hover-lift u-space-y-2xl">
    <div class="u-py-sm">
        <h2 class="u-title" style="letter-spacing: -0.02em; font-size: 1.6rem; font-weight: 600; margin-bottom: 8px;">
            Selamat Datang, {{ auth()->user()->name ?? 'Nama User' }}
        </h2>
        <p class="u-muted" style="letter-spacing: 0.05em; font-size: 0.95rem; margin-top: 4px;">
            <span style="display: flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                {{ now()->format('d M Y') }}
            </span>
        </p>
    </div>
  
    <div style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0, 0, 0, 0.08); padding: 1.5rem; border-radius: 18px; backdrop-filter: blur(10px);">

        <div style="margin-bottom: 1rem; padding-left: 4px;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #1D446F; margin: 0; letter-spacing: -0.01em;">
                Rekap Rekruitmen
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            @isset($recruitmentApproved)
            <div class="u-card u-card--glass u-hover-lift" 
                style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div style="width: 48px; height: 48px; background: var(--accent-ghost); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                </div>

                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px 0;">
                        Total Rekruitment Approved
                    </p>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $recruitmentApproved }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Rekruitment
                        </span>
                    </div>
                </div>
            </div>
            @endisset

            @isset($recruitmentInReview)
            <div class="u-card u-card--glass u-hover-lift" 
                style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div style="width: 48px; height: 48px; background: #ecfdf5; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10b981; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>

                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px 0;">
                        Total Rekruitment In Review
                    </p>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $recruitmentInReview }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Rekruitment
                        </span>
                    </div>
                </div>
            </div>
            @endisset

        </div>
    </div>
    
    <div style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0, 0, 0, 0.08); padding: 1.5rem; border-radius: 18px; backdrop-filter: blur(10px);">

        <div style="margin-bottom: 1rem; padding-left: 4px;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #1D446F; margin: 0; letter-spacing: -0.01em;">
                Rekap Training
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="u-card u-card--glass u-hover-lift" 
                style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div style="width: 48px; height: 48px; background: var(--accent-ghost); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                </div>

                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px 0;">
                        Total Pelatihan Diikuti
                    </p>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
                        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.03em;">
                            {{ $totalPelatihan }}
                        </h3>
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                            Pelatihan
                        </span>
                    </div>
                </div>
            </div>

            <div class="u-card u-card--glass u-hover-lift" 
                style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05); min-height: 100px; background: white;">
                
                <div style="width: 48px; height: 48px; background: #ecfdf5; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10b981; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>

                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px 0;">
                        Selesai
                    </p>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
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
            <div class="u-card u-card--glass u-hover-lift"
                style="display: flex; align-items: center; gap: 1rem; padding: 1.25rem;
                        border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.05);
                        min-height: 100px; background: white;">

                <div style="width: 48px; height: 48px; background: #eff6ff;
                            border-radius: 12px; display: flex; align-items: center;
                            justify-content: center; color: #2563eb;">
                    <svg width="24" height="24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M7 14l4-4 4 4 4-6"/>
                    </svg>
                </div>

                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #64748b; margin: 0 0 4px 0;">
                        Total Pelatihan Unit
                    </p>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
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