<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

$id = $_GET['id'] ?? null;
if (!$id)
    exit;

mysqli_query($link, "DELETE FROM program_types WHERE id=$id");
?>