<?php
/**
 * نگاه مدیا | پاصفحه سایت
 * بدون منو — فقط برند، اطلاعات تماس و شبکه‌های اجتماعی.
 */
$siteShort = setting('site_short', 'نگاه مدیا');
$phone = setting('phone');
$email = setting('email');
$socials = array_filter([
    'اینستاگرام' => setting('instagram'),
    'تلگرام'     => setting('telegram'),
    'لینکدین'    => setting('linkedin'),
]);
?>
<footer class="foot">
  <div class="wrap">

    <div class="foot__top">
      <!-- ────── برند ────── -->
      <div class="foot__brand">
        <a class="foot__logo" href="<?= e(url()) ?>" aria-label="<?= e($siteShort) ?>">
          <span class="foot__mark" aria-hidden="true">
            <svg viewBox="0 0 40 40" role="img" aria-hidden="true" focusable="false">
              <rect width="40" height="40" fill="#F0EBE3"/>
              <text x="20" y="28" text-anchor="middle" font-size="22" font-weight="700"
                    font-family="Vazirmatn, Tahoma, sans-serif" fill="#0C0A09">ن</text>
              <rect x="0" y="36" width="40" height="4" fill="#A8752E" class="foot__mark-bar"/>
            </svg>
          </span>
          <span class="foot__brand-txt">
            <strong><?= e($siteShort) ?></strong>
            <small>NEGAH&nbsp;MEDIA</small>
          </span>
        </a>

        <p class="foot__quote"><?= e(setting('footer_note', setting('hero_tagline'))) ?></p>
      </div>

      <!-- ────── تماس ────── -->
      <div class="foot__col">
        <p class="label">تماس</p>
        <?php if ($phone !== ''): ?>
          <p><a href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a></p>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <p><a href="mailto:<?= e($email) ?>" dir="ltr"><?= e($email) ?></a></p>
        <?php endif; ?>
        <?php if (setting('address') !== ''): ?>
          <p class="foot__mut"><?= e(setting('address')) ?></p>
        <?php endif; ?>
      </div>

      <!-- ────── شبکه‌های اجتماعی ────── -->
      <div class="foot__col">
        <p class="label">شبکه‌های اجتماعی</p>
        <?php if ($socials): ?>
          <?php foreach ($socials as $label => $href): ?>
            <p><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a></p>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="foot__mut">—</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="foot__bar">
      <span><?= e(setting('footer_text', '© نگاه مدیا — تمام حقوق محفوظ است.')) ?></span>
      <a class="foot__admin" href="<?= e(url('admin/login.php')) ?>">ورود مدیریت</a>
    </div>
  </div>
</footer>

<button class="totop" type="button" aria-label="بازگشت به بالا" data-top>↑</button>

<script src="<?= e(url('assets/js/app.js?v=' . setting('style_version', '1'))) ?>" defer></script>
</body>
</html>
