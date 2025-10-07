// PTSI UI – Layout Controls (Sidebar/Dropdown/Theme/Logout Slider)
// Mobile-first, A11y, Reduced Motion aware
document.addEventListener("DOMContentLoaded", () => {

  /* ===============================
     Element Refs
  =============================== */
  const d   = document;
  const w   = window;
  const html= d.documentElement;
  const body= d.body;

  const $   = (sel, root=d) => root.querySelector(sel);
  const $$  = (sel, root=d) => Array.from(root.querySelectorAll(sel));

  const sidebar   = $("#sidebar");
  const overlay   = $("#overlay");
  const hamburger = $("#hamburgerBtn");
  const dmFab     = $("#dmFab");

  // Dropdowns
  const notifBtn        = $("#notifBtn");
  const notifDropdown   = $("#notifDropdown");
  const profileBtn      = $("#profileBtn");
  const profileDropdown = $("#profileDropdown");
  const userBtn         = $("#userBtn");
  const userDropdown    = $("#userDropdown");

  // Logout swipe
  const powerTrack = $("#poweroff");
  const powerKnob  = $("#powerKnob");
  const logoutForm = $("#logoutForm");

  /* ===============================
     Helpers
  =============================== */
  const safeStore = {
    get: (k, fallback=null) => { try { return localStorage.getItem(k) ?? fallback; } catch { return fallback; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
    del: (k)     => { try { localStorage.removeItem(k); } catch {} },
  };

  const prefersReduced = w.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;
  const mq             = w.matchMedia("(max-width:1024px)");
  const isMobile       = () => mq.matches;

  /* ===============================
     Theme (Dark/Light) via FAB
  =============================== */
  const setTheme = (t) => {
    html.setAttribute("data-theme", t);
    safeStore.set("theme", t);
    if (dmFab) {
      dmFab.textContent = t === "dark" ? "🌙" : "🌞";
      dmFab.setAttribute("aria-pressed", String(t === "dark"));
      dmFab.setAttribute("title", t === "dark" ? "Switch to light" : "Switch to dark");
    }
  };

  setTheme(safeStore.get("theme", "light"));
  dmFab?.addEventListener("click", () => {
    setTheme(html.getAttribute("data-theme") === "dark" ? "light" : "dark");
  });

  /* ===============================
     Scroll Lock (drawer/modal)
  =============================== */
  let lockState = { locked:false, scrollY:0, touchStartY:0 };

  const isScrollable = (el, dy) => {
    let n = el;
    while (n && n !== body) {
      if (n.hasAttribute("data-scroll-area")) {
        const { scrollTop, scrollHeight, clientHeight } = n;
        if (dy < 0) return scrollTop > 0;                          // up
        if (dy > 0) return scrollTop + clientHeight < scrollHeight; // down
        return true;
      }
      n = n.parentElement;
    }
    return false;
  };

  const onWheel = (e) => {
    if (lockState.locked && !isScrollable(e.target, e.deltaY)) e.preventDefault();
  };
  const onTouchStart = (e) => { if (lockState.locked) lockState.touchStartY = e.touches[0].clientY; };
  const onTouchMove  = (e) => {
    if (!lockState.locked) return;
    const dy = lockState.touchStartY - e.touches[0].clientY;
    if (!isScrollable(e.target, dy)) e.preventDefault();
  };

  const lockScroll = () => {
    if (lockState.locked) return;
    lockState.locked = true;
    lockState.scrollY = w.scrollY || 0;
    body.style.setProperty("--lock-top", `-${lockState.scrollY}px`);
    html.classList.add("scroll-lock");
    body.classList.add("scroll-lock");
    w.addEventListener("wheel", onWheel, { passive:false, capture:true });
    w.addEventListener("touchstart", onTouchStart, { passive:false, capture:true });
    w.addEventListener("touchmove", onTouchMove, { passive:false, capture:true });
  };

  const unlockScroll = () => {
    if (!lockState.locked) return;
    lockState.locked = false;
    html.classList.remove("scroll-lock");
    body.classList.remove("scroll-lock");
    body.style.removeProperty("--lock-top");
    w.removeEventListener("wheel", onWheel, { capture:true });
    w.removeEventListener("touchstart", onTouchStart, { capture:true });
    w.removeEventListener("touchmove", onTouchMove, { capture:true });
    w.scrollTo(0, lockState.scrollY);
  };

  /* ===============================
     Dropdowns
  =============================== */
  const closeAllDropdowns = () => {
    if (notifDropdown)  { notifDropdown.hidden  = true;  notifBtn?.setAttribute("aria-expanded","false"); }
    if (profileDropdown){ profileDropdown.hidden = true; profileBtn?.setAttribute("aria-expanded","false"); }
    if (userDropdown)   { userDropdown.hidden   = true;  userBtn?.setAttribute("aria-expanded","false"); }
  };

  const attachDropdown = (btn, dd) => {
    if (!btn || !dd) return;
    // Initialize ARIA
    btn.setAttribute("aria-haspopup", "menu");
    btn.setAttribute("aria-expanded", "false");
    dd.hidden = true;

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const willOpen = dd.hidden;
      closeAllDropdowns();
      dd.hidden = !willOpen;
      btn.setAttribute("aria-expanded", String(willOpen));
      if (willOpen) {
        // focus first focusable
        const first = dd.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        first?.focus({ preventScroll: true });
      }
    });
  };

  attachDropdown(notifBtn,   notifDropdown);
  attachDropdown(profileBtn, profileDropdown);
  attachDropdown(userBtn,    userDropdown);

  // Close on outside click
  d.addEventListener("click", (e) => {
    if (!e.target.closest(".dropdown-wrap")) closeAllDropdowns();
  });
  // Close on Escape
  d.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAllDropdowns();
  });

  /* ===============================
     Sidebar: Burger (Desktop collapse / Mobile drawer)
  =============================== */
  const setBurgerVisual = () => {
    if (!hamburger) return;
    hamburger.classList.remove("is-open","is-collapsed");

    if (isMobile()) {
      const open = sidebar?.classList.contains("open");
      hamburger.classList.toggle("is-open", !!open); // 3-lines → X
      hamburger.setAttribute("aria-expanded", String(!!open));
      hamburger.title = open ? "Close menu" : "Open menu";
      body.classList.remove("sidebar-collapsed");    // never collapse on mobile
    } else {
      const collapsed = body.classList.contains("sidebar-collapsed");
      hamburger.classList.toggle("is-collapsed", collapsed);
      hamburger.setAttribute("aria-expanded", String(!collapsed));
      hamburger.title = collapsed ? "Expand sidebar" : "Collapse sidebar";
      sidebar?.classList.remove("open");             // ensure drawer closed in desktop
      overlay && (overlay.hidden = true);
      unlockScroll();
    }
  };

  const openDrawer  = () => { if (!sidebar) return; sidebar.classList.add("open");  overlay && (overlay.hidden = false); lockScroll();  setBurgerVisual(); };
  const closeDrawer = () => { if (!sidebar) return; sidebar.classList.remove("open"); overlay && (overlay.hidden = true);  unlockScroll(); setBurgerVisual(); };

  // Persist desktop collapsed state (optional)
  const savedCollapsed = safeStore.get("sidebar-collapsed", "0") === "1";
  if (!isMobile() && savedCollapsed) body.classList.add("sidebar-collapsed");

  hamburger?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (isMobile()) {
      sidebar?.classList.contains("open") ? closeDrawer() : openDrawer();
    } else {
      body.classList.toggle("sidebar-collapsed");
      safeStore.set("sidebar-collapsed", body.classList.contains("sidebar-collapsed") ? "1" : "0");
      setBurgerVisual();
    }
  });

  overlay?.addEventListener("click", closeDrawer);

  // Keyboard: toggle with Enter/Space
  hamburger?.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      hamburger.click();
    }
  });

  // Breakpoint change
  mq.addEventListener?.("change", () => {
    closeDrawer();  // reset when crossing breakpoints
    setBurgerVisual();
  });

  /* ===============================
     Power-off Swipe Logout (single, improved)
  =============================== */
  const initLogoutSlider = () => {
    if (!(powerTrack && powerKnob && logoutForm)) return;

    // ARIA
    powerTrack.setAttribute("role", "slider");
    powerTrack.setAttribute("aria-label", "Slide to logout");
    powerTrack.setAttribute("aria-valuemin", "0");
    powerTrack.setAttribute("aria-valuemax", "100");
    powerTrack.setAttribute("tabindex", "0");

    let dragging = false;
    let startX = 0, knobX = 0, maxX = 0;

    const padding   = 4; // left/right inset
    const threshold = parseFloat(powerTrack.dataset.threshold || "0.85");

    const computeMax = () => {
      maxX = powerTrack.clientWidth - powerKnob.clientWidth - padding;
    };
    const clamp = (v,min,max)=>Math.max(min,Math.min(max,v));

    const setKnob = (x) => {
      // Avoid janky animation for reduced motion users
      if (prefersReduced) powerKnob.style.transition = "none";
      powerKnob.style.transform = `translateX(${x}px)`;

      const pct = Math.min(1, Math.max(0, x / maxX));
      const r1 = 220, g1 = 38, b1 = 38;
      powerTrack.style.background = `linear-gradient(90deg,
        rgba(${r1},${g1},${b1},${0.20 + .50*pct}),
        rgba(${r1},${g1},${b1},${0.35 + .55*pct})
      )`;
      powerTrack.classList.toggle("done", x >= maxX*threshold);
      powerTrack.setAttribute("aria-valuenow", String(Math.round(pct*100)));
    };

    const submitLogout = () => {
      powerTrack.classList.add("done");
      // tiny delay for UI perception
      setTimeout(() => {
        if (logoutForm.requestSubmit) logoutForm.requestSubmit();
        else logoutForm.submit();
      }, prefersReduced ? 0 : 120);
    };

    const snapBack = () => setKnob(padding);

    const onDown = (clientX) => {
      if (powerTrack.classList.contains("completed")) return;
      dragging = true;
      computeMax();
      const current = powerKnob.style.transform.match(/translateX\(([-\d.]+)px/);
      knobX = current ? parseFloat(current[1]) : padding;
      startX = clientX;
      d.addEventListener("mousemove", onMove, { passive:false });
      d.addEventListener("mouseup", onUp, { passive:false, once:true });
      d.addEventListener("touchmove", onTouchMove, { passive:false });
      d.addEventListener("touchend", onTouchEnd, { passive:false, once:true });
    };

    const onMove = (e) => {
      if (!dragging) return;
      e.preventDefault();
      const dx = e.clientX - startX;
      const x = clamp(knobX + dx, padding, maxX);
      setKnob(x);
    };
    const onUp = () => {
      if (!dragging) return;
      dragging = false;
      const current = powerKnob.style.transform.match(/translateX\(([-\d.]+)px/);
      const x = current ? parseFloat(current[1]) : padding;
      if (x >= maxX*threshold) submitLogout(); else snapBack();
      d.removeEventListener("mousemove", onMove);
      d.removeEventListener("touchmove", onTouchMove);
    };

    const onTouchStart = (e) => onDown(e.touches[0].clientX);
    const onTouchMove  = (e) => {
      if (!dragging) return;
      const dx = e.touches[0].clientX - startX;
      const x  = clamp(knobX + dx, padding, maxX);
      setKnob(x);
      e.preventDefault();
    };
    const onTouchEnd = () => onUp();

    powerKnob.addEventListener("mousedown", (e) => onDown(e.clientX));
    powerKnob.addEventListener("touchstart", onTouchStart, { passive:false });

    // Click track to jump toward position
    powerTrack.addEventListener("click", (e) => {
      if (dragging || powerTrack.classList.contains("completed")) return;
      const rect = powerTrack.getBoundingClientRect();
      computeMax();
      const targetX = clamp(e.clientX - rect.left - (powerKnob.offsetWidth/2), padding, maxX);
      setKnob(targetX);
    });

    // Keyboard accessibility
    powerTrack.addEventListener("keydown", (e) => {
      computeMax();
      const step = Math.max(8, Math.round(maxX/10));
      const current = powerKnob.style.transform.match(/translateX\(([-\d.]+)px/);
      const xNow = current ? parseFloat(current[1]) : padding;

      if (e.key === "ArrowRight") { setKnob(clamp(xNow + step, padding, maxX)); e.preventDefault(); }
      else if (e.key === "ArrowLeft"){ setKnob(clamp(xNow - step, padding, maxX)); e.preventDefault(); }
      else if (e.key === "Enter" || e.key === " "){ submitLogout(); e.preventDefault(); }
      else if (e.key === "Escape"){ snapBack(); e.preventDefault(); }
    });

    // Init position
    setKnob(padding);
  };

  initLogoutSlider();

  /* ===============================
     Init Visual States
  =============================== */
  closeAllDropdowns(); // close all dropdown at start
  overlay && (overlay.hidden = true);
  // Ensure drawer closed & burger synced
  if (isMobile()) {
    sidebar?.classList.remove("open");
    unlockScroll();
  }
  setBurgerVisual();

});
