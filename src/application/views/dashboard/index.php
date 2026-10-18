<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tarazino | Dashboard view (CI3 + Bootstrap 5.3 RTL + Font Awesome).
 *
 * This view is presentation-only. It does NOT fabricate financial numbers or
 * compute reports in PHP. Existing dashboard.js IDs are preserved so current
 * balance, debt, recent transactions and their modals remain usable.
 *
 * Financial report API supplies the selected-period income, expense and
 * cashflow chart. Balance and negative-balance debt are not date filtered.
 * All displayed dates use the existing Jalali date picker adapter; APIs use ISO.
 */
$username = isset($username) && is_string($username) && trim($username) !== ''
    ? trim($username)
    : 'کاربر';
$display_name = isset($display_name) && is_string($display_name) && trim($display_name) !== ''
    ? trim($display_name)
    : $username;

$balance_url = site_url('api/dashboard/balance');
$categories_url = site_url('api/categories');
$transactions_url = site_url('api/transactions');
$categories_page_url = site_url('categories');
$transactions_page_url = site_url('transactions');

$this->load->view('layouts/header', array(
    'page_title' => 'داشبورد',
    'page_styles' => array(base_url('assets/css/dashboard.css?v=' . filemtime(FCPATH . 'assets/css/dashboard.css')))
));
?>

