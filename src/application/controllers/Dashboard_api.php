<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Balance_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Dashboard_api extends BaseApiController
{
    private $balance_service;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Dashboard_report_model');

        $this->balance_service = new Balance_service();
    }

    public function balance()
    {
        $balance = $this->balance_service->get($this->user_id);

        if (!$balance) {
            return $this->respond_json(false, 'موجودی یافت نشد.', 404);
        }

        return $this->respond_json(
            true,
            'موجودی دریافت شد.',
            200,
            array('balance' => $balance->balance)
        );
    }

    public function update_balance()
    {
        $balance = $this->input->post('balance');

        if (!$this->balance_service->update_manual_balance($this->user_id, $balance)) {
            return $this->respond_json(false, 'تغییر موجودی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'موجودی با موفقیت تغییر کرد.');
    }

    public function reset_balance()
    {
        if (!$this->balance_service->reset_manual_balance($this->user_id)) {
            return $this->respond_json(false, 'صفر کردن موجودی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'موجودی با موفقیت صفر شد.');
    }

    /**
     * Return the income, expense, and historical balance
     * series required by the dashboard chart.
     */
    
    public function cashflow()
    {
        $from = $this->input->get('from', true);
        $to = $this->input->get('to', true);

        // A report is either unbounded or has a complete inclusive ISO range.
        if ($from !== null || $to !== null) {
            if (
                !$this->is_valid_report_date($from) ||
                !$this->is_valid_report_date($to) ||
                $from > $to
            ) {
                return $this->respond_json(false, 'بازه گزارش معتبر نیست.', 422);
            }
        }

        $report = $this->Dashboard_report_model
            ->get_cashflow_report($this->user_id, $from, $to);

        if ($report === false) {
            return $this->respond_json(false, 'دریافت گزارش نمودار ناموفق بود.', 500);
        }

        return $this->respond_json(
            true,
            'گزارش نمودار دریافت شد.',
            200,
            $report
        );
    }

    private function is_valid_report_date($value)
    {
        if (
            !is_string($value) ||
            !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)
        ) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

}
