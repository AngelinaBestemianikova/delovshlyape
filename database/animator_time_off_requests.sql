-- Запросы аниматоров на отгулы (одна строка = один календарный день).
-- Таблица создаётся также автоматически из PHP при открытии админки/профиля аниматора;
-- этот файл — эталон для ручной установки или документации.

CREATE TABLE IF NOT EXISTS animator_time_off_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animator_user_id INT NOT NULL COMMENT 'FK на users.id',
    request_date DATE NOT NULL COMMENT 'День отгула (один на строку)',
    dates_json TEXT NOT NULL COMMENT 'Резерв: JSON массив дат ["Y-m-d"] для совместимости',
    comment TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at DATETIME NULL,
    INDEX idx_status_created (status, created_at),
    INDEX idx_animator_status (animator_user_id, status),
    INDEX idx_animator_date_pending (animator_user_id, request_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
