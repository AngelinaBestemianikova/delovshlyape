<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ВАЖНО: Если файл в папке adminmanage, путь должен быть таким:
require_once '../includes/db.php';
// Если файл в корне, оставьте: require_once 'includes/db.php';

session_start();

header('Content-Type: application/json');

// Проверка админа
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Ошибка доступа: вы не админ']);
    exit;
}

// Проверка входящих данных
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Не переданы ID или статус']);
    exit;
}

$id = (int) $_POST['id'];
$status = mysqli_real_escape_string($link, $_POST['status']);

$lock_check = mysqli_query($link, "SELECT id FROM bookings WHERE id = $id AND DATE(event_date) > CURDATE()");
if (!$lock_check || mysqli_num_rows($lock_check) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Нельзя менять статус после наступления даты мероприятия.',
    ]);
    exit;
}

// Пробуем выполнить запрос
$sql = "UPDATE bookings SET status = '$status' WHERE id = $id";
$update = mysqli_query($link, $sql);

if ($update) {
    // Проверяем, была ли реально затронута хоть одна строка
    if (mysqli_affected_rows($link) > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Запись не найдена или статус уже такой же']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка SQL: ' . mysqli_error($link)]);
}