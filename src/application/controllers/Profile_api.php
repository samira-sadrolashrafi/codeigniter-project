<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Profile_service.php';
require_once APPPATH . 'services/Account_deletion_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Profile_api extends BaseApiController
{
    private $profile_service;
    private $account_deletion_service;

    public function __construct()
    {
        parent::__construct();
        $this->profile_service = new Profile_service();
        $this->account_deletion_service = new Account_deletion_service();
    }

    public function update()
    {
        $result = $this->profile_service->update(
            (int) $this->current_user_id(),
            $this->input->post(NULL, false),
            $_FILES
        );

        $status = $result['status'] ?? 200;
        $success = !empty($result['success']);
        $message = $result['message'] ?? '';

        unset($result['status']);
        unset($result['success'], $result['message']);

        return $this->respond_json($success, $message, $status, $result);
    }

    public function delete_account()
    {
        if ((string) $this->input->post('confirm_deletion') !== '1') {
            return $this->respond_json(
                false,
                'برای حذف حساب، تأیید نهایی را علامت بزنید.',
                422
            );
        }

        $password = $this->input->post('current_password', false);

        $result = $this->account_deletion_service->delete_account(
            (int) $this->current_user_id(),
            $password
        );

        $status = $result['status'] ?? 500;
        $success = !empty($result['success']);
        $message = $result['message'] ?? '';

        if (!$success) {
            return $this->respond_json(false, $message, $status);
        }

        clearRememberCookie();
        $this->session->sess_destroy();

        return $this->respond_json(true, $message, $status);
    }
}
