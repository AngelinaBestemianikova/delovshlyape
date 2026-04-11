<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<script src="./js/preloader.js"></script>
<footer>
  <div class="container">
    <div class="footer-content">
      <div class="footer_links">
        <img src="images/logo_footer.png" alt="Logo" class="logo-img" />
        <div class="footer-links">
          <h3>Ссылки:</h3>
          <a href="#main">Главная</a>
          <a href="#about">О нас</a>
          <a href="#programs">Программы</a>
          <a href="booking.php">Забронировать</a>
          <a href="#contact">Контакты</a>
        </div>
      </div>

      <div class="footer-info">
        <div class="address-block">
          <h3>Адрес:</h3>
          <p>пр-т Победителей 39,<br>Минск, Беларусь</p>
        </div>

        <div class="contacts-block">
          <h3>Контакты:</h3>
          <p>+375 (44) 823-26-78<br>info@delovshlyape.com</p>
        </div>

        <div class="social-links social-links-footer">
          <div class="social-icons">
            <a href="#" class="social-icon">
              <img src="images/telegram.svg" alt="Telegram">
            </a>
            <a href="#" class="social-icon">
              <img src="images/facebook.svg" alt="Facebook">
            </a>
            <a href="#" class="social-icon">
              <img src="images/instagram.svg" alt="Instagram">
            </a>
            <a href="#" class="social-icon">
              <img src="images/twitter.svg" alt="TikTok">
            </a>
            <a href="#" class="social-icon">
              <img src="images/vk.svg" alt="VK">
            </a>
            <a href="#" class="social-icon">
              <img src="images/viber.svg" alt="YouTube">
            </a>
          </div>
        </div>
      </div>
      <div class="footer-map">
        <div id="footerMap"></div>
      </div>
    </div>
  </div>
</footer>
<script type="text/javascript">
  ymaps.ready(function () {
    var officeCoords = [53.922037, 27.527375];

    var footerMap = new ymaps.Map("footerMap", {
      center: officeCoords,
      zoom: 15,
      controls: ['zoomControl']
    }, {
      autoFitToViewport: 'always'
    });

    // Важно: принудительно обновляем размер через мгновение после создания
    setTimeout(function () {
      footerMap.container.fitToViewport();
    }, 100);

    var officePlacemark = new ymaps.Placemark(officeCoords, {
      balloonContentHeader: 'Дело в шляпе',
      balloonContentBody: 'пр-т Победителей 39, Минск',
      hintContent: 'Нажмите, чтобы проложить маршрут'
    }, {
      preset: 'islands#redDotIconWithCaption'
    });

    officePlacemark.events.add('click', function () {
      window.open('https://yandex.by/maps/-/CPrOaH5~', '_blank');
    });

    footerMap.geoObjects.add(officePlacemark);

    window.addEventListener('resize', function () {
      footerMap.container.fitToViewport();
    });
  });
</script>