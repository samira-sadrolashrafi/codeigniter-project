<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('session');
    }


    public function index()
    {
        if (!isLoggedIn()) {
            redirect('login');
            return;
        }


        $data = array(
            'username' => $this->session->userdata('username')
        );


        $this->load->view('dashboard/index', $data);
    }
}