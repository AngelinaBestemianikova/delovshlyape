<script>
    let count = 0;
    document.addEventListener("DOMContentLoaded", function () {
        // Check if user is already logged in
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    window.location.href = 'profile.php';
                }
            });

        const form = document.getElementById('login-form');
        const inputs = {
            email: document.querySelector("input[name='email']"),
            'first-password': document.querySelector("input[name='first-password']")
        };

        const errors = {
            email: document.getElementById("email-error"),
            password: document.getElementById("password-error")
        };

        const submitButton = document.querySelector('input[type="submit"]');

        function validateInput(fieldName, isSubmit = false) {
            let formData = new FormData(form);
            formData.append('ajax', '1');
            formData.append('validate_field', fieldName);
            if (isSubmit) {
                formData.append('is_submit', 'true');
            }

            return new Promise((resolve) => {
                let xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        let response = JSON.parse(xhr.responseText);
                        resolve(response);
                    }
                };

                xhr.send(formData);
            });
        }

        function updateErrorDisplay(fieldName, errorMessage) {
            // Hide all errors first
            Object.values(errors).forEach(el => el.classList.remove('show'));
            Object.values(inputs).forEach(input => input.classList.remove('error-input'));

            // Show error for the specific field if it exists
            if (errorMessage && errors[fieldName]) {
                errors[fieldName].textContent = errorMessage;
                errors[fieldName].classList.add('show');
                inputs[fieldName].classList.add('error-input');
            }
        }

        function checkAllFields() {
            validateInput('all').then(response => {
                const hasErrors = response.errors && Object.keys(response.errors).length > 0;
                submitButton.disabled = hasErrors;
                submitButton.classList.toggle('disabled', hasErrors);
            });
        }

        // Form submission handler
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            validateInput('all', true).then(response => {
                if (response.passed) {
                    if (count < 1) {
                        alert("Вы успешно вошли!");
                        count++;
                    }
                    window.location.href = 'profile.php';
                } else {
                    // Show all errors on submit
                    Object.entries(response.errors || {}).forEach(([field, error]) => {
                        updateErrorDisplay(field, error);
                    });
                }
            });
        });

        // Real-time validation for each input
        Object.entries(inputs).forEach(([fieldName, input]) => {
            input.addEventListener("input", () => {
                validateInput(fieldName).then(response => {
                    updateErrorDisplay(fieldName, response.errors?.[fieldName]);
                    checkAllFields();
                });
            });
        });

        // Initial button state check
        checkAllFields();
    });
</script>