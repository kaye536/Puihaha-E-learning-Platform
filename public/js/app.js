// Mobile sidebar toggle (closes on outside click / Escape) + confirm dialogs for destructive actions
document.addEventListener('DOMContentLoaded', function () {
  var menu = document.getElementById('menu-toggle');
  var sidebar = document.getElementById('sidebar');
  if (menu && sidebar) {
    menu.addEventListener('click', function (e) { e.stopPropagation(); sidebar.classList.toggle('open'); });
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) { sidebar.classList.remove('open'); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { sidebar.classList.remove('open'); }
    });
  }
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });
});
