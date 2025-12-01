<!doctype html>
<html lang="id" data-theme="light">
<head>
    <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
  />
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Karier di Test Company</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  
  <style>
    
    /* ===== GLOBAL STYLES ===== */
    html {
      
      scroll-padding-top: 90px;
    }

    body {
      background-color: #ffffff;
      color: #111827;
    }

    /* ===== NAVBAR ===== */
    header {
      background-color: #00A29A !important;
      color: #ffffff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    nav a {
      color: #ffffff;
      transition: color 0.3s ease;
    }


    nav a:hover,
    nav a.active {
      color: #A4F5DD;
    }

    nav a.active::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: -4px;
      width: 100%;
      height: 2px;
      background: #A4F5DD;
      border-radius: 2px;
    }
    #navbar {
    transition: background-color 1.0s ease, box-shadow 1.0s ease, opacity 1.0s ease;
    }

    #navbar.transparent {
        background-color: transparent !important;
        box-shadow: none !important;
        opacity: 1;
    }

    #navbar.visible {
        background-color: #00A29A !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        opacity: 1;
    }

    /* ===== SECTION ANIMATIONS ===== */
    .fade-section {
      opacity: 0;
      transform: translateY(30px);
      transition: all 1.5s ease-out;
    }

    .fade-section.visible {
      opacity: 1;
      transform: translateY(0);
    }
   /* ===== HERO SLIDESHOW ===== */
  .hero-slideshow {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
  }

  .hero-slideshow .slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition:
      opacity 1.5s ease-in-out,
      transform 7s ease-in-out;
  }

  .hero-slideshow .slide.active {
    opacity: 1;
    transform: scale(1) translateX(0);
    z-index: 1;
  }

  .hero-slideshow .slide.prev {
    opacity: 0;
    transform: scale(1.05) translateX(-2%);
  }
   /*=== MISC ==*/
  #submitBtn:disabled {
    background-color: #c4c4c4; /* gray */
    cursor: not-allowed;
  }

  .hero-slideshow .slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0;
  transition: opacity 1s ease;
}

.hero-slideshow .slide.active {
  opacity: 1;
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  background: rgba(255,255,255,0.4);
  cursor: pointer;
  transition: 0.3s;
}

.dot.active {
  background: #ffffff;
  transform: scale(1.2);
}



  /* Smooth grayscale + fade for inactive boxes */
  .akh-grid:hover .akh-box:not(:hover) {
    filter: grayscale(100%);
    opacity: 0.4;
    transform: scale(0.95);
  }

  /* Tooltip/dialog fade-in animation */
  .dialog-show {
    opacity: 1 !important;
    transform: translateY(0) scale(1) !important;
  }
  </style>
</head>

<body class="min-h-screen bg-white text-gray-900">

<!-- ===== NAVBAR ===== -->
<header id="navbar" class="fixed top-0 w-full h-20 z-30 bg-[#00A29A] backdrop-blur transition-all duration-300">
  <div class="container mx-auto px-6 py-3 h-20 flex items-center justify-between">

    <!-- LEFT: Logo -->
    <a href="{{ route('careers.index') }}" class="flex items-center gap-2">
      <img src="{{ Vite::asset('resources/images/sapahc.png') }}" alt="Test Company Logo" class="h-25 w-25 object-contain">
      <img src="/images/logo.png" alt="Logo" class="w-35 h-auto object-contain">
      <img src="/images/Danantara_Indonesia.svg.png" alt="Danantara" class="w-35 h-auto object-contain">
    </a>

    <!-- RIGHT: Navigation + Auth Buttons -->
    <div class="flex items-center gap-6 text-white font-medium">

      <!-- NAV LINKS -->
      <nav class="hidden md:flex items-center gap-4 text-sm">
        <a href="#about" class="hover:text-gray-100">Tentang Kami</a>
        <span class="opacity-50">|</span>

        <a href="#jobs" class="hover:text-gray-100">Lowongan</a>
        <span class="opacity-50">|</span>

        <a href="#contact" class="hover:text-gray-100">Kontak</a>
      </nav>

      <!-- AUTH BUTTONS -->
      <div class="flex items-center gap-4">
     <a onclick="openLoginModal()"
      class="btn btn-sm bg-white text-[#00A29A] rounded-xl px-5 py-2 font-medium
              transition-all duration-200 hover:bg-gray-100 cursor-pointer">
        Login
      </a>


      <a href="#" onclick="openModal()" 
        class="btn btn-sm bg-white text-[#00A29A] rounded-xl px-5 py-2 font-medium transition-all duration-200 hover:bg-gray-100">
        Register
      </a>
    </div>

  </div>

  </div>
