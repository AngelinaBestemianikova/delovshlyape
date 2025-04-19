<?php
session_start();
include __DIR__ . './includes/db.php';

// if (isset($_SESSION['name'])) {
//     header('Location: profile.php'); 
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    function clearString($str)
    {
        return stripslashes(strip_tags(trim($str ?? '')));
    }

    $response = ["passed" => true, "errors" => []];

    $first_name = clearString($_POST["first_name"]);
    $last_name = clearString($_POST["last_name"]);
    $email = clearString($_POST["email"]);
    $firstPassword = clearString($_POST["first-password"]);
    $secondPassword = clearString($_POST["second-password"]);
    $phone = clearString($_POST["phone"]);

    // Validate only the specified field if provided
    $validate_field = $_POST['validate_field'] ?? 'all';
    $is_submit = isset($_POST['is_submit']) && $_POST['is_submit'] === 'true';

    if ($validate_field === 'all' || $validate_field === 'first_name') {
        if (!preg_match('/^[А-ЯЁа-яё]{2,}$/u', $first_name)) {
            $response["errors"]["first_name"] = "Имя должно содержать только буквы.";
            $response["passed"] = false;
        }
    }

    if ($validate_field === 'all' || $validate_field === 'last_name') {
        if (!preg_match('/^[А-ЯЁа-яё]{2,}$/u', $last_name)) {
            $response["errors"]["last_name"] = "Фамилия должна содержать только буквы.";
            $response["passed"] = false;
        }
    }

    if ($validate_field === 'all' || $validate_field === 'email') {
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i', $email)) {
            $response["errors"]["email"] = "Введенная почта не соответствует требованиям.";
            $response["passed"] = false;
        } else {
            $query = "SELECT id FROM users WHERE email='$email'";
            $result = mysqli_query($link, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $response["errors"]["email"] = "Email уже зарегистрирован.";
                $response["passed"] = false;
            }
        }
    }

    if ($validate_field === 'all' || $validate_field === 'first-password') {
        if (strlen($firstPassword) < 4 || preg_match('/[#$%^&_=+-]/', $firstPassword)) {
            $response["errors"]["first-password"] = "Пароль должен содержать минимум 4 символов и не включать спецсимволы.";
            $response["passed"] = false;
        }
    }

    if ($validate_field === 'all' || $validate_field === 'second-password') {
        if ($firstPassword !== $secondPassword) {
            $response["errors"]["second-password"] = "Пароли не совпадают.";
            $response["passed"] = false;
        }
    }

    if ($validate_field === 'all' || $validate_field === 'phone') {
        if (!preg_match('/^\+375(24|25|29|33|44)\d{7}$/', $phone)) {
            $response["errors"]["phone"] = "Номер телефона должен быть в формате +375XXXXXXXXX (например, +375291234567)";
            $response["passed"] = false;
        }
    }

    // Only perform registration on form submission
    if ($is_submit && $validate_field === 'all' && $response["passed"]) {
        $password_salt = mt_rand(100, 999);
        $password_hash = md5(md5($firstPassword) . $password_salt);
        $query = "INSERT INTO users (first_name, last_name, email, phone, password_hash, password_salt) VALUES ('$first_name', '$last_name', '$email', '$phone', '$password_hash', '$password_salt')";

        if (mysqli_query($link, $query)) {
            $client_id = mysqli_insert_id($link);

            $_SESSION['name'] = $first_name;
            $_SESSION['lastname'] = $last_name;
            $_SESSION['client_id'] = $client_id;
        } else {
            $response["errors"]["database"] = "Ошибка при сохранении данных.";
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
    <title>Регистрация</title>
</head>

<?php
include __DIR__ . './elements/auth.php';
?>

<body>
    <?php include './includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <h2>Регистрация</h2>
            <div class="form-wrapper">
                <form id="login-form" class="form">
                    <input type="hidden" name="form-submitted" value="true">
                    <div class="box-input">
                        <input class="input" name="first_name" type="text" required>
                        <label>Введите имя</label>
                        <span id="name-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input class="input" name="last_name" type="text" required>
                        <label>Введите фамилию</label>
                        <span id="lastname-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input class="input" name="email" type="text" required>
                        <label>Введите E-mail</label>
                        <span id="email-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input class="input" name="first-password" type="password" required>
                        <label>Придумайте пароль</label>
                        <span id="password-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input class="input" name="second-password" type="password" required>
                        <label>Повторите пароль</label>
                        <span id="secondpassword-error" class="error"></span>
                    </div>
                    <div class="box-input">
                        <input placeholder="375" class="input" name="phone" type="text" required>
                        <label>Введите номер телефона</label>
                        <span id="phone-error" class="error"></span>
                    </div>
                    <input type="submit" class="button" value="Зарегистрироваться">
                    <a href="./login.php" class="enterOnAuth">Уже есть аккаунт? Войти</a>
                </form>
            </div>
        </div>
    </section>

    <?php include './includes/footer.php'; ?>
</body>

</html>