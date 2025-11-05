document.addEventListener('DOMContentLoaded', () => {
  /* ========= utils ========= */
  const d = document, w = window, html = d.documentElement, body = d.body;
  const $  = (s, r=d) => r.querySelector(s);
  const $$ = (s, r=d) => Array.from(r.querySelectorAll(s));
  const safeStore = {
    get: (k, fb=null)=>{ try{return localStorage.getItem(k) ?? fb;}catch{ return fb; } },
    set: (k,v)=>{ try{ localStorage.setItem(k,v); }catch{} },
  };

  /* ========= iOS Liquid Glass Loader (Universal) ========= */
  (function initAppLoader(){
    // inject SVG filter (gooey) once
    const ensureDefs = ()=>{
      if ($('#gooeyDefs')) return;
      const svg = d.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('width','0'); svg.setAttribute('height','0'); svg.style.position='absolute';
      svg.innerHTML = `
        <defs id="gooeyDefs">
          <filter id="gooey">
            <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur"/>
            <feColorMatrix in="blur" mode="matrix"
              values="1 0 0 0 0
                      0 1 0 0 0
                      0 0 1 0 0
                      0 0 0 18 -7" result="goo"/>
            <feBlend in="SourceGraphic" in2="goo"/>
          </filter>
        </defs>`;
      d.body.appendChild(svg);
    };

    const ensureLoaderDOM = ()=>{
      let el = $('#appLoader');
      if (!el){
        el = d.createElement('div');
        el.id = 'appLoader';
        el.setAttribute('aria-live','polite');
        el.setAttribute('aria-busy','true');
        el.className = 'entering';
        el.innerHTML = `
          <div class="loader-card glass">
            <div class="liquid" aria-hidden="true">
              <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>
            <div class="loader-title">Loading…</div>
          </div>
        `;
        d.body.appendChild(el);
      }
      return el;
    };

    // scroll lock helpers (non-intrusive)
    const lockScroll = ()=>{
      if (body.classList.contains('scroll-lock')) return;
      const top = w.scrollY || d.documentElement.scrollTop || 0;
      html.classList.add('scroll-lock');
      body.classList.add('scroll-lock');
      body.style.setProperty('--lock-top', `-${top}px`);
    };
    const unlockScroll = ()=>{
      if (!body.classList.contains('scroll-lock')) return;
      const topVal = getComputedStyle(body).getPropertyValue('--lock-top');
      const top = parseInt(topVal || '0') || 0;
      html.classList.remove('scroll-lock');
      body.classList.remove('scroll-lock');
      body.style.removeProperty('--lock-top');
      w.scrollTo(0, -top);
    };

    ensureDefs();
    const loaderEl = ensureLoaderDOM();

    let pending = 0;
    let autoWrappedFetch = false;
    let hideTimer = null;

    const show = ()=>{
      clearTimeout(hideTimer);
      loaderEl.hidden = false;
      loaderEl.classList.remove('leaving');
      loaderEl.classList.add('entering');
      lockScroll();
      requestAnimationFrame(()=> loaderEl.classList.remove('entering'));
    };

    const hide = (delay=120)=>{
      clearTimeout(hideTimer);
      hideTimer = setTimeout(()=>{
        loaderEl.classList.add('leaving');
        setTimeout(()=>{
          loaderEl.hidden = true;
          loaderEl.classList.remove('leaving');
          unlockScroll();
        }, 260);
      }, delay);
    };

    const trackPromise = (p)=>{
      if (!p || typeof p.then !== 'function') return p;
      pending++;
      show();
      return p.finally(()=> {
        pending = Math.max(0, pending - 1);
        if (pending === 0) hide();
      });
    };

    const trackFetch = (enable=true)=>{
      if (!enable || autoWrappedFetch) return;
      if (!('fetch' in w)) return;
      const _fetch = w.fetch.bind(w);
      w.fetch = function(...args){
        pending++;
        show();
        return _fetch(...args).finally(()=>{
          pending = Math.max(0, pending - 1);
          if (pending === 0) hide();
        });
      };
      autoWrappedFetch = true;
    };

    // Expose API
    w.AppLoader = {
      show, hide, trackPromise, trackFetch,
      markReady(){ pending = 0; hide(60); }
    };

    // 1) Show at DOMContentLoaded
    show();
    // 2) Hide at window.load (fallback)
    w.addEventListener('load', ()=> w.AppLoader.markReady(), { once:true });

    // 3) Auto-hide when DataTables finishes init (if jQuery present)
    if (w.jQuery && w.jQuery.fn && w.jQuery.fn.dataTable){
      w.jQuery(d).on('init.dt', function(){ w.AppLoader.markReady(); });
    }

    // 4) Optional: enable fetch tracking
    // w.AppLoader.trackFetch(true);

    // 5) Observe data-app-ready flag
    const mo = new MutationObserver(()=> {
      if (body.dataset.appReady === '1') w.AppLoader.markReady();
    });
    mo.observe(body, { attributes:true, attributeFilter:['data-app-ready'] });
  })();

  /* ========= z-index & basic states ========= */
  (function injectTopZ(){
    const css = `
      .topbar{z-index:70!important}
      .dropdown-wrap{position:relative; z-index:95!important}
      .overlay{z-index:55!important}
      .dm-fab{z-index:9999!important}
    `;
    const s = d.createElement('style');
    s.textContent = css;
    d.head.appendChild(s);
  })();

  /* ========= refs ========= */
  const overlay   = $('#overlay');
  const sidebar   = $('#sidebar');
  const hamburger = $('#hamburgerBtn');
  const topbar    = $('#topbar');

  const dmFab     = $('#dmFab');

  const notifBtn      = $('#notifBtn');
  const notifDropdown = $('#notifDropdown');

  const userBtn      = $('#userBtn');
  const userDropdown = $('#userDropdown');

  const changePwBtn = $('#changePwBtn');
  const pwModal     = $('#changePasswordModal'); // FIX id

  const logoutForm  = $('#logoutForm');
  const powerTrack  = $('#poweroff');
  const powerKnob   = $('#powerKnob');

  /* ========= Theme FAB ========= */
  (function themeFab(){
    if (!dmFab) return;
    const KEY = 'theme';
    const apply = (t)=>{
      html.setAttribute('data-theme', t);
      html.classList.toggle('dark', t === 'dark');
      safeStore.set(KEY, t);
      dmFab.textContent = (t === 'dark') ? '🌙' : '🌞';
      dmFab.setAttribute('aria-pressed', String(t === 'dark'));
      dmFab.title = (t === 'dark') ? 'Switch to light' : 'Switch to dark';
    };
    const current = ()=>{
      const s = safeStore.get(KEY);
      if (s === 'dark' || s === 'light') return s;
      const a = html.getAttribute('data-theme');
      return (a === 'dark' || a === 'light') ? a : 'light';
    };
    apply(current());
    dmFab.addEventListener('click', ()=> apply(current()==='dark'?'light':'dark'));
  })();

  /* ========= Dropdowns (notif & user) ========= */
  (function dropdowns(){
    const attach = (btn, panel)=>{
      if (!btn || !panel) return;
      const open  = ()=>{ panel.hidden=false; btn.setAttribute('aria-expanded','true'); };
      const close = ()=>{ panel.hidden=true;  btn.setAttribute('aria-expanded','false'); };
      let justOpened=false;

      btn.addEventListener('click', (e)=>{
        e.stopPropagation();
        if (!panel.hidden){ close(); return; }
        open(); justOpened=true; setTimeout(()=>{justOpened=false;},0);
      });
      panel.addEventListener('click', e=> e.stopPropagation());

      d.addEventListener('click', ()=>{
        if (panel.hidden) return;
        if (justOpened) return;
        close();
      });
      d.addEventListener('keydown', (e)=>{ if (e.key==='Escape' && !panel.hidden) close(); });

      $$('[data-close="#'+panel.id+'"]').forEach(x=> x.addEventListener('click', close));
    };

    attach(notifBtn, notifDropdown);
    attach(userBtn,  userDropdown);
  })();

  /* ========= Change Password Modal ========= */
  (function pw(){
    if (!pwModal) return;
    const show = ()=>{ pwModal.hidden=false; pwModal.setAttribute('aria-hidden','false'); };
    const hide = ()=>{ pwModal.hidden=true;  pwModal.setAttribute('aria-hidden','true');  };
    if (changePwBtn) changePwBtn.addEventListener('click', (e)=>{ e.stopPropagation(); show(); });
    pwModal.addEventListener('click', (e)=>{
      if (e.target.matches('[data-modal-close], .modal-backdrop, .icon-btn')) hide();
    });
    d.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && !pwModal.hidden) hide(); });
    window.openPwModal  = show;
    window.closePwModal = hide;
  })();

  /* ========= Sidebar: drawer (mobile) & collapse (desktop) ========= */
  (function sidebarCtrl(){
    if (!hamburger || !sidebar) return;
    const mq = w.matchMedia('(max-width:1024px)');
    const isMobile = ()=> mq.matches;

    // seed collapse from localStorage (desktop only)
    try{
      const saved = safeStore.get('sidebar-collapsed','0')==='1';
      if (!isMobile() && saved) body.classList.add('sidebar-collapsed');
    }catch(_){}

    const openDrawer  = ()=>{ sidebar.classList.add('open');  overlay && (overlay.hidden=false);  hamburger.classList.add('is-open'); };
    const closeDrawer = ()=>{ sidebar.classList.remove('open'); overlay && (overlay.hidden=true); hamburger.classList.remove('is-open'); };

    const setBurgerVisual = ()=>{
      if (isMobile()){
        hamburger.classList.toggle('is-open', sidebar.classList.contains('open'));
        hamburger.classList.remove('is-collapsed');
        hamburger.setAttribute('aria-expanded', String(sidebar.classList.contains('open')));
      } else {
        hamburger.classList.toggle('is-collapsed', body.classList.contains('sidebar-collapsed'));
        hamburger.classList.remove('is-open');
        hamburger.setAttribute('aria-expanded', String(!body.classList.contains('sidebar-collapsed')));
        sidebar.classList.remove('open'); overlay && (overlay.hidden=true);
      }
    };

    hamburger.addEventListener('click', (e)=>{
      e.stopPropagation();
      if (isMobile()){
        sidebar.classList.contains('open') ? closeDrawer() : openDrawer();
      } else {
        body.classList.toggle('sidebar-collapsed');
        // FIX: missing quote in classList.contains argument
        safeStore.set('sidebar-collapsed', body.classList.contains('sidebar-collapsed') ? '1':'0');
      }
      setBurgerVisual();
    });

    overlay && overlay.addEventListener('click', closeDrawer);
    mq.addEventListener?.('change', ()=>{ closeDrawer(); setBurgerVisual(); });
    setBurgerVisual();
  })();

  /* ========= Topbar elevate on scroll ========= */
  (function topbarScroll(){
    if (!topbar) return;
    let last = -1;
    const onScroll = ()=>{
      const y = w.scrollY || d.documentElement.scrollTop || 0;
      if ((y>0) !== (last>0)){
        topbar.classList.toggle('is-scrolled', y>0);
        last = y;
      }
    };
    onScroll();
    d.addEventListener('scroll', onScroll, {passive:true});
  })();

  /* ========= Swipe to Sign out ========= */
  (function swipeLogout(){
    if (!(powerTrack && powerKnob && logoutForm)) return;
    const THRESH = Math.min(Math.max(parseFloat(powerTrack.dataset.threshold ?? '0.6'), 0.15), 0.95);
    let dragging=false, startX=0, progress=0;
    const pad = 6;

    const sizes = ()=>{
      const rect = powerTrack.getBoundingClientRect();
      const knobW = powerKnob.getBoundingClientRect().width || (rect.height - pad*2);
      const maxX = Math.max(0, rect.width - knobW - pad*2);
      return {maxX};
    };
    const setProgress=(p)=>{
      progress = Math.max(0, Math.min(1, p));
      const {maxX} = sizes();
      const x = progress * maxX;
      powerKnob.style.transform = `translateX(${x}px)`;
      const text = powerTrack.querySelector('.power-text');
      if (text) text.style.opacity = (1 - progress*0.85).toFixed(3);
      powerTrack.classList.toggle('completed', progress >= THRESH);
    };
    const reset=()=>{
      powerTrack.classList.remove('dragging','completed');
      powerKnob.style.transition='transform .25s ease';
      setProgress(0);
      setTimeout(()=>{ powerKnob.style.transition=''; },260);
    };
    const commit=()=>{ powerTrack.classList.add('completed'); logoutForm.submit(); };

    const onStart=(x)=>{ dragging=true; startX=x; powerTrack.classList.add('dragging'); };
    const onMove=(x)=>{
      if (!dragging) return;
      const {maxX} = sizes();
      const dx = Math.max(0, Math.min(maxX, x - startX));
      setProgress(maxX ? (dx / maxX) : 0);
    };
    const onEnd=()=>{
      if (!dragging) return;
      dragging=false; powerTrack.classList.remove('dragging');
      if (progress>=THRESH) commit(); else reset();
    };

    powerTrack.addEventListener('pointerdown', (e)=>{ if(e.button!==0) return; powerTrack.setPointerCapture?.(e.pointerId); onStart(e.clientX); });
    window.addEventListener('pointermove', (e)=> onMove(e.clientX));
    window.addEventListener('pointerup', onEnd);
    window.addEventListener('pointercancel', onEnd);

    powerTrack.addEventListener('keydown', (e)=>{
      if (e.key==='ArrowRight'){ setProgress(progress+.1); e.preventDefault(); }
      if (e.key==='ArrowLeft' ){ setProgress(progress-.1); e.preventDefault(); }
      if (e.key==='Enter' || e.key===' '){
        if (progress>=THRESH) commit(); else setProgress(1);
        e.preventDefault();
      }
    });

    reset();
    powerTrack.setAttribute('aria-valuenow','0');
    powerTrack.addEventListener('transitionend', ()=> powerTrack.setAttribute('aria-valuenow', String(Math.round(progress*100))) );
  })();

  /* ========= Sidebar accordion ========= */
  (function accordion(){
    const setOpen = (group, open)=>{
      const btn   = document.querySelector(`[data-accordion="${group}"]`);
      const panel = document.querySelector(`[data-accordion-panel="${group}"]`);
      if (!btn || !panel) return;
      btn.classList.toggle('open', open);
      panel.classList.toggle('open', open);
    };
    const groupsWithActive = new Set();
    $$('.nav-children').forEach(panel=>{
      if (panel.querySelector('.nav-child.active')){
        const g = panel.getAttribute('data-accordion-panel');
        if (g) groupsWithActive.add(g);
      }
    });
    $$('[data-accordion]').forEach(btn=>{
      const g = btn.getAttribute('data-accordion');
      const panel = document.querySelector(`[data-accordion-panel="${g}"]`);
      const should =
        btn.classList.contains('open') ||
        panel?.classList.contains('open') ||
        groupsWithActive.has(g);
      setOpen(g, !!should);
      btn.addEventListener('click', (e)=>{ e.preventDefault(); setOpen(g, !btn.classList.contains('open')); });
    });
    groupsWithActive.forEach(g=> setOpen(g,true));
  })();

  /* ========= init visuals ========= */
  (function init(){
    overlay && (overlay.hidden = true);
    const notifDropdown = $('#notifDropdown');
    const userDropdown  = $('#userDropdown');
    [notifDropdown, userDropdown].forEach(p=>{ if(p){ p.hidden=true; }});
  })();

  /* ========= SweetAlert2 (Universal iOS Glass) ========= */
  (function initSwalIOS(){
    const ensureSwal = () => new Promise((resolve, reject)=>{
      if (window.Swal) return resolve(window.Swal);
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js';
      s.async = true;
      s.onload = ()=> resolve(window.Swal);
      s.onerror = reject;
      document.head.appendChild(s);
    });

    const buildMixin = () => {
      const ios = Swal.mixin({
        customClass: {
          container: 'swal-ios-container',
          popup:     'swal-ios-popup glass',
          title:     'swal-ios-title',
          htmlContainer: 'swal-ios-html',
          confirmButton: 'swal-ios-btn swal-ios-btn--confirm',
          cancelButton:  'swal-ios-btn swal-ios-btn--cancel',
          denyButton:    'swal-ios-btn swal-ios-btn--danger',
          actions:   'swal-ios-actions',
          icon:      'swal-ios-icon',
          input:     'swal-ios-input',
          timerProgressBar: 'swal-ios-progress'
        },
        buttonsStyling: false,
        backdrop: true,
        background: 'transparent',
        showClass: { popup: 'swal2-show' },
        hideClass: { popup: 'swal2-hide' }
      });
      return ios;
    };

    const readFlashMeta = () => {
      const meta = document.querySelector('meta[name="swal"]');
      if (meta?.content) {
        try { return JSON.parse(meta.content); } catch(_){}
      }
      return null;
    };

    ensureSwal().then(()=>{
      const IOS = buildMixin();

      const api = {
        fire: (opts)=> IOS.fire(opts),
        success: (title='Berhasil', text='')=> IOS.fire({icon:'success', title, text}),
        error:   (title='Gagal', text='')   => IOS.fire({icon:'error',   title, text}),
        info:    (title='Info', text='')    => IOS.fire({icon:'info',    title, text}),
        warn:    (title='Perhatian', text='')=> IOS.fire({icon:'warning', title, text}),
        toast:   (title='Tersimpan', icon='success')=> IOS.fire({
          toast:true, position:'top-end', icon, title,
          showConfirmButton:false, timer:2500, timerProgressBar:true
        }),
        confirm: async (title='Apakah Anda yakin?', text='Tindakan ini tidak dapat dibatalkan.', confirmText='Ya, lanjut', cancelText='Batal', icon='question')=>{
          const res = await IOS.fire({
            icon, title, text, showCancelButton:true,
            confirmButtonText:confirmText, cancelButtonText:cancelText,
            reverseButtons:true, focusCancel:true
          });
          return res.isConfirmed === true;
        }
      };
      window.SwalIOS = api;

      // Auto flash jika ada payload dari server (via <meta name="swal">)
      const flash = readFlashMeta();
      if (flash && typeof flash === 'object') {
        api.fire(flash);
        try {
          const meta = document.querySelector('meta[name="swal"]');
          if (meta) meta.remove();
        } catch(_){}
      }
    }).catch(()=> {
      console.warn('SweetAlert2 gagal dimuat.');
    });
  })();
});
