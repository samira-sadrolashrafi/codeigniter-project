<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/BaseApiController.php';

class Recovery_api extends BaseApiController
{
    private $questions;

    public function __construct()
    {
        // Recovery challenges must remain available to signed-out users.
        parent::__construct(false);
        $this->load->helper('validation');
        $this->load->model(array('User_model', 'Recovery_model'));
        $this->load->config('recovery_questions');
        $this->questions = $this->config->item('recovery_questions');
        $this->output->set_header('Cache-Control: no-store, max-age=0');
    }

    // Step 1: obtain the user's chosen question. Do not disclose account existence.
    public function question()
    {
        $username = $this->input->post('username');
        if (!is_string($username) || !preg_match('/\A[a-zA-Z0-9_]{3,100}\z/', trim($username))) {
            return $this->respond_json(false, 'نام کاربری معتبر وارد کنید.', 422);
        }
        $username = trim($username);
        $user = $this->User_model->find_by_username($username);

        $recovery = $user && $user->deleted_at === null ? $this->Recovery_model->find_for_user($user->id) : null;
        $question_id = $recovery && isset($this->questions[(int) $recovery->question_id]) ? (int) $recovery->question_id : 1;

        $this->session->unset_userdata(array(
            'recovery_grant_user_id', 'recovery_grant_expires', 'recovery_grant_nonce'
        ));

        $this->session->set_userdata(array(
            'recovery_challenge_username' => $username,
            'recovery_challenge_expires' => time() + 600
        ));

        return $this->respond_json(true, 'لطفاً به سؤال امنیتی پاسخ دهید.', 200, array(
            'question' => $this->questions[$question_id]
        ));
    }

    public function verify()
    {
        $answer = $this->input->post('answer');
        $username = $this->session->userdata('recovery_challenge_username');
        $challenge_expires = (int) $this->session->userdata('recovery_challenge_expires');

        if (!is_string($username) || $challenge_expires < time()) {
            return $this->respond_json(false, 'زمان درخواست تمام شد؛ دوباره شروع کنید.', 403);
        }

        if (!is_string($answer) || trim($answer) === '' || strlen($answer) > 256) {
            return $this->respond_json(false, 'پاسخ امنیتی معتبر وارد کنید.', 422);
        }

        $user = $this->User_model->find_by_username($username);
        $recovery = $user && $user->deleted_at === null ? $this->Recovery_model->find_for_user($user->id) : null;
        if (!$recovery || !password_verify(trim($answer), $recovery->answer_hash)) {
            return $this->respond_json(false, 'پاسخ صحیح نیست یا بازیابی برای این حساب تنظیم نشده است.', 401);
        }

        $this->session->sess_regenerate(true);
        $nonce = bin2hex(random_bytes(24));
        $this->session->unset_userdata(array('recovery_challenge_username', 'recovery_challenge_expires'));
        $this->session->set_userdata(array(
            'recovery_grant_user_id' => (int) $user->id,
            'recovery_grant_expires' => time() + 300,
            'recovery_grant_nonce' => hash('sha256', $nonce)
        ));
        return $this->respond_json(true, 'پاسخ تأیید شد. رمز عبور جدید را تعیین کنید.', 200, array('nonce' => $nonce));
    }

    // Step 3: reset only; do not authenticate using the security answer alone.
    public function reset()
    {
        $grant_user_id = (int) $this->session->userdata('recovery_grant_user_id');
        $grant_expires = (int) $this->session->userdata('recovery_grant_expires');
        $stored_nonce = $this->session->userdata('recovery_grant_nonce');
        $nonce = $this->input->post('nonce');
        
        if (!$grant_user_id || $grant_expires < time() || !is_string($stored_nonce)
            || !is_string($nonce) || !hash_equals($stored_nonce, hash('sha256', $nonce))) {
            return $this->respond_json(false, 'مجوز بازیابی نامعتبر یا منقضی است؛ دوباره شروع کنید.', 403);
        }

        $password = $this->input->post('password');
        $confirmation = $this->input->post('password_confirm');
        if (!is_string($password) || !is_string($confirmation)
            || strlen($password) > 1024 || strlen($confirmation) > 1024) {
            return $this->respond_json(false, 'اطلاعات رمز عبور نامعتبر است.', 422);
        }
        $errors = array('recovery_password_err' => validatePassword($password),
                        'recovery_confirm_err' => $password === $confirmation ? '' : 'تکرار رمز عبور مطابقت ندارد.');
        if ($errors['recovery_password_err'] || $errors['recovery_confirm_err']) {
            return $this->respond_json(false, 'رمز عبور را اصلاح کنید.', 422, array('errors' => $errors));
        }

        if (!$this->Recovery_model->reset_password($grant_user_id, $password)) {
            return $this->respond_json(false, 'ذخیره رمز جدید انجام نشد؛ دوباره تلاش کنید.', 500);
        }

        $this->session->unset_userdata(array(
            'recovery_grant_user_id', 'recovery_grant_expires', 'recovery_grant_nonce'
        ));
        $this->session->sess_regenerate(true);
        clearRememberCookie();
        return $this->respond_json(true, 'رمز عبور تغییر کرد. اکنون با رمز جدید وارد شوید.');
    }

    // Existing users can configure or rotate a recovery answer after logging in.
    // Their current password is required even when an authenticated session exists.
    public function enroll()
    {
        if (!$this->require_authenticated_user()) {
            return;
        }
        $id = (int) $this->current_user_id();
        $user = $this->User_model->find_active_by_id($id);
        $password = $this->input->post('current_password');
        $question_id = $this->input->post('question_id');
        $answer = $this->input->post('answer');

        if (!$user || !is_string($password) || strlen($password) > 1024 || !$this->valid_question($question_id)
            || !$this->valid_answer($answer)) {
            return $this->respond_json(false, 'مقادیر فرم تنظیم سؤال امنیتی نامعتبر است.', 422);
        }
        if (!password_verify($password, $user->password)) {
            return $this->respond_json(false, 'رمز عبور فعلی اشتباه است.', 401);
        }
        if (!$this->Recovery_model->save_for_user($id, (int) $question_id, $answer)) {
            return $this->respond_json(false, 'ذخیره سؤال امنیتی انجام نشد.', 500);
        }
        return $this->respond_json(true, 'سؤال بازیابی حساب با موفقیت ثبت شد.');
    }

    private function valid_question($id)
    {
        return is_string($id) && preg_match('/\A[1-5]\z/', $id)
            && isset($this->questions[(int) $id]);
    }

    private function valid_answer($answer)
    {
        return is_string($answer) && strlen(trim($answer)) >= 6 && strlen($answer) <= 256;
    }

}
