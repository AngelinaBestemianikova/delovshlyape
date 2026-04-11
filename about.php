<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Незабываемые праздники</title>
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/style_about.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet" />
</head>

<body>
    <?php
    session_start();
    include 'includes/header.php';
    require_once 'includes/db.php';

    // Fetch team members
    // 1. Получаем параметры из URL (как в programs.php)
    $selected_program_id = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;

    // Fetch team members
    $team_query = "SELECT * FROM team_members ORDER BY id";
    $team_result = mysqli_query($link, $team_query);

    // 2. Получаем список программ для выпадающего списка
    $all_programs_query = "SELECT id, name FROM programs ORDER BY name";
    $all_programs_result = mysqli_query($link, $all_programs_query);

    // 3. Формируем запрос отзывов с учетом фильтра
    $where_clause = " WHERE r.status = 'approved' ";

    if ($selected_program_id > 0) {
        // Если выбрана программа, добавляем её через AND
        $where_clause .= " AND r.program_id = $selected_program_id ";
    }
    $reviews_query = "SELECT r.*, u.first_name as name, u.path_image as avatar, p.name as program_name, p.id as p_id
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  LEFT JOIN programs p ON r.program_id = p.id 
                  $where_clause
                  ORDER BY r.created_time DESC";
    $reviews_result = mysqli_query($link, $reviews_query);
    ?>

    <section class="about_intro">
        <div class="container">
            <div class="about_intro-text">
                <h1>Давайте знакомиться!</h1>
                <p>Мы — команда настоящих профессионалов, которые с любовью и трепетом относятся к организации детских
                    праздников. Наши аниматоры, ведущие и артисты не просто отлично выполняют свою работу, но и умеют
                    по-настоящему увлечь и развеселить ребятишек. Многолетний опыт и творческий подход позволяют нам
                    создавать по-настоящему незабываемые торжества!</p>
            </div>
        </div>
    </section>

    <section class="team">
        <div class="container">
            <h1>Наша команда</h1>
            <div class="team-slider">
                <div class="team-slider-wrapper">
                    <?php if (mysqli_num_rows($team_result) > 0): ?>
                        <?php while ($member = mysqli_fetch_assoc($team_result)): ?>
                            <div class="team-member">
                                <img src="<?php echo htmlspecialchars($member['path_image']); ?>" alt="">
                                <p><?php echo htmlspecialchars($member['role']); ?></p>
                                <p class="member-name"><?php echo htmlspecialchars($member['name']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="advantages">
        <div class="container">
            <h1>Вот, за что нас любят</h1>
            <div class="advantages-grid">
                <div class="advantage-card">
                    <h3>Профессионализм</h3>
                    <ul>
                        <li>Опытные аниматоры</li>
                        <li>Индивидуальный подход</li>
                        <li>Креативные идеи</li>
                        <li>Безопасность детей</li>
                        <li>Многолетний опыт</li>
                    </ul>
                </div>
                <div class="advantage-card">
                    <h3>Удобство</h3>
                    <ul>
                        <li>Легкое бронирование</li>
                        <li>Гибкие условия</li>
                        <li>Поддержка 24/7</li>
                        <li>Прозрачные цены</li>
                        <li>Оплата с рассрочкой</li>
                    </ul>
                </div>
                <div class="advantage-card">
                    <h3>Эмоции</h3>
                    <ul>
                        <li>Уникальные программы</li>
                        <li>Высококачественное оформление</li>
                        <li>Атмосфера чудес и волшебства</li>
                        <li>Довольные дети и родители</li>
                        <li>Фотосессия на память</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="reviews" id="reviews-section">
        <div class="container">
            <h1>Отзывы</h1>

            <div class="any-filter-container" style="margin-bottom: 30px;">
                <div class="sort-container">
                    <select id="programFilter" class="sort-select">
                        <option value="0">Все программы</option>
                        <?php
                        // Сбрасываем указатель результата
                        mysqli_data_seek($all_programs_result, 0);
                        while ($prog = mysqli_fetch_assoc($all_programs_result)):
                            ?>
                            <option value="<?php echo $prog['id']; ?>" <?php echo ($selected_program_id == $prog['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prog['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div id="reviews-ajax-container">
                <?php include 'includes/reviews_fetch.php'; ?>
            </div>
        </div>
    </section>
    <section class="about_end">
        <div class="container">
            <div class="about_end-text">
                <h1>Мечтаете о незабываемом празднике для ребенка?</h1>
                <p>Тогда наша команда профессионалов с радостью возьмется за организацию незабываемого торжества.
                    Ознакомьтесь с нашими программами и выберите то, что больше всего подходит вам и вашему ребенку!
            </div>
            <button class="primary-button" onclick="window.location.href='programs.php'">Выбрать программу</button>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="js/reviews.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterSelect = document.getElementById('programFilter');
            const reviewsContainer = document.getElementById('reviews-ajax-container');

            // 1. Принудительный сброс при загрузке (Ctrl+R)
            // Если вам нужно, чтобы фильтр СОВСЕМ не сохранялся после перезагрузки:
            if (performance.navigation.type === 1) { // type 1 означает перезагрузку (Reload)
                filterSelect.value = "0";
                const url = new URL(window.location.href);
                url.searchParams.delete('program_id');
                url.searchParams.delete('page');
                window.history.replaceState({}, '', url);
            }

            // Функция смены страницы и фильтрации
            window.changePage = function (page) {
                const programId = filterSelect.value;
                reviewsContainer.style.opacity = '0.5';

                fetch(`includes/reviews_fetch.php?program_id=${programId}&page=${page}&ajax=1`)
                    .then(response => response.text())
                    .then(html => {
                        reviewsContainer.innerHTML = html;
                        reviewsContainer.style.opacity = '1';

                        // Прокрутка
                        if (page > 1 || programId > 0) {
                            document.getElementById('reviews-section').scrollIntoView({ behavior: 'smooth' });
                        }

                        // ОБНОВЛЯЕМ URL только если выбрана программа или страница не первая
                        const url = new URL(window.location.href);
                        if (programId > 0) {
                            url.searchParams.set('program_id', programId);
                        } else {
                            url.searchParams.delete('program_id');
                        }

                        if (page > 1) {
                            url.searchParams.set('page', page);
                        } else {
                            url.searchParams.delete('page');
                        }

                        // Используем replaceState вместо pushState, чтобы не захламлять историю назад-вперед
                        window.history.replaceState({}, '', url);
                    });
            };

            // Слушатель фильтра
            filterSelect.addEventListener('change', function () {
                changePage(1);
            });

            // ЛОГИКА СБРОСА: если страница загружена без GET-параметров в PHP, 
            // но они висят в строке браузера (от прошлых переходов) — чистим их.
            <?php if (!isset($_GET['program_id'])): ?>
                const cleanUrl = new URL(window.location.href);
                if (cleanUrl.searchParams.has('program_id')) {
                    cleanUrl.searchParams.delete('program_id');
                    cleanUrl.searchParams.delete('page');
                    window.history.replaceState({}, '', cleanUrl);
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>