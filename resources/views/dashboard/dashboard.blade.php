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
  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      
    <div class="u-card u-card--glass u-p-md u-hover-lift" style="display: flex; align-items: center; gap: 1.25rem; min-height: 110px;">
      {{-- Lingkaran Ikon (Opsional untuk Visual) --}}
      <div style="width: 54px; height: 54px; background: var(--accent-ghost); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
      </div>

      {{-- Konten Teks --}}
      <div style="display: flex; flex-direction: column; gap: 2px;">
          <p class="u-muted" style="text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; margin: 0;">
              Total Pelatihan
          </p>
          <div style="display: flex; align-items: baseline; gap: 6px;">
              <h3 class="u-title" style="font-size: 2.25rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.02em;">
                  0
              </h3>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                  Pelatihan
              </span>
          </div>
      </div>
    </div>
      
    <div class="u-card u-card--glass u-p-md u-hover-lift" style="display: flex; align-items: center; gap: 1.25rem; min-height: 110px;">
      {{-- Lingkaran Ikon (Opsional untuk Visual) --}}
      <div style="width: 54px; height: 54px; background: var(--accent-ghost); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
      </div>

      {{-- Konten Teks --}}
      <div style="display: flex; flex-direction: column; gap: 2px;">
          <p class="u-muted" style="text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; margin: 0;">
              Total Pelatihan
          </p>
          <div style="display: flex; align-items: baseline; gap: 6px;">
              <h3 class="u-title" style="font-size: 2.25rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.02em;">
                  0
              </h3>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                  Pelatihan
              </span>
          </div>
      </div>
    </div>
      
    <div class="u-card u-card--glass u-p-md u-hover-lift" style="display: flex; align-items: center; gap: 1.25rem; min-height: 110px;">
      {{-- Lingkaran Ikon (Opsional untuk Visual) --}}
      <div style="width: 54px; height: 54px; background: var(--accent-ghost); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
      </div>

      {{-- Konten Teks --}}
      <div style="display: flex; flex-direction: column; gap: 2px;">
          <p class="u-muted" style="text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; margin: 0;">
              Total Pelatihan
          </p>
          <div style="display: flex; align-items: baseline; gap: 6px;">
              <h3 class="u-title" style="font-size: 2.25rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.02em;">
                  0
              </h3>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                  Pelatihan
              </span>
          </div>
      </div>
    </div>
      
    <div class="u-card u-card--glass u-p-md u-hover-lift" style="display: flex; align-items: center; gap: 1.25rem; min-height: 110px;">
      {{-- Lingkaran Ikon (Opsional untuk Visual) --}}
      <div style="width: 54px; height: 54px; background: var(--accent-ghost); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
      </div>

      {{-- Konten Teks --}}
      <div style="display: flex; flex-direction: column; gap: 2px;">
          <p class="u-muted" style="text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; margin: 0;">
              Total Pelatihan
          </p>
          <div style="display: flex; align-items: baseline; gap: 6px;">
              <h3 class="u-title" style="font-size: 2.25rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.02em;">
                  0
              </h3>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                  Pelatihan
              </span>
          </div>
      </div>
    </div>
      
    <div class="u-card u-card--glass u-p-md u-hover-lift" style="display: flex; align-items: center; gap: 1.25rem; min-height: 110px;">
      {{-- Lingkaran Ikon (Opsional untuk Visual) --}}
      <div style="width: 54px; height: 54px; background: var(--accent-ghost); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
      </div>

      {{-- Konten Teks --}}
      <div style="display: flex; flex-direction: column; gap: 2px;">
          <p class="u-muted" style="text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; margin: 0;">
              Total Pelatihan
          </p>
          <div style="display: flex; align-items: baseline; gap: 6px;">
              <h3 class="u-title" style="font-size: 2.25rem; font-weight: 800; margin: 0; color: #1D446F; letter-spacing: -0.02em;">
                  0
              </h3>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--muted); letter-spacing: 0.02em;">
                  Pelatihan
              </span>
          </div>
      </div>
    </div>

  </div>
</div>

@endsection