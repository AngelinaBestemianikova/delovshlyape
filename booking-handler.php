<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/staff_schedule.php';

$response = [
    'unavailable_dates' => [],
];

// Проверка, что запрашивается информация о недоступных датах
if (isset($_POST['get_unavailable_dates'])) {
    $program_name = $_POST['program'] ?? '';

    if ($program_name) {
        $program_name_esc = mysqli_real_escape_string($link, $program_name);
        $program_sql = "SELECT p.id, p.animator_count, p.max_children 
                       FROM programs p 
                       WHERE p.name = '$program_name_esc'";
        $program_result = mysqli_query($link, $program_sql);

        if ($program_result && mysqli_num_rows($program_result) > 0) {
            $program = mysqli_fetch_assoc($program_result);
            $program_id = (int) $program['id'];
            $required_animators = (int) $program['animator_count'];
            $response['max_children'] = (int) $program['max_children'];

            $from = (new DateTime('tomorrow'))->format('Y-m-d');
            $to = (new DateTime('+1 year'))->format('Y-m-d');
            $response['unavailable_dates'] = staff_schedule_unavailable_dates_for_program(
                $link,
                $program_id,
                $required_animators,
                $from,
                $to
            );
        }
    }
}

echo json_encode($response);
?>