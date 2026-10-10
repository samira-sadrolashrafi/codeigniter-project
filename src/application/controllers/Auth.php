
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('session');
    }

    public function login()
    {
        if (isLoggedIn()) {
            redirect('dashboard');
            return;
        }

        $this->load->view('auth/login');
    }

    public function register()
    {
        if (isLoggedIn()) {
            redirect('dashboard');
            return;
        }

        $this->load->view('auth/register');
    }
}
