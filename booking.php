<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Незабываемые праздники</title>
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/booking.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet" />
</head>

<body>
    <?php 
    include 'includes/header.php';
    require_once 'includes/db.php'; ?>

    <section class="booking">
        <div class="container">
            <h1>Инструкция <br>по бронированию</h1>
            
            <div class="booking-steps">
                <div class="booking-steps-column">
                    <div class="step-content">
                        <h2>Шаг 1. Выберите программу</h2>
                        <p>Выберите программу, которая вам нравится!</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 2. Заполните форму</h2>
                        <p>Укажите контактные данные (ваше имя, фамилию, почтовый адрес, номер телефона) и данные о мероприятии (дату мероприятия, место проведения, количество гостей и т.д.)</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 3. Подтверждение</h2>
                        <p>После отправки формы наш организатор свяжется с вами, чтобы подтвердить бронирование и обсудить детали</p>
                    </div>
                </div>
                
                <div class="booking-steps-column">
                    <div class="step-content">
                        <h2>Шаг 4. Договор</h2>
                        <p>После оплаты вы получите договор с указанием всех условий и деталей бронирования</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 5. Подготовка</h2>
                        <p>За несколько дней до мероприятия мы уточним все детали и подтвердим время начала шоу-программы</p>
                    </div>
                    
                    <div class="step-content">
                        <h2>Шаг 6. Праздник начинается!</h2>
                        <p>В назначенный день наши аниматоры прибудут и устроят незабываемый праздник!</p>
                    </div>
                </div>
            </div>
            
            <div class="booking-actions">
                <button class="primary-button" onclick="window.location.href='login.php'">В личный кабинет</button>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 