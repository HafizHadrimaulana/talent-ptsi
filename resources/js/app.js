import './bootstrap';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

import $ from 'jquery';
window.$ = window.jQuery = $;

document.addEventListener('DOMContentLoaded', () => {
  // ================================
  // DataTables: Users (User Management)
  // ================================
  const usersTable = document.querySelector('#users-table');
  if (usersTable) {
    initDataTables('#users-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 2, responsivePriority: 3 },
        { targets: 1, responsivePriority: 4 },
      ],
    });

    // external search hanya di halaman User Management
    bindExternalSearch({
      searchSelector: 'input[name="q"]',
      buttonSelector: 'form [type="submit"]',
      tableSelector: '#users-table',
      delay: 250,
    });
  }

  // ================================
  // DataTables: Roles / IP / Permissions
  // ================================
  if (document.querySelector('#roles-table')) {
    initDataTables('#roles-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });
  }

  if (document.querySelector('#ip-table')) {
    initDataTables('#ip-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });
  }

  if (document.querySelector('#perms-table')) {
    initDataTables('#perms-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });
  }

  if (document.querySelector('#contracts-table')) {
    initDataTables('#contracts-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });
  }

  if (document.querySelector('#izin-table')) {
    initDataTables('#izin-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
        { targets: 2, responsivePriority: 4 },
      ],
    });
  }

  if (document.querySelector('#monitor-table')) {
    initDataTables('#monitor-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
        { targets: 2, responsivePriority: 4 },
      ],
    });
  }

  // ================================
  // Employees table + External Search
  // ================================
  if (document.querySelector('#employees-table')) {
    initDataTables('#employees-table', {
      deferRender: true,
      searchDelay: 200,
      pageLength: 25,
      columnDefs: [
        { targets: 4, visible: false, searchable: true }, // _hEmail
        { targets: 5, visible: false, searchable: true }, // _hIndex/_AllBlob
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });

    bindExternalSearch({
      searchSelector: '#empSearchInput',
      buttonSelector: '#empSearchForm [type="submit"]',
      tableSelector: '#employees-table',
      delay: 200,
    });

    const $input = document.getElementById('empSearchInput');
    document.getElementById('empSearchClear')?.addEventListener('click', () => {
      if ($input) { $input.value = ''; $input.dispatchEvent(new Event('input')); }
    });
  }

  // ================================
  // Modal Detail (iOS glass)
  // ================================
  const modal          = document.getElementById('empModal');
  const loading        = document.getElementById('empLoading');
  const btnCloseTop    = document.getElementById('empClose');
  const btnCloseBottom = document.getElementById('empCloseBottom');

  const openModal   = () => { if (modal) modal.hidden = false; };
  const closeModal  = () => { if (modal) modal.hidden = true;  };
  const showLoading = (on = true) => { if (loading) loading.style.display = on ? 'block' : 'none'; };

  modal?.querySelector('.u-modal__card')?.addEventListener('click', (e) => e.stopPropagation());

  btnCloseTop?.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
  btnCloseBottom?.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });

  modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e) => { if (!modal?.hidden && e.key === 'Escape') closeModal(); });

  // ================================
  // Tabs (click + wheel + drag-scroll)
  // ================================
  const tabsWrap = document.getElementById('iosTabs');
  const tabBtns  = tabsWrap ? Array.from(tabsWrap.querySelectorAll('.u-tab')) : [];
  const panels   = {
    ov:     document.getElementById('tab-ov'),
    brevet: document.getElementById('tab-brevet'),
    job:    document.getElementById('tab-job'),
    task:   document.getElementById('tab-task'),
    asg:    document.getElementById('tab-asg'),
    edu:    document.getElementById('tab-edu'),
    train:  document.getElementById('tab-train'),
    doc:    document.getElementById('tab-doc'),
  };

  function setTabsActive(key) {
    tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tab === key));
    Object.keys(panels).forEach(k => panels[k]?.classList.toggle('is-active', k === key));
  }

  tabsWrap?.addEventListener('click', (e) => {
    const btn = e.target.closest('.u-tab');
    if (!btn) return;
    setTabsActive(btn.dataset.tab);
  });

  tabsWrap?.addEventListener('wheel', (e) => {
    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
      tabsWrap.scrollLeft += e.deltaY;
      e.preventDefault();
    }
  }, { passive: false });

  let isDown = false, startX = 0, startScroll = 0;
  tabsWrap?.addEventListener('mousedown', (e) => {
    isDown = true;
    startX = e.pageX;
    startScroll = tabsWrap.scrollLeft;
    tabsWrap.style.cursor = 'grabbing';
  });
  window.addEventListener('mouseup', () => {
    isDown = false;
    if (tabsWrap) tabsWrap.style.cursor = '';
  });
  tabsWrap?.addEventListener('mouseleave', () => {
    isDown = false;
    if (tabsWrap) tabsWrap.style.cursor = '';
  });
  tabsWrap?.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const walk = (e.pageX - startX);
    tabsWrap.scrollLeft = startScroll - walk;
  });

  // ================================
  // Helpers (kv, fmtDate, getMeta, renderList)
  // ================================
  const kv = (label, value) => (
    `<div class="kv"><div class="k"><strong>${label || ''}</strong></div><div class="v">${(value ?? '—') || '—'}</div></div>`
  );

  const fmtDate = (x) => {
    if (!x) return '—';
    const d = new Date(x);
    if (isNaN(d)) return ('' + x).slice(0, 10);
    return d.toISOString().slice(0, 10);
  };

  const getMeta = (obj) => {
    try {
      if (!obj) return {};
      if (obj.meta && typeof obj.meta === 'string') return JSON.parse(obj.meta || '{}') || {};
      if (obj.meta && typeof obj.meta === 'object') return obj.meta || {};
    } catch (_) {}
    return {};
  };

  const renderList = (container, arr, tmpl) => {
    if (!container) return;
    container.innerHTML = '';
    if (!arr || !arr.length) {
      container.innerHTML = '<div class="empty">No data</div>';
      return;
    }
    const frag = document.createDocumentFragment();
    arr.forEach(item => {
      const card = document.createElement('div');
      card.className = 'u-card item';
      card.innerHTML = tmpl(item);
      frag.appendChild(card);
    });
    container.appendChild(frag);
  };

  // ================================
  // Detail Employee (fetch + cache)
  // ================================
  const el = {
    photo:  document.getElementById('empPhoto'),
    name:   document.getElementById('empName'),
    id:     document.getElementById('empId'),
    ovLeft: document.getElementById('ov-left'),
    ovRight:document.getElementById('ov-right'),
    brevet: document.getElementById('brevet-list'),
    jobs:   document.getElementById('job-list'),
    tasks:  document.getElementById('task-list'),
    asg:    document.getElementById('asg-list'),
    edu:    document.getElementById('edu-list'),
    train:  document.getElementById('train-list'),
    docs:   document.getElementById('doc-list'),
  };

  const cache = new Map();

  async function showEmpByUrl(url, fallback = {}) {
    if (!url) return;
    openModal();
    showLoading(true);

    if (el.ovLeft)  el.ovLeft.innerHTML = '';
    if (el.ovRight) el.ovRight.innerHTML = '';
    ['brevet','jobs','tasks','asg','edu','train','docs'].forEach(k => { if (el[k]) el[k].innerHTML = ''; });

    try {
      let data = cache.get(url);
      if (!data) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data = await res.json();
        cache.set(url, data);
      }

      const emp = data.employee || {};
      const pic = (fallback?.photo_url || emp.person_photo || '').trim();

      if (el.photo) el.photo.src = pic || '';
      if (el.name)  el.name.textContent = emp.full_name || fallback?.full_name || '—';
      if (el.id)    el.id.textContent   = emp.employee_key ? ('ID: ' + emp.employee_key) :
                            (fallback?.employee_id ? ('ID: ' + fallback.employee_id) : '—');

      if (el.ovLeft) {
        el.ovLeft.innerHTML =
          kv('Job Title',   emp.job_title || fallback?.job_title) +
          kv('Unit',        emp.unit_name || fallback?.unit_name) +
          kv('Directorate', emp.directorate_name) +
          kv('Location',    [emp.location_city, emp.location_province].filter(Boolean).join(', '));
      }

      if (el.ovRight) {
        el.ovRight.innerHTML =
          kv('Email',            emp.email || fallback?.email) +
          kv('Phone',            emp.phone || fallback?.phone) +
          kv('Talent Class',     emp.talent_class_level) +
          kv('Latest Job Start', fmtDate(emp.latest_jobs_start_date));
      }

      const jobs   = Array.isArray(data.job_histories) ? data.job_histories : [];
      const tasks  = Array.isArray(data.taskforces)    ? data.taskforces    : [];
      const asg    = Array.isArray(data.assignments)   ? data.assignments   : [];
      const edu    = Array.isArray(data.educations)    ? data.educations    : [];
      const train  = Array.isArray(data.trainings)     ? data.trainings     : [];
      const brevet = Array.isArray(data.brevet_list)   ? data.brevet_list   : [];
      const docs   = Array.isArray(data.documents)     ? data.documents     : [];

      renderList(el.jobs, jobs, j => {
        const m = getMeta(j);
        const title = j.title || m.title || 'Role';
        const unit  = j.unit_name || j.organization || m.unit_name || m.organization || '';
        const start = j.start_date || m.start_date;
        const end   = j.end_date   || m.end_date;
        return `<h4>${title}</h4><div class="muted">${unit}</div><div class="text-soft">${fmtDate(start)} — ${fmtDate(end)}</div>`;
      });

      renderList(el.tasks, tasks, t => {
        const m = getMeta(t);
        const title = t.title || m.title || 'Taskforce';
        const role  = t.role  || m.role  || '';
        const org   = t.organization || m.organization || t.unit_name || m.unit_name || '';
        const start = t.start_date || m.start_date;
        const end   = t.end_date   || m.end_date;
        return `<h4>${title}${role ? (' • ' + role) : ''}</h4><div class="muted">${org}</div><div class="text-soft">${fmtDate(start)} — ${fmtDate(end)}</div>`;
      });

      renderList(el.asg, asg, a => {
        const m = getMeta(a);
        const title = a.title || m.title || 'Assignment';
        const org   = a.organization || m.organization || a.unit_name || m.unit_name || '';
        const start = a.start_date || m.start_date;
        const end   = a.end_date   || m.end_date;
        const desc  = a.description || m.description || '';
        return `<h4>${title}</h4><div class="muted">${org}</div><div class="text-soft">${fmtDate(start)} — ${fmtDate(end)}</div>${desc ? ('<div class="mt-1">' + desc + '</div>') : ''}`;
      });

      renderList(el.edu, edu, e => {
        const m = getMeta(e);
        const school = e.organization || e.unit_name || m.organization || m.unit_name || e.title || m.title || 'Education';
        const degree = m.degree || m.level || '';
        const major  = m.major || '';
        const year   = m.year || m.graduation_year || (e.start_date ? ('' + e.start_date).slice(0, 4) : '');
        return `<h4>${school}</h4><div class="muted">${[degree, major].filter(Boolean).join(' ')}</div><div class="text-soft">Year: ${year || '—'}</div>`;
      });

      renderList(el.train, train, tr => {
        const m = getMeta(tr);
        const title = tr.title || m.title || 'Training';
        const org   = tr.organization || m.organization || '';
        const when  = tr.start_date || m.start_date || m.year ? `${fmtDate(tr.start_date || m.start_date)}${m.year ? (' • ' + m.year) : ''}` : '—';
        const extra = [m.level, m.type].filter(Boolean).join(' • ');
        return `<h4>${title}</h4><div class="muted">${org}</div><div class="text-soft">${when}${extra ? (' • ' + extra) : ''}</div>`;
      });

      renderList(el.brevet, brevet, b => {
        const m = getMeta(b);
        const title = b.title || m.title || 'Brevet';
        const org   = b.organization || m.organization || '';
        const issued= b.start_date || m.issued_at || m.start_date || null;
        const valid = b.end_date   || m.valid_until || m.end_date || null;
        const level = m.level || '';
        const no    = m.certificate_number || m.certificate_no || '';
        const meta  = [level, no ? ('#' + no) : ''].filter(Boolean).join(' • ');
        return `<h4>${title}${meta ? (' — ' + meta) : ''}</h4><div class="muted">${org}</div><div class="text-soft">Issued: ${fmtDate(issued)} • Valid: ${fmtDate(valid)}</div>`;
      });

      renderList(el.docs, docs, d => {
        const ttl = d.meta_title || d.title || '';
        const due = d.meta_due_date || d.due_date || d.end_date || '';
        const url = d.url || d.path || '';
        return `<h4>${d.doc_type || d.document_type || 'Document'}</h4><div class="muted">${ttl}</div><div class="text-soft">Due: ${fmtDate(due)} ${url ? ('• <a href="${url}" target="_blank" rel="noopener noreferrer">Open</a>') : ''}</div>`;
      });

      setTabsActive('ov');
    } catch (_err) {
      const f = fallback || {};
      if (el.ovLeft)  el.ovLeft.innerHTML  = kv('Employee ID', f.employee_id) + kv('Name', f.full_name) + kv('Unit', f.unit_name) + kv('Job Title', f.job_title);
      if (el.ovRight) el.ovRight.innerHTML = kv('Email', f.email) + kv('Phone', f.phone);
      ['brevet','jobs','tasks','asg','edu','train','docs'].forEach(k => { if (el[k]) el[k].innerHTML = '<div class="empty">No data</div>'; });
      setTabsActive('ov');
    } finally {
      showLoading(false);
    }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-show-emp]');
    if (!btn) return;
    e.preventDefault();
    const url = btn.getAttribute('data-show-url') || '';
    let fallback = {};
    try { fallback = JSON.parse(btn.getAttribute('data-emp') || '{}'); } catch (_) {}
    showEmpByUrl(url, fallback);
  });

  // ================================
  // Change Password Modal (u-modal) opener (global)
  // ================================
  window.openPwModal = function () {
    const m = document.getElementById('changePasswordModal');
    if (!m) return;
    m.hidden = false;
    document.body.classList.add('modal-open');
    const first = m.querySelector('input.u-input');
    if (first) setTimeout(() => first.focus(), 50);
  };

  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-modal-close]');
    if (!closer) return;
    const modal = closer.closest('.u-modal');
    if (modal) {
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    }
  });

  document.getElementById('changePasswordModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
      this.hidden = true;
      document.body.classList.remove('modal-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const m = document.getElementById('changePasswordModal');
      if (m && !m.hidden) {
        m.hidden = true;
        document.body.classList.remove('modal-open');
      }
    }
  });
});
