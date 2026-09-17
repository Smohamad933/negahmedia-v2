<?php
/**
 * نگاه مدیا | مدیریت صفحه اختصاصی و گالری هر برند
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

$clients = q('SELECT `id`,`name`,`visible` FROM `clients` ORDER BY `sort_order`, `id`');

$selectedId = (int) ($_GET['client'] ?? $_POST['client_id'] ?? 0);
if ($selectedId <= 0 && $clients) {
    $selectedId = (int) $clients[0]['id'];
}

$selectedClient = $selectedId > 0
    ? q1('SELECT * FROM `clients` WHERE `id` = ?', [$selectedId])
    : null;

function gallery_redirect(int $clientId): void
{
    redirect('gallery.php?client=' . $clientId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $client = $clientId > 0 ? q1('SELECT * FROM `clients` WHERE `id` = ?', [$clientId]) : null;

    if ($client === null) {
        flash('برند انتخاب‌شده پیدا نشد.', 'err');
        redirect('gallery.php');
    }

    /* ---------- اطلاعات بالای صفحه برند ---------- */
    if ($act === 'profile_save') {
        $intro = trim((string) ($_POST['intro'] ?? ''));
        $coverUrl = trim((string) ($_POST['cover_url'] ?? ''));

        if ($coverUrl !== '' && !preg_match('~^https?://~i', $coverUrl)) {
            flash('آدرس تصویر کاور باید با http یا https شروع شود.', 'err');
            gallery_redirect($clientId);
        }

        $upload = upload_image('cover_file', 'clients', 4);
        if ($upload['error'] !== null) {
            flash($upload['error'], 'err');
            gallery_redirect($clientId);
        }

        $finalCover = $client['cover_file'];
        if ($upload['file'] !== null) {
            $finalCover = $upload['file'];
            delete_upload($client['cover_file']);
        } elseif (isset($_POST['remove_cover'])) {
            delete_upload($client['cover_file']);
            $finalCover = null;
        }

        db_run(
            'UPDATE `clients` SET `intro`=?,`cover_file`=?,`cover_url`=? WHERE `id`=?',
            [$intro !== '' ? $intro : null, $finalCover, $coverUrl !== '' ? $coverUrl : null, $clientId]
        );
        flash('اطلاعات صفحه برند ذخیره شد.');
        gallery_redirect($clientId);
    }

    /* ---------- افزودن یا ویرایش آیتم گالری ---------- */
    if ($act === 'item_save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $title    = trim((string) ($_POST['title'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $desc     = trim((string) ($_POST['description'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $link     = trim((string) ($_POST['link'] ?? ''));
        $order    = (int) ($_POST['sort_order'] ?? 0);
        $visible  = isset($_POST['visible']) ? 1 : 0;

        if ($title === '') {
            flash('عنوان کار یا محتوا را وارد کنید.', 'err');
            gallery_redirect($clientId);
        }
        if ($imageUrl !== '' && !preg_match('~^https?://~i', $imageUrl)) {
            flash('آدرس تصویر باید با http یا https شروع شود.', 'err');
            gallery_redirect($clientId);
        }
        if ($link !== '' && !preg_match('~^https?://~i', $link)) {
            $link = 'https://' . $link;
        }

        $upload = upload_image('gallery_image', 'gallery', 5);
        if ($upload['error'] !== null) {
            flash($upload['error'], 'err');
            gallery_redirect($clientId);
        }
        $newFile = $upload['file'];
        $removeImage = isset($_POST['remove_image']);

        if ($id > 0) {
            $old = q1('SELECT * FROM `client_gallery` WHERE `id`=? AND `client_id`=?', [$id, $clientId]);
            if ($old === null) {
                if ($newFile !== null) {
                    delete_upload($newFile);
                }
                flash('این محتوای گالری پیدا نشد.', 'err');
                gallery_redirect($clientId);
            }

            $final = $old['image_file'];
            if ($newFile !== null) {
                $final = $newFile;
                delete_upload($old['image_file']);
            } elseif ($removeImage) {
                delete_upload($old['image_file']);
                $final = null;
            }

            db_run(
                'UPDATE `client_gallery` SET `title`=?,`category`=?,`description`=?,`image_file`=?,`image_url`=?,`link`=?,`sort_order`=?,`visible`=? WHERE `id`=? AND `client_id`=?',
                [$title, $category !== '' ? $category : null, $desc !== '' ? $desc : null, $final, $imageUrl !== '' ? $imageUrl : null, $link !== '' ? $link : null, $order, $visible, $id, $clientId]
            );
            flash('محتوای گالری به‌روزرسانی شد.');
        } else {
            db_run(
                'INSERT INTO `client_gallery` (`client_id`,`title`,`category`,`description`,`image_file`,`image_url`,`link`,`sort_order`,`visible`) VALUES (?,?,?,?,?,?,?,?,?)',
                [$clientId, $title, $category !== '' ? $category : null, $desc !== '' ? $desc : null, $newFile, $imageUrl !== '' ? $imageUrl : null, $link !== '' ? $link : null, $order, $visible]
            );
            flash('محتوای جدید به گالری اضافه شد.');
        }
        gallery_redirect($clientId);
    }

    /* ---------- حذف آیتم ---------- */
    if ($act === 'item_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = q1('SELECT * FROM `client_gallery` WHERE `id`=? AND `client_id`=?', [$id, $clientId]);
        if ($row !== null) {
            delete_upload($row['image_file']);
            db_run('DELETE FROM `client_gallery` WHERE `id`=? AND `client_id`=?', [$id, $clientId]);
            flash('محتوای گالری حذف شد.');
        }
        gallery_redirect($clientId);
    }

    /* ---------- جابه‌جایی آیتم ---------- */
    if ($act === 'item_move') {
        $id  = (int) ($_POST['id'] ?? 0);
        $dir = (string) ($_POST['dir'] ?? 'up');
        $row = q1('SELECT * FROM `client_gallery` WHERE `id`=? AND `client_id`=?', [$id, $clientId]);

        if ($row !== null) {
            $sql = $dir === 'up'
                ? 'SELECT `id`,`sort_order` FROM `client_gallery` WHERE `client_id`=? AND (`sort_order`<? OR (`sort_order`=? AND `id`<?)) ORDER BY `sort_order` DESC,`id` DESC LIMIT 1'
                : 'SELECT `id`,`sort_order` FROM `client_gallery` WHERE `client_id`=? AND (`sort_order`>? OR (`sort_order`=? AND `id`>?)) ORDER BY `sort_order` ASC,`id` ASC LIMIT 1';
            $other = q1($sql, [$clientId, (int) $row['sort_order'], (int) $row['sort_order'], (int) $row['id']]);
            if ($other !== null) {
                db_run('UPDATE `client_gallery` SET `sort_order`=? WHERE `id`=?', [(int) $other['sort_order'], (int) $row['id']]);
                db_run('UPDATE `client_gallery` SET `sort_order`=? WHERE `id`=?', [(int) $row['sort_order'], (int) $other['id']]);
            }
        }
        gallery_redirect($clientId);
    }
}

