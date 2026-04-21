<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/staff_schedule.php';

if (!isset($_SESSION['client_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Требуется вход в систему']);
    exit;
}

$client_id = (int) $_SESSION['client_id'];

$stmt = $link->prepare('SELECT id FROM users WHERE id = ? AND is_animator = 1 LIMIT 1');
$stmt->bind_param('i', $client_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Доступ только для аниматоров']);
    exit;
}
$stmt->close();

$role = staff_schedule_animator_role();
$stmt = $link->prepare(
    'SELECT tm.id, tm.name FROM team_members tm
     WHERE tm.email = (SELECT u.email FROM users u WHERE u.id = ? LIMIT 1)
     AND TRIM(tm.role) = ?
     LIMIT 1'
);
$stmt->bind_param('is', $client_id, $role);
$stmt->execute();
$tmRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tmRow) {
    echo json_encode([
        'ok' => false,
        'error' => 'Учётная запись не привязана к карточке сотрудника в команде. Обратитесь к администратору.',
    ]);
    exit;
}

$memberId = (int) $tmRow['id'];
$memberName = $tmRow['name'];

staff_schedule_sync_period_and_defaults($link);
$meta = staff_schedule_get_meta_row($link);
if (!$meta) {
    echo json_encode(['ok' => false, 'error' => 'Не удалось загрузить график']);
    exit;
}

$ps = $meta['period_start'];
$pe = $meta['period_end'];

$schedule = [$memberId => []];

$q = $link->prepare(
    'SELECT work_date, works FROM staff_schedule_days
     WHERE team_member_id = ? AND work_date >= ? AND work_date <= ?'
);
$q->bind_param('iss', $memberId, $ps, $pe);
$q->execute();
$dr = $q->get_result();
if ($dr) {
    while ($row = $dr->fetch_assoc()) {
        $d = $row['work_date'];
        $schedule[$memberId][$d] = (int) $row['works'] === 1 ? 1 : 0;
    }
}
$q->close();

echo json_encode([
    'ok' => true,
    'period_start' => $meta['period_start'],
    'period_end' => $meta['period_end'],
    'members' => [
        ['id' => $memberId, 'name' => $memberName],
    ],
    'schedule' => $schedule,
]);
