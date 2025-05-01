<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Включаем логирование
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Показываем путь к файлу логов
echo "Log file path: " . ini_get('error_log') . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

require_once 'includes/db.php';

// Проверяем, что форма была отправлена
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Form not submitted via POST method');
    die('Форма не была отправлена');
}

// Логируем полученные данные
error_log('POST data: ' . print_r($_POST, true));

if (!isset($_SESSION['client_id'])) {
    error_log('No client_id in session');
    header('Location: login.php');
    exit();
}

error_log('Client ID: ' . $_SESSION['client_id']);

// Функции валидации
function validateName($name)
{
    return preg_match('/^[a-zA-Zа-яА-Я]{2,}$/u', $name);
}
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function validatePhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 9 && strlen($phone) <= 15;
}
function validateDate($date)
{
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $inputDate = new DateTime($date);
    return $inputDate > $today;
}

$errors = [];

// Получаем и валидируем данные формы
$name = trim($_POST['name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$program_name = trim($_POST['program'] ?? '');
$child_name = trim($_POST['celebrant'] ?? '');
$child_age = intval($_POST['age'] ?? 0);
$event_date = trim($_POST['event_date'] ?? '');
$event_location = trim($_POST['location'] ?? '');
$guest_count = intval($_POST['guests'] ?? 0);
$wishes = trim($_POST['wishes'] ?? '');

error_log('Form data processed: ' . print_r([
    'name' => $name,
    'surname' => $surname,
    'email' => $email,
    'phone' => $phone,
    'program_name' => $program_name,
    'child_name' => $child_name,
    'child_age' => $child_age,
    'event_date' => $event_date,
    'event_location' => $event_location,
    'guest_count' => $guest_count,
    'wishes' => $wishes
], true));

// Валидация
if (!validateName($name)) {
    $errors['name'] = 'Имя должно содержать минимум 2 буквы и не содержать специальных символов';
}
if (!validateName($surname)) {
    $errors['surname'] = 'Фамилия должна содержать минимум 2 буквы и не содержать специальных символов';
}
if (!validateEmail($email)) {
    $errors['email'] = 'Введите корректный email адрес';
}
if (!validatePhone($phone)) {
    $errors['phone'] = 'Введите корректный номер телефона';
}
if (empty($program_name)) {
    $errors['program'] = 'Выберите программу';
}
if ($child_age <= 0 || $child_age > 25) {
    $errors['age'] = 'Некорректный возраст именинника';
}
if (!validateDate($event_date)) {
    $errors['event_date'] = 'Выберите дату в будущем';
}
if ($guest_count > 200) {
    $errors['guests'] = 'Некорректное количество гостей';
}

error_log('Validation errors: ' . print_r($errors, true));

// Если есть ошибки, сохраняем их в сессию и возвращаем на форму
if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    error_log('Redirecting back to booking.php with errors');
    header('Location: booking.php');
    exit();
}

// Проверяем подключение к базе данных
if (!$link) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('Ошибка подключения к базе данных');
}

// Поиск ID программы и проверка доступности аниматоров
$program_name_esc = mysqli_real_escape_string($link, $program_name);
$program_sql = "SELECT id, animator_count 
                FROM programs 
                WHERE name = '$program_name_esc'";
error_log('Program SQL query: ' . $program_sql);

$program_result = mysqli_query($link, $program_sql);

if (!$program_result) {
    error_log('Program query failed: ' . mysqli_error($link));
    die('Ошибка базы данных: ' . mysqli_error($link));
}

