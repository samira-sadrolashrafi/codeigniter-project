<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$form_prefix = isset($form_prefix) ? $form_prefix : 'transaction';
$save_button_id = 'save' . ucfirst($form_prefix) . 'Button';
?>
<div class="modal fade" id="<?php echo html_escape($form_prefix); ?>Modal" tabindex="-1" aria-labelledby="<?php echo html_escape($form_prefix); ?>ModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="<?php echo html_escape($form_prefix); ?>Form" novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="<?php echo html_escape($form_prefix); ?>ModalTitle">افزودن تراکنش</h2>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div id="<?php echo html_escape($form_prefix); ?>FormMessage" class="alert d-none" role="alert" aria-live="polite"></div>
                    <div id="<?php echo html_escape($form_prefix); ?>CategoriesWarning" class="alert alert-warning d-none" role="alert" aria-live="polite"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="<?php echo html_escape($form_prefix); ?>Title" class="form-label">عنوان</label>
                            <input type="text" class="form-control" id="<?php echo html_escape($form_prefix); ?>Title" name="title" maxlength="150" required autocomplete="off" aria-describedby="<?php echo html_escape($form_prefix); ?>TitleError">
                            <div id="<?php echo html_escape($form_prefix); ?>TitleError" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="<?php echo html_escape($form_prefix); ?>Type" class="form-label">نوع تراکنش</label>
                            <select class="form-select" id="<?php echo html_escape($form_prefix); ?>Type" required aria-describedby="<?php echo html_escape($form_prefix); ?>TypeHelp <?php echo html_escape($form_prefix); ?>TypeError">
                                <option value="">نوع را انتخاب کنید</option>
                                <option value="income">درآمد</option>
                                <option value="expense">هزینه</option>
                            </select>
                            <div id="<?php echo html_escape($form_prefix); ?>TypeHelp" class="form-text">نوع تراکنش از نوع دسته‌بندی انتخاب‌شده تعیین می‌شود.</div>
                            <div id="<?php echo html_escape($form_prefix); ?>TypeError" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="<?php echo html_escape($form_prefix); ?>Amount" class="form-label">مبلغ</label>
                            <div class="input-group" dir="ltr">
                                <span class="input-group-text">تومان</span>
                                <input type="text" class="form-control" id="<?php echo html_escape($form_prefix); ?>Amount" name="amount" inputmode="decimal" maxlength="20" autocomplete="off" required aria-describedby="<?php echo html_escape($form_prefix); ?>AmountHelp <?php echo html_escape($form_prefix); ?>AmountError">
                            </div>
                            <div id="<?php echo html_escape($form_prefix); ?>AmountHelp" class="form-text">مبلغ مثبت است؛ برای اعشار از نقطه استفاده کنید (حداکثر دو رقم).</div>
                            <div id="<?php echo html_escape($form_prefix); ?>AmountError" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="<?php echo html_escape($form_prefix); ?>Category" class="form-label">دسته‌بندی</label>
                            <select class="form-select" id="<?php echo html_escape($form_prefix); ?>Category" name="category_id" required aria-describedby="<?php echo html_escape($form_prefix); ?>CategoryError">
                                <option value="">ابتدا نوع تراکنش را انتخاب کنید</option>
                            </select>
                            <div id="<?php echo html_escape($form_prefix); ?>CategoryError" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="<?php echo html_escape($form_prefix); ?>Date" class="form-label">تاریخ</label>
                            <input type="date" class="form-control" id="<?php echo html_escape($form_prefix); ?>Date" name="transaction_date" required aria-describedby="<?php echo html_escape($form_prefix); ?>DateError">
                            <div id="<?php echo html_escape($form_prefix); ?>DateError" class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="<?php echo html_escape($form_prefix); ?>Description" class="form-label">توضیحات <span class="text-secondary">(اختیاری)</span></label>
                            <textarea class="form-control" id="<?php echo html_escape($form_prefix); ?>Description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" id="<?php echo html_escape($save_button_id); ?>" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>
