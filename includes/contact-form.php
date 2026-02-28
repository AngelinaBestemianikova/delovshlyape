<form class="contact-form" onsubmit="return handleSubmit(event)">
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

<script>
    function handleSubmit(event) {
        event.preventDefault();
        alert('Ваше сообщение отправлено!');
        event.target.reset();
        return false;
    }
</script>