<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require_once APPPATH . 'services/Balance_service.php';


class Dashboard_api extends CI_Controller
{
    private $balance_service;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('session');

        $this->balance_service = new Balance_service();
    }

    public function balance()
    {
        if (!isLoggedIn()) {
            return $this->respond(
                false,
                'دسترسی غیرمجاز',
                401
            );
        }


        $user_id = $this->session->userdata('user_id');

        $balance = $this->balance_service->get($user_id);

        if (!$balance) {
            return $this->respond(
                false,
                'موجودی یافت نشد.',
                404
            );
        }


        return $this->respond(
            true,
            'موجودی دریافت شد.',
            200,
            array(
                'balance' => $balance->balance
            )
        );
    }


    public function update_balance()
    {
        if (!isLoggedIn()) {
            return $this->respond(
                false,
                'دسترسی غیرمجاز',
                401
            );
        }

        $user_id = $this->session->userdata('user_id');

        $balance = $this->input->post('balance');

        if (!$this->balance_service->update_manual_balance($user_id,$balance)) 
        {
            return $this->respond(
                false,
                'تغییر موجودی انجام نشد.',
                422
            );
        }

        return $this->respond(
            true,
            'موجودی با موفقیت تغییر کرد.'
        );
    }

    private function respond($success, $message, $status = 200, $data = array())
    {
        $response = array_merge(array('success'=>$success,'message'=>$message),$data);

        $this->output
            ->set_status_header($status)
            ->set_content_type(
                'application/json',
                'utf-8'
            )
            ->set_output(
                json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE
                )
            );
    }
}