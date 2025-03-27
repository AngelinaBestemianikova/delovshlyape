<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Незабываемые праздники для ваших детей</title>
  <link rel="stylesheet" href="style_main.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
    rel="stylesheet" />
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <main>
    <section class="main">
      <div class="main-content">
        <h1>Незабываемые праздники <br>для ваших детей</h1>
        <p>
          Добро пожаловать в наш мир волшебных праздников! Мы создаем самые
          незабываемые детские праздники. Наполняем их радостью, смехом и
          бесконечным весельем. Присоединяйтесь!
        </p>
        <button class="primary-button">Выбрать программу</button>
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
          <div class="program-card">
            <img src="images/image.png" alt="Сказочный мир" />
            <h3>Сказочный мир для ваших детей (1-10 лет)</h3>
            <p>Погрузитесь в волшебство с нашими аниматорами!</p>
            <button class="secondary-button">Подробнее</button>
          </div>
          <div class="program-card">
            <img src="images/image-1.png" alt="Тематические вечеринки" />
            <h3>Тематические вечеринки для подростков (11+)</h3>
            <p>Создайте незабываемые воспоминания с друзьями!</p>
            <button class="secondary-button">Подробнее</button>
          </div>
          <div class="program-card">
            <img src="images/image-2.png" alt="Особые мероприятия" />
            <h3>Особые мероприятия (выпускной вечер и т.д.)</h3>
            <p>Незабываемый и торжественный вечер для всех!</p>
            <button class="secondary-button">Подробнее</button>
          </div>
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
        <div class="review-content">
          <p>Празднование дня рождения нашей дочери прошло с огромным успехом благодаря потрясающей команде «Дело
            в шляпе!». Они сделали все возможное, чтобы создать волшебный праздник, который наша дочь и ее друзья
            никогда не забудут. Спасибо вам!
          <div class="reviewer">
            <img src="images/review.png" alt="reviewer" class="reviewer-img">
            <p>Наталья, мама 12-летней Насти</p>
          </div>
        </div>
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
      <div class="container">
        <h1>Остались вопросы?</h1>
        <p>Мы с удовольствием подскажем! Пожалуйста, введите свои данные в форму ниже</p>
        <form class="contact-form">
          <div class="form-row">
            <input type="text" placeholder="Имя*" required>
            <input type="text" placeholder="Фамилия">
          </div>
          <div class="form-row">
            <input type="email" placeholder="Эл. почта*" required>
            <input type="tel" placeholder="Телефон*" required>
          </div>
          <textarea placeholder="Ваш вопрос*" required></textarea>
          <div class="form-disclaimer">
            <button type="submit" class="secondary-button">Отправить</button>
            <p>Нажимая на кнопку, вы принимаете условия <br><a href="#">пользовательского соглашения</a> и <a
                href="#">политики конфиденциальности</a></p>
          </div>
        </form>
      </div>
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