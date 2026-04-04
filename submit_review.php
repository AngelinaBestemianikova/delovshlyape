<?php
// Включаем буферизацию вывода
ob_start();

session_start();
require_once 'includes/db.php';

// Функция для отправки JSON-ответа
function sendJsonResponse($success, $message = '')
{
    ob_clean(); // Очищаем буфер вывода
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    // Проверяем наличие всех нужных полей, включая program_id
    if (!isset($_POST['booking_id']) || !isset($_POST['comment']) || !isset($_POST['program_id'])) {
        sendJsonResponse(false, 'Не все поля заполнены (отсутствует ID программы)');
    }

    $booking_id = intval($_POST['booking_id']);
    $program_id = intval($_POST['program_id']); // Получаем ID программы из формы
    $comment = mysqli_real_escape_string($link, $_POST['comment']);
    $user_id = $_SESSION['client_id'];

    // Обновленный запрос: добавляем колонку program_id
    // Убедись, что колонка в БД называется именно program_id
    $query = "INSERT INTO reviews (user_id, comment, created_time, booking_id, program_id) 
              VALUES ($user_id, '$comment', NOW(), $booking_id, $program_id)";

    if (mysqli_query($link, $query)) {
        sendJsonResponse(true);
    } else {
        // Если ошибка здесь, проверь, добавил ли ты колонку в таблицу через ALTER TABLE
        sendJsonResponse(false, 'Ошибка при сохранении отзыва: ' . mysqli_error($link));
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Произошла ошибка: ' . $e->getMessage());
}
?>