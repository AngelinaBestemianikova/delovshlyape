<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/staff_schedule.php';

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit;
}

$bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : (isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0);
if ($bookingId < 1) {
    echo json_encode(['success' => false, 'message' => 'Не указана заявка']);
    exit;
}

$bk = $link->query("
    SELECT b.id, b.event_date, b.status, b.program_id, p.name AS program_name, p.animator_count
    FROM bookings b
    INNER JOIN programs p ON p.id = b.program_id
    WHERE b.id = " . (int) $bookingId . "
    LIMIT 1
");
if (!$bk || $bk->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Заявка не найдена']);
    exit;
}
$row = $bk->fetch_assoc();

if ($row['status'] === 'canceled') {
    echo json_encode(['success' => false, 'message' => 'Нельзя менять аниматоров у отменённой заявки']);
    exit;
}

$eventDate = $row['event_date'];
if (strtotime($eventDate . ' 23:59:59') <= strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Дата мероприятия уже наступила или прошла']);
    exit;
}

$programId = (int) $row['program_id'];
$animatorCount = (int) $row['animator_count'];
if ($animatorCount < 1) {
    echo json_encode(['success' => false, 'message' => 'У программы не задано число аниматоров']);
    exit;
}

$currentIds = [];
$cur = $link->query('SELECT team_member_id FROM booked_animators WHERE booking_id = ' . (int) $bookingId . ' ORDER BY team_member_id ASC');
if ($cur) {
    while ($r = $cur->fetch_assoc()) {
        $currentIds[] = (int) $r['team_member_id'];
    }
}

$animators = staff_schedule_available_animators_for_booking_edit($link, $programId, $eventDate, $bookingId);

echo json_encode([
    'success' => true,
    'booking_id' => $bookingId,
    'event_date' => $eventDate,
    'program_name' => $row['program_name'],
    'animator_count' => $animatorCount,
    'current_animator_ids' => $currentIds,
    'animators' => $animators,
]);
