document.addEventListener('DOMContentLoaded', initializeDashboard);

function initializeDashboard() {
    const form = document.getElementById('balanceForm');
    const input = document.getElementById('balanceInput');
    const confirmResetButton = document.getElementById('confirmResetBalanceButton');

    loadBalance();
    initializeDashboardOverview();

    if (input) {
        input.addEventListener('keydown', validateBalanceKeydown);
        input.addEventListener('input', formatBalanceInput);
        input.addEventListener('paste', handleBalancePaste);
        input.addEventListener('input', hideBalanceModalMessage);
    }

    const balanceModal = document.getElementById('balanceModal');
    if (balanceModal) {
        balanceModal.addEventListener('hidden.bs.modal', hideBalanceModalMessage);
    }

    const resetModal = document.getElementById('resetBalanceModal');
    if (resetModal) {
        resetModal.addEventListener('hidden.bs.modal', hideResetBalanceMessage);
    }

    if (form) {
        form.addEventListener('submit', saveBalance);
    }

    if (confirmResetButton) {
        confirmResetButton.addEventListener('click', resetBalance);
    }
}

async function resetBalance() {
    const dashboard = document.querySelector('[data-balance-url]');
    const button = document.getElementById('confirmResetBalanceButton');
    button.disabled = true;
    hideResetBalanceMessage();

    try {
        const response = await fetch(dashboard.dataset.balanceUrl + '/reset', {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'صفر کردن موجودی انجام نشد.');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('resetBalanceModal')).hide();
        showMessage(result.message || 'موجودی با موفقیت صفر شد.', 'success');
        await loadBalance();
    } catch (error) {
        showResetBalanceMessage(error.message || 'خطا در صفر کردن موجودی. لطفاً دوباره تلاش کنید.', 'danger');
    } finally {
        button.disabled = false;
    }
}

function showResetBalanceMessage(message, type) {
    const messageElement = document.getElementById('resetBalanceMessage');
    messageElement.textContent = message;
    messageElement.className = 'alert alert-' + type + ' mt-3 mb-0';
}

function hideResetBalanceMessage() {
    const messageElement = document.getElementById('resetBalanceMessage');
    if (!messageElement) return;
    messageElement.textContent = '';
    messageElement.className = 'alert d-none mt-3 mb-0';
}

async function loadBalance() {
    const dashboard = document.querySelector('[data-balance-url]');
    const balanceElement = document.getElementById('balanceAmount');
    const debtElement = document.getElementById('debtAmount');

    if (!dashboard || !balanceElement || !debtElement) {
        return false;
    }

    try {
        const response = await fetch(dashboard.dataset.balanceUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();

        if (!response.ok || !result.success || result.balance === undefined || result.balance === null) {
            throw new Error(result.message || 'دریافت موجودی با خطا مواجه شد.');
        }

        const balance = Number(result.balance);
        if (!Number.isFinite(balance)) {
            throw new Error('مقدار موجودی معتبر نیست.');
        }

        balanceElement.textContent = formatAmount(Math.max(balance, 0));
        debtElement.textContent = formatAmount(Math.max(-balance, 0));
        hideMessage();
        return true;
    } catch (error) {
        balanceElement.textContent = '—';
        debtElement.textContent = '—';
        showMessage(error.message || 'خطا در دریافت موجودی. لطفاً دوباره تلاش کنید.', 'danger');
        return false;
    }
}

async function saveBalance(event) {
    event.preventDefault();

    const dashboard = document.querySelector('[data-balance-url]');
    const input = document.getElementById('balanceInput');
    const saveButton = document.getElementById('saveBalanceButton');
    const parsedBalance = parseBalance(input.value);

    if (parsedBalance === null) {
        showBalanceInputError(input.value.trim() === '' ? 'وارد کردن مبلغ الزامی است.' : 'فقط عدد صحیح معتبر وارد کنید.');
        input.focus();
        return;
    }

    clearBalanceInputError();
    saveButton.disabled = true;

    const body = new FormData();
    body.append('balance', parsedBalance);

    try {
        const response = await fetch(dashboard.dataset.balanceUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: body
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'ذخیره موجودی انجام نشد.');
        }

        const modalElement = document.getElementById('balanceModal');
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        input.value = '';
        showMessage(result.message || 'موجودی با موفقیت ذخیره شد.', 'success');
        await loadBalance();
    } catch (error) {
        showBalanceModalMessage(error.message || 'خطا در ذخیره موجودی. لطفاً دوباره تلاش کنید.', 'danger');
    } finally {
        saveButton.disabled = false;
    }
}

