<?php
/**
 * نگاه مدیا | داشبورد پنل
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

$counts = [
    'clients'   => (int) qv('SELECT COUNT(*) FROM `clients`', [], 0),
    'with_logo' => (int) qv("SELECT COUNT(*) FROM `clients` WHERE (`logo_file` IS NOT NULL AND `logo_file` <> '') OR (`logo_url` IS NOT NULL AND `logo_url` <> '')", [], 0),
    'groups'    => (int) qv('SELECT COUNT(*) FROM `client_groups`', [], 0),
    'services'  => (int) qv('SELECT COUNT(*) FROM `services`', [], 0),
    'projects'  => (int) qv('SELECT COUNT(*) FROM `projects`', [], 0),
    'messages'  => (int) qv('SELECT COUNT(*) FROM `messages`', [], 0),
    'unread'    => (int) qv('SELECT COUNT(*) FROM `messages` WHERE `is_read` = 0', [], 0),
    'featured'  => (int) qv('SELECT COUNT(*) FROM `clients` WHERE `featured` = 1', [], 0),
];
$recent = q('SELECT * FROM `messages` ORDER BY `id` DESC LIMIT 6');
$driver = db_driver() === 'mysql' ? 'MySQL' : 'SQLite';
$noLogo = (int) qv("SELECT COUNT(*) FROM `clients` WHERE (`logo_file` IS NULL OR `logo_file` = '') AND (`logo_url` IS NULL OR `logo_url` = '')", [], 0);

/* ---------- آمار بازدید عمومی سایت ---------- */
$today     = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('-6 days'));
$monthStart = date('Y-m-d', strtotime('-29 days'));
$viewStats = [
    'total'    => (int) qv('SELECT COUNT(*) FROM `site_views`', [], 0),
    'today'    => (int) qv('SELECT COUNT(*) FROM `site_views` WHERE `viewed_date` = ?', [$today], 0),
    'week'     => (int) qv('SELECT COUNT(*) FROM `site_views` WHERE `viewed_date` >= ?', [$weekStart], 0),
    'visitors' => (int) qv('SELECT COUNT(DISTINCT `visitor_hash`) FROM `site_views`', [], 0),
];
$dailyRaw = q(
    'SELECT `viewed_date`, COUNT(*) AS `view_count`
     FROM `site_views` WHERE `viewed_date` >= ?
     GROUP BY `viewed_date` ORDER BY `viewed_date`',
    [$monthStart]
);
$dailyMap = [];
foreach ($dailyRaw as $day) {
    $dailyMap[(string) $day['viewed_date']] = (int) $day['view_count'];
}
$viewDays = [];
$viewMax = 1;
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime('-' . $i . ' days'));
    $count = $dailyMap[$date] ?? 0;
    $viewDays[] = ['date' => $date, 'count' => $count];
    $viewMax = max($viewMax, $count);
}
$topPages = q(
    'SELECT `path`, COUNT(*) AS `view_count`
     FROM `site_views` WHERE `viewed_date` >= ?
     GROUP BY `path` ORDER BY `view_count` DESC, `path` ASC LIMIT 6',
    [$monthStart]
);

admin_head('داشبورد');
?>

