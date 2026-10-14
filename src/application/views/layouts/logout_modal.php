<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmTitle" aria-hidden="true"
     data-logout-url="<?php echo html_escape(site_url('auth/logout')); ?>"
     data-login-url="<?php echo html_escape(site_url('login')); ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutConfirmTitle">تأیید خروج</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">آیا از خروج از حساب کاربری خود اطمینان دارید؟</p>
                <div id="logoutMessage" class="alert alert-danger d-none mt-3 mb-0" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" id="confirmLogoutButton" class="btn btn-danger">بله، خارج شو</button>
            </div>
        </div>
    </div>
</div>
