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
$programs_result = mysqli_query($link, "
    SELECT p.*, t.name AS type_name, t.is_archived AS type_is_archived 
    FROM programs p 
    LEFT JOIN program_types t ON p.type_id = t.id 
    ORDER BY p.id DESC
");
$program_types_result = mysqli_query($link, "SELECT * FROM program_types ORDER BY id DESC");
$programs = mysqli_fetch_all($programs_result, MYSQLI_ASSOC);
$program_types = mysqli_fetch_all($program_types_result, MYSQLI_ASSOC);

$animators_result = mysqli_query($link, "SELECT id, name FROM team_members ORDER BY name ASC");
$all_animators = mysqli_fetch_all($animators_result, MYSQLI_ASSOC);

// Запрос для отзывов
$reviews_moderation_result = mysqli_query($link, "
    SELECT r.*, u.first_name, u.last_name, p.name as program_name 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN programs p ON r.program_id = p.id
    ORDER BY r.created_time DESC
");
$all_reviews = mysqli_fetch_all($reviews_moderation_result, MYSQLI_ASSOC);

// Получаем всех сотрудников с их ролями
$team_result = mysqli_query($link, "SELECT * FROM team_members ORDER BY id DESC");
$team_members = mysqli_fetch_all($team_result, MYSQLI_ASSOC);

// Получаем связи аниматоров и программ для отображения в таблице
$animator_specs_result = mysqli_query($link, "
    SELECT ap.team_member_id, p.name 
    FROM animator_programs ap 
    JOIN programs p ON ap.program_id = p.id
");
$specs = [];
while ($row = mysqli_fetch_assoc($animator_specs_result)) {
    $specs[$row['team_member_id']][] = $row['name'];
}

// Получаем фото вместе с названиями программ
$gallery_result = mysqli_query($link, "
    SELECT ph.id, ph.path, ph.program_id, ph.created_time, p.name as program_name 
    FROM photos ph
    LEFT JOIN programs p ON ph.program_id = p.id 
    ORDER BY ph.id DESC
");
$gallery_photos = mysqli_fetch_all($gallery_result, MYSQLI_ASSOC);

$pending_bookings_count = 0;
if ($pb = mysqli_query($link, "SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'")) {
    $pending_bookings_count = (int) (mysqli_fetch_assoc($pb)['c'] ?? 0);
}
$pending_reviews_count = 0;
if ($pr = mysqli_query($link, "SELECT COUNT(*) AS c FROM reviews WHERE status = 'pending'")) {
    $pending_reviews_count = (int) (mysqli_fetch_assoc($pr)['c'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Админка</title>
    <link rel="stylesheet" href="style/general.css">
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

            <div id="admin-notifications" class="notif-wrapper">
                <div class="notif-dropdown">
                    <div class="notif-header">
                        <h4 id="dropdown-title">Сообщения (0)</h4>
                    </div>
                    <div id="notif-slider-container">
                    </div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="tabs">
                <button class="tab-button active" data-tab="bookings-tab">
                    <span class="tab-button__textWrap">
                        Новые заявки
                        <?php if ($pending_bookings_count > 0): ?>
                            <span class="nav-badge"><?= $pending_bookings_count ?></span>
                        <?php endif; ?>
                    </span>
                </button>
                <button class="tab-button" data-tab="reviews-tab">
                    <span class="tab-button__textWrap">
                        Модерация отзывов
                        <?php if ($pending_reviews_count > 0): ?>
                            <span class="nav-badge"><?= $pending_reviews_count ?></span>
                        <?php endif; ?>
                    </span>
                </button>
                <button class="tab-button" data-tab="programs-tab">Программы</button>
                <button class="tab-button" data-tab="types-tab">Типы программ</button>
                <button class="tab-button" data-tab="team-tab">Сотрудники</button>
                <button class="tab-button" data-tab="gallery-tab">Фотогалерея</button>
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
                        $event_for_lock = new DateTime($row['event_date']);
                        $today_for_lock = new DateTime('today');
                        $event_date_passed = $event_for_lock <= $today_for_lock;

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
                                <?php if ($event_date_passed): ?>
                                    <span style="color:#999;font-size:0.9em;"
                                        title="Дата мероприятия наступила или прошла">Статус нельзя изменить</span>
                                <?php elseif ($row['status'] == 'pending'): ?>
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

            <div class="tab-content" id="reviews-tab" style="display:none;">
                <h3>Модерация отзывов</h3>
                <table class="admin-table">
                    <tr>
                        <th>Дата</th>
                        <th>Клиент</th>
                        <th>Программа</th>
                        <th>Текст отзыва</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                    <?php
                    $reviews_query = "
            SELECT r.*, u.first_name, u.last_name, u.phone, p.name as p_name 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN programs p ON r.program_id = p.id
            ORDER BY r.id DESC";

                    $all_reviews = mysqli_query($link, $reviews_query);

                    while ($row = mysqli_fetch_assoc($all_reviews)):
                        $status_class = 'status-' . $row['status'];
                        $status_text = [
                            'pending' => 'На модерации',
                            'approved' => 'Подтвержден',
                            'rejected' => 'Отменен'
                        ][$row['status']];
                        ?>
                        <tr>
                            <td>
                                <?= date('d.m.Y', strtotime($row['created_time'])) ?>
                            </td>
                            <td>
                                <b>
                                    <?= htmlspecialchars($row['first_name']) ?>
                                </b>
                                <b>
                                    <?= htmlspecialchars($row['last_name']) ?>
                                </b><br>
                                <?= htmlspecialchars($row['phone']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['p_name']) ?>
                            </td>
                            <td style="max-width: 300px; font-size: 0.9em; line-height: 1.4;">
                                <?= nl2br(htmlspecialchars($row['comment'])) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <button class="btn-approve"
                                            onclick="updateReviewStatus(<?= $row['id'] ?>, 'approved')">Одобрить</button>
                                        <button class="btn-reject"
                                            onclick="updateReviewStatus(<?= $row['id'] ?>, 'rejected')">Отклонить</button>
                                    <?php else: ?>
                                        <button class="btn-edit"
                                            onclick="updateReviewStatus(<?= $row['id'] ?>, 'pending')">Изменить</button>
                                    <?php endif; ?>
                                </div>
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
                    foreach ($programs as $p):
                        $isArchived = (int) $p['is_archived'] === 1;
                        $isParentArchived = (int) $p['type_is_archived'] === 1;

                        // Строка будет серой, если либо сама программа в архиве, либо её категория
                        $shouldBeGray = $isArchived || $isParentArchived;
                        ?>
                        <tr class="<?= $shouldBeGray ? 'row-archived' : '' ?>"
                            style="<?= $shouldBeGray ? 'opacity: 0.5; filter: grayscale(1);' : '' ?>">
                            <td><?= $p['id'] ?></td>
                            <td><?= $p['type_id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= shortenText($p['description']) ?></td>
                            <td><?= shortenText($p['included_services']) ?></td>
                            <td><?= $p['duration'] ?> мин</td>
                            <td><?= $p['max_children'] ?></td>
                            <td><?= $p['price'] ?> BYN</td>
                            <td><?= $p['animator_count'] ?></td>
                            <td>
                                <?php if ($p['image_path']): ?>
                                    <img src="<?= $p['image_path'] ?>" alt=""
                                        style="width:60px; height:auto; border-radius:4px;">
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$isArchived): ?>
                                    <button class="btn-edit" onclick="editProgram(<?= $p['id'] ?>)">Редактировать</button>
                                    <button class="btn-del"
                                        onclick="archiveProgram(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                        Архивировать
                                    </button>
                                <?php else: ?>
                                    <?php if ($isParentArchived): ?>
                                        <button class="btn-approve" disabled
                                            style="opacity: 0.5; cursor: not-allowed; background-color: #ccc;"
                                            title="Сначала восстановите тип программы">
                                            Тип в архиве
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-approve"
                                            onclick="restoreProgram(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                            Восстановить
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
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
                        <th>Изображение</th>
                        <th>Меню</th>
                        <th>Действия</th>
                    </tr>
                    <?php foreach ($program_types as $t):
                        $isArchivedType = (int) $t['is_archived'] === 1;
                        ?>
                        <tr class="<?= $isArchivedType ? 'row-archived' : '' ?>"
                            style="<?= $isArchivedType ? 'opacity: 0.5; filter: grayscale(1); background-color: #f9f9f9;' : '' ?>">
                            <td><?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['name']) ?></td>
                            <td><?= shortenText($t['description']) ?></td>
                            <td>
                                <?php if ($t['path_image']): ?>
                                    <img src="<?= $t['path_image'] ?>" alt=""
                                        style="width:60px; height:auto; border-radius:4px;">
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($t['name_for_menu']) ?></td>
                            <td>
                                <?php if (!$isArchivedType): ?>
                                    <button class="btn-edit" onclick="editType(<?= $t['id'] ?>)">Редактировать</button>
                                    <button class="btn-del"
                                        onclick="archiveType(<?= $t['id'] ?>, '<?= addslashes($t['name']) ?>')">
                                        Архивировать
                                    </button>
                                <?php else: ?>
                                    <button class="btn-approve"
                                        onclick="restoreType(<?= $t['id'] ?>, '<?= addslashes($t['name']) ?>')">
                                        Восстановить
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="tab-content" id="team-tab" style="display:none;">
                <h3>Управление сотрудниками</h3>
                <button class="add-button" onclick="openTeamModal()">Добавить сотрудника</button>
                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Фото</th>
                        <th>Имя</th>
                        <th>Роль</th>
                        <th>Email</th>
                        <th>Специализация</th>
                        <th>Действия</th>
                    </tr>
                    <?php foreach ($team_members as $m): ?>
                        <tr>
                            <td>
                                <?= $m['id'] ?>
                            </td>
                            <td>
                                <img src="<?= $m['path_image'] ?: 'images/default-avatar.png' ?>"
                                    style="width:50px; height:50px; object-fit:cover; border-radius:50%;">
                            </td>
                            <td>
                                <?= htmlspecialchars($m['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($m['role']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($m['email']) ?>
                            </td>
                            <td style="font-size: 0.85em;">
                                <?= isset($specs[$m['id']]) ? implode(', ', $specs[$m['id']]) : '<span style="color:gray">Не назначены</span>' ?>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="editTeamMember(<?= $m['id'] ?>)">Редактировать</button>
                                <button class="btn-del"
                                    onclick="deleteTeamMember(<?= $m['id'] ?>, '<?= addslashes($m['name']) ?>')">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="tab-content" id="gallery-tab" style="display:none;">
                <h3>Управление фотогалереей</h3>

                <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <button class="add-button" onclick="openGalleryModal()">Добавить фото</button>

                    <div
                        style="margin-left: auto; display: flex; align-items: center; gap: 10px; background: #fff; padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px;">
                        <label for="gallery-filter">Фильтр программы:</label>
                        <select id="gallery-filter" onchange="filterGallery()"
                            style="padding: 5px; border-radius: 4px;">
                            <option value="all">Все программы</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?= $prog['id'] ?>"><?= htmlspecialchars($prog['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn-del" id="delete-all-btn" onclick="deletePhotosByProgram()"
                        style="display: none; background: #ff4d4d; padding: 10px 15px;">
                        Удалить все фото этой программы
                    </button>
                </div>

                <table class="admin-table" id="gallery-table">
                    <thead>
                        <tr>
                            <th>Фото</th>
                            <th>Программа</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gallery_photos as $photo): ?>
                            <tr class="gallery-row" data-program-id="<?= (int) ($photo['program_id'] ?? 0) ?>">
                                <td style="width: 120px;">
                                    <img src="<?= $photo['path'] ?>"
                                        style="width: 100px; height: 70px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td>
                                    <span
                                        class="prog-name"><?= htmlspecialchars($photo['program_name'] ?? 'Без программы') ?></span>
                                </td>
                                <td style="width: 180px;">
                                    <button class="btn-edit"
                                        onclick="editGalleryPhoto(<?= $photo['id'] ?>)">Редактировать</button>
                                    <button class="btn-del"
                                        onclick="deleteGalleryPhoto(<?= $photo['id'] ?>)">Удалить</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </section>

    <div id="modal-container"></div>
    <script>
        const programTypes = <?php echo json_encode($program_types); ?>;
        const allAnimators = <?php echo json_encode($all_animators); ?>; // Добавили список аниматоров

        // Логика восстановления вкладки при загрузке
        document.addEventListener("DOMContentLoaded", () => {
            const activeTab = localStorage.getItem('adminActiveTab');
            if (activeTab) {
                const btn = document.querySelector(`[data-tab="${activeTab}"]`);
                if (btn) btn.click();
            }
        });
    </script>
    <script>
        let currentSlide = 0;
        let allMessages = [];

        function escapeHtml(str) {
            if (str == null || str === undefined) return '';
            const d = document.createElement('div');
            d.textContent = String(str);
            return d.innerHTML;
        }

        async function loadMessages() {
            try {
                const res = await fetch('adminmanage/get_messages.php');
                const data = await res.json();
                if (!Array.isArray(data)) {
                    console.error('Сообщения недоступны', data);
                    allMessages = [];
                } else {
                    allMessages = data;
                }
                renderSlider();
            } catch (e) {
                console.error("Ошибка загрузки", e);
            }
        }

        function renderSlider() {
            const container = document.getElementById('notif-slider-container');
            const dropdownTitle = document.getElementById('dropdown-title');

            const count = allMessages.length;
            dropdownTitle.innerText = `Сообщения (${count})`;

            if (count === 0) {
                container.innerHTML = '<div class="empty-notif">Новых сообщений нет</div>';
                const badge = document.getElementById('notif-count');
                if (badge) badge.style.display = 'none';
                return;
            }

            if (currentSlide >= count) currentSlide = 0;
            const m = allMessages[currentSlide];

            const msgDate = new Date(m.created_at).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const email = m.email || '';
            const emailLine = email
                ? `<span class="user-email-text">${escapeHtml(email)}</span>`
                : '<span class="user-email-missing">Почта не указана</span>';

            container.innerHTML = `
    <div class="slider-card">
        <div class="user-meta-info">
           <span class="msg-date">${msgDate}</span>
            <div class="user-contact-column">
                <span class="user-name-text">${escapeHtml(m.first_name)} ${escapeHtml(m.last_name)}</span>
                <span class="user-phone-black">${escapeHtml(m.phone)}</span>
                ${emailLine}
            </div>
        </div>
        
        <div class="msg-text-body">
            ${escapeHtml(m.message).replace(/\n/g, '<br>')}
        </div>

        <div class="msg-reply-block">
            <label class="msg-reply-label" for="contact-reply-${m.id}">Ответить на почту клиенту</label>
            <textarea id="contact-reply-${m.id}" class="msg-reply-textarea" rows="4" maxlength="10000" placeholder="Текст ответа…"></textarea>
            <button type="button" class="btn-reply-send" onclick="sendContactReply(${m.id})">Отправить ответ</button>
        </div>
        
        <div class="slider-nav">
            <button class="btn-del" onclick="deleteMsg(${m.id})">Удалить</button>
            <button class="btn-next" onclick="nextSlide()">
                Далее (${currentSlide + 1}/${count})
            </button>
        </div>
    </div>
`;

            const badge = document.getElementById('notif-count');
            if (badge) {
                badge.innerText = count;
                badge.style.display = 'inline';
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % allMessages.length;
            renderSlider();
        }

        async function deleteMsg(id) {
            if (!confirm('Удалить это сообщение?')) return;
            await fetch(`adminmanage/delete_message.php?id=${id}`);
            loadMessages();
        }

        async function sendContactReply(messageId) {
            const ta = document.getElementById('contact-reply-' + messageId);
            if (!ta) return;
            const text = ta.value.trim();
            if (!text) {
                alert('Введите текст ответа');
                return;
            }
            const btn = ta.closest('.msg-reply-block')?.querySelector('.btn-reply-send');
            if (btn) btn.disabled = true;
            try {
                const res = await fetch('adminmanage/send_contact_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message_id: messageId, reply: text }),
                });
                const raw = await res.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch {
                    console.error(raw);
                    alert('Ошибка сервера при отправке');
                    return;
                }
                if (data.success) {
                    alert('Ответ отправлен на почту клиента');
                    ta.value = '';
                    loadMessages();
                } else {
                    alert(data.error || 'Не удалось отправить письмо');
                }
            } catch (e) {
                console.error(e);
                alert('Ошибка сети');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        loadMessages();
        setInterval(loadMessages, 60000);
    </script>
    <script>
        async function archiveProgram(id, name) {
            if (!confirm(`Вы уверены, что хотите архивировать программу "${name}"? Все связанные бронирования будут отменены, а пользователи уведомлены.`)) return;

            const res = await fetch(`adminmanage/archive_program.php?id=${id}`);
            if (res.ok) {
                location.reload();
            }
        }

        async function archiveType(id, name) {
            if (!confirm(`Архивировать тип "${name}"? Это также архивирует все программы внутри этого типа.`)) return;

            const res = await fetch(`adminmanage/archive_type.php?id=${id}`);
            if (res.ok) {
                location.reload();
            }
        }

        async function restoreProgram(id, name) {
            if (!confirm(`Восстановить программу "${name}"?`)) return;

            const res = await fetch(`adminmanage/restore_program.php?id=${id}`);
            if (res.ok) {
                location.reload();
            } else {
                alert("Ошибка при восстановлении");
            }
        }

        async function restoreType(id, name) {
            if (!confirm(`Восстановить тип "${name}"?`)) return;

            const res = await fetch(`adminmanage/restore_type.php?id=${id}`);
            if (res.ok) {
                location.reload();
            } else {
                alert("Ошибка при восстановлении");
            }
        }

        // Передаем данные из PHP
        const allReviews = <?php echo json_encode($all_reviews); ?>;

        // Функция смены статуса (вызывается при смене в select)
        async function updateReviewStatus(reviewId, newStatus) {
            const actionText = newStatus === 'approved' ? 'одобрить' : (newStatus === 'rejected' ? 'отклонить' : 'вернуть в модерацию');

            if (!confirm(`Вы уверены, что хотите ${actionText} этот отзыв?`)) return;

            try {
                const response = await fetch('adminmanage/update_review_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${reviewId}&status=${newStatus}`
                });

                if (response.ok) {
                    location.reload(); // Перезагружаем страницу для обновления таблицы
                } else {
                    alert('Ошибка при обновлении статуса отзыва');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Произошла ошибка связи с сервером');
            }
        }
    </script>
    <script>
        const teamData = <?php echo json_encode($team_members); ?>;
        const allPrograms = <?php echo json_encode($programs); ?>;
        const currentSpecs = <?php echo json_encode($specs); ?>;

        // ОТКРЫТИЕ МОДАЛКИ
        function openTeamModal(memberId = null) {
            // Если передан ID, ищем сотрудника в массиве teamData
            const member = memberId ? teamData.find(m => m.id == memberId) : null;

            // Список названий программ, которые уже закреплены за ним
            const activeProgramsNames = (memberId && currentSpecs[memberId]) ? currentSpecs[memberId] : [];

            // Генерируем чекбоксы программ
            let programsOptions = allPrograms.map(p => {
                const isChecked = activeProgramsNames.includes(p.name) ? 'checked' : '';
                return `
                <label style="display:block; margin-bottom:5px; color: #333; cursor:pointer;">
                    <input type="checkbox" name="programs[]" value="${p.id}" ${isChecked}> ${p.name}
                </label>
            `;
            }).join('');

            const html = `
        <div class="modal" id="teamModal" style="display:block;">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>${member ? 'Редактировать сотрудника' : 'Новый сотрудник'}</h2>
                <form id="teamForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="${member ? member.id : ''}">
                    
                    <label>Имя:</label>
                    <input type="text" name="name" value="${member ? member.name : ''}" required>
                    
                    <label>Роль:</label>
                    <input type="text" name="role" value="${member ? member.role : ''}" placeholder="Например: Ведущий" required>
                    
                    <label>Email:</label>
                    <input type="email" name="email" value="${member ? member.email : ''}" required>
                    
                    <label>Фото:</label>
                    ${member && member.path_image ? `<img src="${member.path_image}" style="width:50px; display:block; margin-bottom:5px; border-radius:4px;">` : ''}
                    <input type="file" name="image_file" accept="image/*">
                    
                    <label style="margin-top:10px; display:block; font-weight:bold;">Может вести программы:</label>
                    <div style="max-height:150px; overflow-y:auto; border:1px solid #ccc; padding:10px; margin-top:5px; background: #fff; border-radius:4px;">
                        ${programsOptions}
                    </div>
                    
                    <button type="submit" class="add-button" style="margin-top:20px; width: 100%;">Сохранить</button>
                </form>
            </div>
        </div>`;

            document.getElementById('modal-container').innerHTML = html;

            // ОТПРАВКА ФОРМЫ
            document.getElementById('teamForm').onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);

                try {
                    const res = await fetch('adminmanage/save_team_member.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await res.json();

                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (result.error || 'Не удалось сохранить'));
                    }
                } catch (error) {
                    console.error('Ошибка fetch:', error);
                    alert('Ошибка связи с сервером. Проверьте путь к save_team_member.php');
                }
            };
        }

        // ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
        function editTeamMember(id) {
            openTeamModal(id);
        }

        function closeModal() {
            const modal = document.getElementById('teamModal');
            if (modal) modal.remove();
        }

        async function deleteTeamMember(id, name) {
            if (!confirm(`Вы действительно хотите удалить сотрудника ${name}?`)) return;

            try {
                const res = await fetch(`adminmanage/delete_team_member.php?id=${id}`);
                if (res.ok) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении');
                }
            } catch (e) {
                alert('Сервер недоступен');
            }
        }
    </script>
    <script src="adminmanage/admin.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>