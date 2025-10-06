document.addEventListener("DOMContentLoaded", () => {
  /* ====== Element refs ====== */
  const html = document.documentElement;
  const body = document.body;

  const sidebar   = document.getElementById("sidebar");
  const overlay   = document.getElementById("overlay");
  const hamburger = document.getElementById("hamburgerBtn");
  const dmFab     = document.getElementById("dmFab");

  // Dropdowns
  const notifBtn       = document.getElementById("notifBtn");
  const notifDropdown  = document.getElementById("notifDropdown");
  const profileBtn     = document.getElementById("profileBtn");
  const profileDropdown= document.getElementById("profileDropdown");
  const userBtn        = document.getElementById("userBtn");
  const userDropdown   = document.getElementById("userDropdown");

  // Logout swipe
  const powerTrack = document.getElementById("poweroff");
  const powerKnob  = document.getElementById("powerKnob");
  const logoutForm = document.getElementById("logoutForm");

  /* ====== Theme (Dark/Light) FAB ====== */
  const setTheme = (t) => {
    html.setAttribute("data-theme", t);
    localStorage.setItem("theme", t);
    if (dmFab) {
      dmFab.textContent = t === "dark" ? "🌙" : "🌞";
      dmFab.setAttribute("aria-pressed", String(t === "dark"));
    }
  };
  setTheme(localStorage.getItem("theme") || "light");
  dmFab?.addEventListener("click", () => {
    const next = html.getAttribute("data-theme") === "dark" ? "light" : "dark";
    setTheme(next);
  });

  /* ====== Scroll Lock (untuk drawer/modal) ====== */
  let locked = false, scrollY = 0, touchStartY = 0;
  const isScrollable = (el, dy) => {
    let n = el;
    while (n && n !== document.body) {
      if (n.hasAttribute("data-scroll-area")) {
        const { scrollTop, scrollHeight, clientHeight } = n;
        if (dy < 0) return scrollTop > 0;                      // scroll ke atas
        if (dy > 0) return scrollTop + clientHeight < scrollHeight; // ke bawah
        return true;
      }
      n = n.parentElement;
    }
    return false;
  };
  const onWheel = (e) => { if (locked && !isScrollable(e.target, e.deltaY)) e.preventDefault(); };
  const onTouchStart = (e) => { if (locked) touchStartY = e.touches[0].clientY; };
  const onTouchMove = (e) => {
    if (!locked) return;
    const dy = touchStartY - e.touches[0].clientY;
    if (!isScrollable(e.target, dy)) e.preventDefault();
  };
  const lockScroll = () => {
    if (locked) return;
    locked = true;
    scrollY = window.scrollY || 0;
    body.style.setProperty("--lock-top", `-${scrollY}px`);
    html.classList.add("scroll-lock"); body.classList.add("scroll-lock");
    window.addEventListener("wheel", onWheel, { passive:false, capture:true });
    window.addEventListener("touchstart", onTouchStart, { passive:false, capture:true });
    window.addEventListener("touchmove", onTouchMove, { passive:false, capture:true });
  };
  const unlockScroll = () => {
    if (!locked) return;
    locked = false;
    html.classList.remove("scroll-lock"); body.classList.remove("scroll-lock");
    body.style.removeProperty("--lock-top");
    window.removeEventListener("wheel", onWheel, { capture:true });
    window.removeEventListener("touchstart", onTouchStart, { capture:true });
    window.removeEventListener("touchmove", onTouchMove, { capture:true });
    window.scrollTo(0, scrollY);
  };

  /* ====== Breakpoint helper ====== */
  const mq = window.matchMedia("(max-width:1024px)");
  const isMobile = () => mq.matches;

  /* ====== Dropdown helpers (notif/profile/user) ====== */
  const closeAllDropdowns = () => {
    if (notifDropdown)  { notifDropdown.hidden = true;  notifBtn?.setAttribute("aria-expanded","false"); }
    if (profileDropdown){ profileDropdown.hidden = true; profileBtn?.setAttribute("aria-expanded","false"); }
    if (userDropdown)   { userDropdown.hidden = true;   userBtn?.setAttribute("aria-expanded","false"); }
  };
  notifBtn?.addEventListener("click",(e)=>{
    e.stopPropagation();
    const willOpen = notifDropdown?.hidden ?? false;
    closeAllDropdowns();
    if (notifDropdown){ notifDropdown.hidden = !willOpen; }
    notifBtn?.setAttribute("aria-expanded", String(willOpen));
  });
  profileBtn?.addEventListener("click",(e)=>{
    e.stopPropagation();
    const willOpen = profileDropdown?.hidden ?? false;
    closeAllDropdowns();
    if (profileDropdown){ profileDropdown.hidden = !willOpen; }
    profileBtn?.setAttribute("aria-expanded", String(willOpen));
  });
  userBtn?.addEventListener("click",(e)=>{
    e.stopPropagation();
    const willOpen = userDropdown?.hidden ?? false;
    closeAllDropdowns();
    if (userDropdown){ userDropdown.hidden = !willOpen; }
    userBtn?.setAttribute("aria-expanded", String(willOpen));
  });
  document.addEventListener("click",(e)=>{
    if (!e.target.closest(".dropdown-wrap")) closeAllDropdowns();
  });

  /* ====== Burger (Desktop collapse + Mobile drawer) ====== */
  const setBurgerVisual = () => {
    hamburger?.classList.remove("is-open","is-collapsed");
    if (isMobile()) {
      const open = sidebar?.classList.contains("open");
      hamburger?.classList.toggle("is-open", open);               // 3 garis -> X saat drawer open
      hamburger?.setAttribute("aria-expanded", String(open));
      hamburger && (hamburger.title = open ? "Close menu" : "Open menu");
      // Di mobile: jangan bawa state collapsed (supaya label tetap terlihat)
      body.classList.remove("sidebar-collapsed");
    } else {
      const collapsed = body.classList.contains("sidebar-collapsed");
      hamburger?.classList.toggle("is-collapsed", collapsed);
      hamburger?.setAttribute("aria-expanded", String(!collapsed));
      hamburger && (hamburger.title = collapsed ? "Expand sidebar" : "Collapse sidebar");
      // Pastikan drawer tertutup saat balik ke desktop
      sidebar?.classList.remove("open");
      overlay && (overlay.hidden = true);
      unlockScroll();
    }
  };

  const openDrawer  = () => { sidebar?.classList.add("open");  overlay && (overlay.hidden = false); lockScroll(); setBurgerVisual(); };
  const closeDrawer = () => { sidebar?.classList.remove("open"); overlay && (overlay.hidden = true); unlockScroll(); setBurgerVisual(); };

  hamburger?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (isMobile()) {
      sidebar?.classList.contains("open") ? closeDrawer() : openDrawer();
    } else {
      body.classList.toggle("sidebar-collapsed");  // desktop collapse/expand
      setBurgerVisual();
    }
  });
  overlay?.addEventListener("click", closeDrawer);

  mq.addEventListener?.("change", () => {
    // Reset state saat melintasi breakpoint
    closeDrawer();
    setBurgerVisual();
  });

  /* ====== Power-off Swipe Logout ====== */
  if (powerTrack && powerKnob && logoutForm) {
    let dragging = false;
    let startX = 0, knobX = 0;
    const padding = 4; // padding kiri/kanan track
    const minX = padding;
    const maxX = () => powerTrack.clientWidth - powerKnob.clientWidth - padding;
    const threshold = () => maxX() * 0.85; // 85% untuk konfirmasi

    const setKnob = (x) => {
      powerKnob.style.transform = `translateX(${x}px)`;
      // progress tint
      const pct = Math.min(1, Math.max(0, x / maxX()));
      const r1 = 220, g1 = 38,  b1 = 38;
      powerTrack.style.background = `linear-gradient(90deg,
        rgba(${r1},${g1},${b1},${0.20 + .50*pct}),
        rgba(${r1},${g1},${b1},${0.35 + .55*pct})
      )`;
      if (x >= threshold()) powerTrack.classList.add("done"); else powerTrack.classList.remove("done");
    };

    const onDown = (clientX) => {
      dragging = true;
      const current = powerKnob.style.transform.match(/translateX\(([-\d.]+)px/);
      knobX = current ? parseFloat(current[1]) : 0;
      startX = clientX;
      document.addEventListener("mousemove", onMove, {passive:false});
      document.addEventListener("mouseup", onUp, {passive:false, once:true});
      document.addEventListener("touchmove", onTouchMove, {passive:false});
      document.addEventListener("touchend", onTouchEnd, {passive:false, once:true});
    };

    const onMove = (e) => {
      if (!dragging) return;
      e.preventDefault();
      const dx = e.clientX - startX;
      const x = Math.max(minX, Math.min(maxX(), knobX + dx));
      setKnob(x);
    };

    const onUp = () => {
      dragging = false;
      const current = powerKnob.style.transform.match(/translateX\(([-\d.]+)px/);
      const x = current ? parseFloat(current[1]) : 0;
      if (x >= threshold()) {
        powerTrack.classList.add("done");
        // submit logout
        logoutForm.requestSubmit ? logoutForm.requestSubmit() : logoutForm.submit();
      } else {
        // snap back
        setKnob(minX);
      }
      document.removeEventListener("mousemove", onMove, {passive:false});
    };

    const onTouchStart = (e) => onDown(e.touches[0].clientX);
    const onTouchMove  = (e) => {
      if (!dragging) return;
      const dx = e.touches[0].clientX - startX;
      const x = Math.max(minX, Math.min(maxX(), knobX + dx));
      setKnob(x);
    };
    const onTouchEnd = () => onUp();

    powerKnob.addEventListener("mousedown", e => onDown(e.clientX));
    powerKnob.addEventListener("touchstart", onTouchStart, {passive:false});
    // init position
    setKnob(minX);
  }

  /* ====== Init ====== */
  // Tutup semua dropdown pas awal
  closeAllDropdowns();
  // Pastikan drawer tertutup & burger state sinkron
  closeDrawer();
  setBurgerVisual();
});
