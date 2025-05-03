<?php
// Включаем буферизацию вывода
ob_start();

session_start();
require_once 'includes/db.php';

// Функция для отправки JSON-ответа
function sendJsonResponse($success, $message = '') {
    ob_clean(); // Очищаем буфер вывода
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    if (!isset($_POST['booking_id']) || !isset($_POST['comment'])) {
        sendJsonResponse(false, 'Не все поля заполнены');
    }

    $booking_id = intval($_POST['booking_id']);
    $comment = mysqli_real_escape_string($link, $_POST['comment']);
    $user_id = $_SESSION['client_id'];

    // Простая вставка отзыва
    $query = "INSERT INTO reviews (user_id, comment, created_time, booking_id) VALUES ($user_id, '$comment', NOW(), $booking_id)";

    if (mysqli_query($link, $query)) {
        sendJsonResponse(true);
    } else {
        sendJsonResponse(false, 'Ошибка при сохранении отзыва: ' . mysqli_error($link));
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Произошла ошибка: ' . $e->getMessage());
}
?> 