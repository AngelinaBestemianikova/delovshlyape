<?php
session_start();
header('Content-Type: application/json');

$response = [
    'loggedIn' => isset($_SESSION['client_id'])
];
echo json_encode([
    'loggedIn' => isset($_SESSION['client_id']),
    'is_admin' => isset($_SESSION['is_admin']) && (int) $_SESSION['is_admin'] === 1
]);
?>