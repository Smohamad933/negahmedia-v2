<?php
/**
 * نگاه مدیا | صفحه اصلی
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

/* =========================================================
   فرم تماس
   ========================================================= */
$formErrors = [];
$formSent   = false;
$old        = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'contact') {
    csrf_verify();

    $old = [
        'name'    => trim((string) ($_POST['name'] ?? '')),
        'phone'   => trim((string) ($_POST['phone'] ?? '')),
        'email'   => trim((string) ($_POST['email'] ?? '')),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'body'    => trim((string) ($_POST['body'] ?? '')),
    ];
    $trap   = trim((string) ($_POST['website'] ?? ''));
    $opened = (int) ($_POST['opened_at'] ?? 0);

    if ($trap !== '') {
        $formErrors[] = 'ارسال انجام نشد.';
    }
    if ($opened > 0 && (time() - $opened) < 2) {
        $formErrors[] = 'کمی با تأمل بیشتری فرم را پر کنید.';
    }
    if (mb_strlen($old['name']) < 3) {
        $formErrors[] = 'نام و نام خانوادگی را کامل وارد کنید.';
    }
    if (!preg_match('/^[\x{06F0}-\x{06F9}0-9+\-\s()]{8,22}$/u', str_replace('ي', 'ی', $old['phone']))) {
        $formErrors[] = 'شماره تماس معتبر نیست.';
    }
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'ایمیل وارد‌شده معتبر نیست.';
    }
    if (mb_strlen($old['body']) < 10) {
        $formErrors[] = 'توضیح پروژه را کامل‌تر بنویسید.';
    }

    if (!$formErrors) {
        db_run(
            'INSERT INTO `messages` (`name`,`phone`,`email`,`subject`,`body`) VALUES (?,?,?,?,?)',
            [$old['name'], $old['phone'], $old['email'], $old['subject'], $old['body']]
        );
        $formSent = true;
        $old = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'body' => ''];
    }
}

/* =========================================================
   واکشی داده‌ها
   ========================================================= */
