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
let currentCropSaveUrl = '';

async function generateTasksForCrop(btn) {
    const generateUrl = btn.dataset.generateUrl;
    const saveUrl     = btn.dataset.saveUrl;
    const original    = btn.innerHTML;

    currentCropSaveUrl = saveUrl;

    btn.disabled = true;
    btn.innerHTML = '⏳ Generating...';

    try {
        const res = await fetch(generateUrl, { method: 'POST' });

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server did not return JSON');
        }

        const data = await res.json();

        if (data.success && data.tasks) {
            showAiModal(data.tasks);
        } else {
            console.error(data.error);
            alert('Failed to generate tasks.');
        }

    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    } finally {
        btn.innerHTML = original;
        btn.disabled = false;
    }
}

function showAiModal(tasks) {
    const list = document.getElementById('aiTaskList');
    list.innerHTML = '';

    tasks.forEach((task, i) => {
        const label = document.createElement('label');
        label.style.cssText = 'display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:14px;padding:8px;border:1px solid #eee;border-radius:8px;';
       label.innerHTML = `
    <input type="checkbox" value="${task.replace(/"/g, '&quot;')}" checked
        style="margin-top:3px;accent-color:#2e7d32;width:16px;height:16px;flex-shrink:0;">
    <span>${task}</span>
`;
        list.appendChild(label);
    });

    const modal = document.getElementById('aiTaskModal');
    modal.style.display = 'flex';
}

function closeAiModal() {
    document.getElementById('aiTaskModal').style.display = 'none';
}

async function saveSelectedTasks() {
    const checkboxes = document.querySelectorAll('#aiTaskList input[type="checkbox"]:checked');
    const selected   = Array.from(checkboxes).map(cb => cb.value);

    if (selected.length === 0) {
        alert('Please select at least one task.');
        return;
    }

    const saveBtn = document.getElementById('aiSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '⏳ Saving...';

    try {
        const res = await fetch(currentCropSaveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tasks: selected })
        });

        const data = await res.json();

        if (data.success) {
            closeAiModal();
            alert('✅ Tasks added successfully!');
        } else {
            alert('Error: ' + data.error);
        }
    } catch (e) {
        console.error(e);
        alert('Failed to save tasks.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Add Selected';
    }
  }
