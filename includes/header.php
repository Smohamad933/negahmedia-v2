<?php
/**
 * نگاه مدیا | سربرگ سایت
 * بدون منو — فقط لوگوتایپ برند و شماره تماس.
 *
 * @var string|null $pageTitle
 * @var string|null $pageDesc
 * @var bool|null   $isListing
 */
$siteName  = setting('site_name', 'آژانس خلاق و تبلیغاتی نگاه مدیا');
$siteShort = setting('site_short', 'نگاه مدیا');
$title     = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' . $siteShort : $siteName;
$desc      = isset($pageDesc) && $pageDesc !== '' ? $pageDesc : setting('meta_description', setting('hero_sub'));
$phone     = setting('phone');
$email     = setting('email');

$animOff = setting('anim_level', 'full') === 'off';
$vers    = setting('style_version', '1');

/* هر بار باز شدن یک صفحه عمومی، یک page view ثبت می‌شود. */
track_page_view();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#F6F4EF">
<meta property="og:type" content="website">
<meta property="og:locale" content="fa_IR">
<meta property="og:site_name" content="<?= e($siteShort) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('assets/fonts/Vazirmatn-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css?v=' . $vers)) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/theme.php?v=' . $vers)) ?>">
<script>document.documentElement.className += ' js';</script>
<script type="application/ld+json">
<?= json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'ProfessionalService',
    'name'          => $siteShort,
    'alternateName' => $siteName,
    'description'   => $desc,
    'telephone'     => $phone,
    'email'         => $email,
    'areaServed'    => 'IR',
    'address'       => ['@type' => 'PostalAddress', 'addressRegion' => 'خوزستان', 'addressLocality' => 'اهواز', 'addressCountry' => 'IR'],
    'sameAs'        => array_values(array_filter([setting('instagram'), setting('telegram'), setting('linkedin')])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
</head>
<body class="<?= $animOff ? 'no-anim' : '' ?><?= !empty($isListing) ? ' is-listing' : '' ?>">

<div class="grain" aria-hidden="true"></div>
<div class="progress" aria-hidden="true"><span class="progress__bar" data-progress></span></div>

<a class="skip" href="#main">رفتن به محتوا</a>

<header class="nav" id="top" data-nav>
  <div class="nav__in">

    <!-- ────── برند ────── -->
    <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($siteShort) ?>">
      <span class="brand__mark" aria-hidden="true">
        <svg viewBox="0 0 40 40" role="img" aria-hidden="true" focusable="false">
          <rect width="40" height="40" fill="currentColor"/>
          <text x="20" y="28" text-anchor="middle" font-size="22" font-weight="700"
                font-family="Vazirmatn, Tahoma, sans-serif" fill="#F6F4EF">ن</text>
          <rect x="0" y="36" width="40" height="4" class="brand__mark-bar"/>
        </svg>
      </span>
      <span class="brand__txt">
        <span class="brand__fa"><?= e($siteShort) ?></span>
        <span class="brand__en">NEGAH&nbsp;MEDIA</span>
      </span>
    </a>

    <!-- ────── شماره تماس ────── -->
    <?php if ($phone !== ''): ?>
      <a class="call" href="tel:<?= e($phone) ?>" aria-label="تماس تلفنی با <?= e($siteShort) ?>">
        <span class="call__ico" aria-hidden="true">
          <span class="call__pulse"></span>
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
          </svg>
        </span>
        <span class="call__txt">
          <?php if (setting('call_label') !== ''): ?>
            <small><?= e(setting('call_label')) ?></small>
          <?php endif; ?>
          <strong dir="ltr"><?= e(fa_digits($phone)) ?></strong>
        </span>
      </a>
    <?php endif; ?>

  </div>
</header>
