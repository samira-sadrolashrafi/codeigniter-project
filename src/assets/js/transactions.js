document.addEventListener('DOMContentLoaded', initializeTransactionsPage);

function initializeTransactionsPage() {
    const page = document.querySelector('[data-transactions-api-url]');
    if (!page) return;

    const state = {
        transactionsUrl: page.dataset.transactionsApiUrl.replace(/\/$/, ''),
        categoriesUrl: page.dataset.categoriesApiUrl.replace(/\/$/, ''),
        transactions: [],
        categories: [],
        categoriesLoaded: false,
        loading: false,
        saving: false,
        deleting: false,
        editingId: null,
        deletingId: null,
        deletedTransactions: [],
        deletedLoaded: false,
        deletedLoading: false,
        restoringId: null
    };

    document.getElementById('addTransactionButton').addEventListener('click', function () {
        if (!state.categoriesLoaded) {
            showPageMessage('برای دریافت دسته‌بندی‌ها تلاش دوباره کنید.', 'warning', function () {
                loadTransactionsPage(state);
            });
            return;
        }
        prepareNewTransaction(state);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('transactionModal')).show();
    });

    document.getElementById('transactionsTableBody').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const transaction = state.transactions.find(function (item) {
            return String(item.id) === button.dataset.id;
        });
        if (!transaction) return;

        if (button.dataset.action === 'edit') {
            if (!state.categoriesLoaded) {
                showPageMessage('برای ویرایش، دریافت دسته‌بندی‌ها باید موفق باشد.', 'warning', function () {
                    loadTransactionsPage(state);
                });
                return;
            }
            prepareEditTransaction(transaction, state);
        } else if (button.dataset.action === 'delete') {
            prepareDeleteTransaction(transaction, state);
        }
    });

    document.getElementById('transactionForm').addEventListener('submit', function (event) {
        saveTransaction(event, state);
    });
    document.getElementById('confirmDeleteTransactionButton').addEventListener('click', function () {
        deleteTransaction(state);
    });
    document.getElementById('showActiveTransactionsButton').addEventListener('click', function () {
        setTransactionPanel(state, false);
    });
    document.getElementById('showDeletedTransactionsButton').addEventListener('click', function () {
        setTransactionPanel(state, true);
    });
    document.getElementById('deletedTransactionsTableBody').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-restore-id]');
        if (button) restoreTransaction(button.dataset.restoreId, state, button);
    });

    document.getElementById('transactionType').addEventListener('change', function () {
        clearTransactionFieldError('transactionType');
        updateFormCategoryOptions(state, '');
    });
    document.getElementById('transactionCategory').addEventListener('change', function () {
        clearTransactionFieldError('transactionCategory');
    });

    ['transactionTitle', 'transactionDate'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            clearTransactionFieldError(id);
        });
        document.getElementById(id).addEventListener('change', function () {
            clearTransactionFieldError(id);
        });
    });

    const amountInput = document.getElementById('transactionAmount');
    amountInput.addEventListener('keydown', validateTransactionAmountKeydown);
    amountInput.addEventListener('input', formatTransactionAmountInput);
    amountInput.addEventListener('paste', handleTransactionAmountPaste);

    ['transactionSearch', 'transactionDateFrom', 'transactionDateTo'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            renderFilteredTransactions(state);
        });
        document.getElementById(id).addEventListener('change', function () {
            renderFilteredTransactions(state);
        });
    });
    document.getElementById('transactionTypeFilter').addEventListener('change', function () {
        updateFilterCategoryOptions(state);
        renderFilteredTransactions(state);
    });
    document.getElementById('transactionCategoryFilter').addEventListener('change', function () {
        renderFilteredTransactions(state);
    });

    document.getElementById('transactionModal').addEventListener('hidden.bs.modal', function () {
        resetTransactionForm(state);
    });
    document.getElementById('deleteTransactionModal').addEventListener('hidden.bs.modal', hideDeleteTransactionMessage);

    loadTransactionsPage(state);
}

