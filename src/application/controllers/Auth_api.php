<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Auth_service.php';

class Auth_api extends CI_Controller
{
    private $auth_service;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(array('session', 'validation'));
        $this->load->model('User_model');
        $this->auth_service = new Auth_service();
    }


    
    public function register()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(false, 'روش درخواست نامعتبر است.', 405);
        }

        if (isLoggedIn()) {
            return $this->respond(false, 'شما قبلاً وارد حساب کاربری شده‌اید.', 409);
        }

        $username = $this->input->post('username');
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $password_confirm = $this->input->post('password_confirm');

        if (!is_string($username) || !is_string($email) || !is_string($password) || !is_string($password_confirm)) {

            return $this->respond(false, 'اطلاعات فرم نامعتبر است.', 422);
        }

        $data = array(
            'username' => trim($username),
            'email' => trim($email),
            'password' => $password,
            'password_confirm' => $password_confirm
        );

        $errors = array(
            'username_err' => validateUsername($data['username']),
            'email_err' => validateEmail($data['email']),
            'password_err' => validatePassword($data['password']),
            'confirm_password_err' => ''
        );

        if ($data['password'] !== $data['password_confirm']) {
            $errors['confirm_password_err'] = "تکرار رمز عبور با رمز وارد شده مطابقت ندارد. ";
        }

        // Check duplicate username
        if (empty($errors['username_err'])) {
            if ($this->User_model->find_by_username($data['username'])) {
                $errors['username_err'] = "این نام کاربری قبلا انتخاب شده است.";
            }
        }

        if (empty($errors['email_err'])) {
            if ($this->User_model->find_by_email($data['email'])) {
                $errors['email_err'] = "این ایمیل قبلا ثبت شده است. ";
            }
        }

        if (!empty($errors['username_err']) || !empty($errors['email_err']) || !empty($errors['password_err']) || !empty($errors['confirm_password_err'])) {

            return $this->respond(
                false,
                'اطلاعات فرم ثبت‌نام را اصلاح کنید.',
                422,
                $errors
            );
        }

        $user_id = $this->auth_service->register($data);

        if (!$user_id) {
            return $this->respond(
                false,
                'خطا در ثبت نام',
                500
            );
        }

        return $this->respond(
            true,
            'ثبت‌نام با موفقیت انجام شد.',
            201
        );
    }



    
    public function login()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(false, 'روش درخواست نامعتبر است.', 405);
        }

        $username = $this->input->post('username');
        $password = $this->input->post('password');

        if (!is_string($username) || !is_string($password)) {
            return $this->respond(false, 'اطلاعات ورود نامعتبر است.', 422);
        }

        $username = trim($username);

        $errors = array(
            'username_err' => '',
            'password_err' => validateLoginPassword($password)
        );

        if (empty($username)) {
            $errors['username_err'] = "لطفا نام کاربری خود را وارد کنید.";
        }

        if (!empty($errors['username_err']) || !empty($errors['password_err'])) {
            return $this->respond(
                false,
                'اطلاعات فرم ورود را اصلاح کنید.',
                422,
                $errors
            );
        }

        $remember = $this->input->post('remember') === '1';

        $success = $this->auth_service->login(
            $username,
            $password,
            $remember
        );

        if (!$success) {
            $errors['password_err'] = "نام کاربری یا رمز عبور اشتباه است.";

            return $this->respond(
                false,
                'نام کاربری یا رمز عبور اشتباه است.',
                401,
                $errors
            );
        }

        return $this->respond(
            true,
            'با موفقیت وارد شدید.'
        );
    }


    public function logout()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->respond(false, 'روش درخواست نامعتبر است.', 405);
        }

        if (!$this->auth_service->logout()) {
            return $this->respond(
                false,
                'خروج انجام شد، اما حذف توکن از دیتابیس تأیید نشد.',
                500
            );
        }

        return $this->respond(
            true,
            'با موفقیت خارج شدید.'
        );
    }


    private function respond($success, $message, $status = 200, $errors = array())
    {
        $response = array(
            'success' => $success,
            'message' => $message,
            'errors' => $errors
        );

        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE));
    }
}
