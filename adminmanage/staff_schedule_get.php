<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/staff_schedule.php';

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Доступ запрещён']);
    exit;
}

staff_schedule_sync_period_and_defaults($link);
$meta = staff_schedule_get_meta_row($link);
if (!$meta) {
    echo json_encode(['ok' => false, 'error' => 'Не удалось загрузить график']);
    exit;
}

$ps = $link->real_escape_string($meta['period_start']);
$pe = $link->real_escape_string($meta['period_end']);

$members = [];
$role = staff_schedule_animator_role();
$stmt = $link->prepare('SELECT id, name FROM team_members WHERE TRIM(role) = ? ORDER BY name ASC');
$stmt->bind_param('s', $role);
$stmt->execute();
$mr = $stmt->get_result();
if ($mr) {
    while ($row = $mr->fetch_assoc()) {
        $members[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
        ];
    }
}
$stmt->close();

$schedule = [];
$dr = $link->query("SELECT team_member_id, work_date, works FROM staff_schedule_days WHERE work_date >= '$ps' AND work_date <= '$pe'");
if ($dr) {
    while ($row = $dr->fetch_assoc()) {
        $mid = (int) $row['team_member_id'];
        $d = $row['work_date'];
        if (!isset($schedule[$mid])) {
            $schedule[$mid] = [];
        }
        $schedule[$mid][$d] = (int) $row['works'] === 1 ? 1 : 0;
    }
}

echo json_encode([
    'ok' => true,
    'period_start' => $meta['period_start'],
    'period_end' => $meta['period_end'],
    'status' => $meta['status'],
    'members' => $members,
    'schedule' => $schedule,
]);
