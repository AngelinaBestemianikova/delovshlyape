<?php
$u_name = $_SESSION['name'] ?? '';
$u_last = $_SESSION['lastname'] ?? '';
$u_email = $_SESSION['email'] ?? '';
$u_phone = $_SESSION['phone'] ?? '';
?>

<form class="contact-form" id="contact-ajax-form">
    <div class="form-row">
        <div class="box-input">
            <input class="input" type="text" name="first_name" placeholder="Имя*"
                value="<?= htmlspecialchars($u_name) ?>" required>
            <span id="name-error" class="error"></span>
        </div>
        <div class="box-input">
            <input class="input" type="text" name="last_name" placeholder="Фамилия"
                value="<?= htmlspecialchars($u_last) ?>">
            <span id="lastname-error" class="error"></span>
        </div>
    </div>
    <div class="form-row">
        <div class="box-input">
            <input class="input" type="email" name="email" placeholder="Эл. почта*"
                value="<?= htmlspecialchars($u_email) ?>" required>
            <span id="email-error" class="error"></span>
        </div>
        <div class="box-input">
            <input class="input" type="tel" name="phone" placeholder="+375XXXXXXXXX*"
                value="<?= htmlspecialchars($u_phone) ?>" required>
            <span id="phone-error" class="error"></span>
        </div>
    </div>
    <div class="box-input">
        <textarea class="input" name="question" placeholder="Ваш вопрос*" required
            style="min-height: 120px;"></textarea>
        <span id="question-error" class="error"></span>
    </div>

    <div class="form-disclaimer">
        <button type="submit" id="contact-submit" class="button secondary-button">Отправить</button>
        <p>Нажимая на кнопку, вы принимаете условия <br>
            <a href="#">пользовательского соглашения</a> и <a href="#">политики конфиденциальности</a>
        </p>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById('contact-ajax-form');
        const submitBtn = document.getElementById('contact-submit');

        // Функция валидации
        async function validate(fieldName, isSubmit = false) {
            let formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('validate_field', fieldName);
            if (isSubmit) formData.append('is_submit', 'true');

            const res = await fetch('./send_message.php', { method: 'POST', body: formData });
            return await res.json();
        }

        function updateFieldStatus(fieldName, errorMessage) {
            const errMap = {
                first_name: 'name-error',
                last_name: 'lastname-error',
                email: 'email-error',
                phone: 'phone-error',
                question: 'question-error'
            };

            const errorSpan = document.getElementById(errMap[fieldName]);
            const inputField = form.querySelector(`[name="${fieldName}"]`);

            if (errorSpan && inputField) {
                if (errorMessage) {
                    errorSpan.textContent = errorMessage;
                    errorSpan.classList.add('show');
                    inputField.classList.add('error-input');
                } else {
                    errorSpan.classList.remove('show');
                    inputField.classList.remove('error-input');
                }
            }
        }

        // Живая проверка при вводе
        form.querySelectorAll('.input').forEach(input => {
            input.addEventListener('input', async () => {
                const response = await validate(input.name);
                updateFieldStatus(input.name, response.errors[input.name]);

                // Проверяем все поля для активации кнопки
                const hasErrors = response.errors && Object.keys(response.errors).length > 0;
                submitBtn.disabled = hasErrors;
                submitBtn.classList.toggle('disabled', hasErrors);
            });
        });

        // Отправка формы
        form.onsubmit = async (e) => {
            e.preventDefault();
            const response = await validate('all', true);

            if (response.passed) {
                alert("Ваше сообщение успешно отправлено!");
                form.reset();
                // Очищаем классы ошибок после сброса
                form.querySelectorAll('.input').forEach(el => el.classList.remove('error-input'));
            } else {
                // Если есть ошибки при попытке отправить — подсвечиваем все
                Object.entries(response.errors).forEach(([field, msg]) => {
                    updateFieldStatus(field, msg);
                });
            }
        };
    });
</script>