<div class="container-fluid dashboard-shell px-0"
     data-balance-url="<?php echo html_escape($balance_url); ?>"
     data-categories-url="<?php echo html_escape($categories_url); ?>"
     data-transactions-url="<?php echo html_escape($transactions_url); ?>"
     data-cashflow-url="<?php echo html_escape(site_url('api/dashboard/cashflow')); ?>">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'dashboard')); ?>

        <main class="col-lg-9 col-xl-10 dashboard-main" id="dashboardMain">
            <?php $this->load->view('layouts/app_header', array(
                'app_page_title' => 'داشبورد',
                'app_page_description' => 'نمای کلی و تحلیل وضعیت مالی شما',
                'app_username' => $display_name
            )); ?>

            <div class="px-3 px-xl-4 pt-4">
                <div id="dashboardMessage" class="alert d-none" role="alert" aria-live="polite"></div>

                <!-- Intro: the financial status cards are kept independent of chart filtering. -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h4 fw-bold mb-1">سلام، <?php echo html_escape($display_name); ?> <span aria-hidden="true">👋</span></h2>
                        <p class="text-body-secondary small mb-0">با ترازینو، کنترل دخل‌وخرجت ساده‌تر میشه!</p>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"
                       href="<?php echo html_escape($transactions_page_url); ?>">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                        مشاهده تراکنش‌ها
                    </a>
                </div>

                <!-- One shared filter for the financial chart and the period totals. -->
                <section class="card dashboard-card mb-4" aria-labelledby="dashboardDateHeading" id="dashboardDateFilter">
                    <div class="card-body py-3 px-3 px-md-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1" id="dashboardDateHeading">
                                    <i class="fa-regular fa-calendar-days text-primary me-2" aria-hidden="true"></i>
                                    بازه زمانی گزارش
                                </h3>
                                <p class="text-body-secondary small mb-0">بازه انتخابی برای نمودار روند درآمد، هزینه و موجودی اعمال می‌شود.</p>
                            </div>
                            <div class="btn-group flex-wrap" role="group" aria-label="انتخاب بازه زمانی گزارش" data-dashboard-period>
                                <input class="btn-check" type="radio" name="dashboardPeriod" id="dashboardPeriodWeek" value="week" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm" for="dashboardPeriodWeek">هفته جاری</label>
                                <input class="btn-check" type="radio" name="dashboardPeriod" id="dashboardPeriodMonth" value="month" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm" for="dashboardPeriodMonth">ماه جاری</label>
                                <input class="btn-check" type="radio" name="dashboardPeriod" id="dashboardPeriodYear" value="year" autocomplete="off" checked>
                                <label class="btn btn-outline-primary btn-sm" for="dashboardPeriodYear">سال جاری</label>
                                <input class="btn-check" type="radio" name="dashboardPeriod" id="dashboardPeriodCustom" value="custom" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm" for="dashboardPeriodCustom">بازه دلخواه</label>
                            </div>
                        </div>
                        <!-- Jalali adapter shows Persian dates while hidden native inputs hold ISO dates. -->
                        <div class="row g-2 align-items-end mt-2 d-none" id="dashboardCustomRange" aria-label="تاریخ شروع و پایان بازه دلخواه">
                            <div class="col-12 col-md-4 col-xl-3">
                                <label for="dashboardDateFrom" class="form-label small mb-1">از تاریخ</label>
                                <input type="date" id="dashboardDateFrom" name="from" class="form-control form-control-sm" autocomplete="off">
                            </div>
                            <div class="col-12 col-md-4 col-xl-3">
                                <label for="dashboardDateTo" class="form-label small mb-1">تا تاریخ</label>
                                <input type="date" id="dashboardDateTo" name="to" class="form-control form-control-sm" autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                            <p id="dashboardDateFilterMessage" class="form-text mb-0 flex-grow-1" role="status" aria-live="polite">
                                در حال آماده‌سازی بازه گزارش...
                            </p>
                            <button type="button" id="applyDashboardDateFilter" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-filter me-1" aria-hidden="true"></i>
                                اعمال بازه
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Uniform four-column KPI grid; Bootstrap owns card widths. -->
                <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-3" aria-label="شاخص‌های مالی">
                    <div class="col">
                        <article class="card dashboard-card tz-kpi h-100 border-primary-subtle">
                            <div class="card-body p-3 p-xxl-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <h3 class="h6 text-body-secondary mb-0">موجودی فعلی</h3>
                                    <span class="bg-primary-subtle text-primary rounded-3 p-2" aria-hidden="true"><i class="fa-solid fa-wallet"></i></span>
                                </div>
                                <p class="mb-0 mt-auto"><span id="balanceAmount" class="tz-kpi-value tz-number text-primary" aria-live="polite">در حال دریافت...</span></p>
                                <span class="text-body-secondary small">تومان · مستقل از فیلتر زمانی</span>
                            </div>
                        </article>
                    </div>
                    <div class="col">
                        <article class="card dashboard-card tz-kpi h-100 border-success-subtle">
                            <div class="card-body p-3 p-xxl-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <h3 class="h6 text-body-secondary mb-0">درآمد دوره</h3>
                                    <span class="bg-success-subtle text-success rounded-3 p-2" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></span>
                                </div>
                                <p class="mb-0 mt-auto"><span id="incomeAmount" class="tz-kpi-value tz-number text-success" aria-live="polite">در حال دریافت...</span></p>
                                <span class="text-body-secondary small">تومان · در بازه انتخابی</span>
                            </div>
                        </article>
                    </div>
                    <div class="col">
                        <article class="card dashboard-card tz-kpi h-100 border-danger-subtle">
                            <div class="card-body p-3 p-xxl-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <h3 class="h6 text-body-secondary mb-0">هزینه دوره</h3>
                                    <span class="bg-danger-subtle text-danger rounded-3 p-2" aria-hidden="true"><i class="fa-solid fa-arrow-trend-down"></i></span>
                                </div>
                                <p class="mb-0 mt-auto"><span id="expenseAmount" class="tz-kpi-value tz-number text-danger" aria-live="polite">در حال دریافت...</span></p>
                                <span class="text-body-secondary small">تومان · در بازه انتخابی</span>
                            </div>
                        </article>
                    </div>
                    <div class="col">
                        <article class="card dashboard-card tz-kpi h-100 border-warning-subtle">
                            <div class="card-body p-3 p-xxl-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <h3 class="h6 text-body-secondary mb-0">بدهی فعلی</h3>
                                    <span class="bg-warning-subtle text-warning-emphasis rounded-3 p-2" aria-hidden="true"><i class="fa-solid fa-credit-card"></i></span>
                                </div>
                                <p class="mb-0 mt-auto"><span id="debtAmount" class="tz-kpi-value tz-number text-danger" aria-live="polite">در حال دریافت...</span></p>
                                <span class="text-body-secondary small">تومان · فقط بخش منفی موجودی، مستقل از فیلتر زمانی</span>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- Quick actions live separately from KPIs, preserving equal card sizes. -->
                <section class="d-flex flex-wrap align-items-center gap-2 mb-4" aria-label="عملیات سریع">
                    <button type="button" class="btn btn-success" id="addDashboardTransactionButton" data-bs-toggle="modal"
                            data-bs-target="#dashboardTransactionModal" disabled>
                        <i class="fa-solid fa-plus me-1" aria-hidden="true"></i> ثبت تراکنش
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dashboardAddCategoryModal">
                        <i class="fa-solid fa-folder-plus me-1" aria-hidden="true"></i> افزودن دسته‌بندی
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#balanceModal">
                        <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i> تغییر دستی موجودی
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetBalanceModal">
                        <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i> صفر کردن موجودی
                    </button>
                </section>

                <!-- The selected period controls the income, expense, and balance trend chart. -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <section class="card dashboard-card h-100" aria-labelledby="cashflowChartHeading">
                            <div class="card-body p-3 p-xxl-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <h3 id="cashflowChartHeading" class="h6 fw-bold mb-0">
                                        <i class="fa-solid fa-chart-line text-primary me-2" aria-hidden="true"></i>روند درآمد، هزینه و موجودی
                                    </h3>
                                    <span class="text-body-secondary small">بازه مشترک گزارش</span>
                                </div>
                                <div class="d-flex flex-wrap gap-3 small mb-2" aria-label="راهنمای نمودار">
                                    <span><i class="fa-solid fa-circle text-success fa-2xs me-1" aria-hidden="true"></i> درآمد</span>
                                    <span><i class="fa-solid fa-circle text-danger fa-2xs me-1" aria-hidden="true"></i> هزینه</span>
                                    <span><i class="fa-solid fa-circle text-primary fa-2xs me-1" aria-hidden="true"></i> موجودی</span>
                                </div>
                                <div class="tz-chart-area" aria-label="نمودار زمانی درآمد، هزینه و موجودی">
                                    <canvas id="cashflowChart" role="img" aria-label="روند درآمد، هزینه و موجودی در بازه انتخابی"></canvas>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="card dashboard-card mb-4" aria-labelledby="recentTransactionsHeading">
                    <div class="card-header bg-body border-0 px-3 px-xxl-4 pt-4 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 id="recentTransactionsHeading" class="h6 fw-bold mb-0">
                            <i class="fa-solid fa-clock-rotate-left text-primary me-2" aria-hidden="true"></i>آخرین تراکنش‌ها
                        </h3>
                        <a class="link-primary small text-decoration-none" href="<?php echo html_escape($transactions_page_url); ?>">
                            مشاهده همه <i class="fa-solid fa-arrow-left ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <!-- Keep exactly five columns until the transaction JS module is upgraded. -->
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3 ps-xxl-4">عنوان</th>
                                        <th scope="col">دسته‌بندی</th>
                                        <th scope="col">مبلغ (تومان)</th>
                                        <th scope="col">تاریخ</th>
                                        <th scope="col" class="text-center pe-3">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboardTransactionsBody" aria-live="polite">
                                    <tr><td colspan="5" class="text-center text-body-secondary py-5">در حال دریافت تراکنش‌ها...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Legacy JS compatibility only. The final dashboard does not display a redundant category list. -->
                <ul id="dashboardCategoriesList" class="d-none" aria-hidden="true"></ul>
            </div>
        </main>
    </div>
