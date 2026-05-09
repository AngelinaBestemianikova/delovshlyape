<?php

declare(strict_types=1);

/** Таблица запросов на отгул аниматоров и поле типа для уведомлений на странице аниматора. */
function time_off_requests_ensure_schema(mysqli $link): void
{
    $link->query(
        "CREATE TABLE IF NOT EXISTS animator_time_off_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            animator_user_id INT NOT NULL,
            request_date DATE NULL,
            dates_json TEXT NOT NULL,
            comment TEXT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            decided_at DATETIME NULL,
            INDEX idx_status_created (status, created_at),
            INDEX idx_animator_status (animator_user_id, status),
            INDEX idx_animator_date_pending (animator_user_id, request_date, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    time_off_requests_migrate_add_request_date_column($link);

    $check = $link->query("SHOW COLUMNS FROM animator_notifications LIKE 'notification_kind'");
    if ($check && $check->num_rows === 0) {
        $link->query(
            'ALTER TABLE animator_notifications ADD COLUMN notification_kind VARCHAR(32) NULL DEFAULT NULL'
        );
    }
}

function time_off_requests_migrate_add_request_date_column(mysqli $link): void
{
    $check = mysqli_query($link, "SHOW COLUMNS FROM animator_time_off_requests LIKE 'request_date'");
    $hasCol = $check && mysqli_num_rows($check) > 0;
    if (!$hasCol) {
        mysqli_query(
            $link,
            'ALTER TABLE animator_time_off_requests ADD COLUMN request_date DATE NULL AFTER animator_user_id'
        );

        $res = mysqli_query($link, 'SELECT id, dates_json FROM animator_time_off_requests WHERE request_date IS NULL');
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $parsed = time_off_parse_dates_json($link, (string) ($row['dates_json'] ?? ''));
                $first = ($parsed !== null && $parsed !== []) ? $parsed[0] : null;
                if ($first !== null) {
                    $esc = mysqli_real_escape_string($link, $first);
                    mysqli_query(
                        $link,
                        'UPDATE animator_time_off_requests SET request_date = \'' . $esc . '\' WHERE id = ' . (int) ($row['id'] ?? 0)
                    );
                }
            }
        }
    }

    $idx = mysqli_query($link, "SHOW INDEX FROM animator_time_off_requests WHERE Key_name = 'idx_animator_date_pending'");
    if ($idx && mysqli_num_rows($idx) === 0) {
        mysqli_query(
            $link,
            'ALTER TABLE animator_time_off_requests ADD INDEX idx_animator_date_pending (animator_user_id, request_date, status)'
        );
    }
}

function time_off_resolve_animator_team_member(mysqli $link, int $userId): ?array
{
    require_once __DIR__ . '/staff_schedule.php';

    $role = staff_schedule_animator_role();

    $stmt = $link->prepare(
        'SELECT tm.id, tm.name FROM team_members tm
         INNER JOIN users u ON u.email = tm.email AND u.id = ?
         WHERE TRIM(tm.role) = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('is', $userId, $role);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? ['id' => (int) $row['id'], 'name' => (string) $row['name']] : null;
}

/** @param list<string> $dates YYYY-MM-DD, unique ascending */
function time_off_format_dates_ru(array $dates): string
{
    $parts = [];
    foreach ($dates as $d) {
        $ts = strtotime($d);
        $parts[] = $ts !== false ? date('d.m.Y', $ts) : $d;
    }
    return implode(', ', $parts);
}

/** Список дат по строке (предпочтительно колонка request_date). @return list<string> */
function time_off_row_effective_dates(mysqli $link, array $row): array
{
    $rd = isset($row['request_date']) ? trim((string) $row['request_date']) : '';
    if ($rd !== '' && $rd !== '0000-00-00' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rd)) {
        return [$rd];
    }
    $parsed = time_off_parse_dates_json($link, (string) ($row['dates_json'] ?? ''));

    return $parsed ?? [];
}