</header>


<!-- ===== HERO SECTION (SLIDESHOW WITH DOTS + ARROWS) ===== -->
<section class="relative min-h-[90vh] flex flex-col justify-center items-center text-center text-white px-6 overflow-hidden">

  <!-- Background slides -->
  <div class="absolute inset-0">
    <div class="hero-slideshow absolute inset-0">
      <div class="slide bg-[rgba(0,162,154,0.5)] bg-blend-multiply brightness-75"
           style="background-image: url('/images/1.jpg');"></div>

      <div class="slide bg-[rgba(0,162,154,0.5)] bg-blend-multiply brightness-75"
           style="background-image: url('/images/2.jpg');"></div>

      <div class="slide bg-[rgba(0,162,154,0.5)] bg-blend-multiply brightness-75"
           style="background-image: url('/images/3.jpg');"></div>
    </div>
  </div>

  <!-- TEXT CONTENT -->
  <div id="heroTextContainer" class="relative z-10 max-w-3xl space-y-6 transition-all duration-700">
    <h1 id="heroTitle"
        class="text-4xl md:text-5xl font-bold leading-tight opacity-0 translate-y-5 transition-all duration-700">
    </h1>

    <p id="heroDesc"
       class="text-lg text-white/90 opacity-0 translate-y-5 transition-all duration-700">
    </p>

    <div id="heroButtons"
         class="flex justify-center gap-4 pt-4 opacity-0 translate-y-5 transition-all duration-700">
      <a href="#jobs" class="px-6 py-3 rounded-2xl font-semibold bg-[#49D4A9] hover:bg-[#3FCC97] text-white transition">
        Lihat Lowongan
      </a>

      <a href="#about"
         class="px-6 py-3 rounded-2xl font-semibold bg-white text-[#00A29A] border border-transparent hover:bg-[#CCF9EA] transition">
        Pelajari Kami
      </a>
    </div>
  </div>

  <!-- LEFT ARROW -->
  <button id="prevBtn"
    class="absolute left-4 top-1/2 -translate-y-1/2 z-20 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full transition cursor-pointer">
    ❮
  </button>

  <!-- RIGHT ARROW -->
  <button id="nextBtn"
    class="absolute right-4 top-1/2 -translate-y-1/2 z-20 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full transition cursor-pointer">
    ❯
  </button>

  <!-- DOTS -->
  <div id="dotsContainer" class="absolute bottom-6 flex gap-3 z-20"></div>

</section>



  <!-- ===== ABOUT ===== -->
