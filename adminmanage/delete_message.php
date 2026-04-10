<?php
include '../includes/db.php';
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($link, "DELETE FROM contact_messages WHERE id = $id");
}
echo json_encode(['success' => true]);