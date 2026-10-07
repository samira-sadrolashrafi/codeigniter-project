<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->input->is_cli_request()) {
            show_404();
        }

        $this->load->library('migration');
    }

    public function index()
    {
        if ($this->migration->latest() === FALSE) {
            echo $this->migration->error_string() . PHP_EOL;
            return;
        }

        echo 'Migration completed successfully.' . PHP_EOL;
    }
}