<?php
header('Content-Type: application/json; charset=utf-8');

$results = [];
$all_ok = true;

function ok($key, $msg) {
    global $results, $all_ok;
    $results[$key] = ['status' => 'ok', 'msg' => $msg];
}

function fail($key, $msg) {
    global $results, $all_ok;
    $results[$key] = ['status' => 'fail', 'msg' => $msg];
    $all_ok = false;
}

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    ok('php', 'PHP ' . PHP_VERSION);
} else {
    fail('php', '需要 PHP 7.4+，当前 ' . PHP_VERSION);
}

if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    ok('pdo', 'PDO MySQL 已启用');
} else {
    fail('pdo', '缺少 pdo_mysql 扩展');
}

$config_file = __DIR__ . '/../admin/config.php';
if (file_exists($config_file)) {
    ok('config', '配置文件存在');
} else {
    fail('config', 'admin/config.php 不存在，请运行 install.php');
}

if (file_exists($config_file)) {
    try {
        require_once $config_file;
        if (function_exists('pdo_connect')) {
            $pdo = pdo_connect();
            ok('db', '数据库连接正常');
        } else {
            fail('db', 'pdo_connect() 函数未定义');
        }
    } catch (Exception $e) {
        fail('db', '数据库连接失败：' . $e->getMessage());
    }
}

$dirs = ['uploads', '../uploads'];
foreach ($dirs as $dir) {
    $full = __DIR__ . '/../' . ltrim($dir, '../');
    if (is_dir($full) && is_writable($full)) {
        ok('dir_' . $dir, 'uploads 目录正常');
        break;
    } elseif (is_dir($full)) {
        ok('dir_' . $dir, 'uploads 目录存在（不可写）');
        break;
    }
}

if (function_exists('curl_init') || ini_get('allow_url_fopen')) {
    ok('network', '网络请求可用');
} else {
    fail('network', 'curl 和 allow_url_fopen 均不可用');
}

echo json_encode([
    'ok'     => $all_ok,
    'time'   => date('Y-m-d H:i:s'),
    'checks' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
