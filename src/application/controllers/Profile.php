<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('session');
    }

    public function index()
    {
        if (!isLoggedIn()) {
            redirect('login');
            return;
        }

        $user_id = (int) $this->session->userdata('user_id');
        $this->load->model('Profile_model');
        $user = $this->Profile_model->find_active_user($user_id);
        $recovery = $this->Profile_model->find_recovery_question($user_id);

        $profile = array();
        if ($user) {
            $profile = array(
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => isset($user->avatar) ? $user->avatar : ''
            );
        }
        if ($recovery) {
            $profile['question_id'] = $recovery->question_id;
        }

        // This is the existing profile-only form token; no global CSRF settings are changed.
        $form_token = $this->session->userdata('profile_form_token');
        if (!is_string($form_token) || !preg_match('/\A[a-f0-9]{64}\z/', $form_token)) {
            $form_token = bin2hex(random_bytes(32));
            $this->session->set_userdata('profile_form_token', $form_token);
        }

        $this->load->view('profile/index', array(
            'profile_form_token' => $form_token,
            'profile' => $profile,
            'username' => $this->session->userdata('username')
        ));
    }
}
