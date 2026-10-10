
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function isLoggedIn()
{
    $CI =& get_instance();
    $CI->load->model('User_model');

    $session_cookie = $CI->config->item('sess_cookie_name');

    if (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[$session_cookie])) {
        $CI->load->library('session');

        $user_id = $CI->session->userdata('user_id');

        if ($user_id && $CI->session->userdata('logged_in')) {

            if ($CI->User_model->find_active_by_id($user_id)) {
                return true;
            }

            $CI->session->sess_destroy();
            clearRememberCookie();

            return false;
        }
    }

    $token = $_COOKIE['remember_token'] ?? null;

    if ($token === null) {
        return false;
    }

    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {

        clearRememberCookie();
        return false;
    }

    $user = $CI->User_model->find_by_remember_token(hash('sha256', $token));

    if (!$user || !$user->remember_token_expires_at || strtotime($user->remember_token_expires_at) <= time()) {

        if ($user) {
            $CI->User_model->clear_remember_token($user->id);
        }

        clearRememberCookie();
        return false;
    }

    $CI->load->library('session');

    $CI->session->sess_regenerate(true);

    $CI->session->set_userdata(array(
        'user_id' => (int) $user->id,
        'username' => $user->username,
        'logged_in' => true
    ));

    return true;
}


function rememberCookieOptions($expires)
{
    return array(
        'expires' => $expires,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax'
    );
}


function clearRememberCookie()
{
    setcookie(
        'remember_token',
        '',
        rememberCookieOptions(time() - 3600)
    );

    unset($_COOKIE['remember_token']);
}
