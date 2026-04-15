// delete-confirm.js
// Reusable delete confirmation modal. Creates one modal and reuses it for all delete buttons.
(function () {
  if (window.__deleteConfirmLoaded) return;
  window.__deleteConfirmLoaded = true;

  function createModal() {
    const modal = document.createElement('div');
    modal.id = 'global-delete-modal';
    Object.assign(modal.style, {
      display: 'none', position: 'fixed', inset: '0',
      background: 'rgba(0,0,0,0.45)', zIndex: 2000,
      alignItems: 'center', justifyContent: 'center'
    });

    const dialog = document.createElement('div');
    Object.assign(dialog.style, { background: '#fff', maxWidth: '560px', width: '90%', padding: '18px', borderRadius: '10px', boxShadow: '0 8px 30px rgba(0,0,0,0.25)' });

    const header = document.createElement('div');
    header.style.display = 'flex'; header.style.justifyContent = 'space-between'; header.style.alignItems = 'center';
    const title = document.createElement('h3'); title.id = 'global-delete-title'; title.style.margin = '0'; title.textContent = 'Confirm Delete';
    const close = document.createElement('button'); close.type = 'button'; close.id = 'global-delete-close'; close.textContent = '✕';
    Object.assign(close.style, { background: 'none', border: 'none', fontSize: '18px', cursor: 'pointer' });
    header.appendChild(title); header.appendChild(close);

    const msg = document.createElement('div'); msg.id = 'global-delete-message'; msg.style.marginTop = '12px'; msg.textContent = '';

    const footer = document.createElement('div'); footer.style.marginTop = '16px'; footer.style.display = 'flex'; footer.style.justifyContent = 'flex-end'; footer.style.gap = '10px';
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.id = 'global-delete-cancel'; cancel.textContent = 'Cancel';
    Object.assign(cancel.style, { padding: '8px 14px', borderRadius: '8px', border: '1px solid #e5e7eb', background: '#fff', cursor: 'pointer' });
    const confirm = document.createElement('button'); confirm.type = 'button'; confirm.id = 'global-delete-confirm'; confirm.textContent = 'Yes, Delete';
    Object.assign(confirm.style, { padding: '8px 14px', borderRadius: '8px', border: 'none', background: '#dc2626', color: '#fff', cursor: 'pointer' });

    footer.appendChild(cancel); footer.appendChild(confirm);

    dialog.appendChild(header); dialog.appendChild(msg); dialog.appendChild(footer);
    modal.appendChild(dialog);

    // attach to body
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
