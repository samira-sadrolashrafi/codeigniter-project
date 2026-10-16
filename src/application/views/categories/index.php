<?php
/**
 * Tarazino | Categories management view (CodeIgniter 3).
 *
 * Bootstrap 5.3 RTL does the layout, cards, tabs, tables, forms and modals.
 * Font Awesome supplies icons; no category icons/colors are persisted.
 * The current categories.js retains ownership of data, API calls and events.
 *
 * Compatibility contract (do not rename without updating categories.js):
 * - data-categories-api-url on the page container
 * - addCategoryButton, categoriesMessage
 * - showActiveCategoriesButton, showDeletedCategoriesButton
 * - activeCategoriesPanel, deletedCategoriesPanel
 * - categoriesTableBody (4 columns), deletedCategoriesTableBody (5 columns)
 * - categoryModal, categoryForm, categoryModalTitle, categoryFormMessage
 * - categoryName, categoryType, categoryNameError, categoryTypeError
 * - saveCategoryButton, deleteCategoryModal, deleteCategoryName
 * - deleteCategoryMessage, confirmDeleteCategoryButton
 *
 * The template is component-oriented for a future Vue 3 migration.
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$this->load->view('layouts/header', array(
    'page_title' => 'مدیریت دسته‌بندی‌ها',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));

$categories_api_url = site_url('api/categories');
$view_username = isset($username) && is_string($username) ? $username : '';
?>

<div class="container-fluid dashboard-shell px-0">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'categories')); ?>

        <main class="col-lg-9 col-xl-10 dashboard-main"
              id="categoriesMain"
              data-component="categories-page"
              data-categories-api-url="<?php echo html_escape($categories_api_url); ?>">

            <?php $this->load->view('layouts/app_header', array(
                'app_page_title' => 'دسته‌بندی‌ها',
                'app_page_description' => 'دسته‌بندی‌های درآمد و هزینه خود را مدیریت کنید.',
                'app_username' => $view_username
            )); ?>

            <div class="px-3 px-xl-4 pt-4">
                <!-- Page heading and the existing JS add-category action. -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">مدیریت دسته‌بندی‌ها</h2>
                        <p class="text-body-secondary small mb-0">
                            دسته‌بندی‌های درآمد و هزینه را ایجاد، ویرایش و در صورت نیاز بازیابی کنید.
                        </p>
                    </div>
                    <button type="button" id="addCategoryButton"
                            class="btn btn-primary d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>افزودن دسته‌بندی</span>
                    </button>
                </div>

                <div id="categoriesMessage" class="alert d-none" role="alert" aria-live="polite"></div>

                <!-- Search/filter layout is reserved for the later JS phase.
                     Disabled controls avoid suggesting that filtering works today. -->
                <section class="card dashboard-card mb-3" aria-labelledby="categoryFiltersHeading"
                         data-component="category-filters">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h3 id="categoryFiltersHeading" class="h6 fw-bold mb-0">
                                <i class="fa-solid fa-filter text-primary me-2" aria-hidden="true"></i>
                                جست‌وجو و فیلتر
                            </h3>
                            <span class="badge text-bg-secondary">در مرحله JavaScript فعال می‌شود</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <label for="categoriesSearch" class="form-label small fw-medium">جست‌وجو در نام دسته‌بندی</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="search" id="categoriesSearch" class="form-control"
                                           placeholder="نام دسته‌بندی را وارد کنید" disabled
                                           autocomplete="off" aria-describedby="categoriesFiltersNote">
                                </div>
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="categoriesTypeFilter" class="form-label small fw-medium">نوع دسته‌بندی</label>
                                <select id="categoriesTypeFilter" class="form-select" disabled
                                        aria-describedby="categoriesFiltersNote">
                                    <option value="all">همه انواع</option>
                                    <option value="income">درآمد</option>
                                    <option value="expense">هزینه</option>
                                </select>
                            </div>
                        </div>
                        <p id="categoriesFiltersNote" class="form-text mb-0 mt-2">
                            جست‌وجو و فیلتر پس از تکمیل کد JavaScript صفحه فعال می‌شوند؛
                            مدیریت دسته‌بندی‌ها در حال حاضر قابل استفاده است.
                        </p>
                    </div>
                </section>

                <!-- JS toggles `hidden` on these plain divs.
                     Do not put class="card" on a hidden container: Bootstrap .card
                     uses display:flex, which can override the browser's [hidden] style. -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="btn-group" role="group" aria-label="وضعیت دسته‌بندی‌ها">
                        <button type="button" id="showActiveCategoriesButton"
                                class="btn btn-primary" aria-controls="activeCategoriesPanel" aria-pressed="true">
                            <i class="fa-solid fa-list-check me-1" aria-hidden="true"></i>
                            فعال
                        </button>
                        <button type="button" id="showDeletedCategoriesButton"
                                class="btn btn-outline-primary" aria-controls="deletedCategoriesPanel" aria-pressed="false">
                            <i class="fa-regular fa-trash-can me-1" aria-hidden="true"></i>
                            حذف‌شده
                        </button>
                    </div>
                    <span class="small text-body-secondary d-inline-flex align-items-center gap-2">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        دسته‌بندی‌های حذف‌شده تا ۳۰ روز امکان بازیابی دارند.
                    </span>
                </div>

                <!-- Active categories; the current renderer outputs four cells per row. -->
                <div id="activeCategoriesPanel" data-component="active-categories">
                    <section class="card dashboard-card mb-4" aria-labelledby="categoriesTableHeading">
                        <div class="card-header bg-body border-bottom py-3 px-3 px-md-4">
                            <h3 id="categoriesTableHeading" class="h6 fw-bold mb-0">
                                <i class="fa-solid fa-tags text-primary me-2" aria-hidden="true"></i>
                                دسته‌بندی‌های فعال
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3">نام دسته‌بندی</th>
                                        <th scope="col">نوع</th>
                                        <th scope="col">تاریخ ایجاد</th>
                                        <th scope="col" class="text-nowrap pe-3">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesTableBody" aria-live="polite">
                                    <tr>
                                        <td colspan="4" class="text-center text-body-secondary py-5">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            در حال دریافت دسته‌بندی‌ها...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Reserved for future client-side pagination. -->
                        <div id="categoriesPagination" class="card-footer bg-body d-none" data-component="pagination">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <span id="categoriesPaginationSummary" class="small text-body-secondary" aria-live="polite"></span>
                                <nav class="tz-pagination" aria-label="صفحه‌بندی دسته‌بندی‌های فعال">
                                    <ul id="categoriesPaginationList" class="pagination pagination-sm mb-0"></ul>
                                </nav>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Deleted categories; keep five columns for existing categories.js. -->
                <div id="deletedCategoriesPanel" data-component="deleted-categories" hidden>
                    <section class="card dashboard-card mb-4" aria-labelledby="deletedCategoriesHeading">
                        <div class="card-header bg-body border-bottom py-3 px-3 px-md-4">
                            <h3 id="deletedCategoriesHeading" class="h6 fw-bold mb-1">
                                <i class="fa-regular fa-trash-can text-danger me-2" aria-hidden="true"></i>
                                دسته‌بندی‌های حذف‌شده
                            </h3>
                            <p class="small text-body-secondary mb-0">
                                بازیابی دسته‌بندی در مهلت مجاز امکان‌پذیر است؛
                                وضعیت دقیق هر مورد از سرور دریافت می‌شود.
                            </p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3">نام دسته‌بندی</th>
                                        <th scope="col">نوع</th>
                                        <th scope="col">زمان حذف</th>
                                        <th scope="col">وضعیت بازیابی</th>
                                        <th scope="col" class="text-nowrap pe-3">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="deletedCategoriesTableBody" aria-live="polite">
                                    <tr>
                                        <td colspan="5" class="text-center text-body-secondary py-5">
                                            برای مشاهده دسته‌بندی‌های حذف‌شده، این بخش را انتخاب کنید.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="deletedCategoriesPagination" class="card-footer bg-body d-none" data-component="pagination">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <span id="deletedCategoriesPaginationSummary" class="small text-body-secondary" aria-live="polite"></span>
                                <nav class="tz-pagination" aria-label="صفحه‌بندی دسته‌بندی‌های حذف‌شده">
                                    <ul id="deletedCategoriesPaginationList" class="pagination pagination-sm mb-0"></ul>
                                </nav>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Shared add/edit form. JS owns modal title; keep the icon outside it. -->
            <div class="modal fade tz-modal" id="categoryModal" tabindex="-1"
                 aria-labelledby="categoryModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="categoryForm" novalidate>
                            <div class="modal-header">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-tags text-primary" aria-hidden="true"></i>
                                    <h2 class="modal-title fs-5 fw-semibold mb-0" id="categoryModalTitle">افزودن دسته‌بندی</h2>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                            </div>
                            <div class="modal-body p-3 p-md-4">
                                <p class="small text-body-secondary mb-3">
                                    برای مرتب‌سازی درآمدها و هزینه‌ها، یک نام و نوع مشخص کنید.
                                </p>
                                <div id="categoryFormMessage" class="alert d-none" role="alert" aria-live="polite"></div>

                                <div class="mb-3">
                                    <label for="categoryName" class="form-label fw-medium">
                                        نام دسته‌بندی <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="categoryName" name="name"
                                           maxlength="100" required autocomplete="off"
                                           placeholder="مثلاً خوراکی، حمل‌ونقل یا حقوق"
                                           aria-describedby="categoryNameError categoryNameHelp">
                                    <div id="categoryNameError" class="invalid-feedback"></div>
                                    <div id="categoryNameHelp" class="form-text">نام باید بین ۱ تا ۱۰۰ نویسه باشد.</div>
                                </div>

                                <div class="mb-0">
                                    <label for="categoryType" class="form-label fw-medium">
                                        نوع دسته‌بندی <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <select class="form-select" id="categoryType" name="type" required
                                            aria-describedby="categoryTypeError">
                                        <option value="">نوع را انتخاب کنید</option>
                                        <option value="income">درآمد</option>
                                        <option value="expense">هزینه</option>
                                    </select>
                                    <div id="categoryTypeError" class="invalid-feedback"></div>
                                </div>
                                <div class="alert alert-info bg-info-subtle border-info-subtle small mt-3 mb-0" role="note">
                                    <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>
                                    رنگ و آیکون دسته‌بندی‌ها به‌صورت پیش‌فرض تعیین می‌شوند؛
                                    نیازی به انتخاب آن‌ها نیست.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                                <button type="submit" id="saveCategoryButton" class="btn btn-primary">
                                    ذخیره
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- JS fills category name and performs its existing soft-delete flow. -->
            <div class="modal fade tz-modal" id="deleteCategoryModal" tabindex="-1"
                 aria-labelledby="deleteCategoryModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5 fw-semibold" id="deleteCategoryModalTitle">
                                <i class="fa-regular fa-trash-can text-danger me-2" aria-hidden="true"></i>
                                حذف دسته‌بندی
                            </h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">
                                آیا از حذف دسته‌بندی «<span id="deleteCategoryName" class="fw-semibold"></span>» اطمینان دارید؟
                            </p>
                            <div class="alert alert-warning small mb-0" role="note">
                                <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
                                تراکنش‌های فعال مرتبط نیز حذف نرم می‌شوند و اثر مالی آن‌ها از موجودی برگردانده می‌شود.
                                بازیابی فقط در مهلت مجاز امکان‌پذیر است.
                            </div>
                            <div id="deleteCategoryMessage" class="alert d-none mt-3 mb-0"
                                 role="alert" aria-live="polite"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                            <button type="button" id="confirmDeleteCategoryButton" class="btn btn-danger">حذف دسته‌بندی</button>
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
        base_url('assets/js/categories-form.js'),
        base_url('assets/js/categories-list.js'),
        base_url('assets/js/categories.js')
    )
));
?>
