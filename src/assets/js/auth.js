document.addEventListener('DOMContentLoaded', function () {

    // Find the registration and login forms
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const logoutForm = document.getElementById('logoutForm');


    // Handle the registration form
    if (registerForm)
        {setupForm(registerForm,'registerMessage',
            [
                'username_err',
                'email_err',
                'password_err',
                'confirm_password_err'
            ]
        );
    }


    // Handle the login form
    if (loginForm)
        {setupForm(loginForm,'loginMessage',
            [
                'username_err',
                'password_err'
            ]
        );
    }

    if (logoutForm) {
        setupForm(logoutForm, 'logoutMessage', []);
    }


    // Set up the selected form
    function setupForm(form, messageBoxId, errorFields) {

        const messageBox = document.getElementById(messageBoxId);
        const submitButton = form.querySelector('button[type="submit"]');


        // Display a general message
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


        // Send the form using AJAX
        form.addEventListener('submit', async function (event) {

            event.preventDefault();

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


                // If the request is successful
                if (response.ok && result.success) {

                    showMessage(result.message, 'success');

                    if (form.dataset.successUrl) {
                        setTimeout(function () {
                            window.location.href = form.dataset.successUrl;
                        }, 1200);
                    }

                    return;
                }


                // Display field errors
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


                // Display a general error if needed
                if (!hasFieldError) {
                    showMessage(
                        result.message || 'خطایی رخ داده است.',
                        'danger'
                    );
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

    }

});
