<?php
http_response_code(404); // Отправляем HTTP статус 404
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Незабываемые праздники для ваших детей</title>
    <link rel="stylesheet" href="style/general.css" />
    <link rel="stylesheet" href="style/style_main.css" />
    <link rel="stylesheet" href="style/contact.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet" />
</head>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script src="./js/404.js"></script>

<body>
    <?php
    include 'includes/header.php';
    require_once 'includes/db.php';
    ?>

    <div class="error-page">
        <h1>Страница не найдена</h1>
        <p>К сожалению, такой страницы не существует.</p>
        <div id="animation404"></div>
        <button onclick="window.location.href='index.php'" class=" primary-button">Вернуться на главную</button>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>