async function requestTransactionsApi(url, options) {
    let response;
    try {
        response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }, options || {}));
    } catch (error) {
        throw new Error('ارتباط با سرور برقرار نشد. اتصال را بررسی کنید و دوباره تلاش کنید.');
    }

    const responseText = await response.text();
    let result;
    try {
        result = responseText ? JSON.parse(responseText) : {};
    } catch (error) {
        throw new Error('پاسخ سرور قابل خواندن نیست. ممکن است نشست شما پایان یافته باشد؛ دوباره وارد شوید.');
    }

    if (!response.ok || result.success !== true) {
        const message = typeof result.message === 'string' && result.message.trim()
            ? result.message
            : response.status === 401
                ? 'نشست شما پایان یافته است. دوباره وارد حساب کاربری شوید.'
                : 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.';
        const apiError = new Error(message);
        apiError.errors = result.errors || null;
        apiError.status = response.status;
        throw apiError;
    }
    return result;
}

async function loadTransactionsPage(state) {
    if (state.loading) return false;
    state.loading = true;
    hidePageMessage();
    setTransactionTableMessage('در حال دریافت تراکنش‌ها...', 'text-secondary');

    const results = await Promise.allSettled([
        requestTransactionsApi(state.transactionsUrl, { method: 'GET' }),
        requestTransactionsApi(state.categoriesUrl, { method: 'GET' })
    ]);

    const transactionsResult = results[0];
    const categoriesResult = results[1];
    const messages = [];
    let transactionsLoaded = false;

    if (transactionsResult.status === 'fulfilled' && Array.isArray(transactionsResult.value.transactions)) {
        state.transactions = transactionsResult.value.transactions;
        transactionsLoaded = true;
    } else {
        state.transactions = [];
        const error = transactionsResult.status === 'rejected'
            ? transactionsResult.reason
            : new Error('ساختار فهرست تراکنش‌های دریافتی معتبر نیست.');
        messages.push('تراکنش‌ها: ' + error.message);
        renderTransactionLoadError(error.message, function () {
            loadTransactionsPage(state);
        });
    }

    if (categoriesResult.status === 'fulfilled' && Array.isArray(categoriesResult.value.categories)) {
        state.categories = categoriesResult.value.categories;
        state.categoriesLoaded = true;
        updateFilterCategoryOptions(state);
    } else {
        state.categories = [];
        state.categoriesLoaded = false;
        updateFilterCategoryOptions(state);
        const error = categoriesResult.status === 'rejected'
            ? categoriesResult.reason
            : new Error('ساختار فهرست دسته‌بندی‌های دریافتی معتبر نیست.');
        messages.push('دسته‌بندی‌ها: ' + error.message);
    }

    if (transactionsLoaded) renderFilteredTransactions(state);
    if (messages.length) {
        showPageMessage(messages.join(' '), 'danger', function () {
            loadTransactionsPage(state);
        });
    }

    state.loading = false;
    return transactionsLoaded && state.categoriesLoaded;
}

function renderTransactionLoadError(message, retry) {
    const body = document.getElementById('transactionsTableBody');
    body.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 7;
    cell.className = 'text-center py-5';
    const text = document.createElement('p');
    text.className = 'text-danger mb-3';
    text.textContent = message;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm';
    button.textContent = 'تلاش دوباره';
    button.addEventListener('click', retry);
    cell.append(text, button);
    row.appendChild(cell);
    body.appendChild(row);
}

