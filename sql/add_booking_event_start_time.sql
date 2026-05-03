-- Время начала мероприятия (шаг 30 мин, окно дня 10:00–19:00 проверяется в приложении).
-- Старые записи остаются с NULL: для расписания считаются как занятость на весь рабочий день.
ALTER TABLE bookings
    ADD COLUMN event_start_time TIME NULL DEFAULT NULL
    AFTER event_date;
