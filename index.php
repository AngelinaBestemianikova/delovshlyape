<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Незабываемые праздники для ваших детей</title>
  <link rel="stylesheet" href="style/general.css" />
  <link rel="stylesheet" href="style/style_main.css" />
  <link rel="stylesheet" href="style/contact.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
    rel="stylesheet" />
</head>

<body>
  <?php 
  include 'includes/header.php';
  require_once 'includes/db.php';
  
  // Fetch program types
  $programs_query = "SELECT * FROM program_types ORDER BY id";
  $programs_result = mysqli_query($link, $programs_query);
  
  // Fetch latest review
  $review_query = "SELECT r.*, u.first_name as name, u.path_image as avatar 
                   FROM reviews r 
                   JOIN users u ON r.user_id = u.id 
                   ORDER BY r.created_time DESC 
                   LIMIT 1";
  $review_result = mysqli_query($link, $review_query);
  $latest_review = mysqli_fetch_assoc($review_result);
  ?>

  <main>
    <section class="main">
      <div class="main-content">
        <h1>Незабываемые праздники <br>для ваших детей</h1>
        <p>
          Добро пожаловать в наш мир волшебных праздников! Мы создаем самые
          незабываемые детские праздники. Наполняем их радостью, смехом и
          бесконечным весельем. Присоединяйтесь!
        </p>
        <button class="primary-button" onclick="window.location.href='programs.php'">Выбрать программу</button>
      </div>
    </section>

    <section id="about" class="about">
      <div class="characters">
        <img src="images/heros.png" alt="Персонажи" class="characters-img" />
      </div>
      <div class="container">
        <h1>Немного о нас</h1>
        <div class="about-content">
          <p>
            Мы - команда профессионалов, которые превращают обычные дни <br>в
            незабываемые праздники!
          </p>
          <p>
            С нами ваши дети будут смеяться, играть и радоваться, <br>как никогда
            раньше!
          </p>
          <p>
            Мы знаем, как сделать праздник, который запомнится на всю жизнь!
          </p>
        </div>
    </section>

    <section id="programs" class="programs">
      <div class="container">
        <h1>Программы</h1>
        <p class="programs-description">Выберите идеальную программу для вашего ребенка!</p>
        <div class="program-cards">
          <?php if (mysqli_num_rows($programs_result) > 0): ?>
            <?php while ($program = mysqli_fetch_assoc($programs_result)): ?>
              <div class="program-card">
                <img src="<?php echo htmlspecialchars($program['path_image']); ?>" alt="" />
                <h3><?php echo htmlspecialchars($program['name']); ?></h3>
                <p><?php echo htmlspecialchars($program['description']); ?></p>
                <button class="secondary-button" onclick="window.location.href='programs.php#program-type-<?php echo $program['id']; ?>'">Подробнее</button>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="features">
      <div class="container">
        <div class="features-list">
          <h1>В каждую программу включено</h1>
          <ul>
            <li>4 часа развлекательной программы</li>
            <li>Профессиональный аниматор для различных игр</li>
            <li>Аренда праздничного зала на 3 часа</li>
            <li>Оформление зала в тематике праздника</li>
            <li>Фотосьемка мероприятия в течение 1 часа</li>
            <li>Тематическая фотозона</li>
            <li>Наш подарок имениннику</li>
          </ul>
        </div>
      </div>
    </section>

    <section id="gallery" class="gallery">
      <div class="container">
        <h1>Праздник в объективе</h1>
        <p>Окунитесь в атмосферу наших незабываемых праздников, запечатленных в ярких фотографиях!</p>
        <div class="gallery-grid">
          <div class="gallery-item text-box">
            <h3>Мы стремимся к идеалу!</h3>
          </div>
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
          <div class="gallery-item text-box">
            <h3>Будь в этот день с нами!</h3>
          </div>
        </div>
      </div>
    </section>

    <section class="reviews">
      <div class="container">
        <h1>Последний отзыв</h1>
        <?php if ($latest_review): ?>
          <div class="review-content">
            <p><?php echo htmlspecialchars($latest_review['comment']); ?></p>
            <div class="reviewer">
              <img src="<?php echo htmlspecialchars($latest_review['avatar']); ?>" alt="<?php echo htmlspecialchars($latest_review['name']); ?>" class="reviewer-img">
              <p><?php echo htmlspecialchars($latest_review['name']); ?></p>
            </div>
          </div>
        <?php else: ?>
          <p>Пока нет отзывов</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="faq">
      <div class="container">
        <h1>Спрашивали? Отвечаем!</h1>
        <div class="faq-list">
          <div class="faq-item">
            <div class="faq-question">
              <h3>Какие конкретно услуги вы предоставляете?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Мы предлагаем широкий спектр услуг по организации детских праздников любой сложности. Разработка
                сценария и программы мероприятия с учетом возраста, интересов и пожеланий именинника. Подбор и аренда
                подходящей площадки. Оформление пространства в соответствии с выбранной тематикой. Организация питания.
                Привлечение профессиональных аниматоров, ведущих, фокусников, музыкантов и других артистов. Фото- и
                видеосъемка праздника. Мы берем на себя все хлопоты, чтобы вы могли полностью расслабиться и получить
                максимум удовольствия от праздника!</p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">
              <h3>Сколько по времени длится праздник?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Стандартная продолжительность праздника составляет 4 часа, но мы можем адаптировать программу под ваши
                пожелания. Для маленьких детей рекомендуем не более 4 часов, для детей постарше - до 6 часов.</p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">
              <h3>Сколько стоит организация детского праздника?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Стоимость организации праздника зависит от выбранной программы и количества гостей. Средняя стоимость
                программы - 1000 рублей. Мы подготовим для вас индивидуальное предложение с учетом всех пожеланий.</p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">
              <h3>Можно ли провести праздник на открытом воздухе?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Да, мы организуем праздники как в помещении, так и на открытом воздухе. У нас есть опыт проведения
                мероприятий в парках, на природе и на частных территориях. В случае плохой погоды мы всегда имеем
                запасной вариант в помещении.</p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">
              <h3>Возможна ли рассрочка или поэтапная оплата услуг?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Да, мы предлагаем гибкую систему оплаты. Вы можете внести предоплату для бронирования даты, а остальную
                сумму оплатить частями или перед мероприятием. Условия оплаты обсуждаются индивидуально.</p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question">
              <h3>Как гарантировать, что праздник понравится моему ребенку?</h3>
              <div class="faq-arrows">
                <img src="images/arrow.svg" alt="arrow" class="faq-arrow default">
                <img src="images/arrow-active.svg" alt="arrow" class="faq-arrow active">
              </div>
            </div>
            <div class="faq-answer">
              <p>Мы тщательно изучаем интересы и предпочтения вашего ребенка перед разработкой программы. Наши аниматоры
                - профессионалы с большим опытом работы с детьми разных возрастов. Мы также предоставляем гарантию
                удовлетворенности и всегда готовы адаптировать программу в процессе праздника.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="contact">
      <h1>Остались вопросы?</h1>
      <p>Мы с удовольствием подскажем! Пожалуйста, введите свои данные в форму ниже</p>
      <?php include 'includes/contact-form.php'; ?>
    </section>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const faqItems = document.querySelectorAll('.faq-item');

      faqItems.forEach(item => {
        item.addEventListener('click', () => {
          // Close all other items
          faqItems.forEach(otherItem => {
            if (otherItem !== item && otherItem.classList.contains('active')) {
              otherItem.classList.remove('active');
            }
          });

          // Toggle current item
          item.classList.toggle('active');
        });
      });
    });
  </script>
</body>

</html>