<!-- ================= BACKGROUND WRAPPER ================= -->
<div class="relative">
  <img src="/images/tekstur.png"
       class="absolute inset-0 w-full h-full object-cover opacity-40 -z-10"
       alt="background" />

  <!-- ================= ABOUT SECTION ================= -->
  <section id="about" class="py-20 fade-section relative">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold mb-4 text-[#00A29A]">
        Mengapa Bergabung dengan Kami?
      </h2>

      <p class="text-gray-700 max-w-2xl mx-auto mb-12">
        Kami percaya bahwa keberhasilan perusahaan berawal dari orang-orang yang hebat.
      </p>

<div class="grid md:grid-cols-3 gap-8">

  <!-- Card -->
  <div class="group p-8 rounded-2xl bg-white border border-[#1D4388]/10 
              shadow-sm hover:shadow-2xl 
              transition-all duration-500 hover:-translate-y-2 
              hover:border-[#00A29A]/40 hover:bg-gradient-to-br 
              hover:from-white hover:to-[#f0f8f8]">

    <div class="text-5xl mb-4 text-[#1D4388] transition-all duration-500 group-hover:scale-110 group-hover:text-[#00A29A]">
      🚀
    </div>

    <h3 class="font-semibold text-xl mb-3 text-[#1D4388] 
               transition-colors duration-500 group-hover:text-[#00A29A]">
      Kesempatan Bertumbuh
    </h3>

    <p class="text-sm text-gray-600 leading-relaxed">
      Kami memberikan pelatihan dan mentoring untuk membantu Anda mencapai potensi terbaik.
    </p>
  </div>

  <!-- Card -->
  <div class="group p-8 rounded-2xl bg-white border border-[#1D4388]/10 
              shadow-sm hover:shadow-2xl 
              transition-all duration-500 hover:-translate-y-2 
              hover:border-[#00A29A]/40 hover:bg-gradient-to-br 
              hover:from-white hover:to-[#f0f8f8]">

    <div class="text-5xl mb-4 text-[#1D4388] transition-all duration-500 group-hover:scale-110 group-hover:text-[#00A29A]">
      🤝
    </div>

    <h3 class="font-semibold text-xl mb-3 text-[#1D4388] 
               transition-colors duration-500 group-hover:text-[#00A29A]">
      Budaya Kolaboratif
    </h3>

    <p class="text-sm text-gray-600 leading-relaxed">
      Kami bekerja sebagai satu tim dalam suasana kerja yang positif.
    </p>
  </div>

  <!-- Card -->
  <div class="group p-8 rounded-2xl bg-white border border-[#1D4388]/10 
              shadow-sm hover:shadow-2xl 
              transition-all duration-500 hover:-translate-y-2 
              hover:border-[#00A29A]/40 hover:bg-gradient-to-br 
              hover:from-white hover:to-[#f0f8f8]">

    <div class="text-5xl mb-4 text-[#1D4388] transition-all duration-500 group-hover:scale-110 group-hover:text-[#00A29A]">
      💡
    </div>

    <h3 class="font-semibold text-xl mb-3 text-[#1D4388] 
               transition-colors duration-500 group-hover:text-[#00A29A]">
      Inovasi Berkelanjutan
    </h3>

    <p class="text-sm text-gray-600 leading-relaxed">
      Kami mendukung ide-ide baru dan keberanian untuk berinovasi.
    </p>
  </div>

</div>

  </section>


  <!-- ================= AKHLAK SECTION ================= -->
  <section class="py-20 text-center fade-section relative">

    <h2 class="text-3xl font-bold mb-8">
      Our Core Value :
      <span class="text-[#083b78]">AKH</span><span class="text-[#19b1c9]">LAK</span>
    </h2>

    <div class="container mx-auto px-6">

      <!-- GRID -->
      <div id="akhGrid"
           class="akh-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 
                  gap-6 max-w-4xl mx-auto text-white relative">

        <!-- A -->
        <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(6deg)]
                    cursor-pointer"
             data-desc="Amanah adalah sikap dapat dipercaya dalam memegang tanggung jawab.">
          A
        </div>

        <!-- K -->
        <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(-6deg)]
                    cursor-pointer"
             data-desc="Kompeten berarti memiliki kemampuan dan profesionalisme.">
          K
        </div>

        <!-- H -->
        <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(6deg)]
                    cursor-pointer"
             data-desc="Harmonis berarti saling menghormati dan peduli.">
          H
        </div>

        <!-- L -->
        <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(-6deg)]
                    cursor-pointer"
             data-desc="Loyal adalah sikap setia terhadap organisasi dan nilai-nilai.">
          L
        </div>

        <!-- A -->
        <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(6deg)]
                    cursor-pointer"
             data-desc="Adaptif berarti mampu menyesuaikan diri dengan perubahan.">
          A
        </div>

        <!-- K -->
        <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center
                    transition-all duration-500 ease-out shadow-lg hover:shadow-2xl
                    hover:-translate-y-3 hover:scale-[1.06]
                    hover:[transform:rotateX(8deg)_rotateY(-6deg)]
                    cursor-pointer"
             data-desc="Kolaboratif berarti saling mendukung untuk solusi bersama.">
          K
        </div>

      </div>

      <!-- DIALOG -->
      <div id="akhDialog"
           class="max-w-xl mx-auto mt-10 p-5 bg-gradient-to-r from-[#083b78] to-[#19b1c9] text-white rounded-xl shadow-xl opacity-0
                  transition-all duration-500 transform translate-y-4 scale-95">
        <p id="dialogContent" class="text-lg"></p>
      </div>

    </div>
  </section>

</div>


<!-- ===== JOBS ===== -->
<section id="jobs" class="py-24" style="background: linear-gradient(to bottom, #1F337E, #00A29A);">>
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-bold text-center mb-6 text-white">Lowongan Tersedia</h2>
    <p class="text-center text-white mb-12">Temukan posisi yang sesuai dengan keahlianmu.</p>

    <!-- Carousel wrapper -->
    <div class="relative">
      <!-- Left button -->
      <button id="prevJob" class="absolute -left-5 top-1/2 -translate-y-1/2 bg-[#00A29A] text-white p-3 rounded-full shadow-lg hover:scale-110 transition" style = "cursor:pointer">
        &#10094;
      </button>

      <!-- Cards container -->
      <div id="jobCarousel" class="flex gap-6 overflow-hidden scroll-smooth px-2" style = "cursor:pointer">
        <!-- Job card -->
        <div class="min-w-[320px] bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2 border border-gray-100">
          <h3 class="font-semibold text-2xl mb-2 text-gray-800">Frontend Developer</h3>
          <p class="text-gray-600 mb-1">PT. Kreatif Digital</p>
          <p class="text-sm text-gray-500 mb-4">Jakarta, Indonesia</p>
          <p class="text-gray-600 mb-4">Membangun antarmuka web interaktif menggunakan React & Tailwind.</p>
          <a href="#" class="text-[#00A29A] font-semibold hover:underline">Lihat Detail</a>
        </div>

        <div class="min-w-[320px] bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2 border border-gray-100">
          <h3 class="font-semibold text-2xl mb-2 text-gray-800">UI/UX Designer</h3>
          <p class="text-gray-600 mb-1">CV. Desain Cerdas</p>
          <p class="text-sm text-gray-500 mb-4">Bandung, Indonesia</p>
          <p class="text-gray-600 mb-4">Merancang pengalaman pengguna yang intuitif dan menarik.</p>
          <a href="#" class="text-[#00A29A] font-semibold hover:underline">Lihat Detail</a>
        </div>

        <div class="min-w-[320px] bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2 border border-gray-100">
          <h3 class="font-semibold text-2xl mb-2 text-gray-800">Backend Engineer</h3>
          <p class="text-gray-600 mb-1">TechnoWorks</p>
          <p class="text-sm text-gray-500 mb-4">Surabaya, Indonesia</p>
          <p class="text-gray-600 mb-4">Membangun API yang efisien dengan Node.js dan PostgreSQL.</p>
          <a href="#" class="text-[#00A29A] font-semibold hover:underline">Lihat Detail</a>
        </div>

        <div class="min-w-[320px] bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition transform hover:-translate-y-2 border border-gray-100">
          <h3 class="font-semibold text-2xl mb-2 text-gray-800">Data Analyst</h3>
          <p class="text-gray-600 mb-1">Insight Analytics</p>
          <p class="text-sm text-gray-500 mb-4">Yogyakarta, Indonesia</p>
          <p class="text-gray-600 mb-4">Menganalisis data bisnis untuk memberikan insight strategis.</p>
          <a href="#" class="text-[#00A29A] font-semibold hover:underline">Lihat Detail</a>
        </div>
      </div>

      <!-- Right button -->
      <button id="nextJob" class="absolute -right-5 top-1/2 -translate-y-1/2 bg-[#00A29A] text-white p-3 rounded-full shadow-lg hover:scale-110 transition" style = "cursor:pointer">
        &#10095;
      </button>
    </div>
  </div>
</section>



  <!-- ===== FOOTER ===== -->
  <footer id="contact" class="bg-[#0b132b] text-gray-200 py-12 relative overflow-hidden fade-section">
    <!-- Background pattern (optional subtle lines) -->
    <div class="absolute inset-0 opacity-5 bg-[url('/images/pattern-lines.svg')] bg-center bg-cover"></div>

    <div class="relative container mx-auto px-6 grid md:grid-cols-3 gap-10 text-sm">
      <!-- Left: Logo + Mission -->
      <div>
        <h3 class="text-xl font-semibold text-[#49D4A9] mb-3">ID Survey</h3>
        <p class="leading-relaxed mb-4">
          Meningkatkan masa depan dengan solusi cerdas dan inovatif, memberdayakan bisnis serta individu untuk tumbuh bersama.
        </p>
        <!-- Social Media Buttons -->
        <div class="flex gap-4 mt-4">
    <!-- LinkedIn -->
    <a href="https://linkedin.com" target="_blank"
      class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
      <img src="/images/LinkedIn_logo_initials.png" alt="LinkedIn"
          class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
    </a>

    <!-- Instagram -->
    <a href="https://instagram.com" target="_blank"
      class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
      <img src="/images/Instagram_icon.png" alt="Instagram"
          class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
    </a>

    <!-- X (Twitter) -->
    <a href="https://x.com" target="_blank"
      class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden bg-white hover:border-[#49D4A9] transition group">
      <img src="/images/X_logo_2023.svg.png" alt="X (Twitter)"
          class="w-full h-full object-contain scale-100 group-hover:scale-110 transition-transform duration-300">
    </a>

    <!-- Facebook -->
    <a href="https://facebook.com" target="_blank"
      class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
      <img src="/images/2021_Facebook_icon.svg.png" alt="Facebook"
          class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
    </a>
  </div>



      <!-- Back to top -->
      <button
        onclick="window.scrollTo({top:0, behavior:'smooth'})"
        class="mt-6 border border-gray-600 hover:border-[#49D4A9] hover:text-[#49D4A9] text-xs uppercase px-4 py-2 rounded-md transition flex items-center gap-2 " style = " cursor: pointer;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
        Back to Top
      </button>
    </div>

    <!-- Middle: Site Map -->
    <div>
      <h4 class="font-semibold text-lg mb-3 text-white">Site Map</h4>
      <ul class="space-y-2">
        <li><a href="#about" class="hover:text-[#49D4A9] transition">Tentang Kami</a></li>
        <li><a href="#jobs" class="hover:text-[#49D4A9] transition">Lowongan</a></li>
        <li><a href="#contact" class="hover:text-[#49D4A9] transition">Kontak</a></li>
      </ul>
    </div>

    <!-- Right: Legal -->
    <div>
      <h4 class="font-semibold text-lg mb-3 text-white">Legal</h4>
      <ul class="space-y-2">
        <li><a href="#" class="hover:text-[#49D4A9] transition">Kebijakan Privasi</a></li>
        <li><a href="#" class="hover:text-[#49D4A9] transition">Syarat & Ketentuan</a></li>
        <li><a href="#" class="hover:text-[#49D4A9] transition">Hak Cipta</a></li>
      </ul>
    </div>
  </div>

  <!-- Bottom bar -->
  <div class="relative mt-10 border-t border-gray-700 pt-6 text-center text-xs text-gray-400">
    <p>© {{ date('Y') }}  PT Surveyor Indonesia. All Rights Reserved.</p>
  </div>
</footer>

<!-- Modal Background -->
<div id="registerModal"
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">

    <!-- Modal Card -->
    <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-xl relative">

        <img src="/images/logo-alter.png" alt="logo-alter"
             class="w-auto h-12 mx-auto mb-5">

        <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">
            Pendaftaran Akun
        </h2>

        <!-- Nama Lengkap -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Nama Lengkap</label>
            <input type="text" placeholder="Masukkan nama lengkap..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- No KTP -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">No KTP</label>
            <input type="text" placeholder="Masukkan nomor KTP..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Email</label>
            <input type="email" placeholder="Masukkan email..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Password</label>
            <input type="password" placeholder="Buat password..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" placeholder="Konfirmasi password..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Checkbox -->
        <div class="mt-2">
            <label class="block font-medium text-gray-700 mb-1">
                <input type="checkbox" id="termsCheckbox" required>
                Dengan melakukan registrasi saya menyatakan telah membaca dan menerima
                <a href="#" class="font-semibold text-brand underline-offset-2 hover:underline">
                    ketentuan yang berlaku
                </a>
            </label>
        </div>

        <!-- Close Button -->
        <button onclick="closeModal()"
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 cursor-pointer">
            ✕
        </button>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-4">
            <button onclick="closeModal()"
                    class="px-5 py-2 rounded-full bg-gray-200 hover:bg-gray-300 cursor-pointer">
                Cancel
            </button>

            <button id="submitBtn"
                    class="px-5 py-2 rounded-full bg-[#00A29A] text-white hover:bg-[#008f87] cursor-pointer"
                    disabled>
                Submit
            </button>
        </div>
    </div>
</div>

<!-- LOGIN Modal Background -->
<div id="loginModal"
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">

    <!-- Modal Card -->
    <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-xl relative">

        <img src="/images/logo-alter.png" alt="logo-alter"
             class="w-auto h-12 mx-auto mb-5">

        <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">
            Login Akun
        </h2>

        <!-- Email -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Email</label>
            <input type="email"
                   placeholder="Masukkan email..."
                   id="loginEmail"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2
                          focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="block font-medium text-gray-700 mb-1">Password</label>
            <input type="password"
                   placeholder="Masukkan password..."
                   id="loginPassword"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2
                          focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   required>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2 mt-2">
            <input type="checkbox" id="rememberMe">
            <label for="rememberMe" class="block font-medium text-gray-700 mb-1">
                Remember Me
            </label>
        </div>

        <!-- Terms & Conditions -->
        <div class="mt-3">
            <label class="block font-medium text-gray-700 mb-1">
                <input type="checkbox" id="loginTermsCheckbox" required>
                Saya menyetujui
                <a href="#"
                   class="font-semibold text-brand underline-offset-2 hover:underline">
                    syarat & ketentuan
                </a>
            </label>
        </div>

        <!-- Close Button -->
        <button onclick="closeLoginModal()"
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 cursor-pointer">
            ✕
        </button>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-5">
            <button onclick="closeLoginModal()"
                    class="px-5 py-2 rounded-full bg-gray-200 hover:bg-gray-300 cursor-pointer">
                Cancel
            </button>

            <button id="loginSubmitBtn"
                    disabled
                    class="px-5 py-2 rounded-full bg-[#00A29A] text-white
                           disabled:opacity-40 disabled:cursor-not-allowed
                           hover:bg-[#008f87] cursor-pointer transition">
                Login
            </button>
        </div>
    </div>
</div>

<!-- FontAwesome Icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>


  <!-- ===== JS: smooth scroll + fade-in ===== -->
  <script>
    // Navbar
      const navbar = document.getElementById('navbar');
      let lastScrollY = window.scrollY;

      function updateNavbarVisibility() {
        if (window.scrollY <= 10) {
          navbar.classList.remove('visible');
          navbar.classList.add('transparent');
        } else {
          navbar.classList.remove('transparent');
          navbar.classList.add('visible');
        }
      }

      // Run on load
      updateNavbarVisibility();

      // Run on scroll
      window.addEventListener('scroll', updateNavbarVisibility);  

    // Smooth scroll animation
    document.querySelectorAll('a.nav-link, a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (!targetId.startsWith('#')) return;
        const target = document.querySelector(targetId);
        if (!target) return;

        e.preventDefault();
        const startPosition = window.scrollY;
        const targetPosition = target.getBoundingClientRect().top + window.scrollY - 80;
        const distance = targetPosition - startPosition;
        const duration = 1000;
        let startTime = null;

        function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

        function animation(currentTime) {
          if (startTime === null) startTime = currentTime;
          const elapsed = currentTime - startTime;
          const progress = Math.min(elapsed / duration, 1);
          const eased = easeOutCubic(progress);
          window.scrollTo(0, startPosition + distance * eased);
          if (elapsed < duration) requestAnimationFrame(animation);
        }

        requestAnimationFrame(animation);
      });
    });

    // Fade-in animation on scroll
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
      });
    }, { threshold: 0.15 });

    document.querySelectorAll('.fade-section').forEach(sec => observer.observe(sec));
  //HERO

  const preloadList = [
  "/images/1.jpg",
  "/images/2.jpg",
  "/images/3.jpg"
];

