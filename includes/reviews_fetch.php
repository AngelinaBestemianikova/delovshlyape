<?php
// Если запрос пришел через AJAX, подключаем базу
if (isset($_GET['ajax'])) {
    require_once 'db.php';
}

// 1. Настройки пагинации
$limit = 9; // Максимум 9 отзывов на странице (вы поставили 1 для теста, я вернул 12)
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

// 2. Получаем ID программы
$selected_program_id = isset($_GET['program_id']) ? (int) $_GET['program_id'] : 0;

// 3. Формируем условие
$where_clause = " WHERE r.status = 'approved' ";
if ($selected_program_id > 0) {
    $where_clause .= " AND r.program_id = $selected_program_id ";
}

// 4. Считаем общее количество для пагинации
$count_query = "SELECT COUNT(*) as total FROM reviews r $where_clause";
$count_result = mysqli_query($link, $count_query);
$total_data = mysqli_fetch_assoc($count_result);
$total_reviews = $total_data['total'];
$total_pages = ceil($total_reviews / $limit);

// 5. Выполняем основной запрос с LIMIT и OFFSET
$query = "SELECT r.*, u.first_name as name, u.path_image as avatar, p.name as program_name, p.id as p_id
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          LEFT JOIN programs p ON r.program_id = p.id 
          $where_clause 
          ORDER BY r.created_time DESC
          LIMIT $limit OFFSET $offset";

$result = mysqli_query($link, $query);

// Задаем цвета (замените на свои HEX, если нужно)
$main_purple = "#8773ff"; // Активный фиолетовый
$muted_purple = "rgb(135, 115, 255, 0.2)"; // Приглушенный фиолетовый

// 6. Вывод данных
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

    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-wrapper">

                <button class="pag-arrow prev" <?php echo ($page <= 1) ? 'disabled' : ''; ?>
                    onclick="changePage(<?php echo $page - 1; ?>)" title="Назад">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6"
                            stroke="<?php echo ($page <= 1) ? 'rgb(135, 115, 255, 0.3)' : $main_purple; ?>" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <div class="pagination-dots">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <span class="dot <?php echo ($i === $page) ? 'active' : ''; ?>"
                            style="background-color: <?php echo ($i === $page) ? $main_purple : $muted_purple; ?>"
                            onclick="changePage(<?php echo $i; ?>)" title="Страница <?php echo $i; ?>"></span>
                    <?php endfor; ?>
                </div>

                <button class="pag-arrow next" <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>
                    onclick="changePage(<?php echo $page + 1; ?>)" title="Вперед">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 6L15 12L9 18"
                            stroke="<?php echo ($page >= $total_pages) ? 'rgb(135, 115, 255, 0.3)' : $main_purple; ?>"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="no-results" style="text-align: center; padding: 40px 0;">
        <p>Отзывов на эту программу пока еще нет.</p>
    </div>
<?php endif; ?>