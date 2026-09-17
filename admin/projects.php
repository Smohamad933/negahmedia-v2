<?php
/**
 * نگاه مدیا | مدیریت نمونه‌کارها
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');
    $id  = (int) ($_POST['id'] ?? 0);

    if ($act === 'delete') {
        $row = q1('SELECT * FROM `projects` WHERE `id` = ?', [$id]);
        if ($row !== null) {
            delete_upload($row['image_file']);
            db_run('DELETE FROM `projects` WHERE `id` = ?', [$id]);
            flash('نمونه‌کار حذف شد.');
        }
        redirect('projects.php');
    }

    if ($act === 'move') {
        $dir = (string) ($_POST['dir'] ?? 'up');
        $row = q1('SELECT * FROM `projects` WHERE `id` = ?', [$id]);

        if ($row !== null) {
            $sql = $dir === 'up'
                ? 'SELECT `id`,`sort_order` FROM `projects` WHERE `sort_order` < ? OR (`sort_order` = ? AND `id` < ?) ORDER BY `sort_order` DESC, `id` DESC LIMIT 1'
                : 'SELECT `id`,`sort_order` FROM `projects` WHERE `sort_order` > ? OR (`sort_order` = ? AND `id` > ?) ORDER BY `sort_order` ASC, `id` ASC LIMIT 1';

            $other = q1($sql, [(int) $row['sort_order'], (int) $row['sort_order'], (int) $row['id']]);
            if ($other !== null) {
                db_run('UPDATE `projects` SET `sort_order`=? WHERE `id`=?', [(int) $other['sort_order'], (int) $row['id']]);
                db_run('UPDATE `projects` SET `sort_order`=? WHERE `id`=?', [(int) $row['sort_order'], (int) $other['id']]);
            }
        }
        redirect('projects.php');
    }

    if ($act === 'save') {
        $title    = trim((string) ($_POST['title'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $desc     = trim((string) ($_POST['description'] ?? ''));
        $imgUrl   = trim((string) ($_POST['image_url'] ?? ''));
        $link     = trim((string) ($_POST['link'] ?? ''));
        $order    = (int) ($_POST['sort_order'] ?? 0);
        $visible  = isset($_POST['visible']) ? 1 : 0;

        if ($title === '') {
            flash('عنوان نمونه‌کار را وارد کنید.', 'err');
            redirect('projects.php');
        }
        if ($imgUrl !== '' && !preg_match('~^https?://~i', $imgUrl)) {
            flash('آدرس تصویر باید با http یا https شروع شود.', 'err');
            redirect('projects.php');
        }
        if ($link !== '' && !preg_match('~^https?://~i', $link)) {
            $link = 'https://' . $link;
        }

        $upload = upload_image('image_file', 'projects', 4);
        if ($upload['error'] !== null) {
            flash($upload['error'], 'err');
            redirect('projects.php');
        }
        $newFile = $upload['file'];
        $remove  = isset($_POST['remove_image']);

        if ($id > 0) {
            $old = q1('SELECT * FROM `projects` WHERE `id` = ?', [$id]);
            if ($old === null) {
                flash('این رکورد پیدا نشد.', 'err');
                redirect('projects.php');
            }

            $final = $old['image_file'];
            if ($newFile !== null) {
                $final = $newFile;
                delete_upload($old['image_file']);
            } elseif ($remove) {
                delete_upload($old['image_file']);
                $final = null;
            }

            db_run(
                'UPDATE `projects` SET `title`=?,`category`=?,`description`=?,`image_file`=?,`image_url`=?,`link`=?,`sort_order`=?,`visible`=? WHERE `id`=?',
                [$title, $category, $desc, $final, $imgUrl !== '' ? $imgUrl : null, $link !== '' ? $link : null, $order, $visible, $id]
            );
            flash('نمونه‌کار به‌روزرسانی شد.');
        } else {
            db_run(
                'INSERT INTO `projects` (`title`,`category`,`description`,`image_file`,`image_url`,`link`,`sort_order`,`visible`) VALUES (?,?,?,?,?,?,?,?)',
                [$title, $category, $desc, $newFile, $imgUrl !== '' ? $imgUrl : null, $link !== '' ? $link : null, $order, $visible]
            );
            flash('نمونه‌کار اضافه شد.');
        }
        redirect('projects.php');
    }
}

$rows = q('SELECT * FROM `projects` ORDER BY `sort_order`, `id`');
$edit = isset($_GET['edit']) ? q1('SELECT * FROM `projects` WHERE `id` = ?', [(int) $_GET['edit']]) : null;

admin_head('نمونه‌کارها');
?>

<div class="panel" id="form">
  <h2><?= $edit ? 'ویرایش نمونه‌کار' : 'افزودن نمونه‌کار' ?></h2>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

    <div class="row">
      <div class="f">
        <label for="title">عنوان *</label>
        <input id="title" name="title" required value="<?= e($edit['title'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="category">دسته‌بندی</label>
        <input id="category" name="category" placeholder="مثلاً: هویت بصری" value="<?= e($edit['category'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="sort_order">ترتیب</label>
        <input id="sort_order" name="sort_order" type="number" dir="ltr" value="<?= (int) ($edit['sort_order'] ?? 0) ?>">
      </div>
    </div>

    <div class="f">
      <label for="description">توضیح کوتاه</label>
      <textarea id="description" name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea>
    </div>

    <div class="row row--2">
      <div class="f">
        <label for="image_file">آپلود تصویر</label>
        <input id="image_file" type="file" name="image_file" accept="image/*">
        <p class="hint">پیشنهاد: نسبت ۴:۳ و عرض حداقل ۱۲۰۰ پیکسل — حداکثر ۴ مگابایت.</p>
        <?php if (!empty($edit['image_file'])): ?>
          <p class="hint" style="margin-top:10px">فایل فعلی: <code dir="ltr"><?= e($edit['image_file']) ?></code></p>
          <label class="inline" style="margin-top:8px">
            <input type="checkbox" name="remove_image" value="1">
            حذف تصویر فعلی
          </label>
        <?php endif; ?>
      </div>

      <div class="f">
        <label for="image_url">یا آدرس تصویر</label>
        <input id="image_url" name="image_url" dir="ltr" placeholder="https://" value="<?= e($edit['image_url'] ?? '') ?>">
        <label for="link" style="margin-top:16px">لینک پروژه <span class="muted">(اختیاری)</span></label>
        <input id="link" name="link" dir="ltr" placeholder="https://" value="<?= e($edit['link'] ?? '') ?>">
      </div>
    </div>

    <div class="f">
      <label>وضعیت</label>
      <label class="inline">
        <input type="checkbox" name="visible" value="1" <?= !isset($edit['visible']) || (int) $edit['visible'] === 1 ? 'checked' : '' ?>>
        نمایش در سایت
      </label>
    </div>

    <div class="act">
      <button class="btn" type="submit"><?= $edit ? 'به‌روزرسانی' : 'افزودن نمونه‌کار' ?></button>
      <?php if ($edit): ?><a class="btn btn--ghost" href="projects.php">انصراف</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <h2>
    فهرست نمونه‌کارها
    <span class="tag"><?= e(fa_digits((string) count($rows))) ?> مورد</span>
  </h2>

  <?php if (!$rows): ?>
    <?= admin_empty('هنوز نمونه‌کاری ثبت نشده است. تا زمانی که نمونه‌کاری اضافه نشود، این بخش در سایت نمایش داده نمی‌شود.') ?>
  <?php else: ?>
    <div class="logo-grid">
      <?php foreach ($rows as $r): ?>
        <?php $src = media_url($r, 'image_file', 'image_url'); ?>
        <div class="logo-card">
          <div class="logo-card__box" style="height:96px">
            <?php if ($src): ?>
              <img src="<?= e($src) ?>" alt="" style="max-height:80px">
            <?php else: ?>
              <span class="muted" style="font-size:12.5px">بدون تصویر</span>
            <?php endif; ?>
          </div>
          <div class="logo-card__name"><?= e($r['title']) ?></div>
          <div class="logo-card__meta">
            <?= e($r['category'] !== null && $r['category'] !== '' ? $r['category'] : 'بدون دسته') ?>
            <?php if ((int) $r['visible'] !== 1): ?> · پنهان<?php endif; ?>
          </div>
          <div class="logo-card__act">
            <a class="btn btn--ghost btn--sm" href="projects.php?edit=<?= (int) $r['id'] ?>#form">ویرایش</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="move">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <input type="hidden" name="dir" value="up">
              <button class="btn btn--ghost btn--sm" type="submit">↑</button>
            </form>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="move">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <input type="hidden" name="dir" value="down">
              <button class="btn btn--ghost btn--sm" type="submit">↓</button>
            </form>
            <form method="post" data-confirm="این نمونه‌کار حذف شود؟">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn btn--danger btn--sm" type="submit">حذف</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php admin_foot(); ?>
