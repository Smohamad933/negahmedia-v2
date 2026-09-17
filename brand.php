<?php
/**
 * نگاه مدیا | صفحه اختصاصی هر برند و همراه
 * محتوای این صفحه از پنل مدیریت → گالری برندها قابل ویرایش است.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$client = $id > 0
    ? q1(
        'SELECT c.*, g.title AS group_title, g.subtitle AS group_subtitle
         FROM `clients` c
         LEFT JOIN `client_groups` g ON g.id = c.group_id
         WHERE c.id = ? AND c.visible = 1',
        [$id]
    )
    : null;

if ($client === null) {
    http_response_code(404);
    $pageTitle = 'صفحه پیدا نشد';
    $pageDesc  = 'این صفحه در نگاه مدیا وجود ندارد.';
    require __DIR__ . '/includes/header.php';
    ?>
    <main id="main" class="brand-page">
      <section class="brand-empty">
        <div class="wrap">
          <p class="label">404 / CLIENT</p>
          <h1>این صفحه پیدا نشد.</h1>
          <p>ممکن است این همراه از فهرست سایت حذف یا جابه‌جا شده باشد.</p>
          <a class="btn" href="<?= e(url('clients.php')) ?>">بازگشت به همراهان</a>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$gallery = q(
    'SELECT * FROM `client_gallery` WHERE `client_id` = ? AND `visible` = 1 ORDER BY `sort_order`, `id`',
    [$id]
);

$logo     = media_url($client);
$cover    = media_url($client, 'cover_file', 'cover_url');
$logoMode = client_logo_mode($client);
$intro = trim((string) ($client['intro'] ?? ''));
$pageTitle = (string) $client['name'];
$pageDesc  = $intro !== '' ? $intro : 'معرفی همراه و بخشی از کارهایی که نگاه مدیا برای این برند انجام داده است.';

require __DIR__ . '/includes/header.php';
?>

<main id="main" class="brand-page">

  <section class="brand-hero">
    <div class="wrap">
      <nav class="crumbs" aria-label="مسیر">
        <a href="<?= e(url()) ?>">صفحه اصلی</a>
        <span aria-hidden="true">/</span>
        <a href="<?= e(url('clients.php')) ?>">همراهان</a>
        <span aria-hidden="true">/</span>
        <span><?= e($client['name']) ?></span>
      </nav>

      <div class="brand-hero__grid">
        <div class="brand-hero__copy">
          <p class="label" data-anim="up">CLIENT PROFILE</p>
          <?php if (!empty($client['group_title'])): ?>
            <p class="brand-hero__group" data-anim="up" style="--d:1"><?= e($client['group_title']) ?></p>
          <?php endif; ?>
          <h1 class="brand-hero__title" data-anim="up" style="--d:2"><?= e($client['name']) ?></h1>
          <p class="brand-hero__intro" data-anim="up" style="--d:3"><?= e($intro !== '' ? $intro : 'روایتی از همکاری نگاه مدیا با این برند؛ از ایده تا خروجی نهایی.') ?></p>

          <div class="brand-hero__actions" data-anim="up" style="--d:4">
            <a class="btn btn--arrow" href="#gallery">
              <span><?= $gallery ? 'دیدن گالری کارها' : 'درباره این همکاری' ?></span>
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 18l-6-6 6-6"/>
              </svg>
            </a>
            <?php if (!empty($client['website'])): ?>
              <a class="btn btn--ghost" href="<?= e($client['website']) ?>" target="_blank" rel="noopener noreferrer">وب‌سایت برند ↗</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="brand-hero__media<?= $cover ? ' has-cover' : '' ?>" data-anim="fade" style="--d:2">
          <?php if ($cover): ?>
            <img src="<?= e($cover) ?>" alt="<?= e($client['name']) ?>" loading="eager">
          <?php elseif ($logo): ?>
            <div class="brand-hero__logo brand-hero__logo--<?= e($logoMode) ?>"><img src="<?= e($logo) ?>" alt="لوگوی <?= e($client['name']) ?>"></div>
          <?php else: ?>
            <div class="brand-hero__wordmark"><?= e($client['name']) ?></div>
          <?php endif; ?>
          <span class="brand-hero__index" aria-hidden="true">NO. <?= e(fa_digits((string) $client['id'])) ?></span>
        </div>
      </div>
    </div>
  </section>

  <section class="brand-intro" id="gallery">
    <div class="wrap">
      <div class="brand-intro__head">
        <div>
          <p class="label" data-anim="up">SELECTED WORKS</p>
          <h2 data-anim="up" style="--d:1">کارهای انجام‌شده برای <?= e($client['name']) ?></h2>
        </div>
        <div class="brand-intro__meta" data-anim="up" style="--d:2">
          <strong><?= e(fa_digits((string) count($gallery))) ?></strong>
          <span>مورد در گالری</span>
        </div>
      </div>

      <?php if ($gallery): ?>
        <div class="brand-gallery">
          <?php foreach ($gallery as $i => $item): ?>
            <?php $image = media_url($item, 'image_file', 'image_url'); ?>
            <article class="brand-work<?= $image ? ' has-image' : '' ?>" data-anim="up" style="--d:<?= min(5, $i) ?>">
              <?php if ($image): ?>
                <div class="brand-work__media">
                  <?php if (!empty($item['link'])): ?><a href="<?= e($item['link']) ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاهده <?= e($item['title']) ?>"><?php endif; ?>
                    <img src="<?= e($image) ?>" alt="<?= e($item['title']) ?>" loading="lazy" decoding="async">
                  <?php if (!empty($item['link'])): ?></a><?php endif; ?>
                </div>
              <?php else: ?>
                <div class="brand-work__media brand-work__media--empty" aria-hidden="true"><span><?= e($client['name']) ?></span></div>
              <?php endif; ?>
              <div class="brand-work__body">
                <?php if (!empty($item['category'])): ?><span class="brand-work__cat"><?= e($item['category']) ?></span><?php endif; ?>
                <h3><?= e($item['title']) ?></h3>
                <?php if (!empty($item['description'])): ?><p><?= e($item['description']) ?></p><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="brand-gallery__empty" data-anim="up">
          <span class="brand-gallery__empty-mark">+</span>
          <p>گالری این همکاری هنوز در حال تکمیل است.</p>
          <small>به‌زودی بخشی از پروژه‌ها و محتوای تولیدشده برای این برند را اینجا می‌بینید.</small>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="sec sec--dark brand-next">
    <div class="wrap brand-next__in">
      <div>
        <p class="label">NEXT PROJECT</p>
        <h2>برند بعدی، برند شما باشد.</h2>
      </div>
      <div class="brand-next__actions">
        <a class="btn" href="<?= e(url()) ?>#contact">شروع یک پروژه</a>
        <a class="btn btn--ghost" href="<?= e(url('clients.php')) ?>">همه همراهان</a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
