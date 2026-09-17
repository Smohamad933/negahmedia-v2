<?php
/**
 * نگاه مدیا | تنظیم نام کاربری و رمز عبور جدید
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in() && current_user() !== null) {
    redirect('index.php');
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenOk = (bool) preg_match('/^[a-f0-9]{64}$/i', $token);
$reset = $tokenOk
    ? q1(
        'SELECT r.* FROM `password_resets` r INNER JOIN `users` u ON u.id = r.user_id
         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?',
        [hash('sha256', $token), date('Y-m-d H:i:s')]
    )
    : null;

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirmation'] ?? '');

    if ($reset === null) {
        $errors[] = 'این لینک بازیابی معتبر نیست یا زمان استفاده از آن تمام شده است.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
        $errors[] = 'نام کاربری جدید باید ۳ تا ۶۰ نویسه لاتین، رقم یا . _ - باشد.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'رمز عبور باید حداقل ۸ نویسه باشد.';
    }
    if ($password !== $confirm) {
        $errors[] = 'تکرار رمز عبور با رمز جدید یکسان نیست.';
    }
    if (!$errors && $reset !== null && q1(
        'SELECT `id` FROM `users` WHERE `username` = ? AND `id` <> ?',
        [$username, (int) $reset['user_id']]
    ) !== null) {
        $errors[] = 'این نام کاربری قبلاً استفاده شده است.';
    }

    if (!$errors && $reset !== null) {
        $now = date('Y-m-d H:i:s');
        db_run(
            'UPDATE `users` SET `username`=?,`password_hash`=? WHERE `id`=?',
            [$username, password_hash($password, PASSWORD_DEFAULT), (int) $reset['user_id']]
        );
        db_run('UPDATE `password_resets` SET `used_at`=? WHERE `user_id`=? AND `used_at` IS NULL', [$now, (int) $reset['user_id']]);

        // هر نشست قبلی و قفل ورود این مرورگر بی‌اعتبار می‌شود.
        $_SESSION = [];
        session_regenerate_id(true);
        flash('نام کاربری و رمز عبور با موفقیت تغییر کرد. اکنون با اطلاعات جدید وارد شوید.');
        redirect('login.php');
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>تنظیم نام کاربری و رمز جدید | نگاه مدیا</title>
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css?v=6')) ?>">
<style>
  .login { min-height: 100vh; display: grid; place-items: center; padding: 26px; }
  .login__card { width: 100%; max-width: 440px; background: #fff; border: 1px solid var(--line); padding: 40px 34px; }
  .login__brand { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
  .login__mark { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; background: var(--ink); color: var(--paper); font-size: 21px; font-weight: 700; padding-bottom: 3px; }
  .login__brand strong { display: block; font-size: 17px; }
  .login__brand small { display: block; font-family: var(--serif); font-size: 8.5px; letter-spacing: .26em; color: var(--muted); }
  .login__title { font-size: 22px; line-height: 1.5; margin: 0 0 9px; }
  .login__lead { color: var(--muted); font-size: 14px; line-height: 1.95; margin-bottom: 24px; }
  .login__card .btn { width: 100%; margin-top: 6px; }
  .login__back { display: block; text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--muted); }
  .login__back:hover { color: var(--accent); }
  .login__foot { margin-top: 26px; padding-top: 18px; border-top: 1px solid var(--line-soft); font-size: 12.5px; color: var(--muted); line-height: 1.9; }
  .recovery-hint { margin-top: 7px; color: var(--muted); font-size: 12.5px; line-height: 1.8; }
</style>
</head>
<body>
<div class="login">
  <form class="login__card" method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="login__brand">
      <span class="login__mark" aria-hidden="true">ن</span>
      <span>
        <strong>نگاه مدیا</strong>
        <small>CONTENT PANEL</small>
      </span>
    </div>

    <h1 class="login__title">تنظیم نام کاربری و رمز جدید</h1>
    <?php if ($reset !== null): ?>
      <p class="login__lead">نام کاربری و رمز عبور جدیدی برای ورود به پنل مدیریت انتخاب کنید.</p>
    <?php else: ?>
      <p class="login__lead">این لینک بازیابی معتبر نیست یا منقضی شده است. دوباره درخواست لینک بازیابی بدهید.</p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="flash flash--err">
        <ul style="margin:0;padding-inline-start:18px"><li><?= e(implode(' ', $errors)) ?></li></ul>
      </div>
    <?php endif; ?>

    <?php if ($reset !== null): ?>
      <div class="f">
        <label for="username">نام کاربری جدید</label>
        <input id="username" name="username" type="text" dir="ltr" required minlength="3" maxlength="60" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
        <p class="recovery-hint">۳ تا ۶۰ نویسه لاتین، رقم یا نشانه‌های . _ -</p>
      </div>
      <div class="f">
        <label for="password">رمز عبور جدید</label>
        <input id="password" name="password" type="password" dir="ltr" required minlength="8" autocomplete="new-password">
      </div>
      <div class="f">
        <label for="password_confirmation">تکرار رمز عبور جدید</label>
        <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" required minlength="8" autocomplete="new-password">
      </div>
      <button class="btn" type="submit">ذخیره نام کاربری و رمز جدید</button>
    <?php endif; ?>

    <a class="login__back" href="forgot.php">درخواست بازیابی جدید</a>
    <p class="login__foot">نام کاربری باید ۳ تا ۶۰ نویسه باشد و رمز عبور حداقل ۸ نویسه داشته باشد.</p>
  </form>
</div>
</body>
</html>
