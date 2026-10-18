<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Transaction_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Transaction_api extends BaseApiController
{
    private $transaction_service;

    public function __construct()
    {
        parent::__construct();
        $this->transaction_service = new Transaction_service();
    }

    public function index()
    {
        $transactions = $this->transaction_service->get_all($this->current_user_id());

        if ($transactions === false) {
            return $this->respond_json(false, 'دریافت تراکنش‌ها انجام نشد.', 422);
        }

        return $this->respond_json(true, 'لیست تراکنش‌ها با موفقیت دریافت شد.', 200, array(
            'transactions' => $transactions
        ));
    }

    public function deleted()
    {
        $transactions = $this->transaction_service->get_deleted($this->current_user_id());

        if ($transactions === false) {
            return $this->respond_json(false, 'دریافت تراکنش‌های حذف‌شده انجام نشد.', 422);
        }

        foreach ($transactions as $transaction) {
            $transaction->restore_available = !empty($transaction->restore_until)
                && strtotime($transaction->restore_until) > time();
        }

        return $this->respond_json(true, 'فهرست تراکنش‌های حذف‌شده دریافت شد.', 200, array(
            'transactions' => $transactions
        ));
    }

    public function store()
    {
        $data = array(
            'title' => $this->input->post('title'),
            'amount' => $this->input->post('amount'),
            'category_id' => $this->input->post('category_id'),
            'transaction_date' => $this->input->post('transaction_date'),
            'description' => $this->input->post('description')
        );

        $transaction_id = $this->transaction_service->create(
            $this->current_user_id(),
            $data
        );

        if ($transaction_id === false) {
            return $this->respond_json(false, 'ایجاد تراکنش انجام نشد.', 422);
        }

        return $this->respond_json(true, 'تراکنش با موفقیت ایجاد شد.', 200, array(
            'transaction_id' => $transaction_id
        ));
    }

    public function update($transaction_id)
    {
        $data = array(
            'title' => $this->input->post('title'),
            'amount' => $this->input->post('amount'),
            'category_id' => $this->input->post('category_id'),
            'transaction_date' => $this->input->post('transaction_date'),
            'description' => $this->input->post('description')
        );

        if (!$this->transaction_service->update(
            $this->current_user_id(),
            $transaction_id,
            $data
        )) {
            return $this->respond_json(false, 'ویرایش تراکنش انجام نشد.', 422);
        }

        return $this->respond_json(true, 'تراکنش با موفقیت ویرایش شد.');
    }

    public function delete($transaction_id)
    {
        if (!$this->transaction_service->delete(
            $this->current_user_id(),
            $transaction_id
        )) {
            return $this->respond_json(false, 'حذف تراکنش انجام نشد.', 422);
        }

        return $this->respond_json(true, 'تراکنش با موفقیت حذف شد.');
    }

    public function restore($transaction_id)
    {
        if (!$this->transaction_service->restore(
            $this->current_user_id(),
            $transaction_id
        )) {
            return $this->respond_json(false, 'بازگردانی تراکنش انجام نشد.', 422);
        }

        return $this->respond_json(true, 'تراکنش با موفقیت بازگردانی شد.');
    }
}
