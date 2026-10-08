<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'services/Balance_service.php';

class Auth_service
{
    protected $CI;
    private $balance_service;
    private $cookie_name = 'remember_token';
    private $remember_seconds = 2592000; // 30 days

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('User_model');
        $this->balance_service = new Balance_service();
    }

    public function register($data)
    {
        if (!is_array($data) || !isset($data['username'], $data['email'], $data['password'], $data['password_confirm']) ||!is_string($data['username']) || !is_string($data['email'])) {
            return false;
        }

        $username = trim((string) $data['username']);
        $email = strtolower(trim((string) $data['email']));
        $password = $data['password'];

        if (!preg_match('/^[a-zA-Z0-9_]{3,100}$/', $username) ||strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||!is_string($password) || !is_string($data['password_confirm']) || !hash_equals($password, $data['password_confirm']) || strlen($password) < 8 || $this->CI->User_model->find_by_username($username) ||$this->CI->User_model->find_by_email($email)) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();
        $user_id = $this->CI->User_model->create(array(
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ));

        if (!$user_id || !$this->balance_service->create_initial_balance($user_id) ||$db->trans_status() === false) {$db->trans_rollback();
            return false;
        }

        if (!$db->trans_commit()) {
            return false;
        }
        return $user_id;
    }

    public function login($username, $password, $remember = false)
    {
        if (!is_string($username) || !is_string($password)) {
            return false;
        }

        $user = $this->CI->User_model->find_by_username(trim($username));
        if (!$user || $user->deleted_at !== null ||!password_verify($password, $user->password)) {
            return false;
        }

        if ($remember) {
            if (!$this->create_remember_token($user->id)) {
                return false;
            }
        } else {
            if (!$this->CI->User_model->clear_remember_token($user->id)) {
                return false;
            }
            $this->forget_remember_cookie();
        }

        $this->CI->session->sess_regenerate(true);

        $this->CI->session->set_userdata(array(
            'user_id' => (int) $user->id,
            'username' => $user->username,
            'logged_in' => true
        ));
        return true;
    }

    public function login_from_remember_cookie()
    {
        if ($this->CI->session->userdata('logged_in')) {
            return true;
        }
        $token = $_COOKIE[$this->cookie_name] ?? null;
        if (!is_string($token) || !preg_match('/^[0-9a-f]{64}$/D', $token)) {
            if ($token !== null) {
                $this->forget_remember_cookie();
            }
            return false;
        }

        $user = $this->CI->User_model->find_by_remember_token(hash('sha256', $token));
        if (!$user) {
            $this->forget_remember_cookie();
            return false;
        }
        if (!$user->remember_token_expires_at || strtotime($user->remember_token_expires_at) <= time()) {
            $this->CI->User_model->clear_remember_token($user->id);
            $this->forget_remember_cookie();
            return false;
        }

        if (!$this->create_remember_token($user->id)) {
            return false;
        }

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
        $user_id = $this->CI->session->userdata('user_id');
        $cleared = true;
        if ($user_id) {
            $cleared = $this->CI->User_model->clear_remember_token($user_id);
        } else {
            $token = $_COOKIE[$this->cookie_name] ?? null;
            if (is_string($token) && preg_match('/^[0-9a-f]{64}$/D', $token)) {
                $user = $this->CI->User_model->find_by_remember_token(hash('sha256', $token));
                if ($user) {
                    $cleared = $this->CI->User_model->clear_remember_token($user->id);
                }
            }
        }
        
        $this->forget_remember_cookie();
        $this->CI->session->sess_destroy();
        return $cleared;
    }

    private function create_remember_token($user_id)
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + $this->remember_seconds;
        if (!$this->CI->User_model->update_remember_token(
            $user_id,
            hash('sha256', $token),
            date('Y-m-d H:i:s', $expires)
        )) {
            return false;
        }
        $ok = setcookie($this->cookie_name, $token, array(
            'expires' => $expires,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        if (!$ok) {
            $this->CI->User_model->clear_remember_token($user_id);
        }
        return $ok;
    }

    private function forget_remember_cookie()
    {
        setcookie($this->cookie_name, '', array(
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        unset($_COOKIE[$this->cookie_name]);
    }
}