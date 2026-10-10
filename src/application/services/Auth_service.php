
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

        $this->CI->load->model('User_model');
        $this->CI->load->helper('session');
        $this->CI->load->helper('validation');

        $this->balance_service = new Balance_service();
    }


        
    public function register($data)
    {
        $db = $this->CI->db;

        $db->trans_begin();

        $user_id = $this->CI->User_model->create(array(
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),
            'created_at' => date('Y-m-d H:i:s')
        ));

        if (!$user_id || !$this->balance_service->create_initial_balance($user_id) || $db->trans_status() === false) {
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

        if (!$user || $user->deleted_at !== null || !password_verify($password, $user->password)) {
            return false;
        }

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

            if (!setcookie('remember_token',$token,rememberCookieOptions($expires))) {
                $this->CI->User_model->clear_remember_token($user->id);
                return false;
            }

        } else {
            if (!$this->CI->User_model->clear_remember_token($user->id)) {
                return false;
            }

            clearRememberCookie();
        }

        // Create session after successful login
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
