<?php
/**
 * نگاه مدیا | صفحه اصلی
 *
 * ساختار صفحه عمداً از لایه داده جدا نگه داشته شده است: متن‌های پنل،
 * خدمات، آمار، همراهان، پروژه‌ها و مراحل همکاری در تمام بخش‌ها از دیتابیس
 * خوانده می‌شوند و این فایل فقط چیدمان رابط عمومی را می‌سازد.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

/* ═════════════════════════════════════════════════════════
   فرم تماس
   ═════════════════════════════════════════════════════════ */
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

/* ═════════════════════════════════════════════════════════
   واکشی داده‌ها
   ═════════════════════════════════════════════════════════ */
$groups   = q('SELECT * FROM `client_groups` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$clients  = q('SELECT * FROM `clients` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$services = q('SELECT * FROM `services` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$steps    = q('SELECT * FROM `process_steps` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$stats    = q('SELECT * FROM `stats` WHERE `visible` = 1 ORDER BY `sort_order`, `id`');
$projects = q('SELECT * FROM `projects` WHERE `visible` = 1 ORDER BY `sort_order`, `id` LIMIT 9');

/* ---------- انتخاب برندهای صفحه اصلی ---------- */
$homeMode  = setting('clients_home_mode', 'featured');
$homeCount = (int) setting('clients_home_count', '14');

if ($homeMode === 'featured') {
    $featured = array_values(array_filter($clients, static fn(array $c) => (int) $c['featured'] === 1));
    if ($featured === []) {
        $featured = $clients;
    }
} else {
    $featured = $clients;
}

$featured = $homeCount > 0 ? array_slice($featured, 0, $homeCount) : [];

/* تکرار برای پر شدن عرض نوار لوگو */
$stripBase = $featured;
if ($stripBase !== []) {
    $repeat = 1;
    while (count($stripBase) * $repeat < 9 && $repeat < 6) {
        $repeat++;
    }
    $padded = [];
    for ($i = 0; $i < $repeat; $i++) {
        foreach ($featured as $item) {
            $padded[] = $item;
        }
    }
    $stripBase = array_merge($padded, $padded);
}

$totalClients  = count($clients);
$hasProjects   = $projects !== [];
$hasClientsSec = $featured !== [] && $homeCount > 0;
$bandText      = trim(setting('band_text'));
$heroStats     = array_slice($stats, 0, 3);
$heroStats[]   = ['value' => '+' . $totalClients, 'label' => 'برند و همراه'];
$phone         = setting('phone');
$email         = setting('email');

require __DIR__ . '/includes/header.php';
?>

<main id="main">

  <!-- ══════════════════ HERO ══════════════════ -->
  <section class="hero" id="home">
    <div class="hero__backdrop" aria-hidden="true">
      <span class="hero__orb hero__orb--one"></span>
      <span class="hero__orb hero__orb--two"></span>
      <span class="hero__gridline hero__gridline--one"></span>
      <span class="hero__gridline hero__gridline--two"></span>
    </div>

    <div class="wrap">
      <div class="hero__layout">
        <div class="hero__copy">
          <div class="hero__eyebrow" data-anim="up">
            <span class="hero__eyebrow-mark"><i></i><i></i><i></i></span>
            <span><?= e(html_entity_decode(strip_tags(setting('hero_kicker', 'CREATIVE & ADVERTISING AGENCY')), ENT_QUOTES, 'UTF-8')) ?></span>
          </div>

          <h1 class="hero__title" data-anim="up" style="--d:1"><?= e(setting('hero_title')) ?></h1>
          <p class="hero__sub" data-anim="up" style="--d:2"><?= e(setting('hero_sub')) ?></p>

          <div class="hero__actions" data-anim="up" style="--d:3">
            <a class="btn btn--royal btn--arrow" href="#contact">
              <span><?= e(setting('cta_primary', 'شروع یک پروژه')) ?></span>
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 18l-6-6 6-6"/>
              </svg>
            </a>
            <a class="btn btn--outline" href="#projects"><?= e(setting('cta_secondary', 'دیدن نمونه‌کارها')) ?></a>
          </div>

          <div class="hero__signature" data-anim="up" style="--d:4">
            <span class="hero__signature-line" aria-hidden="true"></span>
            <span><?= e(setting('hero_tagline', 'ایده‌ای که دیده می‌شود، اثری که می‌ماند.')) ?></span>
          </div>
        </div>

        <div class="hero__stage" data-anim="fade" style="--d:2" aria-label="هویت بصری نگاه مدیا">
          <div class="stage__topline">
            <span>NEGAH / STUDIO</span>
            <span class="stage__status"><i></i> AVAILABLE FOR GOOD WORK</span>
          </div>
          <div class="stage__canvas">
            <span class="stage__corner stage__corner--tl" aria-hidden="true"></span>
            <span class="stage__corner stage__corner--br" aria-hidden="true"></span>
            <span class="stage__halo stage__halo--outer" aria-hidden="true"></span>
            <span class="stage__halo stage__halo--inner" aria-hidden="true"></span>
            <span class="stage__ray stage__ray--a" aria-hidden="true"></span>
            <span class="stage__ray stage__ray--b" aria-hidden="true"></span>
            <div class="stage__monogram" aria-hidden="true">ن</div>
            <span class="stage__number">۰۱</span>
            <span class="stage__vertical">CREATIVE<br>ADVERTISING<br>STUDIO</span>
            <span class="stage__word">NEGAH</span>
          </div>
          <div class="stage__bottomline">
            <span><?= e(setting('site_short', 'نگاه مدیا')) ?></span>
            <span>EST. ۱۴۰۰</span>
          </div>
        </div>
      </div>

      <div class="hero__metrics" data-anim="up" style="--d:5">
        <?php foreach ($heroStats as $i => $stat): ?>
          <div class="metric">
            <span class="metric__index">۰<?= e(fa_num($i + 1)) ?></span>
            <strong><?= e(fa_digits((string) ($stat['value'] ?? '—'))) ?></strong>
            <span><?= e($stat['label'] ?? '') ?></span>
          </div>
        <?php endforeach; ?>
        <a class="metric metric--link" href="#services">
          <span class="metric__index">↘</span>
          <strong>خدمات ما</strong>
          <span>آنچه انجام می‌دهیم</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ══════════════════ نوار تخصص‌ها ══════════════════ -->
  <div class="ticker" aria-hidden="true">
    <div class="ticker__track">
      <?php
      $tickerWords = ['تدوین سینمایی', 'موشن‌گرافیک', 'هویت بصری', 'تیزر تبلیغاتی', 'طراحی صدا', 'کالرگرید', 'تولید محتوا', 'نگاه متفاوت'];
      for ($i = 0; $i < 2; $i++):
          foreach ($tickerWords as $word): ?>
            <span><?= e($word) ?></span><i>✦</i>
          <?php endforeach;
      endfor; ?>
    </div>
  </div>

  <!-- ══════════════════ خدمات ══════════════════ -->
  <section class="section services-section" id="services">
    <div class="wrap">
      <header class="section-head section-head--split">
        <div class="section-head__title">
          <span class="section-number">۰۱</span>
          <div>
            <span class="section-eyebrow">WHAT WE DO</span>
            <h2>هر پروژه، یک زبان تصویری تازه</h2>
          </div>
        </div>
        <p>از ایده و استراتژی تا آخرین فریم؛ تیم نگاه مدیا روایت برند را با تصویر، صدا و حرکت یکپارچه می‌سازد.</p>
      </header>

      <div class="service-grid">
        <?php foreach ($services as $i => $service): ?>
          <article class="service-card service-card--<?= e((string) (($i % 4) + 1)) ?>" data-anim="up" style="--d:<?= min(5, $i) ?>">
            <div class="service-card__head">
              <span class="service-card__number">۰<?= e(fa_num($i + 1)) ?></span>
              <span class="service-card__arrow" aria-hidden="true">↙</span>
            </div>
            <div class="service-card__icon" aria-hidden="true"><span></span><span></span><span></span></div>
            <h3><?= e($service['title']) ?></h3>
            <p><?= e($service['description']) ?></p>
            <span class="service-card__foot">NEGah / CAPABILITY</span>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══════════════════ نمونه‌کارها ══════════════════ -->
  <section class="section portfolio-section" id="projects">
    <div class="wrap">
      <header class="section-head section-head--split">
        <div class="section-head__title">
          <span class="section-number">۰۲</span>
          <div>
            <span class="section-eyebrow">SELECTED WORKS</span>
            <h2>آثاری که در ذهن می‌مانند</h2>
          </div>
        </div>
        <p><?= $hasProjects ? 'منتخبی از پروژه‌هایی که با نگاه متفاوت، جزئیات دقیق و اجرای یکپارچه ساخته‌ایم.' : 'ویترین پروژه‌ها در حال تکمیل است؛ قاب بعدی می‌تواند برای برند شما باشد.' ?></p>
      </header>

      <?php if ($hasProjects): ?>
        <div class="project-board">
          <?php foreach ($projects as $i => $project): ?>
            <?php $image = media_url($project, 'image_file', 'image_url'); ?>
            <article class="work<?= $i === 0 ? ' work--featured' : '' ?>" data-anim="up" style="--d:<?= min(5, $i) ?>">
              <div class="work__media">
                <?php if ($image): ?>
                  <?php if (!empty($project['link'])): ?><a href="<?= e($project['link']) ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاهده <?= e($project['title']) ?>"><?php endif; ?>
                    <img src="<?= e($image) ?>" alt="<?= e($project['title']) ?>" loading="lazy" decoding="async">
                    <span class="work__shade"></span>
                  <?php if (!empty($project['link'])): ?></a><?php endif; ?>
                <?php else: ?>
                  <div class="work__placeholder">
                    <span class="work__placeholder-symbol">✦</span>
                    <span>NO. <?= e(fa_num($i + 1)) ?></span>
                    <strong><?= e($project['title']) ?></strong>
                  </div>
                <?php endif; ?>
                <span class="work__index">۰<?= e(fa_num($i + 1)) ?></span>
                <?php if (!empty($project['category'])): ?><span class="work__badge"><?= e($project['category']) ?></span><?php endif; ?>
              </div>
              <div class="work__body">
                <span class="work__eyebrow">CASE STUDY / <?= e(fa_num($i + 1)) ?></span>
                <h3 class="work__title"><?= e($project['title']) ?></h3>
                <?php if (!empty($project['description'])): ?><p class="work__desc"><?= e($project['description']) ?></p><?php endif; ?>
                <?php if (!empty($project['link'])): ?><a class="work__link" href="<?= e($project['link']) ?>" target="_blank" rel="noopener noreferrer">مشاهده پروژه <span>↙</span></a><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="work-empty" data-anim="up">
          <div class="work-empty__visual" aria-hidden="true"><span>ن</span><i></i></div>
          <div>
            <span class="section-eyebrow">THE NEXT FRAME</span>
            <h3>اولین قاب، می‌تواند برای برند شما باشد.</h3>
            <p>نمونه‌کارهای منتخب نگاه مدیا به‌زودی در این بخش نمایش داده می‌شوند.</p>
            <a class="btn btn--royal" href="#contact">شروع یک پروژه</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ══════════════════ همراهان ══════════════════ -->
  <?php if ($hasClientsSec): ?>
  <section class="section clients-section" id="clients">
    <div class="wrap">
      <header class="section-head section-head--split">
        <div class="section-head__title">
          <span class="section-number">۰۳</span>
          <div>
            <span class="section-eyebrow">BRANDS WE TRUST</span>
            <h2>همراهانی که به ما اعتماد کردند</h2>
          </div>
        </div>
        <p><?= e(setting('clients_lead')) ?></p>
      </header>
    </div>

    <div class="logo-strip" data-anim="fade">
      <div class="logo-strip__track">
        <?php foreach ($stripBase as $client): ?>
          <?php
          $logo = media_url($client);
          $logoMode = client_logo_mode($client);
          ?>
          <div class="logo-strip__cell logo-strip__cell--<?= e($logoMode) ?><?= $logo ? ' has-logo' : '' ?>">
            <a href="<?= e(brand_url($client)) ?>" title="صفحه <?= e($client['name']) ?>">
              <span class="logo-strip__index" aria-hidden="true">/</span>
              <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($client['name']) ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <span class="logo-strip__name"><?= e($client['name']) ?></span>
              <?php endif; ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="wrap clients-section__foot">
      <span><b><?= e(fa_digits((string) $totalClients)) ?></b> برند و همراه در پروژه‌های نگاه مدیا</span>
      <a class="text-link" href="<?= e(url('clients.php')) ?>">
        <?= e(setting('clients_all_label', 'دیدن همه همراهان')) ?> <span aria-hidden="true">↙</span>
      </a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════ درباره نگاه ══════════════════ -->
  <section class="section about-section" id="about">
    <div class="wrap">
      <div class="about-grid">
        <div class="about-copy">
          <div class="section-head__title">
            <span class="section-number">۰۴</span>
            <div>
              <span class="section-eyebrow">ABOUT NEGAH</span>
              <h2>نگاه ما، ساختن چیزی‌ست که فقط دیده نشود.</h2>
            </div>
          </div>
          <p class="about-copy__manifesto"><?= e(setting('manifesto')) ?></p>
          <p class="about-copy__body"><?= e(setting('about_text')) ?></p>
          <a class="text-link" href="#contact">داستان پروژه‌تان را تعریف کنید <span aria-hidden="true">↙</span></a>
        </div>

        <div class="about-art" data-anim="fade">
          <div class="about-art__ring about-art__ring--one" aria-hidden="true"></div>
          <div class="about-art__ring about-art__ring--two" aria-hidden="true"></div>
          <div class="about-art__core" aria-hidden="true">ن</div>
          <span class="about-art__caption">THINK<br>MAKE<br>MATTER</span>
          <span class="about-art__year">۱۴۰۰ — ۲۰۲۶</span>
        </div>
      </div>

      <div class="stat-grid">
        <?php foreach ($stats as $i => $stat): ?>
          <div class="stat" data-anim="up" style="--d:<?= min(4, $i) ?>">
            <span class="stat__index">۰<?= e(fa_num($i + 1)) ?></span>
            <strong><?= e(fa_digits((string) $stat['value'])) ?></strong>
            <span><?= e($stat['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══════════════════ مراحل همکاری ══════════════════ -->
  <section class="section process-section" id="process">
    <div class="wrap">
      <header class="section-head section-head--center">
        <span class="section-number">۰۵</span>
        <span class="section-eyebrow">HOW WE WORK</span>
        <h2>از مسئله تا اثری که منتشر می‌شود</h2>
        <p>فرآیندی روشن و همراه؛ برای اینکه هر تصمیم، از ایده تا اجرا، دلیل خودش را داشته باشد.</p>
      </header>

      <ol class="steps">
        <?php foreach ($steps as $i => $step): ?>
          <li class="step" data-anim="up" style="--d:<?= min(4, $i) ?>">
            <div class="step__top"><span>۰<?= e(fa_num($i + 1)) ?></span><i aria-hidden="true">↙</i></div>
            <h3><?= e($step['title']) ?></h3>
            <p><?= e($step['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ══════════════════ نوار شعار ══════════════════ -->
  <?php if ($bandText !== ''): ?>
  <div class="band" aria-hidden="true">
    <?php $bandItems = array_values(array_filter(array_map('trim', explode('|', $bandText)))) ?: [$bandText]; ?>
    <div class="band__row band__row--a">
      <?php for ($r = 0; $r < 3; $r++): foreach ($bandItems as $item): ?>
        <span><?= e($item) ?></span><b>✦</b>
      <?php endforeach; endfor; ?>
    </div>
    <div class="band__row band__row--b">
      <?php for ($r = 0; $r < 3; $r++): foreach ($bandItems as $item): ?>
        <span class="outline"><?= e($item) ?></span><b>✦</b>
      <?php endforeach; endfor; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════════════ تماس و سفارش ══════════════════ -->
  <section class="section contact-section" id="contact">
    <div class="wrap">
      <div class="contact-shell">
        <div class="contact__aside">
          <span class="section-number">۰۶</span>
          <span class="section-eyebrow">START A PROJECT</span>
          <h2 class="contact__title">ایده‌تان را به حرکت درآوریم.</h2>
          <p class="contact__lead">اطلاعات اولیه پروژه را وارد کنید تا تیم استودیو با شما تماس بگیرد و مسیر همکاری را روشن کنیم.</p>

          <dl class="contact__meta">
            <?php if ($phone !== ''): ?>
              <div><dt>تلفن</dt><dd><a href="tel:<?= e($phone) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a></dd></div>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
              <div><dt>ایمیل</dt><dd><a href="mailto:<?= e($email) ?>" dir="ltr"><?= e($email) ?></a></dd></div>
            <?php endif; ?>
            <?php if (setting('address') !== ''): ?>
              <div><dt>دفتر</dt><dd><?= e(setting('address')) ?></dd></div>
            <?php endif; ?>
          </dl>

          <div class="contact__stamp" aria-hidden="true"><span>ن</span><small>LET'S MAKE<br>SOMETHING<br>MATTER</small></div>
        </div>

        <div class="contact__form">
          <div class="form-heading"><span>۰۱ — BRIEF</span><p>چند خط درباره پروژه‌تان بنویسید.</p></div>
          <?php if ($formSent): ?>
            <div class="note note--ok">پیام شما ثبت شد. به‌زودی با شما تماس می‌گیریم.</div>
          <?php endif; ?>
          <?php if ($formErrors): ?>
            <div class="note note--err"><ul><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>

          <form method="post" action="#contact" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="contact">
            <input type="hidden" name="opened_at" value="<?= e((string) time()) ?>">
            <div class="trap" aria-hidden="true"><label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <div class="f">
              <label for="c-name">نام و نام خانوادگی</label>
              <input id="c-name" name="name" required autocomplete="name" value="<?= e($old['name']) ?>" placeholder="نام شما">
            </div>
            <div class="f2">
              <div class="f">
                <label for="c-phone">شماره تماس</label>
                <input id="c-phone" name="phone" dir="ltr" required inputmode="tel" autocomplete="tel" value="<?= e($old['phone']) ?>" placeholder="۰۹۱۲...">
              </div>
              <div class="f">
                <label for="c-email">ایمیل <span class="opt">اختیاری</span></label>
                <input id="c-email" name="email" type="email" dir="ltr" autocomplete="email" value="<?= e($old['email']) ?>" placeholder="name@email.com">
              </div>
            </div>
            <div class="f">
              <label for="c-subject">موضوع همکاری <span class="opt">اختیاری</span></label>
              <input id="c-subject" name="subject" value="<?= e($old['subject']) ?>" placeholder="مثلاً تولید محتوای برند">
            </div>
            <div class="f">
              <label for="c-body">توضیح پروژه</label>
              <textarea id="c-body" name="body" rows="5" required placeholder="هدف، زمان‌بندی یا هر چیزی که فکر می‌کنید باید بدانیم..."><?= e($old['body']) ?></textarea>
            </div>
            <button class="btn btn--royal btn--block btn--arrow" type="submit">
              <span>ارسال درخواست مشاوره</span>
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 18l-6-6 6-6"/>
              </svg>
            </button>
            <p class="form__hint">اطلاعات شما فقط برای تماس و بررسی پروژه استفاده می‌شود.</p>
          </form>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
