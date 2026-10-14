document.addEventListener('DOMContentLoaded', initializeCategoriesPage);

function initializeCategoriesPage() {
    const page = document.querySelector('[data-categories-api-url]');
    if (!page) return;

    const state = {
        apiUrl: page.dataset.categoriesApiUrl.replace(/\/$/, ''),
        categories: [],
        editingId: null,
        deletingId: null,
        deletedCategories: [],
        deletedLoaded: false,
        deletedLoading: false,
        restoringId: null,
        loading: false,
        saving: false,
        deleting: false
    };

    const form = document.getElementById('categoryForm');
    const categoryModal = document.getElementById('categoryModal');
    const deleteModal = document.getElementById('deleteCategoryModal');

    document.getElementById('addCategoryButton').addEventListener('click', function () {
        prepareNewCategory(state);
    });
    document.getElementById('categoriesTableBody').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const category = state.categories.find(function (item) {
            return String(item.id) === button.dataset.id;
        });
        if (!category) return;

        if (button.dataset.action === 'edit') {
            prepareEditCategory(category, state);
        } else if (button.dataset.action === 'delete') {
            prepareDeleteCategory(category, state);
        }
    });

    form.addEventListener('submit', function (event) {
        submitCategoryForm(event, state);
    });
    document.getElementById('confirmDeleteCategoryButton').addEventListener('click', function () {
        deleteCategory(state);
    });
    document.getElementById('showActiveCategoriesButton').addEventListener('click', function () {
        setCategoryPanel(state, false);
    });
    document.getElementById('showDeletedCategoriesButton').addEventListener('click', function () {
        setCategoryPanel(state, true);
    });
    document.getElementById('deletedCategoriesTableBody').addEventListener('click', function (event) {
        const button = event.target.closest('button[data-restore-id]');
        if (button) restoreCategory(button.dataset.restoreId, state, button);
    });

    ['categoryName', 'categoryType'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            clearFieldError(id);
            hideFormMessage();
        });
        document.getElementById(id).addEventListener('change', function () {
            clearFieldError(id);
            hideFormMessage();
        });
    });

    categoryModal.addEventListener('hidden.bs.modal', function () {
        resetCategoryForm(state);
    });
    deleteModal.addEventListener('hidden.bs.modal', hideDeleteMessage);

    loadCategories(state);
}

