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
      <div class="absolute left-[-10px] top-[-10px] h-[150px] w-[100px] rotate-180" >
        <div class="dots-l h-full w-full"></div>
      </div>

      <!-- Ornaments -->
      <img src="{{ Vite::asset('resources/images/blue-circle.png') }}"
           class="pointer-events-none absolute right-[30px] bottom-[0px] w-[180px] opacity-90 select-none rotate-180" alt="">
      <img src="{{ Vite::asset('resources/images/blue-circle-conjoined.png') }}"
           class="pointer-events-none absolute left-[25px] bottom-[-5px] w-[150px] z-50 opacity-95 select-none" alt="">
      <img src="{{ Vite::asset('resources/images/Group-209.png') }}"
           class="pointer-events-none absolute right-[-20px] top-[80px] w-[194px] opacity-90 select-none" alt="">
      <img src="{{ Vite::asset('resources/images/Vector.png') }}"
           class="pointer-events-none fixed bottom-0 left-0 w-screen select-none" alt="wave">

      <!-- Title + address + people -->
      <div class="relative flex h-full min-h-dvh px-[64px] pt-[60px] pb-[36px] text-white">
        <div class="z-[2] my-auto max-w-[760px]">
          <h1 class="text-[68px] leading-[1.03] translate-y-[-200px] translate-x-[100px] font-extrabold drop-shadow-[0_12px_28px_rgba(0,0,0,.25)]" style="font-family:Montserrat;">
            One Platform for All<br/>Human Capital Service
          </h1>

          <div class="absolute mt-6 flex items-start gap-4  bottom-[0px] left-[180px]">
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
             class="absolute bottom-[54px] left-[50%] w-[600px] h-[500px] -translate-x-[60%] flex-shrink-0 drop-shadow-2xl select-none z-[-1]"
             style="aspect-ratio: 600 / 500;" alt="PTSI Team">
      </div>
    </section>

    {{-- ===== RIGHT: LOGIN CARD (compact) ===== --}}
    <section class="form-col relative flex items-center justify-center">
      <!-- dots kanan-bawah -->
      <img src="{{ Vite::asset('resources/images/Ornament.png') }}"
           class="pointer-events-none absolute right-[0px] bottom-[0px] hidden h-[300px] w-[300px] z-[-1] lg:block" alt="">
      <img src="{{ Vite::asset('resources/images/SI-Logo.png') }}"
           class="pointer-events-none absolute right-[0px] top-[0px] w-[300px] opacity-[1.0] select-none " alt="">

      <div class="w-full px-5 opacity-[1.0]">
        <div class="login-panel is-compact relative z-[1] -ml-[16px] rounded-[20px] p-7 lg:p-8">
          <div class="panel-halo pointer-events-none absolute -top-7 right-5 h-16 w-32 rounded-[16px]"></div>

          <!-- logo kecil -->
          <div class="mb-2 flex justify-center">
            <img src="{{ Vite::asset('resources/images/sapahc.png') }}" class="w-[150px] h-[150px] flex-shrink-0 aspect-[1/1]" alt="SAPA HC">
          </div>
          <h2 class="mb-1 text-center text-[17px] font-extrabold tracking-[.02em] text-[#273142] font-montserrat">
            SELAMAT DATANG
          </h2>
          <p class="mb-5 text-center text-sm text-[#667085]">
            Masukan Credentials anda untuk melanjutkan
          </p>

          <form method="POST" action="{{ route('login.store') }}" class="space-y-5.5">
            @csrf
            <div class="flex flex-col gap-3">
              <input
                type="text"
                name="login"
                value="{{ old('login') }}"
                placeholder="email@domain.com / EMP12345"
                autocomplete="username"
                class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />
              @error('login')
                <div class="text-red-600 text-xs">{{ $message }}</div>
              @enderror

              <input
                type="password"
                name="password"
                placeholder="********"
                autocomplete="current-password"
                class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />
              @error('password')
                <div class="text-red-600 text-xs">{{ $message }}</div>
              @enderror
            </div>

           <div class="text-xs space-y-3 text-[#445167]">

        <!-- Remember Me -->
        <label class="flex items-center gap-2">
          <input 
            type="checkbox" 
            name="remember"
            class="rounded border-[#cdd6e3] text-brand focus:ring-brand"
          >
          <span>Remember me</span>
        </label>

        <!-- Privacy Policy -->
        <label class="flex items-start gap-2">
          <input
            type="checkbox"
            id="termsCheckbox"
            class="mt-0.5 rounded border-[#cdd6e3] text-brand focus:ring-brand"
          >
          <span>
            Saya memahami ketentuan yang berlaku,
            <a href="#" class="font-semibold text-brand underline-offset-2 hover:underline">
              Baca Ketentuan Privasi Pegawai
            </a>
          </span>
        </label>

      </div>


            <div class="flex flex-col gap-2.5 mt-2">
              <button
                id="signInBtn"
                type="submit"
                class="w-full rounded bg-[#98A4B8] py-2.5 font-semibold text-white shadow-sm transition hover:brightness-110 cursor-pointer">
                Sign in
              </button>

              <button
                type="button"
                class="w-full rounded bg-[#FFFFFF] py-2.5 font-semibold text-black shadow-sm transition hover:brightness-110 cursor-pointer">
                Forgot Password
              </button>
            </div>
          </form>

        </div>
      </div>
    </section>

    {{-- MOBILE header --}}
    <section class="relative overflow-hidden lg:hidden">
      <div class="relative px-5 py-8 text-white">
        <h1 class="text-2xl font-extrabold">One Platform for All Human Capital Services </h1>
      </div>
    </section>
  </div>
</div>

<script>
  const checkbox = document.getElementById('termsCheckbox');
  const signInBtn = document.getElementById('signInBtn');

  // Disable button by default
  signInBtn.disabled = true;
  signInBtn.style.backgroundColor = '#98A4B8';
  signInBtn.style.cursor = 'not-allowed';

  checkbox.addEventListener('change', () => {
    if (checkbox.checked) {
      signInBtn.disabled = false;
      signInBtn.style.backgroundColor = '#00A29A';
      signInBtn.style.cursor = 'pointer';
    } else {
      signInBtn.disabled = true;
      signInBtn.style.backgroundColor = '#98A4B8';
      signInBtn.style.cursor = 'not-allowed';
    }
  });
</script>
@endsection
