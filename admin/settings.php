<?php
/**
 * نگاه مدیا | تنظیمات متن‌ها، هیرو و اطلاعات تماس
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

/** ساختار فیلدها: کلید => [برچسب، نوع، راهنما، dir] */
$fields = [
    'hero' => [
        'title'  => 'بخش هیرو (بالای صفحه)',
        'fields' => [
            'hero_kicker'  => ['برچسب بالای تیتر', 'text', 'متن کوچک لاتین بالای تیتر اصلی.', 'ltr'],
            'hero_title'   => ['تیتر اصلی', 'text', 'مهم‌ترین جمله سایت؛ کوتاه و اثرگذار باشد.', ''],
            'hero_sub'     => ['متن توضیحی', 'textarea', 'زیر تیتر اصلی نمایش داده می‌شود.', ''],
            'hero_tagline' => ['شعار فرعی', 'text', 'در ستون کنار هیرو نمایش داده می‌شود.', ''],
            'cta_primary'  => ['متن دکمه اصلی', 'text', '', ''],
            'cta_secondary'=> ['متن دکمه دوم', 'text', '', ''],
        ],
    ],
    'about' => [
        'title'  => 'درباره نگاه و آمار',
        'fields' => [
            'manifesto'  => ['شعار / مانیفست', 'text', 'در بخش «درباره نگاه» با تایپوگرافی درشت نمایش داده می‌شود.', ''],
            'about_text' => ['متن معرفی', 'textarea', 'توضیح کوتاه درباره تیم و روش کار.', ''],
            'clients_lead' => ['توضیح بخش همراهان', 'textarea', '', ''],
        ],
    ],
    'identity' => [
        'title'  => 'نام و عنوان‌ها',
        'fields' => [
            'site_name'  => ['نام کامل مجموعه', 'text', 'در عنوان مرورگر و متادیتا استفاده می‌شود.', ''],
            'site_short' => ['نام کوتاه', 'text', 'در لوگوتایپ بالای سایت و فوتر.', ''],
            'meta_description' => ['توضیح متای سایت (SEO)', 'textarea', 'حدود ۱۵۰ نویسه؛ در نتایج گوگل نمایش داده می‌شود.', ''],
        ],
    ],
    'contact' => [
        'title'  => 'اطلاعات تماس',
        'fields' => [
            'phone'    => ['شماره تماس', 'text', 'در هدر، فوتر و بخش تماس.', 'ltr'],
            'email'    => ['ایمیل', 'text', '', 'ltr'],
            'address'  => ['نشانی / شهر', 'text', '', ''],
        ],
    ],
    'social' => [
        'title'  => 'شبکه‌های اجتماعی',
        'fields' => [
            'instagram' => ['اینستاگرام', 'text', 'آدرس کامل صفحه.', 'ltr'],
            'telegram'  => ['تلگرام', 'text', '', 'ltr'],
            'linkedin'  => ['لینکدین', 'text', '', 'ltr'],
        ],
    ],
    'footer' => [
        'title'  => 'پاصفحه',
        'fields' => [
            'footer_note' => ['جمله پاصفحه', 'text', 'جمله‌ای که در فوتر با فونت درشت می‌آید.', ''],
            'footer_text' => ['متن کپی‌رایت', 'text', '', ''],
        ],
    ],
];

/* ---------- ذخیره ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $saved = 0;
    foreach ($fields as $group) {
        foreach (array_keys($group['fields']) as $key) {
            if (array_key_exists($key, $_POST)) {
                setting_save($key, trim((string) $_POST[$key]));
                $saved++;
            }
        }
    }
    flash('تنظیمات ذخیره شد (' . fa_digits((string) $saved) . ' مورد).');
    redirect('settings.php');
}

admin_head('متن‌ها و تماس');
?>

<form method="post">
  <?= csrf_field() ?>

  <?php foreach ($fields as $groupKey => $group): ?>
    <div class="panel">
      <h2><?= e($group['title']) ?></h2>

      <?php foreach ($group['fields'] as $key => [$label, $type, $hint, $dir]): ?>
        <div class="f">
          <label for="k-<?= e($key) ?>"><?= e($label) ?></label>

          <?php if ($type === 'textarea'): ?>
            <textarea id="k-<?= e($key) ?>" name="<?= e($key) ?>" rows="<?= $key === 'about_text' ? 4 : 3 ?>"<?= $dir !== '' ? ' dir="' . e($dir) . '"' : '' ?>><?= e(setting($key)) ?></textarea>
          <?php else: ?>
            <input id="k-<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>"<?= $dir !== '' ? ' dir="' . e($dir) . '"' : '' ?>>
          <?php endif; ?>

          <?php if ($hint !== ''): ?><p class="hint"><?= e($hint) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="act">
    <button class="btn" type="submit">ذخیره همه تنظیمات</button>
    <a class="btn btn--ghost" href="<?= e(url()) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
  </div>
</form>

<?php admin_foot(); ?>
