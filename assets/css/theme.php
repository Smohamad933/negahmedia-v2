<?php
/**
 * نگاه مدیا | استایل پویا
 * ------------------------------------------------------------------
 * این فایل CSS نهایی سایت را می‌سازد و شامل موارد زیر است:
 *   · تعریف فونت اختصاصی آپلودشده (متن و تیتر)
 *   · رنگ تأکیدی انتخاب‌شده در پنل
 *   · CSS سفارشی مدیر
 * تغییرات از پنل → «ظاهر، فونت و CSS» اعمال می‌شود.
 */
declare(strict_types=1);

define('NO_SESSION', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');

/* ═══════════════ ۱. فونت خارجی (اختیاری) ═══════════════ */
$external = trim(setting('font_external_url'));
if ($external !== '' && preg_match('~^https?://~i', $external)) {
    echo "@import url('" . str_replace(["'", '\\'], '', $external) . "');\n";
}

/* ═══════════════ ۲. فونت اختصاصی آپلودی ═══════════════ */
$bodyFontFile    = trim(setting('font_file'));
$headingFontFile = trim(setting('font_heading_file'));
$bodyFamily      = trim(setting('font_family')) !== '' ? trim(setting('font_family')) : 'Vazirmatn';
$headingFamily   = trim(setting('font_heading_family')) !== '' ? trim(setting('font_heading_family')) : $bodyFamily;

/** یک فایل فونت را با نام دلخواه ثبت می‌کند */
$fontFace = static function (string $relative, string $family): string {
    $src = font_url($relative);
    if ($src === null) {
        return '';
    }
    return "@font-face{"
        . "font-family:'" . str_replace("'", '', $family) . "';"
        . "src:url('" . $src . "') format('" . font_format($relative) . "');"
        . "font-weight:100 900;"
        . "font-style:normal;"
        . "font-display:swap;"
        . "}\n";
};

echo $fontFace($bodyFontFile, $bodyFamily);

if ($headingFontFile !== '' && $headingFontFile !== $bodyFontFile) {
    echo $fontFace($headingFontFile, $headingFamily);
}

/* ═══════════════ ۳. رنگ تأکیدی ═══════════════ */
$accent = trim(setting('accent', '#0A9F83'));
if (!is_hex_color($accent)) {
    $accent = '#0A9F83';
}

$accentDark  = hex_darken($accent, 0.18);
$accentLight = hex_lighten($accent, 0.28);

/* ═══════════════ ۴. متغیرهای CSS ═══════════════ */
$bodyStack    = "'" . str_replace("'", '', $bodyFamily) . "', 'Vazirmatn', -apple-system, 'Segoe UI', Tahoma, sans-serif";
$headingStack = "'" . str_replace("'", '', $headingFamily) . "', 'Vazirmatn', -apple-system, Tahoma, sans-serif";

?>
:root{
  --accent: <?= $accent ?>;
  --accent-dk: <?= $accentDark ?>;
  --accent-lt: <?= $accentLight ?>;
  --accent-08: <?= hex_rgba($accent, 0.08) ?>;
  --accent-18: <?= hex_rgba($accent, 0.18) ?>;
  --accent-35: <?= hex_rgba($accent, 0.35) ?>;
  --font-body: <?= $bodyStack ?>;
  --font-head: <?= $headingStack ?>;
}

body,
input, textarea, select, button {
  font-family: var(--font-body);
}

h1, h2, h3, h4, h5, .hero__title, .head__title, .about__manifesto,
.contact__title, .srv__title, .step__title, .work__title, .foot__quote,
.cgroup__title, .stat__value, .brand__fa, .cl-stat__value {
  font-family: var(--font-head);
}

/* دکمه بازگشت به بالا و خطوط پیشرفت از رنگ تأکیدی */
.totop:hover,
.progress__bar { background: var(--accent); }

/* ═══════════════ ۵. CSS سفارشی مدیر ═══════════════ */
<?= sanitize_custom_css(setting('custom_css')) ?>
