<?php
/**
 * نگاه مدیا | ظاهر سایت
 * آپلود فونت اختصاصی، رنگ تأکیدی، CSS سفارشی، تنظیمات انیمیشن و
 * نحوه نمایش همراهان در صفحه اصلی.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');

    /* ═══════════════ ذخیره ظاهر ═══════════════ */
    if ($act === 'save') {
        $messages = [];

        /* ---------- رنگ ---------- */
        $accent = trim((string) ($_POST['accent'] ?? ''));
        if ($accent !== '' && is_hex_color($accent)) {
            setting_save('accent', $accent);
        }

        /* ---------- سطح انیمیشن ---------- */
        setting_save('anim_level', ($_POST['anim_level'] ?? 'full') === 'off' ? 'off' : 'full');

        /* ---------- صفحه همراهان ---------- */
        setting_save('clients_home_count', (string) max(0, min(60, (int) ($_POST['clients_home_count'] ?? 12))));
        setting_save('clients_home_mode', ($_POST['clients_home_mode'] ?? 'featured') === 'first' ? 'first' : 'featured');
        setting_save('clients_all_label', trim((string) ($_POST['clients_all_label'] ?? '')));
        setting_save('clients_page_title', trim((string) ($_POST['clients_page_title'] ?? '')));
        setting_save('clients_page_lead', trim((string) ($_POST['clients_page_lead'] ?? '')));

        /* ---------- نوار بزرگ ---------- */
        setting_save('band_text', trim((string) ($_POST['band_text'] ?? '')));

        /* ---------- برچسب تماس در هدر ---------- */
        setting_save('call_label', trim((string) ($_POST['call_label'] ?? '')));

        /* ---------- فونت متن ---------- */
        $family = trim((string) ($_POST['font_family'] ?? ''));
        if ($family !== '') {
            setting_save('font_family', preg_replace('/[^A-Za-z0-9 _\-]/', '', $family));
        }
        $headingFamily = trim((string) ($_POST['font_heading_family'] ?? ''));
        if ($headingFamily !== '') {
            setting_save('font_heading_family', preg_replace('/[^A-Za-z0-9 _\-]/', '', $headingFamily));
        }

        $external = trim((string) ($_POST['font_external_url'] ?? ''));
        if ($external !== '' && !preg_match('~^https?://~i', $external)) {
            $external = '';
        }
        setting_save('font_external_url', $external);

        /* فونت متن: آپلود یا حذف */
        $up = upload_font('font_file');
        if ($up['error'] !== null) {
            flash($up['error'], 'err');
            redirect('appearance.php');
        }
        if ($up['file'] !== null) {
            delete_upload(setting('font_file'));
            setting_save('font_file', (string) $up['file']);
            $messages[] = 'فونت متن بارگذاری شد';
        } elseif (isset($_POST['remove_font_file'])) {
            delete_upload(setting('font_file'));
            setting_save('font_file', '');
            $messages[] = 'فونت متن حذف شد';
        }

        /* فونت تیتر */
        $upHead = upload_font('font_heading_file');
        if ($upHead['error'] !== null) {
            flash($upHead['error'], 'err');
            redirect('appearance.php');
        }
        if ($upHead['file'] !== null) {
            delete_upload(setting('font_heading_file'));
            setting_save('font_heading_file', (string) $upHead['file']);
            $messages[] = 'فونت تیتر بارگذاری شد';
        } elseif (isset($_POST['remove_font_heading_file'])) {
            delete_upload(setting('font_heading_file'));
            setting_save('font_heading_file', '');
            $messages[] = 'فونت تیتر حذف شد';
        }

        /* ---------- CSS سفارشی ---------- */
        setting_save('custom_css', sanitize_custom_css((string) ($_POST['custom_css'] ?? '')));

        style_bump();

        flash('تنظیمات ظاهر ذخیره شد' . ($messages ? ' — ' . implode('، ', $messages) . '.' : '.'));
        redirect('appearance.php');
    }

    /* ═══════════════ حذف فونت ═══════════════ */
    if ($act === 'font_delete') {
        $which = (string) ($_POST['which'] ?? 'body');
        if ($which === 'heading') {
            delete_upload(setting('font_heading_file'));
            setting_save('font_heading_file', '');
            flash('فونت تیتر حذف شد.');
        } else {
            delete_upload(setting('font_file'));
            setting_save('font_file', '');
            flash('فونت متن حذف شد.');
        }
        style_bump();
        redirect('appearance.php');
    }

    /* ═══════════════ پیش‌فرض کردن CSS ═══════════════ */
    if ($act === 'css_reset') {
        setting_save('custom_css', '');
        style_bump();
        flash('CSS سفارشی پاک شد.');
        redirect('appearance.php');
    }
}

