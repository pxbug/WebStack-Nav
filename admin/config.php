<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'navigation');
define('DB_USER', 'navigation');
define('DB_PASS', 'navigation');
define('DB_CHARSET', 'utf8mb4');

function pdo_connect() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function normalize_public_path($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) || (strlen($path) >= 2 && substr($path, 0, 2) === '//')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

/**
 * 执行 SQL 建表
 */
function init_database() {
    $pdo = pdo_connect();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(50) NOT NULL,
            `sort_order` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `nav_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT UNSIGNED NOT NULL,
            `title` VARCHAR(100) NOT NULL,
            `subtitle` VARCHAR(200) DEFAULT '',
            `icon` MEDIUMTEXT DEFAULT '',
            `link` VARCHAR(500) NOT NULL,
            `sort_order` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `feeds` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `images` TEXT DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lottery_prob` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `prob` DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT '中奖概率 0-100',
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `carousel` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `image_url` VARCHAR(500) NOT NULL,
            `link` VARCHAR(500) DEFAULT '',
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contacts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(20) NOT NULL COMMENT 'ww|tg|email|wechat|phone|other',
            `label` VARCHAR(50) NOT NULL COMMENT '显示名称，如旺旺/TG/邮箱',
            `value` VARCHAR(255) NOT NULL COMMENT '具体账号或内容',
            `icon_bg` VARCHAR(20) DEFAULT '' COMMENT '图标背景色',
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO contacts (type, label, value, icon_bg, sort_order) VALUES
            ('ww',   '旺旺', '888888',  '#FF5000', 1),
            ('tg',   'TG',   'iTroll888', '#0088cc', 2),
            ('email','邮箱', 'pxxox@qq.com', '#c9a227', 3)
        ");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lottery_prob");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO lottery_prob (prob) VALUES (10.00)");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins");
    $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                REPLACE INTO admins (username, password) VALUES ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "')
            ");
        }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categories (slug, name, sort_order) VALUES
            ('market', '大盘推荐', 1),
            ('sim', '模拟器', 2),
            ('night', '深夜福利', 3)
        ");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM carousel");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO carousel (image_url, link, sort_order) VALUES
            ('/assets/images/Banner.jpg', '', 1),
            ('/assets/images/Banner2.jpg', '', 2),
            ('/assets/images/Banner3.jpg', '', 3)
        ");
    }

    $pdo->exec("UPDATE carousel SET image_url = CONCAT('/', image_url)
        WHERE image_url != ''
        AND image_url NOT LIKE 'http://%'
        AND image_url NOT LIKE 'https://%'
        AND image_url NOT LIKE '/%'");

    $pdo->exec("UPDATE carousel SET image_url = REPLACE(image_url, '/assets/images/Banner.png', '/assets/images/Banner.jpg')
        WHERE image_url LIKE '%/Banner.png'");
    $pdo->exec("UPDATE carousel SET image_url = REPLACE(image_url, '/assets/images/Banner2.png', '/assets/images/Banner2.jpg')
        WHERE image_url LIKE '%/Banner2.png'");
    $pdo->exec("UPDATE carousel SET image_url = REPLACE(image_url, '/assets/images/Banner3.png', '/assets/images/Banner3.jpg')
        WHERE image_url LIKE '%/Banner3.png'");
}
