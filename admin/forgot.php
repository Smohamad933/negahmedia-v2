<?php
/**
 * نگاه مدیا | درخواست بازیابی رمز عبور
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in() && current_user() !== null) {
    redirect('index.php');
}

$notice = '';
$debugLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $recoveryCode = trim((string) ($_POST['recovery_code'] ?? ''));
    $notice = 'اگر اطلاعات درست باشد، امکان ساخت رمز جدید برای شما فعال می‌شود؛ در غیر این صورت، در صورت ثبت بودن ایمیل حساب، لینک بازیابی ارسال خواهد شد.';

    if ($identifier !== '' && mb_strlen($identifier) <= 160) {
        $user = q1(
            'SELECT `id`,`username`,`full_name`,`email` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1',
            [$identifier, $identifier]
        );

        /* روش سریع: نام کاربری + کد بازیابی ثابت */
        if ($user !== null && verify_recovery_code($recoveryCode)) {
            $rawToken = issue_password_reset_token((int) $user['id']);
            if ($rawToken !== null) {
                redirect('reset.php?token=' . rawurlencode($rawToken));
            }
        }

        /* روش ایمیلی قبلی: وقتی کاربر کد بازیابی را وارد نکرده باشد */
        $userEmail = $user !== null ? trim((string) ($user['email'] ?? '')) : '';
        $canSend = $recoveryCode === '' && $user !== null && filter_var($userEmail, FILTER_VALIDATE_EMAIL);

        if ($canSend) {
            $rawToken = issue_password_reset_token((int) $user['id']);
            if ($rawToken !== null) {
                try {
                    $resetLink = absolute_url('admin/reset.php?token=' . rawurlencode($rawToken));
                    $siteName = setting('site_short', 'نگاه مدیا');
                    $subject = '=?UTF-8?B?' . base64_encode('بازیابی رمز عبور پنل ' . $siteName) . '?=';
                    $body = "سلام" . (!empty($user['full_name']) ? ' ' . $user['full_name'] : '') . "،\n\n"
                        . "برای ساختن رمز عبور جدید برای پنل مدیریت، روی لینک زیر کلیک کنید:\n\n"
                        . $resetLink . "\n\n"
                        . "این لینک فقط ۳۰ دقیقه اعتبار دارد و پس از استفاده باطل می‌شود.\n"
                        . "اگر شما این درخواست را ثبت نکرده‌اید، این ایمیل را نادیده بگیرید.\n\n"
                        . $siteName;

                    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                    $from = trim((string) setting('email'));
                    if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
                        $headers .= 'From: ' . $siteName . ' <' . $from . ">\r\n";
                    }

                    $mailSent = function_exists('mail') && @mail($userEmail, $subject, $body, $headers);

                    // فقط برای تست محلی با debug روشن؛ در حالت عادی هرگز توکن نمایش داده نمی‌شود.
                    if (!$mailSent && (bool) cfg('debug', false)) {
                        $debugLink = $resetLink;
                    }
                } catch (Throwable $e) {
                    // پاسخ عمومی باقی می‌ماند تا وجود حساب افشا نشود.
                }
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
  .reset-debug { margin-bottom: 20px; padding: 13px 16px; border: 1px solid var(--accent); background: var(--accent-l); font-size: 13px; line-height: 1.9; overflow-wrap: anywhere; }
  .reset-debug a { color: var(--accent); direction: ltr; display: inline-block; }
  .recovery-hint { margin-top: 7px; color: var(--muted); font-size: 12.5px; line-height: 1.8; }
  .recovery-sep { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 12px; margin: 22px 0 16px; }
  .recovery-sep::before, .recovery-sep::after { content: ''; height: 1px; flex: 1; background: var(--line-soft); }
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

    <h1 class="login__title">بازیابی رمز عبور</h1>
    <p class="login__lead">نام کاربری و کد بازیابی را وارد کنید. در صورت نداشتن کد، می‌توانید فقط با ایمیل ثبت‌شده لینک بازیابی بگیرید.</p>

    <?php if ($notice !== ''): ?>
      <div class="flash"><?= e($notice) ?></div>
    <?php endif; ?>

    <?php if ($debugLink !== ''): ?>
      <div class="reset-debug">
        ارسال ایمیل در محیط تست فعال نیست. لینک بازیابی:<br>
        <a href="<?= e($debugLink) ?>" dir="ltr"><?= e($debugLink) ?></a>
      </div>
    <?php endif; ?>

    <div class="f">
      <label for="identifier">نام کاربری یا ایمیل</label>
      <input id="identifier" name="identifier" required autofocus autocomplete="username" value="<?= e($_POST['identifier'] ?? '') ?>">
    </div>

    <div class="f">
      <label for="recovery_code">کد بازیابی</label>
      <input id="recovery_code" name="recovery_code" type="password" dir="ltr" autocomplete="one-time-code" placeholder="کد بازیابی را وارد کنید">
      <p class="recovery-hint">با وارد کردن کد درست، بدون نیاز به ایمیل مستقیماً به صفحه ساخت رمز جدید می‌روید.</p>
    </div>

    <button class="btn" type="submit">ادامه بازیابی</button>

    <div class="recovery-sep"><span>یا بازیابی با ایمیل</span></div>
    <p class="recovery-hint">برای این روش، کد بازیابی را خالی بگذارید و ایمیل حساب را وارد کنید.</p>

    <a class="login__back" href="login.php">بازگشت به ورود</a>
    <p class="login__foot">کد بازیابی فعلی را فقط در اختیار مدیران مورد اعتماد بگذارید. لینک ایمیلی یک‌بارمصرف است و ۳۰ دقیقه اعتبار دارد.</p>
  </form>
</div>
</body>
</html>