$accent = setting('accent', '#A8752E');
if (!is_hex_color($accent)) {
    $accent = '#A8752E';
}

$fontFile    = setting('font_file');
$headingFile = setting('font_heading_file');

/** نمونه CSS برای راهنمایی */
$cssSample = <<<CSS
/* نمونه: گرد کردن گوشه‌ها
.card, .btn, .wall__cell, .panel { border-radius: 10px; }

/* نمونه: کم‌رنگ‌تر کردن متن توضیحی
.head__lead { color: #7A7268; }

/* نمونه: تغییر فاصله خطوط تیتر
.hero__title { line-height: 1.25; }
CSS;

admin_head('ظاهر، فونت و CSS');
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- ══════════════ فونت اختصاصی ══════════════ -->
  <div class="panel">
    <h2>فونت اختصاصی سایت</h2>
    <p class="muted" style="margin-bottom:20px">
      فایل فونت را از سیستم خود آپلود کنید تا در کل سایت استفاده شود.
      فرمت‌های مجاز: <code dir="ltr">WOFF2</code> (پیشنهادی، سبک‌ترین)،
      <code dir="ltr">WOFF</code>، <code dir="ltr">TTF</code>، <code dir="ltr">OTF</code> — حداکثر ۶ مگابایت.
    </p>

    <div class="row row--2">
      <!-- فونت متن -->
      <div class="f">
        <label for="font_file">فونت متن سایت</label>
        <input id="font_file" type="file" name="font_file" accept=".woff2,.woff,.ttf,.otf,font/*">

        <?php if ($fontFile !== ''): ?>
          <div style="display:flex;align-items:center;gap:12px;margin-top:12px;padding:12px;background:var(--paper);border:1px solid var(--line)">
            <span class="tag tag--ok">فعال</span>
            <span class="muted" dir="ltr" style="font-size:12.5px"><?= e(basename($fontFile)) ?></span>
            <label class="inline" style="margin-inline-start:auto">
              <input type="checkbox" name="remove_font_file" value="1">
              حذف
            </label>
          </div>
          <p class="hint" style="font-family:'<?= e(setting('font_family', 'Vazirmatn')) ?>', Tahoma; font-size:17px">
            پیش‌نمایش: برندت را قابلِ دیده‌شدن کن — ۰۱۲۳۴۵۶۷۸۹
          </p>
        <?php else: ?>
          <p class="hint">در حال حاضر فونت پیش‌فرض <strong>وزیرمتن</strong> استفاده می‌شود.</p>
        <?php endif; ?>

        <label for="font_family" style="margin-top:16px">نام خانواده فونت <span class="muted">(لاتین)</span></label>
        <input id="font_family" name="font_family" dir="ltr" placeholder="Vazirmatn" value="<?= e(setting('font_family', 'Vazirmatn')) ?>">
        <p class="hint">اگر مطمئن نیستید، همان نام پیش‌فرض را نگه دارید.</p>
      </div>

      <!-- فونت تیتر -->
      <div class="f">
        <label for="font_heading_file">فونت تیترها <span class="muted">(اختیاری)</span></label>
        <input id="font_heading_file" type="file" name="font_heading_file" accept=".woff2,.woff,.ttf,.otf,font/*">

        <?php if ($headingFile !== ''): ?>
          <div style="display:flex;align-items:center;gap:12px;margin-top:12px;padding:12px;background:var(--paper);border:1px solid var(--line)">
            <span class="tag tag--ok">فعال</span>
            <span class="muted" dir="ltr" style="font-size:12.5px"><?= e(basename($headingFile)) ?></span>
            <label class="inline" style="margin-inline-start:auto">
              <input type="checkbox" name="remove_font_heading_file" value="1">
              حذف
            </label>
          </div>
          <p class="hint" style="font-family:'<?= e(setting('font_heading_family', 'Vazirmatn')) ?>', Tahoma; font-weight:800; font-size:22px">
            برندت را قابلِ دیده‌شدن کن
          </p>
        <?php else: ?>
          <p class="hint">خالی بگذارید تا تیترها هم با فونت متن نمایش داده شوند.</p>
        <?php endif; ?>

        <label for="font_heading_family" style="margin-top:16px">نام خانواده فونت تیتر</label>
        <input id="font_heading_family" name="font_heading_family" dir="ltr" placeholder="Vazirmatn" value="<?= e(setting('font_heading_family', 'Vazirmatn')) ?>">
      </div>
    </div>

    <div class="f">
      <label for="font_external_url">یا آدرس CSS فونت بیرونی <span class="muted">(اختیاری)</span></label>
      <input id="font_external_url" name="font_external_url" dir="ltr" placeholder="https://cdn.example.com/font-face.css" value="<?= e(setting('font_external_url')) ?>">
      <p class="hint">
        اگر فونت شما روی یک سرویس بیرونی میزبانی می‌شود، آدرس فایل CSS آن را اینجا بگذارید.
        آپلود فایل روی سرور خودتان سریع‌تر و مطمئن‌تر است.
      </p>
    </div>
  </div>

  <!-- ══════════════ رنگ و انیمیشن ══════════════ -->
  <div class="panel">
    <h2>رنگ تأکیدی و حرکت</h2>

    <div class="row">
      <div class="f">
        <label for="accent">رنگ تأکیدی سایت</label>
        <div style="display:flex;align-items:center;gap:12px">
          <input id="accent" name="accent" type="color" value="<?= e($accent) ?>" style="flex:0 0 74px">
          <input dir="ltr" value="<?= e($accent) ?>" readonly style="flex:1;background:var(--paper)">
        </div>
        <p class="hint">
          این رنگ در دکمه‌ها، اعداد، برچسب‌ها و خطوط تأکیدی استفاده می‌شود.
          از آن روشن‌تر و تیره‌ترش به‌صورت خودکار ساخته می‌شود.
        </p>
      </div>

      <div class="f">
        <label for="anim_level">انیمیشن‌های سایت</label>
        <select id="anim_level" name="anim_level">
          <option value="full"<?= setting('anim_level', 'full') === 'full' ? ' selected' : '' ?>>فعال — ورود نرم، نوار متحرک، شمارش اعداد</option>
          <option value="off"<?= setting('anim_level') === 'off' ? ' selected' : '' ?>>خاموش — نمایش ساده و ثابت</option>
        </select>
        <p class="hint">در هر حالت، تنظیم «کاهش حرکت» سیستم کاربر رعایت می‌شود.</p>
      </div>

      <div class="f">
        <label for="call_label">برچسب بالای شماره تماس در هدر</label>
        <input id="call_label" name="call_label" value="<?= e(setting('call_label', 'تماس مستقیم')) ?>">
        <p class="hint">خالی بگذارید تا فقط شماره نمایش داده شود. سایت منو ندارد؛ هدر فقط شامل
        لوگوتایپ برند و همین بلوک شماره تماس است.</p>
      </div>

      <div class="f">
        <label for="band_text">متن نوار بزرگ متحرک</label>
        <input id="band_text" name="band_text" value="<?= e(setting('band_text')) ?>">
        <p class="hint">با <code dir="ltr">|</code> می‌توانید چند عبارت جدا کنید. خالی بگذارید تا نوار نمایش داده نشود.</p>
      </div>
    </div>
  </div>

  <!-- ══════════════ نمایش همراهان ══════════════ -->
  <div class="panel">
    <h2>نمایش برندها و همراهان</h2>

    <div class="row">
      <div class="f">
        <label for="clients_home_mode">کدام‌ها در صفحه اصلی دیده شوند؟</label>
        <select id="clients_home_mode" name="clients_home_mode">
          <option value="featured"<?= setting('clients_home_mode', 'featured') === 'featured' ? ' selected' : '' ?>>
            فقط موارد «ویژه» — انتخاب دستی شما
          </option>
          <option value="first"<?= setting('clients_home_mode') === 'first' ? ' selected' : '' ?>>
            اولین موارد فهرست — بر اساس ترتیب
          </option>
        </select>
        <p class="hint">
          برای انتخاب دستی، در بخش «برندها و لوگوها» گزینه «نمایش در صفحه اصلی» را برای برندهای مهم روشن کنید.
        </p>
      </div>

      <div class="f">
        <label for="clients_home_count">حداکثر تعداد در صفحه اصلی</label>
        <input id="clients_home_count" name="clients_home_count" type="number" dir="ltr" min="0" max="60" value="<?= (int) setting('clients_home_count', '14') ?>">
        <p class="hint">عدد ۰ یعنی این بخش در صفحه اصلی نمایش داده نشود.</p>
      </div>

      <div class="f">
        <label for="clients_all_label">متن دکمه «دیدن همه»</label>
        <input id="clients_all_label" name="clients_all_label" value="<?= e(setting('clients_all_label', 'دیدن همه همراهان')) ?>">
      </div>
    </div>

    <div class="row">
      <div class="f">
        <label for="clients_page_title">عنوان صفحه همراهان</label>
        <input id="clients_page_title" name="clients_page_title" value="<?= e(setting('clients_page_title')) ?>">
      </div>
      <div class="f">
        <label for="clients_page_lead">توضیح صفحه همراهان</label>
        <textarea id="clients_page_lead" name="clients_page_lead" rows="2"><?= e(setting('clients_page_lead')) ?></textarea>
      </div>
    </div>

    <div class="row" style="margin-top:6px">
      <div class="f">
        <a class="btn btn--ghost btn--sm" href="clients.php">انتخاب برندهای ویژه ›</a>
      </div>
      <div class="f">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('clients.php')) ?>" target="_blank" rel="noopener">مشاهده صفحه همراهان ↗</a>
      </div>
    </div>
  </div>

  <!-- ══════════════ CSS سفارشی ══════════════ -->
  <div class="panel">
    <h2>
      CSS سفارشی
      <span class="sp"></span>
      <span class="muted" style="font-weight:400;font-size:13px" id="css-len"></span>
    </h2>

    <div class="f">
      <label for="custom_css">کد CSS دلخواه شما</label>
      <textarea id="custom_css" name="custom_css" rows="14" dir="ltr" spellcheck="false"
        data-counter="#css-len"
        style="font-family:ui-monospace,'SF Mono',Menlo,Consolas,monospace;font-size:13.5px;line-height:1.75;text-align:left;direction:ltr;background:#FBFAF8"
      ><?= e(setting('custom_css')) ?></textarea>
      <p class="hint">
        این کد بعد از استایل اصلی سایت بارگذاری می‌شود، پس با آن اولویت دارد.
        می‌توانید از متغیرهای آماده استفاده کنید:
        <code dir="ltr">var(--accent)</code>، <code dir="ltr">var(--paper)</code>،
        <code dir="ltr">var(--ink)</code>، <code dir="ltr">var(--muted)</code>،
        <code dir="ltr">var(--font-body)</code>، <code dir="ltr">var(--font-head)</code>.
      </p>
    </div>

    <details style="margin-top:6px">
      <summary style="cursor:pointer;font-size:14px;font-weight:600;padding:8px 0">نمونه‌های آماده برای کپی کردن</summary>
      <pre dir="ltr" style="background:#FBFAF8;border:1px solid var(--line);padding:16px;overflow-x:auto;font-size:13px;line-height:1.8;text-align:left;direction:ltr;margin-top:10px"><?= e($cssSample) ?></pre>
    </details>

  </div>

  <div class="act">
    <button class="btn" type="submit" name="act" value="save">ذخیره تنظیمات ظاهر</button>
    <a class="btn btn--ghost" href="<?= e(url()) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
  </div>
</form>

<form method="post" data-confirm="کل CSS سفارشی پاک شود؟">
  <?= csrf_field() ?>
  <input type="hidden" name="act" value="css_reset">
  <p class="muted" style="margin-top:-8px">
    اگر CSS سفارشی باعث به‌هم‌ریختگی ظاهر شد، از این دکمه برای پاک کردن کامل آن استفاده کنید.
    <button class="btn btn--danger btn--sm" type="submit" style="margin-inline-start:10px">پاک کردن CSS سفارشی</button>
  </p>
</form>

<?php admin_foot(); ?>
