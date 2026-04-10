(() => {
  const grid        = document.getElementById('tasks-grid');
  const searchInput = document.getElementById('task-search');
  const sortSelect  = document.getElementById('task-sort');
  const countEl     = document.getElementById('visible-count');
  const pills       = document.querySelectorAll('.pill');

  let activeFilter = 'all';

  /* ── helpers ── */
  function cards() {
    return [...grid.querySelectorAll('.task-card')];
  }

  function applyFilters() {
    const query = searchInput.value.trim().toLowerCase();

    let visible = cards().filter(card => {
      const matchesFilter = activeFilter === 'all' || card.dataset.status === activeFilter;
      const matchesSearch = !query ||
        card.dataset.name.includes(query) ||
        card.dataset.desc.includes(query);
      return matchesFilter && matchesSearch;
    });

    /* hide / show */
    cards().forEach(card => { card.style.display = 'none'; });
    visible.forEach(card  => { card.style.display = '';     });

    /* empty state */
    let emptyEl = grid.querySelector('.empty-state');
    if (visible.length === 0) {
      if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.className = 'empty-state';
        emptyEl.innerHTML = `
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
          </div>
          <h3>No tasks found</h3>
          <p>Try adjusting your search or filter.</p>`;
        grid.appendChild(emptyEl);
      }
    } else if (emptyEl) {
      emptyEl.remove();
    }

    /* update count */
    countEl.textContent = visible.length;
  }

  function applySort() {
    const val   = sortSelect.value;
    const items = cards();
    const statusOrder = { overdue: 0, in_progress: 1, pending: 2, completed: 3, cancelled: 4 };

    items.sort((a, b) => {
      if (val === 'id-asc')   return +a.dataset.id - +b.dataset.id;
      if (val === 'id-desc')  return +b.dataset.id - +a.dataset.id;
      if (val === 'name-asc') return a.dataset.name.localeCompare(b.dataset.name);
      if (val === 'status')   return (statusOrder[a.dataset.status] ?? 9) - (statusOrder[b.dataset.status] ?? 9);
      return 0;
    });

    items.forEach(card => grid.appendChild(card));
    applyFilters();
  }

  /* ── events ── */
  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeFilter = pill.dataset.filter;
      applyFilters();
    });
  });

  searchInput.addEventListener('input', applyFilters);
  sortSelect.addEventListener('change', applySort);

  /* initial */
  applyFilters();
})();