async function requestCategoriesApi(url, options) {
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
        throw new Error('پاسخ سرور قابل خواندن نیست. لطفاً دوباره وارد حساب شوید و مجدداً تلاش کنید.');
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

async function loadCategories(state) {
    if (state.loading) return;
    state.loading = true;
    setTableMessage('در حال دریافت دسته‌بندی‌ها...', 'text-secondary');

    try {
        const result = await requestCategoriesApi(state.apiUrl, { method: 'GET' });
        if (!Array.isArray(result.categories)) {
            throw new Error('ساختار فهرست دریافتی از سرور معتبر نیست.');
        }
        state.categories = result.categories;
        renderCategories(state.categories);
    } catch (error) {
        state.categories = [];
        renderLoadError(error.message, function () {
            hidePageMessage();
            loadCategories(state);
        });
        showPageMessage(error.message || 'دریافت دسته‌بندی‌ها انجام نشد.', 'danger');
    } finally {
        state.loading = false;
    }
}

function renderCategories(categories) {
    const tableBody = document.getElementById('categoriesTableBody');
    tableBody.replaceChildren();

    if (categories.length === 0) {
        setTableMessage('هنوز دسته‌بندی فعالی ثبت نشده است.', 'text-secondary');
        return;
    }

    categories.forEach(function (category) {
        const row = document.createElement('tr');
        const nameCell = document.createElement('th');
        nameCell.scope = 'row';
        nameCell.textContent = category.name || '';

        const typeCell = document.createElement('td');
        typeCell.textContent = category.type === 'income' ? 'درآمد' : category.type === 'expense' ? 'هزینه' : '—';

        const dateCell = document.createElement('td');
        dateCell.className = 'text-nowrap';
        dateCell.textContent = formatCategoryDate(category.created_at);

        const actionsCell = document.createElement('td');
        actionsCell.className = 'text-nowrap';
        actionsCell.appendChild(createCategoryAction('ویرایش', 'edit', category.id, 'btn-outline-primary'));
        actionsCell.appendChild(document.createTextNode(' '));
        actionsCell.appendChild(createCategoryAction('حذف', 'delete', category.id, 'btn-outline-danger'));

        row.append(nameCell, typeCell, dateCell, actionsCell);
        tableBody.appendChild(row);
    });
}

function createCategoryAction(label, action, id, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm ' + className;
    button.textContent = label;
    button.dataset.action = action;
    button.dataset.id = String(id);
    return button;
}

function formatCategoryDate(value) {
    if (typeof value !== 'string' || value === '') return '—';
    return value.replace('T', ' ').slice(0, 16);
}

function setTableMessage(message, textClass) {
    const tableBody = document.getElementById('categoriesTableBody');
    tableBody.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.className = 'text-center py-5 ' + textClass;
    cell.textContent = message;
    row.appendChild(cell);
    tableBody.appendChild(row);
}

function renderLoadError(message, retry) {
    const tableBody = document.getElementById('categoriesTableBody');
    tableBody.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.className = 'text-center py-5';
    const text = document.createElement('p');
    text.className = 'text-danger mb-3';
    text.textContent = message;
    const retryButton = document.createElement('button');
    retryButton.type = 'button';
    retryButton.className = 'btn btn-outline-primary btn-sm';
    retryButton.textContent = 'تلاش دوباره';
    retryButton.addEventListener('click', retry);
    cell.append(text, retryButton);
    row.appendChild(cell);
    tableBody.appendChild(row);
}

function prepareNewCategory(state) {
    resetCategoryForm(state);
    document.getElementById('categoryModalTitle').textContent = 'افزودن دسته‌بندی';
}

function prepareEditCategory(category, state) {
    resetCategoryForm(state);
    state.editingId = String(category.id);
    document.getElementById('categoryModalTitle').textContent = 'ویرایش دسته‌بندی';
    document.getElementById('categoryName').value = category.name || '';
    document.getElementById('categoryType').value = category.type || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal')).show();
}

function prepareDeleteCategory(category, state) {
    state.deletingId = String(category.id);
    document.getElementById('deleteCategoryName').textContent = category.name || '';
    hideDeleteMessage();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCategoryModal')).show();
}

function resetCategoryForm(state) {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    form.reset();
    document.getElementById('categoryModalTitle').textContent = 'افزودن دسته‌بندی';
    document.getElementById('categoryFormMessage').textContent = '';
    document.getElementById('categoryFormMessage').className = 'alert d-none';
    clearFieldError('categoryName');
    clearFieldError('categoryType');
    if (state) state.editingId = null;
}

async function submitCategoryForm(event, state) {
    event.preventDefault();
    if (state.saving) return;

    hideFormMessage();
    if (!validateCategoryForm()) return;

    const name = document.getElementById('categoryName').value.trim();
    const type = document.getElementById('categoryType').value;
    const body = new FormData();
    body.append('name', name);
    body.append('type', type);

    const isEditing = state.editingId !== null;
    const url = isEditing
        ? state.apiUrl + '/' + encodeURIComponent(state.editingId) + '/update'
        : state.apiUrl + '/store';
    const button = document.getElementById('saveCategoryButton');
    state.saving = true;
    button.disabled = true;
    button.textContent = 'در حال ذخیره...';

    try {
        const result = await requestCategoriesApi(url, { method: 'POST', body: body });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal')).hide();
        showPageMessage(result.message || (isEditing ? 'دسته‌بندی ویرایش شد.' : 'دسته‌بندی اضافه شد.'), 'success');
        await loadCategories(state);
    } catch (error) {
        displayCategoryRequestError(error);
    } finally {
        state.saving = false;
        button.disabled = false;
        button.textContent = 'ذخیره';
    }
}

function validateCategoryForm() {
    let valid = true;
    const name = document.getElementById('categoryName').value.trim();
    const type = document.getElementById('categoryType').value;
    const nameLength = Array.from(name).length;

    if (nameLength < 1 || nameLength > 100) {
        setFieldError('categoryName', 'نام دسته‌بندی باید بین ۱ تا ۱۰۰ نویسه باشد.');
        valid = false;
    }
    if (type !== 'income' && type !== 'expense') {
        setFieldError('categoryType', 'نوع دسته‌بندی را انتخاب کنید.');
        valid = false;
    }
    return valid;
}

async function deleteCategory(state) {
    if (state.deleting || state.deletingId === null) return;
    const button = document.getElementById('confirmDeleteCategoryButton');
    state.deleting = true;
    button.disabled = true;
    button.textContent = 'در حال حذف...';
    hideDeleteMessage();

    try {
        const result = await requestCategoriesApi(
            state.apiUrl + '/' + encodeURIComponent(state.deletingId) + '/delete',
            { method: 'POST' }
        );
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCategoryModal')).hide();
        showPageMessage(result.message || 'دسته‌بندی حذف شد.', 'success');
        await loadCategories(state);
    } catch (error) {
        showDeleteMessage(error.message || 'حذف دسته‌بندی انجام نشد.', 'danger');
    } finally {
        state.deleting = false;
        button.disabled = false;
        button.textContent = 'حذف دسته‌بندی';
    }
}

function displayCategoryRequestError(error) {
    const fieldMap = {
        name: 'categoryName',
        type: 'categoryType'
    };
    let fieldErrorShown = false;

    if (error.errors && typeof error.errors === 'object') {
        Object.keys(fieldMap).forEach(function (field) {
            if (typeof error.errors[field] === 'string' && error.errors[field] !== '') {
                setFieldError(fieldMap[field], error.errors[field]);
                fieldErrorShown = true;
            }
        });
    }

    if (!fieldErrorShown) showFormMessage(error.message || 'ذخیره دسته‌بندی انجام نشد.', 'danger');
}

function setFieldError(fieldId, message) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    input.classList.add('is-invalid');
    error.textContent = message;
}

