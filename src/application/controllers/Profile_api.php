<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'services/Profile_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Profile_api extends BaseApiController
{
    private $profile_service;

    public function __construct()
    {
        parent::__construct();
        $this->profile_service = new Profile_service();
    }

    public function update()
    {
        $token = $this->input->post('profile_form_token');
        $expected = $this->session->userdata('profile_form_token');
        if (!is_string($token) || !is_string($expected) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $token) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $expected) ||
            !hash_equals($expected, $token)) {
            return $this->respond_json(
                false,
                'اعتبار فرم پایان یافته است. صفحه را تازه‌سازی کنید.',
                403
            );
        }

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
}
