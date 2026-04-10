/**
 * showcrop.js
 * Handles: live search, status filtering, animated progress bars,
 * crop count updates, and smooth card show/hide transitions.
 */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Element refs ─────────────────────────────────────── */
  const searchInput  = document.getElementById('cropSearch');
  const filterChips  = document.querySelectorAll('#filterChips .chip');
  const cards        = document.querySelectorAll('#cropsGrid .crop-card');
  const countEl      = document.getElementById('cropCount');
  const noResults    = document.getElementById('noResults');

  let activeFilter   = 'all';
  let searchQuery    = '';

  /* ── Progress bars — animate in on load ──────────────── */
  function animateProgressBars() {
    document.querySelectorAll('.progress-fill').forEach(bar => {
      const target = bar.dataset.width || '0';
      // Small delay so CSS transition fires after render
      requestAnimationFrame(() => {
        setTimeout(() => {
          bar.style.width = target + '%';
        }, 120);
      });
    });
  }

  /* ── Filter + search logic ────────────────────────────── */
  function applyFilters() {
    let visible = 0;

    cards.forEach(card => {
      const status  = (card.dataset.status  || '').toLowerCase();
      const name    = (card.dataset.name    || '').toLowerCase();
      const type    = (card.dataset.type    || '').toLowerCase();
      const variety = (card.dataset.variety || '').toLowerCase();

      const matchesFilter = activeFilter === 'all' || status === activeFilter;
      const matchesSearch = searchQuery === ''
        || name.includes(searchQuery)
        || type.includes(searchQuery)
        || variety.includes(searchQuery);

      const show = matchesFilter && matchesSearch;

      if (show) {
        card.classList.remove('filtered-out');
        visible++;
      } else {
        card.classList.add('filtered-out');
      }
    });

    // Update count
    if (countEl) {
      countEl.textContent = `${visible} crop${visible !== 1 ? 's' : ''}`;
    }

    // Show/hide no-results message
    if (noResults) {
      noResults.classList.toggle('hidden', visible > 0 || cards.length === 0);
    }
  }

  /* ── Search input ─────────────────────────────────────── */
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      searchQuery = e.target.value.trim().toLowerCase();
      applyFilters();
    });

    // Clear on Escape
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        searchInput.value = '';
        searchQuery = '';
        applyFilters();
      }
    });
  }

  /* ── Filter chips ─────────────────────────────────────── */
  filterChips.forEach(chip => {
    chip.addEventListener('click', () => {
      filterChips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      activeFilter = chip.dataset.filter;
      applyFilters();
    });
  });

  /* ── Intersection Observer — animate bars when visible ── */
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const bar = entry.target.querySelector('.progress-fill');
          if (bar) {
            const target = bar.dataset.width || '0';
            setTimeout(() => { bar.style.width = target + '%'; }, 80);
          }
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });

    cards.forEach(card => observer.observe(card));
  } else {
    // Fallback for older browsers
    animateProgressBars();
  }

  /* ── Initial apply (in case of browser back-nav) ──────── */
  applyFilters();

});