function renderFilteredTransactions(state) {
    const body = document.getElementById('transactionsTableBody');
    const search = document.getElementById('transactionSearch').value.trim().toLocaleLowerCase();
    const type = document.getElementById('transactionTypeFilter').value;
    const categoryId = document.getElementById('transactionCategoryFilter').value;
    const dateFrom = document.getElementById('transactionDateFrom').value;
    const dateTo = document.getElementById('transactionDateTo').value;
    const categoriesById = new Map(state.categories.map(function (category) {
        return [String(category.id), category];
    }));

    const filtered = state.transactions.filter(function (transaction) {
        const category = categoriesById.get(String(transaction.category_id));
        const transactionType = category ? category.type : '';
        const searchableText = [transaction.title, transaction.description]
            .filter(Boolean)
            .join(' ')
            .toLocaleLowerCase();

        if (search && !searchableText.includes(search)) return false;
        if (type !== 'all' && transactionType !== type) return false;
        if (categoryId !== 'all' && String(transaction.category_id) !== categoryId) return false;
        if (dateFrom && transaction.transaction_date < dateFrom) return false;
        if (dateTo && transaction.transaction_date > dateTo) return false;
        return true;
    });

    body.replaceChildren();
    if (filtered.length === 0) {
        const message = state.transactions.length === 0
            ? 'هنوز تراکنش فعالی ثبت نشده است.'
            : 'تراکنشی با این جست‌وجو و فیلترها پیدا نشد.';
        setTransactionTableMessage(message, 'text-secondary');
        return;
    }

    filtered.forEach(function (transaction) {
        const row = document.createElement('tr');
        const category = categoriesById.get(String(transaction.category_id));
        const type = category ? category.type : '';

        const titleCell = document.createElement('th');
        titleCell.scope = 'row';
        titleCell.textContent = transaction.title || '';

        const typeCell = document.createElement('td');
        const typeBadge = document.createElement('span');
        typeBadge.className = 'badge ' + (type === 'income' ? 'text-bg-success' : type === 'expense' ? 'text-bg-danger' : 'text-bg-secondary');
        typeBadge.textContent = type === 'income' ? 'درآمد' : type === 'expense' ? 'هزینه' : 'نامشخص';
        typeCell.appendChild(typeBadge);

        const amountCell = document.createElement('td');
        amountCell.className = 'text-nowrap fw-semibold ' + (type === 'income' ? 'text-success' : type === 'expense' ? 'text-danger' : '');
        const prefix = type === 'income' ? '+ ' : type === 'expense' ? '− ' : '';
        amountCell.textContent = prefix + formatTransactionAmountDisplay(transaction.amount) + ' تومان';

        const categoryCell = document.createElement('td');
        categoryCell.textContent = category ? category.name : 'دسته‌بندی #' + String(transaction.category_id);

        const dateCell = document.createElement('td');
        dateCell.className = 'text-nowrap';
        dateCell.textContent = typeof transaction.transaction_date === 'string' ? transaction.transaction_date : '—';

        const descriptionCell = document.createElement('td');
        descriptionCell.className = 'text-break';
        descriptionCell.textContent = transaction.description || '—';

        const actionsCell = document.createElement('td');
        actionsCell.className = 'text-nowrap';
        const editButton = createTransactionAction('ویرایش', 'edit', transaction.id, 'btn-outline-primary');
        editButton.disabled = !state.categoriesLoaded;
        actionsCell.appendChild(editButton);
        actionsCell.appendChild(document.createTextNode(' '));
        actionsCell.appendChild(createTransactionAction('حذف', 'delete', transaction.id, 'btn-outline-danger'));

        row.append(titleCell, typeCell, amountCell, categoryCell, dateCell, descriptionCell, actionsCell);
        body.appendChild(row);
    });
}

function createTransactionAction(label, action, id, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm ' + className;
    button.textContent = label;
    button.dataset.action = action;
    button.dataset.id = String(id);
    return button;
}

function setTransactionTableMessage(message, textClass) {
    const body = document.getElementById('transactionsTableBody');
    body.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 7;
    cell.className = 'text-center py-5 ' + textClass;
    cell.textContent = message;
    row.appendChild(cell);
    body.appendChild(row);
}

function updateFilterCategoryOptions(state) {
    const select = document.getElementById('transactionCategoryFilter');
    const previousValue = select.value;
    const typeFilter = document.getElementById('transactionTypeFilter').value;
    select.replaceChildren();
    appendOption(select, 'all', 'همه دسته‌بندی‌ها');

    state.categories.filter(function (category) {
        return typeFilter === 'all' || category.type === typeFilter;
    }).forEach(function (category) {
        appendOption(select, String(category.id), category.name || '');
    });

    if (Array.from(select.options).some(function (option) { return option.value === previousValue; })) {
        select.value = previousValue;
    }
}

