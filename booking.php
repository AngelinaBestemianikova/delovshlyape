<?php
session_start();
require_once 'includes/db.php';

// Check if program is specified
$program = isset($_GET['program']) ? $_GET['program'] : '';

// If user is not logged in, redirect to login with return URL
if (!isset($_SESSION['name'])) {
    $redirect_url = 'login.php?redirect=' . urlencode('booking.php' . ($program ? '?program=' . urlencode($program) : ''));
    header("Location: " . $redirect_url);
    exit();
}

// Get user data from database
$user_data = null;
if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $query = "SELECT first_name, last_name, email, phone FROM users WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
}

// Get all programs for the dropdown
$programs_query = "SELECT id, name FROM programs ORDER BY name";
$programs_result = mysqli_query($link, $programs_query);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Незабываемые праздники</title>
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/contact.css">
    <link rel="stylesheet" href="style/booking.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=158ba693-867d-49c4-8363-db3240a19663"
        type="text/javascript"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* Убираем горизонтальный скролл всей страницы (важно для мобильных) */
        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Настраиваем ТОЛЬКО контейнер, в котором лежит карта */
        .form-row-special .field-group {
            width: 100%;
            display: block;
            /* Чтобы инпут и карта не встали в одну строку */
        }

        /* Настраиваем саму карту */
        #map {
            width: 100% !important;
            /* Всегда по ширине родителя */
            aspect-ratio: 16 / 9;
            /* Пропорциональное изменение высоты */
            min-height: 200px;
            max-height: 450px;
            border-radius: 12px;
            margin-top: 10px;
            box-sizing: border-box;
        }

        /* Если браузер старый и не знает aspect-ratio, зададим высоту по умолчанию */
        @supports not (aspect-ratio: 16/9) {
            #map {
                height: 400px;
            }

            @media (max-width: 768px) {
                #map {
                    height: 250px;
                }
            }
        }
    </style>

