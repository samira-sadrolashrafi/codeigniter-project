<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Auth_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Auth_api extends BaseApiController
{
    private $auth_service;

    public function __construct()
    {
        // Registration and login must be reachable before authentication.
        parent::__construct(false);

        $this->load->helper('validation');
        $this->load->model('User_model');
        $this->load->model('Login_throttle_model');
        $this->auth_service = new Auth_service();
    }

    
    public function register()
    {
        if (isLoggedIn()) {
            return $this->respond_json(false, 'شما قبلاً وارد حساب کاربری شده‌اید.', 409);
        }

        $first_name = $this->input->post('first_name');
        $last_name = $this->input->post('last_name');
        $username = $this->input->post('username');
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $password_confirm = $this->input->post('password_confirm');
        $question_id = $this->input->post('question_id');
        $security_answer = $this->input->post('security_answer');

        if (!is_string($first_name) || !is_string($last_name) || !is_string($username) || !is_string($email) || !is_string($password) || !is_string($password_confirm) || !is_string($question_id) || !is_string($security_answer)) {

            return $this->respond_json(false, 'اطلاعات فرم نامعتبر است.', 422);
        }

        $data = array(
            'first_name' => trim($first_name),
            'last_name' => trim($last_name),
            'username' => trim($username),
            'email' => trim($email),
            'password' => $password,
            'password_confirm' => $password_confirm,
            'question_id' => $question_id,
            'security_answer' => $security_answer
        );

        $errors = array(
            'first_name_err' => validatePersonName($data['first_name'], 'نام'),
            'last_name_err' => validatePersonName($data['last_name'], 'نام خانوادگی'),
            'username_err' => validateUsername($data['username']),
            'email_err' => validateEmail($data['email']),
            'password_err' => validatePassword($data['password']),
            'confirm_password_err' => '',
            'question_id_err' => '',
            'security_answer_err' => ''
        );

        if ($data['password'] !== $data['password_confirm']) {
            $errors['confirm_password_err'] = "تکرار رمز عبور با رمز وارد شده مطابقت ندارد. ";
        }

        $this->load->config('recovery_questions');

        $questions = $this->config->item('recovery_questions');

        if (!preg_match('/\A[1-5]\z/', $data['question_id'])
            || !isset($questions[(int) $data['question_id']])) {
            $errors['question_id_err'] = 'لطفاً یکی از پنج سؤال امنیتی را انتخاب کنید.';
        }
        if (strlen(trim($data['security_answer'])) < 6 || strlen($data['security_answer']) > 256) {
            $errors['security_answer_err'] = 'پاسخ امنیتی باید بین ۶ و ۲۵۶ بایت باشد.';
        }

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

        if (!empty($errors['first_name_err']) || !empty($errors['last_name_err']) || !empty($errors['username_err']) || !empty($errors['email_err']) || !empty($errors['password_err']) || !empty($errors['confirm_password_err']) || !empty($errors['question_id_err']) || !empty($errors['security_answer_err'])) {

            return $this->respond_json(false, 'اطلاعات فرم ثبت‌نام را اصلاح کنید.', 422, array('errors' => $errors));
        }

        $user_id = $this->auth_service->register($data);

        if (!$user_id) {
            return $this->respond_json(false,'خطا در ثبت نام', 500);
        }

        return $this->respond_json(true,'ثبت‌نام با موفقیت انجام شد.', 201);
    }


    public function login()
    {
        if (isLoggedIn()) {
            return $this->respond_json(false, 'شما قبلاً وارد حساب کاربری شده‌اید.', 409);
        }

        $username = $this->input->post('username');
        $password = $this->input->post('password');

        if (!is_string($username) || !is_string($password)) {
            return $this->respond_json(false, 'اطلاعات ورود نامعتبر است.', 422);
        }

        $username = trim($username);

        if (strlen($username) > 100 || strlen($password) > 1024) {
            return $this->respond_json(false, 'اطلاعات ورود نامعتبر است.', 422);
        }

        $errors = array(
            'username_err' => '',
            'password_err' => validateLoginPassword($password)
        );

        if (empty($username)) {
            $errors['username_err'] = "لطفا نام کاربری خود را وارد کنید.";
        }

        if (!empty($errors['username_err']) || !empty($errors['password_err'])) {
            return $this->respond_json(false, 'اطلاعات فرم ورود را اصلاح کنید.', 422, array('errors' => $errors));
        }

        if (!$this->Login_throttle_model->allow($username)) {
            return $this->respond_json(false, 'تعداد درخواست‌های ورود بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        $remember = $this->input->post('remember') === '1';

        $success = $this->auth_service->login($username, $password, $remember);

        if (!$success) {
            $errors['password_err'] = "نام کاربری یا رمز عبور اشتباه است.";

            return $this->respond_json(
                false,
                'نام کاربری یا رمز عبور اشتباه است.',
                401,
                array('errors' => $errors)
            );
        }

        $this->Login_throttle_model->clear_account($username);

        return $this->respond_json(true,'با موفقیت وارد شدید.');
    }


    public function logout()
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        if (!$this->auth_service->logout()) {
            return $this->respond_json(false, 'خروج انجام شد، اما حذف توکن از دیتابیس تأیید نشد.', 500);
        }

        return $this->respond_json(true, 'با موفقیت خارج شدید.');
    }

}
