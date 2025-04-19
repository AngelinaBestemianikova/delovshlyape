<?php
session_start();
header('Content-Type: application/json');

$response = [
    'loggedIn' => isset($_SESSION['client_id'])
];

echo json_encode($response);
?> 