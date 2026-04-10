<?php
// Если запрос пришел через AJAX, нам нужно подключить базу данных самостоятельно
if (isset($_GET['ajax'])) {
    require_once 'db.php';
}

// 1. Получаем ID программы из GET-запроса
$selected_program_id = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;

// 2. Формируем условие: ВСЕГДА только approved
$where_clause = " WHERE r.status = 'approved' ";

if ($selected_program_id > 0) {
    $where_clause .= " AND r.program_id = $selected_program_id ";
}

// 3. Выполняем запрос к БД
// Добавил p.id as p_id, чтобы ссылка на программу работала корректно
$query = "SELECT r.*, u.first_name as name, u.path_image as avatar, p.name as program_name, p.id as p_id
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          LEFT JOIN programs p ON r.program_id = p.id 
          $where_clause 
          ORDER BY r.created_time DESC";

$result = mysqli_query($link, $query);

// 4. Вывод данных
if ($result && mysqli_num_rows($result) > 0): ?>
    <div class="reviews-grid">
        <?php while ($review = mysqli_fetch_assoc($result)): ?>
            <div class="review-content">
                <?php if (!empty($review['program_name'])): ?>
                    <p class="review-program">
                        <a href="programs.php#program-<?php echo $review['p_id']; ?>" class="review-program-link">
                            <?php echo htmlspecialchars($review['program_name']); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <p class="review-text">
                    <?php echo htmlspecialchars($review['comment']); ?>
                </p>

                <div class="reviewer">
                    <?php
                    // Проверяем наличие аватара, если нет — ставим заглушку
                    $avatarPath = (!empty($review['avatar'])) ? $review['avatar'] : 'img/default-avatar.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>"
                        alt="<?php echo htmlspecialchars($review['name']); ?>" class="reviewer-img">
                    <p class="reviewer-name">
                        <?php echo htmlspecialchars($review['name']); ?>
                    </p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="no-results" style="text-align: center; padding: 40px 0;">
        <p>Отзывов на эту программу пока еще нет.</p>
    </div>
<?php endif; ?>