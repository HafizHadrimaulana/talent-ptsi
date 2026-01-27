// Utility functions
export const select = (sel, parent = document) => parent.querySelector(sel);
export const selectAll = (sel, parent = document) => [...parent.querySelectorAll(sel)];
export const hide = el => { if(el){ el.hidden = true; el.style.display = 'none'; el.classList.add('is-hidden'); } };
export const show = el => { if(el){ el.hidden = false; el.style.display = 'flex'; el.classList.remove('is-hidden'); } };
export const showBlock = el => { if(el){ el.hidden = false; el.style.display = 'block'; el.classList.remove('is-hidden'); } };
export const money = n => (!n || n == 0) ? '-' : n.toString().replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
export const safeJSON = (v) => { 
    if (!v) return {};
    if (typeof v === 'object' && v !== null) return v;
    if (typeof v !== 'string') return {};
    
    try {
        // Clean string sebelum parse
        const cleaned = v.trim();
        if (!cleaned || cleaned === 'null' || cleaned === 'undefined') return {};
        
        const parsed = JSON.parse(cleaned);
        return (parsed && typeof parsed === 'object') ? parsed : {};
    } catch(e) {
        console.warn('JSON parse failed, returning empty object:', e.message);
        return {};
    }
};
export const terbilang = (n) => {
    const h = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    n = Math.abs(parseInt(n)) || 0;
    if(n<12) return h[n];
    if(n<20) return terbilang(n-10)+' belas';
    if(n<100) return terbilang(Math.floor(n/10))+' puluh '+terbilang(n%10);
    if(n<200) return 'seratus '+terbilang(n-100);
    if(n<1000) return terbilang(Math.floor(n/100))+' ratus '+terbilang(n%100);
    if(n<2000) return 'seribu '+terbilang(n-1000);
    if(n<1000000) return terbilang(Math.floor(n/1000))+' ribu '+terbilang(n%1000);
    return terbilang(Math.floor(n/1000000))+' juta '+terbilang(n%1000000);
};
export const addDays = (dateStr, days) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    d.setDate(d.getDate() + parseInt(days));
    return d.toISOString().split('T')[0];
};

export const bindCalc = (root) => {
    selectAll('input[data-rupiah="true"]', root).forEach(el => {
        const tgtId = el.dataset.terbilangTarget;
        el.addEventListener('input', () => {
            let v = el.value.replace(/\D/g,'');
            el.value = v ? money(v) : '';
            const tgt = tgtId ? (select(`[name="${tgtId}"]`, root) || select(`#${tgtId}`, root)) : null;
            if(tgt) tgt.value = (v && v != '0') ? (terbilang(v) + ' rupiah').toUpperCase() : '';
        });
    });
};

export const handleLocationAutofill = () => {
    const selects = selectAll('.js-location-autofill');
    selects.forEach(sel => {
        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            if (!opt) return;
            const cat = (opt.dataset.category || '').toUpperCase();
            const name = (opt.dataset.name || '').trim();
            let locValue = '';
            if (cat === 'ENABLER' || cat === 'OPERASI') locValue = 'Jakarta';
            else if (cat === 'CABANG') locValue = name.replace(/^Cabang\s+/i, '');
            const form = sel.closest('form');
            const locInput = form ? form.querySelector('input[name="work_location"]') : null;
            if (locInput && locValue) locInput.value = locValue;
        });
    });
    const hidCat = select('#createUnitCategoryHidden');
    const hidName = select('#createUnitNameHidden');
    if(hidCat && hidName) {
        const cat = (hidCat.value || '').toUpperCase();
        const name = (hidName.value || '').trim();
        let locValue = '';
        if (cat === 'ENABLER' || cat === 'OPERASI') locValue = 'Jakarta';
        else if (cat === 'CABANG') locValue = name.replace(/^Cabang\s+/i, '');
        const locInput = select('#createLocation');
        if(locInput && locValue && !locInput.value) locInput.value = locValue;
    }
};
