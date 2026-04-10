<?php
require_once '../includes/db.php';
session_start();

// Проверка прав администратора
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    exit('Access denied');
}

if (isset($_GET['id'])) {
    $type_id = (int) $_GET['id'];

    // Начало транзакции (желательно, чтобы оба действия выполнились успешно)
    mysqli_begin_transaction($link);

    try {
        // 1. Архивируем сам тип программы
        $stmt1 = mysqli_prepare($link, "UPDATE program_types SET is_archived = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt1, "i", $type_id);
        mysqli_stmt_execute($stmt1);

        // 2. Архивируем все программы, принадлежащие этому типу
        $stmt2 = mysqli_prepare($link, "UPDATE programs SET is_archived = 1 WHERE type_id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $type_id);
        mysqli_stmt_execute($stmt2);

        // Фиксируем изменения
        mysqli_commit($link);
        echo "Success";
    } catch (Exception $e) {
        // Если что-то пошло не так, откатываем изменения
        mysqli_rollback($link);
        http_response_code(500);
        echo "Error";
    }
}