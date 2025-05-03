<?php
session_start();

require_once 'includes/db.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

// Функция валидации даты
function validateDate($date)
{
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $inputDate = new DateTime($date);
    return $inputDate > $today;
}

$errors = [];

// Получаем и валидируем данные формы
$program_name = trim($_POST['program'] ?? '');
$child_name = trim($_POST['celebrant'] ?? '');
$child_age = intval($_POST['age'] ?? 0);
$event_date = trim($_POST['event_date'] ?? '');
$event_location = trim($_POST['location'] ?? '');
$guest_count = intval($_POST['guests'] ?? 0);
$wishes = trim($_POST['wishes'] ?? '');

// Валидация
if (empty($program_name)) {
    $errors['program'] = 'Выберите программу';
}
if ($child_age <= 0 || $child_age > 25) {
    $errors['age'] = 'Некорректный возраст именинника';
}
if (empty($event_date) || !validateDate($event_date)) {
    $errors['event_date'] = 'Выберите дату в будущем';
}
if ($guest_count < 1 || $guest_count > 200) {
    $errors['guests'] = 'Некорректное количество гостей';
}
if (mb_strlen($child_name) < 5) {
    $errors['celebrant'] = 'Имя именинника должно содержать минимум 5 символов';
}
if (mb_strlen($event_location) < 4) {
    $errors['location'] = 'Место проведения должно содержать минимум 4 символа';
}

// Если есть ошибки, сохраняем их в сессию и возвращаем на форму
if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

// Поиск ID программы и проверка доступности аниматоров
$program_name_esc = mysqli_real_escape_string($link, $program_name);
$program_sql = "SELECT id, animator_count 
                FROM programs 
                WHERE name = '$program_name_esc'";

$program_result = mysqli_query($link, $program_sql);

if (mysqli_num_rows($program_result) === 0) {
    $errors['program'] = 'Выбранная программа не найдена';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

$program = mysqli_fetch_assoc($program_result);
$program_id = intval($program['id']);
$animator_count = intval($program['animator_count']);

// Получаем всех аниматоров, кто может вести выбранную программу
$animators_query = "
    SELECT ap.team_member_id
    FROM animator_programs ap
    WHERE ap.program_id = $program_id
";

$animators_result = mysqli_query($link, $animators_query);

// Получить все бронирования на выбранную дату
$event_date_esc = mysqli_real_escape_string($link, $event_date);
$bookings_query = "
    SELECT COUNT(DISTINCT ba.team_member_id) as booked_count
    FROM booked_animators ba
    JOIN bookings b ON ba.booking_id = b.id
    WHERE b.event_date = '$event_date_esc'
";

$bookings_result = mysqli_query($link, $bookings_query);
$booked_count = mysqli_fetch_assoc($bookings_result)['booked_count'];

// Получаем общее количество аниматоров
$total_animators_query = "SELECT COUNT(DISTINCT id) as total FROM team_members";
$total_animators_result = mysqli_query($link, $total_animators_query);

$total_animators = mysqli_fetch_assoc($total_animators_result)['total'];
$available_animators = $total_animators - $booked_count;

// Проверка достаточности свободных аниматоров
if ($available_animators < $animator_count) {
    $errors['animators'] = 'Недостаточно свободных аниматоров на выбранную дату';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

// Получаем список всех аниматоров, которые могут вести эту программу
$animators_array = [];
while ($animator = mysqli_fetch_assoc($animators_result)) {
    $animators_array[] = $animator['team_member_id'];
}

// Получаем список занятых аниматоров на эту дату
$booked_animators_query = "
    SELECT DISTINCT ba.team_member_id
    FROM booked_animators ba
    JOIN bookings b ON ba.booking_id = b.id
    WHERE b.event_date = '$event_date_esc'
";
$booked_animators_result = mysqli_query($link, $booked_animators_query);

$booked_animators = [];
while ($booking = mysqli_fetch_assoc($booked_animators_result)) {
    $booked_animators[] = $booking['team_member_id'];
}

$free_animators = array_diff($animators_array, $booked_animators);
// Переиндексируем массив, чтобы ключи были последовательными
$free_animators = array_values($free_animators);

// Проверяем, что у нас достаточно аниматоров, которые могут вести эту программу
if (count($free_animators) < $animator_count) {
    $errors['animators'] = 'Недостаточно свободных аниматоров, которые могут вести выбранную программу';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

mysqli_begin_transaction($link);

try {
    // Сохранение бронирования
    $user_id = $_SESSION['client_id'];
    $child_name_esc = mysqli_real_escape_string($link, $child_name);
    $event_location_esc = mysqli_real_escape_string($link, $event_location);
    $wishes_esc = mysqli_real_escape_string($link, $wishes);

    $insert_query = "
        INSERT INTO bookings (user_id, program_id, child_name, child_age, event_date, event_location, guest_count, wishes)
        VALUES ($user_id, $program_id, '$child_name_esc', $child_age, '$event_date_esc', '$event_location_esc', $guest_count, '$wishes_esc')";

    if (!mysqli_query($link, $insert_query)) {
        throw new Exception('Ошибка при сохранении бронирования: ' . mysqli_error($link));
    }

    $booking_id = mysqli_insert_id($link);

    // Добавляем аниматоров в бронирование
    for ($i = 0; $i < $animator_count; $i++) {
        $animator_id = intval($free_animators[$i]);
        $insert_booked_animators_query = "
            INSERT INTO booked_animators (booking_id, team_member_id)
            VALUES ($booking_id, $animator_id)";
    }

    mysqli_commit($link);

    $_SESSION['booking_success'] = 'Ваше бронирование прошло успешно!';

    header('Location: profile.php');
    exit();

} catch (Exception $e) {
    mysqli_rollback($link);

    $errors['database'] = 'Произошла ошибка при сохранении бронирования. Пожалуйста, попробуйте позже.';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}
?>