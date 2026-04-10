// Profile page JS: preview selected avatar and animate
document.addEventListener('DOMContentLoaded', function () {
  var fileInput = document.getElementById('profile_image');
  var preview = document.getElementById('avatarPreview');

  if (!fileInput || !preview) return;

  fileInput.addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;

    var allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (allowed.indexOf(file.type) === -1) {
      alert('Invalid image type. Allowed: JPG, PNG, GIF, WEBP.');
      e.target.value = '';
      return;
    }

    if (file.size > 2_000_000) {
      alert('Image is too large (max 2MB).');
      e.target.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function (ev) {
      // if preview is an img element, set src, otherwise replace innerHTML
      if (preview.tagName && preview.tagName.toLowerCase() === 'img') {
        preview.src = ev.target.result;
        preview.classList.add('animate');
        setTimeout(function () { preview.classList.remove('animate'); }, 360);
      } else {
        preview.innerHTML = '';
        var img = document.createElement('img');
        img.src = ev.target.result;
        img.className = 'avatar animate';
        img.style.width = '96px';
        img.style.height = '96px';
        img.style.objectFit = 'cover';
        preview.appendChild(img);
        setTimeout(function () { img.classList.remove('animate'); }, 360);
      }
    };
    reader.readAsDataURL(file);
  });
});
