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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
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
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
