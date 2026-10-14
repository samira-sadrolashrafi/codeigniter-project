<?php
$username = isset($username) && $username !== '' ? $username : 'کاربر';
$this->load->view('layouts/header', array(
    'page_title' => 'داشبورد | مدیریت هزینه‌ها',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
$balance_url = site_url('api/dashboard/balance');
$categories_url = site_url('api/categories');
$transactions_url = site_url('api/transactions');
$categories_page_url = site_url('categories');
$transactions_page_url = site_url('transactions');
?>

<div class="container-fluid dashboard-shell px-3 px-lg-4"
     data-balance-url="<?php echo html_escape($balance_url); ?>"
     data-categories-url="<?php echo html_escape($categories_url); ?>"
     data-transactions-url="<?php echo html_escape($transactions_url); ?>">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'dashboard')); ?>

        <main class="col-lg-9 col-xl-10 dashboard-main">
            <?php $this->load->view('layouts/app_header', array('app_page_title' => 'داشبورد', 'app_page_description' => 'نمای کلی وضعیت مالی شما', 'app_username' => $username)); ?>

            <div id="dashboardMessage" class="alert d-none mt-3" role="alert" aria-live="polite"></div>

            <section class="row g-3 g-xl-4 mt-1 mb-4" aria-label="وضعیت مالی">
                <div class="col-md-6">
                    <article class="card dashboard-card h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-secondary mb-3">موجودی فعلی</h2>
                            <p id="balanceAmount" class="dashboard-money text-success mb-0" aria-live="polite">در حال دریافت...</p>
                            <button type="button" class="btn btn-link px-0 pb-0 mt-3" data-bs-toggle="modal" data-bs-target="#balanceModal">تغییر موجودی</button>
                            <button type="button" class="btn btn-link link-danger px-0 pb-0 mt-3 me-3" data-bs-toggle="modal" data-bs-target="#resetBalanceModal">صفر کردن موجودی</button>
                        </div>
                    </article>
                </div>
                <div class="col-md-6">
                    <article class="card dashboard-card h-100">
                        <div class="card-body p-4">
                            <h2 class="h6 text-secondary mb-3">بدهی</h2>
                            <p id="debtAmount" class="dashboard-money text-danger mb-0" aria-live="polite">در حال دریافت...</p>
                            <p class="small text-secondary mt-3 mb-0">مقدار بدهی از موجودی منفی محاسبه و فقط نمایش داده می‌شود.</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="card dashboard-card mb-4" aria-labelledby="quickActionsHeading">
                <div class="card-body p-4">
                    <h2 id="quickActionsHeading" class="h6 mb-3">عملیات سریع</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="addDashboardTransactionButton" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dashboardTransactionModal" disabled>افزودن تراکنش</button>
                        <a class="btn btn-outline-secondary" href="<?php echo html_escape($categories_page_url); ?>">مدیریت دسته‌بندی‌ها</a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#balanceModal">تغییر موجودی</button>
                    </div>
                </div>
            </section>

            <div class="row g-3 g-xl-4">
                <div class="col-xl-8">
                    <section class="card dashboard-card h-100" aria-labelledby="recentTransactionsHeading">
                        <div class="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex align-items-center justify-content-between gap-2">
                            <h2 id="recentTransactionsHeading" class="h6 mb-0">آخرین تراکنش‌ها</h2>
                            <a class="small text-decoration-none" href="<?php echo html_escape($transactions_page_url); ?>">مشاهده همه تراکنش‌ها</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light"><tr><th>عنوان</th><th>دسته‌بندی</th><th>مبلغ</th><th>تاریخ</th><th><span class="visually-hidden">عملیات</span></th></tr></thead>
                                    <tbody id="dashboardTransactionsBody" aria-live="polite"><tr><td colspan="5" class="text-center text-secondary py-5">در حال دریافت تراکنش‌ها...</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-xl-4">
                    <section class="card dashboard-card h-100" aria-labelledby="dashboardCategoriesHeading">
                        <div class="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex align-items-center justify-content-between gap-2">
                            <h2 id="dashboardCategoriesHeading" class="h6 mb-0">دسته‌بندی‌ها</h2>
                            <a class="small text-decoration-none" href="<?php echo html_escape($categories_page_url); ?>">مدیریت</a>
                        </div>
                        <ul id="dashboardCategoriesList" class="list-group list-group-flush px-3 pb-3" aria-live="polite">
                            <li class="list-group-item text-secondary">در حال دریافت دسته‌بندی‌ها...</li>
                        </ul>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="resetBalanceModal" tabindex="-1" aria-labelledby="resetBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="resetBalanceModalLabel">تأیید صفر کردن موجودی</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button></div>
        <div class="modal-body"><p class="mb-0">آیا از صفر کردن موجودی خود اطمینان دارید؟</p><div id="resetBalanceMessage" class="alert d-none mt-3 mb-0" role="alert" aria-live="polite"></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button><button type="button" id="confirmResetBalanceButton" class="btn btn-danger">بله، موجودی را صفر کن</button></div>
    </div></div>
</div>

<div class="modal fade" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form id="balanceForm" novalidate>
            <div class="modal-header"><h2 class="modal-title fs-5" id="balanceModalLabel">تغییر موجودی</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button></div>
            <div class="modal-body">
                <div id="balanceModalMessage" class="alert d-none" role="alert" aria-live="polite"></div>
                <label for="balanceInput" class="form-label">مبلغ جدید موجودی</label>
                <div class="input-group" dir="ltr"><span class="input-group-text">تومان</span><input type="text" class="form-control" id="balanceInput" name="balance" inputmode="decimal" autocomplete="off" required aria-describedby="balanceInputHelp balanceInputError"></div>
                <div id="balanceInputHelp" class="form-text">برای ثبت بدهی، علامت منفی را وارد کنید.</div><div id="balanceInputError" class="invalid-feedback">مبلغ معتبر وارد کنید.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button><button type="submit" id="saveBalanceButton" class="btn btn-primary">ذخیره</button></div>
        </form>
    </div></div>
</div>

<?php $this->load->view('transactions/form_modal', array('form_prefix' => 'dashboardTransaction')); ?>

<div class="modal fade" id="dashboardDeleteTransactionModal" tabindex="-1" aria-labelledby="dashboardDeleteTransactionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="dashboardDeleteTransactionTitle">حذف تراکنش</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button></div>
        <div class="modal-body"><p>آیا از حذف تراکنش «<span id="dashboardDeleteTransactionName"></span>» اطمینان دارید؟</p><div id="dashboardDeleteTransactionMessage" class="alert d-none" role="alert"></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button><button type="button" id="confirmDashboardDeleteButton" class="btn btn-danger">حذف تراکنش</button></div>
    </div></div>
</div>

<?php $this->load->view('layouts/logout_modal'); ?>

<?php
$this->load->view('layouts/footer', array(
    'page_scripts' => array(base_url('assets/js/dashboard.js'))
));
?>
