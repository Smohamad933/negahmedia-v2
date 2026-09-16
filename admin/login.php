<?php
/**
 * نگاه مدیا | ورود به پنل مدیریت
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in() && current_user() !== null) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $_SESSION['tries']      = (int) ($_SESSION['tries'] ?? 0);
    $_SESSION['lock_until'] = (int) ($_SESSION['lock_until'] ?? 0);

    if ($_SESSION['lock_until'] > time()) {
        $wait  = (int) ceil(($_SESSION['lock_until'] - time()) / 60);
        $error = 'تلاش‌های ناموفق زیاد بود. ' . fa_digits((string) max(1, $wait)) . ' دقیقه دیگر تلاش کنید.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $row      = $username !== '' ? q1('SELECT * FROM `users` WHERE `username` = ?', [$username]) : null;

        if ($row !== null && password_verify($password, (string) $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid']   = (int) $row['id'];
            $_SESSION['tries'] = 0;
            unset($_SESSION['lock_until']);

            db_run('UPDATE `users` SET `last_login` = ' . (db_driver() === 'mysql' ? 'NOW()' : "datetime('now','localtime')") . ' WHERE `id` = ?', [(int) $row['id']]);

            redirect('index.php');
        }

        $_SESSION['tries']++;
        if ($_SESSION['tries'] >= 5) {
            $_SESSION['lock_until'] = time() + 300;
            $_SESSION['tries'] = 0;
        }
        $error = 'نام کاربری یا رمز عبور نادرست است.';
    }
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>ورود به پنل | نگاه مدیا</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css?v=3')) ?>">
<style>
  .login { min-height: 100vh; display: grid; place-items: center; padding: 26px; }
  .login__card { width: 100%; max-width: 400px; background: #fff; border: 1px solid var(--line); padding: 40px 34px; }
  .login__brand { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
  .login__mark { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; background: var(--ink); color: var(--paper); font-size: 21px; font-weight: 700; padding-bottom: 3px; }
  .login__brand strong { display: block; font-size: 17px; }
  .login__brand small { display: block; font-family: var(--serif); font-size: 8.5px; letter-spacing: .26em; color: var(--muted); }
  .login__card .btn { width: 100%; margin-top: 6px; }
  .login__back { display: block; text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--muted); }
  .login__back:hover { color: var(--accent); }
  .login__foot { margin-top: 26px; padding-top: 18px; border-top: 1px solid var(--line-soft); font-size: 12.5px; color: var(--muted); text-align: center; }
</style>
</head>
<body>
<div class="login">
  <form class="login__card" method="post" autocomplete="on">
    <?= csrf_field() ?>

    <div class="login__brand">
      <span class="login__mark" aria-hidden="true">ن</span>
      <span>
        <strong>نگاه مدیا</strong>
        <small>CONTENT PANEL</small>
      </span>
    </div>

    <?php if ($error !== ''): ?>
      <div class="flash flash--err"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="f">
      <label for="u">نام کاربری</label>
      <input id="u" name="username" dir="ltr" required autofocus autocomplete="username">
    </div>

    <div class="f">
      <label for="p">رمز عبور</label>
      <input id="p" name="password" type="password" dir="ltr" required autocomplete="current-password">
    </div>

    <button class="btn" type="submit">ورود به پنل</button>
    <a class="login__back" href="<?= e(url()) ?>">بازگشت به سایت</a>

    <p class="login__foot">دسترسی این صفحه محدود است و در موتورهای جستجو ثبت نمی‌شود.</p>
  </form>
</div>
</body>
</html>
