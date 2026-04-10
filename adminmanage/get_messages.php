<?php
include '../includes/db.php';
// Обязательно добавьте проверку на админа тут!
$res = mysqli_query($link, "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
$msgs = mysqli_fetch_all($res, MYSQLI_ASSOC);
echo json_encode($msgs);