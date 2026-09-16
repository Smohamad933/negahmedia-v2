<?php
/**
 * نگاه مدیا | توابع کمکی، امنیتی و آپلود
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_start();
}

/* =========================================================
   خروجی ایمن
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

/* =========================================================
   تنظیمات سایت
   ========================================================= */

function settings_all(): array
{
    static $cache = null;
    if ($cache === null) {
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
        return;
    }
    db_run(
        'INSERT INTO `settings` (`k`,`v`) VALUES (?,?) ON CONFLICT(`k`) DO UPDATE SET `v` = excluded.`v`',
        [$key, $value]
    );
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

/** ارتفاع کادر نمایش لوگو دقیقاً یکسان -> سایز ثابت */
function upload_path(string $relative = ''): string
{
    $root = dirname(__DIR__) . '/' . trim((string) cfg('upload_dir', 'uploads/'), '/');
    return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
}

/** نام امن و یکتا برای فایل آپلودی */
function safe_filename(string $original): string
{
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
        $ext = 'png';
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

    $err = (int) $_FILES[$field]['error'];
    if ($err !== UPLOAD_ERR_OK) {
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

    // بررسی واقعی بودن تصویر (SVG از این بررسی معاف است)
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