function appendOption(select, value, label) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = label;
    select.appendChild(option);
}

function prepareNewTransaction(state) {
    resetTransactionForm(state);
    document.getElementById('transactionModalTitle').textContent = 'افزودن تراکنش';
    document.getElementById('transactionDate').value = getTodayDate();
    if (!state.categories.length) {
        showCategoryWarning('دسته‌بندی فعالی ندارید. ابتدا از صفحه مدیریت دسته‌بندی‌ها یک دسته‌بندی ایجاد کنید.');
    }
}

function prepareEditTransaction(transaction, state) {
    resetTransactionForm(state);
    state.editingId = String(transaction.id);
    document.getElementById('transactionModalTitle').textContent = 'ویرایش تراکنش';
    document.getElementById('transactionTitle').value = transaction.title || '';
    document.getElementById('transactionAmount').value = formatTransactionAmountInputValue(transaction.amount);
    document.getElementById('transactionDate').value = transaction.transaction_date || '';
    document.getElementById('transactionDescription').value = transaction.description || '';

    const category = state.categories.find(function (item) {
        return String(item.id) === String(transaction.category_id);
    });
    document.getElementById('transactionType').value = category ? category.type : '';
    updateFormCategoryOptions(state, transaction.category_id);
    if (!category) showCategoryWarning('دسته‌بندی فعال این تراکنش در دسترس نیست؛ تا انتخاب دسته‌بندی معتبر امکان ذخیره وجود ندارد.');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('transactionModal')).show();
}

function prepareDeleteTransaction(transaction, state) {
    state.deletingId = String(transaction.id);
    document.getElementById('deleteTransactionTitle').textContent = transaction.title || '';
    hideDeleteTransactionMessage();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTransactionModal')).show();
}

function resetTransactionForm(state) {
    const form = document.getElementById('transactionForm');
    form.reset();
    document.getElementById('transactionAmount').dataset.invalidInput = 'false';
    document.getElementById('transactionModalTitle').textContent = 'افزودن تراکنش';
    hideTransactionFormMessage();
    hideCategoryWarning();
    ['transactionTitle', 'transactionAmount', 'transactionType', 'transactionCategory', 'transactionDate'].forEach(clearTransactionFieldError);
    if (state) state.editingId = null;
    updateFormCategoryOptions(state, '');
}

function updateFormCategoryOptions(state, preferredCategoryId) {
    const type = document.getElementById('transactionType').value;
    const select = document.getElementById('transactionCategory');
    const currentValue = preferredCategoryId !== undefined && preferredCategoryId !== ''
        ? String(preferredCategoryId)
        : select.value;
    select.replaceChildren();
    appendOption(select, '', type ? 'دسته‌بندی را انتخاب کنید' : 'ابتدا نوع تراکنش را انتخاب کنید');

    const available = state && state.categoriesLoaded
        ? state.categories.filter(function (category) { return category.type === type; })
        : [];
    available.forEach(function (category) {
        appendOption(select, String(category.id), category.name || '');
    });

    if (available.some(function (category) { return String(category.id) === currentValue; })) {
        select.value = currentValue;
    } else {
        select.value = '';
    }

    if (type && !available.length && state && state.categoriesLoaded) {
        appendOption(select, '', 'برای این نوع دسته‌بندی فعالی وجود ندارد');
        showCategoryWarning('برای این نوع تراکنش دسته‌بندی فعالی وجود ندارد. ابتدا یک دسته‌بندی مناسب ایجاد کنید.');
    } else {
        hideCategoryWarning();
    }
    clearTransactionFieldError('transactionCategory');
}

