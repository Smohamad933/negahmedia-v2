/* =========================================================================
   نگاه مدیا | اسکریپت‌های سایت — بدون وابستگی خارجی
   ========================================================================= */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var root    = document.documentElement;

  /* ─────────────── ابزارهای کوچک ─────────────── */
  function toFa(input) {
    return String(input).replace(/[0-9]/g, function (d) {
      return '۰۱۲۳۴۵۶۷۸۹'[+d];
    });
  }

  function toEnDigits(input) {
    return String(input).replace(/[۰-۹]/g, function (d) {
      return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
    });
  }

  /* ═══════════════════════════════════════════════
     ۱. تقسیم متن به کلمات برای انیمیشن ورود
     ═══════════════════════════════════════════════ */
  document.querySelectorAll('[data-split]').forEach(function (el) {
    var text = (el.textContent || '').trim().replace(/\s+/g, ' ');
    if (!text) { return; }

    var words = text.split(' ');
    el.setAttribute('aria-label', text);
    el.setAttribute('data-split-ready', '');

    el.innerHTML = words.map(function (word, i) {
      var safe = word.replace(/[&<>"]/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
      });
      return '<span class="word" aria-hidden="true" style="--wi:' + i + '">' + safe + '</span>';
    }).join(' ');
  });

  /* ═══════════════════════════════════════════════
     ۲. ورود عناصر با اسکرول
     ═══════════════════════════════════════════════ */
  var animated = Array.prototype.slice.call(document.querySelectorAll('[data-anim], .reveal'));

  if (!('IntersectionObserver' in window) || reduced) {
    animated.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -7% 0px', threshold: 0.08 });

    animated.forEach(function (el) { io.observe(el); });
  }

  /* ═══════════════════════════════════════════════
     ۳. خط نوار بالا (پیشرفت مطالعه)
     ═══════════════════════════════════════════════ */
  var bar = document.querySelector('[data-progress]');
  var nav = document.querySelector('[data-nav]');
  var top = document.querySelector('[data-top]');
  var steps = document.querySelector('[data-steps]');
  var stepsFill = steps ? steps.querySelector('.steps__line i') : null;
  var ticking = false;

  function onScroll() {
    var y = window.scrollY || window.pageYOffset;
    var max = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);

    if (bar) {
      bar.style.transform = 'scaleX(' + Math.min(1, y / max).toFixed(4) + ')';
    }
    if (nav) {
      nav.classList.toggle('is-stuck', y > 30);
    }
    if (top) {
      top.classList.toggle('show', y > 760);
    }
    if (steps && stepsFill) {
      var rect = steps.getBoundingClientRect();
      var start = window.innerHeight * 0.86;
      var progress = (start - rect.top) / (rect.height + start - window.innerHeight * 0.25);
      stepsFill.style.transform = 'scaleX(' + Math.max(0, Math.min(1, progress)).toFixed(4) + ')';
    }
    ticking = false;
  }

  function requestScroll() {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(onScroll);
    }
  }

  window.addEventListener('scroll', requestScroll, { passive: true });
  window.addEventListener('resize', requestScroll, { passive: true });
  onScroll();

  /* ═══════════════════════════════════════════════
     ۴. شمارش اعداد آمار (۳+ ، ۴۰+ ، ۱۲+)
     ═══════════════════════════════════════════════ */
  var counters = Array.prototype.slice.call(document.querySelectorAll('[data-count]'));

  function runCounter(el) {
    if (el.getAttribute('data-done') === '1') { return; }
    el.setAttribute('data-done', '1');

    var raw    = toEnDigits(el.getAttribute('data-count') || el.textContent || '');
    var match  = raw.match(/[\d.,]+/);
    if (!match) { return; }

    var target   = parseFloat(match[0].replace(/,/g, ''));
    if (isNaN(target)) { return; }

    var decimals = (match[0].split('.')[1] || '').length;
    var before   = raw.slice(0, match.index);
    var after    = raw.slice(match.index + match[0].length);

    if (reduced) {
      el.textContent = before + toFa(target.toFixed(decimals)) + after;
      return;
    }

    var duration = 1500;
    var startAt  = null;

    function frame(now) {
      if (startAt === null) { startAt = now; }
      var p = Math.min(1, (now - startAt) / duration);
      var eased = 1 - Math.pow(1 - p, 3);
      var value = target * eased;

      el.textContent = before + toFa(value.toFixed(decimals)) + after;

      if (p < 1) {
        window.requestAnimationFrame(frame);
      } else {
        el.textContent = before + toFa(target.toFixed(decimals)) + after;
      }
    }
    window.requestAnimationFrame(frame);
  }

  if (counters.length) {
    if (!('IntersectionObserver' in window)) {
      counters.forEach(runCounter);
    } else {
      var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            runCounter(entry.target);
            co.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      counters.forEach(function (el) { co.observe(el); });
    }
  }

  if (top) {
    top.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    });
  }

  /* ═══════════════════════════════════════════════
     ۷. اسکرول نرم به بخش‌ها
     ═══════════════════════════════════════════════ */
  document.addEventListener('click', function (e) {
    var link = e.target.closest ? e.target.closest('a[href*="#"]') : null;
    if (!link) { return; }

    var href = link.getAttribute('href') || '';
    var hashAt = href.indexOf('#');
    if (hashAt === -1) { return; }

    var id = href.slice(hashAt);
    if (id === '#' || id.length < 2) { return; }

    // اگر لینک به صفحه دیگری اشاره دارد، اجازه بده مرورگر خودش برود
    var path = href.slice(0, hashAt);
    var onHome = /(^|\/)index\.php$/.test(window.location.pathname) || window.location.pathname === '/' ||
                 window.location.pathname === '' || window.location.pathname.slice(-1) === '/';
    if (path !== '' && !/index\.php$/.test(path) && path !== './') { return; }
    if (!onHome && path === '') { return; }

    var target = document.querySelector(id);
    if (!target) { return; }

    e.preventDefault();
    var y = target.getBoundingClientRect().top + window.scrollY - 92;
    window.scrollTo({ top: y, behavior: reduced ? 'auto' : 'smooth' });
    if (history.replaceState) {
      history.replaceState(null, '', id);
    }
  });

  /* ═══════════════════════════════════════════════
     ۸. تأخیر کوتاه برای نوار بزرگ در موبایل
        (اجرای روان‌تر انیمیشن‌های سنگین)
     ═══════════════════════════════════════════════ */
  if (window.innerWidth < 720) {
    document.querySelectorAll('.ticker__track, .band__row').forEach(function (el) {
      var dur = parseFloat(getComputedStyle(el).animationDuration) || 46;
      el.style.animationDuration = (dur * 0.65) + 's';
    });
  }

  root.classList.add('anim-ready');
})();
