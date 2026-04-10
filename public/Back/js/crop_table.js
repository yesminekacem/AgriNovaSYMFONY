/**
 * crop_table.js — AgriNova Crop Table
 * Place at: public/js/crop_table.js
 */

const cropTable = (() => {

    let currentFilter = 'all';
    let sortCol       = -1;
    let sortDir       = 1;

    /** Re-run both search and status filter, update counters */
    function filter() {
        const q    = document.getElementById('q').value.toLowerCase();
        const rows = document.querySelectorAll('#TB tr[data-st]');
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = row.textContent.toLowerCase().includes(q);
            const matchFilter = currentFilter === 'all' || row.dataset.st === currentFilter;

            if (matchSearch && matchFilter) {
                row.classList.remove('hide');
                row.style.animationDelay = (visible * 0.028) + 's';
                visible++;
            } else {
                row.classList.add('hide');
            }
        });

        document.getElementById('vis').textContent  = visible;
        document.getElementById('fvis').textContent = visible;
        document.getElementById('NR').style.display = visible === 0 ? 'block' : 'none';
    }

    /** Toggle the active status filter chip */
    function setFilter(value, btn) {
        currentFilter = value;
        document.querySelectorAll('.chip').forEach(c => c.classList.remove('on'));
        btn.classList.add('on');
        filter();
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