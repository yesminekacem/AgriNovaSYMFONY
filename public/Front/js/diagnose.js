const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 5 * 1024 * 1024;

const btn       = document.getElementById('diagnoseBtn');
const input     = document.getElementById('diagnoseInput');
const resultDiv = document.getElementById('diagnoseResult');

const DISEASE_STYLES = {
  healthy:        { bg: '#f0fdf4', border: '#86efac', text: '#166534', label: 'Healthy',        badge: '#dcfce7', badgeText: '#166534' },
  rust:           { bg: '#fefce8', border: '#fde047', text: '#854d0e', label: 'Rust',            badge: '#fef9c3', badgeText: '#854d0e' },
  leaf_spot:      { bg: '#fff7ed', border: '#fdba74', text: '#9a3412', label: 'Leaf Spot',       badge: '#ffedd5', badgeText: '#9a3412' },
  early_blight:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', label: 'Early Blight',   badge: '#fee2e2', badgeText: '#991b1b' },
  powdery_mildew: { bg: '#f5f3ff', border: '#c4b5fd', text: '#5b21b6', label: 'Powdery Mildew', badge: '#ede9fe', badgeText: '#5b21b6' },
  unknown:        { bg: '#f9fafb', border: '#e5e7eb', text: '#374151', label: 'Unknown',         badge: '#f3f4f6', badgeText: '#374151' },
};

function getStyle(disease) {
  const key = disease.toLowerCase().replace(/\s+/g, '_');
  return DISEASE_STYLES[key] || DISEASE_STYLES.unknown;
}

function setLoading() {
  if (!resultDiv) return;
  resultDiv.style.display = 'block';
  resultDiv.innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;
                background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px">
      <div style="width:16px;height:16px;border:2px solid #d1d5db;border-top-color:#1D9E75;
                  border-radius:50%;animation:spin 0.7s linear infinite;flex-shrink:0"></div>
      <span style="font-size:13px;color:#6b7280">Analyzing your photo, please wait...</span>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
  `;
}

function showError(message) {
  if (!resultDiv) return;
  resultDiv.style.display = 'block';
  resultDiv.innerHTML = `
    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;
                background:#fef2f2;border:0.5px solid #fca5a5;border-radius:10px">
      <div style="width:18px;height:18px;border-radius:50%;background:#ef4444;flex-shrink:0;margin-top:1px;
                  display:flex;align-items:center;justify-content:center">
        <span style="color:#fff;font-size:11px;font-weight:700">!</span>
      </div>
      <div>
        <p style="font-size:13px;font-weight:500;color:#991b1b;margin:0 0 2px">Diagnosis failed</p>
        <p style="font-size:12px;color:#b91c1c;margin:0">${message}</p>
      </div>
    </div>
  `;
}

function showDiagnosis(disease, confidence) {
  if (!resultDiv) return;
  const s = getStyle(disease);
  const confidenceNum = parseFloat(confidence);
  const barWidth = isNaN(confidenceNum) ? 0 : confidenceNum;

  resultDiv.style.display = 'block';
  resultDiv.innerHTML = `
    <div style="border:0.5px solid ${s.border};background:${s.bg};border-radius:10px;overflow:hidden">

      <div style="padding:16px 20px;border-bottom:0.5px solid ${s.border}">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <div>
            <p style="font-size:11px;color:#6b7280;margin:0 0 4px;text-transform:uppercase;letter-spacing:0.06em">
              Detected disease
            </p>
            <p style="font-size:20px;font-weight:500;color:${s.text};margin:0">
              ${s.label}
            </p>
          </div>
          <span style="font-size:12px;font-weight:500;padding:4px 12px;border-radius:99px;
                       background:${s.badge};color:${s.badgeText}">
            ${disease === 'healthy' ? 'No disease found' : 'Disease detected'}
          </span>
        </div>
      </div>

      <div style="padding:14px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:12px;color:#6b7280">Confidence level</span>
          <span style="font-size:13px;font-weight:500;color:${s.text}">${confidence}</span>
        </div>
        <div style="height:5px;background:rgba(0,0,0,0.06);border-radius:99px;overflow:hidden">
          <div style="width:${barWidth}%;height:100%;background:${s.text};border-radius:99px;opacity:0.6;transition:width 0.4s ease"></div>
        </div>
        <p style="font-size:11px;color:#9ca3af;margin:8px 0 0">
          ${confidenceNum >= 70
            ? 'High confidence — result is likely accurate.'
            : confidenceNum >= 40
              ? 'Moderate confidence — consider a second check.'
              : 'Low confidence — result may not be reliable.'}
        </p>
      </div>

    </div>
  `;
}

function clearResult() {
  if (!resultDiv) return;
  resultDiv.style.display = 'none';
  resultDiv.innerHTML = '';
}

if (!btn || !input) {
  console.error("diagnose: elements not found in DOM");
} else {

  btn.addEventListener('click', () => input.click());

  input.addEventListener('change', async (ev) => {
    clearResult();

    const file = ev.target.files?.[0];
    if (!file) return;

    if (!ALLOWED.includes(file.type)) {
      showError('Unsupported file type. Please use JPEG, PNG or WebP.');
      return;
    }
    if (file.size > MAX_BYTES) {
      showError('File is too large. Maximum allowed size is 5 MB.');
      return;
    }

    const formData = new FormData();
    formData.append('image', file);

    try {
      btn.disabled = true;
      setLoading();

      const resp = await fetch('/api/v1/diagnose', { method: 'POST', body: formData });
      const data = await resp.json();

      if (!resp.ok) {
        showError(data.error || data.detail || 'The server returned an error.');
        return;
      }

      const disease = data.disease || 'unknown';
      const confidence = typeof data.confidence === 'number'
        ? (data.confidence * 100).toFixed(1) + '%'
        : typeof data.confidence === 'string' && !isNaN(data.confidence)
          ? (parseFloat(data.confidence) * 100).toFixed(1) + '%'
          : 'N/A';

      showDiagnosis(disease, confidence);

    } catch (e) {
      showError('A network error occurred. Please check your connection and try again.');
    } finally {
      btn.disabled = false;
      input.value = '';
    }
  });

}