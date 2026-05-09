<?php
require_once 'includes/db.php';
require_once 'includes/time_off_requests.php';
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

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS animator_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        animator_user_id INT NOT NULL,
        message TEXT NOT NULL,
        event_date DATE NULL,
        event_location VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        INDEX idx_animator_user_read (animator_user_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$nk_chk = mysqli_query($link, "SHOW COLUMNS FROM animator_notifications LIKE 'notification_kind'");
if ($nk_chk && mysqli_num_rows($nk_chk) === 0) {
    mysqli_query($link, "ALTER TABLE animator_notifications ADD COLUMN notification_kind VARCHAR(32) NULL DEFAULT NULL");
}
time_off_requests_ensure_schema($link);

function animator_build_page_url(array $updates): string
{
    $params = $_GET;
    foreach ($updates as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    $query = http_build_query($params);
    return 'animator.php' . ($query !== '' ? '?' . $query : '');
}

$animator_timeoff_limit = 10;
$animator_timeoff_page = isset($_GET['timeoff_page']) ? (int) $_GET['timeoff_page'] : 1;
if ($animator_timeoff_page < 1) {
    $animator_timeoff_page = 1;
}
$animator_timeoff_total = 0;
if ($tcnt = mysqli_query(
    $link,
    'SELECT COUNT(*) AS c FROM animator_time_off_requests WHERE animator_user_id = ' . (int) $client_id
)) {
    $animator_timeoff_total = (int) (mysqli_fetch_assoc($tcnt)['c'] ?? 0);
}
$animator_timeoff_total_pages = max(1, (int) ceil($animator_timeoff_total / $animator_timeoff_limit));
if ($animator_timeoff_page > $animator_timeoff_total_pages) {
    $animator_timeoff_page = $animator_timeoff_total_pages;
}
$animator_timeoff_offset = ($animator_timeoff_page - 1) * $animator_timeoff_limit;

$my_time_off_rows = [];
$tof_sql = '
    SELECT id, request_date, dates_json, status, created_at, decided_at
    FROM animator_time_off_requests
    WHERE animator_user_id = ' . (int) $client_id . '
    ORDER BY created_at DESC
    LIMIT ' . (int) $animator_timeoff_limit . '
    OFFSET ' . (int) $animator_timeoff_offset . '
';
if ($tof_res = mysqli_query($link, $tof_sql)) {
    $my_time_off_rows = mysqli_fetch_all($tof_res, MYSQLI_ASSOC);
}

$animator_notifications = [];
$animator_notif_query = "
    SELECT id, message, event_date, event_location, created_at, notification_kind
    FROM animator_notifications
    WHERE animator_user_id = $client_id
      AND is_read = 0
      AND (event_date IS NULL OR event_date >= CURDATE())
    ORDER BY id DESC
";
$animator_notif_result = mysqli_query($link, $animator_notif_query);
if ($animator_notif_result) {
    $animator_notifications = mysqli_fetch_all($animator_notif_result, MYSQLI_ASSOC);
}

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
        if (!empty($b['event_start_time'])) {
            $date .= ', ' . date('H:i', strtotime((string) $b['event_start_time']));
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        .animator-schedule-error {
            text-align: center;
            margin: 0 0 12px;
            color: #a02828;
            font-size: 15px;
            font-weight: 600;
        }

        .animator-timeoff-panel {
            margin-top: 40px;
        }

        .animator-timeoff-panel h3 {
            margin-bottom: 12px;
        }

        .animator-timeoff-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 8px;
        }

        .animator-timeoff-ok {
            color: #1e7f45;
            text-align: left;
            font-weight: 600;
            margin-top: 8px;
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

            <?php if (!empty($animator_notifications)): ?>
                <div id="user-notifications" class="notif-wrapper" style="margin-bottom: 30px;">
                    <div class="notif-dropdown"
                        style="display: block; position: static; width: 100%; border: 1px solid #eee; border-radius: 18px;">

                        <div class="notif-header"
                            style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background-color: #f9f9f9;">

                            <h4 id="user-dropdown-title" onclick="toggleNotifs(true)"
                                style="margin: 0; cursor: pointer; user-select: none;">
                                Сообщения (
                                <?= count($animator_notifications) ?>)
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
                <div class="tab-content animator-booking-tab" id="<?= $tab['id'] ?>" style="<?= $tab['active'] ? '' : 'display:none' ?>">
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

            <div class="tab-content animator-timeoff-panel staff-schedule-panel timeoff-requests-panel">
                <h3>Отгулы</h3>
                <p class="timeoff-intro">
                    Выберите даты в календаре — доступны только дни, когда вы по графику работаете. Каждый день отправляется отдельной заявкой. После решения администратора придёт сообщение в блок «Сообщения» выше.
                </p>

                <div class="animator-timeoff-form-wrap">
                    <div class="form-row animator-timeoff-datetime-row">
                        <div class="field-group field-group-date">
                            <input type="text" id="animator-timeoff-date" class="animator-timeoff-fp-input" inputmode="none"
                                autocomplete="off" placeholder="Дата отгула*" aria-label="Дата отгула">
                        </div>
                    </div>
                    <div id="animator-timeoff-chips" class="animator-timeoff-chips animator-timeoff-chips-bar" aria-live="polite"></div>
                    <div class="animator-timeoff-buttons">
                        <button type="button" class="add-button animator-timeoff-add-btn" id="animator-timeoff-add">Добавить дату</button>
                        <button type="button" class="add-button animator-timeoff-send-btn" id="animator-timeoff-send">Отправить запросы</button>
                    </div>
                </div>

                <p id="animator-timeoff-feedback" class="animator-schedule-error" hidden></p>

                <?php if ($animator_timeoff_total > 0): ?>
                    <h3 style="margin-top: 28px;">Мои запросы</h3>
                    <table class="admin-table">
                        <tr>
                            <th>День отгула</th>
                            <th>Подано</th>
                            <th>Статус</th>
                        </tr>
                        <?php foreach ($my_time_off_rows as $tr):
                            $disp = time_off_dates_label_from_row($link, $tr);
                            $daysLabel = $disp !== '' ? htmlspecialchars($disp) : '—';
                            $st = $tr['status'] ?? '';
                            $statusLabel = [
                                'pending' => '<span class="status-badge status-pending">На рассмотрении</span>',
                                'approved' => '<span class="status-badge status-approved">Одобрено</span>',
                                'rejected' => '<span class="status-badge status-rejected">Отклонено</span>',
                            ][$st] ?? '<span class="status-badge">' . htmlspecialchars((string) $st) . '</span>';
                            ?>
                            <tr>
                                <td><?= $daysLabel !== '—' ? '<strong>' . $daysLabel . '</strong>' : '—' ?></td>
                                <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string) $tr['created_at']))) ?></td>
                                <td><?= $statusLabel ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php if ($animator_timeoff_total_pages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-wrapper">
                                <?php $at_prev = max(1, $animator_timeoff_page - 1); ?>
                                <button class="pag-arrow prev" <?= ($animator_timeoff_page <= 1) ? 'disabled' : '' ?>
                                    onclick="location.href='<?= htmlspecialchars(animator_build_page_url(['timeoff_page' => $at_prev])) ?>'"
                                    title="Назад">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M15 18L9 12L15 6"
                                            stroke="<?= ($animator_timeoff_page <= 1) ? 'rgb(135, 115, 255, 0.3)' : '#8773ff' ?>" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="pagination-dots">
                                    <?php for ($i = 1; $i <= $animator_timeoff_total_pages; $i++): ?>
                                        <span class="dot <?= ($i === $animator_timeoff_page) ? 'active' : '' ?>"
                                            style="background-color: <?= ($i === $animator_timeoff_page) ? '#8773ff' : 'rgb(135, 115, 255, 0.2)' ?>"
                                            onclick="location.href='<?= htmlspecialchars(animator_build_page_url(['timeoff_page' => $i])) ?>'"
                                            title="Страница <?= $i ?>"></span>
                                    <?php endfor; ?>
                                </div>
                                <?php $at_next = min($animator_timeoff_total_pages, $animator_timeoff_page + 1); ?>
                                <button class="pag-arrow next" <?= ($animator_timeoff_page >= $animator_timeoff_total_pages) ? 'disabled' : '' ?>
                                    onclick="location.href='<?= htmlspecialchars(animator_build_page_url(['timeoff_page' => $at_next])) ?>'"
                                    title="Вперед">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 6L15 12L9 18"
                                            stroke="<?= ($animator_timeoff_page >= $animator_timeoff_total_pages) ? 'rgb(135, 115, 255, 0.3)' : '#8773ff' ?>" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="tab-content staff-schedule-panel" style="margin-top: 40px;">
                <h3>График работы</h3>
                <p id="animator-schedule-error" class="animator-schedule-error" hidden></p>

                <div class="schedule-toolbar-row">
                    <label for="staff-schedule-month">Месяц: </label>
                    <select id="staff-schedule-month"></select>
                </div>
                <div class="schedule-table-scroll">
                    <table class="admin-table staff-schedule-table" id="staff-schedule-table">
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const userNotifs = <?php echo json_encode($animator_notifications); ?>;
        let currentUserSlide = 0;

        function escapeSliderHtml(str) {
            if (str == null) return '';
            const d = document.createElement('div');
            d.textContent = String(str);
            return d.innerHTML;
        }

        function toggleNotifs(show) {
            const content = document.getElementById('user-notif-content');
            const closeBtn = document.getElementById('close-notif-btn');
            if (!content || !closeBtn) return;

            if (show) {
                content.style.display = 'block';
                closeBtn.style.visibility = 'visible';
            } else {
                content.style.display = 'none';
                closeBtn.style.visibility = 'hidden';
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
            const kind = n.notification_kind || '';
            let titleHtml = '';
            let bodyHtml = '';

            if (kind === 'time_off_decision') {
                titleHtml = 'Решение по отгулу';
                bodyHtml = `<div class="msg-text-body" style="font-size: 14px; margin-top: 10px; line-height: 1.5;">${escapeSliderHtml(n.message || '').replace(/\n/g, '<br>')}</div>`;
            } else {
                const ev = n.event_date ? new Date(n.event_date + 'T12:00:00') : null;
                const eventDate = ev && !isNaN(ev.getTime())
                    ? `${String(ev.getDate()).padStart(2, '0')}.${String(ev.getMonth() + 1).padStart(2, '0')}.${ev.getFullYear()}`
                    : '—';
                titleHtml = 'Клиент отменил бронирование';
                bodyHtml = `
                    <div class="msg-text-body" style="font-size: 14px; margin-top: 10px; line-height: 1.5;">
                        Дата события: ${eventDate}<br>
                        Адрес: ${n.event_location || '—'}
                    </div>`;
            }

            container.innerHTML = `
                <div class="slider-card" style="padding: 15px;">
                    <div class="user-meta-info">
                        <b style="font-size: 16px; color: #000;">${titleHtml}</b>
                    </div>
                    
                    ${bodyHtml}
                    
                    <div class="slider-actions" style="margin-top: 15px; display: flex; justify-content: center; align-items: center; gap:10px">
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

        function nextUserSlide() {
            currentUserSlide = (currentUserSlide + 1) % userNotifs.length;
            renderUserSlider();
        }

        document.addEventListener('DOMContentLoaded', renderUserSlider);
    </script>
    <script>
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.onclick = function () {
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.animator-booking-tab').forEach(t => { t.style.display = 'none'; });
                this.classList.add('active');
                document.getElementById(this.dataset.tab).style.display = 'block';
            };
        });
    </script>
    <script>
        (function () {
            const dateInp = document.getElementById('animator-timeoff-date');
            const addBtn = document.getElementById('animator-timeoff-add');
            const chipsWrap = document.getElementById('animator-timeoff-chips');
            const sendBtn = document.getElementById('animator-timeoff-send');
            const feedbackEl = document.getElementById('animator-timeoff-feedback');
            if (
                typeof flatpickr === 'undefined' ||
                !dateInp ||
                !addBtn ||
                !chipsWrap ||
                !sendBtn
            ) {
                return;
            }

            const selectedDates = [];
            let fpInstance = null;
            let allowedSet = new Set();

            function showFeedback(ok, msg) {
                if (!feedbackEl) return;
                feedbackEl.hidden = false;
                feedbackEl.textContent = msg;
                feedbackEl.className = ok ? 'animator-timeoff-ok' : 'animator-schedule-error';
            }

            function hideFeedback() {
                if (!feedbackEl) return;
                feedbackEl.hidden = true;
                feedbackEl.textContent = '';
            }

            function renderChips() {
                chipsWrap.innerHTML = selectedDates
                    .map(
                        (d) =>
                            `<span class="animator-timeoff-chip-tag" data-d="${d}">${d.split('-').reverse().join('.')} <button type="button" aria-label="Убрать" data-remove="${d}">&times;</button></span>`
                    )
                    .join('');
            }

            chipsWrap.addEventListener('click', function (e) {
                const t = e.target;
                if (!(t instanceof HTMLElement)) return;
                const rm = t.getAttribute('data-remove');
                if (!rm) return;
                const i = selectedDates.indexOf(rm);
                if (i >= 0) selectedDates.splice(i, 1);
                selectedDates.sort();
                renderChips();
            });

            function getPickedYmd() {
                if (
                    fpInstance &&
                    fpInstance.selectedDates &&
                    fpInstance.selectedDates.length > 0
                ) {
                    return fpInstance.formatDate(fpInstance.selectedDates[0], 'Y-m-d');
                }
                const raw = (dateInp.value || '').trim();
                return /^(\d{4})-(\d{2})-(\d{2})$/.test(raw) ? raw : '';
            }

            addBtn.addEventListener('click', function () {
                hideFeedback();
                const v = getPickedYmd();
                if (!v) {
                    showFeedback(false, 'Выберите дату в календаре.');
                    return;
                }
                if (!allowedSet.has(v)) {
                    showFeedback(false, 'Отгул можно запрашивать только на дни, отмеченные в вашем графике как рабочие.');
                    return;
                }
                if (selectedDates.includes(v)) {
                    showFeedback(false, 'Эта дата уже в списке.');
                    return;
                }
                if (selectedDates.length >= 31) {
                    showFeedback(false, 'Не более 31 дня за одну отправку.');
                    return;
                }
                selectedDates.push(v);
                selectedDates.sort();
                renderChips();
            });

            sendBtn.addEventListener('click', async function () {
                hideFeedback();
                if (selectedDates.length === 0) {
                    showFeedback(false, 'Добавьте хотя бы одну дату.');
                    return;
                }
                sendBtn.disabled = true;
                try {
                    const res = await fetch('animator_time_off_submit.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            dates: selectedDates.slice(),
                        }),
                    });
                    let data = {};
                    try {
                        data = await res.json();
                    } catch (e) {
                        showFeedback(false, 'Не удалось разобрать ответ сервера.');
                        return;
                    }
                    if (data.success) {
                        location.reload();
                        return;
                    }
                    showFeedback(false, data.error || 'Не удалось отправить.');
                } catch (err) {
                    console.error(err);
                    showFeedback(false, 'Ошибка сети.');
                } finally {
                    sendBtn.disabled = false;
                }
            });

            async function boot() {
                let cal;
                try {
                    const res = await fetch('animator_time_off_calendar.php');
                    cal = await res.json();
                } catch (e) {
                    console.error(e);
                    showFeedback(false, 'Не удалось загрузить календарь. Обновите страницу.');
                    addBtn.disabled = true;
                    dateInp.disabled = true;
                    return;
                }
                if (!cal.ok) {
                    showFeedback(false, cal.error || 'Календарь недоступен.');
                    addBtn.disabled = true;
                    dateInp.disabled = true;
                    return;
                }

                const allowed = Array.isArray(cal.allowed_dates) ? cal.allowed_dates : [];
                allowedSet = new Set(allowed);

                if (fpInstance) fpInstance.destroy();
                const fpOpts = {
                    dateFormat: 'Y-m-d',
                    minDate: cal.today,
                    maxDate: cal.period_end,
                    disableMobile: true,
                };
                if (allowed.length > 0) {
                    fpOpts.enable = allowed;
                } else {
                    fpOpts.clickOpens = false;
                    showFeedback(
                        false,
                        'Нет ни одной рабочей даты в периоде графика, на которую можно запросить отгул.'
                    );
                }
                fpInstance = flatpickr(dateInp, fpOpts);

                if (allowed.length === 0) {
                    addBtn.disabled = true;
                    dateInp.disabled = true;
                } else {
                    hideFeedback();
                }
            }

            boot();
        })();
    </script>
    <script>
        (function () {
            const errorEl = document.getElementById('animator-schedule-error');
            const monthSel = document.getElementById('staff-schedule-month');
            const tableBody = document.querySelector('#staff-schedule-table tbody');
            if (!monthSel || !tableBody) return;

            function showLoadError(msg) {
                if (!errorEl) return;
                errorEl.textContent = msg;
                errorEl.hidden = false;
            }

            function clearLoadError() {
                if (!errorEl) return;
                errorEl.textContent = '';
                errorEl.hidden = true;
            }

            let periodStart = '';
            let periodEnd = '';
            let members = [];
            const scheduleState = {};

            function escapeHtml(str) {
                if (str == null) return '';
                const d = document.createElement('div');
                d.textContent = String(str);
                return d.innerHTML;
            }

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            function enumerateMonths(fromYmd, toYmd) {
                const out = [];
                const a = fromYmd.split('-').map(Number);
                const b = toYmd.split('-').map(Number);
                let y = a[0], m = a[1];
                const endY = b[0], endM = b[1];
                while (y < endY || (y === endY && m <= endM)) {
                    out.push(`${y}-${pad2(m)}`);
                    m += 1;
                    if (m > 12) {
                        m = 1;
                        y += 1;
                    }
                }
                return out;
            }

            const MONTHS_RU = [
                'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',
                'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь',
            ];

            function formatMonthYearRu(ym) {
                const [y, mo] = ym.split('-').map(Number);
                const name = MONTHS_RU[mo - 1] || ym;
                return `${name} - ${y}`;
            }

            function daysInMonth(ym) {
                const [y, mo] = ym.split('-').map(Number);
                const last = new Date(y, mo, 0).getDate();
                const days = [];
                for (let d = 1; d <= last; d++) {
                    const ds = `${y}-${pad2(mo)}-${pad2(d)}`;
                    if (ds >= periodStart && ds <= periodEnd) {
                        days.push(ds);
                    }
                }
                return days;
            }

            function forEachDateInPeriod(fn) {
                const a = new Date(periodStart + 'T12:00:00');
                const b = new Date(periodEnd + 'T12:00:00');
                for (let d = new Date(a); d.getTime() <= b.getTime(); d.setDate(d.getDate() + 1)) {
                    const y = d.getFullYear();
                    const m = d.getMonth() + 1;
                    const day = d.getDate();
                    const ds = `${y}-${pad2(m)}-${pad2(day)}`;
                    fn(ds, d.getDay());
                }
            }

            function getWorkForDate(mid, ds) {
                if (scheduleState[mid] && Object.prototype.hasOwnProperty.call(scheduleState[mid], ds)) {
                    return scheduleState[mid][ds];
                }
                return 1;
            }

            function detectModeForMember(mid) {
                if (!periodStart || !periodEnd) return null;
                let okAlways = true;
                let okWeekdays = true;
                let okWeekendsOnly = true;
                forEachDateInPeriod((ds, dow) => {
                    const w = getWorkForDate(mid, ds);
                    const wk = dow === 0 || dow === 6;
                    if (w !== 1) okAlways = false;
                    if (wk) {
                        if (w !== 0) okWeekdays = false;
                        if (w !== 1) okWeekendsOnly = false;
                    } else {
                        if (w !== 1) okWeekdays = false;
                        if (w !== 0) okWeekendsOnly = false;
                    }
                });
                if (okAlways) return 'always';
                if (okWeekdays) return 'weekdays';
                if (okWeekendsOnly) return 'weekends_only';
                return null;
            }

            function renderTable() {
                const ym = monthSel.value;
                const days = daysInMonth(ym);
                let thead = '<tr><th class="staff-schedule-name-col staff-schedule-th-corner">Сотрудник</th>';
                thead += '<th class="staff-schedule-mode-th">Режим</th>';
                days.forEach((ds) => {
                    const short = ds.slice(8, 10) + '.' + ds.slice(5, 7);
                    const dow = new Date(ds + 'T12:00:00').getDay();
                    const wd = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'][dow];
                    const weekend = dow === 0 || dow === 6;
                    const wkClass = weekend ? ' staff-schedule-th--weekend' : '';
                    thead += `<th class="staff-schedule-day-head${wkClass}" title="${escapeHtml(ds)}">
                        <span class="staff-schedule-wd">${wd}</span>
                        <span class="staff-schedule-date-num">${short}</span>
                    </th>`;
                });
                thead += '</tr>';

                let rows = '';
                members.forEach((mem) => {
                    const det = detectModeForMember(mem.id);
                    const cAlways = det === 'always' ? ' checked' : '';
                    const cWeek = det === 'weekdays' ? ' checked' : '';
                    const cWend = det === 'weekends_only' ? ' checked' : '';
                    const nm = 'staff-mode-' + mem.id;
                    rows += `<tr>
                        <td class="staff-schedule-name-col">${escapeHtml(mem.name)}</td>
                        <td class="staff-schedule-mode-col">
                            <div class="staff-mode-radios">
                                <label class="staff-mode-option"><input type="radio" name="${nm}" value="always"${cAlways} disabled> всегда</label>
                                <label class="staff-mode-option"><input type="radio" name="${nm}" value="weekdays"${cWeek} disabled> не в выходные</label>
                                <label class="staff-mode-option"><input type="radio" name="${nm}" value="weekends_only"${cWend} disabled> только выходные</label>
                            </div>
                        </td>`;
                    days.forEach((ds) => {
                        const dow = new Date(ds + 'T12:00:00').getDay();
                        const weekend = dow === 0 || dow === 6;
                        const wkClass = weekend ? ' staff-schedule-td--weekend' : '';
                        const v = getWorkForDate(mem.id, ds);
                        const checked = v === 1 ? 'checked' : '';
                        rows += `<td${wkClass ? ` class="${wkClass.trim()}"` : ''}><input type="checkbox" class="staff-day-cb" data-mid="${mem.id}" data-date="${escapeHtml(ds)}" ${checked} disabled></td>`;
                    });
                    rows += '</tr>';
                });

                tableBody.parentElement.querySelectorAll('thead').forEach((n) => n.remove());
                const theadEl = document.createElement('thead');
                theadEl.innerHTML = thead;
                tableBody.parentElement.insertBefore(theadEl, tableBody);

                tableBody.innerHTML = rows;
            }

            async function loadSchedule() {
                clearLoadError();
                try {
                    const res = await fetch('animator_schedule_get.php');
                    const data = await res.json();
                    if (!data.ok) {
                        showLoadError(data.error || 'Ошибка загрузки');
                        monthSel.innerHTML = '';
                        tableBody.parentElement.querySelectorAll('thead').forEach((n) => n.remove());
                        tableBody.innerHTML = '';
                        return;
                    }
                    periodStart = data.period_start;
                    periodEnd = data.period_end;
                    members = Array.isArray(data.members) ? data.members : [];
                    Object.keys(scheduleState).forEach((k) => delete scheduleState[k]);
                    const sch = data.schedule || {};
                    Object.keys(sch).forEach((midKey) => {
                        const mid = parseInt(midKey, 10);
                        scheduleState[mid] = Object.assign({}, sch[midKey]);
                    });
                    members.forEach((m) => {
                        if (!scheduleState[m.id]) scheduleState[m.id] = {};
                    });

                    const months = enumerateMonths(periodStart.slice(0, 7), periodEnd.slice(0, 7));
                    monthSel.innerHTML = months.map((ym) => `<option value="${ym}">${escapeHtml(formatMonthYearRu(ym))}</option>`).join('');
                    const cur = new Date();
                    const defYm = `${cur.getFullYear()}-${pad2(cur.getMonth() + 1)}`;
                    if (months.includes(defYm)) {
                        monthSel.value = defYm;
                    } else {
                        monthSel.value = months[0] || defYm;
                    }

                    renderTable();
                } catch (e) {
                    console.error(e);
                    showLoadError('Ошибка сети при загрузке графика.');
                }
            }

            monthSel.addEventListener('change', renderTable);
            loadSchedule();
        })();
    </script>
</body>

</html>