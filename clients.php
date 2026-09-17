<?php
/**
 * نگاه مدیا | صفحه کامل همراهان و برندها
 * همه دسته‌ها و همه برندها در یک صفحه.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$groups  = q('SELECT * FROM `client_groups` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$clients = q('SELECT * FROM `clients` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');

$byGroup = [];
foreach ($clients as $c) {
    $byGroup[(int) $c['group_id']][] = $c;
}

/* فقط دسته‌هایی که همراه دارند */
$groups = array_values(array_filter($groups, static fn(array $g) => !empty($byGroup[(int) $g['id']])));

$total = count($clients);

$pageTitle = setting('clients_page_title', 'همراهان نگاه مدیا');
$pageDesc  = setting('clients_page_lead');
$isListing = true;

require __DIR__ . '/includes/header.php';
?>

<main id="main" class="listing">

  <!-- ══════════════ سرصفحه ══════════════ -->
  <section class="lhead">
    <div class="wrap">
      <nav class="crumbs" aria-label="مسیر">
        <a href="<?= e(url()) ?>">صفحه اصلی</a>
        <span aria-hidden="true">/</span>
        <span>همراهان</span>
      </nav>

      <h1 class="lhead__title"><?= e($pageTitle) ?></h1>
      <?php if ($pageDesc !== ''): ?>
        <p class="lhead__lead"><?= e($pageDesc) ?></p>
      <?php endif; ?>

      <?php if ($groups): ?>
        <div class="lhead__stats">
          <span class="lstat">
            <strong><?= e(fa_digits((string) $total)) ?></strong>
            <small>مجموعه همراه</small>
          </span>
          <span class="lstat">
            <strong><?= e(fa_digits((string) count($groups))) ?></strong>
            <small>حوزه همکاری</small>
          </span>
        </div>

        <nav class="chips" aria-label="دسته‌ها">
          <?php foreach ($groups as $gi => $g): ?>
            <a class="chip" href="#g-<?= (int) $g['id'] ?>">
              <span class="chip__n"><?= e(fa_num($gi + 1)) ?></span>
              <span class="chip__t"><?= e($g['title']) ?></span>
              <span class="chip__c"><?= e(fa_digits((string) count($byGroup[(int) $g['id']]))) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
  </section>

  <!-- ══════════════ فهرست کامل ══════════════ -->
  <?php if (!$groups): ?>
    <section class="sec">
      <div class="wrap">
        <p class="empty-note">هنوز همراهی در سایت ثبت نشده است.</p>
      </div>
    </section>
  <?php else: ?>

    <?php foreach ($groups as $gi => $group): ?>
      <?php $list = $byGroup[(int) $group['id']] ?? []; ?>
      <section class="sec<?= $gi % 2 === 1 ? ' sec--tint' : '' ?>" id="g-<?= (int) $group['id'] ?>">
        <div class="wrap">
          <header class="cgroup__head reveal">
            <span class="cgroup__idx"><?= e(fa_num($gi + 1)) ?></span>
            <h2 class="cgroup__title"><?= e($group['title']) ?></h2>
            <?php if (!empty($group['subtitle'])): ?>
              <span class="cgroup__sub"><?= e($group['subtitle']) ?></span>
            <?php endif; ?>
            <span class="cgroup__count"><?= e(fa_digits((string) count($list))) ?> همراه</span>
          </header>

          <ul class="wall wall--big">
            <?php foreach ($list as $ci => $client): ?>
              <?php
              $logo = media_url($client);
              $cover = media_url($client, 'cover_file', 'cover_url');
              $logoMode = client_logo_mode($client);
              ?>
              <li class="wall__cell wall__cell--<?= e($logoMode) ?><?= $logo ? ' has-logo' : '' ?><?= $cover ? ' has-cover' : '' ?>">
                <a href="<?= e(brand_url($client)) ?>" title="مشاهده صفحه <?= e($client['name']) ?>">
                  <?php if ($cover): ?>
                    <span class="wall__cover" aria-hidden="true">
                      <img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async">
                    </span>
                  <?php endif; ?>
                  <span class="wall__index" aria-hidden="true">NO. <?= e(fa_num($ci + 1)) ?></span>
                  <span class="wall__visual">
                    <?php if ($logo): ?>
                      <span class="wall__logo"><img src="<?= e($logo) ?>" alt="<?= e($client['name']) ?>" loading="lazy" decoding="async"></span>
                    <?php else: ?>
                      <span class="wall__name"><?= e($client['name']) ?></span>
                    <?php endif; ?>
                  </span>
                  <span class="wall__go" aria-hidden="true">مشاهده صفحه ←</span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    <?php endforeach; ?>

  <?php endif; ?>

  <!-- ══════════════ فراخوان ══════════════ -->
  <section class="sec sec--dark">
    <div class="wrap cta-band">
      <div>
        <p class="label">NEXT</p>
        <h2 class="cta-band__title">برند بعدی، برند شما باشد.</h2>
        <p class="cta-band__lead">برای شروع، کافی است هدف و مسئله‌ی برندتان را برای ما بنویسید.</p>
      </div>
      <div class="cta-band__act">
        <a class="btn" href="<?= e(url()) ?>#contact"><?= e(setting('cta_primary', 'شروع یک پروژه')) ?></a>
        <a class="btn btn--ghost" href="<?= e(url()) ?>#projects"><?= e(setting('cta_secondary', 'دیدن نمونه‌کارها')) ?></a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
