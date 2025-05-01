<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit();
}

if (!isset($_POST['booking_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Не все поля заполнены']);
    exit();
}

$booking_id = intval($_POST['booking_id']);
$comment = mysqli_real_escape_string($link, $_POST['comment']);
$user_id = $_SESSION['client_id'];

// Проверяем, принадлежит ли бронирование пользователю и прошло ли оно
$check_query = "
    SELECT id, event_date 
    FROM bookings 
    WHERE id = $booking_id 
    AND user_id = $user_id";
$check_result = mysqli_query($link, $check_query);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Бронирование не найдено']);
    exit();
}

$booking = mysqli_fetch_assoc($check_result);
$event_date = new DateTime($booking['event_date']);
$today = new DateTime();

// Проверяем, не оставлен ли уже отзыв
$review_check_query = "SELECT id FROM reviews WHERE booking_id = $booking_id";
$review_check_result = mysqli_query($link, $review_check_query);

if (mysqli_num_rows($review_check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Отзыв уже оставлен']);
    exit();
}

// Сохраняем отзыв
$insert_query = "
    INSERT INTO reviews (booking_id, comment, created_at)
    VALUES ($booking_id, '$comment', NOW())";

if (mysqli_query($link, $insert_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении отзыва']);
}
?> 