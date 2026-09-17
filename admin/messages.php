<?php
/**
 * نگاه مدیا | پیام‌های دریافتی از فرم تماس
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');
    $id  = (int) ($_POST['id'] ?? 0);

    if ($act === 'delete') {
        db_run('DELETE FROM `messages` WHERE `id` = ?', [$id]);
        flash('پیام حذف شد.');
        redirect('messages.php');
    }

    if ($act === 'read') {
        db_run('UPDATE `messages` SET `is_read` = 1 WHERE `id` = ?', [$id]);
        redirect('messages.php?id=' . $id);
    }

    if ($act === 'unread') {
        db_run('UPDATE `messages` SET `is_read` = 0 WHERE `id` = ?', [$id]);
        redirect('messages.php?id=' . $id);
    }
}

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$open = null;

if ($id > 0) {
    $open = q1('SELECT * FROM `messages` WHERE `id` = ?', [$id]);
    if ($open !== null && (int) $open['is_read'] === 0) {
        db_run('UPDATE `messages` SET `is_read` = 1 WHERE `id` = ?', [$id]);
        $open['is_read'] = 1;
    }
}

$rows = q('SELECT * FROM `messages` ORDER BY `id` DESC LIMIT 300');

admin_head('پیام‌های دریافتی');
?>

<?php if ($open !== null): ?>
  <div class="panel">
    <h2>
      پیام از <?= e($open['name']) ?>
      <span class="sp"></span>
      <a class="btn btn--ghost btn--sm" href="messages.php">فهرست پیام‌ها</a>
    </h2>

    <div class="tablewrap" style="margin-bottom:20px">
      <table>
        <tbody>
          <tr><th style="width:110px">نام</th><td><?= e($open['name']) ?></td></tr>
          <tr><th>تلفن</th><td dir="ltr" class="num"><?= e(fa_digits((string) $open['phone'])) ?></td></tr>
          <?php if (!empty($open['email'])): ?>
            <tr><th>ایمیل</th><td dir="ltr"><?= e($open['email']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($open['subject'])): ?>
            <tr><th>موضوع</th><td><?= e($open['subject']) ?></td></tr>
          <?php endif; ?>
          <tr><th>تاریخ</th><td class="num"><?= e(fa_digits((string) $open['created_at'])) ?></td></tr>
        </tbody>
      </table>
    </div>

    <div style="background:var(--paper);border:1px solid var(--line);padding:18px;white-space:pre-wrap;line-height:2;margin-bottom:20px"><?= e($open['body']) ?></div>

    <div class="act">
      <?php if (!empty($open['phone'])): ?>
        <a class="btn btn--ghost" href="tel:<?= e($open['phone']) ?>">تماس تلفنی</a>
      <?php endif; ?>
      <?php if (!empty($open['email'])): ?>
        <a class="btn btn--ghost" href="mailto:<?= e($open['email']) ?>?subject=<?= rawurlencode('پاسخ به درخواست شما — نگاه مدیا') ?>">پاسخ با ایمیل</a>
      <?php endif; ?>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="unread">
        <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
        <button class="btn btn--ghost" type="submit">علامت‌گذاری به‌عنوان خوانده‌نشده</button>
      </form>

      <form method="post" data-confirm="این پیام حذف شود؟">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
        <button class="btn btn--danger" type="submit">حذف پیام</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="panel">
  <h2>
    فهرست پیام‌ها
    <span class="tag"><?= e(fa_digits((string) count($rows))) ?> پیام</span>
    <span class="sp"></span>
    <input type="search" data-filter="#msgtable" placeholder="جستجو در نام، شماره یا متن…" style="max-width:280px">
  </h2>

  <?php if (!$rows): ?>
    <?= admin_empty('هنوز پیامی از فرم تماس سایت دریافت نشده است.') ?>
  <?php else: ?>
    <div class="tablewrap">
      <table id="msgtable">
        <thead>
          <tr><th>وضعیت</th><th>نام</th><th>تلفن</th><th>موضوع</th><th>خلاصه پیام</th><th>تاریخ</th><th>عملیات</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $m): ?>
            <tr>
              <td>
                <?= (int) $m['is_read'] === 1
                    ? '<span class="tag tag--ok">خوانده‌شده</span>'
                    : '<span class="tag tag--new">جدید</span>' ?>
              </td>
              <td><?= e($m['name']) ?></td>
              <td dir="ltr" class="num"><?= e(fa_digits((string) $m['phone'])) ?></td>
              <td><?= e($m['subject'] !== '' ? $m['subject'] : '—') ?></td>
              <td class="muted"><?= e(mb_substr((string) $m['body'], 0, 42)) ?><?= mb_strlen((string) $m['body']) > 42 ? '…' : '' ?></td>
              <td class="num"><?= e(fa_digits((string) $m['created_at'])) ?></td>
              <td>
                <div class="act">
                  <a class="btn btn--ghost btn--sm" href="messages.php?id=<?= (int) $m['id'] ?>">مشاهده</a>
                  <form method="post" data-confirm="این پیام حذف شود؟">
                    <?= csrf_field() ?>
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php admin_foot(); ?>