<div class="cards">
  <div class="card">
    <p class="card__lbl">CLIENTS</p>
    <p class="card__val"><?= e(fa_digits((string) $counts['clients'])) ?></p>
    <p class="card__sub">
      <a href="clients.php">در <?= e(fa_digits((string) $counts['groups'])) ?> دسته — <?= e(fa_digits((string) $counts['featured'])) ?> ویژه ›</a>
    </p>
  </div>
  <div class="card">
    <p class="card__lbl">LOGOS</p>
    <p class="card__val"><?= e(fa_digits((string) $counts['with_logo'])) ?></p>
    <p class="card__sub">
      <?php if ($noLogo > 0): ?>
        <a href="clients.php"><?= e(fa_digits((string) $noLogo)) ?> مورد بدون لوگو ›</a>
      <?php else: ?>
        همه لوگوها ثبت شده‌اند
      <?php endif; ?>
    </p>
  </div>
  <div class="card">
    <p class="card__lbl">SERVICES</p>
    <p class="card__val"><?= e(fa_digits((string) $counts['services'])) ?></p>
    <p class="card__sub">خدمت فعال در سایت</p>
  </div>
  <div class="card">
    <p class="card__lbl">MESSAGES</p>
    <p class="card__val"><?= e(fa_digits((string) $counts['messages'])) ?></p>
    <p class="card__sub">
      <?php if ($counts['unread'] > 0): ?>
        <a href="messages.php"><?= e(fa_digits((string) $counts['unread'])) ?> پیام خوانده‌نشده ›</a>
      <?php else: ?>
        پیام جدیدی نیست
      <?php endif; ?>
    </p>
  </div>
  <div class="card">
    <p class="card__lbl">PAGE VIEWS</p>
    <p class="card__val"><?= e(fa_digits((string) $viewStats['total'])) ?></p>
    <p class="card__sub"><a href="#site-views"><?= e(fa_digits((string) $viewStats['today'])) ?> بازدید امروز ›</a></p>
  </div>
</div>

