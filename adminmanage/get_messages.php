<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

$res = mysqli_query($link, 'SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 20');
$msgs = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
echo json_encode($msgs, JSON_UNESCAPED_UNICODE);