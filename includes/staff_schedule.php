<?php

/**
 * График работы сотрудников: окно «завтра — +12 месяцев», утверждение админом,
 * пометка рабочих/выходных дней. До утверждения в этом окне аниматоры недоступны для бронирования.
 */

function staff_schedule_ensure_tables(mysqli $link): void
{
    $link->query("CREATE TABLE IF NOT EXISTS staff_schedule_meta (
        id INT NOT NULL PRIMARY KEY DEFAULT 1,
        status ENUM('draft','approved') NOT NULL DEFAULT 'draft',
        period_start DATE NULL,
        period_end DATE NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $link->query("CREATE TABLE IF NOT EXISTS staff_schedule_days (
        team_member_id INT NOT NULL,
        work_date DATE NOT NULL,
        works TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (team_member_id, work_date),
        KEY idx_work_date (work_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Завтра и конец окна (включитель): завтра + 12 календарных месяцев − 1 день. */
function staff_schedule_compute_window(): array
{
    $start = new DateTime('tomorrow');
    $end = (clone $start)->modify('+12 months')->modify('-1 day');
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

/** Роль в `team_members`, для которой строится график бронирования. */
function staff_schedule_animator_role(): string
{
    return 'Аниматор';
}

/** ID сотрудников с ролью «Аниматор». */
function staff_schedule_animator_member_ids(mysqli $link): array
{
    $role = staff_schedule_animator_role();
    $stmt = $link->prepare('SELECT id FROM team_members WHERE TRIM(role) = ? ORDER BY name ASC');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) $row['id'];
    }
    $stmt->close();
    return $ids;
}

function staff_schedule_get_meta_row(mysqli $link): ?array
{
    $res = $link->query('SELECT * FROM staff_schedule_meta WHERE id = 1 LIMIT 1');
    if (!$res || $res->num_rows === 0) {
        return null;
    }
    return $res->fetch_assoc();
}

/**
 * Приводит период в meta к текущему окну, подчищает лишние дни, добавляет строки по умолчанию (работает).
 */
function staff_schedule_sync_period_and_defaults(mysqli $link): array
{
    staff_schedule_ensure_tables($link);
    [$pStart, $pEnd] = staff_schedule_compute_window();

    $meta = staff_schedule_get_meta_row($link);
    if (!$meta) {
        $stmt = $link->prepare('INSERT INTO staff_schedule_meta (id, status, period_start, period_end) VALUES (1, \'draft\', ?, ?)');
        $stmt->bind_param('ss', $pStart, $pEnd);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $link->prepare('UPDATE staff_schedule_meta SET period_start = ?, period_end = ? WHERE id = 1');
        $stmt->bind_param('ss', $pStart, $pEnd);
        $stmt->execute();
        $stmt->close();
    }

    $pStartEsc = $link->real_escape_string($pStart);
    $pEndEsc = $link->real_escape_string($pEnd);
    $link->query("DELETE FROM staff_schedule_days WHERE work_date < '$pStartEsc' OR work_date > '$pEndEsc'");

    $ids = staff_schedule_animator_member_ids($link);
    if ($ids !== []) {
        $inList = implode(',', array_map('intval', $ids));
        $link->query("DELETE FROM staff_schedule_days WHERE team_member_id NOT IN ($inList)");
    } else {
        $link->query('DELETE FROM staff_schedule_days');
    }

    $ins = $link->prepare('INSERT IGNORE INTO staff_schedule_days (team_member_id, work_date, works) VALUES (?, ?, 1)');
    foreach ($ids as $mid) {
        $d = new DateTime($pStart);
        $dEnd = new DateTime($pEnd);
        while ($d <= $dEnd) {
            $ds = $d->format('Y-m-d');
            $ins->bind_param('is', $mid, $ds);
            $ins->execute();
            $d->modify('+1 day');
        }
    }
    $ins->close();

    return [
        'period_start' => $pStart,
        'period_end' => $pEnd,
        'status' => staff_schedule_get_meta_row($link)['status'] ?? 'draft',
    ];
}

function staff_schedule_date_in_window(string $dateYmd, string $winStart, string $winEnd): bool
{
    return $dateYmd >= $winStart && $dateYmd <= $winEnd;
}

/**
 * Доступен ли аниматор на дату по утверждённому графику (внутри окна планирования).
 * Вне окна — true. В окне при черновике графика — false. В окне при утверждении — только works = 1.
 */
function staff_schedule_animator_available_per_graph(mysqli $link, int $teamMemberId, string $eventDateYmd, ?array $meta = null): bool
{
    if ($meta === null) {
        $meta = staff_schedule_get_meta_row($link);
    }
    if (!$meta || empty($meta['period_start']) || empty($meta['period_end'])) {
        return true;
    }
    $winStart = $meta['period_start'];
    $winEnd = $meta['period_end'];
    if (!staff_schedule_date_in_window($eventDateYmd, $winStart, $winEnd)) {
        return true;
    }
    if (($meta['status'] ?? 'draft') !== 'approved') {
        return true;
    }
    $stmt = $link->prepare('SELECT works FROM staff_schedule_days WHERE team_member_id = ? AND work_date = ? LIMIT 1');
    $stmt->bind_param('is', $teamMemberId, $eventDateYmd);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return false;
    }

    return (int) $row['works'] === 1;
}

/**
 * Подтверждённые брони на будущие даты: если назначенный аниматор в графике не работает в день мероприятия (works = 0),
 * бронь переводится в «На уточнении» (status = pending).
 *
 * @return int число обновлённых броней
 */
function staff_schedule_mark_confirmed_conflicts_pending(mysqli $link, string $periodStart, string $periodEnd): int
{
    // DATE(b.event_date): при типе DATETIME иначе sd.work_date (DATE) не совпадает с полной меткой времени
    $sql = 'UPDATE bookings b
        SET b.status = \'pending\'
        WHERE b.status = \'confirmed\'
          AND DATE(b.event_date) >= CURDATE()
          AND DATE(b.event_date) >= ?
          AND DATE(b.event_date) <= ?
          AND EXISTS (
              SELECT 1
              FROM booked_animators ba
              INNER JOIN staff_schedule_days sd
                  ON sd.team_member_id = ba.team_member_id
                  AND sd.work_date = DATE(b.event_date)
              WHERE ba.booking_id = b.id
                AND sd.works = 0
          )';
    $stmt = $link->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('staff_schedule_mark_confirmed_conflicts_pending: prepare failed');
    }
    $stmt->bind_param('ss', $periodStart, $periodEnd);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('staff_schedule_mark_confirmed_conflicts_pending: execute failed');
    }
    $n = (int) $stmt->affected_rows;
    $stmt->close();

    return $n;
}

