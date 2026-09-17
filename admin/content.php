<?php
/**
 * نگاه مدیا | مدیریت خدمات، آمار کلیدی و مراحل همکاری
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

/** جدول‌های قابل مدیریت: نام جدول => [عنوان، ستون‌ها، ستون ترتیب] */
$tables = [
    'services' => [
        'title'  => 'خدمات',
        'cols'   => ['title' => 'عنوان خدمت', 'description' => 'توضیح کوتاه'],
        'labels' => ['title' => 'عنوان خدمت', 'description' => 'توضیح'],
        'type'   => ['description' => 'textarea'],
    ],
    'stats' => [
        'title'  => 'آمار کلیدی',
        'cols'   => ['value' => 'عدد', 'label' => 'برچسب'],
        'labels' => ['value' => 'عدد (مثل ۴۰+)', 'label' => 'برچسب (مثل پروژه اجراشده)'],
        'type'   => [],
    ],
    'process_steps' => [
        'title'  => 'مراحل همکاری',
        'cols'   => ['title' => 'عنوان مرحله', 'description' => 'توضیح'],
        'labels' => ['title' => 'عنوان مرحله', 'description' => 'توضیح'],
        'type'   => ['description' => 'textarea'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $table = (string) ($_POST['table'] ?? '');
    $act   = (string) ($_POST['act'] ?? '');

    if (!isset($tables[$table])) {
        flash('درخواست نامعتبر است.', 'err');
        redirect('content.php');
    }

    $cols = array_keys($tables[$table]['cols']);

    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $values = [];
        foreach ($cols as $c) {
            $values[$c] = trim((string) ($_POST[$c] ?? ''));
        }

        if ($values[$cols[0]] === '') {
            flash('فیلد اول الزامی است.', 'err');
            redirect('content.php');
        }

        $all    = array_merge($cols, ['visible', 'sort_order']);
        $values['visible']    = isset($_POST['visible']) ? 1 : 0;
        $values['sort_order'] = (int) ($_POST['sort_order'] ?? 0);

        $params = array_map(static fn($c) => $values[$c], $all);

        if ($id > 0) {
            $set = implode(',', array_map(static fn($c) => '`' . $c . '`=?', $all));
            $params[] = $id;
            db_run("UPDATE `$table` SET $set WHERE `id`=?", $params);
            flash('مورد به‌روزرسانی شد.');
        } else {
            $names = '`' . implode('`,`', $all) . '`';
            $marks = implode(',', array_fill(0, count($all), '?'));
            db_run("INSERT INTO `$table` ($names) VALUES ($marks)", $params);
            flash('مورد جدید اضافه شد.');
        }
        redirect('content.php#t-' . $table);
    }

    if ($act === 'delete') {
        db_run("DELETE FROM `$table` WHERE `id` = ?", [(int) ($_POST['id'] ?? 0)]);
        flash('مورد حذف شد.');
        redirect('content.php#t-' . $table);
    }

    if ($act === 'move') {
        $id  = (int) ($_POST['id'] ?? 0);
        $dir = (string) ($_POST['dir'] ?? 'up');
        $row = q1("SELECT * FROM `$table` WHERE `id` = ?", [$id]);

        if ($row !== null) {
            $sql = $dir === 'up'
                ? "SELECT `id`,`sort_order` FROM `$table` WHERE `sort_order` < ? OR (`sort_order` = ? AND `id` < ?) ORDER BY `sort_order` DESC, `id` DESC LIMIT 1"
                : "SELECT `id`,`sort_order` FROM `$table` WHERE `sort_order` > ? OR (`sort_order` = ? AND `id` > ?) ORDER BY `sort_order` ASC, `id` ASC LIMIT 1";

            $other = q1($sql, [(int) $row['sort_order'], (int) $row['sort_order'], (int) $row['id']]);

            if ($other !== null) {
                db_run("UPDATE `$table` SET `sort_order`=? WHERE `id`=?", [(int) $other['sort_order'], (int) $row['id']]);
                db_run("UPDATE `$table` SET `sort_order`=? WHERE `id`=?", [(int) $row['sort_order'], (int) $other['id']]);
            }
        }
        redirect('content.php#t-' . $table);
    }
}

$editTable = (string) ($_GET['table'] ?? '');
$editId    = (int) ($_GET['edit'] ?? 0);

admin_head('خدمات، آمار و مراحل');
?>

<?php foreach ($tables as $table => $meta): ?>
  <?php
    $rows = q("SELECT * FROM `$table` ORDER BY `sort_order`, `id`");
    $edit = ($editId > 0 && $editTable === $table) ? q1("SELECT * FROM `$table` WHERE `id` = ?", [$editId]) : null;
    $isEditing = $edit !== null;
  ?>
  <div class="panel" id="t-<?= e($table) ?>">
    <h2>
      <?= e($meta['title']) ?>
      <span class="tag"><?= e(fa_digits((string) count($rows))) ?> مورد</span>
    </h2>

    <form method="post" style="background:var(--paper);border:1px solid var(--line);padding:18px;margin-bottom:22px">
      <?= csrf_field() ?>
      <input type="hidden" name="table" value="<?= e($table) ?>">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="row">
        <?php foreach ($meta['cols'] as $col => $label): ?>
          <div class="f">
            <label for="<?= e($table) ?>-<?= e($col) ?>"><?= e($meta['labels'][$col]) ?></label>
            <?php if (($meta['type'][$col] ?? '') === 'textarea'): ?>
              <textarea id="<?= e($table) ?>-<?= e($col) ?>" name="<?= e($col) ?>" rows="2"><?= e($edit[$col] ?? '') ?></textarea>
            <?php else: ?>
              <input id="<?= e($table) ?>-<?= e($col) ?>" name="<?= e($col) ?>" value="<?= e($edit[$col] ?? '') ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="f">
          <label for="<?= e($table) ?>-order">ترتیب</label>
          <input id="<?= e($table) ?>-order" name="sort_order" type="number" dir="ltr" value="<?= (int) ($edit['sort_order'] ?? 0) ?>">
        </div>

        <div class="f">
          <label>وضعیت</label>
          <label class="inline">
            <input type="checkbox" name="visible" value="1" <?= !$isEditing || (int) $edit['visible'] === 1 ? 'checked' : '' ?>>
            نمایش در سایت
          </label>
        </div>
      </div>

      <div class="act">
        <button class="btn" type="submit"><?= $isEditing ? 'به‌روزرسانی' : 'افزودن' ?></button>
        <?php if ($isEditing): ?><a class="btn btn--ghost" href="content.php#t-<?= e($table) ?>">انصراف</a><?php endif; ?>
      </div>
    </form>

    <?php if (!$rows): ?>
      <?= admin_empty('موردی ثبت نشده است.') ?>
    <?php else: ?>
      <div class="tablewrap">
        <table>
          <thead>
            <tr>
              <th>ترتیب</th>
              <?php foreach ($meta['cols'] as $col => $label): ?><th><?= e($meta['labels'][$col]) ?></th><?php endforeach; ?>
              <th>نمایش</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="num"><?= e(fa_digits((string) $r['sort_order'])) ?></td>
                <?php foreach (array_keys($meta['cols']) as $col): ?>
                  <td><?= e($r[$col]) ?></td>
                <?php endforeach; ?>
                <td>
                  <?= (int) $r['visible'] === 1
                      ? '<span class="tag tag--ok">فعال</span>'
                      : '<span class="tag">پنهان</span>' ?>
                </td>
                <td>
                  <div class="act">
                    <a class="btn btn--ghost btn--sm" href="content.php?table=<?= e($table) ?>&amp;edit=<?= (int) $r['id'] ?>#t-<?= e($table) ?>">ویرایش</a>

                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="table" value="<?= e($table) ?>">
                      <input type="hidden" name="act" value="move">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="btn btn--ghost btn--sm" type="submit" title="بالا">↑</button>
                    </form>

                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="table" value="<?= e($table) ?>">
                      <input type="hidden" name="act" value="move">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="btn btn--ghost btn--sm" type="submit" title="پایین">↓</button>
                    </form>

                    <form method="post" data-confirm="این مورد حذف شود؟">
                      <?= csrf_field() ?>
                      <input type="hidden" name="table" value="<?= e($table) ?>">
                      <input type="hidden" name="act" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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
<?php endforeach; ?>

<?php admin_foot(); ?>
