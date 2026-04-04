<?php
session_start();
include __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $client_id = $_SESSION['client_id'];

    function clearString($str)
    {
        return stripslashes(strip_tags(trim($str ?? '')));
    }

    $first_name = clearString($_POST["first_name"]);
    $last_name = clearString($_POST["last_name"]);
    $email = clearString($_POST["email"]);
    $phone = clearString($_POST["phone"]);

    $errors = [];

    // Валидация как в регистрации
    if (!preg_match('/^[А-ЯЁа-яё]{2,}$/u', $first_name)) {
        $errors["first_name"] = "Имя должно содержать только буквы.";
    }
    if (!preg_match('/^[А-ЯЁа-яё]{2,}$/u', $last_name)) {
        $errors["last_name"] = "Фамилия должна содержать только буквы.";
    }
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i', $email)) {
        $errors["email"] = "Некорректный E-mail.";
    } else {
        // Проверка уникальности Email (исключая текущего юзера)
        $query = "SELECT id FROM users WHERE email='$email' AND id != '$client_id'";
        $result = mysqli_query($link, $query);
        if (mysqli_num_rows($result) > 0)
            $errors["email"] = "Этот Email уже занят.";
    }
    if (!preg_match('/^\+375(24|25|29|33|44)\d{7}$/', $phone)) {
        $errors["phone"] = "Неверный формат телефона.";
    }

    if (empty($errors)) {
        $update = "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' WHERE id='$client_id'";
        if (mysqli_query($link, $update)) {
            $_SESSION['name'] = $first_name; // Обновляем имя в сессии
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Ошибка БД"]);
        }
    } else {
        echo json_encode(["success" => false, "errors" => $errors]);
    }
    exit;
}