</div>

<!-- Manual balance adjustment: existing service ADDS this signed amount to current balance. -->
<div class="modal fade tz-modal" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="balanceForm" novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="balanceModalLabel"><i class="fa-solid fa-wallet text-primary me-2" aria-hidden="true"></i>تغییر دستی موجودی</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div id="balanceModalMessage" class="alert d-none" role="alert" aria-live="polite"></div>
                    <p class="small text-body-secondary">مقدار واردشده به موجودی فعلی اضافه می‌شود. برای کاهش موجودی، عدد منفی وارد کنید.</p>
                    <label class="form-label fw-semibold" for="balanceInput">مبلغ تغییر موجودی <span class="text-danger">*</span></label>
                    <div class="input-group" dir="ltr">
                        <span class="input-group-text">تومان</span>
                        <input type="text" class="form-control" id="balanceInput" name="balance" inputmode="numeric"
                               autocomplete="off" required aria-describedby="balanceInputHelp balanceInputError">
                    </div>
                    <div id="balanceInputHelp" class="form-text">مثال: 500000 برای افزایش یا -200000 برای کاهش.</div>
                    <div id="balanceInputError" class="invalid-feedback">مبلغ صحیح معتبر وارد کنید.</div>
                    <label for="balanceAdjustmentNote" class="form-label small mt-3">توضیحات (اختیاری)</label>
                    <textarea id="balanceAdjustmentNote" class="form-control" rows="2" disabled
                              placeholder="ثبت توضیحات پس از پیاده‌سازی تاریخچه تغییرات فعال می‌شود."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" id="saveBalanceButton" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Destructive confirmation; current API sets stored balance to 0. -->
