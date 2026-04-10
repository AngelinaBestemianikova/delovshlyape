<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    // Удаляем сначала зависимости в animator_programs, затем самого сотрудника
    mysqli_query($link, "DELETE FROM animator_programs WHERE team_member_id = $id");
    mysqli_query($link, "DELETE FROM team_members WHERE id = $id");
    echo "ok";
}