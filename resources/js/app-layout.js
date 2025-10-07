document.addEventListener("DOMContentLoaded", () => {
  /* ===============================
     Element Refs
  =============================== */
  const html = document.documentElement;
  const body = document.body;

  const sidebar   = document.getElementById("sidebar");
  const overlay   = document.getElementById("overlay");
  const hamburger = document.getElementById("hamburgerBtn");
  const dmFab     = document.getElementById("dmFab");

  // Dropdowns
  const notifBtn        = document.getElementById("notifBtn");
  const notifDropdown   = document.getElementById("notifDropdown");
  const profileBtn      = document.getElementById("profileBtn");      // optional
  const profileDropdown = document.getElementById("profileDropdown"); // optional
  const userBtn         = document.getElementById("userBtn");
  const userDropdown    = document.getElementById("userDropdown");

  // Logout swipe
  const powerTrack = document.getElementById("poweroff");
  const powerKnob  = document.getElementById("powerKnob");
  const logoutForm = document.getElementById("logoutForm");

  /* ===============================
     Theme (Dark/Light) via FAB
  =============================== */
  const setTheme = (t) => {
    html.setAttribute("data-theme", t);
    try { localStorage.setItem("theme", t); } catch {}
    if (dmFab) {
      dmFab.textContent = t === "dark" ? "🌙" : "🌞";
      dmFab.setAttribute("aria-pressed", String(t === "dark"));
    }
  };
  setTheme((() => {
    try { return localStorage.getItem("theme") || "light"; } catch { return "light"; }
  })());
  dmFab?.addEventListener("click", () => {
    const next = html.getAttribute("data-theme") === "dark" ? "light" : "dark";
    setTheme(next);
  });

  /* ===============================
     Scroll Lock (untuk drawer/modal)
  =============================== */
  let locked = false, scrollY = 0, touchStartY = 0;

  const isScrollable = (el, dy) => {
    let n = el;
    while (n && n !== document.body) {
      if (n.hasAttribute("data-scroll-area")) {
        const { scrollTop, scrollHeight, clientHeight } = n;
        if (dy < 0) return scrollTop > 0;                                // ke atas
        if (dy > 0) return scrollTop + clientHeight < scrollHeight;       // ke bawah
        return true;
      }
      n = n.parentElement;
    }
    return false;
  };

  const onWheel = (e) => {
    if (locked && !isScrollable(e.target, e.deltaY)) e.preventDefault();
  };
  const onTouchStart = (e) => { if (locked) touchStartY = e.touches[0].clientY; };
  const onTouchMove  = (e) => {
    if (!locked) return;
    const dy = touchStartY - e.touches[0].clientY;
    if (!isScrollable(e.target, dy)) e.preventDefault();
  };

  const lockScroll = () => {
    if (locked) return;
    locked = true;
    scrollY = window.scrollY || 0;
    body.style.setProperty("--lock-top", `-${scrollY}px`);
    html.classList.add("scroll-lock");
    body.classList.add("scroll-lock");
    window.addEventListener("wheel", onWheel, { passive:false, capture:true });
    window.addEventListener("touchstart", onTouchStart, { passive:false, capture:true });
    window.addEventListener("touchmove", onTouchMove, { passive:false, capture:true });
  };

  const unlockScroll = () => {
    if (!locked) return;
    locked = false;
    html.classList.remove("scroll-lock");
    body.classList.remove("scroll-lock");
    body.style.removeProperty("--lock-top");
    window.removeEventListener("wheel", onWheel, { capture:true });
    window.removeEventListener("touchstart", onTouchStart, { capture:true });
    window.removeEventListener("touchmove", onTouchMove, { capture:true });
    window.scrollTo(0, scrollY);
  };

  /* ===============================
     Breakpoint helper
  =============================== */
  const mq = window.matchMedia("(max-width:1024px)");
  const isMobile = () => mq.matches;

  /* ===============================
     Dropdown helpers
  =============================== */
  const closeAllDropdowns = () => {
    if (notifDropdown)  { notifDropdown.hidden  = true;  notifBtn?.setAttribute("aria-expanded","false"); }
    if (profileDropdown){ profileDropdown.hidden = true; profileBtn?.setAttribute("aria-expanded","false"); }
    if (userDropdown)   { userDropdown.hidden   = true;  userBtn?.setAttribute("aria-expanded","false"); }
  };

  const attachDropdown = (btn, dd) => {
    if (!btn || !dd) return;
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const willOpen = dd.hidden;
      closeAllDropdowns();
      dd.hidden = !willOpen;
      btn.setAttribute("aria-expanded", String(willOpen));
    });
  };

  attachDropdown(notifBtn,   notifDropdown);
  attachDropdown(profileBtn, profileDropdown); // optional
  attachDropdown(userBtn,    userDropdown);

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".dropdown-wrap")) closeAllDropdowns();
  });

  /* ===============================
     Burger (Desktop collapse + Mobile drawer)
  =============================== */
  const setBurgerVisual = () => {
    hamburger?.classList.remove("is-open","is-collapsed");
    if (isMobile()) {
      const open = sidebar?.classList.contains("open");
      hamburger?.classList.toggle("is-open", open); // 3 garis -> X saat drawer open
      hamburger?.setAttribute("aria-expanded", String(Boolean(open)));
      if (hamburger) hamburger.title = open ? "Close menu" : "Open menu";
      body.classList.remove("sidebar-collapsed"); // di mobile: jangan collapse
    } else {
      const collapsed = body.classList.contains("sidebar-collapsed");
      hamburger?.classList.toggle("is-collapsed", collapsed);
      hamburger?.setAttribute("aria-expanded", String(!collapsed));
      if (hamburger) hamburger.title = collapsed ? "Expand sidebar" : "Collapse sidebar";
      sidebar?.classList.remove("open"); // pastikan drawer tertutup di desktop
      overlay && (overlay.hidden = true);
      unlockScroll();
    }
  };

  const openDrawer  = () => { sidebar?.classList.add("open");  overlay && (overlay.hidden = false); lockScroll();  setBurgerVisual(); };
  const closeDrawer = () => { sidebar?.classList.remove("open"); overlay && (overlay.hidden = true);  unlockScroll(); setBurgerVisual(); };

  hamburger?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (isMobile()) {
      sidebar?.classList.contains("open") ? closeDrawer() : openDrawer();
    } else {
      body.classList.toggle("sidebar-collapsed"); // desktop collapse/expand
      setBurgerVisual();
    }
  });

  overlay?.addEventListener("click", closeDrawer);

  mq.addEventListener?.("change", () => {
    closeDrawer();         // reset state saat lintas breakpoint
    setBurgerVisual();
  });

  /* ===============================
     Power-off Swipe Logout
  =============================== */
  if (powerTrack && powerKnob && logoutForm) {
    let dragging = false;
    let startX = 0, knobX = 0;
    const padding = 4; // padding kiri/kanan track
    const minX = padding;
    const maxX       = () => powerTrack.clientWidth - powerKnob.clientWidth - padding;
    const threshold  = () => maxX() * 0.85; // 85% untuk konfirmasi

    const setKnob = (x) => {
      powerKnob.style.transform = `translateX(${x}px)`;
      // progress tint
      const pct = Math.min(1, Math.max(0, x / maxX()));
      const r1 = 220, g1 = 38, b1 = 38;
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
      document.addEventListener("mousemove", onMove, { passive:false });
      document.addEventListener("mouseup", onUp, { passive:false, once:true });
      document.addEventListener("touchmove", onTouchMove, { passive:false });
      document.addEventListener("touchend", onTouchEnd, { passive:false, once:true });
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
        logoutForm.requestSubmit ? logoutForm.requestSubmit() : logoutForm.submit();
      } else {
        setKnob(minX); // snap back
      }
      document.removeEventListener("mousemove", onMove);
      document.removeEventListener("touchmove", onTouchMove);
    };

    const onTouchStart = (e) => onDown(e.touches[0].clientX);
    const onTouchMove  = (e) => {
      if (!dragging) return;
      const dx = e.touches[0].clientX - startX;
      const x  = Math.max(minX, Math.min(maxX(), knobX + dx));
      setKnob(x);
    };
    const onTouchEnd = () => onUp();

    powerKnob.addEventListener("mousedown", (e) => onDown(e.clientX));
    powerKnob.addEventListener("touchstart", onTouchStart, { passive:false });

    // init
    setKnob(minX);
  }

  /* ===============================
     Init visual states
  =============================== */
  closeAllDropdowns(); // tutup semua dropdown di awal
  closeDrawer();       // drawer tertutup + unlock scroll
  setBurgerVisual();   // sinkron tombol burger
});

