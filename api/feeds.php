<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../admin/config.php';
init_database();

try {
    $pdo = pdo_connect();

    $stmt = $pdo->query("SELECT id, content, images, created_at FROM feeds ORDER BY created_at DESC LIMIT 50");
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $images = [];
        if (!empty($row['images'])) {
            $images = array_filter(array_map('trim', explode(',', $row['images'])));
        }
        $result[] = [
            'id'        => (int) $row['id'],
            'content'   => $row['content'],
            'images'    => $images,
            'created_at' => $row['created_at'],
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
