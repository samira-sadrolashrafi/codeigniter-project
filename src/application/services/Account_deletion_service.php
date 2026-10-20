<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_deletion_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('User_model');
    }

    public function delete_account($user_id, $password)
    {
        if ((int) $user_id < 1 || !is_string($password) || $password === '') {
            return $this->failure('درخواست حذف حساب معتبر نیست.', 422);
        }

        $db = $this->CI->db;
        $db->trans_begin();

        $user = $this->CI->User_model->find_active_by_id((int) $user_id);

        if (!$user) {
            $db->trans_rollback();
            return $this->failure('حساب کاربری فعال پیدا نشد. دوباره وارد شوید.', 401);
        }

        if (!password_verify($password, $user->password)) {
            $db->trans_rollback();
            return $this->failure('رمز عبور فعلی صحیح نیست.', 422);
        }

        $deleted_at = date('Y-m-d H:i:s');
        $restore_until = date('Y-m-d H:i:s', strtotime('+30 days'));

        $updated = $this->CI->User_model->soft_delete_account(
            (int) $user_id,
            $deleted_at,
            $restore_until
        );

        if (!$updated || $db->trans_status() === false) {
            $db->trans_rollback();
            return $this->failure('حذف حساب انجام نشد. دوباره تلاش کنید.', 500);
        }

        if (!$db->trans_commit()) {
            $db->trans_rollback();
            return $this->failure('ثبت حذف حساب انجام نشد. دوباره تلاش کنید.', 500);
        }

        return array(
            'success' => true,
            'status' => 200,
            'message' => 'حساب کاربری حذف شد. تا ۳۰ روز امکان بازیابی آن وجود دارد.'
        );
    }

    private function failure($message, $status)
    {
        return array(
            'success' => false,
            'status' => $status,
            'message' => $message
        );
    }
}