/* داده نهایی بعد از پردازش */
$selectedClient = $selectedId > 0 ? q1('SELECT * FROM `clients` WHERE `id`=?', [$selectedId]) : null;
$items = $selectedClient
    ? q('SELECT * FROM `client_gallery` WHERE `client_id`=? ORDER BY `sort_order`,`id`', [$selectedId])
    : [];
$editId = (int) ($_GET['edit'] ?? 0);
$edit = ($editId > 0 && $selectedClient !== null)
    ? q1('SELECT * FROM `client_gallery` WHERE `id`=? AND `client_id`=?', [$editId, $selectedId])
    : null;

admin_head('گالری برندها');
?>

<div class="panel">
  <h2>انتخاب برند برای مدیریت صفحه</h2>
  <?php if (!$clients): ?>
    <?= admin_empty('هنوز برندی ثبت نشده است. ابتدا از بخش «برندها و لوگوها» یک برند اضافه کنید.') ?>
  <?php else: ?>
    <form method="get" class="row" style="align-items:end">
      <div class="f" style="margin-bottom:0">
        <label for="client">برند</label>
        <select id="client" name="client">
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === $selectedId ? ' selected' : '' ?>>
              <?= e($c['name']) ?><?= (int) $c['visible'] !== 1 ? ' — پنهان' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="act" style="margin-bottom:18px">
        <button class="btn" type="submit">باز کردن صفحه</button>
        <?php if ($selectedClient): ?><a class="btn btn--ghost" href="<?= e(brand_url($selectedClient)) ?>" target="_blank" rel="noopener">مشاهده صفحه ↗</a><?php endif; ?>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php if ($selectedClient): ?>
  <div class="panel">
    <h2>اطلاعات صفحه «<?= e($selectedClient['name']) ?>»</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="profile_save">
      <input type="hidden" name="client_id" value="<?= (int) $selectedId ?>">

      <div class="f">
        <label for="intro">معرفی کوتاه</label>
        <textarea id="intro" name="intro" rows="4" placeholder="روایت کوتاهی از همکاری، حوزه فعالیت برند یا کاری که برای آن انجام داده‌اید."><?= e($selectedClient['intro'] ?? '') ?></textarea>
        <p class="hint">در بالای صفحه اختصاصی برند نمایش داده می‌شود.</p>
      </div>

      <div class="row row--2">
        <div class="f">
          <label for="cover_file">تصویر اصلی / کاور</label>
          <input id="cover_file" type="file" name="cover_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
          <p class="hint">PNG، JPG، WEBP، GIF یا SVG — حداکثر ۴ مگابایت. اندازه پیشنهادی: ۱۶۰۰×۹۰۰ پیکسل (نسبت ۱۶:۹) تا در کارت برند و صفحه اختصاصی بهتر نمایش داده شود.</p>
          <?php if (!empty($selectedClient['cover_file'])): ?>
            <p class="hint">فایل فعلی: <code dir="ltr"><?= e($selectedClient['cover_file']) ?></code></p>
            <label class="inline"><input type="checkbox" name="remove_cover" value="1"> حذف کاور فعلی</label>
          <?php endif; ?>
        </div>
        <div class="f">
          <label for="cover_url">یا آدرس کاور</label>
          <input id="cover_url" name="cover_url" dir="ltr" placeholder="https://" value="<?= e($selectedClient['cover_url'] ?? '') ?>">
          <p class="hint">اگر فایل آپلود شود، فایل بر آدرس بیرونی اولویت دارد.</p>
        </div>
      </div>

      <div class="act">
        <button class="btn" type="submit">ذخیره اطلاعات صفحه</button>
        <a class="btn btn--ghost" href="clients.php?edit=<?= (int) $selectedId ?>#form">ویرایش اطلاعات برند</a>
      </div>
    </form>
  </div>

  <div class="panel" id="item-form">
    <h2><?= $edit ? 'ویرایش محتوای گالری' : 'افزودن کار یا محتوای جدید' ?></h2>
    <p class="hint" style="margin-top:-8px;margin-bottom:18px">برای هر پروژه می‌توانید تصویر، عنوان، دسته، توضیح و لینک جداگانه ثبت کنید.</p>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="item_save">
      <input type="hidden" name="client_id" value="<?= (int) $selectedId ?>">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="row">
        <div class="f">
          <label for="title">عنوان کار / محتوا *</label>
          <input id="title" name="title" required value="<?= e($edit['title'] ?? '') ?>" placeholder="مثلاً: هویت بصری جدید">
        </div>
        <div class="f">
          <label for="category">دسته</label>
          <input id="category" name="category" value="<?= e($edit['category'] ?? '') ?>" placeholder="برندینگ، تولید محتوا، کمپین ...">
        </div>
        <div class="f">
          <label for="sort_order">ترتیب</label>
          <input id="sort_order" name="sort_order" type="number" dir="ltr" value="<?= (int) ($edit['sort_order'] ?? 0) ?>">
        </div>
      </div>

      <div class="f">
        <label for="description">توضیح کوتاه</label>
        <textarea id="description" name="description" rows="3" placeholder="در این پروژه چه کاری برای برند انجام شد؟"><?= e($edit['description'] ?? '') ?></textarea>
      </div>

      <div class="row row--2">
        <div class="f">
          <label for="gallery_image">آپلود تصویر</label>
          <input id="gallery_image" type="file" name="gallery_image" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
          <p class="hint">حداکثر ۵ مگابایت؛ پیشنهاد: تصویر با عرض حداقل ۱۲۰۰ پیکسل.</p>
          <?php if (!empty($edit['image_file'])): ?>
            <p class="hint">فایل فعلی: <code dir="ltr"><?= e($edit['image_file']) ?></code></p>
            <label class="inline"><input type="checkbox" name="remove_image" value="1"> حذف تصویر فعلی</label>
          <?php endif; ?>
        </div>
        <div class="f">
          <label for="image_url">یا آدرس تصویر</label>
          <input id="image_url" name="image_url" dir="ltr" placeholder="https://" value="<?= e($edit['image_url'] ?? '') ?>">
          <label for="link" style="margin-top:16px">لینک این کار <span class="muted">(اختیاری)</span></label>
          <input id="link" name="link" dir="ltr" placeholder="https://" value="<?= e($edit['link'] ?? '') ?>">
        </div>
      </div>

      <div class="f">
        <label class="inline"><input type="checkbox" name="visible" value="1" <?= !isset($edit['visible']) || (int) $edit['visible'] === 1 ? 'checked' : '' ?>> نمایش در صفحه برند</label>
      </div>

      <div class="act">
        <button class="btn" type="submit"><?= $edit ? 'به‌روزرسانی محتوا' : 'افزودن به گالری' ?></button>
        <?php if ($edit): ?><a class="btn btn--ghost" href="gallery.php?client=<?= (int) $selectedId ?>#item-form">انصراف</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="panel">
    <h2>فهرست گالری <span class="tag"><?= e(fa_digits((string) count($items))) ?> مورد</span></h2>
    <?php if (!$items): ?>
      <?= admin_empty('هنوز محتوایی برای این برند ثبت نشده است. اولین پروژه یا تصویر را از فرم بالا اضافه کنید.') ?>
    <?php else: ?>
      <div class="gallery-admin-grid">
        <?php foreach ($items as $item): ?>
          <?php $src = media_url($item, 'image_file', 'image_url'); ?>
          <article class="gallery-admin-card">
            <div class="gallery-admin-card__media">
              <?php if ($src): ?><img src="<?= e($src) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
              <?php else: ?><span class="muted">بدون تصویر</span><?php endif; ?>
            </div>
            <div class="gallery-admin-card__body">
              <h3><?= e($item['title']) ?></h3>
              <p><?= e($item['category'] ?: 'بدون دسته') ?><?= (int) $item['visible'] !== 1 ? ' · پنهان' : '' ?></p>
              <?php if (!empty($item['description'])): ?><small><?= e($item['description']) ?></small><?php endif; ?>
            </div>
            <div class="logo-card__act">
              <a class="btn btn--ghost btn--sm" href="gallery.php?client=<?= (int) $selectedId ?>&amp;edit=<?= (int) $item['id'] ?>#item-form">ویرایش</a>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="item_move">
                <input type="hidden" name="client_id" value="<?= (int) $selectedId ?>">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="dir" value="up">
                <button class="btn btn--ghost btn--sm" type="submit">↑</button>
              </form>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="item_move">
                <input type="hidden" name="client_id" value="<?= (int) $selectedId ?>">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="dir" value="down">
                <button class="btn btn--ghost btn--sm" type="submit">↓</button>
              </form>
              <form method="post" data-confirm="این محتوا از گالری حذف شود؟">
                <?= csrf_field() ?>
                <input type="hidden" name="act" value="item_delete">
                <input type="hidden" name="client_id" value="<?= (int) $selectedId ?>">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button class="btn btn--danger btn--sm" type="submit">حذف</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php admin_foot(); ?>
