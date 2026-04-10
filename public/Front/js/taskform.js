document.addEventListener('DOMContentLoaded', () => {

  /* ── Stagger field animations ── */
  document.querySelectorAll('.field').forEach((el, i) => {
    el.style.animationDelay = `${0.04 + i * 0.06}s`;
  });

  /* ── Date fields: set min to today ── */
  const today = new Date().toISOString().split('T')[0];
  document.querySelectorAll('input[type="date"]').forEach(input => {
    if (!input.min) input.min = today;
  });

  /* ── Completed Date: must be >= Scheduled Date ── */
  const scheduledInput  = document.getElementById('task_scheduled_date');
  const completedInput  = document.getElementById('task_completed_date');

  if (scheduledInput && completedInput) {
    scheduledInput.addEventListener('change', () => {
      completedInput.min = scheduledInput.value || today;
      if (completedInput.value && completedInput.value < scheduledInput.value) {
        completedInput.value = scheduledInput.value;
      }
    });
  }

  /* ── Status → subtle card border accent ── */
  const statusSelect = document.getElementById('task_status');
  const card         = document.querySelector('.form-card');

  const statusColors = {
    pending:     '#c97c2a',
    in_progress: '#1a6fcc',
    completed:   '#3d7a4f',
  };

  function applyStatusAccent(val) {
    const color = statusColors[val];
    if (card && color) {
      card.style.borderColor = color + '55';
      card.style.boxShadow   = `0 8px 40px rgba(60,50,30,0.10), 0 0 0 1px ${color}22`;
    }
  }

  if (statusSelect) {
    applyStatusAccent(statusSelect.value);
    statusSelect.addEventListener('change', () => applyStatusAccent(statusSelect.value));
  }

  /* ── Save button loading state ── */
  const form    = document.querySelector('form');
  const saveBtn = document.querySelector('.btn-save');

  if (form && saveBtn) {
    form.addEventListener('submit', () => {
      saveBtn.disabled     = true;
      saveBtn.innerHTML    = '<span class="spin">↻</span> Saving…';
      saveBtn.style.opacity = '0.8';
    });
  }

  /* ── Spin keyframe (injected once) ── */
  if (!document.getElementById('spin-style')) {
    const s = document.createElement('style');
    s.id = 'spin-style';
    s.textContent = '@keyframes spin { to { transform: rotate(360deg); } } .spin { display:inline-block; animation: spin 0.7s linear infinite; }';
    document.head.appendChild(s);
  }

});