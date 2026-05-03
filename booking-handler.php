<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/staff_schedule.php';

$response = [
    'unavailable_dates' => [],
];

if (!empty($_POST['get_available_time_slots'])) {
    $response['available_time_slots'] = [];

    $program_name = trim((string) ($_POST['program'] ?? ''));
    $event_date_raw = trim((string) ($_POST['event_date'] ?? ''));

    if ($program_name !== '' && booking_event_date_allowed_for_customer($event_date_raw)) {
        $program_name_esc = mysqli_real_escape_string($link, $program_name);
        $event_date_esc = mysqli_real_escape_string($link, $event_date_raw);

        $program_sql = "SELECT p.id, p.animator_count, COALESCE(p.duration, 0) AS duration 
                       FROM programs p 
                       WHERE p.name = '$program_name_esc' AND p.is_archived = 0 LIMIT 1";
        $program_result = mysqli_query($link, $program_sql);

        if ($program_result && mysqli_num_rows($program_result) > 0) {
            $program = mysqli_fetch_assoc($program_result);
            $program_id = (int) $program['id'];
            $duration = (int) $program['duration'];
            $required_animators = (int) $program['animator_count'];
            $response['available_time_slots'] = staff_schedule_available_start_time_labels_for_booking_date(
                $link,
                $program_id,
                $event_date_esc,
                $duration,
                $required_animators
            );
        }
    }
} elseif (isset($_POST['get_unavailable_dates'])) {
    $program_name = $_POST['program'] ?? '';

    if ($program_name) {
        $program_name_esc = mysqli_real_escape_string($link, $program_name);
        $program_sql = "SELECT p.id, p.animator_count, p.max_children, COALESCE(p.duration, 0) AS duration 
                       FROM programs p 
                       WHERE p.name = '$program_name_esc' AND p.is_archived = 0 LIMIT 1";
        $program_result = mysqli_query($link, $program_sql);

        if ($program_result && mysqli_num_rows($program_result) > 0) {
            $program = mysqli_fetch_assoc($program_result);
            $program_id = (int) $program['id'];
            $required_animators = (int) $program['animator_count'];
            $response['max_children'] = (int) $program['max_children'];
            $response['duration'] = (int) $program['duration'];

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