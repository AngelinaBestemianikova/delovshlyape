<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/staff_schedule.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Ошибка доступа: вы не админ']);
    exit;
}

if (!isset($_POST['id'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Не переданы ID или статус']);
    exit;
}

$id = (int) $_POST['id'];
$allowed = ['pending', 'confirmed', 'canceled', 'archived'];
$status = (string) $_POST['status'];
if (!in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимый статус']);
    exit;
}

$lock = $link->prepare('SELECT id FROM bookings WHERE id = ? AND DATE(event_date) >= CURDATE() LIMIT 1');
if (!$lock) {
    echo json_encode(['success' => false, 'message' => 'Ошибка проверки даты']);
    exit;
}
$lock->bind_param('i', $id);
$lock->execute();
$okLock = $lock->get_result()->fetch_assoc();
$lock->close();
if (!$okLock) {
    echo json_encode([
        'success' => false,
        'message' => 'Нельзя менять статус: дата мероприятия уже прошла.',
    ]);
    exit;
}

if ($status === 'confirmed') {
    $err = staff_schedule_validate_booking_can_confirm($link, $id);
    if ($err !== null) {
        echo json_encode(['success' => false, 'message' => $err]);
        exit;
    }
}

$stmt = $link->prepare('UPDATE bookings SET status = ? WHERE id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса']);
    exit;
}
$stmt->bind_param('si', $status, $id);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Ошибка SQL: ' . $err]);
    exit;
}
$affected = (int) $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Запись не найдена или статус уже такой же']);
}
