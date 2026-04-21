<?php
require_once 'db.php';

// Инициализируем переменную количества уведомлений
$notif_count = 0;

// Проверяем, авторизован ли пользователь (по аналогии с вашим профилем)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['client_id'])) {
    $client_id = $_SESSION['client_id'];
    $isAnimator = isset($_SESSION['is_animator']) && (int) $_SESSION['is_animator'] === 1;

    if ($isAnimator) {
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
        $count_query = "
            SELECT COUNT(*) as total
            FROM animator_notifications
            WHERE animator_user_id = $client_id
              AND is_read = 0
              AND event_date >= CURDATE()
        ";
        $count_result = mysqli_query($link, $count_query);
        if ($count_result) {
            $count_data = mysqli_fetch_assoc($count_result);
            $notif_count = (int) ($count_data['total'] ?? 0);
        }
    } else {
        // Считаем количество подходящих записей
        $count_query = "
            SELECT COUNT(*) as total 
            FROM bookings 
            WHERE user_id = $client_id 
              AND (status = 'canceled' OR status = 'archived')
              AND event_date >= CURDATE()";

        $count_result = mysqli_query($link, $count_query);
        if ($count_result) {
            $count_data = mysqli_fetch_assoc($count_result);
            $notif_count = (int) $count_data['total'];
        }
    }
}

$menu_query = "SELECT id, name_for_menu FROM program_types ORDER BY id";
$menu_result = mysqli_query($link, $menu_query);
?>
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=158ba693-867d-49c4-8363-db3240a19663"
    type="text/javascript"></script>
<div id="preloader">
    <div id="loader-animation"></div>
</div>

<nav class="navbar">
    <div class="container">
        <div class="logo-wrapper">
            <a href="index.php">
                <img src="images/logo.svg" alt="Logo" class="logo-main">
                <img src="images/logo_footer.png" alt="Logo" class="logo-mobile">
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php">Главная</a>
            <div class="nav-item">
                <div class="nav-item-header">
                    <a href="about.php">О нас</a>
                    <img src="images/arrow-down.svg" alt="" class="arrow">
                </div>
                <div class="submenu">
                    <a href="about.php">Полезное</a>
                    <a href="gallery.php">Галерея</a>
                </div>
            </div>
            <div class="nav-item">
                <div class="nav-item-header">
                    <a href="programs.php">Программы</a>
                    <img src="images/arrow-down.svg" alt="" class="arrow">
                </div>
                <div class="submenu submenu-programs">
                    <?php while ($type = mysqli_fetch_assoc($menu_result)): ?>
                        <a
                            href="programs.php#program-type-<?php echo $type['id']; ?>"><?php echo $type['name_for_menu']; ?></a>
                    <?php endwhile; ?>
                </div>
            </div>
            <a href="contact.php">Контакты</a>
            <a href="profile.php" class="profile-link">
                Профиль
                <?php if ($notif_count > 0): ?>
                    <span class="nav-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
        </div>
        <button class="primary-button booking-button"
            onclick="window.location.href='booking.php'">Забронировать</button>
        <div class="burger-menu">
            <img src="images/burger.svg" alt="Menu" class="burger-icon">
            <img src="images/cross.svg" alt="Close" class="cross-icon">
        </div>
    </div>
</nav>
<script src="./js/script.js"></script>