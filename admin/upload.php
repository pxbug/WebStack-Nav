<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '{"code":1,"msg":"\u8bf7\u6c42\u65b9\u5f0f\u9519\u8bef"}';
    exit;
}

if (!isset($_FILES['image'])) {
    echo '{"code":1,"msg":"\u672a\u63a5\u6536\u5230\u6587\u4ef6"}';
    exit;
}

$err = $_FILES['image']['error'];
if ($err !== UPLOAD_ERR_OK) {
    $msg = [
        UPLOAD_ERR_INI_SIZE => '\u6587\u4ef6\u5927\u5c0f\u8d85\u51fa\u670d\u52a1\u5668\u9650\u5236\uff08php.ini upload_max_filesize\uff09',
        UPLOAD_ERR_FORM_SIZE => '\u6587\u4ef6\u5927\u5c0f\u8d85\u51fa\u8868\u5355\u9650\u5236',
        UPLOAD_ERR_PARTIAL => '\u6587\u4ef6\u4ec5\u90e8\u5206\u4e0a\u4f20',
        UPLOAD_ERR_NO_FILE => '\u672a\u9009\u62e9\u6587\u4ef6',
    ];
    echo '{"code":1,"msg":"' . ($msg[$err] ?? '\u4e0a\u4f20\u9519\u8bef: ' . $err) . '"}';
    exit;
}

$file = $_FILES['image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    echo '{"code":1,"msg":"\u4e0d\u652f\u6301\u7684\u56fe\u7247\u683c\u5f0f"}';
    exit;
}

$dir = __DIR__ . '/../assets/images/carousel/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo '{"code":1,"msg":"\u4fdd\u5b58\u6587\u4ef6\u5931\u8d25\uff0c\u8bf7\u68c0\u67e5\u76ee\u5f55\u6743\u9650"}';
    exit;
}

$path = '/assets/images/carousel/' . $filename;
echo '{"code":0,"path":"' . $path . '"}';