</head>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="booking">
        <div class="container">
            <h1>Инструкция <br>по бронированию</h1>

            <div class="booking-steps">
                <div class="booking-steps-column">
                    <div class="step-content">
                        <h2>Шаг 1. Зарегистрируйтесь</h2>
                        <p>Для того, чтобы забронировать программу, вам необходимо зарегестрироваться на сайте.</p>
                    </div>
                    <div class="step-content">
                        <h2>Шаг 2. Выберите программу</h2>
                        <p>Выберите программу, которая вам нравится!</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 3. Заполните форму</h2>
                        <p>Укажите контактные данные (ваше имя, фамилию, адрес, номер телефона) и данные о
                            мероприятии (дату мероприятия, адрес, количество гостей и т.д.).</p>
                    </div>
                </div>

                <div class="booking-steps-column">
                    <div class="step-content">
                        <h2>Шаг 4. Подтверждение</h2>
                        <p>После отправки формы наш организатор свяжется с вами, чтобы подтвердить бронирование и
                            обсудить детали.</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 5. Подготовка</h2>
                        <p>За несколько дней до мероприятия мы уточним все детали и подтвердим время начала
                            шоу-программы.</p>
                    </div>

                    <div class="step-content">
                        <h2>Шаг 6. Праздник начинается!</h2>
                        <p>В назначенный день наши аниматоры прибудут и устроят незабываемый праздник!</p>
                    </div>
                </div>
            </div>

            <div class="booking-form-section">
                <h1>Заявка на бронирование</h1>
                <?php if (isset($_SESSION['booking_errors']['database'])): ?>
                    <div style="color: red; background: #fee; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                        <strong>Ошибка сохранения:</strong>
                        <?php echo $_SESSION['booking_errors']['database']; ?>
                    </div>
                <?php endif; ?>
                <form id="bookingForm" class="booking-form" action="process_booking.php" method="POST">
                    <div class="form-row">
                        <input type="text" name="name" placeholder="Имя*"
                            value="<?php echo isset($user_data['first_name']) ? htmlspecialchars($user_data['first_name']) : ''; ?>"
                            readonly>
                        <input type="text" name="surname" placeholder="Фамилия"
                            value="<?php echo isset($user_data['last_name']) ? htmlspecialchars($user_data['last_name']) : ''; ?>"
                            readonly>
                    </div>

                    <div class="form-row">
                        <input type="email" name="email" placeholder="Эл. почта*"
                            value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>"
                            readonly>
                        <input type="tel" name="phone" placeholder="Телефон*"
                            value="<?php echo isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>"
                            readonly>
                    </div>

                    <div class="form-row form-row-special">
                        <div class="field-group">
                            <select name="program" id="program-select" required>
                                <option value="">Выберите программу</option>
                                <?php while ($program_row = mysqli_fetch_assoc($programs_result)): ?>
                                    <option value="<?php echo htmlspecialchars($program_row['name']); ?>" <?php echo ($program == $program_row['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program_row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (isset($_SESSION['booking_errors']['program'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['program']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row form-row-special">
                        <div class="field-group">
                            <input type="text" name="celebrant"
                                placeholder="У кого планируется праздник? (например: девочка Аня, любит петь)" required>
                            <?php if (isset($_SESSION['booking_errors']['celebrant'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['celebrant']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field-group">
                            <input type="number" min="1" max="25" name="age" placeholder="Сколько лет имениннику?"
                                required>
                            <?php if (isset($_SESSION['booking_errors']['age'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['age']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="field-group">
                            <input type="number" min="1" max="200" name="guests" placeholder="Планируемое кол-во гостей"
                                required>
                            <?php if (isset($_SESSION['booking_errors']['guests'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['guests']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row form-row-special">
                        <div class="field-group">
                            <input type="text" name="location" id="suggest" placeholder="Введите адрес..." required>
                            <div id="map"></div>
                        </div>
                    </div>

                    <div class="form-row form-row-special">
                        <div class="field-group">
                            <input type="date" id="event_date" name="event_date"
                                placeholder="Планируемая дата праздника" required
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            <?php if (isset($_SESSION['booking_errors']['event_date'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['event_date']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <textarea name="wishes" placeholder="Пожелания к празднику"></textarea>

                    <div class="form-disclaimer">
                        <button type="submit" class="primary-button">Отправить</button>
                        <p>Нажимая на кнопку, вы принимаете условия <a href="#">пользовательского соглашения</a> и <a
                                href="#">политики конфиденциальности</a></p>
                    </div>
                </form>
                <?php unset($_SESSION['booking_errors']); ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- JS-логика -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const programSelect = document.getElementById("program-select");
            const dateInput = document.getElementById("event_date");

            let fpInstance = null;

            function updateCalendar(programName) {
                const formData = new FormData();
                formData.append("program", programName);
                formData.append("get_unavailable_dates", "1"); // просто маркер, чтобы отличать

                fetch("booking-handler.php", {
                    method: "POST",
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.unavailable_dates) {
                            if (fpInstance) {
                                fpInstance.destroy(); // переинициализация
                            }

                            fpInstance = flatpickr(dateInput, {
                                minDate: "today",
                                dateFormat: "Y-m-d",
                                disable: data.unavailable_dates
                            });
                        }
                    });
            }

            programSelect.addEventListener("change", () => {
                const selectedProgram = programSelect.value;
                if (selectedProgram) {
                    updateCalendar(selectedProgram);
                }
            });

            if (programSelect.value) {
                updateCalendar(programSelect.value);
            }
        });
    </script>
    <script type="text/javascript">
        ymaps.ready(init);

        function init() {
            // Координаты зоны (Минск + ~20км от МКАД)
            var allowedBounds = [
                [53.78, 27.35], // Юго-запад
                [54.02, 27.75]  // Северо-восток
            ];

            var myMap = new ymaps.Map("map", {
                center: [53.90, 27.56], // Центр Минска
                zoom: 10,
                controls: ['zoomControl']
            }, {
                restrictMapArea: allowedBounds,
                autoFitToViewport: 'always'
            });

            // Этот код пересчитывает размеры карты при ресайзе окна или повороте телефона
            window.addEventListener('resize', function () {
                myMap.container.fitToViewport();
            });

            var myPlacemark;
            var inputField = document.getElementById('suggest');

            // Настройка поисковых подсказок
            var suggestView = new ymaps.SuggestView('suggest', {
                boundedBy: allowedBounds,
                strictBounds: true
            });

            // 2. Событие при выборе адреса из подсказок
            suggestView.events.add('select', function (e) {
                var address = e.get('item').value;
                geocode(address);
            });

            // 3. Событие при потере фокуса (если ввели адрес и кликнули мимо)
            inputField.addEventListener('change', function () {
                geocode(this.value);
            });

            // 4. Событие клика по карте
            myMap.events.add('click', function (e) {
                var coords = e.get('coords');
                if (ymaps.util.bounds.containsPoint(allowedBounds, coords)) {
                    updateAddressByCoords(coords);
                } else {
                    alert("Извините, мы работаем только по Минску и в радиусе 20 км от МКАД.");
                }
            });

            // Функция: получаем адрес по координатам
            function updateAddressByCoords(coords) {
                movePlacemark(coords);

                ymaps.geocode(coords, { results: 1 }).then(function (res) {
                    var firstGeoObject = res.geoObjects.get(0);
                    if (firstGeoObject) {
                        var address = firstGeoObject.getAddressLine();
                        inputField.value = address;
                        inputField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }

            // Функция: получаем координаты по тексту
            function geocode(address) {
                if (!address) return;

                ymaps.geocode(address, {
                    boundedBy: allowedBounds,
                    strictBounds: true,
                    results: 1
                }).then(function (res) {
                    var obj = res.geoObjects.get(0);
                    if (obj) {
                        var coords = obj.geometry.getCoordinates();
                        if (ymaps.util.bounds.containsPoint(allowedBounds, coords)) {
                            myMap.setCenter(coords, 15);
                            movePlacemark(coords);
                        } else {
                            alert("Адрес находится вне зоны обслуживания.");
                        }
                    } else {
                        console.log("Адрес не найден");
                    }
                });
            }

            // Вспомогательная функция для перемещения метки
            function movePlacemark(coords) {
                if (myPlacemark) {
                    myPlacemark.geometry.setCoordinates(coords);
                } else {
                    myPlacemark = new ymaps.Placemark(coords, {}, {
                        preset: 'islands#violetDotIconWithCaption'
                    });
                    myMap.geoObjects.add(myPlacemark);
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('bookingForm');

            form.addEventListener('keydown', function (e) {
                // Если нажат Enter
                if (e.keyCode === 13) {
                    // Проверяем, на чем именно нажат Enter
                    // Если это НЕ кнопка отправки (type="submit") и НЕ текстовое поле (textarea)
                    const tagName = e.target.tagName.toLowerCase();
                    const type = e.target.type;

                    if (tagName !== 'textarea' && type !== 'submit') {
                        e.preventDefault(); // Блокируем отправку формы

                        // Если это поле адреса, вызываем поиск на карте (для удобства)
                        if (e.target.id === 'suggest') {
                            // Функция geocode подхватит изменения сама через событие change 
                            // или можно вызвать её принудительно, если она доступна в области видимости
                            e.target.blur(); // Убираем фокус, чтобы сработало событие 'change'
                        }

                        return false;
                    }
                }
            });
        });
    </script>
</body>

</html>