<?php
/**
 * نگاه مدیا | راه‌انداز مشترک
 * این فایل را در ابتدای هر صفحه فراخوانی کنید.
 *
 * نکته: برای درخواست‌های سبک مثل فایل CSS پویا، پیش از فراخوانی
 * این فایل ثابت NO_SESSION را تعریف کنید تا سشن آغاز نشود.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

date_default_timezone_set((string) cfg('timezone', 'Asia/Tehran'));

if ((bool) cfg('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

require_once __DIR__ . '/functions.php';

/* اگر نصب انجام نشده باشد، به نصب‌کننده هدایت شود */
$isInstaller = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'install.php';

if (!db_is_installed() && !$isInstaller) {
    redirect(url('install.php'));
}

/* ---------- به‌روزرسانی خودکار ساختار دیتابیس ---------- */
if (!$isInstaller && db_is_installed()) {
    $installedVersion = (int) setting('db_version', '0');
    if ($installedVersion < NEGAH_DB_VERSION) {
        require_once __DIR__ . '/schema.php';
        schema_migrate();
        require_once __DIR__ . '/seed.php';
        seed_defaults();
        setting_save('db_version', (string) NEGAH_DB_VERSION);
        settings_all(true);
    }
}