async function saveTransaction(event, state) {
    event.preventDefault();
    if (state.saving) return;
    hideTransactionFormMessage();
    if (!validateTransactionForm(state)) return;

    const title = document.getElementById('transactionTitle').value.trim();
    const amount = normalizeTransactionAmount(document.getElementById('transactionAmount').value);
    const categoryId = document.getElementById('transactionCategory').value;
    const date = document.getElementById('transactionDate').value;
    const description = document.getElementById('transactionDescription').value;
    const formData = new FormData();
    formData.append('title', title);
    formData.append('amount', amount);
    formData.append('category_id', categoryId);
    formData.append('transaction_date', date);
    formData.append('description', description);

    const isEditing = state.editingId !== null;
    const url = isEditing
        ? state.transactionsUrl + '/' + encodeURIComponent(state.editingId) + '/update'
        : state.transactionsUrl;
    const button = document.getElementById('saveTransactionButton');
    state.saving = true;
    button.disabled = true;
    button.textContent = 'در حال ذخیره...';

    try {
        const result = await requestTransactionsApi(url, { method: 'POST', body: formData });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('transactionModal')).hide();
        const refreshed = await loadTransactionsPage(state);
        if (refreshed) {
            showPageMessage(result.message || (isEditing ? 'تراکنش ویرایش شد.' : 'تراکنش ثبت شد.'), 'success');
        } else {
            showPageMessage((result.message || 'تراکنش ذخیره شد.') + ' اما بروزرسانی فهرست کامل نشد؛ دوباره تلاش کنید.', 'warning', function () {
                loadTransactionsPage(state);
            });
        }
    } catch (error) {
        showTransactionFormMessage(error.message || 'ذخیره تراکنش انجام نشد.', 'danger');
    } finally {
        state.saving = false;
        button.disabled = false;
        button.textContent = 'ذخیره';
    }
}

function validateTransactionForm(state) {
    let valid = true;
    const title = document.getElementById('transactionTitle').value.trim();
    const amount = normalizeTransactionAmount(document.getElementById('transactionAmount').value);
    const type = document.getElementById('transactionType').value;
    const categoryId = document.getElementById('transactionCategory').value;
    const date = document.getElementById('transactionDate').value;
    const category = state.categories.find(function (item) {
        return String(item.id) === categoryId;
    });

    if (Array.from(title).length < 1 || Array.from(title).length > 150) {
        setTransactionFieldError('transactionTitle', 'عنوان باید بین ۱ تا ۱۵۰ نویسه باشد.');
        valid = false;
    }
    if (amount === null || document.getElementById('transactionAmount').dataset.invalidInput === 'true') {
        setTransactionFieldError('transactionAmount', 'مبلغ باید مثبت، حداکثر ۱۳ رقم صحیح و حداکثر دو رقم اعشار داشته باشد.');
        valid = false;
    }
    if (type !== 'income' && type !== 'expense') {
        setTransactionFieldError('transactionType', 'نوع تراکنش را انتخاب کنید.');
        valid = false;
    }
    if (!category || category.type !== type) {
        setTransactionFieldError('transactionCategory', 'دسته‌بندی فعالی متناسب با نوع تراکنش انتخاب کنید.');
        valid = false;
    }
    if (!isValidTransactionDate(date)) {
        setTransactionFieldError('transactionDate', 'تاریخ معتبر انتخاب کنید.');
        valid = false;
    }
    return valid;
}

function normalizeTransactionAmount(value) {
    const normalized = String(value).replace(/,/g, '').trim();
    const match = normalized.match(/^([0-9]+)(?:\.([0-9]{1,2}))?$/);
    if (!match) return null;

    const integer = match[1].replace(/^0+/, '') || '0';
    const fraction = match[2] || '';
    if (integer.length > 13 || !/[1-9]/.test(integer + fraction)) return null;
    return integer + (fraction ? '.' + fraction : '');
}

function isValidTransactionDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const parts = value.split('-').map(Number);
    const date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
    return date.getUTCFullYear() === parts[0] && date.getUTCMonth() === parts[1] - 1 && date.getUTCDate() === parts[2];
}

