<?php
session_start();
include __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information from database
$client_id = $_SESSION['client_id'];
$query = "SELECT first_name, last_name, email, phone FROM users WHERE id = '$client_id'";
$result = mysqli_query($link, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($link));
}

$user = mysqli_fetch_assoc($result);

if (!$user) {
    // If no user found, destroy session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user's bookings
$bookings_query = "
    SELECT b.*, p.name as program_name, 
           (SELECT COUNT(*) FROM reviews WHERE booking_id = b.id) as has_review
    FROM bookings b
    JOIN programs p ON b.program_id = p.id
    WHERE b.user_id = $client_id
    ORDER BY b.event_date DESC";
$bookings_result = mysqli_query($link, $bookings_query);

if (!$bookings_result) {
    die("Database query failed: " . mysqli_error($link));
}

$bookings = [];
while ($booking = mysqli_fetch_assoc($bookings_result)) {
    $event_date = new DateTime($booking['event_date']);
    $today = new DateTime();
    $is_past_event = $event_date < $today;
    $has_review = $booking['has_review'] > 0;
    
    $booking['event_date'] = $event_date->format('d.m.Y');
    $booking['is_past_event'] = $is_past_event;
    $booking['has_review'] = $has_review;
    
    $bookings[] = $booking;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get success message if exists
$success_message = $_SESSION['booking_success'] ?? null;
// Clear the message from session after getting it
unset($_SESSION['booking_success']);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/profile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Личный кабинет</title>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <h2>Личный кабинет</h2>
            <div class="profile-wrapper">
                <div class="profile-info">
                    <div class="info-item">
                        <span class="label">Имя:</span>
                        <span class="value"><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Фамилия:</span>
                        <span class="value"><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Телефон:</span>
                        <span class="value"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="?logout=1" class="logout-button">Выйти</a>
                </div>
            </div>

            <div class="bookings-section">
                <h2>Мои бронирования</h2>
                <?php if (empty($bookings)): ?>
                    <p class="no-bookings">У вас пока нет бронирований</p>
                <?php else: ?>
                    <div class="bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <h4><?php echo htmlspecialchars($booking['program_name']); ?></h4>
                                    <span class="booking-date"><?php echo htmlspecialchars($booking['event_date']); ?></span>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-detail-item">
                                        <span class="label">Именинник:</span>
                                        <span class="value"><?php echo htmlspecialchars($booking['child_name']); ?> (<?php echo htmlspecialchars($booking['child_age']); ?> лет)</span>
                                    </div>
                                    <div class="booking-detail-item">
                                        <span class="label">Место проведения:</span>
                                        <span class="value"><?php echo htmlspecialchars($booking['event_location']); ?></span>
                                    </div>
                                    <div class="booking-detail-item">
                                        <span class="label">Количество гостей:</span>
                                        <span class="value"><?php echo htmlspecialchars($booking['guest_count']); ?></span>
                                    </div>
                                    <?php if (!empty($booking['wishes'])): ?>
                                        <div class="booking-detail-item">
                                            <span class="label">Пожелания:</span>
                                            <span class="value"><?php echo htmlspecialchars($booking['wishes']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-actions">
                                    <?php if (!$booking['is_past_event']): ?>
                                        <button class="cancel-booking-btn" data-booking-id="<?php echo htmlspecialchars($booking['id']); ?>">Отменить бронь</button>
                                    <?php elseif (!$booking['has_review']): ?>
                                        <button class="leave-review-btn" data-booking-id="<?php echo htmlspecialchars($booking['id']); ?>">Оставить отзыв</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        <?php if ($success_message): ?>
        window.onload = function() {
            alert('<?php echo addslashes($success_message); ?>');
        };
        <?php endif; ?>
    </script>

    <!-- Модальное окно для отзыва -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Оставить отзыв</h2>
            <form id="reviewForm">
                <input type="hidden" id="bookingId" name="booking_id">
                <div class="form-group">
                    <label for="comment">Комментарий:</label>
                    <textarea id="comment" name="comment" rows="4" required></textarea>
                </div>
                <button type="submit" class="submit-review-btn">Отправить отзыв</button>
            </form>
        </div>
    </div>

    <script>
    // Обработка отмены бронирования
    document.querySelectorAll('.cancel-booking-btn').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'booking_id=' + bookingId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Произошла ошибка при отмене бронирования');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отмене бронирования');
            });
        });
    });

    // Обработка отзыва
    const modal = document.getElementById('reviewModal');
    const closeBtn = document.querySelector('.close');
    const reviewForm = document.getElementById('reviewForm');

    document.querySelectorAll('.leave-review-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('bookingId').value = this.dataset.bookingId;
            modal.style.display = 'block';
        });
    });

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    reviewForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Произошла ошибка при отправке отзыва');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при отправке отзыва');
        });
    }
    </script>
</body>

</html>