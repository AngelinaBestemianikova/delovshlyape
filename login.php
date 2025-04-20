<?php
session_start();
include __DIR__ . '/includes/db.php';

// Get redirect URL if set
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'profile.php';

if (isset($_SESSION['name'])) {
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    function clearString($str)
    {
        return stripslashes(strip_tags(trim($str ?? '')));
    }

    $response = ["passed" => true, "errors" => []];

    $email = clearString($_POST["email"]);
    $password = clearString($_POST["first-password"]);

    // Validate only the specified field if provided
    $validate_field = $_POST['validate_field'] ?? 'all';
    $is_submit = isset($_POST['is_submit']) && $_POST['is_submit'] === 'true';

    if ($validate_field === 'all' || $validate_field === 'email') {
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i', $email)) {
            $response["errors"]["email"] = "Введенная почта не соответствует требованиям.";
            $response["passed"] = false;
        } else {
            $query = "SELECT id FROM users WHERE email=?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!$result || mysqli_num_rows($result) === 0) {
                $response["errors"]["email"] = "Данная почта не зарегистрирована.";
                $response["passed"] = false;
            }
        }
    }

    if ($validate_field === 'all' || $validate_field === 'first-password') {
        if (strlen($password) < 4 || preg_match('/[#$%^&_=+-]/', $password)) {
            $response["errors"]["password"] = "Пароль должен содержать не менее 4 символов и не включать специальные символы.";
            $response["passed"] = false;
        } else if ($validate_field === 'all') {
            $query = "SELECT id, password_hash, password_salt FROM users WHERE email=?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                if (md5(md5($password) . $row['password_salt']) !== $row['password_hash']) {
                    $response["errors"]["password"] = "Неверный пароль.";
                    $response["passed"] = false;
                }
            }
        }
    }

    // Only perform login on form submission
    if ($is_submit && $validate_field === 'all' && $response["passed"]) {
        $query = "SELECT id, first_name, last_name FROM users WHERE email=?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $_SESSION["name"] = $row['first_name'];
            $_SESSION["lastname"] = $row['last_name'];
            $_SESSION["client_id"] = $row['id'];
            $response["redirect"] = $redirect;
        } else {
            $response["errors"]["database"] = "Ошибка при входе в систему.";
            $response["passed"] = false;
        }
    }

    mysqli_close($link);
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/general.css">
    <link rel="stylesheet" href="style/auth.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <title>Авторизация</title>
</head>

<?php
include __DIR__ . './elements/login.php';
?>

<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <h2>Авторизация</h2>
            <div class="form-wrapper">
                <form id="login-form" class="form">
                    <div class="box-input">
                        <input class="input" name="email" type="text" required>
                        <label>Введите email</label>
                        <span id="email-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input class="input" name="first-password" type="password" required>
                        <label>Введите пароль</label>
                        <span id="password-error" class="error"></span>
                    </div>
                    <input type="submit" class="button" value="Войти">
                    <a href="auth.php" class="enterOnAuth">Нет аккаунта? Зарегистрироваться</a>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>

</html>