preloadList.forEach(src => {
  const img = new Image();
  img.src = src;
});


  const slides = document.querySelectorAll(".slide");
  const title = document.getElementById("heroTitle");
  const desc = document.getElementById("heroDesc");
  const buttons = document.getElementById("heroButtons");
  const dotsContainer = document.getElementById("dotsContainer");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");

  let currentSlide = 0;


  const slideTexts = [
    {
      title: `
        <span class="font-montserrat text-5xl font-light leading-tight">
          Selamat Datang
        </span><br>
        <span class="font-montserrat text-8xl font-semibold block">
          Rekrutmen
        </span>
        <span class="font-montserrat text-5xl font-bold text-[#A4F5DD]">
          PT Surveyor Indonesia
        </span>
      `,
      desc: `
        <span class="font-montserrat text-base md:text-lg font-light">
          Your Trusted Partner for Assurance.
        </span>
      `,
    },

    {
      title: `
        <span class="font-montserrat text-3xl md:text-5xl font-bold leading-snug">
          Be The Guardians of 
          <span class="text-[#A4F5DD] font-bold">Assurance</span>
        </span>
      `,
      desc: `
        <span class="font-montserrat text-base md:text-lg font-light">
          Kami mencari kandidat potensial yang dapat membuka jalan bagi pertumbuhan industri yang tangguh dan berkelanjutan.
        </span>
      `,
    },

    {
      title: `
        <span class="font-montserrat text-3xl md:text-5xl font-bold leading-snug">
          Temukan
          <span class="text-[#A4F5DD] font-bold">Peluang Karier</span>
          Terbaikmu
        </span>
      `,
      desc: `
        <span class="font-montserrat text-base md:text-lg font-light">
          Raih karir impianmu bersama Surveyor Indonesia
        </span>
      `,
    },
  ];

  // Create dots
  slideTexts.forEach((_, i) => {
    const dot = document.createElement("div");
    dot.classList.add("dot");
    dot.addEventListener("click", () => goToSlide(i));
    dotsContainer.appendChild(dot);
  });

  const dots = document.querySelectorAll(".dot");

  // Fade helpers
  function fadeTextOut() {
    [title, desc, buttons].forEach(el => {
      el.classList.remove("opacity-100", "translate-y-0");
      el.classList.add("opacity-0", "translate-y-5");
    });
  }

  function fadeTextIn() {
    [title, desc, buttons].forEach(el => {
      el.classList.remove("opacity-0", "translate-y-5");
      el.classList.add("opacity-100", "translate-y-0");
    });
  }

  function updateSlide(index) {
    slides.forEach((s, i) => s.classList.toggle("active", i === index));
    dots.forEach((d, i) => d.classList.toggle("active", i === index));

    fadeTextOut();

    setTimeout(() => {
      title.innerHTML = slideTexts[index].title;
      desc.innerHTML = slideTexts[index].desc;
      fadeTextIn();
    }, 400);
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlide(currentSlide);
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlide(currentSlide);
  }

  function goToSlide(i) {
    currentSlide = i;
    updateSlide(i);
  }

  prevBtn.addEventListener("click", prevSlide);
  nextBtn.addEventListener("click", nextSlide);
  
  // Start
  updateSlide(currentSlide);
  setInterval(nextSlide, 6500);

  const carousel = document.getElementById('jobCarousel');
  const next = document.getElementById('nextJob');
  const prev = document.getElementById('prevJob');
  const scrollAmount = 340; // pixels per click

  next.addEventListener('click', () => {
    carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  });

  prev.addEventListener('click', () => {
    carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
  });
