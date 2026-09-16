<?php
/**
 * نگاه مدیا | ساختار جداول (MySQL و SQLite)
 */
declare(strict_types=1);

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
                `sort_order` INT NOT NULL DEFAULT 0,
                `visible` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `idx_group` (`group_id`)
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
            `sort_order` INTEGER NOT NULL DEFAULT 0,
            `visible` INTEGER NOT NULL DEFAULT 1
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
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_clients_group` ON `clients` (`group_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_messages_read` ON `messages` (`is_read`)",
    ];
    foreach ($indexes as $sql) {
        try {
            db()->exec($sql);
        } catch (Throwable $e) {
            // در MySQL ممکن است IF NOT EXISTS پشتیبانی نشود؛ نادیده بگیر
        }
    }
}
