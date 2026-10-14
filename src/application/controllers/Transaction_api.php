<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Transaction_service.php';

class Transaction_api extends CI_Controller
{
    private $transaction_service;


    public function __construct()
    {
        parent::__construct();

        $this->transaction_service = new Transaction_service();

        $this->load->library('session');
    }

    public function index()
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }

        $transactions = $this->transaction_service->get_all($user_id);

        if ($transactions === false) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'دریافت تراکنش‌ها انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }

        $this->output
            ->set_content_type('application/json', 'utf-8');

        echo json_encode(array(
            'success' => true,
            'transactions' => $transactions
        ), JSON_UNESCAPED_UNICODE);
    }

    public function deleted()
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            return $this->respond_deleted(false, 'ابتدا وارد حساب کاربری شوید.', 401);
        }

        $transactions = $this->transaction_service->get_deleted($user_id);
        if ($transactions === false) {
            return $this->respond_deleted(false, 'دریافت تراکنش‌های حذف‌شده انجام نشد.', 422);
        }

        foreach ($transactions as $transaction) {
            $transaction->restore_available = !empty($transaction->restore_until) && strtotime($transaction->restore_until) > time();
        }

        return $this->respond_deleted(true, 'فهرست تراکنش‌های حذف‌شده دریافت شد.', 200, array('transactions' => $transactions));
    }

    public function store()
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }

        $data = array(
            'title' => $this->input->post('title'),
            'amount' => $this->input->post('amount'),
            'category_id' => $this->input->post('category_id'),
            'transaction_date' => $this->input->post('transaction_date'),
            'description' => $this->input->post('description')
        );

        $transaction_id = $this->transaction_service->create($user_id,$data);

        if ($transaction_id === false) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ایجاد تراکنش انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }

        $this->output
            ->set_content_type('application/json', 'utf-8');

        echo json_encode(array(
            'success' => true,
            'message' => 'تراکنش با موفقیت ایجاد شد.',
            'transaction_id' => $transaction_id
        ), JSON_UNESCAPED_UNICODE);
    }

    public function update($transaction_id)
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        $data = array(
            'title' => $this->input->post('title'),
            'amount' => $this->input->post('amount'),
            'category_id' => $this->input->post('category_id'),
            'transaction_date' => $this->input->post('transaction_date'),
            'description' => $this->input->post('description')
        );


        $result = $this->transaction_service->update(
            $user_id,
            $transaction_id,
            $data
        );


        if (!$result) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ویرایش تراکنش انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        echo json_encode(array(
            'success' => true,
            'message' => 'تراکنش با موفقیت ویرایش شد.'
        ), JSON_UNESCAPED_UNICODE);
    }

    public function delete($transaction_id)
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        $result = $this->transaction_service->delete(
            $user_id,
            $transaction_id
        );


        if (!$result) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'حذف تراکنش انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        $this->output
            ->set_content_type('application/json', 'utf-8');

        echo json_encode(array(
            'success' => true,
            'message' => 'تراکنش با موفقیت حذف شد.'
        ), JSON_UNESCAPED_UNICODE);
    }

    public function restore($transaction_id)
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        $result = $this->transaction_service->restore(
            $user_id,
            $transaction_id
        );


        if (!$result) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json', 'utf-8');

            echo json_encode(array(
                'success' => false,
                'message' => 'بازگردانی تراکنش انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);

            return;
        }


        $this->output
            ->set_content_type('application/json', 'utf-8');

        echo json_encode(array(
            'success' => true,
            'message' => 'تراکنش با موفقیت بازگردانی شد.'
        ), JSON_UNESCAPED_UNICODE);
    }

    private function respond_deleted($success, $message, $status, $data = array())
    {
        $this->output->set_status_header($status)->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array_merge(array('success' => $success, 'message' => $message), $data), JSON_UNESCAPED_UNICODE));
    }

}