function openModal() {
    const modal = document.getElementById('registerModal');
    const card = document.getElementById('registerCard');

    modal.classList.remove('opacity-0', 'pointer-events-none');
    card.classList.remove('opacity-0', 'scale-95');
}

function closeModal() {
    const modal = document.getElementById('registerModal');
    const card = document.getElementById('registerCard');

    // Start animation
    modal.classList.add('opacity-0', 'pointer-events-none');
    card.classList.add('opacity-0', 'scale-95');
}
    const checkbox = document.getElementById('termsCheckbox');
    const submitBtn = document.getElementById('submitBtn');

    checkbox.addEventListener('change', function () {
        if (this.checked) {
            submitBtn.disabled = false;
            submitBtn.classList.remove("bg-gray-300");
            submitBtn.classList.add("bg-[#00A29A]", "hover:bg-[#008f87]");
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove("bg-[#00A29A]", "hover:bg-[#008f87]");
            submitBtn.classList.add("bg-gray-300");
        }
    });

    const boxes = document.querySelectorAll(".akh-box");
    const dialog = document.getElementById("akhDialog");
    const content = document.getElementById("dialogContent");

    boxes.forEach(box => {
      box.addEventListener("mouseenter", () => {
        content.textContent = box.dataset.desc;
        dialog.classList.add("dialog-show");
      });

      box.addEventListener("mouseleave", () => {
        dialog.classList.remove("dialog-show");
      });
    });

        const loginModal = document.getElementById("loginModal");
    const loginSubmitBtn = document.getElementById("loginSubmitBtn");
    const loginTermsCheckbox = document.getElementById("loginTermsCheckbox");

    // OPEN modal (attach this to your login button)
    function openLoginModal() {
        loginModal.classList.remove("opacity-0", "pointer-events-none");
        loginModal.classList.add("opacity-100");
    }

    // CLOSE modal
    function closeLoginModal() {
        loginModal.classList.add("opacity-0", "pointer-events-none");
        loginModal.classList.remove("opacity-100");
    }

    // Enable submit only if T&C checked
    loginTermsCheckbox.addEventListener("change", () => {
        loginSubmitBtn.disabled = !loginTermsCheckbox.checked;
    });
</script>

</body>
</html>
