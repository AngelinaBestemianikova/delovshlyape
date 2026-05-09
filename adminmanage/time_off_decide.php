<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/time_off_requests.php';
require_once __DIR__ . '/../includes/staff_schedule.php';

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Нет доступа']);
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
$action = isset($data['action']) && is_string($data['action']) ? $data['action'] : '';

if ($id < 1 || !in_array($action, ['approved', 'rejected', 'pending'], true)) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

time_off_requests_ensure_schema($link);

$res = mysqli_query(
    $link,
    'SELECT id, animator_user_id, request_date, dates_json, status FROM animator_time_off_requests WHERE id = ' . $id . ' LIMIT 1'
);

if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'error' => 'Запись не найдена']);
    exit;
}

$row = mysqli_fetch_assoc($res);
$animatorUserId = (int) $row['animator_user_id'];
$currentStatus = $row['status'] ?? '';

$dates = time_off_row_effective_dates($link, $row);
if ($dates === []) {
    echo json_encode(['success' => false, 'error' => 'Битые данные запроса']);
    exit;
}

$tm = time_off_resolve_animator_team_member($link, $animatorUserId);

if ($action === 'pending') {
    if ($currentStatus === 'pending') {
        echo json_encode(['success' => false, 'error' => 'Запрос уже на рассмотрении']);
        exit;
    }
    if ($currentStatus !== 'approved' && $currentStatus !== 'rejected') {
        echo json_encode(['success' => false, 'error' => 'Некорректный статус']);
        exit;
    }

    if ($currentStatus === 'approved' && $tm !== null) {
        staff_schedule_sync_period_and_defaults($link);
        foreach ($dates as $d) {
            time_off_restore_schedule_to_working($link, $tm['id'], $d);
        }
    }

    if (!mysqli_query(
        $link,
        'UPDATE animator_time_off_requests SET status = \'pending\', decided_at = NULL
         WHERE id = ' . $id . " AND status IN ('approved','rejected')"
    )) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
        exit;
    }
    if (mysqli_affected_rows($link) === 0) {
        echo json_encode(['success' => false, 'error' => 'Не удалось изменить решение']);
        exit;
    }

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($currentStatus !== 'pending') {
    echo json_encode(['success' => false, 'error' => 'Запрос уже обработан']);
    exit;
}

if ($action === 'approved') {
    if ($tm !== null) {
        staff_schedule_sync_period_and_defaults($link);
        time_off_apply_approved_dates_to_schedule($link, $tm['id'], $dates);
    }
}

$verb = $action === 'approved' ? 'одобрен' : 'отклонён';
$daysRu = time_off_format_dates_ru($dates);
$dateWord = count($dates) === 1 ? 'Дата' : 'Даты';
$msgRu = sprintf(
    'Ваш запрос на отгул был %s. %s: %s.',
    $verb,
    $dateWord,
    $daysRu
);
$msgEsc = mysqli_real_escape_string($link, $msgRu);
$escapedAction = mysqli_real_escape_string($link, $action);

if (!mysqli_query(
    $link,
    "UPDATE animator_time_off_requests
     SET status = '$escapedAction', decided_at = NOW()
     WHERE id = $id AND status = 'pending'"
)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
    exit;
}

if (mysqli_affected_rows($link) === 0) {
    echo json_encode(['success' => false, 'error' => 'Запрос уже обработан']);
    exit;
}

time_off_insert_animator_notification($link, $animatorUserId, $msgEsc, 'time_off_decision');

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
