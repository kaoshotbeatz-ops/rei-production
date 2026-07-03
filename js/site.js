// Marketing site behaviour: CSRF injection, nav, scroll progress, reveals,
// counters, parallax, tilt, and magnetic buttons.
(function () {
  // --- CSRF: stamp every form with the session token ---
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (meta) {
    document.querySelectorAll('form').forEach(function (f) {
      if (f.method && f.method.toLowerCase() === 'get') return;
      if (f.querySelector('input[name="_csrf"]')) return;
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = '_csrf'; i.value = meta.content;
      f.appendChild(i);
    });
  }

  // --- header scroll state ---
  var hdr = document.getElementById('hdr');
  if (hdr && !hdr.classList.contains('light')) {
    var onScroll = function () { hdr.classList.toggle('scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  }

  // --- scroll progress bar ---
  var bar = document.getElementById('scrollbar');
  if (bar) {
    window.addEventListener('scroll', function () {
      var h = document.documentElement;
      var p = h.scrollTop / (h.scrollHeight - h.clientHeight);
      bar.style.transform = 'scaleX(' + (p || 0) + ')';
    }, { passive: true });
  }

  // --- mobile nav ---
  var nav = document.getElementById('nav'), mb = document.getElementById('menuBtn');
  if (mb) {
    mb.addEventListener('click', function () { nav.classList.toggle('open'); });
    nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { nav.classList.remove('open'); }); });
  }

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // --- staggered reveal on scroll ---
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) {
      if (e.isIntersecting) {
        var d = e.target.dataset.delay || 0;
        e.target.style.transitionDelay = d + 'ms';
        e.target.classList.add('in');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(function (el, i) {
    if (el.closest('[data-stagger]') && !el.dataset.delay) el.dataset.delay = (i % 6) * 80;
    io.observe(el);
  });

  // --- animated counters ---
  function animate(el) {
    // data-since auto-computes years (current year - since), self-incrementing every year
    var to = el.dataset.since ? (new Date().getFullYear() - +el.dataset.since) : +el.dataset.to;
    var suf = el.dataset.suffix || '', raw = el.dataset.raw === 'true';
    var start = null, dur = 1700;
    function step(t) {
      if (!start) start = t;
      var p = Math.min((t - start) / dur, 1);
      var val = Math.floor((1 - Math.pow(1 - p, 3)) * to);
      el.textContent = (raw ? String(val) : val.toLocaleString()) + (p === 1 ? suf : '');
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var co = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { animate(e.target); co.unobserve(e.target); } });
  }, { threshold: 0.5 });
  document.querySelectorAll('.num[data-to]').forEach(function (n) { co.observe(n); });

  if (reduce) return;

  // --- parallax layers (data-parallax = speed) ---
  var parallax = [].slice.call(document.querySelectorAll('[data-parallax]'));
  if (parallax.length) {
    window.addEventListener('scroll', function () {
      var y = window.scrollY;
      parallax.forEach(function (el) {
        var s = parseFloat(el.dataset.parallax) || 0.2;
        el.style.transform = 'translate3d(0,' + (y * s) + 'px,0)';
      });
    }, { passive: true });
  }

  // --- 3D tilt on cards (data-tilt) ---
  document.querySelectorAll('[data-tilt]').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      var r = el.getBoundingClientRect();
      var px = (e.clientX - r.left) / r.width - 0.5;
      var py = (e.clientY - r.top) / r.height - 0.5;
      el.style.transform = 'perspective(800px) rotateY(' + (px * 7) + 'deg) rotateX(' + (-py * 7) + 'deg) translateY(-4px)';
    });
    el.addEventListener('mouseleave', function () { el.style.transform = ''; });
  });

  // --- magnetic buttons (data-magnetic) ---
  document.querySelectorAll('[data-magnetic]').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      var r = el.getBoundingClientRect();
      el.style.transform = 'translate(' + ((e.clientX - r.left - r.width / 2) * 0.25) + 'px,' + ((e.clientY - r.top - r.height / 2) * 0.35) + 'px)';
    });
    el.addEventListener('mouseleave', function () { el.style.transform = ''; });
  });

  // --- cursor glow ---
  var glow = document.getElementById('glow');
  if (glow) {
    window.addEventListener('mousemove', function (e) {
      glow.style.transform = 'translate(' + (e.clientX - 250) + 'px,' + (e.clientY - 250) + 'px)';
    });
  }
})();

// ===== Accessibility: dark / light mode toggle =====
(function () {
  var btn = document.getElementById('themeToggle');
  if (!btn) return;
  var ico = btn.querySelector('.ti-ico'), txt = btn.querySelector('.ti-txt');
  function set(mode) {
    document.documentElement.setAttribute('data-theme', mode);
    var dark = mode === 'dark';
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    if (ico) ico.textContent = dark ? '☀️' : '🌙';
    if (txt) txt.textContent = dark ? 'Light mode' : 'Dark mode';
    try { localStorage.setItem('theme', mode); } catch (e) {}
  }
  var isMobile = window.matchMedia && window.matchMedia('(max-width: 760px)').matches;
  if (isMobile) {
    // Mobile is always light — no dark mode on phones
    set('light');
  } else {
    var saved;
    try { saved = localStorage.getItem('theme'); } catch (e) {}
    if (!saved) saved = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    set(saved);
    btn.addEventListener('click', function () {
      set(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
  }
})();
