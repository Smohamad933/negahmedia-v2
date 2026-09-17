<?php
/**
 * نگاه مدیا | بازیابی رمز با شماره و کد ثابت
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in() && current_user() !== null) {
    redirect('index.php');
}

$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $phone = trim((string) ($_POST['phone'] ?? ''));
    $recoveryCode = trim((string) ($_POST['recovery_code'] ?? ''));
    $notice = 'اگر شماره و کد بازیابی درست باشد، به صفحه ساخت رمز جدید منتقل می‌شوید.';

    if (verify_recovery_phone($phone) && verify_recovery_code($recoveryCode)) {
        // کد ثابت فقط حساب مدیر اصلی را باز می‌کند؛ سپس لینک یک‌بارمصرف ساخته می‌شود.
        $user = q1('SELECT `id` FROM `users` WHERE `role` = ? ORDER BY `id` LIMIT 1', ['admin']);
        if ($user !== null) {
            $rawToken = issue_password_reset_token((int) $user['id']);
            if ($rawToken !== null) {
                redirect('reset.php?token=' . rawurlencode($rawToken));
            }
        }
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>بازیابی رمز عبور | نگاه مدیا</title>
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

    <div class="login__brand">
      <span class="login__mark" aria-hidden="true">ن</span>
      <span>
        <strong>نگاه مدیا</strong>
        <small>CONTENT PANEL</small>
      </span>
    </div>

    <h1 class="login__title">بازیابی رمز عبور</h1>
    <p class="login__lead">شماره بازیابی و رمز ثابت را وارد کنید تا امکان ساخت رمز جدید فعال شود.</p>

    <?php if ($notice !== ''): ?>
      <div class="flash"><?= e($notice) ?></div>
    <?php endif; ?>

    <div class="f">
      <label for="phone">شماره بازیابی</label>
      <input id="phone" name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" required placeholder="۰۹۰۴۶۶۲۳۸۱۶" value="<?= e($_POST['phone'] ?? '') ?>">
    </div>

    <div class="f">
      <label for="recovery_code">رمز ثابت بازیابی</label>
      <input id="recovery_code" name="recovery_code" type="password" dir="ltr" autocomplete="one-time-code" required placeholder="رمز ثابت را وارد کنید">
      <p class="recovery-hint">این رمز را فقط در اختیار مدیران مورد اعتماد بگذارید.</p>
    </div>

    <button class="btn" type="submit">ریست رمز عبور</button>
    <a class="login__back" href="login.php">بازگشت به ورود</a>

    <p class="login__foot">پس از تأیید، یک صفحه امن برای انتخاب رمز جدید باز می‌شود.</p>
  </form>
</div>
</body>
</html>
