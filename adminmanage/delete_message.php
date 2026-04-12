<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Не указан id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) $_GET['id'];
if ($id < 1) {
    echo json_encode(['success' => false, 'error' => 'Некорректный id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = mysqli_prepare($link, 'DELETE FROM contact_messages WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);