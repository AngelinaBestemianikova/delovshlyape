<?php
session_start();
include './includes/db.php';
header('Content-Type: application/json');

function clearString($str)
{
    return stripslashes(strip_tags(trim($str ?? '')));
}

$response = ["passed" => true, "errors" => []];
$validate_field = $_POST['validate_field'] ?? 'all';
$is_submit = isset($_POST['is_submit']) && $_POST['is_submit'] === 'true';

$f_name = clearString($_POST['first_name']);
$l_name = clearString($_POST['last_name']);
$email = clearString($_POST['email']);
$phone = clearString($_POST['phone']);
$msg = clearString($_POST['question']);

// ВАЛИДАЦИЯ (Твои правила)
if ($validate_field === 'all' || $validate_field === 'first_name') {
    if (!preg_match('/^[А-ЯЁа-яё]{2,}$/u', $f_name))
        $response["errors"]["first_name"] = "Только буквы, от 2-х символов.";
}
if ($validate_field === 'all' || $validate_field === 'email') {
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i', $email))
        $response["errors"]["email"] = "Неверный формат почты.";
}
if ($validate_field === 'all' || $validate_field === 'phone') {
    if (!preg_match('/^\+375(24|25|29|33|44)\d{7}$/', $phone))
        $response["errors"]["phone"] = "Формат: +375XXXXXXXXX";
}
if ($validate_field === 'all' || $validate_field === 'question') {
    if (empty($msg))
        $response["errors"]["question"] = "Введите ваш вопрос.";
}

if (!empty($response["errors"]))
    $response["passed"] = false;

// СОХРАНЕНИЕ
if ($is_submit && $response["passed"]) {
    $user_id = $_SESSION['client_id'] ?? null;
    $stmt = mysqli_prepare($link, "INSERT INTO contact_messages (user_id, first_name, last_name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssss", $user_id, $f_name, $l_name, $email, $phone, $msg);
    if (!mysqli_stmt_execute($stmt)) {
        $response["passed"] = false;
        $response["errors"]["database"] = "Ошибка БД";
    }
}

echo json_encode($response);