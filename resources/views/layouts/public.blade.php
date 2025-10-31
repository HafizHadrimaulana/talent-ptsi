<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Karier di Test Company</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <style>
    /* ===== GLOBAL STYLES ===== */
    html {
      scroll-behavior: smooth;
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
    .nav-links {
      margin-right:8rem;
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
      transform: translateX(100%);
      transition: opacity 1s ease-in-out, transform 1.5s ease-in-out;
    }

    .hero-slideshow .slide.active {
      opacity: 1;
      transform: translateX(0);
    }

    .hero-slideshow .slide.prev {
      transform: translateX(-100%);
    }

  </style>
</head>

<body class="min-h-screen bg-white text-gray-900">

  <!-- ===== NAVBAR ===== -->
 <header id="navbar" class="fixed top-0 w-full z-30 bg-[#00A29A] backdrop-blur transition-all duration-300">
  <div class="container mx-auto px-6 py-3 flex items-center justify-between">
    <a href="{{ route('careers.index') }}" class="flex items-center gap-2">
      <img 
        src="{{ Vite::asset('resources/images/sapahc.png') }}" 
        alt="Test Company Logo" 
        class="h-12 w-auto object-contain"
      >
      <span class="text-xl font-bold text-white">Logo Idsurvey</span>
    </a>
    <div class = "nav-links">
    <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-white">
      <a href="#about" class="nav-link hover:text-gray-100">Tentang Kami</a>
      <a href="#vision" class="nav-link hover:text-gray-100">Visi & Misi</a>
      <a href="#jobs" class="nav-link hover:text-gray-100">Lowongan</a>
      <a href="#contact" class="nav-link hover:text-gray-100">Kontak</a>
    </nav>
    </div>
    <a href="{{ route('login') }}" class="btn btn-sm bg-white text-[#00A29A] rounded-full px-5 hover:bg-gray-100">Login</a>
  </div>
</header>

<!-- ===== HERO SECTION (SLIDESHOW) ===== -->
<section class="relative min-h-[90vh] flex flex-col justify-center items-center text-center text-white px-6 overflow-hidden">
  <!-- Slideshow container -->
  <div class="absolute inset-0">
    <div class="hero-slideshow absolute inset-0">
<div class="slide" style="background-image:url('/images/1.jpg');"></div>
<div class="slide" style="background-image:url('/images/2.jpg');"></div>
<div class="slide" style="background-image:url('/images/3.jpg');"></div>
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-[#1F337E]/80 to-[#49D4A9]/60"></div>
  </div>

  <!-- Hero content -->
  <div class="relative z-10 max-w-3xl space-y-6 fade-section">
    <h1 class="text-4xl md:text-5xl font-bold leading-tight">
      Bangun Karier Masa Depan Bersama <span class="text-[#A4F5DD]">Test Company</span>
    </h1>
    <p class="text-lg text-white/90">
      Kami membuka peluang bagi talenta terbaik untuk berkembang dan berkontribusi dalam lingkungan kerja yang dinamis dan kolaboratif.
    </p>
    <div class="flex justify-center gap-4 pt-4">
      <a href="#jobs" class="px-6 py-3 rounded-full font-semibold bg-[#49D4A9] hover:bg-[#38c29a] text-white">Lihat Lowongan</a>
      <a href="#about" class="px-6 py-3 rounded-full font-semibold border border-white/70 hover:bg-white/10">Pelajari Kami</a>
    </div>
  </div>
</section>


  <!-- ===== ABOUT ===== -->
  <section id="about" class="py-20 bg-white fade-section">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold mb-4 text-[#00A29A]">Mengapa Bergabung dengan Kami?</h2>
      <p class="text-gray-600 max-w-2xl mx-auto mb-12">
        Kami percaya bahwa keberhasilan perusahaan berawal dari orang-orang yang hebat.
      </p>
      <div class="grid md:grid-cols-3 gap-8">
        <div class="p-6 rounded-2xl bg-white shadow hover:shadow-lg transition">
          <div class="text-4xl mb-3">🚀</div>
          <h3 class="font-semibold text-lg mb-2 text-[#00A29A]">Kesempatan Bertumbuh</h3>
          <p class="text-sm text-gray-600">Kami memberikan pelatihan dan mentoring untuk membantu Anda mencapai potensi terbaik.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow hover:shadow-lg transition">
          <div class="text-4xl mb-3">🤝</div>
          <h3 class="font-semibold text-lg mb-2 text-[#00A29A]">Budaya Kolaboratif</h3>
          <p class="text-sm text-gray-600">Kami bekerja sebagai satu tim untuk mencapai tujuan bersama dalam suasana kerja yang positif.</p>
        </div>
        <div class="p-6 rounded-2xl bg-white shadow hover:shadow-lg transition">
          <div class="text-4xl mb-3">💡</div>
          <h3 class="font-semibold text-lg mb-2 text-[#00A29A]">Inovasi Berkelanjutan</h3>
          <p class="text-sm text-gray-600">Kami mendukung ide-ide baru dan keberanian untuk menciptakan perubahan positif.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== VISION ===== -->
  <section id="vision" class="py-20 bg-gradient-to-r from-[#1F337E] to-[#49D4A9] text-white text-center fade-section">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold mb-8">Visi & Misi Kami</h2>
      <div class="grid md:grid-cols-2 gap-10 text-left max-w-5xl mx-auto">
        <div class="p-6 bg-white/10 rounded-2xl backdrop-blur-sm">
          <h3 class="font-semibold text-xl mb-2">Visi</h3>
          <p class="text-white/90">
            Menjadi perusahaan yang unggul dalam memberikan solusi inovatif dan berkelanjutan.
          </p>
        </div>
        <div class="p-6 bg-white/10 rounded-2xl backdrop-blur-sm">
          <h3 class="font-semibold text-xl mb-2">Misi</h3>
          <ul class="list-disc list-inside text-white/90 space-y-1">
            <li>Menciptakan lingkungan kerja yang inklusif dan suportif.</li>
            <li>Mengembangkan talenta untuk masa depan yang lebih baik.</li>
            <li>Mendorong inovasi dalam setiap aspek pekerjaan.</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== JOBS ===== -->
  <section id="jobs" class="py-20 container mx-auto px-6 fade-section bg-white">
    <h2 class="text-3xl font-bold text-center mb-10 text-[#00A29A]">Lowongan Tersedia</h2>
    <p class="text-center text-gray-600">Belum ada lowongan saat ini.</p>
  </section>

<!-- ===== FOOTER ===== -->
<footer class="bg-[#0b132b] text-gray-200 py-12 relative overflow-hidden fade-section">
  <!-- Background pattern (optional subtle lines) -->
  <div class="absolute inset-0 opacity-5 bg-[url('/images/pattern-lines.svg')] bg-center bg-cover"></div>

  <div class="relative container mx-auto px-6 grid md:grid-cols-3 gap-10 text-sm">
    <!-- Left: Logo + Mission -->
    <div>
      <h3 class="text-xl font-semibold text-[#49D4A9] mb-3">Test Company</h3>
      <p class="leading-relaxed mb-4">
        Meningkatkan masa depan dengan solusi cerdas dan inovatif, memberdayakan bisnis serta individu untuk tumbuh bersama.
      </p>
      <!-- Social Media Buttons -->
      <div class="flex gap-4 mt-4">
        <a href="https://linkedin.com" target="_blank"
          class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full hover:border-[#49D4A9] hover:text-[#49D4A9] transition">
          <i class="fab fa-linkedin-in"></i>
        </a>
        <a href="https://instagram.com" target="_blank"
          class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full hover:border-[#49D4A9] hover:text-[#49D4A9] transition">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://x.com" target="_blank"
          class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full hover:border-[#49D4A9] hover:text-[#49D4A9] transition">
          <i class="fab fa-x-twitter"></i>
        </a>
        <a href="https://facebook.com" target="_blank"
          class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full hover:border-[#49D4A9] hover:text-[#49D4A9] transition">
          <i class="fab fa-facebook-f"></i>
        </a>
      </div>

      <!-- Back to top -->
      <button
        onclick="window.scrollTo({top:0, behavior:'smooth'})"
        class="mt-6 border border-gray-600 hover:border-[#49D4A9] hover:text-[#49D4A9] text-xs uppercase px-4 py-2 rounded-md transition flex items-center gap-2">
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
        <li><a href="#vision" class="hover:text-[#49D4A9] transition">Visi & Misi</a></li>
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
    <p>© {{ date('Y') }} Test Company — All Rights Reserved.</p>
  </div>
</footer>

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
     // Hero slideshow
      const slides = document.querySelectorAll('.hero-slideshow .slide');
      let currentSlide = 0;

      function showNextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
      }

      // Initialize slideshow
      slides[currentSlide].classList.add('active');
      setInterval(showNextSlide, 5000); // Change every 5 seconds
  </script>

</body>
</html>
