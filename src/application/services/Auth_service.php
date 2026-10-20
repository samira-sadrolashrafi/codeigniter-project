<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Balance_service.php';

class Auth_service
{
    protected $CI;
    private $balance_service;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->model(array('User_model', 'Recovery_model'));
        $this->CI->load->helper('session');
        $this->CI->load->helper('validation');

        $this->balance_service = new Balance_service();
    }


    public function register($data)
    {
        $db = $this->CI->db;

        $db->trans_begin();

        $user_id = $this->CI->User_model->create(array(
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),
            'created_at' => date('Y-m-d H:i:s')
        ));

        if (!$user_id || !$this->balance_service->create_initial_balance($user_id)
            || !$this->CI->Recovery_model->save_for_user($user_id, $data['question_id'], $data['security_answer'])
            || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit() ? $user_id : false;
    }


    public function login($username, $password, $remember = false)
    {
        if (isLoggedIn()) {
            return true;
        }

        if (!is_string($username) || !is_string($password)) {
            return false;
        }

        $user = $this->CI->User_model->find_by_username(trim($username));

        if (!$user || !password_verify($password, $user->password)) {
            return false;
        }

        if ($user->deleted_at !== null) {
            if ($this->is_recovery_available($user)) {
                return array(
                    'status' => 'recovery_available'
                );
            }

            return array(
                'status' => 'recovery_expired'
            );
        }

        return $this->complete_login($user, $remember);
    }


    public function restore_deleted_account($username, $password, $remember = false)
    {
        if (isLoggedIn()) {
            return false;
        }

        if (!is_string($username) || !is_string($password)) {
            return false;
        }

        $user = $this->CI->User_model->find_by_username(trim($username));

        if (!$user || $user->deleted_at === null
            || !password_verify($password, $user->password)) {
            return false;
        }

        if (!$this->is_recovery_available($user)) {
            return array(
                'status' => 'recovery_expired'
            );
        }

        $db = $this->CI->db;
        $db->trans_begin();

        $restored = $this->CI->User_model->restore_account(
            (int) $user->id,
            date('Y-m-d H:i:s')
        );

        if (!$restored) {
            $db->trans_rollback();
            return false;
        }

        $user = $this->CI->User_model->find_active_by_id((int) $user->id);

        if (!$user || !$this->complete_login($user, $remember)
            || $db->trans_status() === false) {
            $db->trans_rollback();
            $this->CI->load->library('session');
            $this->CI->session->sess_destroy();
            clearRememberCookie();
            return false;
        }

        if (!$db->trans_commit()) {
            $db->trans_rollback();
            $this->CI->load->library('session');
            $this->CI->session->sess_destroy();
            clearRememberCookie();
            return false;
        }

        return true;
    }


    private function is_recovery_available($user)
    {
        if (empty($user->restore_until)) {
            return false;
        }

        $restore_timestamp = strtotime($user->restore_until);

        return $restore_timestamp !== false && $restore_timestamp > time();
    }


    private function complete_login($user, $remember)
    {
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 30 * 86400;

            if (!$this->CI->User_model->update_remember_token(
                $user->id,
                hash('sha256', $token),
                date('Y-m-d H:i:s', $expires)
            )) {
                return false;
            }

            if (!setcookie('remember_token', $token, rememberCookieOptions($expires))) {
                $this->CI->User_model->clear_remember_token($user->id);
                clearRememberCookie();
                return false;
            }

        } else {
            if (!$this->CI->User_model->clear_remember_token($user->id)) {
                return false;
            }

            clearRememberCookie();
        }

        $this->CI->load->library('session');

        $this->CI->session->sess_regenerate(true);

        $this->CI->session->set_userdata(array(
            'user_id' => (int) $user->id,
            'username' => $user->username,
            'logged_in' => true
        ));

        return true;
    }


    public function logout()
    {
        $user_id = null;

        $session_cookie = $this->CI->config->item('sess_cookie_name');

        if (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[$session_cookie])) {

            $this->CI->load->library('session');

            $user_id = $this->CI->session->userdata('user_id');
        }

        if (!$user_id) {
            $token = $_COOKIE['remember_token'] ?? null;

            if (is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token)) {

                $user = $this->CI->User_model->find_by_remember_token(
                    hash('sha256', $token)
                );

                if ($user) {
                    $user_id = $user->id;
                }
            }
        }

        $cleared = true;

        if ($user_id) {
            $cleared = $this->CI->User_model->clear_remember_token($user_id);
        }

        clearRememberCookie();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->CI->session->sess_destroy();
        }

        return $cleared;
    }
}
