<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/staff_schedule.php';
require_once __DIR__ . '/includes/time_off_requests.php';

if (!isset($_SESSION['client_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Требуется вход']);
    exit;
}

$userId = (int) $_SESSION['client_id'];

$res = mysqli_query($link, "SELECT id FROM users WHERE id = $userId AND is_animator = 1 LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Только для аниматоров']);
    exit;
}

$tm = time_off_resolve_animator_team_member($link, $userId);
if ($tm === null) {
    echo json_encode([
        'ok' => false,
        'error' => 'Учётная запись не привязана к карточке аниматора в команде.',
    ]);
    exit;
}

staff_schedule_sync_period_and_defaults($link);
$meta = staff_schedule_get_meta_row($link);
if (!$meta || empty($meta['period_start']) || empty($meta['period_end'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Не удалось загрузить период графика.',
    ]);
    exit;
}

$periodStart = (string) $meta['period_start'];
$periodEnd = (string) $meta['period_end'];
$today = (new DateTimeImmutable('today'))->format('Y-m-d');

$memberId = $tm['id'];
$allowed = [];

$d = new DateTimeImmutable(max($today, $periodStart));
$dEnd = new DateTimeImmutable($periodEnd);
while ($d <= $dEnd) {
    $ymd = $d->format('Y-m-d');
    if (staff_schedule_animator_eligible_time_off_date($link, $memberId, $ymd)) {
        $allowed[] = $ymd;
    }
    $d = $d->modify('+1 day');
}

echo json_encode(
    [
        'ok' => true,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'allowed_dates' => $allowed,
        'today' => $today,
    ],
    JSON_UNESCAPED_UNICODE
);
