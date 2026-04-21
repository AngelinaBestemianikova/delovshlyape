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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный JSON']);
    exit;
}

$action = isset($data['action']) ? (string) $data['action'] : 'save';
if ($action !== 'save' && $action !== 'approve') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Неизвестное действие']);
    exit;
}

$schedule = isset($data['schedule']) && is_array($data['schedule']) ? $data['schedule'] : [];

staff_schedule_sync_period_and_defaults($link);
$meta = staff_schedule_get_meta_row($link);
if (!$meta) {
    echo json_encode(['ok' => false, 'error' => 'Нет метаданных графика']);
    exit;
}

$ps = $meta['period_start'];
$pe = $meta['period_end'];

$newStatus = $action === 'approve' ? 'approved' : 'draft';

if (!$link->begin_transaction()) {
    echo json_encode(['ok' => false, 'error' => 'Не удалось начать транзакцию']);
    exit;
}

$scheduleStmt = null;
try {
    $scheduleStmt = $link->prepare('REPLACE INTO staff_schedule_days (team_member_id, work_date, works) VALUES (?, ?, ?)');
    if (!$scheduleStmt) {
        throw new RuntimeException('prepare schedule');
    }

    foreach ($schedule as $midKey => $dates) {
        $mid = (int) $midKey;
        if ($mid < 1 || !is_array($dates)) {
            continue;
        }
        foreach ($dates as $dateStr => $worksVal) {
            if (!is_string($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                continue;
            }
            if ($dateStr < $ps || $dateStr > $pe) {
                continue;
            }
            $w = ((int) $worksVal === 1) ? 1 : 0;
            $scheduleStmt->bind_param('isi', $mid, $dateStr, $w);
            if (!$scheduleStmt->execute()) {
                throw new RuntimeException('execute schedule');
            }
        }
    }
    $scheduleStmt->close();
    $scheduleStmt = null;

    $st = $link->prepare('UPDATE staff_schedule_meta SET status = ? WHERE id = 1');
    if (!$st) {
        throw new RuntimeException('prepare meta');
    }
    $st->bind_param('s', $newStatus);
    if (!$st->execute()) {
        $st->close();
        throw new RuntimeException('execute meta');
    }
    $st->close();

    $bookingsMarked = staff_schedule_mark_confirmed_conflicts_pending($link, $ps, $pe);

    if (!$link->commit()) {
        throw new RuntimeException('commit');
    }

    echo json_encode([
        'ok' => true,
        'status' => $newStatus,
        'bookings_marked_pending' => $bookingsMarked,
    ]);
} catch (Throwable $e) {
    $link->rollback();
    if ($scheduleStmt instanceof mysqli_stmt) {
        $scheduleStmt->close();
    }
    echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить график']);
}
