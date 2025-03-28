<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'Delovshlyape';

$link = new mysqli($host, $user, $pass, $db);

if ($link->connect_error) {
    die("Ошибка подключения: " . $link->connect_error);
}
?>