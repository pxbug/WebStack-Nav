<?php
require_once __DIR__ . '/../admin/config.php';

try {
    $pdo = pdo_connect();
    $pdo->exec("ALTER TABLE nav_items MODIFY icon MEDIUMTEXT NOT NULL DEFAULT ''");
    echo '升级成功：icon 字段已改为 MEDIUMTEXT';
} catch (Exception $e) {
    echo '错误：' . $e->getMessage();
}
