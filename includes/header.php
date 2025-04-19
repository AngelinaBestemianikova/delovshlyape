<?php
require_once 'db.php';

$menu_query = "SELECT id, name_for_menu FROM program_types ORDER BY id";
$menu_result = mysqli_query($link, $menu_query);
?>

<nav class="navbar">
    <div class="container">
        <div class="logo-wrapper">
            <img src="images/logo.svg" alt="Logo" class="logo-main">
            <img src="images/logo_footer.png" alt="Logo" class="logo-mobile">
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
                        <a href="programs.php#program-type-<?php echo $type['id']; ?>"><?php echo $type['name_for_menu']; ?></a>
                    <?php endwhile; ?>
                </div>
            </div>
            <a href="contact.php">Контакты</a>
        </div>
        <button class="primary-button booking-button" onclick="window.location.href='booking.php'">Забронировать</button>
        <div class="burger-menu">
            <img src="images/burger.svg" alt="Menu" class="burger-icon">
            <img src="images/cross.svg" alt="Close" class="cross-icon">
        </div>
    </div>
</nav>
<script src="./js/script.js"></script>