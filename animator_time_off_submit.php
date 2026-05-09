<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/time_off_requests.php';
require_once __DIR__ . '/includes/staff_schedule.php';

if (!isset($_SESSION['client_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Необходим вход']);
    exit;
}

$userId = (int) $_SESSION['client_id'];

$chk = mysqli_query($link, "SELECT id FROM users WHERE id = $userId AND is_animator = 1 LIMIT 1");
if (!$chk || mysqli_num_rows($chk) === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Только для аниматоров']);
    exit;
}

$tm = time_off_resolve_animator_team_member($link, $userId);
if ($tm === null) {
    echo json_encode(['success' => false, 'error' => 'Профиль не привязан к сотруднику команды']);
    exit;
}

$raw = file_get_contents('php://input');
$body = [];
if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}
$datesIn = isset($body['dates']) && is_array($body['dates']) ? $body['dates'] : [];

$cleanDates = [];
foreach ($datesIn as $v) {
    if (!is_string($v)) {
        echo json_encode(['success' => false, 'error' => 'Некорректный формат дат']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        echo json_encode(['success' => false, 'error' => 'Даты должны быть в формате ГГГГ-ММ-ДД']);
        exit;
    }
    $cleanDates[] = $v;
}
$cleanDates = array_values(array_unique($cleanDates));
sort($cleanDates);

if ($cleanDates === []) {
    echo json_encode(['success' => false, 'error' => 'Добавьте хотя бы один день отгула']);
    exit;
}

if (count($cleanDates) > 31) {
    echo json_encode(['success' => false, 'error' => 'За один раз не более 31 дня']);
    exit;
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
foreach ($cleanDates as $d) {
    if ($d < $today) {
        echo json_encode(['success' => false, 'error' => 'Нельзя запрашивать отгулы за прошедшие дни']);
        exit;
    }
}

time_off_requests_ensure_schema($link);

staff_schedule_sync_period_and_defaults($link);

foreach ($cleanDates as $d) {
    if (!staff_schedule_animator_eligible_time_off_date($link, $tm['id'], $d)) {
        echo json_encode([
            'success' => false,
            'error' => 'Отгул можно запрашивать только на дни, когда вы по графику работаете (внутри периода планирования).',
        ]);
        exit;
    }
}

if (time_off_pending_dates_overlap($userId, $cleanDates, $link)) {
    echo json_encode([
        'success' => false,
        'error' => 'На одну или несколько выбранных дат уже есть нерассмотренный запрос',
    ]);
    exit;
}

mysqli_begin_transaction($link);
try {
    foreach ($cleanDates as $d) {
        $dEsc = mysqli_real_escape_string($link, $d);
        $oneJson = mysqli_real_escape_string($link, json_encode([$d], JSON_UNESCAPED_UNICODE));
        $ok = mysqli_query(
            $link,
            "INSERT INTO animator_time_off_requests (animator_user_id, request_date, dates_json, comment)
             VALUES ($userId, '$dEsc', '$oneJson', NULL)"
        );
        if (!$ok) {
            mysqli_rollback($link);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
            exit;
        }
    }
    mysqli_commit($link);
} catch (Throwable $e) {
    mysqli_rollback($link);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сохранения']);
    exit;
}

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
