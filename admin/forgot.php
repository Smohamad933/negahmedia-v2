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
    $notice = 'اگر حسابی با این مشخصات وجود داشته باشد و ایمیل آن ثبت شده باشد، لینک بازیابی برایتان ارسال می‌شود.';

    if ($identifier !== '' && mb_strlen($identifier) <= 160) {
        $user = q1(
            'SELECT `id`,`username`,`full_name`,`email` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1',
            [$identifier, $identifier]
        );

        $userEmail = $user !== null ? trim((string) ($user['email'] ?? '')) : '';
        $canSend = $user !== null && filter_var($userEmail, FILTER_VALIDATE_EMAIL);

        if ($canSend) {
            $now = date('Y-m-d H:i:s');
            $recent = (int) qv(
                'SELECT COUNT(*) FROM `password_resets` WHERE `user_id`=? AND `created_at` >= ?',
                [(int) $user['id'], date('Y-m-d H:i:s', time() - 3600)],
                0
            );

            if ($recent < 5) {
                // توکن‌های منقضی حذف و لینک‌های قبلی همین کاربر بی‌اعتبار می‌شوند.
                db_run('DELETE FROM `password_resets` WHERE `expires_at` <= ? OR `used_at` IS NOT NULL', [$now]);
                db_run('UPDATE `password_resets` SET `used_at`=? WHERE `user_id`=? AND `used_at` IS NULL', [$now, (int) $user['id']]);
                try {
                    $rawToken = bin2hex(random_bytes(32));
                    db_run(
                        'INSERT INTO `password_resets` (`user_id`,`token_hash`,`expires_at`) VALUES (?,?,?)',
                        [(int) $user['id'], hash('sha256', $rawToken), date('Y-m-d H:i:s', time() + 1800)]
                    );

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
    <p class="login__lead">نام کاربری یا ایمیل حساب مدیریت را وارد کنید تا لینک ساخت رمز جدید برایتان ارسال شود.</p>

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

    <button class="btn" type="submit">ارسال لینک بازیابی</button>
    <a class="login__back" href="login.php">بازگشت به ورود</a>

    <p class="login__foot">لینک بازیابی یک‌بار مصرف است و فقط ۳۰ دقیقه اعتبار دارد.</p>
  </form>
</div>
</body>
</html>
