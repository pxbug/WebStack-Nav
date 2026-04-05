<?php
header('Content-Type: application/json; charset=utf-8');

$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '仅支持 POST']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errs = [
        UPLOAD_ERR_INI_SIZE   => '文件超过服务器大小限制',
        UPLOAD_ERR_FORM_SIZE  => '文件超过表单大小限制',
        UPLOAD_ERR_PARTIAL    => '文件仅上传了一部分',
        UPLOAD_ERR_NO_FILE    => '未选择文件',
        UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不存在',
        UPLOAD_ERR_CANT_WRITE => '写入临时文件失败',
    ];
    $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = $errs[$code] ?? '上传失败';
    http_response_code(400);
    echo json_encode(['error' => $msg]);
    exit;
}

$file = $_FILES['image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => '不支持的图片格式：' . $ext]);
    exit;
}

if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => '图片大小不能超过 8MB']);
    exit;
}

$new_name = date('YmdHis') . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
$dest = $upload_dir . $new_name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    if (!copy($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['error' => '文件保存失败，请检查 uploads 目录权限']);
        exit;
    }
}

echo json_encode(['url' => '../uploads/' . $new_name], JSON_UNESCAPED_UNICODE);
