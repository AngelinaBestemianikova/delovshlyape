<?php
session_start();
include __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

// Если это администратор — отправляем в админку
if (isset($_SESSION['is_admin']) && (int) $_SESSION['is_admin'] === 1) {
    header('Location: admin.php');
    exit;
}
if (isset($_SESSION['is_animator']) && (int) $_SESSION['is_animator'] === 1) {
    header('Location: animator.php');
    exit;
}

// Get user information from database
$client_id = $_SESSION['client_id'];
$query = "SELECT first_name, last_name, email, phone FROM users WHERE id = '$client_id'";
$result = mysqli_query($link, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($link));
}

$user = mysqli_fetch_assoc($result);

if (!$user) {
    // If no user found, destroy session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['name'] = $user['first_name'];
$_SESSION['lastname'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['phone'] = $user['phone'];

// Получаем аннулированные бронирования для уведомлений
// Добавляем условие: дата события должна быть больше или равна сегодняшней
$notifications_query = "
    SELECT b.*, p.name as program_name, b.status as booking_status, p.is_archived as p_archived
    FROM bookings b
    JOIN programs p ON b.program_id = p.id
    WHERE b.user_id = $client_id 
      AND (b.status = 'canceled' OR b.status = 'archived')
      AND b.event_date >= CURDATE() 
    ORDER BY b.id DESC";

$notifications_result = mysqli_query($link, $notifications_query);
$notifications = mysqli_fetch_all($notifications_result, MYSQLI_ASSOC);

// Get user's bookings
$bookings_query = "
    SELECT b.*, p.name as program_name, 
           (SELECT COUNT(*) FROM reviews WHERE booking_id = b.id) as has_review
    FROM bookings b
    JOIN programs p ON b.program_id = p.id
    WHERE b.user_id = $client_id
    ORDER BY b.event_date DESC";
$bookings_result = mysqli_query($link, $bookings_query);

if (!$bookings_result) {
    die("Database query failed: " . mysqli_error($link));
}

$active_bookings = [];
$past_bookings = [];

while ($booking = mysqli_fetch_assoc($bookings_result)) {
    $event_date = new DateTime($booking['event_date']);
    $today = new DateTime('today'); // Берем начало текущего дня для корректного сравнения
    $tomorrow = new DateTime('tomorrow');

    $is_past_event = $event_date < $today;
    $has_review = $booking['has_review'] > 0;
    // Отмена: только если дата мероприятия позже завтра (не завтра и не сегодня)
    $can_cancel = ($booking['status'] !== 'canceled') && ($event_date > $tomorrow);

    $booking['display_date'] = $event_date->format('d.m.Y');
    $booking['is_past_event'] = $is_past_event;
    $booking['has_review'] = $has_review;
    $booking['can_cancel'] = $can_cancel;

    if ($is_past_event) {
        $past_bookings[] = $booking;
    } else {
        $active_bookings[] = $booking;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get success message if exists
$success_message = $_SESSION['booking_success'] ?? null;
// Clear the message from session after getting it
unset($_SESSION['booking_success']);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/profile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Личный кабинет</title>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <h2>Личный кабинет</h2>

            <?php if (!empty($notifications)): ?>
                <div id="user-notifications" class="notif-wrapper" style="margin-bottom: 30px;">
                    <div class="notif-dropdown"
                        style="display: block; position: static; width: 100%; border: 1px solid #eee; border-radius: 18px;">

                        <div class="notif-header"
                            style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background-color: #f9f9f9;">

                            <h4 id="user-dropdown-title" onclick="toggleNotifs(true)"
                                style="margin: 0; cursor: pointer; user-select: none;">
                                Сообщения (
                                <?= count($notifications) ?>)
                            </h4>

                            <span id="close-notif-btn" onclick="toggleNotifs(false)"
                                style="cursor: pointer; font-size: 28px; line-height: 1; color: #999; user-select: none;">&times;</span>
                        </div>

                        <div id="user-notif-content" style="display: block; border-top: 1px solid #eee;">
                            <div id="user-notif-slider-container"></div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>

            <div class="profile-wrapper">
                <div class="profile-info">
                    <div class="info-item">
                        <span class="label">Имя:</span>
                        <span class="value"><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Фамилия:</span>
                        <span class="value"><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Телефон:</span>
                        <span class="value"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="profile-actions">
                    <button class="edit-profile-btn" id="openEditModal">Редактировать профиль</button>
                    <a href="?logout=1" class="logout-button">Выйти</a>
                </div>
            </div>

            <div class="bookings-section">
                <h2 style="margin-top: 40px">Мои бронирования</h2>

                <div class="tabs">
                    <button class="tab-button active" data-tab="active-tab">Предстоящие</button>
                    <button class="tab-button" data-tab="past-tab">Прошедшие</button>
                </div>

                <div id="active-tab" class="tab-content">
                    <?php if (empty($active_bookings)): ?>
                        <p class="no-bookings">У вас нет предстоящих бронирований</p>
                    <?php else: ?>
                        <div class="bookings-slider-wrapper">
                            <?php foreach ($active_bookings as $booking): ?>
                                <div class="booking-card-item">
                                    <?php
                                    // Форматируем дату перед выводом
                                    $date_obj = new DateTime($booking['event_date']);
                                    $formatted_date = $date_obj->format('d.m.Y');

                                    include 'includes/booking_card.php';
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="past-tab" class="tab-content" style="display: none;">
                    <?php if (empty($past_bookings)): ?>
                        <p class="no-bookings">История бронирований пуста</p>
                    <?php else: ?>
                        <div class="bookings-slider-wrapper">
                            <?php foreach ($past_bookings as $booking): ?>
                                <div class="booking-card-item">
                                    <?php
                                    // Форматируем дату перед выводом
                                    $date_obj = new DateTime($booking['event_date']);
                                    $formatted_date = $date_obj->format('d.m.Y');

                                    include 'includes/booking_card.php';
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script>
        <?php if ($success_message): ?>
            window.onload = function () {
                alert('<?php echo addslashes($success_message); ?>');
            };
        <?php endif; ?>
    </script>

    <!-- Модальное окно для отзыва -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Оставить отзыв</h2>
            <form id="reviewForm">
                <input type="hidden" id="bookingId" name="booking_id">
                <input type="hidden" id="programId" name="program_id">
                <div class="form-group">
                    <label for="comment">Комментарий:</label>
                    <textarea id="comment" name="comment" rows="4" required></textarea>
                </div>
                <button type="submit" class="submit-review-btn">Отправить отзыв</button>
            </form>
        </div>
    </div>

    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close-edit">&times;</span>
            <h2>Редактировать профиль</h2>
            <form id="editProfileForm" class="form">
                <div class="box-input">
                    <input class="input" name="first_name" type="text"
                        value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    <label>Имя</label>
                    <span class="error-msg" id="err_first_name"></span>
                </div>
                <div class="box-input">
                    <input class="input" name="last_name" type="text"
                        value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    <label>Фамилия</label>
                    <span class="error-msg" id="err_last_name"></span>
                </div>
                <div class="box-input">
                    <input class="input" name="email" type="text"
                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <label>E-mail</label>
                    <span class="error-msg" id="err_email"></span>
                </div>
                <div class="box-input">
                    <input class="input" name="phone" type="text"
                        value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    <label>Телефон</label>
                    <span class="error-msg" id="err_phone"></span>
                </div>
                <button type="submit" class="submit-edit-btn">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <script>
        // Обработка отмены бронирования
        document.querySelectorAll('.cancel-booking-btn').forEach(button => {
            button.addEventListener('click', function () {
                const bookingId = this.dataset.bookingId;
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'booking_id=' + bookingId
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Произошла ошибка при отмене бронирования');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Произошла ошибка при отмене бронирования');
                    });
            });
        });

        // Обработка отзыва
        const modal = document.getElementById('reviewModal');
        const closeBtn = document.querySelector('.close');
        const reviewForm = document.getElementById('reviewForm');

        document.querySelectorAll('.leave-review-btn').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('bookingId').value = this.dataset.bookingId;
                document.getElementById('programId').value = this.dataset.programId;
                modal.style.display = 'block';
            });
        });

        closeBtn.onclick = function () {
            modal.style.display = 'none';
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        reviewForm.onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Отзыв успешно отправлен!');
                        // Находим кнопку "Оставить отзыв" для текущего бронирования
                        const bookingId = formData.get('booking_id');
                        const reviewButton = document.querySelector(`.leave-review-btn[data-booking-id="${bookingId}"]`);
                        if (reviewButton) {
                            reviewButton.textContent = 'Отзыв оставлен';
                            reviewButton.style.backgroundColor = '#ccc';
                            reviewButton.style.cursor = 'default';
                            reviewButton.disabled = true;
                        }
                        // Закрываем модальное окно
                        modal.style.display = 'none';
                    } else {
                        alert(data.message || 'Произошла ошибка при отправке отзыва');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при отправке отзыва');
                });
        }
    </script>

    <script>
        document.querySelectorAll(".tab-button").forEach((btn) => {
            btn.addEventListener("click", () => {
                // Скрываем все блоки контента
                document.querySelectorAll(".tab-content").forEach((tab) => {
                    tab.style.display = "none";
                });

                // Показываем нужный
                const targetId = btn.getAttribute("data-tab");
                document.getElementById(targetId).style.display = "block";

                // Переключаем активный класс у кнопок
                document.querySelectorAll(".tab-button").forEach((b) => {
                    b.classList.remove("active");
                });
                btn.classList.add("active");
            });
        });
    </script>

    <script> const editModal = document.getElementById('editProfileModal');
        const openEditBtn = document.getElementById('openEditModal');
        const closeEditBtn = document.querySelector('.close-edit');

        openEditBtn.onclick = () => editModal.style.display = 'block';
        closeEditBtn.onclick = () => editModal.style.display = 'none';

        document.getElementById('editProfileForm').onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Очистка старых ошибок
            document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');

            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Данные обновлены!');
                        location.reload();
                    } else if (data.errors) {
                        for (let field in data.errors) {
                            const errSpan = document.getElementById(`err_${field}`);
                            if (errSpan) errSpan.textContent = data.errors[field];
                        }
                    }
                });
        };
    </script>

    <script>
        const userNotifs = <?php echo json_encode($notifications); ?>;
        let currentUserSlide = 0;

        // Упрощенная функция переключения
        function toggleNotifs(show) {
            const content = document.getElementById('user-notif-content');
            const closeBtn = document.getElementById('close-notif-btn');

            if (show) {
                // Разворачиваем
                content.style.display = 'block';
                closeBtn.style.visibility = 'visible'; // Показываем крестик
            } else {
                // Сворачиваем
                content.style.display = 'none';
                closeBtn.style.visibility = 'hidden'; // Прячем крестик
            }
        }

        function renderUserSlider() {
            const container = document.getElementById('user-notif-slider-container');
            const fullWrapper = document.getElementById('user-notifications');

            if (!userNotifs || userNotifs.length === 0) {
                if (fullWrapper) fullWrapper.style.display = 'none';
                return;
            }

            if (!container) return;

            const n = userNotifs[currentUserSlide];
            const count = userNotifs.length;
            const d = new Date(n.event_date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            const eventDate = `${day}.${month}.${year}`;

            let statusTitle = "";
            if (n.booking_status === 'canceled' && parseInt(n.p_archived) === 1) {
                statusTitle = "Программа больше не проводится, ваша бронь была аннулирована";
            } else if (n.booking_status === 'canceled') {
                statusTitle = "Ваша бронь была аннулирована менеджером";
            } else if (n.booking_status === 'archived') {
                statusTitle = "Программа больше не проводится";
            }

            container.innerHTML = `
            <div class="slider-card" style="padding: 15px;">
                <div class="user-meta-info">
                    <b style="font-size: 16px; color: #000;">${statusTitle}</b>
                </div>
                
                <div class="msg-text-body" style="font-size: 14px; margin-top: 10px; line-height: 1.5;">
                    Программа: ${n.program_name}<br>
                    Дата события: ${eventDate}<br>
                    Адрес: ${n.event_location}
                </div>
                <span class="notif-date" style="display: block; font-size: 13px; color: #888; margin-top: 12px;">
                    В ближайшее время наш менеджер свяжется с вами для уточнения деталей
                </span>
                
                <div class="slider-actions" style="margin-top: 15px; display: flex; justify-content: center; align-items: center; gap:10px">
                        <button class="btn-del" onclick="event.stopPropagation(); deleteNotification(${n.id});" style="padding: 10px; width: 150px; cursor:pointer; display: none">
                                Удалить
                        </button>    
                <div class="nav-side">
                        ${count > 1 ? `
                            <button class="btn-next" onclick="event.stopPropagation(); nextUserSlide();" style="padding: 10px; width: 150px; cursor:pointer;">
                                Далее (${currentUserSlide + 1}/${count})
                            </button>
                        ` : '<div></div>'}
                    </div>
                </div>
            </div>
        `;
        }

        function deleteNotification(id) {
            if (!confirm('Удалить это уведомление?')) return;

            fetch('includes/delete_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            }).then(() => {
                userNotifs.splice(currentUserSlide, 1);
                if (currentUserSlide >= userNotifs.length) currentUserSlide = 0;

                const title = document.getElementById('user-dropdown-title');
                if (title) title.innerText = `Сообщения (${userNotifs.length})`;

                renderUserSlider();
            });
        }

        function nextUserSlide() {
            currentUserSlide = (currentUserSlide + 1) % userNotifs.length;
            renderUserSlider();
        }

        document.addEventListener('DOMContentLoaded', renderUserSlider);
    </script>
</body>

</html>