function parseBalance(value) {
    const normalized = value.replace(/,/g, '');

    if (!/^-?\d+$/.test(normalized)) {
        return null;
    }

    return normalized;
}

// Keep the original input behavior: ASCII digits, an optional leading minus,
// three-digit grouping, and caret restoration based on the digits before it.
function formatBalanceInput(event) {
    const input = event.target;
    const cursorPosition = input.selectionStart;
    const numbersBeforeCursor = input.value
        .slice(0, cursorPosition)
        .replace(/[^0-9]/g, '')
        .length;

    let value = input.value.replace(/[^0-9-]/g, '');

    if (value === '-') {
        input.value = value;
        return;
    }

    const isNegative = value.startsWith('-');
    value = value.replace(/-/g, '');

    const formattedValue = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    input.value = isNegative && formattedValue ? '-' + formattedValue : formattedValue;
    restoreBalanceCursor(input, numbersBeforeCursor);
    clearBalanceInputError();
}

function validateBalanceKeydown(event) {
    if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) {
        return;
    }

    if (/^[0-9]$/.test(event.key)) {
        return;
    }

    if (event.key === '-') {
        const input = event.currentTarget;
        const remainingValue = input.value.slice(0, input.selectionStart) + input.value.slice(input.selectionEnd);
        if (input.selectionStart === 0 && !remainingValue.replace(/,/g, '').includes('-')) {
            return;
        }
    }

    event.preventDefault();
}

function handleBalancePaste(event) {
    event.preventDefault();

    const input = event.currentTarget;
    const clipboard = event.clipboardData || (typeof window !== 'undefined' ? window.clipboardData : null);
    const pastedText = clipboard ? clipboard.getData('text') : '';
    const cursorPosition = input.selectionStart;
    const numbersBeforeCursor = input.value.slice(0, cursorPosition).replace(/[^0-9]/g, '').length;
    const value = input.value.slice(0, cursorPosition) + pastedText + input.value.slice(input.selectionEnd);
    input.value = value;

    // Reuse the same formatter used for regular typing and restore the caret
    // at the end of the pasted digits.
    const pastedDigits = pastedText.replace(/[^0-9]/g, '').length;
    const targetDigitPosition = numbersBeforeCursor + pastedDigits;
    formatBalanceInput({
        target: input,
        get selectionStart() { return input.value.length; }
    });
    restoreBalanceCursor(input, targetDigitPosition);
}

function restoreBalanceCursor(input, numbersBeforeCursor) {
    let cursorPosition = 0;
    let numberCount = 0;

    while (cursorPosition < input.value.length && numberCount < numbersBeforeCursor) {
        if (/\d/.test(input.value[cursorPosition])) {
            numberCount++;
        }
        cursorPosition++;
    }

    input.setSelectionRange(cursorPosition, cursorPosition);
}

function showBalanceInputError(message) {
    const input = document.getElementById('balanceInput');
    const error = document.getElementById('balanceInputError');
    input.classList.add('is-invalid');
    error.textContent = message;
    error.classList.add('d-block');
}

function clearBalanceInputError() {
    const input = document.getElementById('balanceInput');
    const error = document.getElementById('balanceInputError');
    if (input) input.classList.remove('is-invalid');
    if (error) error.classList.remove('d-block');
}

function formatAmount(amount) {
    return new Intl.NumberFormat('en-US').format(amount) + ' تومان';
}

