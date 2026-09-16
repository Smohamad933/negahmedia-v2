<?php
/**
 * نگاه مدیا | نصب‌کننده
 * این فایل پس از نصب باید از هاست حذف شود.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
$cfgFile = __DIR__ . '/includes/config.php';

/* --------- افزودن پوشه‌های موردنیاز --------- */
foreach (['data', 'uploads', 'uploads/logos', 'uploads/projects', 'uploads/fonts'] as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        @mkdir(__DIR__ . '/' . $dir, 0755, true);
    }
}

$errors  = [];
$done    = false;
$already = false;

/* --------- بررسی نصب قبلی --------- */
try {
    require_once __DIR__ . '/includes/db.php';
    $already = db_is_installed();
} catch (Throwable $e) {
    $already = false;
}

/* --------- پردازش فرم --------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver   = ($_POST['driver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
    $siteName = trim((string) ($_POST['site_name'] ?? ''));

    $myHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $myName = trim((string) ($_POST['db_name'] ?? ''));
    $myUser = trim((string) ($_POST['db_user'] ?? ''));
    $myPass = (string) ($_POST['db_pass'] ?? '');

    $adminUser = trim((string) ($_POST['admin_user'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? ''));
    $adminMail = trim((string) ($_POST['admin_email'] ?? ''));

    if (mb_strlen($adminUser) < 3) {
        $errors[] = 'نام کاربری مدیر باید حداقل ۳ نویسه باشد.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $adminUser)) {
        $errors[] = 'نام کاربری فقط می‌تواند شامل حروف لاتین، رقم، نقطه، خط تیره و آندرلاین باشد.';
    }
    if (mb_strlen($adminPass) < 8) {
        $errors[] = 'رمز عبور باید حداقل ۸ نویسه باشد.';
    }
    if ($driver === 'mysql' && ($myName === '' || $myUser === '')) {
        $errors[] = 'نام دیتابیس و نام کاربری MySQL الزامی است.';
    }

    /* --------- تست اتصال و نصب --------- */
    if (!$errors) {
        try {
            $dsn = $driver === 'mysql'
                ? sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $myHost, $myName)
                : 'sqlite:' . __DIR__ . '/data/negah.sqlite';

            $pdo = new PDO($dsn, $driver === 'mysql' ? $myUser : null, $driver === 'mysql' ? $myPass : null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            if ($driver === 'sqlite') {
                $pdo->exec('PRAGMA journal_mode = WAL');
            }

            /* --------- نوشتن فایل پیکربندی --------- */
            $tpl = <<<'PHP'
<?php
/**
 * نگاه مدیا | پیکربندی پروژه
 * این فایل به‌صورت خودکار توسط نصب‌کننده ساخته شده است.
 */
declare(strict_types=1);

return [
    'driver'      => '%DRIVER%',
    'sqlite_path' => __DIR__ . '/../data/negah.sqlite',
    'mysql' => [
        'host'    => '%HOST%',
        'name'    => '%NAME%',
        'user'    => '%USER%',
        'pass'    => '%PASS%',
        'charset' => 'utf8mb4',
    ],
    'base_url'   => '%BASE%',
    'upload_dir' => 'uploads/',
    'timezone'   => 'Asia/Tehran',
    'debug'      => false,
];
PHP;

            $esc = static fn (string $v): string => str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
            $configBody = str_replace(
                ['%DRIVER%', '%HOST%', '%NAME%', '%USER%', '%PASS%', '%BASE%'],
                [$driver, $esc($myHost), $esc($myName), $esc($myUser), $esc($myPass), $esc((string) ($_POST['base_url'] ?? '/'))],
                $tpl
            );
            file_put_contents($cfgFile, $configBody);

            /* --------- از این پس از همان لایه پروژه استفاده می‌کنیم --------- */
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/functions.php';
            require_once __DIR__ . '/includes/schema.php';
            require_once __DIR__ . '/includes/seed.php';

            schema_create();
            seed_all($already);   // اگر قبلاً نصب بوده، داده‌ها بازنویسی می‌شوند

            if ($siteName !== '') {
                setting_save('site_name', $siteName);
            }

            /* --------- کاربر مدیر --------- */
            $exists = q1('SELECT `id` FROM `users` WHERE `username` = ?', [$adminUser]);
            if ($exists) {
                db_run('UPDATE `users` SET `password_hash`=?,`full_name`=?,`email`=?,`role`=? WHERE `id`=?', [
                    password_hash($adminPass, PASSWORD_DEFAULT),
                    $adminName,
                    $adminMail,
                    'admin',
                    (int) $exists['id'],
                ]);
            } else {
                db_run(
                    'INSERT INTO `users` (`username`,`password_hash`,`full_name`,`email`,`role`) VALUES (?,?,?,?,?)',
                    [$adminUser, password_hash($adminPass, PASSWORD_DEFAULT), $adminName, $adminMail, 'admin']
                );
            }

            /* --------- محافظت از پوشه‌ها --------- */
            $ht = "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n";
            @file_put_contents(__DIR__ . '/data/.htaccess', $ht);
            @file_put_contents(__DIR__ . '/includes/.htaccess', $ht);
            @file_put_contents(
                __DIR__ . '/uploads/.htaccess',
                "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps|pl|py|cgi|asp|sh)$\">\n  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n  </IfModule>\n</FilesMatch>\n"
            );

            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'خطا در نصب: ' . $e->getMessage();
        }
    }
}

