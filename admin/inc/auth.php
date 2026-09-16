<?php
/**
 * نگاه مدیا | احراز هویت و قالب پنل مدیریت
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$USER = current_user();
if ($USER === null) {
    $_SESSION = [];
    redirect('login.php');
}

/** فهرست منوی پنل */
function admin_menu(): array
{
    return [
        'index.php'    => ['داشبورد', 'خانه'],
        'settings.php' => ['متن‌ها و تماس', 'تنظیمات'],
        'clients.php'  => ['برندها و لوگوها', 'محتوا'],
        'content.php'  => ['خدمات، آمار و مراحل', 'محتوا'],
        'projects.php' => ['نمونه‌کارها', 'محتوا'],
        'messages.php' => ['پیام‌های دریافتی', 'ارتباط'],
        'users.php'    => ['کاربران پنل', 'سیستم'],
        'backup.php'   => ['پشتیبان‌گیری', 'سیستم'],
    ];
}

function admin_head(string $title): void
{
    global $USER;
    $current = basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));
    $fl = flash();
    $menu = admin_menu();
    $unread = (int) qv('SELECT COUNT(*) FROM `messages` WHERE `is_read` = 0', [], 0);
    ?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | پنل نگاه مدیا</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css?v=3')) ?>">
</head>
<body>
<div class="shell">

  <aside class="side" data-side>
    <div class="side__top">
      <a class="side__brand" href="<?= e(url('admin/index.php')) ?>">
        <span class="side__mark" aria-hidden="true">ن</span>
        <span>
          <strong>نگاه مدیا</strong>
          <small>CONTENT PANEL</small>
        </span>
      </a>
    </div>

    <nav class="side__nav">
      <?php
      $lastGroup = '';
      foreach ($menu as $file => $info):
          [$label, $group] = $info;
          if ($group !== $lastGroup):
              $lastGroup = $group;
              ?>
              <span class="side__group"><?= e($group) ?></span>
          <?php endif; ?>
          <a class="<?= $current === $file ? 'on' : '' ?>" href="<?= e($file) ?>">
            <span><?= e($label) ?></span>
            <?php if ($file === 'messages.php' && $unread > 0): ?>
              <span class="badge"><?= e(fa_digits((string) $unread)) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
    </nav>

    <div class="side__foot">
      <p class="side__user">
        <span><?= e($USER['full_name'] !== '' ? $USER['full_name'] : $USER['username']) ?></span>
        <small><?= $USER['role'] === 'admin' ? 'مدیر کل' : 'ویرایشگر' ?></small>
      </p>
      <a class="side__link" href="<?= e(url()) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
      <a class="side__link" href="logout.php">خروج از حساب</a>
    </div>
  </aside>

  <main class="main">
    <header class="main__head">
      <button class="main__burger" type="button" data-side-toggle aria-label="منو">☰</button>
      <h1><?= e($title) ?></h1>
      <?php if (!empty($_SESSION['head_action'])): ?>
        <div class="main__act"><?= $_SESSION['head_action'] ?></div>
      <?php endif; ?>
    </header>

    <?php if ($fl): ?>
      <div class="flash flash--<?= e($fl['type'] ?? 'ok') ?>"><?= e($fl['msg']) ?></div>
    <?php endif; ?>
<?php
}

function admin_foot(): void
{
    ?>
  </main>
</div>
<script src="<?= e(url('assets/js/admin.js?v=3')) ?>" defer></script>
</body>
</html>
<?php
}

/** جدول ساده */
function admin_empty(string $text): string
{
    return '<p class="empty">' . e($text) . '</p>';
}