function showMessage(message, type) {
    const messageElement = document.getElementById('dashboardMessage');
    messageElement.textContent = message;
    messageElement.className = 'alert alert-' + type;
}

function hideMessage() {
    const messageElement = document.getElementById('dashboardMessage');
    messageElement.textContent = '';
    messageElement.className = 'alert d-none';
}

function showBalanceModalMessage(message, type) {
    const messageElement = document.getElementById('balanceModalMessage');
    if (!messageElement) return;
    messageElement.textContent = message;
    messageElement.className = 'alert alert-' + type;
}

function hideBalanceModalMessage() {
    const messageElement = document.getElementById('balanceModalMessage');
    if (!messageElement) return;
    messageElement.textContent = '';
    messageElement.className = 'alert d-none';
}

function initializeDashboardOverview() {
    const root = document.querySelector('.dashboard-shell');
    if (!root) return;

    const state = {
        categoriesUrl: root.dataset.categoriesUrl,
        transactionsUrl: root.dataset.transactionsUrl,
        categories: [],
        transactions: [],
        editingId: null,
        deletingId: null,
        loading: false,
        saving: false,
        deleting: false
    };

    const transactionForm = document.getElementById('dashboardTransactionForm');
    const typeInput = document.getElementById('dashboardTransactionType');
    const amountInput = document.getElementById('dashboardTransactionAmount');

    transactionForm.addEventListener('submit', function (event) {
        saveDashboardTransaction(event, state);
    });
    typeInput.addEventListener('change', function () {
        populateDashboardTransactionCategories(state, '');
        clearDashboardTransactionFieldError('Type');
        clearDashboardTransactionFieldError('Category');
    });
    ['Title', 'Date', 'Description'].forEach(function (suffix) {
        const field = document.getElementById('dashboardTransaction' + suffix);
        field.addEventListener('input', function () { clearDashboardTransactionFieldError(suffix); });
        field.addEventListener('change', function () { clearDashboardTransactionFieldError(suffix); });
    });
    document.getElementById('dashboardTransactionCategory').addEventListener('change', function () {
        clearDashboardTransactionFieldError('Category');
    });
    amountInput.addEventListener('keydown', validateDashboardTransactionAmountKeydown);
    amountInput.addEventListener('input', formatDashboardTransactionAmountInput);
    amountInput.addEventListener('paste', handleDashboardTransactionAmountPaste);
    document.getElementById('dashboardTransactionsBody').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const transaction = state.transactions.find(function (item) { return String(item.id) === button.dataset.id; });
        if (!transaction) return;
        if (button.dataset.action === 'edit') prepareDashboardTransactionEdit(transaction, state);
        if (button.dataset.action === 'delete') prepareDashboardTransactionDelete(transaction, state);
    });
    document.getElementById('confirmDashboardDeleteButton').addEventListener('click', function () {
        deleteDashboardTransaction(state);
    });
    document.getElementById('dashboardTransactionModal').addEventListener('hidden.bs.modal', function () {
        resetDashboardTransactionForm(state);
    });
    loadDashboardOverview(state);
}

async function loadDashboardOverview(state) {
    if (state.loading) return;
    state.loading = true;
    setDashboardTransactionsMessage('در حال دریافت تراکنش‌ها...', 'text-secondary');
    setDashboardCategoriesMessage('در حال دریافت دسته‌بندی‌ها...', 'text-secondary');
    try {
        const results = await Promise.all([
            requestDashboardApi(state.transactionsUrl, { method: 'GET' }),
            requestDashboardApi(state.categoriesUrl, { method: 'GET' })
        ]);
        if (!Array.isArray(results[0].transactions) || !Array.isArray(results[1].categories)) {
            throw new Error('ساختار اطلاعات دریافتی از سرور معتبر نیست.');
        }
        state.transactions = results[0].transactions;
        state.categories = results[1].categories;
        renderDashboardTransactions(state);
        renderDashboardCategories(state.categories);
        document.getElementById('addDashboardTransactionButton').disabled = state.categories.length === 0;
        if (state.categories.length === 0) {
            showDashboardMessage('برای ثبت تراکنش ابتدا یک دسته‌بندی بسازید.', 'warning');
        }
    } catch (error) {
        setDashboardTransactionsMessage(error.message || 'دریافت تراکنش‌ها انجام نشد.', 'text-danger');
        setDashboardCategoriesMessage(error.message || 'دریافت دسته‌بندی‌ها انجام نشد.', 'text-danger');
        document.getElementById('addDashboardTransactionButton').disabled = true;
    } finally {
        state.loading = false;
    }
}

