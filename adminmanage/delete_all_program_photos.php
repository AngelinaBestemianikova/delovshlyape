<?php
require_once '../includes/db.php';
$p_id = (int) $_GET['program_id'];
// Опционально: здесь можно добавить код для физического удаления файлов через unlink()
mysqli_query($link, "DELETE FROM photos WHERE program_id = $p_id");
echo json_encode(['success' => true]);