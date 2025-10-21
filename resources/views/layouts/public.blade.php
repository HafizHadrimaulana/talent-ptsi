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
      margin-right:5rem;
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
      <span class="text-xl font-bold text-white">Test Logo</span>
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

  <!-- ===== HERO SECTION ===== -->
  <section 
    class="relative min-h-[90vh] flex flex-col justify-center items-center text-center text-white px-6"
    style="background:url('/images/office-team.jpg') center/cover no-repeat;">
    <div class="absolute inset-0 bg-gradient-to-r from-[#1F337E]/80 to-[#49D4A9]/60"></div>

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

  <!-- ===== CONTACT ===== -->
  <footer id="contact" class="bg-[#0b132b] text-white py-12 fade-section">
    <div class="container mx-auto px-6 grid md:grid-cols-3 gap-8 text-sm">
      <div>
        <h3 class="font-semibold text-lg mb-2">Test Company</h3>
        <p>Jl. Contoh No.123, Jakarta Selatan</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg mb-2">Kontak</h3>
        <p>Email: hr@testcompany.co.id</p>
        <p>Telepon: +62 812-3456-7890</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg mb-2">Ikuti Kami</h3>
        <div class="flex gap-3 mt-2">
          <a href="#" class="hover:text-[#49D4A9]">LinkedIn</a>
          <a href="#" class="hover:text-[#49D4A9]">Instagram</a>
          <a href="#" class="hover:text-[#49D4A9]">Facebook</a>
        </div>
      </div>
    </div>
    <div class="text-center text-xs mt-8 opacity-70">© {{ date('Y') }} Test Company — All rights reserved.</div>
  </footer>

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

  </script>

</body>
</html>
