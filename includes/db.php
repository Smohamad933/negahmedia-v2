<?php
/**
 * نگاه مدیا | لایه دسترسی به دیتابیس (PDO)
 * پشتیبانی همزمان از MySQL و SQLite با یک API مشترک.
 */
declare(strict_types=1);

/** تنظیمات را برمی‌گرداند: cfg('driver') */
function cfg(string $key, $default = null)
{
    static $conf = null;
    if ($conf === null) {
        $conf = require __DIR__ . '/config.php';
    }
    return $conf[$key] ?? $default;
}

function db_driver(): string
{
    return cfg('driver', 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if (db_driver() === 'mysql') {
            $my = (array) cfg('mysql', []);
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                (string) ($my['host'] ?? 'localhost'),
                (string) ($my['name'] ?? ''),
                (string) ($my['charset'] ?? 'utf8mb4')
            );
            $pdo = new PDO($dsn, (string) ($my['user'] ?? ''), (string) ($my['pass'] ?? ''), $options);
            $pdo->exec("SET time_zone = '+03:30'");
        } else {
            $path = (string) cfg('sqlite_path');
            $dir  = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        }
    } catch (PDOException $e) {
        if (cfg('debug')) {
            http_response_code(500);
            exit('DB error: ' . $e->getMessage());
        }
        http_response_code(500);
        exit('اتصال به دیتابیس برقرار نشد. تنظیمات فایل includes/config.php را بررسی کنید.');
    }

    return $pdo;
}

/** چند رکورد */
function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** یک رکورد یا null */
function q1(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** یک مقدار تک */
function qv(string $sql, array $params = [], $default = null)
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

/** اجرای INSERT/UPDATE/DELETE — شناسه آخرین درج را برمی‌گرداند */
function db_run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int) db()->lastInsertId();
}

/** آیا جدول‌ها ساخته شده‌اند؟ */
function db_is_installed(): bool
{
    try {
        $driver = db_driver();
        if ($driver === 'mysql') {
            $row = q1("SHOW TABLES LIKE 'settings'");
            return !empty($row);
        }
        $row = q1("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
        return !empty($row);
    } catch (Throwable $e) {
        return false;
    }
}
