<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

$id = (int) $_GET['id'];

// 1. Получаем путь к файлу, чтобы удалить его с диска
$res = mysqli_query($link, "SELECT path FROM photos WHERE id = $id");
$photo = mysqli_fetch_assoc($res);

if ($photo) {
    $full_path = '../' . $photo['path'];
    if (file_exists($full_path)) {
        unlink($full_path); // Удаляем физический файл
    }

    // 2. Удаляем запись из базы
    mysqli_query($link, "DELETE FROM photos WHERE id = $id");
    echo json_encode(['success' => true]);
}