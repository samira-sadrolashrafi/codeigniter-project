document.addEventListener('DOMContentLoaded', initializeDashboard);


function initializeDashboard() {
    loadBalance();

    const balanceForm = document.getElementById('balanceForm');
    const logoutButton = document.getElementById('logoutButton');
    const balanceInput = document.getElementById('balanceInput');
    const resetBalanceButton = document.getElementById('resetBalanceButton');

    if (balanceInput) {
        balanceInput.addEventListener('input', formatBalanceInput);
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', logout);
    }

    if (resetBalanceButton) {
        resetBalanceButton.addEventListener('click', resetBalance);
    }

    if (balanceForm) {
        balanceForm.addEventListener('submit', function (event) {
            event.preventDefault();
            updateBalance();
        });
    }
}


async function logout() {
    const button = document.getElementById('logoutButton');

    button.disabled = true;

    try {

        const response = await fetch('/auth/logout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message || 'خروج از حساب انجام نشد.', 'danger');
            button.disabled = false;
            return;
        }

        window.location.href = '/login';

    } catch (error) {

        showMessage('خطا در خروج از حساب.', 'danger');
        button.disabled = false;

    }
}


async function loadBalance() {
    try {

        const response = await fetch('/api/dashboard/balance', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message, 'danger');
            return;
        }

        renderBalance(data.balance);

    } catch (error) {

        showMessage('خطا در دریافت موجودی.', 'danger');

    }
}


function renderBalance(balance) {
    const balanceElement = document.getElementById('balanceAmount');
    const debtElement = document.getElementById('debtAmount');

    balance = Number(balance);

    if (balance < 0) {
        balanceElement.innerText = '0 تومان';
        debtElement.innerText = formatAmount(Math.abs(balance)) + ' تومان';
        return;
    }

    balanceElement.innerText = formatAmount(balance) + ' تومان';
    debtElement.innerText = '0 تومان';
}


async function updateBalance() {
    const input = document.getElementById('balanceInput');
    const balance = input.value.replace(/,/g, '');

    await sendBalanceUpdate(balance, input);
}


async function resetBalance() {
    const confirmReset = confirm(
        'آیا مطمئن هستید که می‌خواهید موجودی را صفر کنید؟'
    );

    if (!confirmReset) {
        return;
    }

    try {

        const response = await fetch('/api/dashboard/balance', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message, 'danger');
            return;
        }

        const currentBalance = Number(data.balance);

        const adjustment = -currentBalance;

        await sendBalanceUpdate(
            adjustment.toString()
        );

    } catch (error) {

        showMessage(
            'خطا در صفر کردن موجودی.',
            'danger'
        );

    }
}

async function sendBalanceUpdate(balance, input = null) {
    const formData = new FormData();

    formData.append('balance', balance);

    try {

        const response = await fetch('/api/dashboard/balance', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            showMessage(data.message, 'danger');
            return;
        }

        showMessage(data.message, 'success');

        if (input) {
            input.value = '';
        }

        loadBalance();

    } catch (error) {

        showMessage('خطا در تغییر موجودی.', 'danger');

    }
}


function formatBalanceInput(event) {
    const input = event.target;
    const cursorPosition = input.selectionStart;

    const numbersBeforeCursor = input.value
        .slice(0, cursorPosition)
        .replace(/[^0-9]/g, '')
        .length;

    let value = input.value;

    value = value.replace(/[^0-9-]/g, '');

    if (value === '-') {
        input.value = value;
        return;
    }

    const isNegative = value.startsWith('-');

    value = value.replace(/-/g, '');

    let integerPart = value;

    integerPart = integerPart.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        ','
    );

    let formattedValue = integerPart;

    if (isNegative && formattedValue) {
        formattedValue = '-' + formattedValue;
    }

    input.value = formattedValue;

    restoreCursor(input, numbersBeforeCursor);
}


function restoreCursor(input, numbersBeforeCursor) {
    let cursorPosition = 0;
    let numberCount = 0;

    while (
        cursorPosition < input.value.length &&
        numberCount < numbersBeforeCursor
    ) {

        if (/\d/.test(input.value[cursorPosition])) {
            numberCount++;
        }

        cursorPosition++;
    }

    input.setSelectionRange(cursorPosition, cursorPosition);
}


function formatAmount(amount) {
    let value = String(amount);

    const isNegative = value.startsWith('-');

    value = value.replace('-', '');

    const parts = value.split('.');

    let integerPart = parts[0];
    const decimalPart = parts.length > 1 ? '.' + parts[1] : '';

    integerPart = integerPart.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        ','
    );

    return isNegative
        ? '-' + integerPart + decimalPart
        : integerPart + decimalPart;
}


function showMessage(message, type) {
    const messageBox = document.getElementById('dashboardMessage');

    messageBox.className = 'alert alert-' + type;
    messageBox.innerText = message;

    messageBox.classList.remove('d-none');
}