$driverNow = (string) ($_POST['driver'] ?? ($_GET['driver'] ?? 'sqlite'));
if ($driverNow !== 'mysql') {
    $driverNow = 'sqlite';
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب | نگاه مدیا</title>
<style>
@font-face{font-family:Vazirmatn;src:url('assets/fonts/Vazirmatn-Variable.woff2') format('woff2-variations');font-weight:100 900;font-display:swap}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:#F6F4EF;padding:40px 20px;font-family:Vazirmatn,Tahoma,sans-serif;color:#171310;line-height:1.9;font-size:15.5px}
.card{max-width:640px;margin:0 auto;background:#fff;border:1px solid rgba(23,19,16,.14);padding:38px 34px}
h1{font-size:22px;margin:0 0 4px;font-weight:800}
.en{font-family:Georgia,serif;font-size:9.5px;letter-spacing:.26em;color:#6E655A;margin-bottom:26px}
fieldset{border:1px solid rgba(23,19,16,.14);padding:20px;margin:0 0 22px}
legend{font-size:13px;font-weight:700;padding:0 8px;color:#6E655A}
label{display:block;font-size:13.5px;font-weight:600;margin-bottom:6px}
input,select{width:100%;padding:11px 13px;border:1px solid rgba(23,19,16,.18);background:#fff;margin-bottom:14px;font:inherit;outline:none}
input:focus,select:focus{border-color:#A8752E}
.hint{font-size:13px;color:#6E655A;margin:-8px 0 14px}
button{padding:13px 30px;background:#171310;color:#F6F4EF;border:0;font:inherit;font-weight:600;cursor:pointer}
button:hover{background:#A8752E}
.err{padding:12px 16px;border:1px solid #BE5A50;color:#8C3330;background:#FDF3F2;margin-bottom:20px;font-size:14px}
.err ul{margin:0;padding-inline-start:18px}
.ok{padding:16px;border:1px solid #A8752E;background:rgba(168,117,46,.07);margin-bottom:20px}
ol{padding-inline-start:20px}
a{color:#A8752E}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
@media(max-width:560px){.grid2{grid-template-columns:1fr}.card{padding:26px 20px}}
code{background:#F6F4EF;padding:1px 6px;font-family:ui-monospace,Menlo,monospace;font-size:13.5px;direction:ltr;display:inline-block}
</style>
</head>
<body>
<div class="card">
  <h1>نصب نگاه مدیا</h1>
  <div class="en">INSTALLER</div>

<?php if ($done): ?>
  <div class="ok">
    <strong>نصب با موفقیت انجام شد.</strong>
    <ol>
      <li>فایل <code>install.php</code> را از هاست <strong>حذف</strong> کنید.</li>
      <li>وارد پنل مدیریت شوید و متن‌ها و لوگوها را تنظیم کنید.</li>
    </ol>
  </div>
  <a href="admin/login.php"><button type="button">ورود به پنل مدیریت</button></a>
  <a href="index.php" style="margin-inline-start:14px;font-size:14px">مشاهده سایت</a>

<?php else: ?>
  <?php if ($already): ?>
    <div class="err">
      این پروژه قبلاً نصب شده است. اگر دوباره نصب کنید، متن‌ها و داده‌های پیش‌فرض بازنویسی می‌شوند
      (لوگوها و نمونه‌کارهای آپلودی حذف نمی‌شوند).
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="err"><ul><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post">
    <fieldset>
      <legend>حساب مدیر</legend>
      <div class="grid2">
        <div>
          <label>نام کاربری</label>
          <input name="admin_user" dir="ltr" required value="<?= htmlspecialchars((string) ($_POST['admin_user'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
          <label>رمز عبور (حداقل ۸ نویسه)</label>
          <input name="admin_pass" type="password" dir="ltr" required>
        </div>
        <div>
          <label>نام و نام خانوادگی</label>
          <input name="admin_name" value="<?= htmlspecialchars((string) ($_POST['admin_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
          <label>ایمیل</label>
          <input name="admin_email" dir="ltr" value="<?= htmlspecialchars((string) ($_POST['admin_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>اطلاعات سایت</legend>
      <label>نام مجموعه</label>
      <input name="site_name" value="<?= htmlspecialchars((string) ($_POST['site_name'] ?? 'آژانس خلاق و تبلیغاتی نگاه مدیا'), ENT_QUOTES, 'UTF-8') ?>">
      <label>آدرس پایه سایت</label>
      <input name="base_url" dir="ltr" value="<?= htmlspecialchars((string) ($_POST['base_url'] ?? '/'), ENT_QUOTES, 'UTF-8') ?>">
      <div class="hint">اگر سایت در ریشه دامنه است <code>/</code> بگذارید؛ اگر داخل پوشه است مثل <code>/negah/</code>.</div>
    </fieldset>

    <fieldset>
      <legend>دیتابیس</legend>
      <label>نوع دیتابیس</label>
      <select name="driver" id="driver">
        <option value="sqlite"<?= $driverNow === 'sqlite' ? ' selected' : '' ?>>SQLite — بدون تنظیم، فایل دیتابیس در پوشه data ساخته می‌شود</option>
        <option value="mysql"<?= $driverNow === 'mysql' ? ' selected' : '' ?>>MySQL / MariaDB — مناسب هاست cPanel</option>
      </select>
      <div id="mybox" style="display:<?= $driverNow === 'mysql' ? 'block' : 'none' ?>">
        <div class="grid2">
          <div><label>هاست</label><input name="db_host" dir="ltr" value="<?= htmlspecialchars((string) ($_POST['db_host'] ?? 'localhost'), ENT_QUOTES, 'UTF-8') ?>"></div>
          <div><label>نام دیتابیس</label><input name="db_name" dir="ltr" value="<?= htmlspecialchars((string) ($_POST['db_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
          <div><label>نام کاربری</label><input name="db_user" dir="ltr" value="<?= htmlspecialchars((string) ($_POST['db_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
          <div><label>رمز عبور</label><input name="db_pass" type="password" dir="ltr"></div>
        </div>
        <div class="hint">نام دیتابیس و کاربر را در cPanel → MySQL Databases بسازید (کاراکترست utf8mb4).</div>
      </div>
    </fieldset>

    <button type="submit">شروع نصب</button>
  </form>

  <script>
  document.getElementById('driver').addEventListener('change', function () {
    document.getElementById('mybox').style.display = this.value === 'mysql' ? 'block' : 'none';
  });
  </script>
<?php endif; ?>
</div>
</body>
</html>
