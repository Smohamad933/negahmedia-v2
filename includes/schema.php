<?php
/**
 * نگاه مدیا | ساختار جداول و به‌روزرسانی ساختار (MySQL و SQLite)
 */
declare(strict_types=1);

/** نسخه ساختار دیتابیس — با هر تغییر ساختار یک عدد اضافه شود */
const NEGAH_DB_VERSION = 7;

/** @return string[] فهرست دستورات CREATE TABLE */
function schema_statements(): array
{
    if (db_driver() === 'mysql') {
        return [
            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(60) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(120) DEFAULT NULL,
                `email` VARCHAR(160) DEFAULT NULL,
                `role` ENUM('admin','editor') NOT NULL DEFAULT 'admin',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_login` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `settings` (
                `k` VARCHAR(64) NOT NULL,
                `v` TEXT DEFAULT NULL,
                PRIMARY KEY (`k`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `password_resets` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_password_reset_token` (`token_hash`),
                KEY `idx_password_resets_user` (`user_id`),
                KEY `idx_password_resets_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `site_views` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `path` VARCHAR(220) NOT NULL,
                `visitor_hash` CHAR(64) NOT NULL,
                `viewed_date` DATE NOT NULL,
                `viewed_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_site_views_date` (`viewed_date`),
                KEY `idx_site_views_visitor` (`visitor_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `stats` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `value` VARCHAR(24) NOT NULL,
                `label` VARCHAR(160) NOT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `services` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(160) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `process_steps` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(120) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `client_groups` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(160) NOT NULL,
                `subtitle` VARCHAR(220) DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `clients` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `group_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(220) NOT NULL,
                `logo_file` VARCHAR(255) DEFAULT NULL,
                `logo_url` VARCHAR(600) DEFAULT NULL,
                `website` VARCHAR(400) DEFAULT NULL,
                `intro` TEXT DEFAULT NULL,
                `cover_file` VARCHAR(255) DEFAULT NULL,
                `cover_url` VARCHAR(600) DEFAULT NULL,
                `featured` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `idx_group` (`group_id`),
                KEY `idx_featured` (`featured`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `client_gallery` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` INT UNSIGNED NOT NULL,
                `title` VARCHAR(220) NOT NULL,
                `category` VARCHAR(140) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `image_file` VARCHAR(255) DEFAULT NULL,
                `image_url` VARCHAR(600) DEFAULT NULL,
                `link` VARCHAR(600) DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_client_gallery_client` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `projects` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(220) NOT NULL,
                `category` VARCHAR(140) DEFAULT NULL,
                `description` TEXT DEFAULT NULL,
                `image_file` VARCHAR(255) DEFAULT NULL,
                `image_url` VARCHAR(600) DEFAULT NULL,
                `link` VARCHAR(600) DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `messages` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(140) NOT NULL,
                `phone` VARCHAR(60) DEFAULT NULL,
                `email` VARCHAR(180) DEFAULT NULL,
                `subject` VARCHAR(220) DEFAULT NULL,
                `body` TEXT NOT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    return [
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `username` TEXT NOT NULL UNIQUE,
            `password_hash` TEXT NOT NULL,
            `full_name` TEXT DEFAULT NULL,
            `email` TEXT DEFAULT NULL,
            `role` TEXT NOT NULL DEFAULT 'admin',
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime')),
            `last_login` TEXT DEFAULT NULL
        )",

        "CREATE TABLE IF NOT EXISTS `settings` (
            `k` TEXT NOT NULL PRIMARY KEY,
            `v` TEXT DEFAULT NULL
        )",

        "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `user_id` INTEGER NOT NULL,
            `token_hash` TEXT NOT NULL UNIQUE,
            `expires_at` TEXT NOT NULL,
            `used_at` TEXT DEFAULT NULL,
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )",

        "CREATE TABLE IF NOT EXISTS `site_views` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `path` TEXT NOT NULL,
            `visitor_hash` TEXT NOT NULL,
            `viewed_date` TEXT NOT NULL,
            `viewed_at` TEXT NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS `stats` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `value` TEXT NOT NULL,
            `label` TEXT NOT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `services` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` TEXT NOT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `process_steps` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` TEXT NOT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `client_groups` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` TEXT NOT NULL,
            `subtitle` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `clients` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `group_id` INTEGER NOT NULL,
            `name` TEXT NOT NULL,
            `logo_file` TEXT DEFAULT NULL,
            `logo_url` TEXT DEFAULT NULL,
            `website` TEXT DEFAULT NULL,
            `intro` TEXT DEFAULT NULL,
            `cover_file` TEXT DEFAULT NULL,
            `cover_url` TEXT DEFAULT NULL,
            `featured` INTEGER NOT NULL DEFAULT 0,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `client_gallery` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `client_id` INTEGER NOT NULL,
            `title` TEXT NOT NULL,
            `category` TEXT DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image_file` TEXT DEFAULT NULL,
            `image_url` TEXT DEFAULT NULL,
            `link` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1,
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )",

        "CREATE TABLE IF NOT EXISTS `projects` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` TEXT NOT NULL,
            `category` TEXT DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image_file` TEXT DEFAULT NULL,
            `image_url` TEXT DEFAULT NULL,
            `link` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS `messages` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` TEXT NOT NULL,
            `phone` TEXT DEFAULT NULL,
            `email` TEXT DEFAULT NULL,
            `subject` TEXT DEFAULT NULL,
            `body` TEXT NOT NULL,
            `is_read` INTEGER NOT NULL DEFAULT 0,
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )",
    ];
}

