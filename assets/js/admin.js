/* نگاه مدیا | اسکریپت پنل مدیریت */
(function () {
  'use strict';

  /* ---------- باز و بسته کردن منوی موبایل ---------- */
  var toggle = document.querySelector('[data-side-toggle]');
  var side   = document.querySelector('[data-side]');

  if (toggle && side) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      side.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (side.classList.contains('open') && !side.contains(e.target) && e.target !== toggle) {
        side.classList.remove('open');
      }
    });
  }

  /* ---------- پیش‌نمایش تصویر انتخابی ---------- */
  document.querySelectorAll('input[type="file"][accept*="image"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !file.type.match(/^image\//)) { return; }

      var box = input.parentNode.querySelector('.file-preview');
      if (!box) {
        box = document.createElement('div');
        box.className = 'file-preview';
        box.style.marginTop = '10px';
        input.parentNode.appendChild(box);
      }
      var url = URL.createObjectURL(file);
      box.innerHTML = '<img src="' + url + '" alt="" style="max-height:70px;width:auto;background:#F6F4EF;border:1px solid rgba(22,19,15,.13);padding:5px">' +
                      '<div class="muted" style="font-size:12px">' + file.name + '</div>';
    });
  });

  /* ---------- تأیید عملیات حساس ---------- */
  document.querySelectorAll('[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  /* ---------- جستجوی زنده در جدول‌ها ---------- */
  document.querySelectorAll('[data-filter]').forEach(function (input) {
    var table = document.querySelector(input.getAttribute('data-filter'));
    if (!table) { return; }
    input.addEventListener('input', function () {
      var term = input.value.trim().toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (tr) {
        tr.style.display = tr.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
      });
    });
  });

  /* ---------- شمارش نویسه ---------- */
  document.querySelectorAll('[data-counter]').forEach(function (field) {
    var out = document.querySelector(field.getAttribute('data-counter'));
    if (!out) { return; }
    var update = function () {
      var max = parseInt(field.getAttribute('maxlength') || '0', 10);
      out.textContent = max ? (field.value.length + ' / ' + max) : String(field.value.length);
    };
    field.addEventListener('input', update);
    update();
  });
})();
