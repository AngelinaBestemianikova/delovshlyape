<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

$id = (int) ($_GET['id'] ?? 0);
if (!$id)
    exit;

// 1. Получаем инфо о программе, чтобы составить текст уведомления
$prog_res = mysqli_query($link, "SELECT name FROM programs WHERE id = $id");
$prog_data = mysqli_fetch_assoc($prog_res);
$prog_name = $prog_data['name'];

// 2. Архивируем программу
mysqli_query($link, "UPDATE programs SET is_archived = 1 WHERE id = $id");

// 3. Находим все активные бронирования этой программы (не отмененные)
$bookings_res = mysqli_query($link, "SELECT id, user_id, event_date FROM bookings WHERE program_id = $id AND status != 'canceled'");

while ($booking = mysqli_fetch_assoc($bookings_res)) {
    $b_id = $booking['id'];
    $u_id = $booking['user_id'];
    $date = date('d.m.Y', strtotime($booking['event_date']));

    // 4. Отменяем бронь
    mysqli_query($link, "UPDATE bookings SET status = 'canceled' WHERE id = $b_id");

    // 5. Отправляем уведомление пользователю
    $msg = "К сожалению, программа «{$prog_name}» более недоступна. Ваше бронирование на {$date} было автоматически отменено. Свяжитесь с нами для выбора другой программы.";
    $msg = mysqli_real_escape_string($link, $msg);

    mysqli_query($link, "INSERT INTO user_notifications (user_id, message) VALUES ($u_id, '$msg')");
}

echo "ok";