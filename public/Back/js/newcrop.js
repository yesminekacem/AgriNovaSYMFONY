document.addEventListener('DOMContentLoaded', () => {
  const input   = document.getElementById(cropFormConfig.imageFieldId);
  const preview = document.getElementById('preview');
  const badge   = document.getElementById('filename-badge');
  const box     = document.getElementById('upload-box');

  input.addEventListener('change', () => {
    if (input.files.length > 0) showPreview(input.files[0]);
  });

  box.addEventListener('dragover', (e) => {
    e.preventDefault();
    box.style.borderColor = '#22c55e';
  });

  box.addEventListener('dragleave', () => {
    box.style.borderColor = '#c4dbc9';
  });

  box.addEventListener('drop', (e) => {
    e.preventDefault();
    box.style.borderColor = '#c4dbc9';
    const dt = e.dataTransfer;
    if (dt.files.length > 0) {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(dt.files[0]);
      input.files = dataTransfer.files;
      showPreview(dt.files[0]);
    }
  });

  function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
    badge.textContent = '✅ ' + file.name;
    badge.style.display = 'block';
  }
});