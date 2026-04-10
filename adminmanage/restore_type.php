<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // Восстанавливаем тип
    mysqli_query($link, "UPDATE program_types SET is_archived = 0 WHERE id = $id");
    // Если нужно автоматически восстановить и программы этого типа:
    mysqli_query($link, "UPDATE programs SET is_archived = 0 WHERE type_id = $id");
    echo "ok";
}