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
      margin-left:1.5rem;
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
    transform: scale(1.08) translateX(2%);
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
    <div class = "nav-links">
    <a href="{{ route('login') }}" class="btn btn-sm bg-white text-[#00A29A] rounded-full px-5 hover:bg-gray-100">Login</a>
   <a href="#" class="btn btn-sm bg-transparent text-white border border-white px-5 rounded-full hover:bg-gray-100 hover:text-[#00A29A]-900">Register</a>
  </div>
  </div>
</header>

<!-- ===== HERO SECTION (SLIDESHOW) ===== -->
<section class="relative min-h-[90vh] flex flex-col justify-center items-center text-center text-white px-6 overflow-hidden">
  <!-- Background slides -->
<div class="absolute inset-0">
  <div class="hero-slideshow absolute inset-0">
    <div class="slide" style="background-image:linear-gradient(rgba(0,128,0,0.3), rgba(0,128,0,0.3)), url('/images/1.jpg');"></div>
    <div class="slide" style="background-image:linear-gradient(rgba(0,128,0,0.3), rgba(0,128,0,0.3)), url('/images/2.jpg');"></div>
    <div class="slide" style="background-image:linear-gradient(rgba(0,128,0,0.3), rgba(0,128,0,0.3)), url('/images/3.jpg');"></div>
  </div>
</div>

  <!-- Text content (changes per slide) -->
  <div id="heroTextContainer" class="relative z-10 max-w-3xl space-y-6 transition-all duration-700">
    <h1 class="hero-title text-4xl md:text-5xl font-bold leading-tight opacity-0 translate-y-5 transition-all duration-700">
      Bangun Karier Masa Depan Bersama <span class="text-[#A4F5DD]">Test Company</span>
    </h1>
    <p class="hero-desc text-lg text-white/90 opacity-0 translate-y-5 transition-all duration-700">
      Kami membuka peluang bagi talenta terbaik untuk berkembang dan berkontribusi dalam lingkungan kerja yang dinamis dan kolaboratif.
    </p>
    <div class="hero-buttons flex justify-center gap-4 pt-4 opacity-0 translate-y-5 transition-all duration-700">
      <a href="#jobs" class="px-6 py-3 rounded-full font-semibold bg-[#49D4A9] hover:bg-[#3FCC97] text-white transition">Lihat Lowongan</a>
      <a href="#about" 
      class="px-6 py-3 rounded-full font-semibold bg-white text-[#00A29A] border border-transparent hover:bg-[#CCF9EA] transition">
      Pelajari Kami
     </a>
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
<section id="jobs" class="py-24 bg-gradient-to-b from-white to-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-bold text-center mb-6 text-[#00A29A]">Lowongan Tersedia</h2>
    <p class="text-center text-gray-600 mb-12">Temukan posisi yang sesuai dengan keahlianmu.</p>

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
    <p>© {{ date('Y') }} ID Survey — All Rights Reserved.</p>
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
    const slides = document.querySelectorAll('.hero-slideshow .slide');
    const title = document.querySelector('.hero-title');
    const desc = document.querySelector('.hero-desc');
    const buttons = document.querySelector('.hero-buttons');

    const slideTexts = [
      {
        title: `Bangun Karier Masa Depan Bersama <span class="text-[#A4F5DD]">ID Survey</span>`,
        desc: `Kami membuka peluang bagi talenta terbaik untuk berkembang dan berkontribusi dalam lingkungan kerja yang dinamis dan kolaboratif.`,
      },
      {
        title: `Jadilah Bagian dari <span class="text-[#A4F5DD]">Perubahan Besar</span>`,
        desc: `Berkolaborasi dengan tim inovatif yang berkomitmen membangun solusi berdampak.`,
      },
      {
        title: `Temukan <span class="text-[#A4F5DD]">Peluang Karier</span> Terbaikmu`,
        desc: `Kami percaya talenta seperti kamu dapat membawa perbedaan nyata di dunia kerja.`,
      },
    ];

    let currentSlide = 0;

    function fadeTextOut() {
      [title, desc, buttons].forEach(el => {
        el.classList.remove('opacity-100', 'translate-y-0');
        el.classList.add('opacity-0', 'translate-y-5');
      });
    }

    function fadeTextIn() {
      [title, desc, buttons].forEach(el => {
        el.classList.remove('opacity-0', 'translate-y-5');
        el.classList.add('opacity-100', 'translate-y-0');
      });
    }

    function updateSlide(index) {
      slides.forEach((s, i) => s.classList.toggle('active', i === index));
      fadeTextOut();
      setTimeout(() => {
        title.innerHTML = slideTexts[index].title;
        desc.innerHTML = slideTexts[index].desc;
        fadeTextIn();
      }, 500);
    }

    function nextSlide() {
      currentSlide = (currentSlide + 1) % slides.length;
      updateSlide(currentSlide);
    }

    // Initialize
    updateSlide(currentSlide);
    setInterval(nextSlide, 6500); // change every 6.5s

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
  </script>

</body>
</html>