function formatTransactionAmountInput(event) {
    const input = event.target;
    const hadInvalidCharacters = /[^0-9.,]/.test(input.value);
    if (hadInvalidCharacters) {
        input.dataset.invalidInput = 'true';
        setTransactionFieldError('transactionAmount', 'فقط از اعداد لاتین و نقطه برای اعشار استفاده کنید.');
        return;
    }
    const cursor = input.selectionStart;
    const beforeCursor = input.value.slice(0, cursor);
    const digitsBeforeCursor = (beforeCursor.match(/[0-9]/g) || []).length;
    const decimalBeforeCursor = beforeCursor.includes('.');
    const fractionDigitsBeforeCursor = decimalBeforeCursor
        ? (beforeCursor.slice(beforeCursor.indexOf('.') + 1).match(/[0-9]/g) || []).length
        : 0;

    const raw = input.value.replace(/,/g, '').replace(/[^0-9.]/g, '');
    const decimalIndex = raw.indexOf('.');
    const integer = decimalIndex === -1 ? raw : raw.slice(0, decimalIndex);
    const fraction = decimalIndex === -1 ? '' : raw.slice(decimalIndex + 1).replace(/\./g, '');
    const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    input.value = formattedInteger + (decimalIndex === -1 ? '' : '.' + fraction);
    restoreTransactionAmountCursor(input, digitsBeforeCursor, decimalBeforeCursor, fractionDigitsBeforeCursor);
    input.dataset.invalidInput = 'false';
    clearTransactionFieldError('transactionAmount');
}

function validateTransactionAmountKeydown(event) {
    if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
    if (/^[0-9]$/.test(event.key)) return;
    if (event.key === '.') {
        const input = event.currentTarget;
        const remaining = input.value.slice(0, input.selectionStart) + input.value.slice(input.selectionEnd);
        const integerBeforeCursor = input.value.slice(0, input.selectionStart).replace(/,/g, '').split('.')[0];
        if (!remaining.includes('.') && /[0-9]/.test(integerBeforeCursor)) return;
    }
    event.preventDefault();
}

function handleTransactionAmountPaste(event) {
    event.preventDefault();
    const input = event.currentTarget;
    const clipboard = event.clipboardData || (typeof window !== 'undefined' ? window.clipboardData : null);
    const pastedText = clipboard ? clipboard.getData('text') : '';
    if (!/^(?:[0-9]{1,3}(?:,[0-9]{3})+|[0-9]+)(?:\.[0-9]{0,2})?$/.test(pastedText)) {
        input.dataset.invalidInput = 'true';
        setTransactionFieldError('transactionAmount', 'مبلغ کپی‌شده معتبر نیست؛ فقط عدد مثبت با حداکثر دو رقم اعشار وارد کنید.');
        return;
    }
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const value = input.value.slice(0, start) + pastedText + input.value.slice(end);
    const targetCursor = start + pastedText.length;
    input.value = value;
    formatTransactionAmountInput({
        target: input,
        get selectionStart() { return targetCursor; }
    });
}

function restoreTransactionAmountCursor(input, digitsBeforeCursor, decimalBeforeCursor, fractionDigitsBeforeCursor) {
    let position = 0;
    let digits = 0;

    while (position < input.value.length && digits < digitsBeforeCursor && input.value[position] !== '.') {
        if (/\d/.test(input.value[position])) digits++;
        position++;
    }

    if (decimalBeforeCursor && input.value.includes('.')) {
        position = input.value.indexOf('.') + 1;
        let fractionDigits = 0;
        while (position < input.value.length && fractionDigits < fractionDigitsBeforeCursor) {
            if (/\d/.test(input.value[position])) fractionDigits++;
            position++;
        }
    }

    input.setSelectionRange(position, position);
}

