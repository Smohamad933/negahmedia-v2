<?php
/**
 * نگاه مدیا | کاربران پنل
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

/* فقط مدیر کل اجازه دارد */
if (($USER['role'] ?? '') !== 'admin') {
    flash('برای مدیریت کاربران به دسترسی مدیر کل نیاز است.', 'err');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $act = (string) ($_POST['act'] ?? '');
    $id  = (int) ($_POST['id'] ?? 0);

    if ($act === 'delete') {
        if ($id === (int) $USER['id']) {
            flash('حساب خودتان را نمی‌توانید حذف کنید.', 'err');
            redirect('users.php');
        }
        $total = (int) qv('SELECT COUNT(*) FROM `users`', [], 0);
        if ($total <= 1) {
            flash('حداقل یک کاربر باید باقی بماند.', 'err');
            redirect('users.php');
        }
        db_run('DELETE FROM `users` WHERE `id` = ?', [$id]);
        flash('کاربر حذف شد.');
        redirect('users.php');
    }

    if ($act === 'save') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $role     = (string) ($_POST['role'] ?? 'admin') === 'editor' ? 'editor' : 'admin';
        $password = (string) ($_POST['password'] ?? '');

        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            flash('نام کاربری باید ۳ تا ۶۰ نویسه لاتین، رقم یا . _ - باشد.', 'err');
            redirect('users.php');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('ایمیل معتبر نیست.', 'err');
            redirect('users.php');
        }

        if ($id > 0) {
            if (q1('SELECT `id` FROM `users` WHERE `username` = ? AND `id` <> ?', [$username, $id]) !== null) {
                flash('این نام کاربری قبلاً استفاده شده است.', 'err');
                redirect('users.php');
            }

            db_run('UPDATE `users` SET `username`=?,`full_name`=?,`email`=?,`role`=? WHERE `id`=?', [$username, $fullName, $email, $role, $id]);

            if ($password !== '') {
                if (mb_strlen($password) < 8) {
                    flash('رمز عبور باید حداقل ۸ نویسه باشد.', 'err');
                    redirect('users.php');
                }
                db_run('UPDATE `users` SET `password_hash`=? WHERE `id`=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            flash('کاربر به‌روزرسانی شد.');
        } else {
            if (mb_strlen($password) < 8) {
                flash('رمز عبور باید حداقل ۸ نویسه باشد.', 'err');
                redirect('users.php');
            }
            if (q1('SELECT `id` FROM `users` WHERE `username` = ?', [$username]) !== null) {
                flash('این نام کاربری قبلاً استفاده شده است.', 'err');
                redirect('users.php');
            }

            db_run('INSERT INTO `users` (`username`,`password_hash`,`full_name`,`email`,`role`) VALUES (?,?,?,?,?)', [
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $fullName,
                $email,
                $role,
            ]);
            flash('کاربر جدید ساخته شد.');
        }
        redirect('users.php');
    }
}

$users = q('SELECT * FROM `users` ORDER BY `id`');
$edit  = isset($_GET['edit']) ? q1('SELECT * FROM `users` WHERE `id` = ?', [(int) $_GET['edit']]) : null;

admin_head('کاربران پنل');
?>

<div class="panel">
  <h2><?= $edit ? 'ویرایش کاربر: ' . e($edit['username']) : 'افزودن کاربر جدید' ?></h2>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

    <div class="row">
      <div class="f">
        <label for="username">نام کاربری *</label>
        <input id="username" name="username" dir="ltr" required value="<?= e($edit['username'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="full_name">نام و نام خانوادگی</label>
        <input id="full_name" name="full_name" value="<?= e($edit['full_name'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="email">ایمیل <span class="muted">(برای بازیابی رمز)</span></label>
        <input id="email" name="email" dir="ltr" value="<?= e($edit['email'] ?? '') ?>">
        <p class="hint">اگر رمز فراموش شود، لینک بازیابی به این آدرس ارسال می‌شود.</p>
      </div>
      <div class="f">
        <label for="role">نقش</label>
        <select id="role" name="role">
          <option value="admin"<?= isset($edit['role']) && $edit['role'] === 'admin' ? ' selected' : '' ?>>مدیر کل — دسترسی کامل</option>
          <option value="editor"<?= isset($edit['role']) && $edit['role'] === 'editor' ? ' selected' : '' ?>>ویرایشگر محتوا</option>
        </select>
      </div>
      <div class="f">
        <label for="password">رمز عبور <?= $edit ? '<span class="muted">(خالی بگذارید تا تغییر نکند)</span>' : '' ?></label>
        <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" <?= $edit ? '' : 'required' ?>>
        <p class="hint">حداقل ۸ نویسه؛ ترکیبی از حرف و عدد و نماد توصیه می‌شود.</p>
      </div>
    </div>

    <div class="act">
      <button class="btn" type="submit"><?= $edit ? 'به‌روزرسانی کاربر' : 'افزودن کاربر' ?></button>
      <?php if ($edit): ?><a class="btn btn--ghost" href="users.php">انصراف</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <h2>کاربران موجود <span class="tag"><?= e(fa_digits((string) count($users))) ?> کاربر</span></h2>

  <div class="tablewrap">
    <table>
      <thead>
        <tr><th>#</th><th>نام کاربری</th><th>نام</th><th>ایمیل</th><th>نقش</th><th>آخرین ورود</th><th>عملیات</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
          <tr>
            <td class="num"><?= e(fa_num($i + 1)) ?></td>
            <td dir="ltr"><?= e($u['username']) ?><?= (int) $u['id'] === (int) $USER['id'] ? ' <span class="tag tag--ok">شما</span>' : '' ?></td>
            <td><?= e($u['full_name'] !== '' && $u['full_name'] !== null ? $u['full_name'] : '—') ?></td>
            <td dir="ltr"><?= e($u['email'] !== '' && $u['email'] !== null ? $u['email'] : '—') ?></td>
            <td><?= $u['role'] === 'admin' ? 'مدیر کل' : 'ویرایشگر' ?></td>
            <td class="num"><?= e($u['last_login'] !== null && $u['last_login'] !== '' ? fa_digits((string) $u['last_login']) : '—') ?></td>
            <td>
              <div class="act">
                <a class="btn btn--ghost btn--sm" href="users.php?edit=<?= (int) $u['id'] ?>">ویرایش</a>
                <?php if ((int) $u['id'] !== (int) $USER['id']): ?>
                  <form method="post" data-confirm="کاربر «<?= e($u['username']) ?>» حذف شود؟">
                    <?= csrf_field() ?>
                    <input type="hidden" name="act" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php admin_foot(); ?>