async function requestDashboardApi(url, options) {
    let response;
    try {
        response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }, options || {}));
    } catch (error) {
        throw new Error('ارتباط با سرور برقرار نشد. اتصال را بررسی کنید و دوباره تلاش کنید.');
    }
    const text = await response.text();
    let result;
    try {
        result = text ? JSON.parse(text) : {};
    } catch (error) {
        throw new Error('پاسخ سرور قابل خواندن نیست. لطفاً دوباره وارد حساب شوید.');
    }
    if (!response.ok || result.success !== true) {
        throw new Error(result.message || (response.status === 401
            ? 'نشست شما پایان یافته است. دوباره وارد حساب کاربری شوید.'
            : 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.'));
    }
    return result;
}

function renderDashboardTransactions(state) {
    const body = document.getElementById('dashboardTransactionsBody');
    body.replaceChildren();
    if (!state.transactions.length) {
        setDashboardTransactionsMessage('هنوز تراکنشی ثبت نشده است.', 'text-secondary');
        return;
    }
    const categoryById = new Map(state.categories.map(function (category) { return [String(category.id), category]; }));
    state.transactions.slice(0, 5).forEach(function (transaction) {
        const category = categoryById.get(String(transaction.category_id));
        const row = document.createElement('tr');
        [transaction.title || '', category ? category.name : '—'].forEach(function (value, index) {
            const cell = document.createElement(index === 0 ? 'th' : 'td');
            if (index === 0) cell.scope = 'row';
            cell.textContent = value;
            row.appendChild(cell);
        });
        const amountCell = document.createElement('td');
        amountCell.className = 'text-nowrap ' + (category && category.type === 'expense' ? 'text-danger' : 'text-success');
        const amountPrefix = category && category.type === 'expense' ? '− ' : '+ ';
        amountCell.textContent = amountPrefix + formatDashboardTransactionAmount(transaction.amount);
        row.appendChild(amountCell);
        const dateCell = document.createElement('td');
        dateCell.className = 'text-nowrap';
        dateCell.textContent = transaction.transaction_date || '—';
        row.appendChild(dateCell);
        const actions = document.createElement('td');
        actions.className = 'text-nowrap';
        actions.appendChild(createDashboardTransactionAction('ویرایش', 'edit', transaction.id, 'btn-outline-primary'));
        actions.appendChild(document.createTextNode(' '));
        actions.appendChild(createDashboardTransactionAction('حذف', 'delete', transaction.id, 'btn-outline-danger'));
        row.appendChild(actions);
        body.appendChild(row);
    });
}

function renderDashboardCategories(categories) {
    const list = document.getElementById('dashboardCategoriesList');
    list.replaceChildren();
    if (!categories.length) {
        setDashboardCategoriesMessage('هنوز دسته‌بندی فعالی ثبت نشده است.', 'text-secondary');
        return;
    }
    categories.slice(0, 8).forEach(function (category) {
        const item = document.createElement('li');
        item.className = 'list-group-item d-flex align-items-center justify-content-between gap-2';
        const name = document.createElement('span');
        name.textContent = category.name || '';
        const type = document.createElement('span');
        type.className = 'badge text-bg-light';
        type.textContent = category.type === 'income' ? 'درآمد' : category.type === 'expense' ? 'هزینه' : '—';
        item.append(name, type);
        list.appendChild(item);
    });
}

function createDashboardTransactionAction(label, action, id, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm ' + className;
    button.textContent = label;
    button.dataset.action = action;
    button.dataset.id = String(id);
    return button;
}

function setDashboardTransactionsMessage(message, className) {
    const body = document.getElementById('dashboardTransactionsBody');
    body.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 5;
    cell.className = 'text-center py-5 ' + className;
    cell.textContent = message;
    row.appendChild(cell);
    body.appendChild(row);
}

function setDashboardCategoriesMessage(message, className) {
    const list = document.getElementById('dashboardCategoriesList');
    list.replaceChildren();
    const item = document.createElement('li');
    item.className = 'list-group-item ' + className;
    item.textContent = message;
    list.appendChild(item);
}

function prepareDashboardTransactionEdit(transaction, state) {
    resetDashboardTransactionForm(state);
    state.editingId = String(transaction.id);
    document.getElementById('dashboardTransactionModalTitle').textContent = 'ویرایش تراکنش';
    document.getElementById('dashboardTransactionTitle').value = transaction.title || '';
    document.getElementById('dashboardTransactionAmount').value = formatDashboardTransactionAmount(transaction.amount);
    document.getElementById('dashboardTransactionDate').value = transaction.transaction_date || '';
    document.getElementById('dashboardTransactionDescription').value = transaction.description || '';
    const category = state.categories.find(function (item) { return String(item.id) === String(transaction.category_id); });
    document.getElementById('dashboardTransactionType').value = category ? category.type : '';
    populateDashboardTransactionCategories(state, String(transaction.category_id));
    bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardTransactionModal')).show();
}

function prepareDashboardTransactionDelete(transaction, state) {
    state.deletingId = String(transaction.id);
    document.getElementById('dashboardDeleteTransactionName').textContent = transaction.title || '';
    showDashboardDeleteMessage('', '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardDeleteTransactionModal')).show();
}