function formatTransactionAmountInputValue(value) {
    const parts = String(value === undefined || value === null ? '' : value).split('.');
    const integer = (parts[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return integer + (parts.length > 1 ? '.' + parts[1] : '');
}

function formatTransactionAmountDisplay(value) {
    const parts = String(value === undefined || value === null ? '' : value).replace(/,/g, '').split('.');
    const integer = (parts[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return integer + (parts.length > 1 ? '.' + parts[1] : '');
}

function getTodayDate() {
    const now = new Date();
    const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    return localDate.toISOString().slice(0, 10);
}

function prepareDeleteTransaction(transaction, state) {
    state.deletingId = String(transaction.id);
    document.getElementById('deleteTransactionTitle').textContent = transaction.title || '';
    hideDeleteTransactionMessage();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTransactionModal')).show();
}

async function deleteTransaction(state) {
    if (state.deleting || state.deletingId === null) return;
    const button = document.getElementById('confirmDeleteTransactionButton');
    state.deleting = true;
    button.disabled = true;
    button.textContent = 'در حال حذف...';
    hideDeleteTransactionMessage();

    try {
        const result = await requestTransactionsApi(
            state.transactionsUrl + '/' + encodeURIComponent(state.deletingId) + '/delete',
            { method: 'POST' }
        );
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTransactionModal')).hide();
        const refreshed = await loadTransactionsPage(state);
        if (refreshed) {
            showPageMessage(result.message || 'تراکنش حذف شد.', 'success');
        } else {
            showPageMessage((result.message || 'تراکنش حذف شد.') + ' اما بروزرسانی فهرست کامل نشد؛ دوباره تلاش کنید.', 'warning', function () {
                loadTransactionsPage(state);
            });
        }
    } catch (error) {
        showDeleteTransactionMessage(error.message || 'حذف تراکنش انجام نشد.', 'danger');
    } finally {
        state.deleting = false;
        button.disabled = false;
        button.textContent = 'حذف تراکنش';
    }
}

function setTransactionFieldError(fieldId, message) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    input.classList.add('is-invalid');
    error.textContent = message;
    error.classList.add('d-block');
}

function clearTransactionFieldError(fieldId) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    if (!input || !error) return;
    input.classList.remove('is-invalid');
    error.textContent = '';
    error.classList.remove('d-block');
}

function showTransactionFormMessage(message, type) {
    const element = document.getElementById('transactionFormMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type;
}

function hideTransactionFormMessage() {
    const element = document.getElementById('transactionFormMessage');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none';
}

function showCategoryWarning(message) {
    const element = document.getElementById('transactionCategoriesWarning');
    element.textContent = message;
    element.className = 'alert alert-warning';
}

function hideCategoryWarning() {
    const element = document.getElementById('transactionCategoriesWarning');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none';
}

function showDeleteTransactionMessage(message, type) {
    const element = document.getElementById('deleteTransactionMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type + ' mt-3 mb-0';
}

function hideDeleteTransactionMessage() {
    const element = document.getElementById('deleteTransactionMessage');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none mt-3 mb-0';
}

function showPageMessage(message, type, retry) {
    const element = document.getElementById('transactionsMessage');
    element.replaceChildren();
    const text = document.createElement('span');
    text.textContent = message;
    element.appendChild(text);
    if (retry) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-' + (type === 'danger' ? 'danger' : 'dark') + ' me-2';
        button.textContent = 'تلاش دوباره';
        button.addEventListener('click', retry);
        element.appendChild(button);
    }
    element.className = 'alert alert-' + type;
}

function hidePageMessage() {
    const element = document.getElementById('transactionsMessage');
    if (!element) return;
    element.replaceChildren();
    element.className = 'alert d-none';
}

function setTransactionPanel(state, showDeleted) {
    document.getElementById('activeTransactionsPanel').hidden = showDeleted;
    document.getElementById('deletedTransactionsPanel').hidden = !showDeleted;
    document.getElementById('showActiveTransactionsButton').className = 'btn ' + (showDeleted ? 'btn-outline-primary' : 'btn-primary');
    document.getElementById('showDeletedTransactionsButton').className = 'btn ' + (showDeleted ? 'btn-primary' : 'btn-outline-primary');
    document.getElementById('showActiveTransactionsButton').setAttribute('aria-pressed', String(!showDeleted));
    document.getElementById('showDeletedTransactionsButton').setAttribute('aria-pressed', String(showDeleted));
    if (showDeleted && !state.deletedLoaded) loadDeletedTransactions(state);
}

async function loadDeletedTransactions(state) {
    if (state.deletedLoading) return;
    state.deletedLoading = true;
    setDeletedTransactionTableMessage('در حال دریافت تراکنش‌های حذف‌شده...', 'text-secondary');
    try {
        const result = await requestTransactionsApi(state.transactionsUrl + '/deleted', { method: 'GET' });
        if (!Array.isArray(result.transactions)) throw new Error('ساختار فهرست تراکنش‌های حذف‌شده معتبر نیست.');
        state.deletedTransactions = result.transactions;
        state.deletedLoaded = true;
        renderDeletedTransactions(state);
    } catch (error) {
        renderDeletedTransactionLoadError(error.message, function () { loadDeletedTransactions(state); });
    } finally {
        state.deletedLoading = false;
    }
}

function renderDeletedTransactions(state) {
    const body = document.getElementById('deletedTransactionsTableBody');
    body.replaceChildren();
    if (!state.deletedTransactions.length) {
        setDeletedTransactionTableMessage('تراکنش حذف‌شده‌ای وجود ندارد.', 'text-secondary');
        return;
    }

    state.deletedTransactions.forEach(function (transaction) {
        const row = document.createElement('tr');
        [transaction.title || '', formatTransactionAmountDisplay(transaction.amount), transaction.category_name || '—', transaction.transaction_date || '—', formatTransactionDateTime(transaction.deleted_at)].forEach(function (value, index) {
            const cell = document.createElement(index === 0 ? 'th' : 'td');
            if (index === 0) cell.scope = 'row';
            cell.textContent = value;
            row.appendChild(cell);
        });

        const actions = document.createElement('td');
        const canRestore = transaction.restore_available === true;
        const categoryIsActive = transaction.category_deleted_at === null;
        const status = document.createElement('div');
        status.className = 'small mb-2 ' + (canRestore && categoryIsActive ? 'text-success' : 'text-warning');
        status.textContent = !canRestore
            ? 'مهلت ۳۰ روزه بازیابی پایان یافته'
            : categoryIsActive
                ? 'قابل بازیابی'
                : 'ابتدا دسته‌بندی «' + (transaction.category_name || 'نامشخص') + '» را بازیابی کنید';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-success';
        button.textContent = 'بازیابی';
        button.dataset.restoreId = String(transaction.id);
        button.disabled = !canRestore || !categoryIsActive || state.restoringId !== null;
        actions.append(status, button);
        row.appendChild(actions);
        body.appendChild(row);
    });
}

function formatTransactionDateTime(value) {
    return typeof value === 'string' && value ? value.replace('T', ' ').slice(0, 16) : '—';
}

async function restoreTransaction(id, state, button) {
    if (state.restoringId !== null || !window.confirm('آیا از بازیابی این تراکنش اطمینان دارید؟ اثر مالی آن توسط سرور اعمال می‌شود.')) return;
    state.restoringId = String(id);
    button.disabled = true;
    button.textContent = 'در حال بازیابی...';
    try {
        const result = await requestTransactionsApi(state.transactionsUrl + '/' + encodeURIComponent(id) + '/restore', { method: 'POST' });
        state.deletedLoaded = false;
        await Promise.all([loadTransactionsPage(state), loadDeletedTransactions(state)]);
        showPageMessage(result.message || 'تراکنش بازیابی شد.', 'success');
    } catch (error) {
        showPageMessage(error.message || 'بازیابی تراکنش انجام نشد.', 'danger');
        button.disabled = false;
        button.textContent = 'بازیابی';
    } finally {
        state.restoringId = null;
        if (state.deletedLoaded) renderDeletedTransactions(state);
    }
}

function setDeletedTransactionTableMessage(message, textClass) {
    const body = document.getElementById('deletedTransactionsTableBody');
    body.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 6;
    cell.className = 'text-center py-5 ' + textClass;
    cell.textContent = message;
    row.appendChild(cell);
    body.appendChild(row);
}

function renderDeletedTransactionLoadError(message, retry) {
    setDeletedTransactionTableMessage(message, 'text-danger');
    const cell = document.getElementById('deletedTransactionsTableBody').firstElementChild.firstElementChild;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm me-2';
    button.textContent = 'تلاش دوباره';
    button.addEventListener('click', retry);
    cell.append(document.createElement('br'), button);
}