<!-- ══════════════ بازدید سایت ══════════════ -->
<div class="panel views-panel" id="site-views">
  <h2>
    بازدید سایت
    <span class="tag">۳۰ روز اخیر</span>
    <span class="sp"></span>
    <span class="muted" style="font-size:12px;font-weight:400">فقط صفحات عمومی؛ بازدید پنل محاسبه نمی‌شود</span>
  </h2>

  <div class="view-cards">
    <div class="view-card">
      <span class="view-card__label">TOTAL VIEWS</span>
      <strong><?= e(fa_digits((string) $viewStats['total'])) ?></strong>
      <small>کل بازدید صفحات</small>
    </div>
    <div class="view-card view-card--accent">
      <span class="view-card__label">TODAY</span>
      <strong><?= e(fa_digits((string) $viewStats['today'])) ?></strong>
      <small>بازدید امروز</small>
    </div>
    <div class="view-card">
      <span class="view-card__label">LAST 7 DAYS</span>
      <strong><?= e(fa_digits((string) $viewStats['week'])) ?></strong>
      <small>بازدید هفت روز اخیر</small>
    </div>
    <div class="view-card">
      <span class="view-card__label">VISITORS</span>
      <strong><?= e(fa_digits((string) $viewStats['visitors'])) ?></strong>
      <small>بازدیدکننده تقریبی</small>
    </div>
  </div>

  <div class="views-detail">
    <div>
      <p class="view-chart__title">روند روزانه</p>
      <div class="view-chart" role="img" aria-label="نمودار بازدید ۳۰ روز اخیر">
        <?php foreach ($viewDays as $day): ?>
          <?php $height = $day['count'] > 0 ? max(8, (int) round(($day['count'] / $viewMax) * 100)) : 2; ?>
          <div class="view-chart__item" title="<?= e(fa_digits($day['date'])) ?>: <?= e(fa_digits((string) $day['count'])) ?> بازدید">
            <span class="view-chart__bar" style="height:<?= $height ?>%"></span>
            <small><?= e(fa_digits(date('m/d', strtotime($day['date'])))) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="top-pages">
      <p class="view-chart__title">صفحات پربازدید در ۳۰ روز اخیر</p>
      <?php if (!$topPages): ?>
        <p class="muted">هنوز بازدیدی ثبت نشده است.</p>
      <?php else: ?>
        <ol>
          <?php foreach ($topPages as $page): ?>
            <?php
              $path = (string) $page['path'];
              $label = $path === '/' || substr($path, -10) === '/index.php'
                  ? 'صفحه اصلی'
                  : (strpos($path, 'brand.php') !== false ? 'صفحه برند' : (strpos($path, 'clients.php') !== false ? 'همراهان' : $path));
            ?>
            <li>
              <span><?= e($label) ?></span>
              <strong><?= e(fa_digits((string) $page['view_count'])) ?></strong>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="panel">
  <h2>
    آخرین پیام‌های دریافتی
    <span class="sp"></span>
    <a class="btn btn--ghost btn--sm" href="messages.php">همه پیام‌ها</a>
  </h2>

  <?php if (!$recent): ?>
    <?= admin_empty('هنوز پیامی از فرم تماس سایت دریافت نشده است.') ?>
  <?php else: ?>
    <div class="tablewrap">
      <table>
        <thead>
          <tr><th>وضعیت</th><th>نام</th><th>تماس</th><th>موضوع</th><th>تاریخ</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $m): ?>
            <tr>
              <td>
                <?= (int) $m['is_read'] === 1
                    ? '<span class="tag tag--ok">خوانده‌شده</span>'
                    : '<span class="tag tag--new">جدید</span>' ?>
              </td>
              <td><?= e($m['name']) ?></td>
              <td dir="ltr" class="num"><?= e(fa_digits((string) $m['phone'])) ?></td>
              <td><?= e($m['subject'] !== '' ? $m['subject'] : '—') ?></td>
              <td class="num"><?= e(fa_digits((string) $m['created_at'])) ?></td>
              <td><a class="btn btn--ghost btn--sm" href="messages.php?id=<?= (int) $m['id'] ?>">مشاهده</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2>شروع سریع</h2>
  <div class="row">
    <div class="f">
      <label>۱. متن‌ها و اطلاعات تماس</label>
      <p class="muted">تیتر هیرو، متن معرفی، شماره تماس و شبکه‌های اجتماعی را در این بخش تنظیم کنید.</p>
      <p style="margin-top:12px"><a class="btn btn--sm" href="settings.php">متن‌ها و تماس</a></p>
    </div>
    <div class="f">
      <label>۲. برندها و صفحه اختصاصی</label>
      <p class="muted">لوگو، معرفی، کاور و صفحه مستقل هر کارفرما را بسازید؛ سپس کارهایش را در گالری جداگانه بگذارید.</p>
      <p style="margin-top:12px"><a class="btn btn--sm" href="clients.php">برندها و لوگوها</a> <a class="btn btn--ghost btn--sm" href="gallery.php">گالری برندها</a></p>
    </div>
    <div class="f">
      <label>۳. فونت، رنگ و CSS</label>
      <p class="muted">فونت اختصاصی را آپلود کنید، رنگ تأکیدی را تغییر دهید یا CSS دلخواه اضافه کنید.</p>
      <p style="margin-top:12px"><a class="btn btn--sm" href="appearance.php">ظاهر، فونت و CSS</a></p>
    </div>
    <div class="f">
      <label>۴. نمونه‌کارها</label>
      <p class="muted">پروژه‌های اجراشده را با تصویر، دسته‌بندی و لینک اضافه کنید.</p>
      <p style="margin-top:12px"><a class="btn btn--sm" href="projects.php">نمونه‌کارها</a></p>
    </div>
  </div>
</div>

<div class="panel">
  <h2>وضعیت سیستم</h2>
  <div class="tablewrap">
    <table>
      <tbody>
        <tr><th>نسخه PHP</th><td dir="ltr" class="num"><?= e(PHP_VERSION) ?></td></tr>
        <tr><th>دیتابیس</th><td><?= e($driver) ?></td></tr>
        <tr><th>پوشه آپلود</th><td><?= is_writable(upload_path()) ? '<span class="tag tag--ok">قابل نوشتن</span>' : '<span class="tag">بدون دسترسی نوشتن</span>' ?></td></tr>
        <tr><th>افزونه Zip</th><td><?= class_exists('ZipArchive') ? '<span class="tag tag--ok">فعال</span>' : '<span class="tag">غیرفعال</span>' ?></td></tr>
        <tr><th>حالت نمایش خطا</th><td><?= cfg('debug') ? 'روشن (برای توسعه)' : 'خاموش' ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php admin_foot(); ?>
