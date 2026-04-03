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
                <button class="tab-button active" data-tab="programs-tab">Программы</button>
                <button class="tab-button" data-tab="types-tab">Типы программ</button>
            </div>

            <!-- Программы -->
            <div class="tab-content" id="programs-tab">
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