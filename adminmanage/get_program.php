<?php
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Нет ID']);
    exit;
}

$id = (int) $_GET['id'];
$result = mysqli_query($link, "SELECT * FROM programs WHERE id=$id");
$program = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode($program);