/** Подпись дат одной строки заявки. */
function time_off_dates_label_from_row(mysqli $link, array $row): string
{
    $dates = time_off_row_effective_dates($link, $row);
    if ($dates === []) {
        return '';
    }

    return time_off_format_dates_ru($dates);
}

/** Декодирует JSON дат или null при ошибке. @return ?list<string> */
function time_off_parse_dates_json(mysqli $link, string $json): ?array
{
    unset($link);
    $arr = json_decode($json, true);
    if (!is_array($arr) || $arr === []) {
        return null;
    }
    $out = [];
    foreach ($arr as $v) {
        if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return null;
        }
        $out[] = $v;
    }
    $out = array_values(array_unique($out));
    sort($out);

    return $out;
}

/** @param list<string> $newDates */
function time_off_pending_dates_overlap(int $animatorUserId, array $newDates, mysqli $link): bool
{
    if ($newDates === []) {
        return false;
    }

    $setNew = array_fill_keys($newDates, true);

    $stmt = $link->prepare(
        'SELECT request_date, dates_json FROM animator_time_off_requests
         WHERE animator_user_id = ? AND status = \'pending\''
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $animatorUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rd = isset($row['request_date']) ? trim((string) $row['request_date']) : '';
        if ($rd !== '' && $rd !== '0000-00-00' && isset($setNew[$rd])) {
            $stmt->close();
            return true;
        }
        $existing = time_off_parse_dates_json($link, (string) ($row['dates_json'] ?? ''));
        if ($existing === null) {
            continue;
        }
        foreach ($existing as $d) {
            if (isset($setNew[$d])) {
                $stmt->close();
                return true;
            }
        }
    }
    $stmt->close();

    return false;
}

/** @param list<string> $dates */
function time_off_dates_have_confirmed_booking(mysqli $link, int $teamMemberId, array $dates): bool
{
    if ($dates === []) {
        return false;
    }
    $stmt = $link->prepare(
        'SELECT 1 FROM booked_animators ba
         INNER JOIN bookings b ON b.id = ba.booking_id
         WHERE ba.team_member_id = ?
           AND DATE(b.event_date) = ?
           AND b.status = \'confirmed\'
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    foreach ($dates as $d) {
        $stmt->bind_param('is', $teamMemberId, $d);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            return true;
        }
    }
    $stmt->close();

    return false;
}

/** Помечает указанные даты как выходные (works=0) в утверждённом периоде графика. */
function time_off_apply_approved_dates_to_schedule(mysqli $link, int $teamMemberId, array $dates): void
{
    require_once __DIR__ . '/staff_schedule.php';

    staff_schedule_ensure_tables($link);
    $upd = $link->prepare(
        'INSERT INTO staff_schedule_days (team_member_id, work_date, works)
         VALUES (?, ?, 0)
         ON DUPLICATE KEY UPDATE works = 0'
    );
    if (!$upd) {
        return;
    }
    foreach ($dates as $ds) {
        $upd->bind_param('is', $teamMemberId, $ds);
        $upd->execute();
    }
    $upd->close();
}

/** Вернёт день как рабочий в графике (например, после отмены одобрения отгула). */
function time_off_restore_schedule_to_working(mysqli $link, int $teamMemberId, string $dateYmd): void
{
    require_once __DIR__ . '/staff_schedule.php';

    staff_schedule_ensure_tables($link);
    $stmt = $link->prepare(
        'UPDATE staff_schedule_days SET works = 1 WHERE team_member_id = ? AND work_date = ?'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('is', $teamMemberId, $dateYmd);
    $stmt->execute();
    $stmt->close();
}

function time_off_insert_animator_notification(
    mysqli $link,
    int $animatorUserId,
    string $messageEscaped,
    string $notificationKind
): void
{
    $kindEsc = $link->real_escape_string($notificationKind);
    mysqli_query(
        $link,
        "INSERT INTO animator_notifications (animator_user_id, message, event_date, event_location, notification_kind)
         VALUES ($animatorUserId, '$messageEscaped', NULL, NULL, '$kindEsc')"
    );
}
