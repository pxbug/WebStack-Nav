<?php
require_once __DIR__ . '/../admin/config.php';
header('Content-Type: application/json; charset=utf-8');

init_database();
$pdo = pdo_connect();

$stmt = $pdo->prepare("SELECT id, type, label, value, icon_bg, sort_order FROM contacts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
