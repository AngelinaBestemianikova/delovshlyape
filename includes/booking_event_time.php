<?php

/** Начало рабочего окна для мероприятий (включительно). */
const BOOKING_DAY_START_H = 10;
/** Конец рабочего окна: мероприятие должно закончиться не позже этой отметки. */
const BOOKING_DAY_END_H = 19;
const BOOKING_SLOT_STEP_MIN = 30;

/** Минут между окончанием одного и началом другого мероприятия (один сотрудник в один день). */
const BOOKING_MIN_GAP_BETWEEN_EVENTS_MIN = 120;

function booking_event_minutes_from_time_string(string $time): int
{
    $time = trim($time);
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $m)) {
        return (int) $m[1] * 60 + (int) $m[2];
    }

    return -1;
}

/** @return int[] минуты от полуночи для допустимых начал (шаг 30 мин). */
function booking_event_allowed_start_minutes(int $durationMinutes): array
{
    if ($durationMinutes < 1) {
        return [];
    }
    $starts = [];
    $start = BOOKING_DAY_START_H * 60;
    $deadline = BOOKING_DAY_END_H * 60;

    while ($start + $durationMinutes <= $deadline) {
        $starts[] = $start;
        $start += BOOKING_SLOT_STEP_MIN;
    }

    return $starts;
}

function booking_event_format_hh_mm(int $minutesFromMidnight): string
{
    $h = intdiv($minutesFromMidnight, 60);
    $m = $minutesFromMidnight % 60;

    return sprintf('%02d:%02d', $h, $m);
}

/** @return string[] список 'HH:MM' */
function booking_event_allowed_start_labels(int $durationMinutes): array
{
    return array_map('booking_event_format_hh_mm', booking_event_allowed_start_minutes($durationMinutes));
}

/**
 * Интервал занятости [startMin, startMin + duration) в минутах от полуночи.
 *
 * @return array{start:int,duration:int}|null если не удалось разобрать
 */
function booking_event_interval_minutes(?string $eventStartTimeFromDb, int $programDurationMin): ?array
{
    if ($programDurationMin < 1) {
        return null;
    }
    if ($eventStartTimeFromDb === null || $eventStartTimeFromDb === '') {
        return null;
    }

    $startMin = booking_event_minutes_from_time_string((string) $eventStartTimeFromDb);
    if ($startMin < 0) {
        return null;
    }

    return ['start' => $startMin, 'duration' => $programDurationMin];
}

/** Пересечение полуинтервалов [a, a+da) и [b, b+db). */
function booking_event_intervals_overlap(int $aStart, int $aDur, int $bStart, int $bDur): bool
{
    if ($aDur < 1 || $bDur < 1) {
        return false;
    }

    return $aStart < $bStart + $bDur && $bStart < $aStart + $aDur;
}

/**
 * Несовместимы для одного сотрудника: интервалы пересекаются или зазор между ними строго меньше $gapMin минут.
 * Полуинтервалы [start, start+duration); стык «конец = начало» даёт зазор 0 — при gap минутах тоже конфликт.
 */
function booking_event_intervals_too_close_or_overlap(
    int $aStart,
    int $aDur,
    int $bStart,
    int $bDur,
    int $gapMin = BOOKING_MIN_GAP_BETWEEN_EVENTS_MIN
): bool {
    if ($aDur < 1 || $bDur < 1) {
        return false;
    }

    if (booking_event_intervals_overlap($aStart, $aDur, $bStart, $bDur)) {
        return true;
    }

    $aEnd = $aStart + $aDur;
    $bEnd = $bStart + $bDur;
    if ($aEnd <= $bStart) {
        return ($bStart - $aEnd) < $gapMin;
    }
    if ($bEnd <= $aStart) {
        return ($aStart - $bEnd) < $gapMin;
    }

    return true;
}

/** Бронь без времени: блокирует всё рабочее окно по слотам. */
function booking_event_legacy_full_day_interval(): array
{
    $start = BOOKING_DAY_START_H * 60;
    $end = BOOKING_DAY_END_H * 60;

    return ['start' => $start, 'duration' => $end - $start];
}

/** Дата бронирования в допустимом диапазоне: завтра — не позже чем через год. */
function booking_event_date_allowed_for_customer(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    try {
        $inputDate = new DateTime($date);
        $inputDate->setTime(0, 0, 0);

        $tomorrow = new DateTime('tomorrow');
        $tomorrow->setTime(0, 0, 0);

        $maxDate = new DateTime('+1 year');
        $maxDate->setTime(0, 0, 0);
    } catch (Exception $e) {
        return false;
    }

    return $inputDate >= $tomorrow && $inputDate <= $maxDate;
}
