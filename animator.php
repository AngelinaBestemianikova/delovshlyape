<?php
require_once 'includes/db.php';
session_start();

// 1. Проверка авторизации и прав
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$user_res = mysqli_query($link, "SELECT first_name, last_name, is_animator FROM users WHERE id = '$client_id'");
$user_data = mysqli_fetch_assoc($user_res);

if (!$user_data || (int) $user_data['is_animator'] !== 1) {
    header("Location: profile.php");
    exit;
}

$full_name = $user_data['first_name'] . ' ' . ($user_data['last_name'] ?? '');

// 2. Логика выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 3. Получение данных (один запрос)
$query = "
    SELECT b.*, p.name as program_name, p.duration,
           u.first_name as client_name, u.phone as client_phone
    FROM bookings b
    JOIN programs p ON b.program_id = p.id
    JOIN users u ON b.user_id = u.id
    JOIN booked_animators ba ON b.id = ba.booking_id
    JOIN team_members tm ON ba.team_member_id = tm.id
    WHERE tm.email = (SELECT email FROM users WHERE id = $client_id)
    AND b.status = 'confirmed'
    ORDER BY b.event_date ASC";

$result = mysqli_query($link, $query);
$upcoming = [];
$past = [];
$today = date('Y-m-d');

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['event_date'] >= $today)
        $upcoming[] = $row;
    else
        $past[] = $row;
}

// 4. ФУНКЦИЯ ДЛЯ ОТРИСОВКИ ТАБЛИЦЫ (Убирает дублирование)
function renderBookingsTable($bookings, $emptyMessage)
{
    if (empty($bookings)) {
        // Увеличиваем colspan до 5, так как колонок стало больше
        echo "<tr><td colspan='5' class='no-bookings'>$emptyMessage</td></tr>";
        return;
    }

    foreach ($bookings as $b) {
        $date = date('d.m.Y', strtotime($b['event_date']));
        $progName = htmlspecialchars($b['program_name']);
        $child = htmlspecialchars($b['child_name']) . " ({$b['child_age']} л.)";

        // Если поле wishes пустое — ставим прочерк
        $wishes = !empty($b['wishes']) ? htmlspecialchars($b['wishes']) : "—";

        $client = htmlspecialchars($b['client_name']);
        $phone = $b['client_phone'];
        $location = htmlspecialchars($b['event_location']);

        echo "
        <tr>
            <td><strong>$date</strong><br>{$b['duration']} мин.</td>
            <td><strong>$progName</strong><br>Именинник: $child</td>
            <td>$client<br><a href='tel:$phone'>$phone</a></td>
            <td>$location</td>
            <td>$wishes</td>
        </tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Панель аниматора | <?= htmlspecialchars($full_name) ?></title>
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="adminmanage/admin.css">
    <style>
        .animator-header-info {
            margin-bottom: 20px;
        }

        .animator-name {
            font-family: "Fantazyor", sans-serif;
            display: block;
        }

        .admin-table a {
            color: #000;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <div class="animator-header-info">
                <h1 class="animator-name"><?= htmlspecialchars($full_name) ?></h1>
            </div>

            <div class="logout-container" style="margin-bottom: 25px;">
                <a href="?logout=1" class="logout-button">Выйти</a>
            </div>

            <div class="tabs">
                <button class="tab-button active" data-tab="upcoming-tab">Предстоящие</button>
                <button class="tab-button" data-tab="past-tab">Прошедшие</button>
            </div>

            <?php
            // Массив для цикличного вывода табов
            $tabs = [
                ['id' => 'upcoming-tab', 'data' => $upcoming, 'msg' => 'У вас пока нет назначенных программ', 'active' => true],
                ['id' => 'past-tab', 'data' => $past, 'msg' => 'История пуста', 'active' => false]
            ];

            foreach ($tabs as $tab):
                ?>
                <div class="tab-content" id="<?= $tab['id'] ?>" style="<?= $tab['active'] ? '' : 'display:none' ?>">
                    <h3>Мероприятия</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Дата и время</th>
                                <th>Программа / Детали</th>
                                <th>Клиент</th>
                                <th>Место проведения</th>
                                <th>Пожелания</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php renderBookingsTable($tab['data'], $tab['msg']); ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.onclick = function () {
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
                this.classList.add('active');
                document.getElementById(this.dataset.tab).style.display = 'block';
            }
        });
    </script>
</body>

</html>