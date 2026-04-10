<?php
require_once '../includes/db.php';
session_start();

// Проверка прав (только админ)
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    die(json_encode(['success' => false, 'message' => 'No access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status = mysqli_real_escape_string($link, $_POST['status']);

    $query = "UPDATE reviews SET status = '$status' WHERE id = $id";

    if (mysqli_query($link, $query)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
    }
}