function populateDashboardTransactionCategories(state, selectedId) {
    const type = document.getElementById('dashboardTransactionType').value;
    const select = document.getElementById('dashboardTransactionCategory');
    select.replaceChildren();
    appendDashboardOption(select, '', type ? 'دسته‌بندی را انتخاب کنید' : 'ابتدا نوع را انتخاب کنید');
    const categories = state.categories.filter(function (category) { return category.type === type; });
    categories.forEach(function (category) { appendDashboardOption(select, String(category.id), category.name || ''); });
    if (selectedId && categories.some(function (category) { return String(category.id) === selectedId; })) select.value = selectedId;
    if (type && categories.length === 0) appendDashboardOption(select, '', 'دسته‌بندی فعالی وجود ندارد');
}

function appendDashboardOption(select, value, label) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = label;
    select.appendChild(option);
}

function resetDashboardTransactionForm(state) {
    document.getElementById('dashboardTransactionForm').reset();
    document.getElementById('dashboardTransactionModalTitle').textContent = 'افزودن تراکنش';
    document.getElementById('dashboardTransactionAmount').dataset.invalid = 'false';
    document.getElementById('dashboardTransactionFormMessage').className = 'alert d-none';
    document.getElementById('dashboardTransactionFormMessage').textContent = '';
    ['Title', 'Type', 'Amount', 'Category', 'Date'].forEach(function (suffix) {
        const field = document.getElementById('dashboardTransaction' + suffix);
        const error = document.getElementById('dashboardTransaction' + suffix + 'Error');
        field.classList.remove('is-invalid');
        error.textContent = '';
    });
    state.editingId = null;
    populateDashboardTransactionCategories(state, '');
}

