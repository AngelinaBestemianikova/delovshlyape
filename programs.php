<?php
require_once 'includes/db.php';

// Get all filter parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$age = isset($_GET['age']) ? $_GET['age'] : 'any';
$price = isset($_GET['price']) ? $_GET['price'] : 'any';
$children = isset($_GET['children']) ? $_GET['children'] : 'any';
$duration = isset($_GET['duration']) ? $_GET['duration'] : 'any';

$total_programs_found = 0;

// Fetch program types
$types_query = "SELECT * FROM program_types ORDER BY id";
$types_result = mysqli_query($link, $types_query);

$program_types = [
    1 => [
        'id' => 'kids',
        'title' => 'Шоу-программы для малышей и школьников',
        'description' => 'Следующие шоу-программы предназначены для детей в возрасте от 1 года до 10 лет'
    ],
    2 => [
        'id' => 'teens',
        'title' => 'Шоу-программы для подростков',
        'description' => 'Следующие тематические шоу-программы предназначены для детей старше 11 лет'
    ],
    3 => [
        'id' => 'special',
        'title' => 'Особые мероприятия',
        'description' => 'Мы наполним ваш вечер радостью, эмоциями, яркими моментами и вместе с вами создадим атмосферу волшебства!'
    ]
];
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Незабываемые праздники</title>
  <link rel="stylesheet" href="style/general.css">
  <link rel="stylesheet" href="style/contact.css">
  <link rel="stylesheet" href="style/programs.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
    rel="stylesheet" />
</head>

<body>
<?php include 'includes/header.php'; ?>

