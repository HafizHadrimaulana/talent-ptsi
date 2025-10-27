// resources/js/app.js
import './bootstrap';
import { initDataTables, bindExternalSearch } from './plugins/datatables';

document.addEventListener('DOMContentLoaded', () => {
  initDataTables('#users-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 2, responsivePriority: 3 },
      { targets: 1, responsivePriority: 4 },
    ],
  });

  bindExternalSearch({
    searchSelector: 'input[name="q"]',
    buttonSelector: 'form [type="submit"]',
    tableSelector: '#users-table',
    delay: 250,
  });

  initDataTables('#roles-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 1, responsivePriority: 3 },
    ],
  });

  initDataTables('#ip-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 1, responsivePriority: 3 },
    ],
  });

  initDataTables('#perms-table', {
    columnDefs: [
      { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 140, responsivePriority: 1 },
      { targets: 0, responsivePriority: 2 },
      { targets: 1, responsivePriority: 3 },
    ],
  });

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

  if (document.querySelector('#employees-table')) {
    initDataTables('#employees-table', {
      columnDefs: [
        { targets: -1, orderable: false, searchable: false, className: 'cell-actions', width: 120, responsivePriority: 1 },
        { targets: 0, responsivePriority: 2 },
        { targets: 1, responsivePriority: 3 },
      ],
    });

    const $input = document.getElementById('empSearchInput');
    const $form = document.getElementById('empSearchForm');
    const $clear = document.getElementById('empSearchClear');
    bindExternalSearch({
      searchSelector: '#empSearchInput',
      buttonSelector: '#empSearchForm [type="submit"]',
      tableSelector: '#employees-table',
      delay: 200,
    });
    if ($clear) $clear.addEventListener('click', () => { if($input){ $input.value=''; $input.dispatchEvent(new Event('input')); } });

    const modal = document.getElementById('empModal');
    const btnClose = document.getElementById('empClose');
    const btnCloseBottom = document.getElementById('empCloseBottom');
    const tabsWrap = document.getElementById('iosTabs');
    const liquid = document.getElementById('iosLiquid');
    const tabBtns = tabsWrap ? Array.from(tabsWrap.querySelectorAll('.ios-tab')) : [];
    const panels = {
      ov: document.getElementById('tab-ov'),
      brevet: document.getElementById('tab-brevet'),
      job: document.getElementById('tab-job'),
      edu: document.getElementById('tab-edu'),
      train: document.getElementById('tab-train'),
      cert: document.getElementById('tab-cert')
    };
    const el = {
      photo: document.getElementById('empPhoto'),
      name: document.getElementById('empName'),
      id: document.getElementById('empId'),
      ovLeft: document.getElementById('ov-left'),
      ovRight: document.getElementById('ov-right'),
      brevet: document.getElementById('brevet-list'),
      jobs: document.getElementById('job-list'),
      edu: document.getElementById('edu-list'),
      train: document.getElementById('train-list'),
      cert: document.getElementById('cert-list')
    };

    function openModal(){ if(modal) modal.hidden = false; }
    function closeModal(){ if(modal) modal.hidden = true; }
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCloseBottom) btnCloseBottom.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (!modal?.hidden && e.key==='Escape') closeModal(); });

    function setTabsActive(key){
      tabBtns.forEach(b => b.classList.toggle('is-active', b.dataset.tab===key));
      Object.keys(panels).forEach(k => panels[k]?.classList.toggle('is-active', k===key));
      const active = tabBtns.find(b => b.dataset.tab===key);
      if (active && liquid){
        const r = active.getBoundingClientRect();
        liquid.style.left = (active.offsetLeft + 6) + 'px';
        liquid.style.width = (r.width - 12) + 'px';
      }
    }
    if (tabsWrap) {
      tabsWrap.addEventListener('click', e => {
        const btn = e.target.closest('.ios-tab');
        if (!btn) return;
        setTabsActive(btn.dataset.tab);
      });
    }

    function kv(label, value){
      return `<div class="kv"><div class="k">${label||''}</div><div class="v">${value||'—'}</div></div>`;
    }
    function fmtDate(x){
      if(!x) return '—';
      try{ return new Date(x).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});}catch(_){return x;}
    }
    function renderList(container, arr, mapper){
      if (!container) return;
      container.innerHTML = '';
      if (!arr || !arr.length){ container.innerHTML = '<div class="muted">No data.</div>'; return; }
      const frag = document.createDocumentFragment();
      arr.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = mapper(item);
        frag.appendChild(div);
      });
      container.appendChild(frag);
    }
    function showEmp(data){
      if (el.photo) el.photo.src = data.photo_url || 'https://via.placeholder.com/96x96.png?text=EMP';
      if (el.name) el.name.textContent = data.full_name || '—';
      if (el.id) el.id.textContent = data.employee_id ? ('ID: '+data.employee_id) : '—';
      if (el.ovLeft) el.ovLeft.innerHTML =
        kv('Employee ID', data.employee_id) +
        kv('Name', data.full_name) +
        kv('Unit', data.unit_name) +
        kv('Job Title', data.job_title);
      if (el.ovRight) el.ovRight.innerHTML =
        kv('Email', data.email) +
        kv('Phone', data.phone);

      renderList(el.brevet, data.brevets, b => {
        const grade = b.grade || b.level || '';
        const badge = grade ? (' <span class="badge">'+grade+'</span>') : '';
        return '<h4>'+(b.name || b.title || 'Brevet')+badge+'</h4>' +
               '<div class="muted">'+(b.issuer || b.institution || '')+'</div>' +
               '<div class="text-soft">'+[fmtDate(b.issued_at||b.date), b.number||b.no||''].filter(Boolean).join(' • ')+'</div>';
      });
      renderList(el.jobs, data.jobs, j => {
        const period = [fmtDate(j.start_date||j.start), fmtDate(j.end_date||j.end)].filter(Boolean).join(' – ');
        return '<h4>'+(j.title || j.job_title || 'Role')+'</h4>' +
               '<div class="muted">'+(j.unit || j.unit_name || j.department || '')+'</div>' +
               '<div class="text-soft">'+period+'</div>';
      });
      renderList(el.edu, data.educations, e => {
        const yr = e.year || (e.end_year || e.start_year) || '';
        return '<h4>'+(e.degree || e.level || 'Education')+'</h4>' +
               '<div class="muted">'+(e.major || e.field || '')+'</div>' +
               '<div class="text-soft">'+[(e.institution||e.school||''), yr].filter(Boolean).join(' • ')+'</div>';
      });
      renderList(el.train, data.trainings, t => {
        const when = fmtDate(t.date||t.held_at||t.completed_at);
        return '<h4>'+(t.title || 'Training')+'</h4>' +
               '<div class="muted">'+(t.provider || t.organizer || '')+'</div>' +
               '<div class="text-soft">'+when+'</div>';
      });
      renderList(el.cert, data.certs, c => {
        const when = [fmtDate(c.issued_at||c.date), fmtDate(c.expires_at||c.expiry)].filter(Boolean).join(' → ');
        const badge = c.status ? (' <span class="badge">'+c.status+'</span>') : '';
        return '<h4>'+(c.name || c.title || 'Certificate')+badge+'</h4>' +
               '<div class="muted">'+(c.issuer || c.body || '')+'</div>' +
               '<div class="text-soft">'+when+'</div>';
      });

      setTabsActive('ov');
      openModal();
    }

    document.addEventListener('click', function(e){
      const btn = e.target.closest('[data-show-emp][data-emp]');
      if (!btn) return;
      try{
        const payload = JSON.parse(btn.getAttribute('data-emp'));
        showEmp(payload || {});
      }catch(_){}
    });
  }
});
