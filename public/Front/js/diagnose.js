document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('diagnoseBtn');
  const input = document.getElementById('diagnoseInput');
  const modal = document.getElementById('diagnoseModal');
  const modalContent = document.getElementById('diagnoseModalContent');
  const modalClose = document.getElementById('diagnoseModalClose');

  if (!btn || !input || !modal || !modalContent || !modalClose) return;

  btn.addEventListener('click', () => input.click());

  modalClose.addEventListener('click', () => {
    modal.style.display = 'none';
    modalContent.innerHTML = '';
    input.value = '';
  });

  // close on Esc
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      modal.style.display = 'none';
    }
  });

  input.addEventListener('change', async (ev) => {
    const file = ev.target.files && ev.target.files[0];
    if (!file) return;

    modal.style.display = 'flex';
    modalContent.textContent = 'Uploading and diagnosing...';

    const form = new FormData();
    form.append('image', file, file.name);

    try {
      const resp = await fetch('/api/v1/diagnose', {
        method: 'POST',
        body: form,
      });

      const data = await resp.json();
      if (!resp.ok) {
        modalContent.textContent = data.error || data.detail || 'Diagnosis failed';
        return;
      }

      const disease = data.disease || 'unknown';
      const confidence = typeof data.confidence === 'number' ? (data.confidence * 100).toFixed(1) + '%' : data.confidence;
      modalContent.innerHTML = `<p><strong>Disease:</strong> ${escapeHtml(disease)}</p><p><strong>Confidence:</strong> ${escapeHtml(confidence)}</p>`;
    } catch (e) {
      modalContent.textContent = 'Network error while diagnosing';
    }
  });

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
});
