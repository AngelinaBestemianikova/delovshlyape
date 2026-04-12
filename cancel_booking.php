<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$user_id = (int) $_SESSION['client_id'];

if ($booking_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Некорректная заявка']);
    exit;
}

$eligible = mysqli_query(
    $link,
    "SELECT b.id FROM bookings b
     WHERE b.id = $booking_id AND b.user_id = $user_id
       AND DATE(b.event_date) > DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
);

if (!$eligible || mysqli_num_rows($eligible) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Отменить бронирование можно только если до даты мероприятия больше суток (не в день события и не накануне).',
    ]);
    exit;
}

$delete_query = "
    DELETE b, ba 
    FROM bookings b
    LEFT JOIN booked_animators ba ON b.id = ba.booking_id
    WHERE b.id = $booking_id AND b.user_id = $user_id";

if (mysqli_query($link, $delete_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении бронирования']);
} 