/**
 * Нормализует дату мероприятия до Y-m-d.
 */
function staff_schedule_booking_event_date_ymd(string $eventDateFromDb): string
{
    if (strlen($eventDateFromDb) >= 10) {
        return substr($eventDateFromDb, 0, 10);
    }

    return date('Y-m-d', strtotime($eventDateFromDb));
}

/**
 * По ячейке графика (только внутри окна планирования): works = 1 — рабочий день. Вне окна — true.
 */
function staff_schedule_animator_day_cell_is_working(mysqli $link, int $teamMemberId, string $dateYmd): bool
{
    $meta = staff_schedule_get_meta_row($link);
    if (!$meta || empty($meta['period_start']) || empty($meta['period_end'])) {
        return true;
    }
    $ps = $meta['period_start'];
    $pe = $meta['period_end'];
    if ($dateYmd < $ps || $dateYmd > $pe) {
        return true;
    }
    $stmt = $link->prepare('SELECT works FROM staff_schedule_days WHERE team_member_id = ? AND work_date = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $teamMemberId, $dateYmd);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        return false;
    }

    return (int) $row['works'] === 1;
}

/**
 * Есть ли у сотрудника другая (не эта) бронь на ту же календарную дату (не отменена / не в архиве).
 */
function staff_schedule_animator_has_other_booking_same_day(
    mysqli $link,
    int $excludeBookingId,
    int $teamMemberId,
    string $dateYmd
): bool {
    $stmt = $link->prepare('
        SELECT 1
        FROM booked_animators ba
        INNER JOIN bookings b ON b.id = ba.booking_id
        WHERE ba.team_member_id = ?
          AND DATE(b.event_date) = ?
          AND b.id != ?
          AND b.status NOT IN (\'canceled\', \'archived\')
        LIMIT 1
    ');
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param('isi', $teamMemberId, $dateYmd, $excludeBookingId);
    $stmt->execute();
    $res = $stmt->get_result();
    $busy = $res && $res->fetch_row() !== null;
    $stmt->close();

    return $busy;
}

/**
 * Проверка перед переводом заявки в «Подтверждена».
 *
 * @return string|null текст ошибки или null, если можно подтверждать
 */
function staff_schedule_validate_booking_can_confirm(mysqli $link, int $bookingId): ?string
{
    staff_schedule_sync_period_and_defaults($link);

    $stmt = $link->prepare('SELECT id, program_id, event_date FROM bookings WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 'Внутренняя ошибка при проверке брони.';
    }
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$booking) {
        return 'Бронь не найдена.';
    }

    $dateYmd = staff_schedule_booking_event_date_ymd((string) $booking['event_date']);
    $programId = (int) $booking['program_id'];

    $ba = $link->prepare('SELECT team_member_id FROM booked_animators WHERE booking_id = ? ORDER BY team_member_id ASC');
    if (!$ba) {
        return 'Внутренняя ошибка при проверке назначений.';
    }
    $ba->bind_param('i', $bookingId);
    $ba->execute();
    $br = $ba->get_result();
    $mids = [];
    if ($br) {
        while ($row = $br->fetch_assoc()) {
            $mids[] = (int) $row['team_member_id'];
        }
    }
    $ba->close();

    if ($mids === []) {
        return 'Подтверждение невозможно: не назначен ни один аниматор.';
    }

    $capable = staff_schedule_capable_member_ids($link, $programId);
    foreach ($mids as $mid) {
        if (!in_array($mid, $capable, true)) {
            return 'Подтверждение невозможно: выбранный сотрудник не закреплён за этой программой.';
        }
        if (!staff_schedule_animator_day_cell_is_working($link, $mid, $dateYmd)) {
            return 'Подтверждение невозможно: в графике на дату мероприятия у сотрудника отмечен выходной.';
        }
        if (staff_schedule_animator_has_other_booking_same_day($link, $bookingId, $mid, $dateYmd)) {
            return 'Подтверждение невозможно: сотрудник уже занят другой бронью на эту дату.';
        }
    }

    return null;
}

/**
 * Кандидаты на назначение в заявку: умеют программу, не заняты в других неотменённых бронях на эту дату
 * (текущая заявка $excludeBookingId не учитывается), по графику доступны в этот день.
 *
 * @return list of ['id' => int, 'name' => string]
 */
function staff_schedule_available_animators_for_booking_edit(
    mysqli $link,
    int $programId,
    string $eventDateYmd,
    int $excludeBookingId,
    ?array $meta = null
): array {
    staff_schedule_sync_period_and_defaults($link);
    if ($meta === null) {
        $meta = staff_schedule_get_meta_row($link);
    }

    $capable = staff_schedule_capable_member_ids($link, $programId);
    sort($capable);
    if ($capable === []) {
        return [];
    }

    $stmt = $link->prepare('
        SELECT DISTINCT ba.team_member_id
        FROM booked_animators ba
        INNER JOIN bookings b ON b.id = ba.booking_id
        WHERE DATE(b.event_date) = ?
          AND b.status != \'canceled\'
          AND b.id != ?
    ');
    $stmt->bind_param('si', $eventDateYmd, $excludeBookingId);
    $stmt->execute();
    $res = $stmt->get_result();
    $booked = [];
    while ($row = $res->fetch_assoc()) {
        $booked[] = (int) $row['team_member_id'];
    }
    $stmt->close();

    $pool = [];
    foreach ($capable as $mid) {
        if (in_array($mid, $booked, true)) {
            continue;
        }
        if (!staff_schedule_animator_available_per_graph($link, $mid, $eventDateYmd, $meta)) {
            continue;
        }
        $pool[] = $mid;
    }

    if ($pool === []) {
        return [];
    }

    $inList = implode(',', array_map('intval', $pool));
    $out = [];
    $nr = $link->query("SELECT id, name FROM team_members WHERE id IN ($inList) ORDER BY name ASC");
    if ($nr) {
        while ($row = $nr->fetch_assoc()) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
            ];
        }
    }

    return $out;
}