$groups   = q('SELECT * FROM `client_groups` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$clients  = q('SELECT * FROM `clients` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$services = q('SELECT * FROM `services` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$steps    = q('SELECT * FROM `process_steps` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$stats    = q('SELECT * FROM `stats` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$projects = q('SELECT * FROM `projects` WHERE `visible` = 1 ORDER BY `sort_order`, `id` LIMIT 9');

$byGroup = [];
foreach ($clients as $c) {
    $byGroup[(int) $c['group_id']][] = $c;
}

$hasProjects = $projects !== [];
$phone = setting('phone');
$email = setting('email');

require __DIR__ . '/includes/header.php';
?>

<main id="main">

  <!-- ══════════════ HERO ══════════════ -->
  <section class="hero">
    <div class="wrap hero__grid">
      <div class="hero__main">
        <p class="kicker reveal"><?= setting('hero_kicker') ?></p>
        <h1 class="hero__title reveal"><?= e(setting('hero_title')) ?></h1>
        <p class="hero__sub reveal"><?= e(setting('hero_sub')) ?></p>
        <div class="hero__cta reveal">
          <a class="btn" href="#contact"><?= e(setting('cta_primary', 'شروع یک پروژه')) ?></a>
          <?php if ($hasProjects): ?>
            <a class="btn btn--ghost" href="#projects"><?= e(setting('cta_secondary', 'دیدن نمونه‌کارها')) ?></a>
          <?php else: ?>
            <a class="btn btn--ghost" href="#services">دیدن خدمات</a>
          <?php endif; ?>
        </div>
      </div>

      <aside class="hero__side reveal">
        <div class="hero__tag">
          <span class="hero__tagline"><?= e(setting('hero_tagline')) ?></span>
        </div>
        <dl class="hero__meta">
          <div>
            <dt>حوزه کار</dt>
            <dd>برندینگ · محتوا · کمپین · دیجیتال</dd>
          </div>
          <div>
            <dt>دفتر</dt>
            <dd><?= e(setting('address', 'اهواز')) ?></dd>
          </div>
          <?php if ($phone !== ''): ?>
          <div>
            <dt>تماس</dt>
            <dd><a href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a></dd>
          </div>
          <?php endif; ?>
        </dl>
      </aside>
    </div>
  </section>

  <!-- ══════════════ TICKER ══════════════ -->
  <div class="ticker" aria-hidden="true">
    <div class="ticker__track">
      <?php
      $words = ['هویت بصری', 'تولید محتوا', 'کمپین تبلیغاتی', 'دیجیتال مارکتینگ', 'طراحی وب', 'عکاسی', 'سوشال مدیا', 'استراتژی برند'];
      for ($i = 0; $i < 2; $i++):
          foreach ($words as $w): ?>
            <span><?= e($w) ?></span><i>◆</i>
          <?php endforeach;
      endfor; ?>
    </div>
  </div>

  <!-- ══════════════ CLIENTS ══════════════ -->
  <section class="sec" id="clients">
    <div class="wrap">
      <header class="head">
        <p class="label reveal">CLIENTS &amp; PARTNERS</p>
        <h2 class="head__title reveal">برندها و همراهان</h2>
        <p class="head__lead reveal"><?= e(setting('clients_lead')) ?></p>
      </header>

      <?php foreach ($groups as $gi => $group): ?>
        <?php $list = $byGroup[(int) $group['id']] ?? []; ?>
        <?php if (!$list) { continue; } ?>
        <section class="cgroup reveal">
          <div class="cgroup__head">
            <span class="cgroup__idx"><?= e(fa_num($gi + 1)) ?></span>
            <h3 class="cgroup__title"><?= e($group['title']) ?></h3>
            <?php if (!empty($group['subtitle'])): ?>
              <span class="cgroup__sub"><?= e($group['subtitle']) ?></span>
            <?php endif; ?>
            <span class="cgroup__count"><?= e(fa_digits((string) count($list))) ?> همراه</span>
          </div>

          <ul class="wall">
            <?php foreach ($list as $client): ?>
              <?php $logo = media_url($client); ?>
              <li class="wall__cell<?= $logo ? ' has-logo' : '' ?>">
                <?php $open = !empty($client['website']); ?>
                <?php if ($open): ?>
                  <a href="<?= e($client['website']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($client['name']) ?>">
                <?php endif; ?>

                <?php if ($logo): ?>
                  <img src="<?= e($logo) ?>" alt="<?= e($client['name']) ?>" loading="lazy" decoding="async">
                <?php else: ?>
                  <span class="wall__name"><?= e($client['name']) ?></span>
                <?php endif; ?>

                <?php if ($open): ?></a><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ══════════════ SERVICES ══════════════ -->
  <section class="sec sec--tint" id="services">
    <div class="wrap">
      <header class="head">
        <p class="label reveal">SERVICES</p>
        <h2 class="head__title reveal">خدمات</h2>
      </header>

      <ol class="srv">
        <?php foreach ($services as $i => $s): ?>
          <li class="srv__row reveal">
            <span class="srv__num"><?= e(fa_num($i + 1)) ?></span>
            <h3 class="srv__title"><?= e($s['title']) ?></h3>
            <p class="srv__desc"><?= e($s['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ══════════════ ABOUT + STATS ══════════════ -->
  <section class="sec" id="about">
    <div class="wrap about">
      <div class="about__txt">
        <p class="label reveal">ABOUT NEGAH</p>
        <p class="about__manifesto reveal"><?= e(setting('manifesto')) ?></p>
        <p class="about__body reveal"><?= e(setting('about_text')) ?></p>
      </div>

      <div class="about__stats">
        <?php foreach ($stats as $s): ?>
          <div class="stat reveal">
            <span class="stat__value"><?= e(fa_digits($s['value'])) ?></span>
            <span class="stat__label"><?= e($s['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══════════════ PROCESS ══════════════ -->
  <section class="sec sec--tint" id="process">
    <div class="wrap">
      <header class="head">
        <p class="label reveal">PROCESS</p>
        <h2 class="head__title reveal">مراحل همکاری</h2>
      </header>

      <ol class="steps">
        <?php foreach ($steps as $i => $st): ?>
          <li class="step reveal">
            <span class="step__num"><?= e(fa_num($i + 1)) ?></span>
            <h3 class="step__title"><?= e($st['title']) ?></h3>
            <p class="step__desc"><?= e($st['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ══════════════ PROJECTS ══════════════ -->
  <?php if ($hasProjects): ?>
  <section class="sec" id="projects">
    <div class="wrap">
      <header class="head">
        <p class="label reveal">SELECTED WORK</p>
        <h2 class="head__title reveal">نمونه‌کارها</h2>
      </header>

      <div class="works">
        <?php foreach ($projects as $p): ?>
          <?php $img = media_url($p, 'image_file', 'image_url'); ?>
          <article class="work reveal">
            <?php if ($img): ?>
              <div class="work__media">
                <?php if (!empty($p['link'])): ?><a href="<?= e($p['link']) ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                  <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" decoding="async">
                <?php if (!empty($p['link'])): ?></a><?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="work__body">
              <?php if (!empty($p['category'])): ?><span class="work__cat"><?= e($p['category']) ?></span><?php endif; ?>
              <h3 class="work__title"><?= e($p['title']) ?></h3>
              <?php if (!empty($p['description'])): ?><p class="work__desc"><?= e($p['description']) ?></p><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════ CONTACT ══════════════ -->
  <section class="sec sec--dark" id="contact">
    <div class="wrap contact">
      <div class="contact__aside reveal">
        <p class="label">START A PROJECT</p>
        <h2 class="contact__title">شروع یک پروژه</h2>
        <p class="contact__lead">هدف، مخاطب و مسئله‌ی برندتان را برای ما بنویسید؛ در اولین فرصت تماس می‌گیریم و مسیر کار را روشن می‌کنیم.</p>

        <dl class="contact__meta">
          <?php if ($phone !== ''): ?>
            <div>
              <dt>تلفن</dt>
              <dd><a href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a></dd>
            </div>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
            <div>
              <dt>ایمیل</dt>
              <dd><a href="mailto:<?= e($email) ?>" dir="ltr"><?= e($email) ?></a></dd>
            </div>
          <?php endif; ?>
          <?php if (setting('address') !== ''): ?>
            <div>
              <dt>دفتر</dt>
              <dd><?= e(setting('address')) ?></dd>
            </div>
          <?php endif; ?>
        </dl>
      </div>

      <div class="contact__form reveal">
        <?php if ($formSent): ?>
          <div class="note note--ok">پیام شما ثبت شد. به‌زودی با شما تماس می‌گیریم.</div>
        <?php endif; ?>

        <?php if ($formErrors): ?>
          <div class="note note--err">
            <ul><?php foreach ($formErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <form method="post" action="#contact" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="contact">
          <input type="hidden" name="opened_at" value="<?= e((string) time()) ?>">
          <div class="trap" aria-hidden="true">
            <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="f">
            <label for="c-name">نام و نام خانوادگی</label>
            <input id="c-name" name="name" required autocomplete="name" value="<?= e($old['name']) ?>">
          </div>

          <div class="f2">
            <div class="f">
              <label for="c-phone">شماره تماس</label>
              <input id="c-phone" name="phone" dir="ltr" required inputmode="tel" autocomplete="tel" value="<?= e($old['phone']) ?>">
            </div>
            <div class="f">
              <label for="c-email">ایمیل <span class="opt">اختیاری</span></label>
              <input id="c-email" name="email" type="email" dir="ltr" autocomplete="email" value="<?= e($old['email']) ?>">
            </div>
          </div>

          <div class="f">
            <label for="c-subject">موضوع همکاری <span class="opt">اختیاری</span></label>
            <input id="c-subject" name="subject" value="<?= e($old['subject']) ?>">
          </div>

          <div class="f">
            <label for="c-body">توضیح پروژه</label>
            <textarea id="c-body" name="body" rows="5" required><?= e($old['body']) ?></textarea>
          </div>

          <button class="btn btn--block" type="submit">ارسال درخواست</button>
          <p class="form__hint">اطلاعات شما فقط برای تماس و بررسی پروژه استفاده می‌شود.</p>
        </form>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
