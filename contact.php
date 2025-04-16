<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Незабываемые праздники</title>
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/contact.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="contact-page">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="info-section">
                        <h2>Телефон и почта</h2>
                        <p>Если у вас есть вопросы или вы хотите забронировать праздник, свяжитесь с нами по телефону или электронной почте. Наши операторы будут рады ответить на ваши вопросы и помочь с организацией торжества в любое удобное для вас время.</p>
                        <div class="contact-details">
                            <div class="detail">
                                <span class="label">Телефон:</span>
                                <a href="tel:+375448232678">+375 (44) 823-26-78</a>
                            </div>
                            <div class="detail">
                                <span class="label">Email:</span>
                                <a href="mailto:info@delovshlyape.com">info@delovshlyape.com</a>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h2>Социальные сети</h2>
                        <p>Вы также можете найти нас в социальных сетях и задать вопрос или оставить заявку через наши официальные аккаунты. Мы всегда на связи и быстро реагируем на ваши сообщения.</p>
                        <div class="social-links">
                            <a href="#" class="social-link telegram">
                                <img src="images/telegram.svg" alt="Telegram">
                                <span>Telegram</span>
                            </a>
                            <a href="#" class="social-link twitter">
                                <img src="images/twitter.svg" alt="Twitter">
                                <span>Twitter</span>
                            </a>
                            <a href="#" class="social-link facebook">
                                <img src="images/facebook.svg" alt="Facebook">
                                <span>Facebook</span>
                            </a>
                            <a href="#" class="social-link vk">
                                <img src="images/vk.svg" alt="ВКонтакте">
                                <span>ВКонтакте</span>
                            </a>
                            <a href="#" class="social-link instagram">
                                <img src="images/instagram.svg" alt="Instagram">
                                <span>Instagram</span>
                            </a>
                            <a href="#" class="social-link viber">
                                <img src="images/viber.svg" alt="Viber">
                                <span>Viber</span>
                            </a>
                        </div>
                    </div>                    
                </div>

                <div class="contact-info">
                    <div class="info-section">
                        <h2>Как нас найти?</h2>
                        <p>Наш офис расположен в самом центре города по адресу: пр-т Победителей 39, Минск, Беларусь. Рядом расположены две станции метро: "Немига" и "Молодежная".</p>
                        
                        <div class="transport-info">
                            <div class="transport-section">
                                <h3>На машине:</h3>
                                <p>Если вы едете на личном автомобиле, вы можете воспользоваться платной парковкой, расположенной в 100 метрах от нашего офиса. Также рядом есть несколько бесплатных парковочных мест на прилегающих улицах.</p>
                            </div>
                            
                            <div class="transport-section">
                                <h3>На общественном транспорте:</h3>
                                <p>Добраться до нас можно на метро. Также недалеко проходят маршруты некоторых автобусных и троллейбусных линий:</p>
                                <ul>
                                    <li>Автобус: 1, 119с, 136, 163, 29, 69, 73, 91</li>
                                    <li>Троллейбус: 14, 58</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <img src="./images/map.png" class="map" alt="map">
                </div>
            </div>
        </div>

        <section class="contact">
            <h1>Остались вопросы?</h1>
            <p>Мы с удовольствием подскажем! Пожалуйста, введите свои данные в форму ниже</p>
            <?php include 'includes/contact-form.php'; ?>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
