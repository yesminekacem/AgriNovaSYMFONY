// Admin UI interactions: animate form submissions and provide small UX feedback
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.anim-submit').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      // add a small animation to the row
      var row = form.closest('.user-row');
      if (row) {
        row.classList.add('submitting');
        row.classList.add('anim-pulse');
        setTimeout(function () {
          row.classList.remove('anim-pulse');
        }, 450);
      }
      // let form submit normally
    });
  });

  // optional: progressive enhancement - confirm promote/demote/block actions
  document.querySelectorAll('.anim-submit[data-action="promote"]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm('Promote this user to ADMIN?')) e.preventDefault();
    });
  });

  document.querySelectorAll('.anim-submit[data-action="demote"]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm('Demote this user to USER?')) e.preventDefault();
    });
  });

  document.querySelectorAll('.anim-submit[data-action="block"]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm('Toggle block for this user?')) e.preventDefault();
    });
  });

});
