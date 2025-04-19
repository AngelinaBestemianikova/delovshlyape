<?php
require_once 'includes/db.php';

$query = "SELECT * FROM photos ORDER BY created_time DESC LIMIT 20";
$result = mysqli_query($link, $query);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Незабываемые праздники</title>
  <link rel="stylesheet" href="style/general.css">
  <link rel="stylesheet" href="style/gallery.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
    rel="stylesheet" />
</head>

<body>
<?php include 'includes/header.php'; ?>

<section id="gallery" class="gallery">
    <div class="container">
        <h1>Галерея</h1>
        <p>Добро пожаловать в нашу фотогалерею. Здесь вы увидите, как мы создаем настоящее волшебство!</p>
        <div class="gallery-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($photo = mysqli_fetch_assoc($result)): ?>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($photo['path']); ?>" alt="Праздник" />
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>В галерее пока нет фотографий.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="gallery_end">
    <div class="container">
        <div class="gallery_end-text">
            <h1>А вы Готовы устроить незабываемый праздник?</h1>
            <p>ТНаши профессиональные аниматоры и организаторы позаботятся обо всех деталях, чтобы ваш праздник стал ярким и запоминающимся. Познакомьтесь с нашими программами и выберите то, что идеально подойдет вашему маленькому имениннику!
        </div>
        <button class="primary-button" onclick="window.location.href='programs.php'">Выбрать программу</button>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>