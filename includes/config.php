<?php
/**
 * نگاه مدیا | پیکربندی پروژه
 * ------------------------------------------------------------------
 * این فایل را می‌توانید دستی ویرایش کنید یا از طریق install.php بسازید.
 *
 * driver:
 *   'sqlite'  → بدون نیاز به تنظیم؛ دیتابیس در پوشه data ساخته می‌شود.
 *               مناسب شروع سریع و هاست‌هایی که MySQL ندارند.
 *   'mysql'   → برای هاست‌های معمول cPanel / DirectAdmin.
 *               نام دیتابیس و کاربر را در بخش mysql پر کنید.
 */
declare(strict_types=1);

return [

    // 'sqlite' یا 'mysql'
    'driver'      => 'sqlite',

    // فقط برای حالت sqlite
    'sqlite_path' => __DIR__ . '/../data/negah.sqlite',

    // فقط برای حالت mysql — اطلاعات را از cPanel → MySQL Databases بردارید
    'mysql' => [
        'host'    => 'localhost',
        'name'    => '',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // آدرس پایه سایت:
    // اگر سایت در ریشه دامنه است همان '/' بگذارید، و اگر داخل پوشه است مثل '/negah/'.
    'base_url'   => '/',

    // پوشه ذخیره لوگو و تصاویر (نسبت به ریشه پروژه)
    'upload_dir' => 'uploads/',

    'timezone'   => 'Asia/Tehran',

    // روی هاست نهایی false بگذارید؛ فقط برای عیب‌یابی true کنید.
    'debug'      => false,
];
