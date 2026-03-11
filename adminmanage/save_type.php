<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

$id = $_POST['id'] ?? null;
$name = $_POST['name'];
$description = $_POST['description'] ?? '';
$path_image = $_POST['path_image'] ?? '';
$name_for_menu = $_POST['name_for_menu'] ?? '';

if ($id) {
    $stmt = mysqli_prepare($link, "UPDATE program_types SET name=?, description=?, path_image=?, name_for_menu=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $description, $path_image, $name_for_menu, $id);
    mysqli_stmt_execute($stmt);
} else {
    $stmt = mysqli_prepare($link, "INSERT INTO program_types (name,description,path_image,name_for_menu) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $description, $path_image, $name_for_menu);
    mysqli_stmt_execute($stmt);
}
?>