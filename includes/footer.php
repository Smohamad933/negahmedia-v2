<?php
/**
 * نگاه مدیا | پاصفحه سایت
 */
$siteShort = setting('site_short', 'نگاه مدیا');
$phone = setting('phone');
$email = setting('email');
$socials = [
    'اینستاگرام' => setting('instagram'),
    'تلگرام'     => setting('telegram'),
    'لینکدین'    => setting('linkedin'),
];
$socials = array_filter($socials);
?>
<footer class="foot">
  <div class="wrap">
    <div class="foot__top">
      <div class="foot__claim">
        <p class="label">NEGAH MEDIA</p>
        <p class="foot__quote"><?= e(setting('footer_note', setting('hero_tagline'))) ?></p>
      </div>

      <div class="foot__col">
        <p class="label">CONTACT</p>
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

      <div class="foot__col">
        <p class="label">SOCIAL</p>
        <?php if ($socials): ?>
          <?php foreach ($socials as $label => $href): ?>
            <p><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a></p>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="foot__mut">—</p>
        <?php endif; ?>
      </div>

      <div class="foot__col">
        <p class="label">MENU</p>
        <p><a href="#clients">همراهان</a></p>
        <p><a href="#services">خدمات</a></p>
        <p><a href="#process">مراحل همکاری</a></p>
        <p><a href="#contact">شروع یک پروژه</a></p>
      </div>
    </div>

    <div class="foot__bar">
      <span><?= e(setting('footer_text', '© نگاه مدیا — تمام حقوق محفوظ است.')) ?></span>
      <a class="foot__admin" href="<?= e(url('admin/login.php')) ?>">ورود مدیریت</a>
    </div>
  </div>
</footer>

<button class="totop" type="button" aria-label="بازگشت به بالا" data-top>↑</button>

<script src="<?= e(url('assets/js/app.js?v=3')) ?>" defer></script>
</body>
</html>