document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('poweroff');
  const knob  = document.getElementById('powerKnob');
  const form  = document.getElementById('logoutForm');
  if(!track || !knob || !form) return;

  let dragging = false, startX = 0, startLeft = 0, maxLeft = 0;

  const THRESHOLD = parseFloat(track.dataset.threshold || '0.6'); // 60%
  const px = () => {
    const rect = track.getBoundingClientRect();
    const knobRect = knob.getBoundingClientRect();
    // ruang bergerak = lebar track - (knob + margin)
    maxLeft = rect.width - knobRect.width - 8; // 4px inset kiri + 4px kanan
  };
  const clamp = (v,min,max)=>Math.max(min,Math.min(max,v));
  const setLeft = (leftPx) => {
    knob.style.left = `${leftPx + 4}px`; // 4px inset kiri
    const progress = leftPx / maxLeft;
    track.style.background = `linear-gradient(90deg,
      rgba(76,175,80,.35) 0%,
      rgba(76,175,80,.35) ${progress*100}%,
      rgba(255,255,255,.06) ${progress*100}%,
      rgba(255,255,255,.06) 100%)`;
    track.setAttribute('aria-valuenow', Math.round(progress*100));
  };
  const complete = () => {
    track.classList.add('completed');
    knob.style.left = `${maxLeft + 4}px`;
    // submit dengan sedikit jeda biar UI terasa responsive
    setTimeout(() => form.submit(), 150);
  };
  const reset = () => {
    track.classList.remove('completed','dragging');
    setLeft(0);
  };

  const onDown = (clientX) => {
    if(track.classList.contains('completed')) return;
    dragging = true; track.classList.add('dragging');
    px();
    const knobRect = knob.getBoundingClientRect();
    startX = clientX; startLeft = knobRect.left - track.getBoundingClientRect().left - 4;
  };
  const onMove = (clientX) => {
    if(!dragging) return;
    const dx = clientX - startX;
    const next = clamp(startLeft + dx, 0, maxLeft);
    setLeft(next);
  };
  const onUp = () => {
    if(!dragging) return;
    dragging = false; track.classList.remove('dragging');
    const currentLeft = parseFloat((knob.style.left||'4px').replace('px','')) - 4;
    const progress = currentLeft / maxLeft;
    if(progress >= THRESHOLD) complete(); else reset();
  };

  // mouse
  knob.addEventListener('mousedown', e => { e.preventDefault(); onDown(e.clientX); });
  window.addEventListener('mousemove', e => onMove(e.clientX), { passive:true });
  window.addEventListener('mouseup', onUp);

  // touch
  knob.addEventListener('touchstart', e => { onDown(e.touches[0].clientX); }, { passive:true });
  window.addEventListener('touchmove', e => onMove(e.touches[0].clientX), { passive:false });
  window.addEventListener('touchend', onUp);

  // klik pada track untuk “lompat” mendekati posisi
  track.addEventListener('click', e => {
    if(dragging || track.classList.contains('completed')) return;
    px();
    const left = clamp(e.clientX - track.getBoundingClientRect().left - (knob.offsetWidth/2) - 4, 0, maxLeft);
    setLeft(left);
  });

  // keyboard accessibility
  track.addEventListener('keydown', e => {
    if(track.classList.contains('completed')) return;
    px();
    const step = Math.max(8, Math.round(maxLeft/10));
    const currentLeft = parseFloat((knob.style.left||'4px').replace('px','')) - 4;
    if(e.key === 'ArrowRight'){
      setLeft(clamp(currentLeft + step, 0, maxLeft)); e.preventDefault();
    }else if(e.key === 'ArrowLeft'){
      setLeft(clamp(currentLeft - step, 0, maxLeft)); e.preventDefault();
    }else if(e.key === 'Enter' || e.key === ' '){
      // langsung selesaikan
      complete(); e.preventDefault();
    }else if(e.key === 'Escape'){
      reset(); e.preventDefault();
    }
  });

  // init
  reset();
});
