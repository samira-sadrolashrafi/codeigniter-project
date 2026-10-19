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

    private function get_pagination_params()
    {
        $requested_page = $this->input->get('page');
        $requested_per_page = $this->input->get('per_page');

        if (
            $requested_page === null &&
            $requested_per_page === null
        ) {
            return array(
                'page' => null,
                'per_page' => 10
            );
        }

        $page = filter_var(
            $requested_page,
            FILTER_VALIDATE_INT
        );

        if ($page === false || $page < 1) {
            $page = 1;
        }

        $per_page = filter_var(
            $requested_per_page,
            FILTER_VALIDATE_INT
        );

        if ($per_page === false || $per_page < 1) {
            $per_page = 10;
        }

        return array(
            'page' => $page,
            'per_page' => min($per_page, 100)
        );
    }

    private function get_filter_params()
    {
        return array(
            'search' => $this->input->get('search') ?? '',
            'type' => $this->input->get('type') ?? 'all',
            'category_id' => $this->input->get('category_id') ?? '',
            'date_from' => $this->input->get('date_from') ?? '',
            'date_to' => $this->input->get('date_to') ?? '',
            'sort' => $this->input->get('sort') ?? 'newest'
        );
    }

    public function index()
    {
        $pagination = $this->get_pagination_params();
        $filters = $this->get_filter_params();

        $result = $this->transaction_service->get_all(
            $this->current_user_id(),
            $pagination['page'],
            $pagination['per_page'],
            $filters
        );

        if ($result === false) {
            return $this->respond_service_error(
                $this->transaction_service,
                'دریافت تراکنش‌ها انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        return $this->respond_json(
            true,
            'لیست تراکنش‌ها با موفقیت دریافت شد.',
            200,
            $result
        );
    }

    public function deleted()
    {
        $pagination = $this->get_pagination_params();

        $result = $this->transaction_service->get_deleted(
            $this->current_user_id(),
            $pagination['page'],
            $pagination['per_page']
        );

        if ($result === false) {
            return $this->respond_service_error(
                $this->transaction_service,
                'دریافت تراکنش‌های حذف‌شده انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        foreach ($result['transactions'] as $transaction) {
            $transaction->restore_available =
                !empty($transaction->restore_until)
                && strtotime($transaction->restore_until) > time();
        }

        return $this->respond_json(
            true,
            'فهرست تراکنش‌های حذف‌شده دریافت شد.',
            200,
            $result
        );
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
            return $this->respond_service_error(
                $this->transaction_service,
                'ایجاد تراکنش انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        return $this->respond_json(
            true,
            'تراکنش با موفقیت ایجاد شد.',
            200,
            array(
                'transaction_id' => $transaction_id
            )
        );
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
            return $this->respond_service_error(
                $this->transaction_service,
                'ویرایش تراکنش انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        return $this->respond_json(
            true,
            'تراکنش با موفقیت ویرایش شد.'
        );
    }

    public function delete($transaction_id)
    {
        if (!$this->transaction_service->delete(
            $this->current_user_id(),
            $transaction_id
        )) {
            return $this->respond_service_error(
                $this->transaction_service,
                'حذف تراکنش انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        return $this->respond_json(
            true,
            'تراکنش با موفقیت حذف شد.'
        );
    }

    public function restore($transaction_id)
    {
        if (!$this->transaction_service->restore(
            $this->current_user_id(),
            $transaction_id
        )) {
            return $this->respond_service_error(
                $this->transaction_service,
                'بازگردانی تراکنش انجام نشد؛ لطفاً دوباره تلاش کنید.'
            );
        }

        return $this->respond_json(
            true,
            'تراکنش با موفقیت بازگردانی شد.'
        );
    }
}
