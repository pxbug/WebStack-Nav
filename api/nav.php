<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/config.php';

try {
    $pdo = pdo_connect();

    $categories = $pdo->query("SELECT slug, name FROM categories ORDER BY sort_order")->fetchAll();
    $nav_items = $pdo->query("
        SELECT c.slug AS cat_slug, n.title, n.subtitle, n.icon, n.link
        FROM nav_items n
        JOIN categories c ON c.id = n.category_id
        ORDER BY n.category_id, n.sort_order
    ")->fetchAll();

    $result = [];
    foreach ($categories as $cat) {
        $result[$cat['slug']] = [];
    }
    foreach ($nav_items as $item) {
        if (isset($result[$item['cat_slug']])) {
            $result[$item['cat_slug']][] = [
                'title' => $item['title'],
                'sub' => $item['subtitle'],
                'icon' => $item['icon'],
                'href' => $item['link'],
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误'], JSON_UNESCAPED_UNICODE);
}
