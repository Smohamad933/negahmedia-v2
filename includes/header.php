<?php
/**
 * نگاه مدیا | سربرگ سایت
 * @var string|null $pageTitle
 * @var string|null $pageDesc
 */
$siteName = setting('site_name', 'آژانس خلاق و تبلیغاتی نگاه مدیا');
$siteShort = setting('site_short', 'نگاه مدیا');
$title = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' . $siteShort : $siteName;
$desc  = isset($pageDesc) && $pageDesc !== '' ? $pageDesc : setting('meta_description', setting('hero_sub'));
$phone = setting('phone');
$email = setting('email');
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
<link rel="stylesheet" href="<?= e(url('assets/css/style.css?v=3')) ?>">
<script type="application/ld+json">
<?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'ProfessionalService',
    'name'        => $siteShort,
    'alternateName' => $siteName,
    'description' => $desc,
    'telephone'   => $phone,
    'email'       => $email,
    'areaServed'  => 'IR',
    'address'     => ['@type' => 'PostalAddress', 'addressRegion' => 'خوزستان', 'addressLocality' => 'اهواز', 'addressCountry' => 'IR'],
    'sameAs'      => array_values(array_filter([setting('instagram'), setting('telegram'), setting('linkedin')])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>

</script>
</head>
<body>
<div class="grain" aria-hidden="true"></div>

<a class="skip" href="#main">رفتن به محتوا</a>

<header class="nav" id="top">
  <div class="nav__in">
    <a class="brand" href="<?= e(url()) ?>#top" aria-label="<?= e($siteShort) ?>">
      <span class="brand__mark" aria-hidden="true">ن</span>
      <span class="brand__txt">
        <span class="brand__fa"><?= e($siteShort) ?></span>
        <span class="brand__en">NEGAH&nbsp;MEDIA</span>
      </span>
    </a>

    <nav class="nav__links" aria-label="فهرست اصلی">
      <a href="#clients">همراهان</a>
      <a href="#services">خدمات</a>
      <a href="#about">درباره نگاه</a>
      <a href="#process">مراحل همکاری</a>
      <?php if (!empty($hasProjects)): ?><a href="#projects">نمونه‌کارها</a><?php endif; ?>
      <a href="#contact">تماس</a>
    </nav>

    <div class="nav__act">
      <?php if ($phone !== ''): ?>
        <a class="nav__tel" href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a>
      <?php endif; ?>
      <a class="btn btn--sm" href="#contact"><?= e(setting('cta_primary', 'شروع یک پروژه')) ?></a>
      <button class="nav__burger" type="button" aria-label="فهرست" aria-expanded="false" data-menu>
        <span></span><span></span>
      </button>
    </div>
  </div>

  <div class="nav__mobile" data-menu-panel hidden>
    <a href="#clients">همراهان</a>
    <a href="#services">خدمات</a>
    <a href="#about">درباره نگاه</a>
    <a href="#process">مراحل همکاری</a>
    <?php if (!empty($hasProjects)): ?><a href="#projects">نمونه‌کارها</a><?php endif; ?>
    <a href="#contact">تماس</a>
  </div>
</header>
