<?php
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    exit(json_encode(['error' => 'Нет ID']));
}

$id = (int) $_GET['id'];

// 1. Получаем данные программы
$result = mysqli_query($link, "SELECT * FROM programs WHERE id=$id");
$program = mysqli_fetch_assoc($result);

// 2. Получаем массив ID аниматоров из связующей таблицы
$anim_res = mysqli_query($link, "SELECT team_member_id FROM animator_programs WHERE program_id=$id");
$anim_ids = [];
while ($row = mysqli_fetch_assoc($anim_res)) {
    $anim_ids[] = (int) $row['team_member_id'];
}

// Добавляем этот массив в объект программы
$program['animator_ids'] = $anim_ids;

header('Content-Type: application/json');
echo json_encode($program);