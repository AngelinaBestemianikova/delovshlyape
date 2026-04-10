<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Проверка прав доступа
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = mysqli_real_escape_string($link, $_POST['name']);
        $role = mysqli_real_escape_string($link, $_POST['role']);
        $email = mysqli_real_escape_string($link, $_POST['email']);
        $selected_programs = isset($_POST['programs']) ? $_POST['programs'] : [];

        // 1. Обработка изображения
        $image_path = null;
        // Исправлено: в JS мы назвали поле 'image_file'
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/team/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('staff_') . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                $image_path = 'uploads/team/' . $file_name;
            }
        }

        if ($id > 0) {
            // РЕДАКТИРОВАНИЕ
            $query = "UPDATE team_members SET name = '$name', role = '$role', email = '$email'";
            if ($image_path) {
                $query .= ", path_image = '$image_path'";
            }
            $query .= " WHERE id = $id";
            if (!mysqli_query($link, $query))
                throw new Exception(mysqli_error($link));
        } else {
            // СОЗДАНИЕ НОВОГО
            $img_val = $image_path ? "'$image_path'" : "NULL";
            $query = "INSERT INTO team_members (name, role, email, path_image) 
                      VALUES ('$name', '$role', '$email', $img_val)";
            if (!mysqli_query($link, $query))
                throw new Exception(mysqli_error($link));
            $id = mysqli_insert_id($link);
        }

        // 2. Обновление программ (animator_programs)
        mysqli_query($link, "DELETE FROM animator_programs WHERE team_member_id = $id");

        if (!empty($selected_programs)) {
            foreach ($selected_programs as $program_id) {
                $p_id = (int) $program_id;
                mysqli_query($link, "INSERT INTO animator_programs (team_member_id, program_id) VALUES ($id, $p_id)");
            }
        }

        // ВАЖНО: Ваша функция renderModal в JS проверяет result.success
        echo json_encode(['success' => true, 'id' => $id]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}