<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'services/Profile_service.php';

/** کنترلر فقط مسئول احراز هویت، توکن فرم و پاسخ HTTP است. */
class Profile_api extends CI_Controller
{
    private $profile_service;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('session');
        $this->profile_service = new Profile_service();
    }

    public function update()
    {
        if (!isLoggedIn()) {
            return $this->respond(array(
                'success' => false, 'message' => 'ابتدا وارد حساب شوید.', 'status' => 401
            ));
        }

        $token = $this->input->post('profile_form_token');
        $expected = $this->session->userdata('profile_form_token');
        if (!is_string($token) || !is_string($expected) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $token) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $expected) ||
            !hash_equals($expected, $token)) {
            return $this->respond(array(
                'success' => false,
                'message' => 'اعتبار فرم پایان یافته است. صفحه را تازه‌سازی کنید.',
                'status' => 403
            ));
        }

        $result = $this->profile_service->update(
            (int) $this->session->userdata('user_id'),
            $this->input->post(NULL, false),
            $_FILES,
            $this->input->ip_address()
        );
        return $this->respond($result);
    }

    private function respond($result)
    {
        $status = isset($result['status']) ? $result['status'] : 200;
        unset($result['status']);
        $this->output->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}
