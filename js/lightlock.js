/* Force light mode on conference-flow pages (no dark mode), CSP-safe external script. */
(function () {
  var H = document.documentElement;
  function light() { H.setAttribute('data-theme', 'light'); }
  function hideToggle() { var b = document.getElementById('themeToggle'); if (b) b.style.display = 'none'; }
  light();
  document.addEventListener('DOMContentLoaded', function () { light(); hideToggle(); });
  window.addEventListener('load', function () { light(); hideToggle(); });
  // site.js sets data-theme from system pref after this runs — revert any dark flip.
  try {
    new MutationObserver(function () { if (H.getAttribute('data-theme') === 'dark') light(); })
      .observe(H, { attributes: true, attributeFilter: ['data-theme'] });
  } catch (e) {}
})();
