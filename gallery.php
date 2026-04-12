<?php
require_once 'includes/db.php';

$query = "SELECT * FROM photos ORDER BY created_time DESC LIMIT 20";
$result = mysqli_query($link, $query);
$photos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
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
            <?php if (count($photos) > 0): ?>
                <?php foreach ($photos as $index => $photo): ?>
                    <div
                        class="gallery-item"
                        role="button"
                        tabindex="0"
                        data-gallery-index="<?= (int) $index ?>"
                        aria-label="Открыть фото в полном размере">
                        <img src="<?= htmlspecialchars($photo['path']) ?>" alt="Фото из галереи" loading="lazy" />
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>В галерее пока нет фотографий.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (count($photos) > 0): ?>
<div
    id="gallery-lightbox"
    class="gallery-lightbox"
    hidden
    role="dialog"
    aria-modal="true"
    aria-label="Просмотр фотографий">
    <div class="gallery-lightbox__backdrop" data-lightbox-close></div>
    <div class="gallery-lightbox__body">
        <button type="button" class="gallery-lightbox__close" aria-label="Закрыть">&times;</button>
        <button type="button" class="gallery-lightbox__nav gallery-lightbox__prev" aria-label="Предыдущее фото">‹</button>
        <div class="gallery-lightbox__frame">
            <img class="gallery-lightbox__img" src="" alt="Фото в полном размере" decoding="async" />
            <p class="gallery-lightbox__counter" aria-live="polite"></p>
        </div>
        <button type="button" class="gallery-lightbox__nav gallery-lightbox__next" aria-label="Следующее фото">›</button>
    </div>
</div>
<script>
(function () {
    const paths = <?= json_encode(array_column($photos, 'path'), JSON_UNESCAPED_UNICODE) ?>;
    const root = document.getElementById("gallery-lightbox");
    if (!root || !paths.length) return;

    const img = root.querySelector(".gallery-lightbox__img");
    const counter = root.querySelector(".gallery-lightbox__counter");
    const btnPrev = root.querySelector(".gallery-lightbox__prev");
    const btnNext = root.querySelector(".gallery-lightbox__next");
    const btnClose = root.querySelector(".gallery-lightbox__close");

    let index = 0;

    function show() {
        img.src = paths[index];
        counter.textContent = index + 1 + " / " + paths.length;
    }

    function open(i) {
        index = ((i % paths.length) + paths.length) % paths.length;
        root.classList.toggle("gallery-lightbox--single", paths.length < 2);
        root.hidden = false;
        document.body.classList.add("gallery-lightbox-open");
        show();
        btnClose.focus({ preventScroll: true });
    }

    function close() {
        root.hidden = true;
        document.body.classList.remove("gallery-lightbox-open");
        img.removeAttribute("src");
    }

    function prev() {
        index = (index - 1 + paths.length) % paths.length;
        show();
    }

    function next() {
        index = (index + 1) % paths.length;
        show();
    }

    document.querySelectorAll(".gallery-item[data-gallery-index]").forEach(function (el) {
        el.addEventListener("click", function () {
            open(parseInt(el.getAttribute("data-gallery-index"), 10));
        });
        el.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                open(parseInt(el.getAttribute("data-gallery-index"), 10));
            }
        });
    });

    btnPrev.addEventListener("click", function (e) {
        e.stopPropagation();
        prev();
    });
    btnNext.addEventListener("click", function (e) {
        e.stopPropagation();
        next();
    });
    btnClose.addEventListener("click", function (e) {
        e.stopPropagation();
        close();
    });
    root.querySelectorAll("[data-lightbox-close]").forEach(function (el) {
        el.addEventListener("click", close);
    });

    document.addEventListener("keydown", function (e) {
        if (root.hidden) return;
        if (e.key === "Escape") close();
        else if (e.key === "ArrowLeft") prev();
        else if (e.key === "ArrowRight") next();
    });

    let touchStartX = 0;
    root.addEventListener(
        "touchstart",
        function (e) {
            touchStartX = e.changedTouches[0].screenX;
        },
        { passive: true },
    );
    root.addEventListener("touchend", function (e) {
        if (root.hidden) return;
        const x = e.changedTouches[0].screenX;
        const d = x - touchStartX;
        if (d > 60) prev();
        else if (d < -60) next();
    });
})();
</script>
<?php endif; ?>

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