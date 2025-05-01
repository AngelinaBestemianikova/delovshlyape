<?php
session_start();
require_once 'includes/db.php';

$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['client_id'];

// Проверяем, принадлежит ли бронирование пользователю и удаляем его
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
?> 