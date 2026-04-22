/**
 * crop_table.js — AgriNova Crop Table
 * Place at: public/Back/js/crop_table.js
 */

const cropTable = (() => {

    let currentFilter = 'all';
    let sortCol       = -1;
    let sortDir       = 1;

   function setFilter(value, btn) {
    currentFilter = value; // store raw chip value

    document.querySelectorAll('.chip').forEach(c => c.classList.remove('on'));
    btn.classList.add('on');

    filter();
}

function filter() {
    const input = document.getElementById('cropSearch');
    const q = input ? input.value.toLowerCase() : '';
    const rows = document.querySelectorAll('#TB tr[data-st]');
    let visible = 0;

    // "active" chip covers both growing + planted statuses
    const activeStatuses = ['growing', 'planted'];

    rows.forEach(row => {
        const st = row.dataset.st;
        const matchSearch = row.textContent.toLowerCase().includes(q);

        let matchFilter;
        if (currentFilter === 'all') {
            matchFilter = true;
        } else if (currentFilter === 'active') {
            matchFilter = activeStatuses.includes(st);
        } else {
            matchFilter = st === currentFilter;
        }

        if (matchSearch && matchFilter) {
            row.classList.remove('hide');
            row.style.animationDelay = (visible * 0.028) + 's';
            visible++;
        } else {
            row.classList.add('hide');
        }
    });

    const cropCountEl = document.getElementById('cropCount');
    if (cropCountEl) {
        cropCountEl.textContent = `${visible} crop${visible !== 1 ? 's' : ''} registered`;
    }
    const fvisEl = document.getElementById('fvis');
    if (fvisEl) fvisEl.textContent = visible;
    const nr = document.getElementById('NR');
    if (nr) nr.style.display = visible === 0 ? 'block' : 'none';
}

    /** Sort rows by a column index */
    function sort(col, th) {
        if (sortCol === col) {
            sortDir *= -1;
        } else {
            sortDir = 1;
            sortCol = col;
        }

        // Update header indicators
        document.querySelectorAll('#T thead th').forEach(h => h.classList.remove('asc', 'desc'));
        th.classList.add(sortDir === 1 ? 'asc' : 'desc');

        const tbody = document.getElementById('TB');
        const rows  = Array.from(tbody.querySelectorAll('tr[data-st]'));

        rows.sort((a, b) => {
            const av = a.cells[col]?.textContent.trim().toLowerCase() || '';
            const bv = b.cells[col]?.textContent.trim().toLowerCase() || '';
            return av.localeCompare(bv) * sortDir;
        });

        rows.forEach((row, i) => {
            row.style.animationDelay = (i * 0.028) + 's';
            tbody.appendChild(row);
        });
    }

    // Public API
    return { filter, setFilter, sort };

})();

// Auto-wire on DOM ready: attach listeners and run initial filter
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('cropSearch');
    if (input) {
        input.addEventListener('input', () => cropTable.filter());
    }

    document.querySelectorAll('.chip').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cropTable.setFilter(btn.dataset.filter, btn);
        });
    });

    // Initial run to set counts and visibility
    cropTable.filter();
});
function openDeleteModal(actionUrl, token) {
    document.getElementById('delete-modal-form').action = actionUrl;
    document.getElementById('delete-modal-token').value = token;
    document.getElementById('delete-modal-overlay').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('delete-modal-overlay').style.display = 'none';
}

// Close when clicking outside the modal box
document.getElementById('delete-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});