<section class="programs">
    <div class="filter-toggle">
        <img src="images/filter.svg" alt="Фильтры" class="filter-icon">
    </div>
    
    <div class="filter-panel">
        <div class="filter-header">
            <h3>Фильтры</h3>
            <img src="images/cross.svg" alt="Закрыть" class="close-filter">
        </div>
        
        <div class="filter-content">
            <div class="filter-section">
                <h4>Возраст</h4>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="radio" name="age" value="any" checked>
                        <span class="radio-custom"></span>
                        Любой
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="age" value="1-10">
                        <span class="radio-custom"></span>
                        от 1 до 10 лет
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="age" value="11+">
                        <span class="radio-custom"></span>
                        от 11 лет
                    </label>
                </div>
            </div>

            <div class="filter-section">
                <h4>Стоимость</h4>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="radio" name="price" value="any" checked>
                        <span class="radio-custom"></span>
                        Не важно
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="price" value="<1000">
                        <span class="radio-custom"></span>
                        Менее 1000 BYN
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="price" value="1000-2000">
                        <span class="radio-custom"></span>
                        От 1000 BYN до 2000 BYN
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="price" value=">2000">
                        <span class="radio-custom"></span>
                        Более 2000 BYN
                    </label>
                </div>
            </div>

            <div class="filter-section">
                <h4>Кол-во детей</h4>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="radio" name="children" value="any" checked>
                        <span class="radio-custom"></span>
                        Не важно
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="children" value="10">
                        <span class="radio-custom"></span>
                        10 и более
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="children" value="15">
                        <span class="radio-custom"></span>
                        15 и более
                    </label>
                </div>
            </div>

            <div class="filter-section">
                <h4>Длительность</h4>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="radio" name="duration" value="any" checked>
                        <span class="radio-custom"></span>
                        Не важно
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="duration" value=">=2">
                        <span class="radio-custom"></span>
                        2 часа и более
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="duration" value=">=4">
                        <span class="radio-custom"></span>
                        4 часа и более
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="duration" value=">=6">
                        <span class="radio-custom"></span>
                        6 часов и более
                    </label>
                </div>
            </div>
        </div>

        <div class="filter-footer">
            <button type="button" class="apply-filters-btn primary-button">Применить фильтры</button>
        </div>
    </div>

    <div class="container">
        <div class="any-filter-container">
            <div class="sort-container">
                <select name="sort" class="sort-select" onchange="updateSort(this.value)">
                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>По умолчанию</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Цена по возрастанию</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Цена по убыванию</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Название А-Я</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Название Я-А</option>
                </select>
            </div>
            <div class="search-container">
                <form class="search-box" method="GET" id="searchForm">
                    <input type="text" name="search" placeholder="Введите программу" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Найти</button>
                </form>
            </div>
        </div>

        <?php while ($type = mysqli_fetch_assoc($types_result)): 
            $programs_query = "SELECT * FROM programs WHERE type_id = " . $type['id'];
            
            // Add search condition
            if (!empty($search)) {
                $search_term = mysqli_real_escape_string($link, $search);
                $programs_query .= " AND name LIKE '%{$search_term}%'";
            }

            // Add age filter
            if ($age === '1-10') {
                $programs_query .= " AND type_id = 1";
            } elseif ($age === '11+') {
                $programs_query .= " AND (type_id = 2 OR type_id = 3)";
            }

            // Add price filter
            if ($price === '<1000') {
                $programs_query .= " AND price < 1000";
            } elseif ($price === '1000-2000') {
                $programs_query .= " AND price >= 1000 AND price <= 2000";
            } elseif ($price === '>2000') {
                $programs_query .= " AND price > 2000";
            }

            // Add children filter
            if ($children === '15') {
                $programs_query .= " AND max_children >= 15";
            } elseif ($children === '10') {
                $programs_query .= " AND max_children >= 10";
            }

            // Add duration filter
            if ($duration === '>=2') {
                $programs_query .= " AND duration >= 120";
            } elseif ($duration === '>=4') {
                $programs_query .= " AND duration >= 240";
            } elseif ($duration === '>=6') {
                $programs_query .= " AND duration >= 360";
            }

            // Add sorting
            switch ($sort) {
                case 'price_asc':
                    $programs_query .= " ORDER BY price ASC";
                    break;
                case 'price_desc':
                    $programs_query .= " ORDER BY price DESC";
                    break;
                case 'name_asc':
                    $programs_query .= " ORDER BY name ASC";
                    break;
                case 'name_desc':
                    $programs_query .= " ORDER BY name DESC";
                    break;
                default:
                    $programs_query .= " ORDER BY id ASC";
            }

            $programs_result = mysqli_query($link, $programs_query);
            $programs_count = mysqli_num_rows($programs_result);
            $total_programs_found += $programs_count;

            if ($programs_count > 0 || empty($search)):
        ?>
            <div class="program-type" id="program-type-<?php echo $type['id']; ?>">
                <div class="program-type-header">
                    <h1><?php echo $program_types[$type['id']]['title'] ?? htmlspecialchars($type['name']); ?></h1>
                    <p class="type-description"><?php echo $program_types[$type['id']]['description'] ?? htmlspecialchars($type['description']); ?></p>
                </div>
                
                <div class="programs-grid">
                    <?php while ($program = mysqli_fetch_assoc($programs_result)): ?>
                        <div class="program-card">
                            <img src="<?php echo htmlspecialchars($program['image_path']); ?>" alt="<?php echo htmlspecialchars($program['name']); ?>">
                            <div class="program-info">
                                <h2><?php echo htmlspecialchars($program['name']); ?></h2>
                                <p class="program-description"><?php echo htmlspecialchars($program['description']); ?></p>
                                <p class="program-price-included" style="font-weight: bold;">В стоимость включено:</p>
                                <ul class="program-services">
                                    <?php 
                                    $services = explode(';', $program['included_services']);
                                    foreach ($services as $service): 
                                    ?>
                                        <li><?php echo htmlspecialchars(trim($service)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="program-details">
                                    <div class="detail">
                                        <img src="images/time.svg" alt="Duration" class="detail-icon">
                                        <span class="value">
                                            <?php 
                                            if ($program['duration'] % 60 == 0) {
                                                $hours = $program['duration'] / 60;
                                                echo $hours . 'ч';
                                            } else {
                                                $hours = floor($program['duration'] / 60);
                                                $minutes = $program['duration'] % 60;
                                                echo $hours . ' ч ' . $minutes . ' мин';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="detail">
                                        <img src="images/child.svg" alt="Children" class="detail-icon">
                                        <span class="value"><?php echo $program['max_children']; ?> детей</span>
                                    </div>
                                    <div class="detail">
                                        <img src="images/money.svg" alt="Price" class="detail-icon">
                                        <span class="value"><?php echo number_format($program['price'], 0, ',', ' '); ?>p</span>
                                    </div>
                                </div>
                                <button class="primary-button">Забронировать</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php 
            endif;
        endwhile; 
        
        if (!empty($search) && $total_programs_found === 0):
        ?>
            <div class="no-results">
                <p>Ничего не найдено</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="contact">
      <div class="container">
        <h1>Не знаете, что выбрать?</h1>
        <p>Мы с удовольствием подскажем! Пожалуйста, введите свои данные в форму ниже</p>
        <?php include 'includes/contact-form.php'; ?>
      </div>
    </section>

<?php include 'includes/footer.php'; ?>

<script>
function updateSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    
    // Preserve all current filter values
    const currentSearch = document.querySelector('input[name="search"]').value;
    if (currentSearch) {
        url.searchParams.set('search', currentSearch);
    }
    
    window.location.href = url.toString();
}

function applyFilters() {
    const url = new URL(window.location.href);
    
    // Get current search and sort values
    const currentSearch = document.querySelector('input[name="search"]').value;
    const currentSort = document.querySelector('.sort-select').value;
    
    // Get all filter values
    const age = document.querySelector('input[name="age"]:checked').value;
    const price = document.querySelector('input[name="price"]:checked').value;
    const children = document.querySelector('input[name="children"]:checked').value;
    const duration = document.querySelector('input[name="duration"]:checked').value;
    
    // Update URL parameters
    if (currentSearch) url.searchParams.set('search', currentSearch);
    if (currentSort !== 'default') url.searchParams.set('sort', currentSort);
    if (age !== 'any') url.searchParams.set('age', age);
    if (price !== 'any') url.searchParams.set('price', price);
    if (children !== 'any') url.searchParams.set('children', children);
    if (duration !== 'any') url.searchParams.set('duration', duration);
    
    // Remove 'any' parameters to keep URL clean
    if (age === 'any') url.searchParams.delete('age');
    if (price === 'any') url.searchParams.delete('price');
    if (children === 'any') url.searchParams.delete('children');
    if (duration === 'any') url.searchParams.delete('duration');
    
    // Reload page with new filters
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.querySelector('.filter-toggle');
    const filterPanel = document.querySelector('.filter-panel');
    const closeFilter = document.querySelector('.close-filter');
    const applyFiltersBtn = document.querySelector('.apply-filters-btn');

    filterToggle.addEventListener('click', function() {
        filterPanel.classList.add('active');
    });

    closeFilter.addEventListener('click', function() {
        filterPanel.classList.remove('active');
    });

    applyFiltersBtn.addEventListener('click', applyFilters);

    // Set initial filter values from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('age')) document.querySelector(`input[name="age"][value="${urlParams.get('age')}"]`).checked = true;
    if (urlParams.has('price')) document.querySelector(`input[name="price"][value="${urlParams.get('price')}"]`).checked = true;
    if (urlParams.has('children')) document.querySelector(`input[name="children"][value="${urlParams.get('children')}"]`).checked = true;
    if (urlParams.has('duration')) document.querySelector(`input[name="duration"][value="${urlParams.get('duration')}"]`).checked = true;
});
</script>
</body>
</html>