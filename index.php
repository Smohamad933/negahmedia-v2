<?php
/**
 * نگاه مدیا | صفحه اصلی
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

$byGroup = [];
foreach ($clients as $c) {
    $byGroup[(int) $c['group_id']][] = $c;
}

/* ---------- انتخاب برندهای صفحه اصلی ---------- */
$homeMode  = setting('clients_home_mode', 'featured');
$homeCount = (int) setting('clients_home_count', '14');

if ($homeMode === 'featured') {
    $featured = array_values(array_filter($clients, static fn(array $c) => (int) $c['featured'] === 1));
    if ($featured === []) {
        $featured = $clients;   // اگر موردی ویژه نشده باشد، همه نمایش داده می‌شوند
    }
    $usingFallback = $featured === $clients && $clients !== [];
} else {
    $featured = $clients;
    $usingFallback = false;
}

$featured = $homeCount > 0 ? array_slice($featured, 0, $homeCount) : [];

/* تکرار برای پر شدن عرض نوار متحرک */
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
    $stripBase = array_merge($padded, $padded);   // دو نیمه یکسان برای حلقه بی‌وقفه
}

$totalClients   = count($clients);
$hasProjects    = $projects !== [];
$hasClientsSec  = $featured !== [] && $homeCount > 0;
$bandText       = trim(setting('band_text'));

/* کلمات تیتر هیرو برای انیمیشن ورود */
$heroTitle = setting('hero_title');
$heroWords = array_values(array_filter(preg_split('/\s+/u', $heroTitle) ?: []));

$phone = setting('phone');
$email = setting('email');

require __DIR__ . '/includes/header.php';
?>

