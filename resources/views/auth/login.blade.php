@extends('layouts.auth')
@section('title','Login • Talent PTSI')

@section('content')
<div class="auth-root relative min-h-dvh w-screen overflow-hidden">
  <!-- Fullscreen gradient -->
  <div class="bg-canvas fixed inset-0 -z-10 pointer-events-none"></div>

  <div class="auth-page grid min-h-dvh w-screen grid-cols-1 lg:grid-cols-[58%_42%]">

    {{-- ===== LEFT: HERO ===== --}}
    <section class="relative hidden overflow-hidden lg:block">
      <!-- Dots kiri-atas -->
      <div class="absolute left-[64px] top-[40px] h-[150px] w-[150px]">
        <div class="dots-l h-full w-full"></div>
      </div>

      <!-- Ornaments -->
      <img src="{{ Vite::asset('resources/images/blue-circle.png') }}"
           class="pointer-events-none absolute left-[-26px] top-[100px] w-[150px] opacity-90 select-none" alt="">
      <img src="{{ Vite::asset('resources/images/blue-circle-conjoined.png') }}"
           class="pointer-events-none absolute right-[360px] top-[126px] w-[176px] opacity-95 select-none" alt="">
      <img src="{{ Vite::asset('resources/images/Group-209.png') }}"
           class="pointer-events-none absolute right-[336px] top-[116px] w-[194px] opacity-90 select-none" alt="">
      <img src="{{ Vite::asset('resources/images/Vector.png') }}"
           class="pointer-events-none absolute bottom-0 left-0 w-full select-none" alt="wave">
      <img src="{{ Vite::asset('resources/images/SI-Logo.png') }}"
           class="pointer-events-none absolute right-[-120px] top-[-40px] w-[360px] opacity-[.08] select-none" alt="">

      <!-- Title + address + people -->
      <div class="relative flex h-full min-h-dvh px-[64px] pt-[60px] pb-[36px] text-white">
        <div class="z-[2] my-auto max-w-[760px]">
          <h1 class="text-[68px] leading-[1.03] font-extrabold drop-shadow-[0_12px_28px_rgba(0,0,0,.25)]">
            Talent Management<br/>System
          </h1>

          <div class="mt-6 flex items-start gap-4">
            <img src="{{ Vite::asset('resources/images/SI-Logo.png') }}" class="h-9" alt="">
            <div class="text-[13px] leading-5 opacity-95">
              <div class="font-semibold">PT Surveyor Indonesia</div>
              <div>
                Graha Surveyor Indonesia<br/>
                Jl. Jend. Gatot Subroto Kav. 56<br/>
                Jakarta 12950 - Indonesia
              </div>
            </div>
          </div>
        </div>

        <img src="{{ Vite::asset('resources/images/image-removebg-preview 1.png') }}"
             class="absolute bottom-[54px] left-[47%] w-[520px] -translate-x-[32%] drop-shadow-2xl select-none"
             alt="PTSI Team">
      </div>
    </section>

    {{-- ===== RIGHT: LOGIN CARD (compact) ===== --}}
    <section class="form-col relative flex items-center justify-center">
      <!-- dots kanan-bawah -->
      <div class="pointer-events-none absolute right-[32px] bottom-[28px] hidden h-[240px] w-[240px] lg:block">
        <div class="dots-r h-full w-full"></div>
      </div>

      <div class="w-full px-5">
        <div class="login-panel is-compact relative z-[1] -ml-[16px] rounded-[20px] p-7 lg:p-8">
          <div class="panel-halo pointer-events-none absolute -top-7 right-5 h-16 w-32 rounded-[16px]"></div>

          <!-- logo kecil -->
<div class="mb-2 flex justify-center">
  <img src="{{ Vite::asset('resources/images/sapahc.png') }}" class="h-9" alt="SAPA HC">
</div>


          <h2 class="mb-1 text-center text-[17px] font-extrabold tracking-[.02em] text-[#273142]">
            SELAMAT DATANG
          </h2>
          <p class="mb-5 text-center text-sm text-[#667085]">
            Masukan Credentials anda untuk melanjutkan
          </p>

          <form method="POST" action="{{ route('login') }}" class="space-y-3.5">
            @csrf
            <input type="email" name="email" placeholder="you@example.com"
                   class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />
            <input type="password" name="password" placeholder="********"
                   class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />

            <label class="flex items-start gap-2 text-xs text-[#445167]">
              <input type="checkbox" class="mt-0.5 rounded border-[#cdd6e3] text-brand focus:ring-brand">
              <span>Saya memahami ketentuan yang berlaku,
                <a href="#" class="font-semibold text-brand underline-offset-2 hover:underline">Baca Ketentuan Privasi Pegawai</a>
              </span>
            </label>

            <!-- hanya 1 tombol seperti request -->
            <button type="submit"
                    class="w-full rounded-lg bg-[#98A4B8] py-2.5 font-semibold text-white shadow-sm transition hover:brightness-110">
              Sign in
            </button>
          </form>
        </div>
      </div>
    </section>

    {{-- MOBILE header --}}
    <section class="relative overflow-hidden lg:hidden">
      <div class="relative px-5 py-8 text-white">
        <h1 class="text-2xl font-extrabold">Talent Management System</h1>
      </div>
    </section>
  </div>
</div>
@endsection