if (mysqli_num_rows($program_result) === 0) {
    error_log('Program not found: ' . $program_name);
    $errors['program'] = 'Выбранная программа не найдена';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

$program = mysqli_fetch_assoc($program_result);
$program_id = intval($program['id']);
$animator_count = intval($program['animator_count']);

error_log('Program found: ID=' . $program_id . ', animator_count=' . $animator_count);

// Получаем всех аниматоров, кто может вести выбранную программу
$animators_query = "
    SELECT ap.team_member_id
    FROM animator_programs ap
    WHERE ap.program_id = $program_id
";

error_log('Animators query: ' . $animators_query);

$animators_result = mysqli_query($link, $animators_query);
if (!$animators_result) {
    error_log('Animators query failed: ' . mysqli_error($link));
    die('Ошибка базы данных: ' . mysqli_error($link));
}

// Получить все бронирования на выбранную дату
$event_date_esc = mysqli_real_escape_string($link, $event_date);
$bookings_query = "
    SELECT COUNT(DISTINCT ba.team_member_id) as booked_count
    FROM booked_animators ba
    JOIN bookings b ON ba.booking_id = b.id
    WHERE b.event_date = '$event_date_esc'
";

error_log('Bookings query: ' . $bookings_query);

$bookings_result = mysqli_query($link, $bookings_query);
if (!$bookings_result) {
    error_log('Bookings query failed: ' . mysqli_error($link));
    die('Ошибка базы данных: ' . mysqli_error($link));
}

$booked_count = mysqli_fetch_assoc($bookings_result)['booked_count'];

// Получаем общее количество аниматоров
$total_animators_query = "SELECT COUNT(DISTINCT id) as total FROM team_members";
$total_animators_result = mysqli_query($link, $total_animators_query);
if (!$total_animators_result) {
    error_log('Total animators query failed: ' . mysqli_error($link));
    die('Ошибка базы данных: ' . mysqli_error($link));
}

$total_animators = mysqli_fetch_assoc($total_animators_result)['total'];
$available_animators = $total_animators - $booked_count;

error_log("Total animators: $total_animators, Booked: $booked_count, Available: $available_animators, Required: $animator_count");

// Проверка достаточности свободных аниматоров
if ($available_animators < $animator_count) {
    error_log('Not enough free animators. Required: ' . $animator_count . ', Available: ' . $available_animators);
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
if (!$booked_animators_result) {
    error_log('Booked animators query failed: ' . mysqli_error($link));
    die('Ошибка базы данных: ' . mysqli_error($link));
}

$booked_animators = [];
while ($booking = mysqli_fetch_assoc($booked_animators_result)) {
    $booked_animators[] = $booking['team_member_id'];
}

$free_animators = array_diff($animators_array, $booked_animators);
// Переиндексируем массив, чтобы ключи были последовательными
$free_animators = array_values($free_animators);
error_log('Free animators for program: ' . print_r($free_animators, true));

// Проверяем, что у нас достаточно аниматоров, которые могут вести эту программу
if (count($free_animators) < $animator_count) {
    error_log('Not enough program-specific animators. Required: ' . $animator_count . ', Available: ' . count($free_animators));
    $errors['animators'] = 'Недостаточно свободных аниматоров, которые могут вести выбранную программу';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}

// Начинаем транзакцию
mysqli_begin_transaction($link);
error_log('Transaction started');

try {
    // Сохранение бронирования
    $user_id = $_SESSION['client_id'];
    $child_name_esc = mysqli_real_escape_string($link, $child_name);
    $event_location_esc = mysqli_real_escape_string($link, $event_location);
    $wishes_esc = mysqli_real_escape_string($link, $wishes);
    
    $insert_query = "
        INSERT INTO bookings (user_id, program_id, child_name, child_age, event_date, event_location, guest_count, wishes)
        VALUES ($user_id, $program_id, '$child_name_esc', $child_age, '$event_date_esc', '$event_location_esc', $guest_count, '$wishes_esc')";

    error_log('Insert booking query: ' . $insert_query);

    if (!mysqli_query($link, $insert_query)) {
        throw new Exception('Ошибка при сохранении бронирования: ' . mysqli_error($link));
    }

    $booking_id = mysqli_insert_id($link);
    error_log('Booking created with ID: ' . $booking_id);

    // Добавляем аниматоров в бронирование
    for ($i = 0; $i < $animator_count; $i++) {
        $animator_id = intval($free_animators[$i]);
        $insert_booked_animators_query = "
            INSERT INTO booked_animators (booking_id, team_member_id)
            VALUES ($booking_id, $animator_id)";

        error_log('Insert animator query: ' . $insert_booked_animators_query);

        if (!mysqli_query($link, $insert_booked_animators_query)) {
            throw new Exception('Ошибка при сохранении аниматоров: ' . mysqli_error($link));
        }
    }

    // Подтверждаем транзакцию
    mysqli_commit($link);
    error_log('Transaction committed successfully');
    
    // Устанавливаем сообщение об успехе
    $_SESSION['booking_success'] = 'Ваше бронирование прошло успешно!';
    
    // Перенаправляем на страницу профиля
    error_log('Redirecting to profile.php');
    header('Location: profile.php');
    exit();

} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    mysqli_rollback($link);
    error_log('Transaction rolled back due to error: ' . $e->getMessage());
    
    $errors['database'] = 'Произошла ошибка при сохранении бронирования. Пожалуйста, попробуйте позже.';
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_form_data'] = $_POST;
    header('Location: booking.php');
    exit();
}
?>