<main id="main">

  <!-- ══════════════════ HERO ══════════════════ -->
  <section class="hero">
    <div class="wrap hero__grid">
      <div class="hero__main">
        <p class="kicker" data-anim="up" style="--d:0"><?= setting('hero_kicker') ?></p>

        <h1 class="hero__title" aria-label="<?= e($heroTitle) ?>">
          <?php foreach ($heroWords as $i => $w): ?>
            <span class="w" aria-hidden="true" style="--i:<?= (int) $i ?>"><i><?= e($w) ?></i></span>
          <?php endforeach; ?>
        </h1>

        <p class="hero__sub" data-anim="up" style="--d:3"><?= e(setting('hero_sub')) ?></p>

        <div class="hero__cta" data-anim="up" style="--d:4">
          <a class="btn btn--arrow" href="#contact">
            <span><?= e(setting('cta_primary', 'شروع یک پروژه')) ?></span>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5M11 18l-6-6 6-6"/>
            </svg>
          </a>
          <?php if ($hasProjects): ?>
            <a class="btn btn--ghost" href="#projects"><?= e(setting('cta_secondary', 'دیدن نمونه‌کارها')) ?></a>
          <?php else: ?>
            <a class="btn btn--ghost" href="#services">دیدن خدمات</a>
          <?php endif; ?>
        </div>
      </div>

      <aside class="hero__side" data-anim="right" style="--d:2">
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

  <!-- ══════════════════ نوار چرخان کوچک ══════════════════ -->
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

  <!-- ══════════════════ همراهان (نمونه ویژه) ══════════════════ -->
  <?php if ($hasClientsSec): ?>
  <section class="sec sec--clients" id="clients">
    <div class="wrap">
      <header class="head head--row">
        <div>
          <p class="label" data-anim="up">CLIENTS &amp; PARTNERS</p>
          <h2 class="head__title" data-anim="up" style="--d:1">برندها و همراهان</h2>
          <p class="head__lead" data-anim="up" style="--d:2"><?= e(setting('clients_lead')) ?></p>
        </div>
        <div class="head__aside" data-anim="up" style="--d:3">
          <span class="head__count"><?= e(fa_digits((string) $totalClients)) ?> <small>همراه</small></span>
        </div>
      </header>
    </div>

    <div class="logo-strip" data-anim="fade">
      <div class="logo-strip__track">
        <?php foreach ($stripBase as $c): ?>
          <?php $logo = media_url($c); ?>
          <div class="logo-strip__cell<?= $logo ? ' has-logo' : '' ?>">
            <a href="<?= e(brand_url($c)) ?>" title="صفحه <?= e($c['name']) ?>">
              <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($c['name']) ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <span class="logo-strip__name"><?= e($c['name']) ?></span>
              <?php endif; ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="wrap">
      <div class="clients-cta" data-anim="up">
        <a class="btn btn--ghost btn--arrow" href="<?= e(url('clients.php')) ?>">
          <span><?= e(setting('clients_all_label', 'دیدن همه همراهان')) ?></span>
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M11 18l-6-6 6-6"/>
          </svg>
        </a>
        <span class="clients-cta__hint">
          <?= e(fa_digits((string) count($featured))) ?> مورد در این صفحه ·
          فهرست کامل در صفحه همراهان
        </span>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════ نوار بزرگ متحرک ══════════════════ -->
  <?php if ($bandText !== ''): ?>
  <div class="band" aria-hidden="true">
    <?php
    $bandItems = array_values(array_filter(array_map('trim', explode('|', $bandText))));
    if ($bandItems === []) {
        $bandItems = [$bandText];
    }
    ?>
    <div class="band__row band__row--a">
      <?php for ($r = 0; $r < 3; $r++): foreach ($bandItems as $bi): ?>
        <span><?= e($bi) ?></span><b>◆</b>
      <?php endforeach; endfor; ?>
    </div>
    <div class="band__row band__row--b">
      <?php for ($r = 0; $r < 3; $r++): foreach ($bandItems as $bi): ?>
        <span class="outline"><?= e($bi) ?></span><b>◆</b>
      <?php endforeach; endfor; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════════════ خدمات ══════════════════ -->
  <section class="sec sec--tint" id="services">
    <div class="wrap">
      <header class="head">
        <p class="label" data-anim="up">SERVICES</p>
        <h2 class="head__title" data-anim="up" style="--d:1">خدمات</h2>
      </header>

      <ol class="srv">
        <?php foreach ($services as $i => $s): ?>
          <li class="srv__row" data-anim="up" style="--d:<?= min(6, $i) ?>">
            <span class="srv__num"><?= e(fa_num($i + 1)) ?></span>
            <h3 class="srv__title"><?= e($s['title']) ?></h3>
            <p class="srv__desc"><?= e($s['description']) ?></p>
            <span class="srv__rule" aria-hidden="true"></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ══════════════════ درباره + آمار ══════════════════ -->
  <section class="sec" id="about">
    <div class="wrap about">
      <div class="about__txt">
        <p class="label" data-anim="up">ABOUT NEGAH</p>
        <p class="about__manifesto" data-split data-anim="up" style="--d:1"><?= e(setting('manifesto')) ?></p>
        <p class="about__body" data-anim="up" style="--d:2"><?= e(setting('about_text')) ?></p>
      </div>

      <div class="about__stats">
        <?php foreach ($stats as $i => $s): ?>
          <div class="stat" data-anim="up" style="--d:<?= min(5, $i) ?>">
            <span class="stat__value" data-count="<?= e($s['value']) ?>"><?= e(fa_digits($s['value'])) ?></span>
            <span class="stat__label"><?= e($s['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══════════════════ مراحل همکاری ══════════════════ -->
  <section class="sec sec--tint" id="process">
    <div class="wrap">
      <header class="head">
        <p class="label" data-anim="up">PROCESS</p>
        <h2 class="head__title" data-anim="up" style="--d:1">مراحل همکاری</h2>
      </header>

      <ol class="steps" data-steps>
        <span class="steps__line" aria-hidden="true"><i></i></span>
        <?php foreach ($steps as $i => $st): ?>
          <li class="step" data-anim="up" style="--d:<?= min(4, $i) ?>">
            <span class="step__dot" aria-hidden="true"></span>
            <span class="step__num"><?= e(fa_num($i + 1)) ?></span>
            <h3 class="step__title"><?= e($st['title']) ?></h3>
            <p class="step__desc"><?= e($st['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ══════════════════ نمونه‌کارها ══════════════════ -->
  <?php if ($hasProjects): ?>
  <section class="sec" id="projects">
    <div class="wrap">
      <header class="head">
        <p class="label" data-anim="up">SELECTED WORK</p>
        <h2 class="head__title" data-anim="up" style="--d:1">نمونه‌کارها</h2>
      </header>

      <div class="works">
        <?php foreach ($projects as $i => $p): ?>
          <?php $img = media_url($p, 'image_file', 'image_url'); ?>
          <article class="work" data-anim="up" style="--d:<?= min(5, $i) ?>">
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

  <!-- ══════════════════ تماس ══════════════════ -->
  <section class="sec sec--dark" id="contact">
    <div class="wrap contact">
      <div class="contact__aside" data-anim="up">
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

      <div class="contact__form" data-anim="up" style="--d:1">
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

          <button class="btn btn--block btn--arrow" type="submit">
            <span>ارسال درخواست</span>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M19 12H5M11 18l-6-6 6-6"/>
            </svg>
          </button>
          <p class="form__hint">اطلاعات شما فقط برای تماس و بررسی پروژه استفاده می‌شود.</p>
        </form>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
