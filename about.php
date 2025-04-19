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
include 'includes/header.php'; 
require_once 'includes/db.php';

// Fetch team members
$team_query = "SELECT * FROM team_members ORDER BY id";
$team_result = mysqli_query($link, $team_query);

// Fetch all reviews
$reviews_query = "SELECT r.*, u.first_name as name, u.path_image as avatar 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.id 
                 ORDER BY r.created_time DESC
                 LIMIT 10";
$reviews_result = mysqli_query($link, $reviews_query);
?>

<section class="about_intro">
    <div class="container">
        <div class="about_intro-text">
            <h1>Давайте знакомиться!</h1>
            <p>Мы — команда настоящих профессионалов, которые с любовью и трепетом относятся к организации детских праздников. Наши аниматоры, ведущие и артисты не просто отлично выполняют свою работу, но и умеют по-настоящему увлечь и развеселить ребятишек. Многолетний опыт и творческий подход позволяют нам создавать по-настоящему незабываемые торжества!</p>
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

<section class="reviews">
    <div class="container">
        <h1>Отзывы</h1>
        <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <div class="reviews-grid">
                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                    <div class="review-content">
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <div class="reviewer">
                            <img src="<?php echo htmlspecialchars($review['avatar']); ?>" alt="<?php echo htmlspecialchars($review['name']); ?>" class="reviewer-img">
                            <p><?php echo htmlspecialchars($review['name']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Пока нет отзывов</p>
        <?php endif; ?>
    </div>
</section>

<section class="about_end">
    <div class="container">
        <div class="about_end-text">
            <h1>Мечтаете о незабываемом празднике для ребенка?</h1>
            <p>Тогда наша команда профессионалов с радостью возьмется за организацию незабываемого торжества. Ознакомьтесь с нашими программами и выберите то, что больше всего подходит вам и вашему ребенку!
            </div>
        <button class="primary-button" onclick="window.location.href='programs.php'">Выбрать программу</button>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="js/reviews.js"></script>
</body>
</html>