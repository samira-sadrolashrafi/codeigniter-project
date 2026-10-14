<?php
$this->load->view('layouts/header', array(
    'page_title' => 'مدیریت تراکنش‌ها',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
$transactions_api_url = site_url('api/transactions');
$categories_api_url = site_url('api/categories');
?>

<div class="container-fluid dashboard-shell px-3 px-lg-4">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'transactions')); ?>
        <main class="col-lg-9 col-xl-10 dashboard-main"
      data-transactions-api-url="<?php echo html_escape($transactions_api_url); ?>"
      data-categories-api-url="<?php echo html_escape($categories_api_url); ?>">
    <?php $this->load->view('layouts/app_header', array('app_page_title' => 'مدیریت تراکنش‌ها', 'app_page_description' => 'درآمدها و هزینه‌های خود را ثبت و پیگیری کنید.', 'app_username' => isset($username) ? $username : '')); ?>
    <div class="d-flex justify-content-end my-4">
        <button type="button" id="addTransactionButton" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">افزودن تراکنش</button>
    </div>
    <div id="transactionsMessage" class="alert d-none" role="alert" aria-live="polite"></div>
    <div class="btn-group mb-3" role="group" aria-label="نمایش تراکنش‌ها">
        <button type="button" id="showActiveTransactionsButton" class="btn btn-primary" aria-pressed="true">تراکنش‌های فعال</button>
        <button type="button" id="showDeletedTransactionsButton" class="btn btn-outline-primary" aria-pressed="false">تراکنش‌های حذف‌شده</button>
    </div>

    <section id="activeTransactionsPanel">
    <section class="card border-0 shadow-sm mb-4" aria-labelledby="transactionFiltersHeading">
        <div class="card-body">
            <h2 id="transactionFiltersHeading" class="h6 mb-3">جست‌وجو و فیلتر</h2>
            <div class="row g-3">
                <div class="col-lg-3">
                    <label for="transactionSearch" class="form-label">جست‌وجو در عنوان و توضیحات</label>
                    <input type="search" id="transactionSearch" class="form-control" autocomplete="off">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="transactionTypeFilter" class="form-label">نوع</label>
                    <select id="transactionTypeFilter" class="form-select">
                        <option value="all">همه</option>
                        <option value="income">درآمد</option>
                        <option value="expense">هزینه</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="transactionCategoryFilter" class="form-label">دسته‌بندی</label>
                    <select id="transactionCategoryFilter" class="form-select">
                        <option value="all">همه دسته‌بندی‌ها</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="transactionDateFrom" class="form-label">از تاریخ</label>
                    <input type="date" id="transactionDateFrom" class="form-control">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="transactionDateTo" class="form-label">تا تاریخ</label>
                    <input type="date" id="transactionDateTo" class="form-control">
                </div>
            </div>
            <p class="form-text mb-0 mt-3">فیلترها روی تراکنش‌های فعال دریافت‌شده از سرور اعمال می‌شوند.</p>
        </div>
    </section>

    <section class="card border-0 shadow-sm" aria-labelledby="transactionsTableHeading">
        <div class="card-body p-0">
            <h2 id="transactionsTableHeading" class="visually-hidden">فهرست تراکنش‌ها</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">عنوان</th>
                            <th scope="col">نوع</th>
                            <th scope="col">مبلغ</th>
                            <th scope="col">دسته‌بندی</th>
                            <th scope="col">تاریخ</th>
                            <th scope="col">توضیحات</th>
                            <th scope="col" class="text-nowrap">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody" aria-live="polite">
                        <tr><td colspan="7" class="text-center text-secondary py-5">در حال دریافت تراکنش‌ها...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    </section>
    <section id="deletedTransactionsPanel" class="card border-0 shadow-sm" aria-labelledby="deletedTransactionsHeading" hidden>
        <div class="card-body p-0">
            <h2 id="deletedTransactionsHeading" class="visually-hidden">تراکنش‌های حذف‌شده</h2>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>عنوان</th><th>مبلغ</th><th>دسته‌بندی</th><th>تاریخ تراکنش</th><th>حذف‌شده در</th><th>وضعیت و عملیات</th></tr></thead>
                <tbody id="deletedTransactionsTableBody" aria-live="polite"><tr><td colspan="6" class="text-center text-secondary py-5">برای مشاهده، این بخش را انتخاب کنید.</td></tr></tbody>
            </table></div>
        </div>
    </section>

    <?php $this->load->view('transactions/form_modal', array('form_prefix' => 'transaction')); ?>

    <div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="deleteTransactionModalTitle">حذف تراکنش</h2>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">آیا از حذف تراکنش «<span id="deleteTransactionTitle" class="fw-semibold"></span>» اطمینان دارید؟</p>
                    <div id="deleteTransactionMessage" class="alert d-none mt-3 mb-0" role="alert" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" id="confirmDeleteTransactionButton" class="btn btn-danger">حذف تراکنش</button>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<?php $this->load->view('layouts/logout_modal'); ?>

<?php
$this->load->view('layouts/footer', array(
    'page_scripts' => array(base_url('assets/js/transactions.js'))
));
?>
