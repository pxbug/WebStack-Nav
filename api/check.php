<?php
header('Content-Type: application/json; charset=utf-8');

$lock_file = __DIR__ . '/../admin/install.lock';

if (!file_exists($lock_file)) {
    http_response_code(503);
    echo json_encode([
        'status' => 'not_installed',
        'message' => '尚未安装',
        'url' => 'admin/install.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../admin/config.php';
    $pdo = pdo_connect();
    $pdo->query("SELECT 1");
    echo json_encode([
        'status' => 'ok',
        'message' => '已安装'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'db_error',
        'message' => '数据库连接失败',
        'url' => 'admin/install.php'
    ], JSON_UNESCAPED_UNICODE);
}
