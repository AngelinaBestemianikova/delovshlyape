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
            <div class="gallery-item">
                <img src="images/image_gallery.png" alt="Праздник 1" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-1.png" alt="Праздник 2" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-2.png" alt="Праздник 3" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-3.png" alt="Праздник 4" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-4.png" alt="Праздник 5" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-5.png" alt="Праздник 6" />
            </div>
            <div class="gallery-item">
                <img src="images/image_gallery-6.png" alt="Праздник 7" />
            </div>
        </div>
    </div>
</section>

<section class="gallery_end">
    <div class="container">
        <div class="gallery_end-text">
            <h1>А вы Готовы устроить незабываемый праздник?</h1>
            <p>ТНаши профессиональные аниматоры и организаторы позаботятся обо всех деталях, чтобы ваш праздник стал ярким и запоминающимся. Познакомьтесь с нашими программами и выберите то, что идеально подойдет вашему маленькому имениннику!
        </div>
        <button class="primary-button">Выбрать программу</button>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>