async function saveDashboardTransaction(event, state) {
    event.preventDefault();
    if (state.saving) return;
    const title = document.getElementById('dashboardTransactionTitle').value.trim();
    const amountInput = document.getElementById('dashboardTransactionAmount');
    const amount = normalizeDashboardTransactionAmount(amountInput.value);
    const categoryId = document.getElementById('dashboardTransactionCategory').value;
    const date = document.getElementById('dashboardTransactionDate').value;
    const category = state.categories.find(function (item) { return String(item.id) === categoryId; });
    let valid = true;
    if (Array.from(title).length < 1 || Array.from(title).length > 150) valid = setDashboardTransactionError('Title', 'عنوان باید بین ۱ تا ۱۵۰ نویسه باشد.') && valid;
    if (amount === null || amountInput.dataset.invalid === 'true') valid = setDashboardTransactionError('Amount', 'مبلغ مثبت معتبر با حداکثر دو رقم اعشار وارد کنید.') && valid;
    if (!['income', 'expense'].includes(document.getElementById('dashboardTransactionType').value)) valid = setDashboardTransactionError('Type', 'نوع تراکنش را انتخاب کنید.') && valid;
    if (!category || category.type !== document.getElementById('dashboardTransactionType').value) valid = setDashboardTransactionError('Category', 'دسته‌بندی فعال و متناسب انتخاب کنید.') && valid;
    if (!isDashboardTransactionDateValid(date)) valid = setDashboardTransactionError('Date', 'تاریخ معتبر انتخاب کنید.') && valid;
    if (!valid) return;

    const body = new FormData();
    body.append('title', title);
    body.append('amount', amount);
    body.append('category_id', categoryId);
    body.append('transaction_date', date);
    body.append('description', document.getElementById('dashboardTransactionDescription').value);
    const isEditing = state.editingId !== null;
    const url = isEditing ? state.transactionsUrl + '/' + encodeURIComponent(state.editingId) + '/update' : state.transactionsUrl;
    const button = document.getElementById('saveDashboardTransactionButton');
    state.saving = true;
    button.disabled = true;
    button.textContent = 'در حال ذخیره...';
    try {
        const result = await requestDashboardApi(url, { method: 'POST', body: body });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardTransactionModal')).hide();
        showDashboardMessage(result.message || 'تراکنش ذخیره شد.', 'success');
        await refreshDashboardData(state);
    } catch (error) {
        showDashboardTransactionMessage(error.message || 'ذخیره تراکنش انجام نشد.', 'danger');
    } finally {
        state.saving = false;
        button.disabled = false;
        button.textContent = 'ذخیره تراکنش';
    }
}

async function deleteDashboardTransaction(state) {
    if (state.deleting || state.deletingId === null) return;
    const button = document.getElementById('confirmDashboardDeleteButton');
    state.deleting = true;
    button.disabled = true;
    button.textContent = 'در حال حذف...';
    try {
        const result = await requestDashboardApi(state.transactionsUrl + '/' + encodeURIComponent(state.deletingId) + '/delete', { method: 'POST' });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardDeleteTransactionModal')).hide();
        showDashboardMessage(result.message || 'تراکنش حذف شد.', 'success');
        await refreshDashboardData(state);
    } catch (error) {
        showDashboardDeleteMessage(error.message || 'حذف تراکنش انجام نشد.', 'danger');
    } finally {
        state.deleting = false;
        button.disabled = false;
        button.textContent = 'حذف تراکنش';
    }
}

async function refreshDashboardData(state) {
    await loadDashboardOverview(state);
    await loadBalance();
}

function setDashboardTransactionError(field, message) {
    const input = document.getElementById('dashboardTransaction' + field);
    const error = document.getElementById('dashboardTransaction' + field + 'Error');
    input.classList.add('is-invalid');
    error.textContent = message;
    return false;
}

function clearDashboardTransactionFieldError(field) {
    const input = document.getElementById('dashboardTransaction' + field);
    const error = document.getElementById('dashboardTransaction' + field + 'Error');
    if (input) input.classList.remove('is-invalid');
    if (error) error.textContent = '';
}

