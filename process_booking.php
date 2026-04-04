<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

function validateDate($date)
{
    $inputDate = new DateTime($date);
    $inputDate->setTime(0, 0, 0);

    // Завтрашний день
    $tomorrow = new DateTime('tomorrow');
    $tomorrow->setTime(0, 0, 0);

    // Ровно через год от сегодняшнего дня
    $maxDate = new DateTime('+1 year');
    $maxDate->setTime(0, 0, 0);

    // Проверяем, что дата входит в диапазон [завтра; через год]
    return $inputDate >= $tomorrow && $inputDate <= $maxDate;
}

$errors = [];

// 1. Получаем данные
$user_id = $_SESSION['client_id']; // ВАЖНО: добавили получение ID
$program_name = trim($_POST['program'] ?? '');
$child_name = trim($_POST['celebrant'] ?? '');
$child_age = intval($_POST['age'] ?? 0);
$event_date = trim($_POST['event_date'] ?? '');
$event_location = trim($_POST['location'] ?? '');
$guest_count = intval($_POST['guests'] ?? 0);
$wishes = trim($_POST['wishes'] ?? '');

// 2. Валидация (оставляем вашу логику)
if (empty($program_name))
    $errors['program'] = 'Выберите программу';
if ($child_age <= 0 || $child_age > 25)
    $errors['age'] = 'Некорректный возраст';
if (empty($event_date) || !validateDate($event_date)) {
    $errors['event_date'] = 'Выберите дату со следующего дня и не позднее чем через год';
}
if ($guest_count < 1 || $guest_count > 200)
    $errors['guests'] = 'Некорректное кол-во гостей';
if (mb_strlen($child_name) < 2)
    $errors['celebrant'] = 'Минимум 2 символа';
if (mb_strlen($event_location) < 4)
    $errors['location'] = 'Минимум 4 символа';

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

// 3. Экранирование ВСЕХ строк для БД
$program_name_esc = mysqli_real_escape_string($link, $program_name);
$child_name_esc = mysqli_real_escape_string($link, $child_name);
$event_date_esc = mysqli_real_escape_string($link, $event_date);
$event_location_esc = mysqli_real_escape_string($link, $event_location);
$wishes_esc = mysqli_real_escape_string($link, $wishes);

// 4. Поиск программы
$program_sql = "SELECT id, animator_count FROM programs WHERE name = '$program_name_esc'";
$program_result = mysqli_query($link, $program_sql);

if (mysqli_num_rows($program_result) === 0) {
    $_SESSION['booking_errors'] = ['program' => 'Программа не найдена'];
    header('Location: booking.php');
    exit();
}

$program = mysqli_fetch_assoc($program_result);
$program_id = intval($program['id']);
$animator_count = intval($program['animator_count']);

// 5. Поиск свободных аниматоров (ваша логика верна)
$animators_query = "SELECT team_member_id FROM animator_programs WHERE program_id = $program_id";
$animators_result = mysqli_query($link, $animators_query);

$booked_animators_query = "
    SELECT DISTINCT ba.team_member_id FROM booked_animators ba
    JOIN bookings b ON ba.booking_id = b.id
    WHERE b.event_date = '$event_date_esc' AND b.status != 'canceled'";
$booked_result = mysqli_query($link, $booked_animators_query);

$booked_ids = [];
while ($row = mysqli_fetch_assoc($booked_result))
    $booked_ids[] = $row['team_member_id'];

$capable_ids = [];
while ($row = mysqli_fetch_assoc($animators_result))
    $capable_ids[] = $row['team_member_id'];

$free_animators = array_values(array_diff($capable_ids, $booked_ids));

if (count($free_animators) < $animator_count) {
    $_SESSION['booking_errors'] = ['animators' => 'Нет свободных аниматоров на эту дату'];
    header('Location: booking.php');
    exit();
}

// 6. ЗАПИСЬ В БАЗУ
mysqli_begin_transaction($link);
try {
    $insert_query = "
        INSERT INTO bookings (user_id, program_id, child_name, child_age, event_date, event_location, guest_count, wishes, status)
        VALUES ($user_id, $program_id, '$child_name_esc', $child_age, '$event_date_esc', '$event_location_esc', $guest_count, '$wishes_esc', 'pending')";

    if (!mysqli_query($link, $insert_query)) {
        throw new Exception('Ошибка БД: ' . mysqli_error($link));
    }

    $booking_id = mysqli_insert_id($link);

    for ($i = 0; $i < $animator_count; $i++) {
        $anim_id = $free_animators[$i];
        $ins_anim = "INSERT INTO booked_animators (booking_id, team_member_id) VALUES ($booking_id, $anim_id)";
        mysqli_query($link, $ins_anim);
    }

    mysqli_commit($link);
    $_SESSION['booking_success'] = 'Успешно!';
    header('Location: profile.php');
    exit();

} catch (Exception $e) {
    mysqli_rollback($link);
    $_SESSION['booking_errors'] = ['database' => $e->getMessage()];
    header('Location: booking.php');
    exit();
}
?>