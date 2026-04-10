// Profile page JS: preview selected avatar, toggle edit/view, and client-side security validation
document.addEventListener('DOMContentLoaded', function () {
  var fileInput = document.getElementById('profile_image');
  var preview = document.getElementById('avatarPreview');

  // Preview avatar (existing behavior)
  if (fileInput && preview) {
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
  }

  // Toggle view/edit for profile
  var editBtn = document.getElementById('edit-profile-btn');
  var profileForm = document.getElementById('profile-form');
  var profileView = document.getElementById('profile-view');
  var cancelEdit = document.getElementById('cancel-edit');

  if (editBtn && profileForm && profileView) {
    editBtn.addEventListener('click', function () {
      profileView.style.display = 'none';
      profileForm.style.display = '';
      profileForm.scrollIntoView({behavior: 'smooth', block: 'center'});
    });
  }

  if (cancelEdit && profileForm && profileView) {
    cancelEdit.addEventListener('click', function () {
      profileForm.style.display = 'none';
      profileView.style.display = '';
      profileView.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
  }

  // Wire 'Change Photo' button to open hidden file input
  var changePhotoBtn = document.getElementById('change-photo-btn');
  if (changePhotoBtn && fileInput) {
    changePhotoBtn.addEventListener('click', function (e) {
      e.preventDefault();
      fileInput.click();
    });
  }

  // Security form validation (client-side)
  var securityForm = document.getElementById('security-form');
  var cancelSecurity = document.getElementById('cancel-security');
  var newPw = document.getElementById('new_password');
  var confirmPw = document.getElementById('confirm_password');
  var pwError = document.getElementById('pw-error');
  var gotoSecurity = document.getElementById('goto-security');

  if (gotoSecurity) {
    gotoSecurity.addEventListener('click', function (e) {
      // if anchor navigation, let default happen; additionally focus the security form
      setTimeout(function () {
        var sec = document.getElementById('security-section');
        if (sec) sec.scrollIntoView({behavior: 'smooth', block: 'center'});
      }, 50);
    });
  }

  if (cancelSecurity) {
    cancelSecurity.addEventListener('click', function () {
      // simply jump back to view
      var sec = document.getElementById('security-section');
      if (sec) sec.scrollIntoView({behavior: 'smooth', block: 'center'});
    });
  }

  if (securityForm) {
    securityForm.addEventListener('submit', function (e) {
      if (!newPw || !confirmPw) return; // allow server-side validation if missing elements
      var a = newPw.value || '';
      var b = confirmPw.value || '';
      if (a !== b) {
        e.preventDefault();
        if (pwError) {
          pwError.style.display = '';
          pwError.textContent = 'Passwords do not match.';
        } else {
          alert('Passwords do not match.');
        }
        return false;
      }

      // basic client-side pattern check (same as server)
      var pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
      if (!pattern.test(a)) {
        e.preventDefault();
        if (pwError) {
          pwError.style.display = '';
          pwError.textContent = 'Password must be at least 8 chars and include upper/lower, number and special char.';
        } else {
          alert('Password must be at least 8 chars and include upper/lower, number and special char.');
        }
        return false;
      }

      // otherwise allow submit
      return true;
    });

    // live hide error when user types
    [newPw, confirmPw].forEach(function (el) {
      if (!el) return;
      el.addEventListener('input', function () {
        if (pwError) { pwError.style.display = 'none'; }
      });
    });
  }

});
