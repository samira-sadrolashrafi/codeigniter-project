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

        $this->load->view('profile/index', array(
            'profile' => $profile,
            'username' => $this->session->userdata('username')
        ));
    }
}