/** ID аниматоров, которые могут вести программу. */
function staff_schedule_capable_member_ids(mysqli $link, int $programId): array
{
    $stmt = $link->prepare('SELECT DISTINCT team_member_id FROM animator_programs WHERE program_id = ?');
    $stmt->bind_param('i', $programId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) $row['team_member_id'];
    }
    $stmt->close();
    return $ids;
}

/**
 * Карта дата => список занятых на мероприятиях team_member_id (не отменённые брони).
 */
function staff_schedule_booked_animators_by_date(mysqli $link, string $dateFrom, string $dateTo): array
{
    $map = [];
    $stmt = $link->prepare("
        SELECT b.event_date, ba.team_member_id
        FROM bookings b
        INNER JOIN booked_animators ba ON ba.booking_id = b.id
        WHERE b.status != 'canceled'
          AND b.event_date >= ?
          AND b.event_date <= ?
    ");
    $stmt->bind_param('ss', $dateFrom, $dateTo);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $d = $row['event_date'];
        $id = (int) $row['team_member_id'];
        if (!isset($map[$d])) {
            $map[$d] = [];
        }
        $map[$d][$id] = true;
    }
    $stmt->close();
    foreach ($map as $d => $_) {
        $map[$d] = array_keys($map[$d]);
    }
    return $map;
}

/**
 * Карта дата => список сотрудников с works=1 в окне (только указанные member ids).
 */