<div class="modal fade tz-modal" id="resetBalanceModal" tabindex="-1" aria-labelledby="resetBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="resetBalanceModalLabel">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2" aria-hidden="true"></i>صفر کردن موجودی
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">آیا از صفر کردن موجودی حساب اطمینان دارید؟</p>
                <p class="small text-body-secondary mb-0">با تأیید این عملیات، موجودی فعلی برابر صفر خواهد شد.</p>
                <div id="resetBalanceMessage" class="alert d-none mt-3 mb-0" role="alert" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" id="confirmResetBalanceButton" class="btn btn-danger">
                    <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>تأیید و صفر کردن
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Shared transaction form, existing IDs, validation hooks and API behavior preserved. -->
<?php $this->load->view('transactions/form_modal', array('form_prefix' => 'dashboardTransaction')); ?>

<!-- Quick add category is submitted through the dashboard JavaScript module. -->
<div class="modal fade tz-modal" id="dashboardAddCategoryModal" tabindex="-1" aria-labelledby="dashboardAddCategoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="dashboardAddCategoryForm">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="dashboardAddCategoryTitle">
                        <i class="fa-solid fa-folder-plus text-primary me-2" aria-hidden="true"></i>افزودن دسته‌بندی
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-body-secondary">رنگ و آیکون دسته‌بندی به‌صورت پیش‌فرض توسط ترازینو تعیین می‌شود.</p>
                    <label for="dashboardCategoryName" class="form-label">نام دسته‌بندی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control mb-3" id="dashboardCategoryName" name="name" maxlength="100" required>
                    <label for="dashboardCategoryType" class="form-label">نوع دسته‌بندی <span class="text-danger">*</span></label>
                    <select id="dashboardCategoryType" name="type" class="form-select" required>
                        <option value="">نوع را انتخاب کنید</option>
                        <option value="income">درآمد</option>
                        <option value="expense">هزینه</option>
                    </select>
                    <p class="small text-body-secondary mt-3 mb-0">با ثبت دسته‌بندی، فهرست انتخاب دسته‌بندی تراکنش نیز به‌روزرسانی می‌شود.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                    <a class="btn btn-primary" href="<?php echo html_escape($categories_page_url); ?>">
                        ادامه در صفحه دسته‌بندی‌ها <i class="fa-solid fa-arrow-left ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tz-modal" id="dashboardDeleteTransactionModal" tabindex="-1" aria-labelledby="dashboardDeleteTransactionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="dashboardDeleteTransactionTitle">
                    <i class="fa-solid fa-trash-can text-danger me-2" aria-hidden="true"></i>حذف تراکنش
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <p>آیا از حذف تراکنش «<span id="dashboardDeleteTransactionName"></span>» اطمینان دارید؟</p>
                <p class="small text-body-secondary">تراکنش حذف‌شده از بخش حذف‌شده‌های صفحه تراکنش‌ها قابل بازیابی است.</p>
                <div id="dashboardDeleteTransactionMessage" class="alert d-none" role="alert" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" id="confirmDashboardDeleteButton" class="btn btn-danger">حذف تراکنش</button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/logout_modal'); ?>

<?php
$this->load->view('layouts/footer', array(
    'use_charts' => TRUE,
    'page_scripts' => array(
        base_url('assets/js/api.js'),
        base_url('assets/js/dashboard-reports.js'),
        base_url('assets/js/dashboard-transactions.js'),
        base_url('assets/js/dashboard.js')
    )
));
?>
