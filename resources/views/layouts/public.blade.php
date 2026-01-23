<!doctype html>
<html lang="id" data-theme="light">
  <head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Karier di PTSI</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    
    <style>
      html { scroll-padding-top: 90px; }
      body { background-color: #ffffff; color: #111827; }
      header { background-color: rgba(2, 67, 170, 0) !important; color: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
      nav a { color: #ffffff; transition: color 0.3s ease; }
      nav a:hover,
      nav a.active { color: #A4F5DD; }
      nav a.active::after { content: ""; position: absolute; left: 0; bottom: -4px; width: 100%; height: 2px; background: #A4F5DD; border-radius: 2px; }
      #navbar { transition: background-color 1.0s ease, box-shadow 1.0s ease, opacity 1.0s ease; }
      #navbar.transparent { background-color: transparent !important; box-shadow: none !important; opacity: 1; }
      #navbar.visible {background-color: rgba(0, 63, 164, 0.19) !important; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); opacity: 1; }
      .fade-section { opacity: 0; transform: translateY(30px); transition: all 1.5s ease-out; }
      .fade-section.visible { opacity: 1; transform: translateY(0); }
      .hero-slideshow { position: relative; width: 100%; height: 100%; overflow: hidden; }
      .hero-slideshow .slide { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0; transition: opacity 1.5s ease-in-out, transform 7s ease-in-out; }
      .hero-slideshow .slide.active { opacity: 1; transform: scale(1) translateX(0); z-index: 1; }
      .hero-slideshow .slide.prev { opacity: 0; transform: scale(1.05) translateX(-2%); }
      #submitBtn:disabled { background-color: #c4c4c4; cursor: not-allowed; }
      .hero-slideshow .slide { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0;transition: opacity 1s ease; }
      .hero-slideshow .slide.active { opacity: 1; }
      .dot { width: 12px; height: 12px; border-radius: 999px; background: rgba(255,255,255,0.4); cursor: pointer; transition: 0.3s; }
      .dot.active { background: #ffffff; transform: scale(1.2); }
      .akh-grid:hover .akh-box:not(:hover) { filter: grayscale(100%); opacity: 0.4; transform: scale(0.95); }
      .dialog-show { opacity: 1 !important;transform: translateY(0) scale(1) !important;}
      .ck-content ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
      .ck-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 1rem; }
      .ck-content li { margin-bottom: 0.25rem; }
      .ck-content p { margin-bottom: 0.75rem; line-height: 1.6; }
      .ck-content strong { color: #111827; }
    </style>
  </head>
  <body class="min-h-screen bg-white text-gray-900">
    <header id="navbar" class="fixed top-0 w-full h-20 z-50 transition-all duration-300 shadow-md" style="background-color: #000000;">
        <div class="w-full px-6 md:px-12 h-full flex items-center justify-end md:justify-between relative">
            <a href="{{ route('recruitment.external.index') }}" class="hidden md:flex items-center gap-2 md:gap-4 overflow-hidden">
                <img src="/images/logo-danantara.png" alt="Danantara" class="h-8 md:h-10 w-auto object-contain">
                <img src="/images/logo-IDS.png" alt="Logo IDSurvey" class="h-8 md:h-10 w-auto object-contain">
                <img src="/images/logo_SI.png" alt="SI Logo" class="h-6 md:h-9 w-auto object-contain">    
            </a>
            <div class="hidden md:flex items-center gap-6 text-white font-medium">
                <nav class="flex items-center gap-4 text-sm">
                    <a href="#about" class="hover:text-[#A4F5DD] transition-colors">Tentang Kami</a>
                    <span class="opacity-50">|</span>
                    <a href="#jobs" class="hover:text-[#A4F5DD] transition-colors">Lowongan</a>
                    <span class="opacity-50">|</span>
                    <a href="#contact" class="hover:text-[#A4F5DD] transition-colors">Kontak</a>
                </nav>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="openLoginModal()" class="px-5 py-2 rounded-xl bg-white text-[#00A29A] font-bold text-sm hover:bg-gray-100 transition shadow-sm cursor-pointer">
                        Login
                    </button>
                    <button type="button" onclick="openRegisterModal()" class="px-5 py-2 rounded-xl border border-white text-white font-bold text-sm hover:bg-white/10 transition shadow-sm cursor-pointer">
                        Register
                    </button>
                </div>
            </div>

            <button id="mobileMenuBtn" onclick="toggleMobileMenu()" class="md:hidden text-white p-3 focus:outline-none cursor-pointer z-50 rounded-xl shadow-lg transition-all border border-white/20 mr-1">
            
                <svg id="iconOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                </svg>
                
                <svg id="iconClose" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 hidden">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div id="mobileMenu" class="absolute top-20 left-0 right-0 mx-auto w-[90%] max-w-[320px] bg-white border border-gray-100 shadow-xl rounded-2xl transform origin-top scale-y-0 opacity-0 transition-all duration-300 ease-in-out md:hidden z-40 overflow-hidden">
                <div class="flex flex-col p-4 gap-2">
                    <a href="#about" onclick="toggleMobileMenu()" class="text-gray-600 font-medium text-sm hover:text-[#00A29A] hover:bg-gray-50 px-4 py-2.5 rounded-lg transition-colors flex items-center gap-3">
                        <i class="fas fa-info-circle text-gray-400 w-5"></i> Tentang Kami
                    </a>
                    <a href="#jobs" onclick="toggleMobileMenu()" class="text-gray-600 font-medium text-sm hover:text-[#00A29A] hover:bg-gray-50 px-4 py-2.5 rounded-lg transition-colors flex items-center gap-3">
                        <i class="fas fa-briefcase text-gray-400 w-5"></i> Lowongan
                    </a>
                    <a href="#contact" onclick="toggleMobileMenu()" class="text-gray-600 font-medium text-sm hover:text-[#00A29A] hover:bg-gray-50 px-4 py-2.5 rounded-lg transition-colors flex items-center gap-3">
                        <i class="fas fa-phone text-gray-400 w-5"></i> Kontak
                    </a>
                    
                    <div class="h-px bg-gray-100 my-1"></div>

                    <div class="grid grid-cols-2 gap-3 mt-1">
                        <button onclick="toggleMobileMenu(); setTimeout(openLoginModal, 200);" class="flex justify-center items-center gap-2 py-2.5 px-3 bg-white border border-[#00A29A] text-[#00A29A] hover:bg-[#e6fbf7] rounded-xl text-sm font-semibold transition shadow-sm">
                            Login
                        </button>
                        
                        <button onclick="toggleMobileMenu(); setTimeout(openRegisterModal, 200);" class="flex justify-center items-center gap-2 py-2.5 px-3 bg-[#00A29A] text-white hover:bg-[#008f87] rounded-xl text-sm font-semibold transition shadow-md shadow-[#00A29A]/30">
                            Register
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <section class="relative h-screen flex flex-col justify-center items-center text-center text-white px-6 overflow-hidden">
      <div class="absolute inset-0">
        <div class="hero-slideshow absolute inset-0">
            <div class="slide relative bg-cover bg-center 
                        after:content-[''] after:absolute after:inset-0 
                        after:bg-gradient-to-t after:from-black/50 after:via-black/50 after:to-black/50 after:opacity-90" 
                style="background-image: url('/images/1.jpg');">
            </div>

            <div class="slide relative bg-cover bg-center 
                        after:content-[''] after:absolute after:inset-0 
                        after:bg-gradient-to-t after:from-black/50 after:via-black/50 after:to-black/50 after:opacity-90" 
                style="background-image: url('/images/2.jpg');">
            </div>

            <div class="slide relative bg-cover bg-center 
                        after:content-[''] after:absolute after:inset-0 
                        after:bg-gradient-to-t after:from-black/50 after:via-black/50 after:to-black/50 after:opacity-90" 
                style="background-image: url('/images/3.jpg');">
            </div>
        </div>
      </div>
      <div id="heroTextContainer" class="relative z-10 max-w-3xl space-y-6 transition-all duration-700">
        <h1 id="heroTitle"class="text-4xl md:text-5xl font-bold leading-tight opacity-0 translate-y-5 transition-all duration-700"></h1>
        <p id="heroDesc"class="text-lg text-white/90 opacity-0 translate-y-5 transition-all duration-700"></p>
        <div id="heroButtons" class="flex justify-center gap-4 pt-4 opacity-0 translate-y-5 transition-all duration-700">
          <a href="#jobs" class="px-6 py-3 rounded-2xl font-semibold bg-[#49D4A9] hover:bg-[#3FCC97] text-white transition">
            Lihat Lowongan
          </a>
          <a href="#about" class="px-6 py-3 rounded-2xl font-semibold bg-white text-[#00A29A] border border-transparent hover:bg-[#CCF9EA] transition">
            Pelajari Kami
          </a>
        </div>
      </div>
      <div id="dotsContainer" class="absolute bottom-6 flex gap-3 z-20"></div>
    </section>
    <div class="relative">
      <img src="/images/tekstur.png"
          class="absolute inset-0 w-full h-full object-cover opacity-40 -z-10"
          alt="background" />
      <section id="about" class="py-20 fade-section relative">
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl font-bold mb-4 text-[#00A29A]">
            VISI & MISI
          </h2>
          <p class="text-gray-700 max-w-2xl mx-auto mb-12">
            Kami hadir untuk memberikan sumbangsih terbaik bagi negeri dengan mengutamakan profesionalisme, integritas serta bersinergi dalam mewujudkan Indonesia yang lebih baik di masa depan.
          </p>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="group p-8 rounded-2xl bg-white border border-[#1D4388]/10 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 hover:border-[#00A29A]/40 hover:bg-gradient-to-br hover:from-white hover:to-[#f0f8f8]">
                    <h3 class="font-semibold text-xl mb-3 text-[#1D4388] transition-colors duration-500 group-hover:text-[#00A29A]">
                    VISI
                    </h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                    Menjadi perusahaan pemberi jaminan kepastian yang tidak memihak (Independent Assurance) yang terpercaya dan berkontribusi strategis bagi kepentingan nasional
                    </p>
                </div>
                <div class="group p-8 rounded-2xl bg-white border border-[#1D4388]/10 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 hover:border-[#00A29A]/40 hover:bg-gradient-to-br hover:from-white hover:to-[#f0f8f8]">
                    <h3 class="font-semibold text-xl mb-3 text-[#1D4388] transition-colors duration-500 group-hover:text-[#00A29A]">
                    MISI
                    </h3>
                    <p>
                        <li class="text-sm text-gray-600 leading-relaxed" style="text-align: left;">Meningkatkan pangsa pasar sebagai penyedia layanan TIC (Testing, Inspection, Certification) terdepan di Indonesia</li>
                        <li class="text-sm text-gray-600 leading-relaxed" style="text-align: left;">Mengembangkan inovasi produk dan layanan yang terintegrasi dan canggih</li>
                        <li class="text-sm text-gray-600 leading-relaxed" style="text-align: left;">Membangun keunggulan sebagai penyedia layanan TIC kelas dunia</li>
                        <li class="text-sm text-gray-600 leading-relaxed" style="text-align: left;">Menjadi mitra strategis Pemerintah dan Swasta untuk mengoptimalkan sumber daya nasional</li>
                        <li class="text-sm text-gray-600 leading-relaxed" style="text-align: left;">Meningkatkan kompetensi SDM dan kemampuan teknologi sesuai standar nasional & internasional</li>
                    </p>
                </div>
            </div>
        </div>
      </section>
      <section class="py-20 text-center fade-section relative">
        <h2 class="text-3xl font-bold mb-8">
          Our Core Values :
          <span class="text-[#083b78]">AKH</span><span class="text-[#19b1c9]">LAK</span>
        </h2>
        <div class="container mx-auto px-6">
          <div id="akhGrid" class="akh-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 max-w-4xl mx-auto text-white relative">
            <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(6deg)] cursor-pointer" data-title="Amanah" data-desc="Memegang teguh kepercayaan yang diberikan.">
              A
            </div>
            <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(-6deg)] cursor-pointer" data-title="Kompeten" data-desc="Terus belajar dan meningkatkan kapabilitas.">
              K
            </div>
            <div class="akh-box p-6 bg-[#083b78] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(6deg)] cursor-pointer" data-title="Harmonis" data-desc="Saling peduli dan menghormati perbedaan.">
              H
            </div>
            <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(-6deg)] cursor-pointer" data-title="Loyal" data-desc="Berdedikasi dan dan mengutamakan kepentigan bangsa dan negara.">
              L
            </div>
            <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(6deg)] cursor-pointer" data-title="Adaptif" data-desc="Terus berinovasi dan antusias dalam menggerakkan ataupun menghadapi perubahan.">
              A
            </div>
            <div class="akh-box p-6 bg-[#19b1c9] rounded-xl font-bold text-3xl text-center transition-all duration-500 ease-out shadow-lg hover:shadow-2xl hover:-translate-y-3 hover:scale-[1.06] hover:[transform:rotateX(8deg)_rotateY(-6deg)] cursor-pointer" data-title="Kompeten" data-desc="Membangun kerjasama yang sinergis.">
              K
            </div>
          </div>
          <div id="akhDialog" class="max-w-xl mx-auto mt-10 p-5 bg-gradient-to-r from-[#083b78] to-[#19b1c9] text-white rounded-xl shadow-xl opacity-0 transition-all duration-500 transform translate-y-4 scale-95">            
          <h2 id="akhDialogTitle" class="text-xl font-bold mb-3"></h2>
          <p id="akhDialogContent" class="text-lg"></p>
          </div>
        </div>
      </section>
    </div>
    <section id="jobs" class="py-24" style="background: linear-gradient(to bottom, #1F337E, #00A29A);">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-white mb-4">Lowongan Tersedia</h2>
                <p class="text-white/90">Bergabunglah bersama kami untuk membangun masa depan.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($vacancies as $job)
                    <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 flex flex-col justify-between h-full">
                        <div>
                            <h3 class="font-bold text-xl text-gray-800 mb-1 line-clamp-2" title="{{ $job->positionObj->name ?? $job->position }}">
                                {{ $job->positionObj->name ?? $job->position }}
                            </h3>
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                                <i class="fas fa-building text-[#00A29A]"></i>
                                <span class="truncate">
                                    @php
                                        $displayLoc = $job->publish_location;
                                        if (empty($displayLoc)) {
                                            $displayLoc = data_get($job->meta, 'recruitment_details.0.location');
                                        }
                                        if (empty($displayLoc)) {
                                            $displayLoc = $job->unit->name ?? 'Kantor Pusat';
                                        }
                                    @endphp
                                    
                                    {{ $displayLoc }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                                <i class="fas fa-building text-[#00A29A]"></i>
                                <span class="truncate">{{ $job->unit->name ?? 'Kantor Pusat' }}</span>
                            </div>
                            <div class="space-y-2 mb-6 text-sm border-t border-gray-100 pt-4">
                              <div class="flex justify-between">
                                  <span class="text-gray-500">Dibuka:</span>
                                  <span class="font-medium text-gray-700">
                                      @if($job->publish_start_date)
                                          {{ \Carbon\Carbon::parse($job->publish_start_date)->format('d M Y') }}
                                      @elseif($job->target_start_date)
                                          {{ \Carbon\Carbon::parse($job->target_start_date)->format('d M Y') }}
                                      @else
                                          -
                                      @endif
                                  </span>
                              </div>
                              <div class="flex justify-between">
                                  <span class="text-gray-500">Ditutup:</span>
                                  <span class="font-medium text-red-500">
                                      @if($job->publish_end_date)
                                          {{ \Carbon\Carbon::parse($job->publish_end_date)->format('d M Y') }}
                                      @elseif($job->target_end_date)
                                          {{ \Carbon\Carbon::parse($job->target_end_date)->format('d M Y') }}
                                      @else
                                          Secepatnya
                                      @endif
                                  </span>
                              </div>
                          </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mt-auto">
                            <button type="button" 
                                    class="px-4 py-2 rounded-xl border border-[#00A29A] text-[#00A29A] font-semibold hover:bg-[#e6fbf7] transition text-sm cursor-pointer js-btn-detail"
                                    data-job="{{ json_encode($job, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
                                Detail
                            </button>
                            @auth
                                <a href="{{ route('recruitment.external.index') }}" 
                                  class="px-4 py-2 rounded-xl bg-[#00A29A] text-white font-semibold hover:bg-[#008f87] transition text-sm text-center">
                                    Lamar
                                </a>
                            @else
                                <button onclick="triggerLoginForApply()" 
                                        class="px-4 py-2 rounded-xl bg-[#00A29A] text-white font-semibold hover:bg-[#008f87] transition text-sm">
                                    Lamar
                                </button>
                            @endauth
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 bg-white/10 rounded-2xl backdrop-blur-sm border border-white/20">
                        <div class="text-6xl mb-4">📫</div>
                        <h3 class="text-2xl font-bold text-white mb-2">Belum ada lowongan dibuka</h3>
                        <p class="text-white/80">Silakan cek kembali secara berkala.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <footer id="contact" class="bg-[#0b132b] text-gray-200 py-12 relative overflow-hidden fade-section">
      <div class="absolute inset-0 opacity-5 bg-[url('/images/pattern-lines.svg')] bg-center bg-cover"></div>
      <div class="relative container mx-auto px-6 grid md:grid-cols-3 gap-10 text-sm">
        <div>
          <h3 class="text-xl font-semibold text-[#49D4A9] mb-3">PT Surveyor Indonesia</h3>
          <p class="leading-relaxed mb-4">
            Meningkatkan masa depan dengan solusi cerdas dan inovatif, memberdayakan bisnis serta individu untuk tumbuh bersama.
          </p>
          <div class="flex gap-4 mt-4">
            <a href="https://linkedin.com" target="_blank" class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
              <img src="/images/LinkedIn_logo_initials.png" alt="LinkedIn" class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
            </a>
            <a href="https://instagram.com" target="_blank" class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
              <img src="/images/Instagram_icon.png" alt="Instagram" class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
            </a>
            <a href="https://x.com" target="_blank" class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden bg-white hover:border-[#49D4A9] transition group">
              <img src="/images/X_logo_2023.svg.png" alt="X (Twitter)" class="w-full h-full object-contain scale-100 group-hover:scale-110 transition-transform duration-300">
            </a>
            <a href="https://facebook.com" target="_blank" class="w-9 h-9 flex items-center justify-center border border-gray-500 rounded-full overflow-hidden hover:border-[#49D4A9] transition group">
              <img src="/images/2021_Facebook_icon.svg.png" alt="Facebook" class="w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-300">
            </a>
          </div>
          <button onclick="window.scrollTo({top:0, behavior:'smooth'})" class="mt-6 border border-gray-600 hover:border-[#49D4A9] hover:text-[#49D4A9] text-xs uppercase px-4 py-2 rounded-md transition flex items-center gap-2 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
            Back to Top
          </button>
        </div>
        <div>
          <h4 class="font-semibold text-lg mb-3 text-white">Site Map</h4>
          <ul class="space-y-2">
            <li><a href="#about" class="hover:text-[#49D4A9] transition">Tentang Kami</a></li>
            <li><a href="#jobs" class="hover:text-[#49D4A9] transition">Lowongan</a></li>
            <li><a href="#contact" class="hover:text-[#49D4A9] transition">Kontak</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold text-lg mb-3 text-white">Legal</h4>
          <ul class="space-y-2">
            <li><a href="https://sapahc.ptsi.co.id/privacy-policy" class="hover:text-[#49D4A9] transition">Kebijakan Privasi</a></li>
          </ul>
        </div>
      </div>
      <div class="relative mt-10 border-t border-gray-700 pt-6 w-full flex flex-col items-center justify-center">
        <p class="text-xs text-gray-400 text-center">
            &copy; {{ date('Y') }} Human Capital Information System. <br>
            All rights reserved.
        </p>
      </div>
    </footer>
    <div id="registerModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] opacity-0 pointer-events-none transition-opacity duration-300 backdrop-blur-sm">
        <div id="registerCard" class="bg-white w-[90%] sm:w-full max-w-[380px] rounded-2xl p-5 sm:p-6 shadow-2xl relative max-h-[85vh] overflow-y-auto scroll-smooth">
            <button type="button" onclick="closeRegisterModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 cursor-pointer z-10">
                ✕
            </button>
            <div class="text-center mb-5">
                <img src="/images/logo-alter.png" alt="logo-alter" class="w-auto h-12 mx-auto mb-2">
                <h2 class="text-xl font-semibold text-gray-800">Pendaftaran Akun</h2>
            </div>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="block font-medium text-gray-700 mb-1 text-sm">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama lengkap..." class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[#00A29A] focus:outline-none text-sm" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="block font-medium text-gray-700 mb-1 text-sm">No KTP</label>
                    <input type="text" name="nik" value="{{ old('nik') }}" placeholder="16 digit NIK..." class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[#00A29A] focus:outline-none text-sm" maxlength="16" required>
                    @error('nik') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="block font-medium text-gray-700 mb-1 text-sm">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[#00A29A] focus:outline-none text-sm" required>
                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="block font-medium text-gray-700 mb-1 text-sm">Password</label>
                    <div class="relative">
                        <input type="password" id="registerPassword" name="password" placeholder="Password..." class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 focus:ring-2 focus:ring-[#00A29A] focus:outline-none text-sm" required>
                        <button type="button" onclick="togglePasswordVisibility('registerPassword', this)" class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-500 hover:text-[#00A29A] focus:outline-none cursor-pointer bg-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.452 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="block font-medium text-gray-700 mb-1 text-sm">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="registerConfirmPassword" name="password_confirmation" placeholder="Ulangi password..." class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 focus:ring-2 focus:ring-[#00A29A] focus:outline-none text-sm" required>
                        <button type="button" onclick="togglePasswordVisibility('registerConfirmPassword', this)" class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-500 hover:text-[#00A29A] focus:outline-none cursor-pointer bg-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.452 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-start gap-2 font-medium text-gray-700 text-xs cursor-pointer">
                        <input type="checkbox" id="termsCheckbox" class="mt-0.5" required>
                        <span>
                            Saya menyetujui
                            <a href="https://sapahc.ptsi.co.id/privacy-policy" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#00A29A] hover:underline">syarat & ketentuan</a> yang berlaku.
                        </span>
                    </label>
                </div>
                <div class="text-sm text-[#667085] mt-4" style="text-align: right;">
                    Sudah punya akun?
                    <a href="#" onclick="event.preventDefault(); openLoginModal(); closeRegisterModal();" class="font-semibold text-brand underline-offset-2 hover:underline" style="color: #00A29A;">
                        Masuk
                    </a>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeRegisterModal()" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium transition cursor-pointer">
                        Batal
                    </button>
                    <button id="submitBtn" type="submit" disabled class="px-6 py-2 rounded-lg bg-gray-300 text-white text-sm font-medium cursor-not-allowed transition-all">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="loginModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] opacity-0 pointer-events-none transition-opacity duration-300 backdrop-blur-sm">
        <div class="bg-white w-[90%] sm:w-full max-w-[350px] rounded-2xl p-5 sm:p-6 shadow-2xl relative transform transition-all">
            <img src="/images/logo-alter.png" alt="logo-alter" class="w-auto h-12 mx-auto mb-5">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">
                Login
            </h2>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5.5">
                @csrf
                <div class="flex flex-col gap-3">
                    <input type="text" name="login" value="{{ old('login') }}" placeholder="email@domain.com / EMP12345" autocomplete="username" class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />
                    <div class="relative">
                        <input type="password" id="loginPassword" name="password" placeholder="********" autocomplete="current-password" class="w-full rounded-lg border border-[#d8dee8] bg-white px-4 py-2.5 pr-10 text-sm focus:border-brand focus:ring-2 focus:ring-brand" />
                        <button type="button" onclick="togglePasswordVisibility('loginPassword', this)" class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-500 hover:text-[#00A29A] focus:outline-none cursor-pointer bg-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.452 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @if($errors->has('login') || $errors->has('password'))
                        <div class="text-red-600 text-xs">
                            {{ $errors->first('login') ?: $errors->first('password') }}
                        </div>
                    @endif
                </div>
                <div class="text-xs space-y-3 text-[#445167]">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-[#cdd6e3] text-brand focus:ring-brand">
                        <span>Remember me</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" id="loginTermsCheckbox" class="mt-0.5 rounded border-[#cdd6e3] text-brand focus:ring-brand">
                        <span>
                            Saya memahami ketentuan yang berlaku,
                            <a href="https://sapahc.ptsi.co.id/privacy-policy" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand underline-offset-2 hover:underline">
                                Baca Ketentuan Privasi Pegawai
                            </a>
                        </span>
                    </label>
                </div>
                <div class="text-sm text-[#667085]" style="text-align: right;">
                    Belum punya akun? 
                    <a href="#" onclick="event.preventDefault(); closeLoginModal(); openRegisterModal();" class="font-semibold text-brand underline-offset-2 hover:underline" style="color: #00A29A;">
                        Daftar disini
                    </a>
                </div>
                <div class="flex flex-col gap-2.5 mt-2">
                    <button id="loginSubmitBtn" type="submit" class="w-full rounded bg-[#98A4B8] py-2.5 font-semibold text-white shadow-sm transition-all duration-300 hover:brightness-110 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                        Sign in
                    </button>
                    <button id="forgotPwBtn" type="button" class="w-full rounded bg-white py-2.5 font-semibold text-black shadow-sm transition-all duration-300 hover:bg-gray-100 hover:shadow-md cursor-pointer">
                      Forgot Password
                  </button>
                </div>
            </form>
            <button onclick="closeLoginModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 cursor-pointer">
                ✕
            </button>
        </div>
    </div>
    <div id="publicJobModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] opacity-0 pointer-events-none transition-opacity duration-300 backdrop-blur-sm">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl relative transform transition-all duration-300 scale-95 flex flex-col max-h-[85vh]">
            <div class="p-6 border-b border-gray-100 flex justify-between items-start bg-gray-50 rounded-t-2xl">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800" id="modalJobTitle">Posisi</h3>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <i class="fas fa-building text-[#00A29A]"></i> <span id="modalJobUnit">Unit</span>
                    </div>
                </div>
                <button onclick="closePublicJobModal()" class="text-gray-400 hover:text-red-500 transition p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <div class="grid grid-cols-2 gap-4 mb-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
                  <div>
                      <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Lowongan Dibuka</div>
                      <div id="modalJobStart" class="text-gray-800 font-medium">-</div>
                  </div>
                  <div>
                      <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Lowongan Ditutup</div>
                      <div id="modalJobEnd" class="text-gray-800 font-medium">-</div> 
                  </div>
                  <div>
                      <div class="text-xs text-gray-500 uppercase font-bold tracking-wider mt-2">Lokasi Penempatan</div>
                      <div id="modalJobLocation" class="text-gray-800 font-medium">-</div>
                  </div>
              </div>
                <div class="mb-2">
                    <h4 class="font-bold text-gray-800 mb-3 text-lg border-l-4 border-[#00A29A] pl-3">Deskripsi & Kualifikasi</h4>
                    <div id="modalJobDesc" class="prose prose-sm max-w-none text-gray-600 ck-content"></div>
                </div>
            </div>
            <div class="p-5 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                <button onclick="closePublicJobModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-medium hover:bg-gray-100 transition text-sm">
                    Tutup
                </button>
                @auth
                    <a href="{{ route('recruitment.external.index') }}" class="px-6 py-2.5 rounded-xl bg-[#00A29A] text-white font-bold hover:bg-[#008f87] transition shadow-lg shadow-[#00A29A]/30 text-sm">
                        Lamar Sekarang
                    </a>
                @else
                    <button onclick="triggerLoginForApply()" class="px-6 py-2.5 rounded-xl bg-[#00A29A] text-white font-bold hover:bg-[#008f87] transition shadow-lg shadow-[#00A29A]/30 text-sm">
                        Register / Login untuk Melamar Lowongan
                    </button>
                @endauth
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function openPublicJobDetail(jobData) {
            const modal = document.getElementById('publicJobModal');
            if (!modal) return;
            document.getElementById('modalJobDesc').innerHTML = '<div class="u-text-center"><i class="fas fa-circle-notch fa-spin"></i> Memuat...</div>';
            let displayTitle = jobData.position;
            if (jobData.position_obj && jobData.position_obj.name) {
                displayTitle = jobData.position_obj.name;
            } else if (jobData.positionObj && jobData.positionObj.name) {
                displayTitle = jobData.positionObj.name;
            }
            if (!displayTitle) displayTitle = jobData.title;
            document.getElementById('modalJobTitle').textContent = displayTitle;
            document.getElementById('modalJobUnit').textContent = jobData.unit ? jobData.unit.name : '-';
            const startDate = jobData.publish_start_date || jobData.target_start_date;
            const endDate   = jobData.publish_end_date   || jobData.target_end_date;
            if (startDate) {
                const d = new Date(startDate);
                document.getElementById('modalJobStart').textContent = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            } else {
                document.getElementById('modalJobStart').textContent = '-';
            }
            if (endDate) {
                const d = new Date(endDate);
                document.getElementById('modalJobEnd').textContent = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            } else {
                document.getElementById('modalJobEnd').textContent = 'Secepatnya';
            }
            let location = jobData.publish_location;
            if (!location && jobData.meta && jobData.meta.recruitment_details && jobData.meta.recruitment_details.length > 0) {
                location = jobData.meta.recruitment_details[0].location;
            }
            if (!location) {
                location = jobData.unit ? jobData.unit.name : 'Kantor Pusat';
            }
            document.getElementById('modalJobLocation').textContent = location;
            const descBox = document.getElementById('modalJobDesc');
            if (jobData.description) {
                descBox.innerHTML = jobData.description;
            } else {
                descBox.innerHTML = '<div class="p-4 text-center text-gray-500 italic">Tidak ada deskripsi detail untuk posisi ini.</div>';
            }
            modal.classList.remove('opacity-0', 'pointer-events-none');
            const card = modal.querySelector('div');
            if(card) {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }
            document.body.style.overflow = 'hidden';
        }
        function closePublicJobModal() {
            const modal = document.getElementById('publicJobModal');
            if (!modal) return;
            modal.classList.add('opacity-0', 'pointer-events-none');
            const card = modal.querySelector('div');
            if(card) {
                card.classList.add('scale-95');
                card.classList.remove('scale-100');
            }
            document.body.style.overflow = 'auto';
        }
        function openLoginModal() {
            const modal = document.getElementById("loginModal");
            if(modal) { 
                modal.classList.remove("opacity-0", "pointer-events-none"); 
                modal.classList.add("opacity-100", "pointer-events-auto"); 
            }
        }
        function closeLoginModal() {
            const modal = document.getElementById("loginModal");
            if(modal) { 
                modal.classList.add("opacity-0", "pointer-events-none"); 
                modal.classList.remove("opacity-100", "pointer-events-auto"); 
            }
        }
        function openRegisterModal() {
            const modal = document.getElementById("registerModal");
            if(modal) { 
                modal.classList.remove("opacity-0", "pointer-events-none"); 
                modal.classList.add("opacity-100", "pointer-events-auto"); 
            }
        }
        function closeRegisterModal() {
            const modal = document.getElementById("registerModal");
            if(modal) { 
                modal.classList.add("opacity-0", "pointer-events-none"); 
                modal.classList.remove("opacity-100", "pointer-events-auto"); 
            }
        }
        function triggerLoginForApply() {
            closePublicJobModal();
            setTimeout(() => { openLoginModal(); }, 300);
        }
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const iconOpen = document.getElementById('iconOpen');
            const iconClose = document.getElementById('iconClose');
            if (menu.classList.contains('scale-y-0')) {
                menu.classList.remove('scale-y-0', 'opacity-0');
                menu.classList.add('scale-y-100', 'opacity-100');
                if(iconOpen) iconOpen.classList.add('hidden');
                if(iconClose) iconClose.classList.remove('hidden');
            } else {
                menu.classList.add('scale-y-0', 'opacity-0');
                menu.classList.remove('scale-y-100', 'opacity-100');
                if(iconOpen) iconOpen.classList.remove('hidden');
                if(iconClose) iconClose.classList.add('hidden');
            }
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const btn = document.getElementById('mobileMenuBtn');
            if (menu && btn && !menu.contains(event.target) && !btn.contains(event.target) && !menu.classList.contains('scale-y-0')) {
                toggleMobileMenu();
            }
        });
        function togglePasswordVisibility(inputId, triggerBtn) {
            const input = document.getElementById(inputId);
            const svgs = triggerBtn.getElementsByTagName('svg');
            const eyeIcon = svgs[0];
            const eyeSlashIcon = svgs[1];
            if (input.type === "password") {
                input.type = "text";
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                input.type = "password";
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
            const loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeLoginModal();
                    }
                });
            }
            const registerModal = document.getElementById('registerModal');
            if (registerModal) {
                registerModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeRegisterModal();
                    }
                });
            }
            const publicJobModal = document.getElementById('publicJobModal');
            if (publicJobModal) {
                publicJobModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closePublicJobModal();
                    }
                });
            }
            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-btn-detail');
                if (btn) {
                    try {
                        const rawData = btn.getAttribute('data-job');
                        if (!rawData) {
                            throw new Error("Data lowongan kosong");
                        }
                        const jobData = JSON.parse(rawData);
                        openPublicJobDetail(jobData);
                    } catch (error) {
                        console.error("Gagal memproses data lowongan:", error);
                        alert("Terjadi kesalahan saat memuat detail data. Silakan refresh halaman.");
                    }
                }
            });
            const navbar = document.getElementById('navbar');
            if(navbar){
                window.addEventListener('scroll', () => {
                    if (window.scrollY <= 10) {
                        navbar.classList.remove('visible'); navbar.classList.add('transparent');
                    } else {
                        navbar.classList.remove('transparent'); navbar.classList.add('visible');
                    }
                });
                if (window.scrollY <= 10) { navbar.classList.add('transparent'); } 
                else { navbar.classList.add('visible'); }
            }
            const slides = document.querySelectorAll(".slide");
            const title = document.getElementById("heroTitle");
            const desc = document.getElementById("heroDesc");
            const buttons = document.getElementById("heroButtons");
            const dotsContainer = document.getElementById("dotsContainer");
            const prevBtn = document.getElementById("prevBtn");
            const nextBtn = document.getElementById("nextBtn");
            const slideTexts = [
                {
                    title: `<span class="font-montserrat text-3xl md:text-3xl font-light leading-snug">Selamat Datang di</span><br><span class="font-montserrat text-4xl md:text-6xl font-semibold block">SAPA HC</span><span class="text-[#A4F5DD] font-semibold">PT Surveyor Indonesia</span>`,
                    desc: `<span class="font-montserrat text-base md:text-lg font-light">Your Trusted Partner for Assurance.</span>`,
                },
                {
                    title: `<span class="font-montserrat text-3xl md:text-5xl font-bold leading-snug">Be The Guardians of <span class="text-[#A4F5DD] font-bold">Assurance</span></span>`,
                    desc: `<span class="font-montserrat text-base md:text-lg font-light">Kami mencari kandidat potensial yang dapat membuka jalan bagi pertumbuhan industri yang tangguh dan berkelanjutan.</span>`,
                },
                {
                    title: `<span class="font-montserrat text-3xl md:text-5xl font-bold leading-snug">Temukan <span class="text-[#A4F5DD] font-bold">Peluang Karier</span> Terbaikmu</span>`,
                    desc: `<span class="font-montserrat text-base md:text-lg font-light">Raih karir impianmu bersama Surveyor Indonesia</span>`,
                },
            ];
            let currentSlide = 0;
            function updateSlide(index) {
                if(slides.length === 0) return;
                slides.forEach((s, i) => s.classList.toggle("active", i === index));
                const allDots = document.querySelectorAll(".dot");
                allDots.forEach((d, i) => d.classList.toggle("active", i === index));
                if(title) { title.classList.remove("opacity-100", "translate-y-0"); title.classList.add("opacity-0", "translate-y-5"); }
                if(desc) { desc.classList.remove("opacity-100", "translate-y-0"); desc.classList.add("opacity-0", "translate-y-5"); }
                if(buttons) { buttons.classList.remove("opacity-100", "translate-y-0"); buttons.classList.add("opacity-0", "translate-y-5"); }
                setTimeout(() => {
                    if(title) { title.innerHTML = slideTexts[index].title; title.classList.remove("opacity-0", "translate-y-5"); title.classList.add("opacity-100", "translate-y-0"); }
                    if(desc) { desc.innerHTML = slideTexts[index].desc; desc.classList.remove("opacity-0", "translate-y-5"); desc.classList.add("opacity-100", "translate-y-0"); }
                    if(buttons) { buttons.classList.remove("opacity-0", "translate-y-5"); buttons.classList.add("opacity-100", "translate-y-0"); }
                }, 400);
            }
            if(dotsContainer && slides.length > 0) {
                slideTexts.forEach((_, i) => {
                    const dot = document.createElement("div");
                    dot.classList.add("dot");
                    dot.addEventListener("click", () => { currentSlide = i; updateSlide(i); });
                    dotsContainer.appendChild(dot);
                });
            }
            if(prevBtn) prevBtn.addEventListener("click", () => {
                currentSlide = (currentSlide - 1 + slideTexts.length) % slideTexts.length;
                updateSlide(currentSlide);
            });
            if(nextBtn) nextBtn.addEventListener("click", () => {
                currentSlide = (currentSlide + 1) % slideTexts.length;
                updateSlide(currentSlide);
            });
            if(slides.length > 0) {
                updateSlide(0);
                setInterval(() => {
                    currentSlide = (currentSlide + 1) % slideTexts.length;
                    updateSlide(currentSlide);
                }, 6500);
            }
            const termsCheckbox = document.getElementById('termsCheckbox');
            const regSubmitBtn = document.getElementById('submitBtn');
            if(termsCheckbox && regSubmitBtn){
                const updateSubmitBtn = () => {
                    regSubmitBtn.disabled = !termsCheckbox.checked;
                    if(termsCheckbox.checked) {
                        regSubmitBtn.classList.remove("bg-gray-300", "cursor-not-allowed");
                        regSubmitBtn.classList.add("bg-[#00A29A]", "hover:bg-[#008f87]", "cursor-pointer", "shadow-md");
                    } else {
                        regSubmitBtn.classList.add("bg-gray-300", "cursor-not-allowed");
                        regSubmitBtn.classList.remove("bg-[#00A29A]", "hover:bg-[#008f87]", "cursor-pointer", "shadow-md");
                    }
                };
                termsCheckbox.addEventListener('change', updateSubmitBtn);
                updateSubmitBtn();
            }
            const loginTermsCheckbox = document.getElementById("loginTermsCheckbox");
            const loginSubmitBtn = document.getElementById("loginSubmitBtn");            
            if (loginTermsCheckbox && loginSubmitBtn) {
                const updateLoginBtn = () => {
                    loginSubmitBtn.disabled = !loginTermsCheckbox.checked;
                    if(loginTermsCheckbox.checked) {
                        loginSubmitBtn.classList.remove("bg-[#98A4B8]", "cursor-not-allowed");
                        loginSubmitBtn.classList.add("bg-[#00A29A]", "cursor-pointer");
                    } else {
                        loginSubmitBtn.classList.remove("bg-[#00A29A]", "cursor-pointer");
                        loginSubmitBtn.classList.add("bg-[#98A4B8]", "cursor-not-allowed");
                    }
                };
                loginTermsCheckbox.addEventListener("change", updateLoginBtn);
                updateLoginBtn();
            }
            const boxes = document.querySelectorAll(".akh-box");
            const dialog = document.getElementById("akhDialog");
            const titleEl = document.getElementById("akhDialogTitle");
            const contentEl = document.getElementById("akhDialogContent");
            if(boxes.length > 0 && dialog) {
                boxes.forEach(box => {
                    box.addEventListener("mouseenter", () => {
                        if(titleEl) titleEl.textContent = box.dataset.title || "";
                        if(contentEl) contentEl.textContent = box.dataset.desc || "";
                        dialog.classList.add("dialog-show");
                    });
                    box.addEventListener("mouseleave", () => {
                        dialog.classList.remove("dialog-show");
                    });
                });
            }
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.15 });
            document.querySelectorAll('.fade-section').forEach(sec => observer.observe(sec));
            @if($errors->any())
                @if($errors->has('login'))
                    openLoginModal();
                @else
                    openRegisterModal();
                @endif
            @endif
        });
    </script>
  </body>
</html>
