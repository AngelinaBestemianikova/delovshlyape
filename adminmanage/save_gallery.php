<?php
// Полностью отключаем вывод любых ошибок в браузер, чтобы они не портили JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'error' => 'Нет доступа']);
    exit;
}

// Проверяем ID: если он пустой или "undefined", делаем его null
$id = (!empty($_POST['id']) && $_POST['id'] !== 'undefined') ? (int) $_POST['id'] : null;
$program_id = isset($_POST['program_id']) ? (int) $_POST['program_id'] : 0;
$upload_dir = '../images/gallery/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($id) {
    // РЕДАКТИРОВАНИЕ
    $stmt = mysqli_prepare($link, "UPDATE photos SET program_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $program_id, $id);
    mysqli_stmt_execute($stmt);

    if (!empty($_FILES['photos']['name'][0])) {
        $file_name = time() . '_' . $_FILES['photos']['name'][0];
        if (move_uploaded_file($_FILES['photos']['tmp_name'][0], $upload_dir . $file_name)) {
            $path = 'images/gallery/' . $file_name;
            $stmt_img = mysqli_prepare($link, "UPDATE photos SET path = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_img, "si", $path, $id);
            mysqli_stmt_execute($stmt_img);
        }
    }
    echo json_encode(['success' => true]);
} else {
    // ДОБАВЛЕНИЕ НОВЫХ
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === 0) {
                $file_name = time() . '_' . $_FILES['photos']['name'][$key];
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    $path = 'images/gallery/' . $file_name;
                    $stmt_ins = mysqli_prepare(
                        $link,
                        "INSERT INTO photos (path, program_id, created_time) VALUES (?, ?, NOW())",
                    );
                    mysqli_stmt_bind_param($stmt_ins, "si", $path, $program_id);
                    mysqli_stmt_execute($stmt_ins);
                }
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Файлы не выбраны']);
    }
}
exit;