function showDashboardTransactionMessage(message, type) {
    const element = document.getElementById('dashboardTransactionFormMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type;
}

function showDashboardDeleteMessage(message, type) {
    const element = document.getElementById('dashboardDeleteTransactionMessage');
    element.textContent = message;
    element.className = message ? 'alert alert-' + type : 'alert d-none';
}

function showDashboardMessage(message, type) {
    const element = document.getElementById('dashboardMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type + ' mt-3';
}

function normalizeDashboardTransactionAmount(value) {
    const normalized = String(value).replace(/,/g, '').trim();
    const match = normalized.match(/^([0-9]+)(?:\.([0-9]{1,2}))?$/);
    if (!match) return null;
    const integer = match[1].replace(/^0+/, '') || '0';
    const fraction = match[2] || '';
    if (integer.length > 13 || !/[1-9]/.test(integer + fraction)) return null;
    return integer + (fraction ? '.' + fraction : '');
}

function formatDashboardTransactionAmount(value) {
    const parts = String(value === undefined || value === null ? '' : value).replace(/,/g, '').split('.');
    const integer = (parts[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return integer + (parts.length > 1 ? '.' + parts[1] : '');
}

function formatDashboardTransactionAmountInput(event) {
    const input = event.target;
    if (/[^0-9.,]/.test(input.value)) {
        input.dataset.invalid = 'true';
        setDashboardTransactionError('Amount', 'فقط عدد لاتین و نقطه برای اعشار وارد کنید.');
        return;
    }
    const cursor = input.selectionStart;
    const digitsBeforeCursor = (input.value.slice(0, cursor).match(/[0-9]/g) || []).length;
    let raw = input.value.replace(/,/g, '');
    const decimalIndex = raw.indexOf('.');
    const integer = (decimalIndex < 0 ? raw : raw.slice(0, decimalIndex)).replace(/\D/g, '');
    const fraction = decimalIndex < 0 ? '' : raw.slice(decimalIndex + 1).replace(/\D/g, '').replace(/\./g, '').slice(0, 2);
    input.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (decimalIndex < 0 ? '' : '.' + fraction);
    restoreDashboardTransactionCursor(input, digitsBeforeCursor);
    input.dataset.invalid = 'false';
    const error = document.getElementById('dashboardTransactionAmountError');
    error.textContent = '';
    input.classList.remove('is-invalid');
}

function validateDashboardTransactionAmountKeydown(event) {
    if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1 || /^[0-9]$/.test(event.key)) return;
    if (event.key === '.') {
        const remaining = event.currentTarget.value.slice(0, event.currentTarget.selectionStart) + event.currentTarget.value.slice(event.currentTarget.selectionEnd);
        if (!remaining.includes('.') && /[0-9]/.test(remaining)) return;
    }
    event.preventDefault();
}

function handleDashboardTransactionAmountPaste(event) {
    event.preventDefault();
    const input = event.currentTarget;
    const clipboard = event.clipboardData || window.clipboardData;
    const pasted = clipboard ? clipboard.getData('text') : '';
    if (!/^(?:[0-9]{1,3}(?:,[0-9]{3})+|[0-9]+)(?:\.[0-9]{1,2})?$/.test(pasted)) {
        input.dataset.invalid = 'true';
        setDashboardTransactionError('Amount', 'مبلغ کپی‌شده معتبر نیست.');
        return;
    }
    input.setRangeText(pasted, input.selectionStart, input.selectionEnd, 'end');
    formatDashboardTransactionAmountInput({ target: input });
}

function restoreDashboardTransactionCursor(input, digitsBeforeCursor) {
    let position = 0;
    let digits = 0;
    while (position < input.value.length && digits < digitsBeforeCursor) {
        if (/\d/.test(input.value[position])) digits++;
        position++;
    }
    input.setSelectionRange(position, position);
}

function isDashboardTransactionDateValid(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const parts = value.split('-').map(Number);
    const date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
    return date.getUTCFullYear() === parts[0] && date.getUTCMonth() === parts[1] - 1 && date.getUTCDate() === parts[2];
}
