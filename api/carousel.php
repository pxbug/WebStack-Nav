<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    init_database();
    $pdo = pdo_connect();
    $slides = $pdo->query("SELECT id, image_url, link FROM carousel WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
    foreach ($slides as &$row) {
        $row['image_url'] = normalize_public_path($row['image_url']);
    }
    unset($row);
    echo json_encode($slides, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}