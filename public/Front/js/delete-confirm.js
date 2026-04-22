// delete-confirm.js
// Reusable delete confirmation modal. Creates one modal and reuses it for all delete buttons.
(function () {
  if (window.__deleteConfirmLoaded) return;
  window.__deleteConfirmLoaded = true;

  function createModal() {
    const modal = document.createElement('div');
    modal.id = 'global-delete-modal';
    modal.className = 'gd-modal';

    const dialog = document.createElement('div');
    dialog.className = 'gd-dialog';

    // header / icon
    const iconWrap = document.createElement('div');
    iconWrap.className = 'gd-icon-wrap';
    iconWrap.innerHTML = '<div class="gd-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 3v1H4v2h16V4h-5V3H9z" fill="#F43F5E"/><path d="M6 7l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14H6z" fill="#FEE2E2"/><path d="M9 11v6" stroke="#D32F2F" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11v6" stroke="#D32F2F" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 11v6" stroke="#D32F2F" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>';

    const close = document.createElement('button'); close.type = 'button'; close.id = 'global-delete-close'; close.className = 'gd-close'; close.textContent = '✕';

    const title = document.createElement('h3'); title.id = 'global-delete-title'; title.className = 'gd-title'; title.textContent = 'Confirm Delete';
    const msg = document.createElement('div'); msg.id = 'global-delete-message'; msg.className = 'gd-message'; msg.textContent = '';

    const footer = document.createElement('div'); footer.className = 'gd-footer';
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.id = 'global-delete-cancel'; cancel.className = 'gd-btn gd-cancel'; cancel.textContent = 'Cancel';
    const confirm = document.createElement('button'); confirm.type = 'button'; confirm.id = 'global-delete-confirm'; confirm.className = 'gd-btn gd-confirm'; confirm.textContent = 'Yes, Delete';

    footer.appendChild(cancel); footer.appendChild(confirm);

    dialog.appendChild(close);
    dialog.appendChild(iconWrap);
    dialog.appendChild(title);
    dialog.appendChild(msg);
    dialog.appendChild(footer);
    modal.appendChild(dialog);

    document.body.appendChild(modal);

    // events
    close.addEventListener('click', () => hideModal());
    cancel.addEventListener('click', () => hideModal());
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    return { modal, title, msg, confirm };
  }

  let state = null;
  function ensure() { if (!state) state = createModal(); return state; }

  function showModal(opts) {
    const s = ensure();
    s.title.textContent = opts.title || 'Confirm Delete';
    s.msg.textContent = opts.message || 'Are you sure? This action cannot be undone.';
    s.modal.style.display = 'flex';

    const handler = () => {
      try { document.getElementById(opts.formId).submit(); } catch (e) { console.error(e); }
      hideModal();
    };

    s.confirm.addEventListener('click', handler, { once: true });
  }

  function hideModal() { if (!state) return; state.modal.style.display = 'none'; }

  document.addEventListener('click', function (e) {
    const target = e.target.closest && e.target.closest('.js-delete-btn');
    if (!target) return;
    const formId = target.dataset.targetForm;
    const title = target.dataset.title;
    const message = target.dataset.message;
    if (!formId) return;
    showModal({ formId, title, message });
  });

  // allow Escape to close
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && state && state.modal.style.display === 'flex') hideModal(); });

})();
