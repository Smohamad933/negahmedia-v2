<?php
/**
 * نگاه مدیا | مدیریت برندها، همراهان و لوگوها
 * دو روش ثبت لوگو: آپلود فایل تصویری یا وارد کردن آدرس تصویر.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

/* ══════════════ پردازش فرم‌ها ══════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');

    /* ---------- ذخیره دسته ---------- */
    if ($act === 'group_save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $title    = trim((string) ($_POST['title'] ?? ''));
        $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
        $order    = (int) ($_POST['sort_order'] ?? 0);
        $visible  = isset($_POST['visible']) ? 1 : 0;

        if ($title === '') {
            flash('عنوان دسته را وارد کنید.', 'err');
            redirect('clients.php');
        }

        if ($id > 0) {
            db_run('UPDATE `client_groups` SET `title`=?,`subtitle`=?,`sort_order`=?,`visible`=? WHERE `id`=?', [$title, $subtitle, $order, $visible, $id]);
            flash('دسته به‌روزرسانی شد.');
        } else {
            db_run('INSERT INTO `client_groups` (`title`,`subtitle`,`sort_order`,`visible`) VALUES (?,?,?,?)', [$title, $subtitle, $order, $visible]);
            flash('دسته جدید ساخته شد.');
        }
        redirect('clients.php');
    }

    /* ---------- حذف دسته ---------- */
    if ($act === 'group_delete') {
        $id = (int) ($_POST['id'] ?? 0);

        foreach (q('SELECT `id`,`logo_file`,`cover_file` FROM `clients` WHERE `group_id` = ?', [$id]) as $row) {
            delete_upload($row['logo_file']);
            delete_upload($row['cover_file']);
            foreach (q('SELECT `image_file` FROM `client_gallery` WHERE `client_id` = ?', [(int) $row['id']]) as $galleryRow) {
                delete_upload($galleryRow['image_file']);
            }
            db_run('DELETE FROM `client_gallery` WHERE `client_id` = ?', [(int) $row['id']]);
        }
        db_run('DELETE FROM `clients` WHERE `group_id` = ?', [$id]);
        db_run('DELETE FROM `client_groups` WHERE `id` = ?', [$id]);

        flash('دسته و همه همراهان آن حذف شدند.');
        redirect('clients.php');
    }

    /* ---------- ذخیره همراه ---------- */
    if ($act === 'client_save') {
        $id      = (int) ($_POST['id'] ?? 0);
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $name    = trim((string) ($_POST['name'] ?? ''));
        $logoUrl = trim((string) ($_POST['logo_url'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $intro   = trim((string) ($_POST['intro'] ?? ''));
        $coverUrl = trim((string) ($_POST['cover_url'] ?? ''));
        $order   = (int) ($_POST['sort_order'] ?? 0);
        $visible = isset($_POST['visible']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;

        if ($name === '') {
            flash('نام مجموعه را وارد کنید.', 'err');
            redirect($id > 0 ? 'clients.php?edit=' . $id : 'clients.php');
        }
        if ($groupId <= 0) {
            flash('یک دسته انتخاب کنید.', 'err');
            redirect('clients.php');
        }

        // اعتبارسنجی آدرس تصویر (در صورت وارد کردن لینک)
        if ($logoUrl !== '' && !preg_match('~^https?://~i', $logoUrl)) {
            flash('آدرس تصویر باید با http یا https شروع شود.', 'err');
            redirect('clients.php');
        }
        if ($coverUrl !== '' && !preg_match('~^https?://~i', $coverUrl)) {
            flash('آدرس تصویر کاور باید با http یا https شروع شود.', 'err');
            redirect('clients.php');
        }
        if ($website !== '' && !preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }

        // آپلود لوگو و تصویر کاور صفحه اختصاصی
        $upload = upload_image('logo_file', 'logos', 2);
        if ($upload['error'] !== null) {
            flash($upload['error'], 'err');
            redirect('clients.php');
        }
        $newFile = $upload['file'];

        $coverUpload = upload_image('cover_file', 'clients', 4);
        if ($coverUpload['error'] !== null) {
            if ($newFile !== null) {
                delete_upload($newFile);
            }
            flash($coverUpload['error'], 'err');
            redirect('clients.php');
        }
        $newCoverFile = $coverUpload['file'];

        $removeLogo  = isset($_POST['remove_logo']);
        $removeCover = isset($_POST['remove_cover']);

        if ($id > 0) {
            $old = q1('SELECT * FROM `clients` WHERE `id` = ?', [$id]);
            if ($old === null) {
                flash('این رکورد پیدا نشد.', 'err');
                redirect('clients.php');
            }

            $finalFile = $old['logo_file'];
            if ($newFile !== null) {
                $finalFile = $newFile;
                delete_upload($old['logo_file']);
            } elseif ($removeLogo) {
                delete_upload($old['logo_file']);
                $finalFile = null;
            }

            $finalCover = $old['cover_file'];
            if ($newCoverFile !== null) {
                $finalCover = $newCoverFile;
                delete_upload($old['cover_file']);
            } elseif ($removeCover) {
                delete_upload($old['cover_file']);
                $finalCover = null;
            }

            db_run(
                'UPDATE `clients` SET `group_id`=?,`name`=?,`logo_file`=?,`logo_url`=?,`website`=?,`intro`=?,`cover_file`=?,`cover_url`=?,`featured`=?,`sort_order`=?,`visible`=? WHERE `id`=?',
                [$groupId, $name, $finalFile, $logoUrl !== '' ? $logoUrl : null, $website !== '' ? $website : null, $intro !== '' ? $intro : null, $finalCover, $coverUrl !== '' ? $coverUrl : null, $featured, $order, $visible, $id]
            );
            flash('«' . $name . '» به‌روزرسانی شد.');
        } else {
            db_run(
                'INSERT INTO `clients` (`group_id`,`name`,`logo_file`,`logo_url`,`website`,`intro`,`cover_file`,`cover_url`,`featured`,`sort_order`,`visible`) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$groupId, $name, $newFile, $logoUrl !== '' ? $logoUrl : null, $website !== '' ? $website : null, $intro !== '' ? $intro : null, $newCoverFile, $coverUrl !== '' ? $coverUrl : null, $featured, $order, $visible]
            );
            flash('«' . $name . '» اضافه شد.');
        }
        redirect('clients.php');
    }

    /* ---------- حذف همراه ---------- */
    if ($act === 'client_delete') {
        $id  = (int) ($_POST['id'] ?? 0);
        $row = q1('SELECT * FROM `clients` WHERE `id` = ?', [$id]);
        if ($row !== null) {
            delete_upload($row['logo_file']);
            delete_upload($row['cover_file']);
            foreach (q('SELECT `image_file` FROM `client_gallery` WHERE `client_id` = ?', [$id]) as $galleryRow) {
                delete_upload($galleryRow['image_file']);
            }
            db_run('DELETE FROM `client_gallery` WHERE `client_id` = ?', [$id]);
            db_run('DELETE FROM `clients` WHERE `id` = ?', [$id]);
            flash('همراه و صفحه اختصاصی آن حذف شد.');
        }
        redirect('clients.php');
    }

    /* ---------- ویژه / عادی کردن سریع ---------- */
    if ($act === 'client_feature') {
        $id  = (int) ($_POST['id'] ?? 0);
        $row = q1('SELECT `id`,`name`,`featured` FROM `clients` WHERE `id` = ?', [$id]);

        if ($row !== null) {
            $now = (int) $row['featured'] === 1 ? 0 : 1;
            db_run('UPDATE `clients` SET `featured` = ? WHERE `id` = ?', [$now, $id]);
            flash($now === 1 ? '«' . $row['name'] . '» به صفحه اصلی اضافه شد.' : '«' . $row['name'] . '» از صفحه اصلی برداشته شد.');
        }
        redirect('clients.php');
    }

    /* ---------- ویژه کردن همه موارد یک دسته ---------- */
    if ($act === 'group_feature_all') {
        $id  = (int) ($_POST['id'] ?? 0);
        $val = ($_POST['value'] ?? '1') === '1' ? 1 : 0;

        db_run('UPDATE `clients` SET `featured` = ? WHERE `group_id` = ?', [$val, $id]);
        flash($val === 1 ? 'همه همراهان این دسته به صفحه اصلی اضافه شدند.' : 'همه همراهان این دسته از صفحه اصلی برداشته شدند.');
        redirect('clients.php');
    }

    /* ---------- جابه‌جایی ترتیب ---------- */
    if ($act === 'client_move') {
        $id  = (int) ($_POST['id'] ?? 0);
        $dir = (string) ($_POST['dir'] ?? 'up');
        $row = q1('SELECT * FROM `clients` WHERE `id` = ?', [$id]);

        if ($row !== null) {
            $sql = $dir === 'up'
                ? 'SELECT `id`,`sort_order` FROM `clients` WHERE `group_id` = ? AND (`sort_order` < ? OR (`sort_order` = ? AND `id` < ?)) ORDER BY `sort_order` DESC, `id` DESC LIMIT 1'
                : 'SELECT `id`,`sort_order` FROM `clients` WHERE `group_id` = ? AND (`sort_order` > ? OR (`sort_order` = ? AND `id` > ?)) ORDER BY `sort_order` ASC, `id` ASC LIMIT 1';

            $other = q1($sql, [(int) $row['group_id'], (int) $row['sort_order'], (int) $row['sort_order'], (int) $row['id']]);

            if ($other !== null) {
                db_run('UPDATE `clients` SET `sort_order`=? WHERE `id`=?', [(int) $other['sort_order'], (int) $row['id']]);
                db_run('UPDATE `clients` SET `sort_order`=? WHERE `id`=?', [(int) $row['sort_order'], (int) $other['id']]);
            }
        }
        redirect('clients.php');
    }
}

/* ══════════════ داده‌ها ══════════════ */
$groups  = q('SELECT * FROM `client_groups` ORDER BY `sort_order`, `id`');
$clients = q('SELECT * FROM `clients` ORDER BY `sort_order`, `id`');

$byGroup = [];
foreach ($clients as $c) {
    $byGroup[(int) $c['group_id']][] = $c;
}

$edit      = isset($_GET['edit']) ? q1('SELECT * FROM `clients` WHERE `id` = ?', [(int) $_GET['edit']]) : null;
$editGroup = isset($_GET['edit_group']) ? q1('SELECT * FROM `client_groups` WHERE `id` = ?', [(int) $_GET['edit_group']]) : null;
$onlyNoLogo = isset($_GET['no_logo']);

$featuredCount = (int) qv('SELECT COUNT(*) FROM `clients` WHERE `featured` = 1', [], 0);
$homeCount     = (int) setting('clients_home_count', '14');
$homeMode      = setting('clients_home_mode', 'featured');

admin_head('برندها و لوگوها');
?>

<!-- ══════════════ وضعیت نمایش در صفحه اصلی ══════════════ -->
<div class="panel">
  <h2>
    نمایش در صفحه اصلی
    <span class="sp"></span>
    <a class="btn btn--ghost btn--sm" href="appearance.php">تنظیمات ظاهر ›</a>
  </h2>

  <div class="cards" style="margin-bottom:0">
    <div class="card">
      <p class="card__lbl">FEATURED</p>
      <p class="card__val"><?= e(fa_digits((string) $featuredCount)) ?></p>
      <p class="card__sub">مورد «ویژه» انتخاب‌شده</p>
    </div>
    <div class="card">
      <p class="card__lbl">HOME LIMIT</p>
      <p class="card__val"><?= e(fa_digits((string) $homeCount)) ?></p>
      <p class="card__sub">حداکثر نمایش در صفحه اصلی</p>
    </div>
    <div class="card">
      <p class="card__lbl">MODE</p>
      <p class="card__val" style="font-size:20px;line-height:1.7">
        <?= $homeMode === 'featured' ? 'انتخاب دستی' : 'اولین موارد فهرست' ?>
      </p>
      <p class="card__sub"><a href="appearance.php">تغییر حالت ›</a></p>
    </div>
    <div class="card">
      <p class="card__lbl">FULL PAGE</p>
      <p class="card__val" style="font-size:20px;line-height:1.7">صفحه همراهان</p>
      <p class="card__sub"><a href="<?= e(url('clients.php')) ?>" target="_blank" rel="noopener">مشاهده ↗</a></p>
    </div>
  </div>

  <?php if ($homeMode === 'featured' && $featuredCount === 0): ?>
    <p class="hint" style="margin-top:16px">
      هنوز هیچ برندی «ویژه» نشده است؛ بنابراین در صفحه اصلی، اولین موارد هر دسته نمایش داده می‌شود.
      با دکمه ★ کنار هر برند، آن را به صفحه اصلی اضافه کنید.
    </p>
  <?php endif; ?>
</div>

<!-- ══════════════ افزودن / ویرایش همراه ══════════════ -->
<div class="panel" id="form">
  <h2><?= $edit ? 'ویرایش همراه: ' . e($edit['name']) : 'افزودن همراه جدید' ?></h2>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="client_save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

    <div class="row">
      <div class="f">
        <label for="group_id">دسته *</label>
        <select id="group_id" name="group_id" required>
          <option value="">— انتخاب دسته —</option>
          <?php foreach ($groups as $g): ?>
            <option value="<?= (int) $g['id'] ?>"<?= isset($edit['group_id']) && (int) $edit['group_id'] === (int) $g['id'] ? ' selected' : '' ?>>
              <?= e($g['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label for="name">نام مجموعه *</label>
        <input id="name" name="name" required value="<?= e($edit['name'] ?? '') ?>">
      </div>
    </div>

    <div class="row row--2">
      <div class="f">
        <label for="logo_file">روش اول: آپلود فایل لوگو</label>
        <input id="logo_file" type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
        <p class="hint">
          فرمت‌های PNG، JPG، WEBP، GIF یا SVG — حداکثر ۲ مگابایت.<br>
          پیشنهاد: تصویر با پس‌زمینه شفاف و عرض حدود ۴۰۰ پیکسل.
        </p>
        <?php if (!empty($edit['logo_file'])): ?>
          <p class="hint" style="margin-top:10px">
            فایل فعلی: <code dir="ltr"><?= e($edit['logo_file']) ?></code>
          </p>
          <label class="inline" style="margin-top:8px">
            <input type="checkbox" name="remove_logo" value="1">
            حذف لوگوی فعلی
          </label>
        <?php endif; ?>
      </div>

      <div class="f">
        <label for="logo_url">روش دوم: آدرس تصویر لوگو</label>
        <input id="logo_url" name="logo_url" dir="ltr" placeholder="https://example.com/logo.png" value="<?= e($edit['logo_url'] ?? '') ?>">
        <p class="hint">اگر فایل آپلود شود، فایل بر لینک اولویت دارد.</p>
      </div>
    </div>

    <div class="f">
      <label for="intro">معرفی کوتاه صفحه اختصاصی <span class="muted">(اختیاری)</span></label>
      <textarea id="intro" name="intro" rows="3" placeholder="مثلاً: طراحی هویت بصری و تولید محتوای این برند را از سال ... همراهی کرده‌ایم."><?= e($edit['intro'] ?? '') ?></textarea>
      <p class="hint">این متن بالای صفحه اختصاصی برند نمایش داده می‌شود.</p>
    </div>

    <div class="row row--2">
      <div class="f">
        <label for="cover_file">تصویر اصلی صفحه برند</label>
        <input id="cover_file" type="file" name="cover_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
        <p class="hint">اختیاری — پیشنهاد: تصویر افقی با عرض حداقل ۱۲۰۰ پیکسل، حداکثر ۴ مگابایت.</p>
        <?php if (!empty($edit['cover_file'])): ?>
          <p class="hint" style="margin-top:10px">فایل فعلی: <code dir="ltr"><?= e($edit['cover_file']) ?></code></p>
          <label class="inline" style="margin-top:8px">
            <input type="checkbox" name="remove_cover" value="1">
            حذف تصویر فعلی
          </label>
        <?php endif; ?>
      </div>
      <div class="f">
        <label for="cover_url">یا آدرس تصویر اصلی</label>
        <input id="cover_url" name="cover_url" dir="ltr" placeholder="https://example.com/brand-cover.jpg" value="<?= e($edit['cover_url'] ?? '') ?>">
        <p class="hint">اگر فایل آپلود شود، فایل بر لینک اولویت دارد.</p>
      </div>
    </div>

    <div class="row">
      <div class="f">
        <label for="website">وب‌سایت <span class="muted">(اختیاری)</span></label>
        <input id="website" name="website" dir="ltr" placeholder="https://" value="<?= e($edit['website'] ?? '') ?>">
        <p class="hint">در صفحه اختصاصی برند، دکمه‌ای برای باز کردن این آدرس نمایش داده می‌شود.</p>
      </div>
      <div class="f">
        <label for="sort_order">ترتیب نمایش</label>
        <input id="sort_order" name="sort_order" type="number" dir="ltr" value="<?= (int) ($edit['sort_order'] ?? 0) ?>">
        <p class="hint">عدد کمتر = بالاتر.</p>
      </div>
      <div class="f">
        <label>وضعیت‌ها</label>
        <label class="inline">
          <input type="checkbox" name="visible" value="1" <?= !isset($edit['visible']) || (int) $edit['visible'] === 1 ? 'checked' : '' ?>>
          نمایش در سایت
        </label>
        <label class="inline" style="margin-top:8px">
          <input type="checkbox" name="featured" value="1" <?= isset($edit['featured']) && (int) $edit['featured'] === 1 ? 'checked' : '' ?>>
          نمایش در صفحه اصلی (ویژه)
        </label>
        <p class="hint">موارد ویژه در بخش «برندها و همراهان» صفحه اصلی دیده می‌شوند؛ بقیه در صفحه «همه همراهان».</p>
      </div>
    </div>

    <div class="act">
      <button class="btn" type="submit"><?= $edit ? 'به‌روزرسانی' : 'افزودن همراه' ?></button>
      <?php if ($edit): ?><a class="btn btn--ghost" href="clients.php">انصراف</a><?php endif; ?>
      <?php if ($edit): ?>
        <span class="muted">در حال ویرایش: <?= e($edit['name']) ?></span>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- ══════════════ دسته‌ها ══════════════ -->
<div class="panel">
  <h2><?= $editGroup ? 'ویرایش دسته' : 'افزودن دسته' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="group_save">
    <input type="hidden" name="id" value="<?= (int) ($editGroup['id'] ?? 0) ?>">
    <div class="row">
      <div class="f">
        <label for="g_title">عنوان دسته *</label>
        <input id="g_title" name="title" required value="<?= e($editGroup['title'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="g_sub">زیرعنوان</label>
        <input id="g_sub" name="subtitle" value="<?= e($editGroup['subtitle'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="g_order">ترتیب</label>
        <input id="g_order" name="sort_order" type="number" dir="ltr" value="<?= (int) ($editGroup['sort_order'] ?? 0) ?>">
      </div>
      <div class="f">
        <label>وضعیت</label>
        <label class="inline">
          <input type="checkbox" name="visible" value="1" <?= !isset($editGroup['visible']) || (int) $editGroup['visible'] === 1 ? 'checked' : '' ?>>
          نمایش در سایت
        </label>
      </div>
    </div>
    <div class="act">
      <button class="btn" type="submit"><?= $editGroup ? 'به‌روزرسانی دسته' : 'افزودن دسته' ?></button>
      <?php if ($editGroup): ?><a class="btn btn--ghost" href="clients.php">انصراف</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- ══════════════ فهرست همراهان ══════════════ -->
<?php foreach ($groups as $gi => $g): ?>
  <?php $list = $byGroup[(int) $g['id']] ?? []; ?>
  <?php if ($onlyNoLogo) { $list = array_values(array_filter($list, static fn($c) => media_url($c) === null)); } ?>

  <div class="panel">
    <h2>
      <span class="tag"><?= e(fa_num($gi + 1)) ?></span>
      <?= e($g['title']) ?>
      <span class="tag"><?= e(fa_digits((string) count($list))) ?> همراه</span>
      <?php if ((int) $g['visible'] !== 1): ?><span class="tag">پنهان</span><?php endif; ?>
      <span class="sp"></span>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="group_feature_all">
        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
        <input type="hidden" name="value" value="1">
        <button class="btn btn--ghost btn--sm" type="submit" title="همه موارد این دسته در صفحه اصلی نمایش داده شوند">★ همه ویژه</button>
      </form>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="group_feature_all">
        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
        <input type="hidden" name="value" value="0">
        <button class="btn btn--ghost btn--sm" type="submit" title="برداشتن همه موارد این دسته از صفحه اصلی">☆ هیچ‌کدام</button>
      </form>

      <a class="btn btn--ghost btn--sm" href="clients.php?edit_group=<?= (int) $g['id'] ?>#form">ویرایش دسته</a>
      <form method="post" data-confirm="این دسته و همه همراهان و لوگوهای آن حذف شوند؟">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="group_delete">
        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
        <button class="btn btn--danger btn--sm" type="submit">حذف دسته</button>
      </form>
    </h2>

    <?php if (!$list): ?>
      <?= admin_empty('در این دسته همراهی ثبت نشده است.') ?>
    <?php else: ?>
      <div class="logo-grid">
        <?php foreach ($list as $c): ?>
          <?php $src = media_url($c); ?>
          <div class="logo-card">
            <div class="logo-card__box">
              <?php if ($src): ?>
                <img src="<?= e($src) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="muted" style="font-size:12.5px">بدون لوگو</span>
              <?php endif; ?>
            </div>

            <div class="logo-card__name">
              <?php if ((int) $c['featured'] === 1): ?><span class="tag tag--new" title="در صفحه اصلی نمایش داده می‌شود">★ ویژه</span> <?php endif; ?>
              <?= e($c['name']) ?>
            </div>

            <div class="logo-card__meta">
              <?= $src ? ($c['logo_file'] !== null && $c['logo_file'] !== '' ? 'فایل آپلودی' : 'لینک تصویر') : '—' ?>
              <?php if ((int) $c['visible'] !== 1): ?> · پنهان<?php endif; ?>
              <?php if (!empty($c['website'])): ?> · <span dir="ltr">↗</span><?php endif; ?>
            </div>

            <div class="logo-card__act">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="client_feature">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn <?= (int) $c['featured'] === 1 ? '' : 'btn--ghost' ?> btn--sm" type="submit"
                        title="<?= (int) $c['featured'] === 1 ? 'برداشتن از صفحه اصلی' : 'افزودن به صفحه اصلی' ?>">
                  <?= (int) $c['featured'] === 1 ? '★' : '☆' ?>
                </button>
              </form>

              <a class="btn btn--ghost btn--sm" href="gallery.php?client=<?= (int) $c['id'] ?>">صفحه و گالری</a>
              <a class="btn btn--ghost btn--sm" href="clients.php?edit=<?= (int) $c['id'] ?>#form">ویرایش</a>

              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="client_move">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="dir" value="up">
                <button class="btn btn--ghost btn--sm" type="submit" title="جابه‌جایی به بالا">↑</button>
              </form>

              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="client_move">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="dir" value="down">
                <button class="btn btn--ghost btn--sm" type="submit" title="جابه‌جایی به پایین">↓</button>
              </form>

              <form method="post" data-confirm="«<?= e($c['name']) ?>» حذف شود؟">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="client_delete">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn--danger btn--sm" type="submit">حذف</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<p class="muted">
  نکته: تا زمانی که لوگویی ثبت نشده باشد، نام برند با تایپوگرافی فارسی در سایت نمایش داده می‌شود؛
  پس سایت از همان ابتدا کامل به نظر می‌رسد.
</p>

<?php admin_foot(); ?>
