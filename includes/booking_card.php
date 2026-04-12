<?php
$is_canceled = ($booking['status'] === 'canceled');
?>
<div class="booking-card <?php echo $is_canceled ? 'booking-card-canceled' : ''; ?>">
    <div class="booking-header">
        <h4><?php echo htmlspecialchars($booking['program_name']); ?></h4>
        <span class="booking-date"><?php echo $formatted_date; ?></span>
    </div>

    <div class="booking-details">
        <div class="booking-detail-item">
            <span class="label">Статус:</span>
            <span class="status-badge status-<?php echo $booking['status']; ?>">
                <?php
                echo [
                    'pending' => 'На уточнении',
                    'confirmed' => 'Бронь подтверждена',
                    'canceled' => 'Бронь отменена'
                ][$booking['status']];
                ?>
            </span>
        </div>

        <div class="booking-detail-item">
            <span class="label">Именинник:</span>
            <span class="value"><?php echo htmlspecialchars($booking['child_name']); ?>
                (<?php echo htmlspecialchars($booking['child_age']); ?> лет)</span>
        </div>
        <div class="booking-detail-item">
            <span class="label">Адрес:</span>
            <span class="value"><?php echo htmlspecialchars($booking['event_location']); ?></span>
        </div>
        <div class="booking-detail-item">
            <span class="label">Количество гостей:</span>
            <span class="value"><?php echo htmlspecialchars($booking['guest_count']); ?></span>
        </div>
        <div class="booking-detail-item">
            <span class="label">Пожелания:</span>
            <span class="value">
                <?php
                // Проверяем: если строка не пустая и не состоит из одних пробелов
                if (!empty(trim($booking['wishes']))) {
                    echo htmlspecialchars($booking['wishes']);
                } else {
                    echo '<span class="no-data">отсутствуют</span>';
                }
                ?>
            </span>
        </div>
    </div>

    <div class="booking-actions">
        <?php if (!$booking['is_past_event']): ?>
            <?php if (!$is_canceled && !empty($booking['can_cancel'])): ?>
                <button class="cancel-booking-btn" data-booking-id="<?php echo htmlspecialchars($booking['id']); ?>">
                    Отменить бронь
                </button>
            <?php elseif (!$is_canceled): ?>
                <span class="cancel-label"
                    title="Отмена возможна только если мероприятие не раньше чем послезавтра">Отмена недоступна
                    (мероприятие завтра или раньше)</span>
            <?php else: ?>
                <span class="cancel-label">Заявка аннулирована</span>
            <?php endif; ?>

        <?php elseif (!$booking['has_review'] && !$is_canceled): ?>
            <button class="leave-review-btn" data-booking-id="<?php echo htmlspecialchars($booking['id']); ?>"
                data-program-id="<?php echo $booking['program_id']; ?>">
                Оставить отзыв
            </button>
        <?php endif; ?>
    </div>
</div>