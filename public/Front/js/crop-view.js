document.addEventListener('DOMContentLoaded', () => {
  const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
  const MAX_BYTES = 5 * 1024 * 1024; // 5MB

  const viewButtons = document.querySelectorAll('.view-btn');
  const modal = document.getElementById('cropViewModal');
  const modalContent = document.getElementById('cropViewContent');
  const modalClose = document.getElementById('cropViewModalClose');

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function showModal() {
    if (!modal) return;
    modal.style.display = 'flex';
  }
  function hideModal() {
    if (!modal) return;
    modal.style.display = 'none';
    if (modalContent) modalContent.innerHTML = '';
  }

  if (modalClose) modalClose.addEventListener('click', hideModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideModal(); });

  async function runDiagnosis(file, resultEl, showInModal = true) {
    if (!file) return;
    if (!ALLOWED.includes(file.type)) {
      resultEl.innerHTML = '<strong>Error:</strong> Unsupported file type.';
      resultEl.style.borderColor = '#f87171';
      return;
    }
    if (file.size > MAX_BYTES) {
      resultEl.innerHTML = '<strong>Error:</strong> File too large (max 5 MB)';
      resultEl.style.borderColor = '#f87171';
      return;
    }

    const form = new FormData();
    form.append('image', file, file.name);

    try {
      resultEl.innerHTML = '⏳ Uploading and diagnosing...';
      resultEl.style.borderColor = '';

      const resp = await fetch('/api/v1/diagnose', { method: 'POST', body: form });
      const data = await resp.json();

      if (!resp.ok) {
        const err = data.error || data.detail || 'Diagnosis failed';
        resultEl.innerHTML = `<strong>Error:</strong> ${escapeHtml(err)}`;
        resultEl.style.borderColor = '#f87171';
        return;
      }

      const disease = escapeHtml(data.disease || 'unknown');
      const confidence = typeof data.confidence === 'number' ? (data.confidence * 100).toFixed(1) + '%' : (data.confidence || 'N/A');
      resultEl.innerHTML = `\n        <p>🌿 <strong>Disease:</strong> ${disease}</p>\n        <p>📊 <strong>Confidence:</strong> ${escapeHtml(confidence)}</p>\n      `;
      resultEl.style.borderColor = '#4ade80';
    } catch (e) {
      console.error('Diagnosis error', e);
      resultEl.innerHTML = '<strong>Error:</strong> Network error while diagnosing.';
      resultEl.style.borderColor = '#f87171';
    }
  }

  viewButtons.forEach(btn => {
    btn.addEventListener('click', (ev) => {
      ev.preventDefault();
      const card = btn.closest('.crop-card');
      if (!card) return;

      const id = card.dataset.id || '';
      const name = card.dataset.name || '';
      const type = card.dataset.type || '';
      const variety = card.dataset.variety || '';
      const planting = card.dataset.plantingDate || '';
      const expected = card.dataset.expectedHarvest || '';
      const stage = card.dataset.growthStage || '';
      const area = card.dataset.areaSize || '';
      const image = card.dataset.imagePath || '';

      const imageUrl = image ? ('/cropsimages/' + image) : '';

      const html = `
        <div style="display:flex;gap:18px;align-items:flex-start;">
          <div style="flex:0 0 220px;">
            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(name)}" style="width:100%;border-radius:6px;object-fit:cover;"/>` : '<div style="width:100%;height:140px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8">No image</div>'}
          </div>
          <div style="flex:1;">
            <h3 style="margin-top:0">${escapeHtml(name)}</h3>
            <p><strong>Type:</strong> ${escapeHtml(type)} · <strong>Variety:</strong> ${escapeHtml(variety)}</p>
            <p><strong>Growth stage:</strong> ${escapeHtml(stage)}</p>
            <p><strong>Planting date:</strong> ${escapeHtml(planting)}</p>
            <p><strong>Expected harvest:</strong> ${escapeHtml(expected)}</p>
            <p><strong>Area size:</strong> ${escapeHtml(area)}</p>

            <div style="margin-top:12px;display:flex;gap:12px;align-items:center">
              <button id="modalDiagnoseBtn" class="btn btn-primary">Diagnose Photo</button>
              <input id="modalDiagnoseInput" type="file" accept="image/*" style="display:none">
            </div>

            <div id="modalDiagnoseResult" style="display:block;margin-top:12px;border:1px solid #e6f4ea;padding:12px;border-radius:6px;min-height:40px"></div>
          </div>
        </div>
      `;

      modalContent.innerHTML = html;

      // wire diagnose inside modal
      const modalDiagnoseBtn = document.getElementById('modalDiagnoseBtn');
      const modalDiagnoseInput = document.getElementById('modalDiagnoseInput');
      const modalDiagnoseResult = document.getElementById('modalDiagnoseResult');

      if (modalDiagnoseBtn && modalDiagnoseInput) {
        modalDiagnoseBtn.addEventListener('click', () => modalDiagnoseInput.click());
        modalDiagnoseInput.addEventListener('change', (e) => {
          const file = e.target.files?.[0];
          runDiagnosis(file, modalDiagnoseResult, true);
          // reset after selection so same file can be picked again
          modalDiagnoseInput.value = '';
        });
      }

      showModal();
    });
  });
});
