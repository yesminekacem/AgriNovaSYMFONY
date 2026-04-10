// Client-side controls for admin users table: search, filters, sort, PDF export
(function(){
    // Helper to select
    const $ = (s, el=document) => el.querySelector(s);
    const $$ = (s, el=document) => Array.from(el.querySelectorAll(s));

    const searchInput = $('#user-search');
    const filterRole = $('#filter-role');
    const filterBlocked = $('#filter-blocked');
    const resetBtn = $('#reset-filters');
    const exportBtn = $('#export-pdf');
    const tbody = $('#users-tbody');
    const rows = () => tbody ? $$('#users-tbody tr') : [];

    function normalize(s){ return (s||'').toString().trim().toLowerCase(); }

    function applyFilters(){
        if(!tbody) return;
        const q = normalize(searchInput && searchInput.value);
        const role = filterRole ? filterRole.value : '';
        const blocked = filterBlocked ? filterBlocked.value : '';

        rows().forEach(tr => {
            const email = tr.dataset.email || '';
            const fullname = tr.dataset.fullname || '';
            const r = tr.dataset.role || '';
            const isBlocked = tr.dataset.blocked === '1';

            // search matches either email or full name or id
            const id = (tr.querySelector('td') && tr.querySelector('td').textContent) || '';
            const matchesSearch = !q || (email.indexOf(q)!==-1) || (fullname.indexOf(q)!==-1) || (id.indexOf(q)!==-1);
            const matchesRole = !role || r === role;
            const matchesBlocked = !blocked || (blocked === 'blocked' ? isBlocked : !isBlocked);

            tr.style.display = (matchesSearch && matchesRole && matchesBlocked) ? '' : 'none';
        });
    }

    function resetFilters(){
        if(searchInput) searchInput.value = '';
        if(filterRole) filterRole.value = '';
        if(filterBlocked) filterBlocked.value = '';
        applyFilters();
    }

    // Sorting
    let sortState = { key: null, dir: 1 };
    function sortTable(key){
        if(!tbody || !key) return;
        const tb = tbody;
        const trs = rows().slice();
        if(sortState.key === key) sortState.dir = -sortState.dir; else { sortState.key = key; sortState.dir = 1; }

        const getCellValue = (tr) => {
            switch(key){
                case 'id': return parseInt((tr.querySelector('td')?.textContent || '0').trim(),10) || 0;
                case 'fullName': return normalize(tr.dataset.fullname || tr.cells[2]?.textContent || '');
                case 'email': return normalize(tr.dataset.email || tr.cells[3]?.textContent || '');
                case 'role': return normalize(tr.dataset.role || tr.cells[4]?.textContent || '');
                case 'verified': return normalize(tr.dataset.verified || tr.cells[5]?.textContent || '');
                case 'blocked': return normalize(tr.dataset.blocked || tr.cells[6]?.textContent || '');
                default: return '';
            }
        };

        trs.sort((a,b)=>{
            const va = getCellValue(a);
            const vb = getCellValue(b);
            if (va === vb) return 0;
            if (va == null) return sortState.dir;
            if (vb == null) return -sortState.dir;
            return va > vb ? sortState.dir : -sortState.dir;
        });

        // append back in order
        trs.forEach(tr => tb.appendChild(tr));
        // update header indicators
        $$('.sortable').forEach(h=>{
            h.classList.remove('sort-asc','sort-desc');
            if(h.dataset.sortKey === sortState.key) h.classList.add(sortState.dir === 1 ? 'sort-asc' : 'sort-desc');
        });
    }

    // Attach header click listeners
    document.querySelectorAll('th.sortable').forEach(h => {
        h.style.cursor = 'pointer';
        h.setAttribute('role','button');
        h.addEventListener('click', ()=> sortTable(h.dataset.sortKey));
        h.addEventListener('keydown', (ev)=>{ if(ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); sortTable(h.dataset.sortKey); } });
    });

    // Export to PDF using jsPDF
    async function exportToPdf(){
        // ensure jsPDF is available
        const { jsPDF } = window.jspdf || {};
        if(!jsPDF){
            alert('PDF export library not loaded.');
            return;
        }

        const doc = new jsPDF('l', 'pt', 'a4');
        const padding = 40;
        const startY = 40;
        const cellPadding = 6;

        // table headers
        const headers = ['ID','Full name','Email','Role','Verified','Blocked'];
        const visibleRows = rows().filter(r=> r.style.display !== 'none');

        // Compose simple text table
        const colWidths = [40, 200, 260, 80, 60, 60];
        let x = padding;
        let y = startY;

        doc.setFontSize(14);
        doc.text('Users (filtered)', x, y);
        y += 20;

        doc.setFontSize(10);
        // header
        x = padding;
        headers.forEach((h, i)=>{
            doc.text(h, x + cellPadding, y);
            x += colWidths[i];
        });
        y += 16;

        // rows
        visibleRows.forEach(tr=>{
            x = padding;
            const id = tr.querySelector('td')?.textContent?.trim() || '';
            const full = tr.cells[2]?.textContent?.trim() || '';
            const email = tr.cells[3]?.textContent?.trim() || '';
            const role = tr.cells[4]?.textContent?.trim() || '';
            const verified = tr.cells[5]?.textContent?.trim() || '';
            const blocked = tr.cells[6]?.textContent?.trim() || '';
            const rowVals = [id, full, email, role, verified, blocked];
            rowVals.forEach((val,i)=>{
                const lines = doc.splitTextToSize(val, colWidths[i]-cellPadding*2);
                doc.text(lines, x + cellPadding, y);
                x += colWidths[i];
            });
            y += 16 * Math.max(1, 1);
            if(y > doc.internal.pageSize.getHeight() - 60){ doc.addPage(); y = startY; }
        });

        doc.save('users.pdf');
    }

    // Wire up events
    [searchInput, filterRole, filterBlocked].forEach(el=> el && el.addEventListener('input', applyFilters));
    resetBtn && resetBtn.addEventListener('click', (e)=>{ e.preventDefault(); resetFilters(); });
    exportBtn && exportBtn.addEventListener('click', (e)=>{ e.preventDefault(); exportToPdf(); });

    // initial
    applyFilters();
})();
