/* نگاه مدیا | اسکریپت‌های سبک سایت — بدون وابستگی خارجی */
(function () {
  'use strict';

  /* ---------- ورود تدریجی عناصر ---------- */
  var items = Array.prototype.slice.call(document.querySelectorAll('.reveal'));

  if (!('IntersectionObserver' in window) ||
      window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    items.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.1 });

    items.forEach(function (el) { io.observe(el); });
  }

  /* ---------- منوی موبایل ---------- */
  var burger = document.querySelector('[data-menu]');
  var panel  = document.querySelector('[data-menu-panel]');

  if (burger && panel) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') === 'true';
      burger.setAttribute('aria-expanded', open ? 'false' : 'true');
      if (open) {
        panel.setAttribute('hidden', '');
      } else {
        panel.removeAttribute('hidden');
      }
    });

    panel.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        panel.setAttribute('hidden', '');
        burger.setAttribute('aria-expanded', 'false');
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 1080 && !panel.hasAttribute('hidden')) {
        panel.setAttribute('hidden', '');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---------- دکمه بازگشت به بالا ---------- */
  var top = document.querySelector('[data-top]');
  if (top) {
    var toggle = function () {
      if (window.scrollY > 700) {
        top.classList.add('show');
      } else {
        top.classList.remove('show');
      }
    };
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();

    top.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- فعال‌سازی نرم لینک‌های داخلی ---------- */
  document.addEventListener('click', function (e) {
    var link = e.target.closest ? e.target.closest('a[href^="#"]') : null;
    if (!link) { return; }
    var id = link.getAttribute('href');
    if (id === '#' || id.length < 2) { return; }
    var target = document.querySelector(id);
    if (!target) { return; }
    e.preventDefault();
    var y = target.getBoundingClientRect().top + window.scrollY - 84;
    window.scrollTo({ top: y, behavior: 'smooth' });
    history.replaceState(null, '', id);
  });
})();
