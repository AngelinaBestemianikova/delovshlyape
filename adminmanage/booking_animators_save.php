<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/staff_schedule.php';

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit;
}

$bookingId = (int) ($data['booking_id'] ?? 0);
$idsIn = $data['animator_ids'] ?? [];
if ($bookingId < 1 || !is_array($idsIn)) {
    echo json_encode(['success' => false, 'message' => 'Не указаны заявка или аниматоры']);
    exit;
}

$ids = [];
foreach ($idsIn as $v) {
    $i = (int) $v;
    if ($i > 0) {
        $ids[] = $i;
    }
}
$ids = array_values(array_unique($ids));

$bk = $link->query("
    SELECT b.id, b.event_date, b.status, b.program_id, p.animator_count
    FROM bookings b
    INNER JOIN programs p ON p.id = b.program_id
    WHERE b.id = " . (int) $bookingId . "
    LIMIT 1
");
if (!$bk || $bk->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Заявка не найдена']);
    exit;
}
$brow = $bk->fetch_assoc();

if ($brow['status'] === 'canceled') {
    echo json_encode(['success' => false, 'message' => 'Заявка отменена']);
    exit;
}

$eventDate = $brow['event_date'];
if (strtotime($eventDate . ' 23:59:59') <= strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Дата мероприятия уже наступила или прошла']);
    exit;
}

$programId = (int) $brow['program_id'];
$need = (int) $brow['animator_count'];
if ($need < 1) {
    echo json_encode(['success' => false, 'message' => 'У программы не задано число аниматоров']);
    exit;
}

if (count($ids) !== $need) {
    echo json_encode(['success' => false, 'message' => 'Нужно выбрать ровно ' . $need . ' аниматор(ов)']);
    exit;
}

$allowed = staff_schedule_available_animators_for_booking_edit($link, $programId, $eventDate, $bookingId);
$allowedMap = [];
foreach ($allowed as $a) {
    $allowedMap[(int) $a['id']] = true;
}
foreach ($ids as $mid) {
    if (!isset($allowedMap[$mid])) {
        echo json_encode(['success' => false, 'message' => 'Один из выбранных аниматоров недоступен для этой даты или программы']);
        exit;
    }
}

mysqli_begin_transaction($link);
try {
    if (!$link->query('DELETE FROM booked_animators WHERE booking_id = ' . (int) $bookingId)) {
        throw new RuntimeException($link->error);
    }
    $ins = $link->prepare('INSERT INTO booked_animators (booking_id, team_member_id) VALUES (?, ?)');
    if (!$ins) {
        throw new RuntimeException($link->error);
    }
    foreach ($ids as $mid) {
        $b = (int) $bookingId;
        $m = (int) $mid;
        $ins->bind_param('ii', $b, $m);
        if (!$ins->execute()) {
            throw new RuntimeException($ins->error);
        }
    }
    $ins->close();
    mysqli_commit($link);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $link->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
