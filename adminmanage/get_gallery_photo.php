<?php
require_once '../includes/db.php';
$id = (int) $_GET['id'];
$res = mysqli_query($link, "SELECT * FROM photos WHERE id = $id");
echo json_encode(mysqli_fetch_assoc($res));