function clearFieldError(fieldId) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    if (!input || !error) return;
    input.classList.remove('is-invalid');
    error.textContent = '';
}

function showFormMessage(message, type) {
    const element = document.getElementById('categoryFormMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type;
}

function hideFormMessage() {
    const element = document.getElementById('categoryFormMessage');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none';
}

function showDeleteMessage(message, type) {
    const element = document.getElementById('deleteCategoryMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type + ' mt-3 mb-0';
}

function hideDeleteMessage() {
    const element = document.getElementById('deleteCategoryMessage');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none mt-3 mb-0';
}

function showPageMessage(message, type) {
    const element = document.getElementById('categoriesMessage');
    element.textContent = message;
    element.className = 'alert alert-' + type;
}

function hidePageMessage() {
    const element = document.getElementById('categoriesMessage');
    if (!element) return;
    element.textContent = '';
    element.className = 'alert d-none';
}

function setCategoryPanel(state, showDeleted) {
    document.getElementById('activeCategoriesPanel').hidden = showDeleted;
    document.getElementById('deletedCategoriesPanel').hidden = !showDeleted;
    document.getElementById('showActiveCategoriesButton').className = 'btn ' + (showDeleted ? 'btn-outline-primary' : 'btn-primary');
    document.getElementById('showDeletedCategoriesButton').className = 'btn ' + (showDeleted ? 'btn-primary' : 'btn-outline-primary');
    document.getElementById('showActiveCategoriesButton').setAttribute('aria-pressed', String(!showDeleted));
    document.getElementById('showDeletedCategoriesButton').setAttribute('aria-pressed', String(showDeleted));
    if (showDeleted && !state.deletedLoaded) loadDeletedCategories(state);
}

async function loadDeletedCategories(state) {
    if (state.deletedLoading) return;
    state.deletedLoading = true;
    setDeletedCategoryTableMessage('در حال دریافت دسته‌بندی‌های حذف‌شده...', 'text-secondary');
    try {
        const result = await requestCategoriesApi(state.apiUrl + '/deleted', { method: 'GET' });
        if (!Array.isArray(result.categories)) throw new Error('ساختار فهرست دسته‌بندی‌های حذف‌شده معتبر نیست.');
        state.deletedCategories = result.categories;
        state.deletedLoaded = true;
        renderDeletedCategories(state);
    } catch (error) {
        renderDeletedCategoryLoadError(error.message, function () { loadDeletedCategories(state); });
    } finally {
        state.deletedLoading = false;
    }
}

function renderDeletedCategories(state) {
    const body = document.getElementById('deletedCategoriesTableBody');
    body.replaceChildren();
    if (!state.deletedCategories.length) {
        setDeletedCategoryTableMessage('دسته‌بندی حذف‌شده‌ای وجود ندارد.', 'text-secondary');
        return;
    }
    state.deletedCategories.forEach(function (category) {
        const row = document.createElement('tr');
        [category.name || '', category.type === 'income' ? 'درآمد' : category.type === 'expense' ? 'هزینه' : '—', formatCategoryDate(category.deleted_at)].forEach(function (value, index) {
            const cell = document.createElement(index === 0 ? 'th' : 'td');
            if (index === 0) cell.scope = 'row';
            cell.textContent = value;
            row.appendChild(cell);
        });
        const status = document.createElement('td');
        const canRestore = category.restore_available === true;
        status.textContent = canRestore ? 'در مهلت بازیابی' : 'مهلت بازیابی پایان یافته';
        const actions = document.createElement('td');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-success';
        button.textContent = 'بازیابی';
        button.dataset.restoreId = String(category.id);
        button.disabled = !canRestore || state.restoringId !== null;
        actions.appendChild(button);
        row.append(status, actions);
        body.appendChild(row);
    });
}

async function restoreCategory(id, state, button) {
    if (state.restoringId !== null || !window.confirm('آیا از بازیابی این دسته‌بندی اطمینان دارید؟')) return;
    state.restoringId = String(id);
    button.disabled = true;
    button.textContent = 'در حال بازیابی...';
    try {
        const result = await requestCategoriesApi(state.apiUrl + '/' + encodeURIComponent(id) + '/restore', { method: 'POST' });
        state.deletedLoaded = false;
        await Promise.all([loadCategories(state), loadDeletedCategories(state)]);
        showPageMessage(result.message || 'دسته‌بندی بازیابی شد.', 'success');
    } catch (error) {
        showPageMessage(error.message || 'بازیابی دسته‌بندی انجام نشد.', 'danger');
        button.disabled = false;
        button.textContent = 'بازیابی';
    } finally {
        state.restoringId = null;
        if (state.deletedLoaded) renderDeletedCategories(state);
    }
}

function setDeletedCategoryTableMessage(message, textClass) {
    const body = document.getElementById('deletedCategoriesTableBody');
    body.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 5;
    cell.className = 'text-center py-5 ' + textClass;
    cell.textContent = message;
    row.appendChild(cell);
    body.appendChild(row);
}

function renderDeletedCategoryLoadError(message, retry) {
    setDeletedCategoryTableMessage(message, 'text-danger');
    const body = document.getElementById('deletedCategoriesTableBody');
    const cell = body.firstElementChild.firstElementChild;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm me-2';
    button.textContent = 'تلاش دوباره';
    button.addEventListener('click', retry);
    cell.appendChild(document.createElement('br'));
    cell.appendChild(button);
}
