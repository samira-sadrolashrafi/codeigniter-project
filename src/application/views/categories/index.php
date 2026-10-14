<?php
$this->load->view('layouts/header', array(
    'page_title' => 'مدیریت دسته‌بندی‌ها',
    'page_styles' => array(base_url('assets/css/dashboard.css'))
));
$categories_api_url = site_url('api/categories');
?>

<div class="container-fluid dashboard-shell px-3 px-lg-4">
    <div class="row g-0">
        <?php $this->load->view('layouts/app_sidebar', array('active_page' => 'categories')); ?>
        <main class="col-lg-9 col-xl-10 dashboard-main" data-categories-api-url="<?php echo html_escape($categories_api_url); ?>">
    <?php $this->load->view('layouts/app_header', array('app_page_title' => 'مدیریت دسته‌بندی‌ها', 'app_page_description' => 'دسته‌بندی‌های درآمد و هزینه خود را مدیریت کنید.', 'app_username' => isset($username) ? $username : '')); ?>
    <div class="d-flex justify-content-end my-4">
        <button type="button" id="addCategoryButton" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            افزودن دسته‌بندی
        </button>
    </div>

    <div id="categoriesMessage" class="alert d-none" role="alert" aria-live="polite"></div>

    <div class="btn-group mb-3" role="group" aria-label="نمایش دسته‌بندی‌ها">
        <button type="button" id="showActiveCategoriesButton" class="btn btn-primary" aria-pressed="true">دسته‌بندی‌های فعال</button>
        <button type="button" id="showDeletedCategoriesButton" class="btn btn-outline-primary" aria-pressed="false">دسته‌بندی‌های حذف‌شده</button>
    </div>

    <section id="activeCategoriesPanel" class="card border-0 shadow-sm" aria-labelledby="categoriesTableHeading">
        <div class="card-body p-0">
            <h2 id="categoriesTableHeading" class="visually-hidden">فهرست دسته‌بندی‌ها</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">نام دسته‌بندی</th>
                            <th scope="col">نوع</th>
                            <th scope="col">تاریخ ایجاد</th>
                            <th scope="col" class="text-nowrap">عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody" aria-live="polite">
                        <tr><td colspan="4" class="text-center text-secondary py-5">در حال دریافت دسته‌بندی‌ها...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="deletedCategoriesPanel" class="card border-0 shadow-sm" aria-labelledby="deletedCategoriesHeading" hidden>
        <div class="card-body p-0">
            <h2 id="deletedCategoriesHeading" class="visually-hidden">دسته‌بندی‌های حذف‌شده</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>نام دسته‌بندی</th><th>نوع</th><th>حذف‌شده در</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody id="deletedCategoriesTableBody" aria-live="polite"><tr><td colspan="5" class="text-center text-secondary py-5">برای مشاهده، این بخش را انتخاب کنید.</td></tr></tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="categoryForm" novalidate>
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="categoryModalTitle">افزودن دسته‌بندی</h2>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <div id="categoryFormMessage" class="alert d-none" role="alert" aria-live="polite"></div>
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">نام دسته‌بندی</label>
                            <input type="text" class="form-control" id="categoryName" name="name" maxlength="100" required autocomplete="off" aria-describedby="categoryNameError">
                            <div id="categoryNameError" class="invalid-feedback"></div>
                            <div class="form-text">نام باید بین ۱ تا ۱۰۰ نویسه باشد.</div>
                        </div>
                        <div>
                            <label for="categoryType" class="form-label">نوع دسته‌بندی</label>
                            <select class="form-select" id="categoryType" name="type" required aria-describedby="categoryTypeError">
                                <option value="">نوع را انتخاب کنید</option>
                                <option value="income">درآمد</option>
                                <option value="expense">هزینه</option>
                            </select>
                            <div id="categoryTypeError" class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" id="saveCategoryButton" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="deleteCategoryModalTitle">حذف دسته‌بندی</h2>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">آیا از حذف دسته‌بندی «<span id="deleteCategoryName" class="fw-semibold"></span>» اطمینان دارید؟</p>
                    <p class="small text-secondary mt-2 mb-0">تراکنش‌های فعال مرتبط نیز حذف نرم می‌شوند و اثر آن‌ها از موجودی برگردانده می‌شود.</p>
                    <div id="deleteCategoryMessage" class="alert d-none mt-3 mb-0" role="alert" aria-live="polite"></div>
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
    'page_scripts' => array(base_url('assets/js/categories.js'))
));
?>