function schema_create(): void
{
    foreach (schema_statements() as $sql) {
        db()->exec($sql);
    }
    schema_indexes();
}

function schema_indexes(): void
{
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_clients_group` ON `clients` (`group_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_clients_featured` ON `clients` (`featured`)",
        "CREATE INDEX IF NOT EXISTS `idx_client_gallery_client` ON `client_gallery` (`client_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_site_views_date` ON `site_views` (`viewed_date`)",
        "CREATE INDEX IF NOT EXISTS `idx_site_views_visitor` ON `site_views` (`visitor_hash`)",
        "CREATE INDEX IF NOT EXISTS `idx_password_resets_user` ON `password_resets` (`user_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_password_resets_expires` ON `password_resets` (`expires_at`)",
        "CREATE INDEX IF NOT EXISTS `idx_messages_read` ON `messages` (`is_read`)",
    ];
    foreach ($indexes as $sql) {
        try {
            db()->exec($sql);
        } catch (Throwable $e) {
            // MySQL نسخه‌های قدیمی IF NOT EXISTS را پشتیبانی نمی‌کنند؛ نادیده بگیر
        }
    }
}

/** فهرست ستون‌های یک جدول */
function schema_columns(string $table): array
{
    try {
        if (db_driver() === 'mysql') {
            return array_map(
                static fn(array $r) => (string) $r['Field'],
                q('SHOW COLUMNS FROM `' . $table . '`')
            );
        }
        return array_map(
            static fn(array $r) => (string) $r['name'],
            q('PRAGMA table_info(`' . $table . '`)')
        );
    } catch (Throwable $e) {
        return [];
    }
}

function schema_has_column(string $table, string $column): bool
{
    return in_array($column, schema_columns($table), true);
}

/**
 * افزودن ستون‌های جدید به نصب‌های قدیمی.
 * فقط ستون‌هایی که وجود ندارند اضافه می‌شوند و داده‌ای از دست نمی‌رود.
 */
function schema_migrate(): void
{
    $additions = [
        'clients' => [
            'featured' => db_driver() === 'mysql'
                ? "ALTER TABLE `clients` ADD COLUMN `featured` TINYINT(1) NOT NULL DEFAULT 0"
                : "ALTER TABLE `clients` ADD COLUMN `featured` INTEGER NOT NULL DEFAULT 0",
            'intro' => db_driver() === 'mysql'
                ? "ALTER TABLE `clients` ADD COLUMN `intro` TEXT DEFAULT NULL"
                : "ALTER TABLE `clients` ADD COLUMN `intro` TEXT DEFAULT NULL",
            'cover_file' => db_driver() === 'mysql'
                ? "ALTER TABLE `clients` ADD COLUMN `cover_file` VARCHAR(255) DEFAULT NULL"
                : "ALTER TABLE `clients` ADD COLUMN `cover_file` TEXT DEFAULT NULL",
            'cover_url' => db_driver() === 'mysql'
                ? "ALTER TABLE `clients` ADD COLUMN `cover_url` VARCHAR(600) DEFAULT NULL"
                : "ALTER TABLE `clients` ADD COLUMN `cover_url` TEXT DEFAULT NULL",
        ],
        'process_steps' => [
            'visible' => db_driver() === 'mysql'
                ? "ALTER TABLE `process_steps` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1"
                : "ALTER TABLE `process_steps` ADD COLUMN `visible` INTEGER NOT NULL DEFAULT 1",
        ],
        'client_groups' => [
            'visible' => db_driver() === 'mysql'
                ? "ALTER TABLE `client_groups` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1"
                : "ALTER TABLE `client_groups` ADD COLUMN `visible` INTEGER NOT NULL DEFAULT 1",
        ],
    ];

    foreach ($additions as $table => $columns) {
        if (schema_columns($table) === []) {
            continue;
        }
        foreach ($columns as $column => $sql) {
            if (!schema_has_column($table, $column)) {
                try {
                    db()->exec($sql);
                } catch (Throwable $e) {
                    // نادیده بگیر
                }
            }
        }
    }

    $gallerySql = db_driver() === 'mysql'
        ? "CREATE TABLE IF NOT EXISTS `client_gallery` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(220) NOT NULL,
            `category` VARCHAR(140) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image_file` VARCHAR(255) DEFAULT NULL,
            `image_url` VARCHAR(600) DEFAULT NULL,
            `link` VARCHAR(600) DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `visible` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_client_gallery_client` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        : "CREATE TABLE IF NOT EXISTS `client_gallery` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `client_id` INTEGER NOT NULL,
            `title` TEXT NOT NULL,
            `category` TEXT DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image_file` TEXT DEFAULT NULL,
            `image_url` TEXT DEFAULT NULL,
            `link` TEXT DEFAULT NULL,
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1,
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )";
    try {
        db()->exec($gallerySql);
    } catch (Throwable $e) {
        // نصب‌های قدیمی باید بدون از دست رفتن داده ادامه پیدا کنند.
    }

    $viewsSql = db_driver() === 'mysql'
        ? "CREATE TABLE IF NOT EXISTS `site_views` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `path` VARCHAR(220) NOT NULL,
            `visitor_hash` CHAR(64) NOT NULL,
            `viewed_date` DATE NOT NULL,
            `viewed_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_site_views_date` (`viewed_date`),
            KEY `idx_site_views_visitor` (`visitor_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        : "CREATE TABLE IF NOT EXISTS `site_views` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `path` TEXT NOT NULL,
            `visitor_hash` TEXT NOT NULL,
            `viewed_date` TEXT NOT NULL,
            `viewed_at` TEXT NOT NULL
        )";
    try {
        db()->exec($viewsSql);
    } catch (Throwable $e) {
        // ثبت بازدید نباید جلوی بالا آمدن سایت را بگیرد.
    }

    $resetSql = db_driver() === 'mysql'
        ? "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `token_hash` CHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `used_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_password_reset_token` (`token_hash`),
            KEY `idx_password_resets_user` (`user_id`),
            KEY `idx_password_resets_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        : "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `user_id` INTEGER NOT NULL,
            `token_hash` TEXT NOT NULL UNIQUE,
            `expires_at` TEXT NOT NULL,
            `used_at` TEXT DEFAULT NULL,
            `created_at` TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )";
    try {
        db()->exec($resetSql);
    } catch (Throwable $e) {
        // درخواست بازیابی نباید باعث خطای عمومی سایت شود.
    }

    schema_indexes();
}
