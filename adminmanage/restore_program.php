<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1)
    exit;

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($link, "UPDATE programs SET is_archived = 0 WHERE id = $id");
    echo "ok";
}