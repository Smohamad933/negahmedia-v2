<?php
/**
 * نگاه مدیا | پشتیبان‌گیری
 * دانلود سورس کامل پروژه به‌صورت فایل ZIP و دریافت پشتیبان دیتابیس.
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';

$action = (string) ($_GET['action'] ?? '');

/* ---------------------------------------------------------
   دانلود سورس کامل (ZIP) — معادل «لینک دانلود سورس» روی هاست شما
   --------------------------------------------------------- */
if ($action === 'source') {
    if (!class_exists('ZipArchive')) {
        exit('افزونه ZipArchive روی این سرور فعال نیست. از cPanel → File Manager فایل‌ها را ZIP کنید.');
    }

    $root    = dirname(__DIR__);
    $tmpFile = sys_get_temp_dir() . '/negah-source-' . date('Ymd-His') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit('ساخت فایل ZIP ممکن نشد.');
    }

    $skipDirs = ['data', '.git', 'node_modules'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        $path = $file->getPathname();
        $rel  = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
        $rel  = str_replace('\\', '/', $rel);

        $top = explode('/', $rel)[0];
        if (in_array($top, $skipDirs, true)) {
            continue;
        }
        if ($file->getExtension() === 'zip') {
            continue;
        }

        if ($file->isDir()) {
            $zip->addEmptyDir($rel);
        } else {
            $zip->addFile($path, $rel);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="negahmedia-source-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . (string) filesize($tmpFile));
    header('Cache-Control: no-store');
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}

/* ---------------------------------------------------------
   دانلود پشتیبان دیتابیس
   --------------------------------------------------------- */
if ($action === 'database') {
    $stamp = date('Ymd-His');

    if (db_driver() === 'mysql') {
        $db   = (string) (cfg('mysql')['name'] ?? 'database');
        $lines = ["-- پشتیبان دیتابیس نگاه مدیا", '-- تاریخ: ' . date('Y-m-d H:i:s'), 'SET NAMES utf8mb4;', ''];

        $tables = array_column(q('SHOW TABLES'), 'Tables_in_' . $db);

        foreach ($tables as $table) {
            $create = q1('SHOW CREATE TABLE `' . $table . '`');
            $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
            $lines[] = (string) (array_values($create)[1] ?? '') . ';';

            foreach (q('SELECT * FROM `' . $table . '`') as $row) {
                $values = array_map(
                    static fn($v) => $v === null ? 'NULL' : db()->quote((string) $v),
                    array_values($row)
                );
                $lines[] = 'INSERT INTO `' . $table . '` VALUES (' . implode(',', $values) . ');';
            }
            $lines[] = '';
        }

        $payload  = implode("\n", $lines);
        $filename = 'negahmedia-db-' . $stamp . '.sql';
    } else {
        $path = (string) cfg('sqlite_path');
        if (!is_file($path)) {
            exit('فایل دیتابیس پیدا نشد.');
        }
        $payload  = (string) file_get_contents($path);
        $filename = 'negahmedia-db-' . $stamp . '.sqlite';
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($payload));
    header('Cache-Control: no-store');
    echo $payload;
    exit;
}

/* ---------- آمار کوچک برای نمایش ---------- */
$dbPath  = (string) cfg('sqlite_path');
$dbSize  = db_driver() === 'sqlite' && is_file($dbPath) ? filesize($dbPath) : null;
$uploads = 0;
$upBytes = 0;

foreach (['logos', 'projects'] as $sub) {
    $dir = upload_path($sub);
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $uploads++;
                $upBytes += (int) filesize($file);
            }
        }
    }
}

$human = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return $bytes . ' B';
};

admin_head('پشتیبان‌گیری');
?>

<div class="panel">
  <h2>دانلود سورس کامل پروژه</h2>
  <p class="muted" style="margin-bottom:18px">
    تمام فایل‌های سایت (به‌جز پوشه <code dir="ltr">data</code>) در یک فایل ZIP بسته‌بندی و دانلود می‌شود.
    برای انتقال سایت به هاست دیگر یا نگه‌داری نسخه پشتیبان مناسب است.
  </p>
  <a class="btn" href="backup.php?action=source">دانلود سورس (ZIP)</a>
  <?php if (!class_exists('ZipArchive')): ?>
    <p class="hint" style="color:#8C3330">افزونه ZipArchive روی این سرور فعال نیست، بنابراین این گزینه کار نمی‌کند.</p>
  <?php endif; ?>
</div>

<div class="panel">
  <h2>پشتیبان دیتابیس</h2>
  <div class="tablewrap" style="margin-bottom:18px">
    <table>
      <tbody>
        <tr><th style="width:170px">نوع دیتابیس</th><td><?= db_driver() === 'mysql' ? 'MySQL / MariaDB' : 'SQLite' ?></td></tr>
        <?php if ($dbSize !== null): ?>
          <tr><th>حجم فایل دیتابیس</th><td class="num"><?= e(fa_digits($human((int) $dbSize))) ?></td></tr>
        <?php endif; ?>
        <tr><th>فایل‌های آپلودی</th><td class="num"><?= e(fa_digits((string) $uploads)) ?> فایل — <?= e(fa_digits($human($upBytes))) ?></td></tr>
      </tbody>
    </table>
  </div>
  <a class="btn" href="backup.php?action=database">دانلود پشتیبان دیتابیس</a>
  <p class="hint">
    <?= db_driver() === 'mysql'
        ? 'خروجی به‌صورت فایل SQL است و در phpMyAdmin قابل بازگردانی است.'
        : 'خروجی همان فایل دیتابیس SQLite است.' ?>
  </p>
</div>

<div class="panel">
  <h2>نکات نگه‌داری</h2>
  <p class="muted" style="line-height:2">
    · هر چند وقت یک‌بار از پوشه <code dir="ltr">uploads</code> و دیتابیس نسخه پشتیبان بگیرید.<br>
    · پس از نصب، فایل <code dir="ltr">install.php</code> را از هاست حذف کنید.<br>
    · روی هاست نهایی مقدار <code dir="ltr">debug</code> در فایل <code dir="ltr">includes/config.php</code> را خاموش بگذارید.
  </p>
</div>

<?php admin_foot(); ?>
