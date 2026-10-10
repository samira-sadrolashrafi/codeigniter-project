
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('registerForm') ||
        document.getElementById('loginForm');

    if (!form) {
        return;
    }

    const isRegister = form.id === 'registerForm';

    const messageBox = document.getElementById(
        isRegister ? 'registerMessage' : 'loginMessage'
    );

    const submitButton = form.querySelector('button[type="submit"]');

    const errorFields = isRegister
        ? ['username_err', 'email_err', 'password_err', 'confirm_password_err']
        : ['username_err', 'password_err'];


    // Display general message
    function showMessage(message, type) {
        messageBox.textContent = message;
        messageBox.className = 'alert alert-' + type;
    }


    // Clear previous errors
    function clearErrors() {
        errorFields.forEach(function (field) {
            document.getElementById(field).textContent = '';
        });

        messageBox.textContent = '';
        messageBox.className = 'alert d-none';
    }


    // Submit form using AJAX
    form.addEventListener('submit', async function (e) {

        e.preventDefault();

        clearErrors();

        const buttonText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.textContent = 'در حال ارسال...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();


            // Successful request
            if (response.ok && result.success) {

                showMessage(result.message, 'success');

                if (form.dataset.successUrl) {
                    setTimeout(function () {
                        window.location.href = form.dataset.successUrl;
                    }, 1200);
                }

                return;
            }


            // Display validation errors
            let hasFieldError = false;

            if (result.errors) {

                Object.keys(result.errors).forEach(function (field) {

                    const errorElement = document.getElementById(field);

                    if (errorElement && result.errors[field]) {
                        errorElement.textContent = result.errors[field];
                        hasFieldError = true;
                    }

                });
            }


            // Display other errors
            if (!hasFieldError) {
                showMessage(result.message || 'خطایی رخ داده است.', 'danger');
            }

        } catch (error) {

            showMessage(
                'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.',
                'danger'
            );

        } finally {

            submitButton.disabled = false;
            submitButton.textContent = buttonText;

        }

    });

});