function staff_schedule_working_members_by_date(mysqli $link, array $memberIds, string $dateFrom, string $dateTo): array
{
    if ($memberIds === []) {
        return [];
    }
    $map = [];
    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $types = str_repeat('i', count($memberIds)) . 'ss';
    $params = $memberIds;
    $params[] = $dateFrom;
    $params[] = $dateTo;

    $sql = "
        SELECT work_date, team_member_id
        FROM staff_schedule_days
        WHERE works = 1
          AND team_member_id IN ($placeholders)
          AND work_date >= ?
          AND work_date <= ?
    ";
    $stmt = $link->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $d = $row['work_date'];
        $mid = (int) $row['team_member_id'];
        if (!isset($map[$d])) {
            $map[$d] = [];
        }
        $map[$d][] = $mid;
    }
    $stmt->close();
    return $map;
}

/**
 * Свободные для назначения аниматоры (порядок стабильный по id).
 */
function staff_schedule_get_free_animator_ids(
    mysqli $link,
    int $programId,
    string $eventDateYmd,
    ?array $capableIds = null,
    ?array $bookedByDate = null,
    ?array $workingByDate = null,
    ?string $winStart = null,
    ?string $winEnd = null,
    ?bool $approved = null
): array {
    if ($capableIds === null) {
        $capableIds = staff_schedule_capable_member_ids($link, $programId);
    }
    sort($capableIds);
    if ($capableIds === []) {
        return [];
    }

    if ($winStart === null || $winEnd === null || $approved === null) {
        staff_schedule_sync_period_and_defaults($link);
        $meta = staff_schedule_get_meta_row($link);
        $winStart = $meta['period_start'];
        $winEnd = $meta['period_end'];
        $approved = ($meta['status'] ?? 'draft') === 'approved';
    }

    $inWin = staff_schedule_date_in_window($eventDateYmd, $winStart, $winEnd);

    if ($inWin && !$approved) {
        return [];
    }

    if ($inWin && $approved) {
        if ($workingByDate === null) {
            $workingByDate = staff_schedule_working_members_by_date($link, $capableIds, $winStart, $winEnd);
        }
        $workingSet = [];
        foreach ($capableIds as $mid) {
            $workingSet[$mid] = false;
        }
        if (!empty($workingByDate[$eventDateYmd])) {
            foreach ($workingByDate[$eventDateYmd] as $mid) {
                if (array_key_exists($mid, $workingSet)) {
                    $workingSet[$mid] = true;
                }
            }
        }
        $pool = [];
        foreach ($capableIds as $mid) {
            if (!empty($workingSet[$mid])) {
                $pool[] = $mid;
            }
        }
    } else {
        $pool = $capableIds;
    }

    if ($bookedByDate === null) {
        $bookedByDate = staff_schedule_booked_animators_by_date($link, $eventDateYmd, $eventDateYmd);
    }
    $booked = $bookedByDate[$eventDateYmd] ?? [];

    return array_values(array_diff($pool, $booked));
}

/**
 * Даты в диапазоне [dateFrom; dateTo], когда нельзя набрать нужное число свободных аниматоров по программе.
 */
function staff_schedule_unavailable_dates_for_program(
    mysqli $link,
    int $programId,
    int $requiredAnimators,
    string $dateFrom,
    string $dateTo
): array {
    staff_schedule_sync_period_and_defaults($link);
    $meta = staff_schedule_get_meta_row($link);
    if (!$meta) {
        return [];
    }
    $winStart = $meta['period_start'];
    $winEnd = $meta['period_end'];
    $approved = ($meta['status'] ?? 'draft') === 'approved';

    $capable = staff_schedule_capable_member_ids($link, $programId);
    if ($capable === [] || $requiredAnimators < 1) {
        $out = [];
        $d = new DateTime($dateFrom);
        $de = new DateTime($dateTo);
        while ($d <= $de) {
            $out[] = $d->format('Y-m-d');
            $d->modify('+1 day');
        }
        return $out;
    }

    $bookedBy = staff_schedule_booked_animators_by_date($link, $dateFrom, $dateTo);
    $workingBy = $approved
        ? staff_schedule_working_members_by_date($link, $capable, $winStart, $winEnd)
        : null;

    $unavailable = [];
    $d = new DateTime($dateFrom);
    $de = new DateTime($dateTo);
    while ($d <= $de) {
        $ds = $d->format('Y-m-d');
        $free = staff_schedule_get_free_animator_ids(
            $link,
            $programId,
            $ds,
            $capable,
            $bookedBy,
            $workingBy,
            $winStart,
            $winEnd,
            $approved
        );
        if (count($free) < $requiredAnimators) {
            $unavailable[] = $ds;
        }
        $d->modify('+1 day');
    }

    return $unavailable;
}
