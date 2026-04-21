<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$user_id = (int) $_SESSION['client_id'];

if ($booking_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Некорректная заявка']);
    exit;
}

$eligible = mysqli_query(
    $link,
    "SELECT b.id FROM bookings b
     WHERE b.id = $booking_id AND b.user_id = $user_id
       AND DATE(b.event_date) > DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
);

if (!$eligible || mysqli_num_rows($eligible) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Отменить бронирование можно только если до даты мероприятия больше суток (не в день события и не накануне).',
    ]);
    exit;
}

$booking_data_res = mysqli_query(
    $link,
    "SELECT event_date, event_location
     FROM bookings
     WHERE id = $booking_id AND user_id = $user_id
     LIMIT 1"
);
$booking_data = $booking_data_res ? mysqli_fetch_assoc($booking_data_res) : null;

if ($booking_data) {
    $today = new DateTime('today');
    $eventDate = new DateTime($booking_data['event_date']);
    $daysUntilEvent = (int) $today->diff($eventDate)->format('%r%a');

    if ($daysUntilEvent < 30) {
        mysqli_query($link, "
            CREATE TABLE IF NOT EXISTS animator_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                animator_user_id INT NOT NULL,
                message TEXT NOT NULL,
                event_date DATE NULL,
                event_location VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                INDEX idx_animator_user_read (animator_user_id, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $eventDateText = date('d.m.Y', strtotime($booking_data['event_date']));
        $eventLocationText = mysqli_real_escape_string($link, (string) ($booking_data['event_location'] ?? ''));
        $messageText = mysqli_real_escape_string(
            $link,
            "Клиент отменил бронирование менее чем за месяц до мероприятия. Дата: {$eventDateText}. Адрес: " . ($booking_data['event_location'] ?? '')
        );

        $animators_query = "
            SELECT DISTINCT u.id AS animator_user_id
            FROM booked_animators ba
            JOIN team_members tm ON tm.id = ba.team_member_id
            JOIN users u ON u.email = tm.email
            WHERE ba.booking_id = $booking_id
              AND u.is_animator = 1
        ";
        $animators_result = mysqli_query($link, $animators_query);
        if ($animators_result) {
            while ($animator = mysqli_fetch_assoc($animators_result)) {
                $animatorUserId = (int) ($animator['animator_user_id'] ?? 0);
                if ($animatorUserId > 0) {
                    mysqli_query(
                        $link,
                        "INSERT INTO animator_notifications (animator_user_id, message, event_date, event_location)
                         VALUES ($animatorUserId, '$messageText', '{$booking_data['event_date']}', '$eventLocationText')"
                    );
                }
            }
        }
    }
}

$delete_query = "
    DELETE b, ba 
    FROM bookings b
    LEFT JOIN booked_animators ba ON b.id = ba.booking_id
    WHERE b.id = $booking_id AND b.user_id = $user_id";

if (mysqli_query($link, $delete_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении бронирования']);
} 