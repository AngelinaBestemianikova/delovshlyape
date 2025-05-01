<?php
session_start();
require_once 'includes/db.php';

$response = [
    'unavailable_dates' => []
];

// Проверка, что запрашивается информация о недоступных датах
if (isset($_POST['get_unavailable_dates'])) {
    $program_name = $_POST['program'] ?? '';

    if ($program_name) {
        // Получаем ID программы и количество требуемых аниматоров
        $program_name_esc = mysqli_real_escape_string($link, $program_name);
        $program_sql = "SELECT p.id, p.animator_count 
                       FROM programs p 
                       WHERE p.name = '$program_name_esc'";
        $program_result = mysqli_query($link, $program_sql);

        if ($program_result && mysqli_num_rows($program_result) > 0) {
            $program = mysqli_fetch_assoc($program_result);
            $program_id = intval($program['id']);
            $required_animators = intval($program['animator_count']);

            // Получаем список дат, где недостаточно свободных аниматоров
            $query = "
                SELECT b.event_date, 
                       COUNT(DISTINCT ba.team_member_id) as booked_count,
                       COUNT(DISTINCT CASE WHEN ap.program_id = $program_id THEN ba.team_member_id END) as program_booked_count
                FROM bookings b
                LEFT JOIN booked_animators ba ON b.id = ba.booking_id
                LEFT JOIN animator_programs ap ON ba.team_member_id = ap.team_member_id
                GROUP BY b.event_date
                HAVING (
                    -- Проверяем общее количество свободных аниматоров
                    (SELECT COUNT(DISTINCT id) FROM team_members) - booked_count < $required_animators
                    OR
                    -- Проверяем количество свободных аниматоров, которые могут вести эту программу
                    (SELECT COUNT(DISTINCT team_member_id) FROM animator_programs WHERE program_id = $program_id) - program_booked_count < $required_animators
                )
            ";
            $result = mysqli_query($link, $query);

            while ($row = mysqli_fetch_assoc($result)) {
                $response['unavailable_dates'][] = $row['event_date'];
            }
        }
    }
}

echo json_encode($response);
?>