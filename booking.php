<?php
session_start();
require_once 'includes/db.php';

$booking_form_data = (isset($_SESSION['booking_form_data']) && is_array($_SESSION['booking_form_data']))
    ? $_SESSION['booking_form_data']
    : [];

// Программа: после ошибки бронирования — из формы, иначе из ?program=
$program = '';
if (!empty($booking_form_data['program'])) {
    $program = $booking_form_data['program'];
} elseif (isset($_GET['program'])) {
    $program = $_GET['program'];
}

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

// Get only active programs for the dropdown (duration — допустимые слоты времени на форме)
$programs_query = "SELECT id, name, max_children, COALESCE(duration, 0) AS duration FROM programs WHERE is_archived = 0 ORDER BY name";
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
                <?php if (isset($_SESSION['booking_errors']['animators'])): ?>
                    <div style="color: #c62828; background: #ffebee; padding: 10px; margin-bottom: 16px; border-radius: 5px;">
                        <?php echo htmlspecialchars($_SESSION['booking_errors']['animators']); ?>
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
                                    <option value="<?php echo htmlspecialchars($program_row['name']); ?>"
                                        data-max-children="<?php echo (int) $program_row['max_children']; ?>"
                                        data-duration="<?php echo (int) $program_row['duration']; ?>"
                                        <?php echo ($program == $program_row['name']) ? 'selected' : ''; ?>>
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
                                placeholder="У кого планируется праздник? (например: девочка Аня, любит петь)" required
                                value="<?php echo isset($booking_form_data['celebrant']) ? htmlspecialchars($booking_form_data['celebrant']) : ''; ?>">
                            <?php if (isset($_SESSION['booking_errors']['celebrant'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['celebrant']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field-group">
                            <input type="number" min="1" max="18" name="age" placeholder="Сколько лет имениннику?"
                                required
                                value="<?php echo (isset($booking_form_data['age']) && $booking_form_data['age'] !== '') ? htmlspecialchars((string) (int) $booking_form_data['age']) : ''; ?>">
                            <?php if (isset($_SESSION['booking_errors']['age'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['age']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="field-group">
                            <input type="number" min="1" name="guests" id="guests-input"
                                placeholder="Планируемое кол-во гостей" required
                                value="<?php echo (isset($booking_form_data['guests']) && $booking_form_data['guests'] !== '') ? htmlspecialchars((string) (int) $booking_form_data['guests']) : ''; ?>">
                            <?php if (isset($_SESSION['booking_errors']['guests'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['guests']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row form-row-special">
                        <div class="field-group booking-location-field booking-dependent-field<?php echo ($program !== '') ? '' : ' booking-location--locked'; ?>"
                            id="booking-location-field">
                            <div class="booking-dependent-mask" role="presentation" aria-hidden="true"></div>
                            <input type="text" name="location" id="suggest" placeholder="Введите адрес..." required
                                value="<?php echo (($program !== '') && isset($booking_form_data['location'])) ? htmlspecialchars($booking_form_data['location']) : ''; ?>">
                            <div id="map"></div>
                        </div>
                    </div>

                    <div id="booking-datetime-row"
                        class="form-row form-row-datetime<?php echo ($program !== '') ? '' : ' booking-datetime--locked'; ?>">
                        <div class="field-group field-group-date booking-datetime-field">
                            <div class="booking-dependent-mask" role="presentation" aria-hidden="true"></div>
                            <input type="text" id="event_date" name="event_date" inputmode="none" autocomplete="off"
                                placeholder="Дата мероприятия*" required
                                value="<?php echo isset($booking_form_data['event_date']) ? htmlspecialchars($booking_form_data['event_date']) : ''; ?>">
                            <?php if (isset($_SESSION['booking_errors']['event_date'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['event_date']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="field-group field-group-time booking-datetime-field">
                            <div class="booking-dependent-mask" role="presentation" aria-hidden="true"></div>
                            <select name="event_start_time" id="event_start_time" required>
                                <option value="">Время мероприятия*</option>
                            </select>
                            <?php if (isset($_SESSION['booking_errors']['event_start_time'])): ?>
                                <span
                                    class="field-error"><?php echo htmlspecialchars($_SESSION['booking_errors']['event_start_time']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <textarea name="wishes" placeholder="Пожелания к празднику"><?php echo isset($booking_form_data['wishes']) ? htmlspecialchars($booking_form_data['wishes']) : ''; ?></textarea>

                    <div class="form-disclaimer">
                        <button type="submit" class="primary-button">Отправить</button>
                        <p>Нажимая на кнопку, вы принимаете условия <a href="#">пользовательского соглашения</a> и <a
                                href="#">политики конфиденциальности</a></p>
                    </div>
                </form>
                <?php
                $booking_preserve_event_start_time = isset($booking_form_data['event_start_time'])
                    ? trim((string) $booking_form_data['event_start_time'])
                    : '';
                unset($_SESSION['booking_errors'], $_SESSION['booking_form_data']);
                ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- JS-логика -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const preservedEventStartTime = <?php echo json_encode($booking_preserve_event_start_time, JSON_UNESCAPED_UNICODE); ?>;
            const programSelect = document.getElementById("program-select");
            const dateInput = document.getElementById("event_date");
            const guestsInput = document.getElementById("guests-input");
            const timeSelect = document.getElementById("event_start_time");
            const datetimeRow = document.getElementById("booking-datetime-row");
            const locationField = document.getElementById("booking-location-field");
            const bookingFormEl = document.getElementById("bookingForm");

            const MSG_NEED_PROGRAM_FIRST = "Сначала выберите программу";

            let fpInstance = null;
            let slotRequestCounter = 0;

            /** Построить select времени из ответа сервера или заглушек (без программы / без даты / нет слотов). */
            function renderTimeSelectFromServer(availableSlots, keepValue) {
                const slots = Array.isArray(availableSlots) ? availableSlots : [];
                const prevValue = typeof keepValue === "string" ? keepValue : "";
                const hasProg = !!programSelect.value;
                const dateOk = /^(\d{4})-(\d{2})-(\d{2})$/.test((dateInput.value || "").trim());

                timeSelect.innerHTML = "";

                const ph = document.createElement("option");
                ph.value = "";
                if (!hasProg) {
                    ph.textContent = "Время мероприятия*";
                } else if (!dateOk) {
                    ph.textContent = "Сначала укажите дату*";
                } else if (slots.length === 0) {
                    ph.textContent = "Нет свободных слотов";
                } else {
                    ph.textContent = "Время*";
                }
                timeSelect.appendChild(ph);

                const showSlots = hasProg && dateOk && slots.length > 0;
                if (showSlots) {
                    slots.forEach(function (label) {
                        const opt = document.createElement("option");
                        opt.value = label;
                        opt.textContent = label;
                        timeSelect.appendChild(opt);
                    });
                }

                if (prevValue && Array.from(timeSelect.options).some((o) => o.value === prevValue)) {
                    timeSelect.value = prevValue;
                }
                syncTimeSelectUnpickedClass();
            }

            function requestAvailableTimeSlots(keepValue) {
                if (datetimeRow && datetimeRow.classList.contains("booking-datetime--locked")) {
                    renderTimeSelectFromServer([], "");
                    return;
                }

                const hasProg = !!programSelect.value;
                const dateStr = (dateInput.value || "").trim();
                const dateOk = /^(\d{4})-(\d{2})-(\d{2})$/.test(dateStr);

                if (!hasProg || !dateOk) {
                    renderTimeSelectFromServer([], keepValue);
                    return;
                }

                slotRequestCounter += 1;
                const rid = slotRequestCounter;
                const formData = new FormData();
                formData.append("program", programSelect.value);
                formData.append("event_date", dateStr);
                formData.append("get_available_time_slots", "1");

                fetch("booking-handler.php", {
                    method: "POST",
                    body: formData,
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        if (rid !== slotRequestCounter) return;
                        renderTimeSelectFromServer(data.available_time_slots || [], keepValue);
                    })
                    .catch(function () {
                        if (rid !== slotRequestCounter) return;
                        renderTimeSelectFromServer([], keepValue);
                    });
            }

            function syncTimeSelectUnpickedClass() {
                if (!timeSelect) return;
                timeSelect.classList.toggle("booking-time-unpicked", timeSelect.value === "");
            }

            function setDatetimeFieldsLocked(locked) {
                if (!datetimeRow) return;
                datetimeRow.classList.toggle("booking-datetime--locked", locked);
                [dateInput, timeSelect].forEach(function (el) {
                    if (!el) return;
                    if (locked) {
                        el.setAttribute("tabindex", "-1");
                    } else {
                        el.removeAttribute("tabindex");
                    }
                });
                if (locked && fpInstance) {
                    fpInstance.destroy();
                    fpInstance = null;
                }
            }

            if (bookingFormEl) {
                bookingFormEl.addEventListener(
                    "click",
                    function (e) {
                        const mask = e.target.closest(".booking-dependent-mask");
                        if (!mask) return;
                        const dtRow = mask.closest("#booking-datetime-row");
                        const datetimeLocked = dtRow && dtRow.classList.contains("booking-datetime--locked");
                        const locGroup = mask.closest("#booking-location-field");
                        const locLocked = locGroup && locGroup.classList.contains("booking-location--locked");
                        if (datetimeLocked || locLocked) {
                            e.preventDefault();
                            e.stopPropagation();
                            alert(MSG_NEED_PROGRAM_FIRST);
                        }
                    },
                    true
                );
                bookingFormEl.querySelectorAll(".booking-dependent-mask").forEach(function (mask) {
                    mask.addEventListener("mousedown", function (e) {
                        const dtRow = mask.closest("#booking-datetime-row");
                        const datetimeLocked = dtRow && dtRow.classList.contains("booking-datetime--locked");
                        const locGroup = mask.closest("#booking-location-field");
                        const locLocked = locGroup && locGroup.classList.contains("booking-location--locked");
                        if (datetimeLocked || locLocked) {
                            e.preventDefault();
                        }
                    });
                });
            }

            function setLocationFieldsLocked(locked) {
                if (!locationField) return;
                locationField.classList.toggle("booking-location--locked", locked);
                const sug = document.getElementById("suggest");
                if (!sug) return;
                if (locked) {
                    sug.value = "";
                    sug.setAttribute("tabindex", "-1");
                    window.bookingAddressValid = false;
                } else {
                    sug.removeAttribute("tabindex");
                }
            }

            timeSelect.addEventListener("change", syncTimeSelectUnpickedClass);

            /** Минимальная дата мероприятия — завтра (как на сервере и в атрибуте min у input). */
            function getTomorrowDate() {
                const d = new Date();
                d.setDate(d.getDate() + 1);
                d.setHours(0, 0, 0, 0);
                return d;
            }

            function parseYmdLocal(str) {
                const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(str);
                if (!m) return null;
                const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
                d.setHours(0, 0, 0, 0);
                return isNaN(d.getTime()) ? null : d;
            }

            function clampGuestsToMax(max) {
                if (!(max > 0) || !guestsInput.value) return;
                const v = parseInt(guestsInput.value, 10);
                if (!isNaN(v) && v > max) guestsInput.value = String(max);
            }

            function syncGuestsMaxFromProgram() {
                const opt = programSelect.options[programSelect.selectedIndex];
                const max = opt && opt.dataset.maxChildren ? parseInt(opt.dataset.maxChildren, 10) : NaN;
                if (max > 0) {
                    guestsInput.max = max;
                    guestsInput.setAttribute("title", "Максимум для выбранной программы: " + max);
                    clampGuestsToMax(max);
                } else {
                    guestsInput.removeAttribute("max");
                    guestsInput.removeAttribute("title");
                }
            }

            function updateCalendar(programName, keepPresetTimeValue) {
                const keepSlot = typeof keepPresetTimeValue === "string" ? keepPresetTimeValue : "";
                const formData = new FormData();
                formData.append("program", programName);
                formData.append("get_unavailable_dates", "1"); // просто маркер, чтобы отличать

                fetch("booking-handler.php", {
                    method: "POST",
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (typeof data.max_children === "number" && data.max_children > 0) {
                            guestsInput.max = data.max_children;
                            guestsInput.setAttribute("title", "Максимум для выбранной программы: " + data.max_children);
                            clampGuestsToMax(data.max_children);
                        }
                        const lockedNow = datetimeRow && datetimeRow.classList.contains("booking-datetime--locked");
                        if (lockedNow) {
                            renderTimeSelectFromServer([], "");
                        } else if (data.unavailable_dates) {
                            if (fpInstance) {
                                fpInstance.destroy(); // переинициализация
                            }

                            const minEventDate = getTomorrowDate();
                            const fpOpts = {
                                minDate: minEventDate,
                                dateFormat: "Y-m-d",
                                disable: data.unavailable_dates || [],
                                onChange: function (_selDates, _dateStr) {
                                    requestAvailableTimeSlots("");
                                },
                                onReady: function (selectedDates, _dateStr, instance) {
                                    let ds = (dateInput.value || "").trim();
                                    if (
                                        selectedDates &&
                                        selectedDates.length > 0 &&
                                        instance &&
                                        typeof instance.formatDate === "function"
                                    ) {
                                        ds = instance.formatDate(selectedDates[0], "Y-m-d");
                                    }
                                    if (ds && /^(\d{4})-(\d{2})-(\d{2})$/.test(ds)) {
                                        requestAvailableTimeSlots(keepSlot);
                                    } else {
                                        renderTimeSelectFromServer([], keepSlot);
                                    }
                                },
                            };
                            if (dateInput.value) {
                                const parsed = parseYmdLocal(dateInput.value);
                                if (parsed && parsed.getTime() >= minEventDate.getTime()) {
                                    fpOpts.defaultDate = dateInput.value;
                                }
                            }
                            fpInstance = flatpickr(dateInput, fpOpts);
                        } else {
                            renderTimeSelectFromServer([], keepSlot);
                        }
                    })
                    .catch(function () {
                        renderTimeSelectFromServer([], keepSlot);
                    });
            }

            programSelect.addEventListener("change", () => {
                syncGuestsMaxFromProgram();
                const selectedProgram = programSelect.value;
                if (!selectedProgram) {
                    setDatetimeFieldsLocked(true);
                    setLocationFieldsLocked(true);
                    dateInput.value = "";
                    renderTimeSelectFromServer([], "");
                    return;
                }
                setDatetimeFieldsLocked(false);
                setLocationFieldsLocked(false);
                renderTimeSelectFromServer([], "");
                updateCalendar(selectedProgram, "");
            });

            syncGuestsMaxFromProgram();
            if (programSelect.value) {
                setDatetimeFieldsLocked(false);
                setLocationFieldsLocked(false);
                renderTimeSelectFromServer([], "");
                updateCalendar(programSelect.value, preservedEventStartTime);
            } else {
                setDatetimeFieldsLocked(true);
                setLocationFieldsLocked(true);
                renderTimeSelectFromServer([], "");
                syncTimeSelectUnpickedClass();
            }
        });
    </script>
    <script type="text/javascript">
        // 1. ПЕРЕМЕННАЯ ТЕПЕРЬ ГЛОБАЛЬНАЯ
        window.bookingAddressValid = false;

        ymaps.ready(init);

        function init() {
            var allowedBounds = [
                [53.78, 27.35], // Юго-запад
                [54.02, 27.75]  // Северо-восток
            ];

            var myMap = new ymaps.Map("map", {
                center: [53.90, 27.56],
                zoom: 10,
                controls: ['zoomControl']
            }, {
                restrictMapArea: allowedBounds,
                autoFitToViewport: 'always'
            });

            window.addEventListener('resize', function () {
                myMap.container.fitToViewport();
            });

            var myPlacemark;
            var inputField = document.getElementById('suggest');

            // Настройка поисковых подсказок БЕЗ организаций (только адреса)
            var suggestView = new ymaps.SuggestView('suggest', {
                boundedBy: allowedBounds,
                strictBounds: true,
                provider: 'yandex#map', // Использовать провайдер карты (убирает лишние подписи организаций)
                results: 5
            });

            suggestView.events.add('select', function (e) {
                var address = e.get('item').value;
                geocode(address);
            });

            inputField.addEventListener('change', function () {
                geocode(this.value);
            });

            myMap.events.add('click', function (e) {
                var coords = e.get('coords');
                if (ymaps.util.bounds.containsPoint(allowedBounds, coords)) {
                    updateAddressByCoords(coords);
                } else {
                    alert("Извините, мы работаем только по Минску и в радиусе 20 км от МКАД.");
                }
            });

            function updateAddressByCoords(coords) {
                if (!ymaps.util.bounds.containsPoint(allowedBounds, coords)) {
                    window.bookingAddressValid = false;
                    return;
                }
                movePlacemark(coords);
                ymaps.geocode(coords, { results: 1 }).then(function (res) {
                    var firstGeoObject = res.geoObjects.get(0);
                    if (!firstGeoObject) {
                        window.bookingAddressValid = false;
                        return;
                    }
                    var gcCoords = firstGeoObject.geometry.getCoordinates();
                    if (!ymaps.util.bounds.containsPoint(allowedBounds, gcCoords)) {
                        window.bookingAddressValid = false;
                        alert("Адрес находится вне зоны обслуживания.");
                        return;
                    }
                    var address = firstGeoObject.getAddressLine();
                    inputField.value = address;
                    window.bookingAddressValid = true;
                });
            }

            function geocode(address) {
                if (!address) {
                    window.bookingAddressValid = false;
                    return;
                }

                ymaps.geocode(address, {
                    boundedBy: allowedBounds,
                    strictBounds: true,
                    results: 1
                }).then(function (res) {
                    var obj = res.geoObjects.get(0);
                    if (!obj) {
                        window.bookingAddressValid = false;
                        return;
                    }
                    var coords = obj.geometry.getCoordinates();
                    if (!ymaps.util.bounds.containsPoint(allowedBounds, coords)) {
                        window.bookingAddressValid = false;
                        alert("Адрес находится вне зоны обслуживания.");
                        return;
                    }
                    inputField.value = obj.getAddressLine();
                    myMap.setCenter(coords, 15);
                    movePlacemark(coords);
                    window.bookingAddressValid = true;
                });
            }

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

            if (inputField.value && inputField.value.trim()) {
                geocode(inputField.value.trim());
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('bookingForm');
            const inputField = document.getElementById('suggest');

            inputField.addEventListener('input', function () {
                window.bookingAddressValid = false;
            });

            form.addEventListener('submit', function (e) {
                // Теперь проверка работает корректно
                if (!window.bookingAddressValid) {
                    e.preventDefault();
                    alert("Укажите адрес в пределах зоны на карте (Минск и окрестности)");
                    inputField.focus();
                }
            });

            form.addEventListener('keydown', function (e) {
                if (e.keyCode === 13) {
                    const tagName = e.target.tagName.toLowerCase();
                    const type = e.target.type;

                    if (tagName !== 'textarea' && type !== 'submit') {
                        e.preventDefault();
                        if (e.target.id === 'suggest') {
                            e.target.blur();
                        }
                        return false;
                    }
                }
            });
        });
    </script>
</body>

</html>