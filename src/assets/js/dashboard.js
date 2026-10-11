document.addEventListener('DOMContentLoaded', function () {

    loadBalance();

    const form = document.getElementById('balanceForm');
    const logoutButton = document.getElementById('logoutButton');
    const balanceInput = document.getElementById('balanceInput');

    if (balanceInput) {
        balanceInput.addEventListener('input', formatBalanceInput);
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', logout);
    }

    if (form) {

        form.addEventListener('submit', function (e) {

            e.preventDefault();

            updateBalance();

        });

    }

});


function logout() {
    const button = document.getElementById('logoutButton');
    button.disabled = true;

    fetch('/auth/logout', {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/login';
                return;
            }

            showMessage(data.message || 'خروج از حساب انجام نشد.', 'danger');
            button.disabled = false;
        })
        .catch(() => {
            showMessage('خطا در خروج از حساب.', 'danger');
            button.disabled = false;
        });
}


function loadBalance() {
    fetch('/api/dashboard/balance', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                document.getElementById('balanceAmount').innerText =
                    formatAmount(data.balance) + ' تومان';

            } else {

                showMessage(data.message, 'danger');

            }

        })

        .catch(() => {

            showMessage(
                'خطا در دریافت موجودی.',
                'danger'
            );

        });
}


function updateBalance() {
    const balance =
        document.getElementById('balanceInput').value.replace(/,/g, '');


    const formData = new FormData();

    formData.append(
        'balance',
        balance
    );


    fetch('/api/dashboard/balance', {
        method: 'POST',
        body: formData

    })

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(
                    data.message,
                    'success'
                );
                loadBalance();
                document.getElementById('balanceInput').value = '';
            } else {
                showMessage(
                    data.message,
                    'danger'
                );
            }
        })

        .catch(() => {
            showMessage(
                'خطا در تغییر موجودی.',
                'danger'
            );
        });
}

function formatBalanceInput(event) {
    const input = event.target;
    const rawValue = input.value;
    const caret = input.selectionStart;
    const digitsBeforeCaret = rawValue.slice(0, caret).replace(/[^0-9]/g, '').length;

    let value = rawValue.replace(/[^0-9.\-]/g, '');
    const isNegative = value.charAt(0) === '-';
    value = value.replace(/-/g, '').replace(/\.(?=.*\.)/g, '');

    const parts = value.split('.', 2);
    const integer = parts[0].replace(/^0+(?=\d)/, '');
    const fraction = parts.length > 1 ? '.' + parts[1].slice(0, 2) : '';
    const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const formatted = (isNegative ? '-' : '') + formattedInteger + fraction;

    input.value = formatted;
    let newCaret = 0;
    let digitCount = 0;
    while (newCaret < formatted.length && digitCount < digitsBeforeCaret) {
        if (/\d/.test(formatted[newCaret])) digitCount++;
        newCaret++;
    }
    input.setSelectionRange(newCaret, newCaret);
}

function formatAmount(amount) {
    const value = String(amount);
    const parts = value.split('.', 2);
    const integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = parts.length > 1 && !/^0+$/.test(parts[1])
        ? '.' + parts[1]
        : '';
    return integer + fraction;
}


function showMessage(message, type) {

    const box =
        document.getElementById('dashboardMessage');

    box.className =
        'alert alert-' + type;

    box.innerText =
        message;

    box.classList.remove('d-none');

}
