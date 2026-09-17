<?php
/**
 * نگاه مدیا | توابع کمکی، امنیتی و آپلود
 */
declare(strict_types=1);

/* سشن برای درخواست‌های سبک (مثل CSS پویا) آغاز نمی‌شود */
if (!defined('NO_SESSION') && session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_start();
}

require_once __DIR__ . '/schema.php';

/* =========================================================
   خروجی ایمن و آدرس‌ها
   ========================================================= */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** آدرس کامل یک فایل داخل پروژه */
function url(string $path = ''): string
{
    $base = (string) cfg('base_url', '/');
    if ($base === '' || $base[0] !== '/') {
        $base = '/' . $base;
    }
    if (substr($base, -1) !== '/') {
        $base .= '/';
    }
    return $base . ltrim($path, '/');
}

/** مسیر واقعی پوشه آپلود */
function upload_path(string $relative = ''): string
{
    $root = dirname(__DIR__) . '/' . trim((string) cfg('upload_dir', 'uploads/'), '/');
    return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
}

/* =========================================================
   تنظیمات سایت
   ========================================================= */

function settings_all(bool $reset = false): array
{
    static $cache = null;
    if ($cache === null || $reset) {
        $cache = [];
        try {
            foreach (q('SELECT `k`,`v` FROM `settings`') as $row) {
                $cache[(string) $row['k']] = (string) $row['v'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    $val = $all[$key] ?? '';
    return $val !== '' ? $val : $default;
}

function setting_save(string $key, string $value): void
{
    if (db_driver() === 'mysql') {
        db_run(
            'INSERT INTO `settings` (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v` = VALUES(`v`)',
            [$key, $value]
        );
    } else {
        db_run(
            'INSERT INTO `settings` (`k`,`v`) VALUES (?,?) ON CONFLICT(`k`) DO UPDATE SET `v` = excluded.`v`',
            [$key, $value]
        );
    }
    settings_all(true);
}

/**
 * ثبت تنظیم فقط اگر مقدار فعلی خالی باشد.
 * برای افزودن تنظیمات جدید در نسخه‌های بعدی بدون بازنویسی تغییرات کاربر.
 */
function setting_ensure(string $key, string $value): void
{
    $all = settings_all();
    if (!array_key_exists($key, $all) || $all[$key] === '') {
        setting_save($key, $value);
    }
}

/** افزایش شمارنده نسخه ظاهر (برای تازه‌سازی کش CSS) */
function style_bump(): void
{
    setting_save('style_version', (string) (time()));
}

/* =========================================================
   امنیت
   ========================================================= */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($sent === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(419);
        exit('درخواست نامعتبر است. صفحه را دوباره بارگذاری کنید.');
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['uid']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    return q1('SELECT `id`,`username`,`full_name`,`email`,`role`,`last_login` FROM `users` WHERE `id` = ?', [(int) $_SESSION['uid']]);
}

function require_login(): void
{
    if (!is_logged_in() || current_user() === null) {
        $_SESSION = [];
        redirect('login.php');
    }
}

/* =========================================================
   کمکی‌ها
   ========================================================= */

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($f) ? $f : null;
}

/** ارقام لاتین → فارسی */
function fa_digits($value): string
{
    return str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        (string) $value
    );
}

function fa_num(int $n, int $pad = 2): string
{
    return fa_digits(str_pad((string) $n, $pad, '0', STR_PAD_LEFT));
}

/** آدرس نهایی تصویر: اولویت با فایل آپلودی، بعد لینک بیرونی */
function media_url(array $row, string $fileKey = 'logo_file', string $urlKey = 'logo_url'): ?string
{
    $file = trim((string) ($row[$fileKey] ?? ''));
    $link = trim((string) ($row[$urlKey] ?? ''));
    if ($file !== '') {
        return url((string) cfg('upload_dir', 'uploads/') . ltrim($file, '/'));
    }
    if ($link !== '') {
        return $link;
    }
    return null;
}

/** لینک صفحه اختصاصی هر برند — مستقل از وب‌سایت خارجی برند */
function brand_url(array $client): string
{
    $id = (int) ($client['id'] ?? 0);
    return url('brand.php?id=' . $id);
}

/**
 * ثبت یک بازدید عمومی.
 * فقط درخواست‌های GET صفحات سایت ثبت می‌شوند؛ پنل، فایل‌های استاتیک و ربات‌ها
 * به‌صورت طبیعی وارد این جدول نمی‌شوند. شناسه بازدیدکننده نیز به شکل هش‌شده
 * نگه‌داری می‌شود و IP در دیتابیس ذخیره نمی‌شود.
 */
function track_page_view(): void
{
    if (defined('NO_SESSION') || (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }

    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($userAgent !== '' && preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|uptimerobot/i', $userAgent)) {
        return;
    }

    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $path = is_string($path) && $path !== '' ? $path : '/';
    $path = substr($path, 0, 220);

    $token = (string) ($_COOKIE['negah_visitor'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
        try {
            $token = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            return;
        }

        $base = (string) cfg('base_url', '/');
        if ($base === '' || $base[0] !== '/') {
            $base = '/' . $base;
        }
        if (substr($base, -1) !== '/') {
            $base .= '/';
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        @setcookie('negah_visitor', $token, [
            'expires'  => time() + 31536000,
            'path'     => $base,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    try {
        db_run(
            'INSERT INTO `site_views` (`path`,`visitor_hash`,`viewed_date`,`viewed_at`) VALUES (?,?,?,?)',
            [$path, hash('sha256', $token), date('Y-m-d'), date('Y-m-d H:i:s')]
        );
    } catch (Throwable $e) {
        // آمار نباید باعث خطای سایت شود.
    }
}

/** آدرس فایل فونت آپلودی */
function font_url(?string $relative): ?string
{
    $relative = trim((string) $relative);
    if ($relative === '') {
        return null;
    }
    return url((string) cfg('upload_dir', 'uploads/') . ltrim($relative, '/'));
}

/** قالب فونت بر اساس پسوند فایل */
function font_format(string $filename): string
{
    return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
        'woff2' => 'woff2',
        'woff'  => 'woff',
        'otf'   => 'opentype',
        'ttf'   => 'truetype',
        default => 'woff2',
    };
}

/** نام امن و یکتا برای فایل آپلودی */
function safe_filename(string $original, ?array $allowed = null): string
{
    $allowed = $allowed ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        $ext = $allowed[0];
    }
    return date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
}

/**
 * آپلود تصویر با اعتبارسنجی کامل.
 * @return array{ok:bool,file:?string,error:?string}
 */
function upload_image(string $field, string $subdir, int $maxMb = 2): array
{
    $out = ['ok' => false, 'file' => null, 'error' => null];

    if (empty($_FILES[$field]['name']) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $out;
    }

    if ((int) $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $out['error'] = 'آپلود فایل انجام نشد. دوباره تلاش کنید.';
        return $out;
    }

    $tmp  = (string) $_FILES[$field]['tmp_name'];
    $size = (int) $_FILES[$field]['size'];
    $name = (string) $_FILES[$field]['name'];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($size > $maxMb * 1024 * 1024) {
        $out['error'] = 'حجم فایل باید کمتر از ' . fa_digits((string) $maxMb) . ' مگابایت باشد.';
        return $out;
    }

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
        $out['error'] = 'فرمت مجاز: PNG، JPG، WEBP، GIF یا SVG.';
        return $out;
    }

    if ($ext === 'svg') {
        $head = (string) @file_get_contents($tmp, false, null, 0, 512);
        if (stripos($head, '<svg') === false) {
            $out['error'] = 'فایل SVG معتبر نیست.';
            return $out;
        }
    } elseif (@getimagesize($tmp) === false) {
        $out['error'] = 'فایل انتخابی تصویر معتبری نیست.';
        return $out;
    }

    $dir = upload_path($subdir);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $out['error'] = 'ساخت پوشه ذخیره‌سازی ممکن نشد. دسترسی پوشه uploads را بررسی کنید.';
        return $out;
    }

    $newName = safe_filename($name);
    if (!@move_uploaded_file($tmp, $dir . '/' . $newName)) {
        $out['error'] = 'ذخیره فایل روی سرور ممکن نشد.';
        return $out;
    }

    $out['ok']   = true;
    $out['file'] = $subdir . '/' . $newName;
    return $out;
}

/**
 * آپلود فایل فونت.
 * @return array{ok:bool,file:?string,error:?string}
 */
function upload_font(string $field, int $maxMb = 6): array
{
    $out = ['ok' => false, 'file' => null, 'error' => null];

    if (empty($_FILES[$field]['name']) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $out;
    }

    if ((int) $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $out['error'] = 'آپلود فونت انجام نشد. دوباره تلاش کنید.';
        return $out;
    }

    $tmp  = (string) $_FILES[$field]['tmp_name'];
    $size = (int) $_FILES[$field]['size'];
    $name = (string) $_FILES[$field]['name'];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($size > $maxMb * 1024 * 1024) {
        $out['error'] = 'حجم فایل فونت باید کمتر از ' . fa_digits((string) $maxMb) . ' مگابایت باشد.';
        return $out;
    }

    if (!in_array($ext, ['woff2', 'woff', 'ttf', 'otf'], true)) {
        $out['error'] = 'فرمت مجاز فونت: WOFF2، WOFF، TTF یا OTF.';
        return $out;
    }

    // بررسی امضای فایل فونت
    $sig = (string) @file_get_contents($tmp, false, null, 0, 4);
    $signatures = [
        'woff2' => "wOF2",
        'woff'  => "wOFF",
        'ttf'   => "\x00\x01\x00\x00",
        'otf'   => 'OTTO',
    ];
    if (isset($signatures[$ext]) && $sig !== $signatures[$ext]) {
        // برخی OTF/TTFهای فشرده امضای متفاوتی دارند؛ فقط هشدار می‌دهیم
        if (!in_array($ext, ['ttf', 'otf'], true)) {
            $out['error'] = 'فایل انتخابی یک فونت معتبر به‌نظر نمی‌رسد.';
            return $out;
        }
    }

    $dir = upload_path('fonts');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $out['error'] = 'ساخت پوشه fonts ممکن نشد.';
        return $out;
    }

    $newName = safe_filename($name, ['woff2', 'woff', 'ttf', 'otf']);
    if (!@move_uploaded_file($tmp, $dir . '/' . $newName)) {
        $out['error'] = 'ذخیره فایل فونت ممکن نشد.';
        return $out;
    }

    $out['ok']   = true;
    $out['file'] = 'fonts/' . $newName;
    return $out;
}

/** حذف فایل آپلودی (با محدود کردن مسیر به پوشه uploads) */
function delete_upload(?string $relative): void
{
    $relative = trim((string) $relative);
    if ($relative === '' || strpos($relative, '..') !== false) {
        return;
    }
    $full = upload_path($relative);
    if (is_file($full)) {
        @unlink($full);
    }
}

/* =========================================================
   رنگ
   ========================================================= */

/** بررسی معتبر بودن رنگ هگز */
function is_hex_color(string $color): bool
{
    return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
}

function hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

function rgb_to_hex(array $rgb): string
{
    return sprintf('#%02X%02X%02X', max(0, min(255, (int) $rgb[0])), max(0, min(255, (int) $rgb[1])), max(0, min(255, (int) $rgb[2])));
}

/** تیره‌تر کردن رنگ (factor بین ۰ و ۱) */
function hex_darken(string $hex, float $factor = 0.25): string
{
    $rgb = hex_to_rgb($hex);
    return rgb_to_hex(array_map(static fn($c) => (int) round($c * (1 - $factor)), $rgb));
}

/** روشن‌تر کردن رنگ */
function hex_lighten(string $hex, float $factor = 0.2): string
{
    $rgb = hex_to_rgb($hex);
    return rgb_to_hex(array_map(static fn($c) => (int) round($c + (255 - $c) * $factor), $rgb));
}

/** رنگ با شفافیت */
function hex_rgba(string $hex, float $alpha = 1.0): string
{
    [$r, $g, $b] = hex_to_rgb($hex);
    return sprintf('rgba(%d, %d, %d, %.2F)', $r, $g, $b, $alpha);
}

/**
 * پاک‌سازی CSS سفارشی کاربر.
 * فقط جلوی بسته‌شدن تگ استایل و باز کردن کد PHP را می‌گیرد؛
 * بقیه CSS دست‌نخورده می‌ماند چون فقط مدیر به آن دسترسی دارد.
 */
function sanitize_custom_css(string $css): string
{
    $css = str_replace(['</style', '</STYLE', '<?', '?>', '<script'], '', $css);
    return trim($css);
}
