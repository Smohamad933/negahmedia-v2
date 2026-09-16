<?php
/**
 * نگاه مدیا | سربرگ سایت
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

/* روی صفحه‌های داخلی، لینک بخش‌ها به صفحه اصلی اشاره می‌کند */
$isHome    = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php')) === 'index.php';
$home      = $isHome ? '' : url();
$anchor    = static fn(string $id): string => $home . '#' . $id;

$animOff   = setting('anim_level', 'full') === 'off';
$vers      = setting('style_version', '1');
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
<script>document.documentElement.className += ' js';</script>
<link rel="preload" href="<?= e(url('assets/fonts/Vazirmatn-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css?v=' . $vers)) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/theme.php?v=' . $vers)) ?>">
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

    <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($siteShort) ?>">
      <span class="brand__mark" aria-hidden="true">
        <svg viewBox="0 0 40 40" width="40" height="40" role="img" aria-hidden="true">
          <rect width="40" height="40" fill="currentColor"/>
          <text x="20" y="28" text-anchor="middle" font-size="22" font-weight="700"
                font-family="Vazirmatn, Tahoma, sans-serif" fill="#F6F4EF">ن</text>
          <rect x="0" y="36" width="40" height="4" fill="#A8752E" class="brand__mark-bar"/>
        </svg>
      </span>
      <span class="brand__txt">
        <span class="brand__fa"><?= e($siteShort) ?></span>
        <span class="brand__en">NEGAH&nbsp;MEDIA</span>
      </span>
    </a>

    <nav class="nav__links" aria-label="فهرست اصلی">
      <a href="<?= e($anchor('clients')) ?>">همراهان</a>
      <a href="<?= e($anchor('services')) ?>">خدمات</a>
      <a href="<?= e($anchor('about')) ?>">درباره نگاه</a>
      <a href="<?= e($anchor('process')) ?>">مراحل همکاری</a>
      <?php if (!empty($hasProjects)): ?><a href="<?= e($anchor('projects')) ?>">نمونه‌کارها</a><?php endif; ?>
      <a href="<?= e($anchor('contact')) ?>">تماس</a>
    </nav>

    <div class="nav__act">
      <?php if ($phone !== ''): ?>
        <a class="nav__call" href="tel:<?= e($phone) ?>">
          <span class="nav__call-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
            </svg>
          </span>
          <span class="nav__call-txt">
            <?php if (setting('call_label') !== ''): ?>
              <small><?= e(setting('call_label')) ?></small>
            <?php endif; ?>
            <strong dir="ltr"><?= e(fa_digits($phone)) ?></strong>
          </span>
        </a>
      <?php endif; ?>

      <a class="btn btn--sm nav__cta" href="<?= e($anchor('contact')) ?>">
        <?= e(setting('cta_primary', 'شروع یک پروژه')) ?>
      </a>

      <button class="nav__burger" type="button" aria-label="فهرست" aria-expanded="false" data-menu>
        <span></span><span></span>
      </button>
    </div>
  </div>

  <div class="nav__mobile" data-menu-panel hidden>
    <a href="<?= e($anchor('clients')) ?>">همراهان</a>
    <a href="<?= e($anchor('services')) ?>">خدمات</a>
    <a href="<?= e($anchor('about')) ?>">درباره نگاه</a>
    <a href="<?= e($anchor('process')) ?>">مراحل همکاری</a>
    <?php if (!empty($hasProjects)): ?><a href="<?= e($anchor('projects')) ?>">نمونه‌کارها</a><?php endif; ?>
    <a href="<?= e($anchor('contact')) ?>">تماس</a>
    <a href="<?= e(url('clients.php')) ?>">همه همراهان</a>
    <?php if ($phone !== ''): ?>
      <a class="nav__mobile-call" href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a>
    <?php endif; ?>
  </div>
</header>
