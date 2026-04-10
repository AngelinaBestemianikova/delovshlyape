<?php
require_once '../includes/db.php'; // Проверьте путь до db.php
session_start();

header('Content-Type: application/json');

// Проверка прав (как в вашем save_team_member.php)
if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    // 1. Получаем основные данные сотрудника
    $result = mysqli_query($link, "SELECT * FROM team_members WHERE id = $id");
    $member = mysqli_fetch_assoc($result);

    if ($member) {
        // 2. Получаем ID программ, которые за ним закреплены
        $programs_query = mysqli_query($link, "SELECT program_id FROM animator_programs WHERE team_member_id = $id");
        $program_ids = [];
        while ($row = mysqli_fetch_assoc($programs_query)) {
            $program_ids[] = (int) $row['program_id'];
        }

        // Добавляем массив ID в объект сотрудника
        $member['program_ids'] = $program_ids;

        echo json_encode($member);
    } else {
        echo json_encode(['error' => 'Member not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid ID']);
}