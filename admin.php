<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    header("Location: profile.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Получаем данные из БД
$programs_result = mysqli_query($link, "SELECT p.*, t.name AS type_name FROM programs p LEFT JOIN program_types t ON p.type_id = t.id ORDER BY p.id DESC");
$program_types_result = mysqli_query($link, "SELECT * FROM program_types ORDER BY id DESC");

$programs = mysqli_fetch_all($programs_result, MYSQLI_ASSOC);
$program_types = mysqli_fetch_all($program_types_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Админка</title>
    <link rel="stylesheet" href="style/general.css">
    <!-- <link rel="stylesheet" href="style/profile.css"> -->
    <link rel="stylesheet" href="adminmanage/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <h2>Админпанель</h2>
            <div class="logout-container">
                <a href="?logout=1" class="logout-button">Выйти</a>
            </div>

            <!-- Вкладки -->
            <div class="tabs">
                <button class="tab-button active" data-tab="bookings-tab">Новые заявки</button>
                <button class="tab-button" data-tab="programs-tab">Программы</button>
                <button class="tab-button" data-tab="types-tab">Типы программ</button>
            </div>

            <div class="tab-content" id="bookings-tab">
                <h3>Управление бронированиями</h3>
                <table class="admin-table">
                    <tr>
                        <th>Поступила / Дата события</th>
                        <th>Клиент/Программа</th>
                        <th>Аниматор и Статус занятости</th>
                        <th>Детали</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                    <?php
                    $query = "
            SELECT 
                b.*, 
                p.name as p_name, 
                u.first_name, 
                u.phone,
                GROUP_CONCAT(tm.name SEPARATOR ', ') as animator_names,
                -- Проверка конфликтов: ищем другие активные брони тех же аниматоров на ту же дату
                (SELECT COUNT(*) 
                 FROM booked_animators ba2 
                 JOIN bookings b2 ON ba2.booking_id = b2.id 
                 WHERE ba2.team_member_id IN (SELECT team_member_id FROM booked_animators WHERE booking_id = b.id)
                 AND b2.event_date = b.event_date 
                 AND b2.id != b.id 
                 AND b2.status = 'confirmed') as conflicts
            FROM bookings b 
            JOIN programs p ON b.program_id = p.id 
            JOIN users u ON b.user_id = u.id 
            LEFT JOIN booked_animators ba ON b.id = ba.booking_id
            LEFT JOIN team_members tm ON ba.team_member_id = tm.id
            GROUP BY b.id
            ORDER BY b.id DESC";

                    $all_bookings = mysqli_query($link, $query);

                    while ($row = mysqli_fetch_assoc($all_bookings)):
                        $status_class = 'status-' . $row['status'];
                        $status_text = [
                            'pending' => 'На уточнении',
                            'confirmed' => 'Подтверждена',
                            'canceled' => 'Отменена'
                        ][$row['status']];
                        ?>
                        <tr>
                            <td>
                                Заявка: <?= date('d.m.Y H:i', strtotime($row['created_at'])) ?>
                                <b>Событие: <?= date('d.m.Y', strtotime($row['event_date'])) ?></b>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['first_name']) ?><br>
                                <?= htmlspecialchars($row['phone']) ?><br><br>
                                <?= htmlspecialchars($row['p_name']) ?>
                            </td>
                            <td>
                                <?php if ($row['animator_names']): ?>
                                    <?= htmlspecialchars($row['animator_names']) ?><br>
                                    <?php if ($row['conflicts'] > 0): ?>
                                        <span style="color: #ff4d4d;">Занят на эту дату!</span>
                                    <?php else: ?>
                                        <span style="color: #2ecc71;">Свободен</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">Не назначен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                Именинник: <?= htmlspecialchars($row['child_name']) ?> (<?= $row['child_age'] ?> л.)<br>
                                Адрес: <?= htmlspecialchars($row['event_location']) ?>
                            </td>
                            <td><span class="status-badge <?= $status_class ?>"><?= $status_text ?></span></td>
                            <td>
                                <?php if ($row['status'] == 'pending'): ?>
                                    <button class="btn-approve"
                                        onclick="updateBookingStatus(<?= $row['id'] ?>, 'confirmed')">Одобрить</button>
                                    <button class="btn-reject"
                                        onclick="updateBookingStatus(<?= $row['id'] ?>, 'canceled')">Отклонить</button>
                                <?php else: ?>
                                    <button class="btn-edit" onclick="updateBookingStatus(<?= $row['id'] ?>, 'pending')">
                                        Изменить</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- Программы -->
            <div class="tab-content" id="programs-tab" style="display:none;">
                <h3>Программы</h3>
                <button class="add-button" onclick="openProgramModal()">Добавить программу</button>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Тип программы</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Включённые услуги</th>
                        <th>Длительность</th>
                        <th>Макс. детей</th>
                        <th>Цена</th>
                        <th>Кол-во аниматоров</th>
                        <th>Изображение</th>
                        <th>Действия</th>
                    </tr>
                    <?php
                    function shortenText($text, $length = 50)
                    {
                        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
                    }
                    foreach ($programs as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= $p['type_id'] ?></td>
                            <td><?= $p['name'] ?></td>
                            <td><?= shortenText($p['description']) ?></td>
                            <td><?= shortenText($p['included_services']) ?></td>
                            <td><?= $p['duration'] ?> мин</td>
                            <td><?= $p['max_children'] ?></td>
                            <td><?= $p['price'] ?> BYN</td>
                            <td><?= $p['animator_count'] ?></td>
                            <td>
                                <?php if ($p['image_path']): ?>
                                    <img src="<?= $p['image_path'] ?>" alt="Изображение"
                                        style="width:100px; height:auto; border-radius:5px;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="editProgram(<?= $p['id'] ?>)">Редактировать</button>
                                <button onclick="deleteProgram(<?= $p['id'] ?>)">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Типы программ -->
            <div class="tab-content" id="types-tab" style="display:none;">
                <h3>Типы программ</h3>
                <button class="add-button" onclick="openTypeModal()">Добавить тип</button>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Путь к изображению</th>
                        <th>Название для меню</th>
                        <th>Действия</th>
                    </tr>
                    <?php foreach ($program_types as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= $t['name'] ?></td>
                            <td><?= shortenText($t['description']) ?></td>
                            <td>
                                <?php if ($t['path_image']): ?>
                                    <img src="<?= $t['path_image'] ?>" alt="Изображение"
                                        style="width:100px; height:auto; border-radius:5px;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= $t['name_for_menu'] ?></td>
                            <td>
                                <button onclick="editType(<?= $t['id'] ?>)">Редактировать</button>
                                <button onclick="deleteType(<?= $t['id'] ?>)">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </section>

    <div id="modal-container"></div>
    <script>
        const programTypes = <?php echo json_encode($program_types); ?>;
    </script>
    <script src="adminmanage/admin.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>