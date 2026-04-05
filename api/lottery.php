<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = pdo_connect();
init_database();

$action = $_GET['action'] ?? '';

if ($action === 'prob') {
    $row = $pdo->query("SELECT prob FROM lottery_prob ORDER BY id DESC LIMIT 1")->fetch();
    echo json_encode(['prob' => $row ? floatval($row['prob']) : 10.0]);
    exit;
}

echo json_encode(['error' => '未知操作']);
