<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Transactions page
 * CodeIgniter 3, Bootstrap 5.3 RTL.
 */

$transactions_api_url = site_url('api/transactions');
$categories_api_url = site_url('api/categories');

$view_username = isset($display_name)
    && is_string($display_name)
    && trim($display_name) !== ''
        ? trim($display_name)
        : (
            isset($username)
            && is_string($username)
            && trim($username) !== ''
                ? trim($username)
                : 'کاربر'
        );

$this->load->view('layouts/header', array(
    'page_title' => 'مدیریت تراکنش‌ها',
    'page_styles' => array(
        base_url(
            'assets/css/dashboard.css?v=' .
            filemtime(FCPATH . 'assets/css/dashboard.css')
        )
    )
));
?>

<div class="container-fluid dashboard-shell px-0">
    <div class="row g-0">

        <?php
        $this->load->view(
            'layouts/app_sidebar',
            array('active_page' => 'transactions')
        );
        ?>

        <main
            class="col-lg-9 col-xl-10 dashboard-main"
            id="transactionsMain"
            data-component="transactions-page"
            data-transactions-api-url="<?php echo html_escape($transactions_api_url); ?>"
            data-categories-api-url="<?php echo html_escape($categories_api_url); ?>"
        >

            <?php
            $this->load->view('layouts/app_header', array(
                'app_page_title' => 'تراکنش‌ها',
                'app_page_description' =>
                    'ثبت، ویرایش، مدیریت و بازیابی درآمدها و هزینه‌ها',
                'app_username' => $view_username
            ));
            ?>

            <div class="px-3 px-xl-4 pt-4">

                <!-- Page heading -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">
                            فهرست تراکنش‌ها
                        </h2>

                        <p class="text-body-secondary small mb-0">
                            سوابق مالی خود را جست‌وجو و مدیریت کنید.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="addTransactionButton"
                        class="btn btn-primary d-inline-flex align-items-center gap-2"
                    >
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>افزودن تراکنش</span>
                    </button>
                </div>

                <div
                    id="transactionsMessage"
                    class="alert d-none"
                    role="alert"
                    aria-live="polite"
                ></div>

                <!-- Active and deleted transactions -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">

                    <div
                        class="btn-group"
                        role="group"
                        aria-label="وضعیت فهرست تراکنش‌ها"
                    >
                        <button
                            type="button"
                            id="showActiveTransactionsButton"
                            class="btn btn-primary"
                            aria-controls="activeTransactionsPanel"
                            aria-pressed="true"
                        >
                            <i class="fa-solid fa-list-check me-1" aria-hidden="true"></i>
                            فعال
                        </button>

                        <button
                            type="button"
                            id="showDeletedTransactionsButton"
                            class="btn btn-outline-primary"
                            aria-controls="deletedTransactionsPanel"
                            aria-pressed="false"
                        >
                            <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i>
                            حذف‌شده
                        </button>
                    </div>

                    <span class="small text-body-secondary d-inline-flex align-items-center gap-2">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        تراکنش‌های حذف‌شده تا ۳۰ روز قابل بازیابی‌اند.
                    </span>
                </div>

                <!-- Active transactions -->
                <section
                    id="activeTransactionsPanel"
                    aria-labelledby="activeTransactionsHeading"
                    data-component="active-transactions"
                >
                    <h2
                        id="activeTransactionsHeading"
                        class="visually-hidden"
                    >
                        تراکنش‌های فعال
                    </h2>

                    <!-- Transaction filters -->
                    <section
                        class="card dashboard-card mb-3"
                        aria-labelledby="transactionFiltersHeading"
                        data-component="transaction-filters"
                    >
                        <div class="card-body p-3 p-md-4">

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i
                                    class="fa-solid fa-filter text-primary"
                                    aria-hidden="true"
                                ></i>

                                <h3
                                    id="transactionFiltersHeading"
                                    class="h6 fw-bold mb-0"
                                >
                                    جست‌وجو و فیلتر
                                </h3>
                            </div>

                            <div class="row g-3 align-items-end">

                                <!-- Search -->
                                <div class="col-12 col-md-6 col-xl-4">
                                    <label
                                        for="transactionSearch"
                                        class="form-label small fw-medium"
                                    >
                                        جست‌وجو در تراکنش‌ها
                                    </label>

                                    <div class="input-group">
                                        <span
                                            class="input-group-text bg-body-tertiary"
                                            aria-hidden="true"
                                        >
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        <input
                                            type="search"
                                            id="transactionSearch"
                                            class="form-control"
                                            placeholder="عنوان یا توضیحات..."
                                            autocomplete="off"
                                        >
                                    </div>
                                </div>

                                <!-- Transaction type -->
                                <div class="col-6 col-md-3 col-xl-2">
                                    <label
                                        for="transactionTypeFilter"
                                        class="form-label small fw-medium"
                                    >
                                        نوع تراکنش
                                    </label>

                                    <select
                                        id="transactionTypeFilter"
                                        class="form-select"
                                    >
                                        <option value="all">همه انواع</option>
                                        <option value="income">درآمد</option>
                                        <option value="expense">هزینه</option>
                                    </select>
                                </div>

                                <!-- Category -->
                                <div class="col-6 col-md-3 col-xl-2">
                                    <label
                                        for="transactionCategoryFilter"
                                        class="form-label small fw-medium"
                                    >
                                        دسته‌بندی
                                    </label>

                                    <select
                                        id="transactionCategoryFilter"
                                        class="form-select"
                                    >
                                        <option value="all">همه دسته‌بندی‌ها</option>
                                    </select>
                                </div>

                                <!-- Date from -->
                                <div class="col-6 col-md-6 col-xl-2">
                                    <label
                                        for="transactionDateFrom"
                                        class="form-label small fw-medium"
                                    >
                                        از تاریخ
                                    </label>

                                    <input
                                        type="date"
                                        id="transactionDateFrom"
                                        class="form-control"
                                        autocomplete="off"
                                    >
                                </div>

                                <!-- Date to -->
                                <div class="col-6 col-md-6 col-xl-2">
                                    <label
                                        for="transactionDateTo"
                                        class="form-label small fw-medium"
                                    >
                                        تا تاریخ
                                    </label>

                                    <input
                                        type="date"
                                        id="transactionDateTo"
                                        class="form-control"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <!-- Apply and clear filters -->
                            <div class="row g-3 mt-4 align-items-end">
                                <div class="col-12 col-md-auto">
                                    <div class="d-flex align-items-center gap-2">
                                    <label
                                        for="transactionSort"
                                        class="form-label small fw-medium mb-0"
                                    >
                                        مرتب‌سازی
                                    </label>
                                    <select
                                        id="transactionSort"
                                        class="form-select"
                                        style="width: 160px"
                                    >
                                        <option value="newest" selected>جدیدترین</option>
                                        <option value="oldest">قدیمی‌ترین</option>
                                    </select>
                                    </div>
                                </div>

                                <div class="col d-flex justify-content-end gap-2">
                                    <button
                                        type="button"
                                        id="clearTransactionFiltersButton"
                                        class="btn btn-outline-secondary"
                                    >
                                        <i
                                            class="fa-solid fa-rotate-left me-1"
                                            aria-hidden="true"
                                        ></i>
                                        پاک‌کردن فیلترها
                                    </button>

                                    <button
                                        type="button"
                                        id="applyTransactionFiltersButton"
                                        class="btn btn-primary"
                                    >
                                        <i
                                            class="fa-solid fa-filter me-1"
                                            aria-hidden="true"
                                        ></i>
                                        اعمال فیلترها
                                    </button>
                                </div>

                            </div>
                        </div>
                    </section>

                    <!-- Active transactions table -->
                    <section
                        class="card dashboard-card mb-4"
                        aria-labelledby="transactionsTableHeading"
                        data-component="transactions-table"
                    >
                        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-3 px-md-4">
                            <h3
                                id="transactionsTableHeading"
                                class="h6 fw-bold mb-0"
                            >
                                <i
                                    class="fa-solid fa-table-list text-primary me-2"
                                    aria-hidden="true"
                                ></i>
                                تراکنش‌های فعال
                            </h3>

                            <span class="small text-body-secondary">
                                نمایش فهرست
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3">عنوان</th>
                                        <th scope="col">نوع</th>
                                        <th scope="col">مبلغ</th>
                                        <th scope="col">دسته‌بندی</th>
                                        <th scope="col">تاریخ</th>
                                        <th scope="col">توضیحات</th>
                                        <th scope="col" class="text-nowrap pe-3">
                                            عملیات
                                        </th>
                                    </tr>
                                </thead>

                                <tbody
                                    id="transactionsTableBody"
                                    aria-live="polite"
                                >
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="text-center text-body-secondary py-5"
                                        >
                                            <span
                                                class="spinner-border spinner-border-sm me-2"
                                                role="status"
                                                aria-hidden="true"
                                            ></span>
                                            در حال دریافت تراکنش‌ها...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Active transactions pagination -->
                        <div
                            id="transactionsPagination"
                            class="card-footer bg-body d-none"
                            data-component="pagination"
                        >
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                                <span
                                    id="transactionsPaginationSummary"
                                    class="small text-body-secondary"
                                    aria-live="polite"
                                ></span>

                                <nav
                                    aria-label="صفحه‌بندی تراکنش‌های فعال"
                                    class="tz-pagination"
                                >
                                    <ul
                                        id="transactionsPaginationList"
                                        class="pagination pagination-sm mb-0"
                                    ></ul>
                                </nav>

                            </div>
                        </div>
                    </section>
                </section>

                <!-- Deleted transactions -->
                <section
                    id="deletedTransactionsPanel"
                    class="card dashboard-card mb-4"
                    aria-labelledby="deletedTransactionsHeading"
                    data-component="deleted-transactions"
                    hidden
                >
                    <div class="card-header bg-body border-bottom py-3 px-3 px-md-4">
                        <h2
                            id="deletedTransactionsHeading"
                            class="h6 fw-bold mb-1"
                        >
                            <i
                                class="fa-regular fa-trash-can text-danger me-2"
                                aria-hidden="true"
                            ></i>
                            تراکنش‌های حذف‌شده
                        </h2>

                        <p class="small text-body-secondary mb-0">
                            بازیابی تراکنش فقط در مهلت مقرر و در صورت فعال بودن دسته‌بندی آن امکان‌پذیر است.
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">عنوان</th>
                                    <th scope="col">مبلغ</th>
                                    <th scope="col">دسته‌بندی</th>
                                    <th scope="col">تاریخ تراکنش</th>
                                    <th scope="col">زمان حذف</th>
                                    <th scope="col" class="text-nowrap pe-3">
                                        وضعیت و عملیات
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                id="deletedTransactionsTableBody"
                                aria-live="polite"
                            >
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-body-secondary py-5"
                                    >
                                        برای مشاهده تراکنش‌های حذف‌شده، این بخش را انتخاب کنید.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Deleted transactions pagination -->
                    <div
                        id="deletedTransactionsPagination"
                        class="card-footer bg-body d-none"
                        data-component="pagination"
                    >
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                            <span
                                id="deletedTransactionsPaginationSummary"
                                class="small text-body-secondary"
                                aria-live="polite"
                            ></span>

                            <nav
                                aria-label="صفحه‌بندی تراکنش‌های حذف‌شده"
                                class="tz-pagination"
                            >
                                <ul
                                    id="deletedTransactionsPaginationList"
                                    class="pagination pagination-sm mb-0"
                                ></ul>
                            </nav>

                        </div>
                    </div>
                </section>

            </div>

            <!-- Create/edit transaction modal -->
            <?php
            $this->load->view(
                'transactions/form_modal',
                array('form_prefix' => 'transaction')
            );
            ?>

            <!-- Delete transaction confirmation modal -->
            <div
                class="modal fade tz-modal"
                id="deleteTransactionModal"
                tabindex="-1"
                aria-labelledby="deleteTransactionModalTitle"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h2
                                class="modal-title fs-5 fw-semibold"
                                id="deleteTransactionModalTitle"
                            >
                                <i
                                    class="fa-regular fa-trash-can text-danger me-2"
                                    aria-hidden="true"
                                ></i>
                                حذف تراکنش
                            </h2>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="بستن"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-2">
                                آیا از حذف تراکنش «
                                <span
                                    id="deleteTransactionTitle"
                                    class="fw-semibold"
                                ></span>
                                » اطمینان دارید؟
                            </p>

                            <p class="small text-body-secondary mb-0">
                                تراکنش پس از حذف تا ۳۰ روز در بخش حذف‌شده‌ها قابل بازیابی خواهد بود.
                            </p>

                            <div
                                id="deleteTransactionMessage"
                                class="alert d-none mt-3 mb-0"
                                role="alert"
                                aria-live="polite"
                            ></div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal"
                            >
                                انصراف
                            </button>

                            <button
                                type="button"
                                id="confirmDeleteTransactionButton"
                                class="btn btn-danger"
                            >
                                <i
                                    class="fa-solid fa-trash-can me-1"
                                    aria-hidden="true"
                                ></i>
                                حذف تراکنش
                            </button>
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
    'page_scripts' => array(
        base_url('assets/js/api.js'),
        base_url('assets/js/transaction-form.js'),
        base_url('assets/js/transaction-list.js'),
        base_url('assets/js/transactions.js')
    )
));
?>
