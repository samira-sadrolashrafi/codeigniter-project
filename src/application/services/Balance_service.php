<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Balance_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->model('Balance_model');
        $this->CI->load->model('User_model');
        $this->CI->load->model('Balance_history_model');
    }


    public function amount_error($amount, $allow_negative = false)
    {
        if (!is_string($amount) && !is_int($amount)) {
            return 'مبلغ نامعتبر است.';
        }

        $value = trim((string) $amount);

        $pattern = $allow_negative
            ? '/^-?[0-9]+(?:\.[0-9]{1,2})?$/'
            : '/^[0-9]+(?:\.[0-9]{1,2})?$/';

        if (!preg_match($pattern, $value)) {
            return 'فرمت مبلغ نامعتبر است؛ فقط عدد با حداکثر دو رقم اعشار وارد کنید.';
        }

        $unsigned = ($value[0] === '-') ? substr($value, 1) : $value;

        $integer = ltrim(explode('.', $unsigned, 2)[0], '0');

        if (strlen($integer === '' ? '0' : $integer) > 13) {
            return 'مبلغ واردشده بیش از حد مجاز است.';
        }

        if (!$allow_negative && !preg_match('/[1-9]/', $unsigned)) {
            return 'مبلغ تراکنش نمی‌تواند صفر باشد.';
        }

        return null;
    }

    public function normalize_amount($amount, $allow_negative = false)
    {
        if (!is_string($amount) && !is_int($amount)) {
            return false;
        }

        $value = trim((string) $amount);

        $pattern = $allow_negative
            ? '/^-?[0-9]+(?:\.[0-9]{1,2})?$/'
            : '/^[0-9]+(?:\.[0-9]{1,2})?$/';

        if (!preg_match($pattern, $value)) {
            return false;
        }

        $negative = ($value[0] === '-');
        $unsigned = $negative ? substr($value, 1) : $value;

        $parts = explode('.', $unsigned, 2);

        $integer = ltrim($parts[0], '0');
        $integer = $integer === '' ? '0' : $integer;

        if (strlen($integer) > 13) {
            return false;
        }

        $fraction = $parts[1] ?? '';
        $result = $integer . '.' . str_pad($fraction, 2, '0');

        if (
            !$allow_negative &&
            !preg_match('/[1-9]/', $result)
        ) {
            return false;
        }

        if ($negative && preg_match('/[1-9]/', $unsigned)) {
            return '-' . $result;
        }

        return $result;
    }

  
    private function negate_amount($amount)
    {
        $amount = $this->normalize_amount($amount, true);

        if ($amount === false) {
            return false;
        }

        if ($amount === '0.00') {
            return '0.00';
        }

        return ($amount[0] === '-')
            ? substr($amount, 1)
            : '-' . $amount;
    }

    public function create_initial_balance($user_id)
    {
        if ((int) $user_id <= 0) {
            return false;
        }

        return $this->CI->Balance_model->create(array(
            'user_id' => $user_id,
            'balance' => '0.00',
            'created_at' => date('Y-m-d H:i:s')
        ));
    }

    public function increase($user_id, $amount)
    {
        $amount = $this->normalize_amount($amount);

        if ($amount === false ||
            !$this->CI->User_model->find_active_by_id($user_id) ||
            !$this->CI->Balance_model->find_by_user_id($user_id)
        ) {
            return false;
        }

        return $this->CI->Balance_model->change_by(
            $user_id,
            $amount
        );
    }

    public function decrease($user_id, $amount)
    {
        $amount = $this->normalize_amount($amount);

        if (
            $amount === false ||
            !$this->CI->User_model->find_active_by_id($user_id) ||
            !$this->CI->Balance_model->find_by_user_id($user_id)
        ) {
            return false;
        }

        return $this->CI->Balance_model->change_by(
            $user_id,
            '-' . $amount
        );
    }

    public function get($user_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        return $this->CI->Balance_model->find_by_user_id($user_id);
    }

    /**
     * Build the signed balance-history delta for a transaction effect.
     *
     * income  => +amount, expense => -amount; reversed when $reverse is true,
     * e.g. the reversal recorded when a transaction is soft-deleted.
     */
    public function get_history_delta($type, $amount, $reverse = false)
    {
        if ($type !== 'income' && $type !== 'expense') {
            return false;
        }

        $amount = $this->normalize_amount($amount);

        if ($amount === false) {
            return false;
        }

        $is_negative = ($type === 'expense');

        if ($reverse) {
            $is_negative = !$is_negative;
        }

        if ($amount === '0.00') {
            return '0.00';
        }

        return $is_negative ? '-' . $amount : $amount;
    }

    
    public function record_transaction_history_event(
        $user_id,
        $event_type,
        $effect_type,
        $amount,
        $effective_date,
        $transaction_id,
        $reverse = false
    ) {
        $amount_delta = $this->get_history_delta(
            $effect_type,
            $amount,
            $reverse
        );

        if ($amount_delta === false) {
            return false;
        }

        $event_id = $this->CI->Balance_history_model->create_event(array(
            'user_id' => $user_id,
            'transaction_id' => $transaction_id,
            'event_type' => $event_type,
            'amount_delta' => $amount_delta,
            'effective_date' => $effective_date
        ));

        return $event_id !== false;
    }

    public function apply_transaction_effect($user_id, $type, $amount)
    {
        if ($type === 'income') {
            return $this->increase($user_id, $amount);
        }

        if ($type === 'expense') {
            return $this->decrease($user_id, $amount);
        }

        return false;
    }

    public function reverse_transaction_effect($user_id, $type, $amount)
    {
        if ($type === 'income') {
            return $this->decrease($user_id, $amount);
        }

        if ($type === 'expense') {
            return $this->increase($user_id, $amount);
        }

        return false;
    }

    public function update_manual_balance($user_id, $amount)
    {
        $amount = $this->normalize_amount($amount, true);

        if ($amount === false || (int) $user_id <= 0) {
            return false;
        }

        $db = $this->CI->db;

        $db->trans_begin();

        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $balance = $this->CI->Balance_model->find_by_user_id($user_id);

        if (!$balance) {
            $db->trans_rollback();
            return false;
        }

       
        if ($amount !== '0.00') {
            if (
                !$this->CI->Balance_model->change_by(
                    $user_id,
                    $amount
                )
            ) {
                $db->trans_rollback();
                return false;
            }

            $event_id = $this->CI->Balance_history_model->create_event(
                array(
                    'user_id' => $user_id,
                    'event_type' => 'manual_adjustment',
                    'amount_delta' => $amount,
                    'effective_date' => date('Y-m-d'),
                    'description' => 'Manual balance adjustment'
                )
            );

            if ($event_id === false) {
                $db->trans_rollback();
                return false;
            }
        }

        if ($db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    public function reset_manual_balance($user_id)
    {
        if ((int) $user_id <= 0) {
            return false;
        }

        $db = $this->CI->db;

        $db->trans_begin();

        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $balance = $this->CI->Balance_model->find_by_user_id($user_id);

        if (!$balance) {
            $db->trans_rollback();
            return false;
        }

        $amount_delta = $this->negate_amount($balance->balance);

        if ($amount_delta === false) {
            $db->trans_rollback();
            return false;
        }

        if (
            !$this->CI->Balance_model->update_balance(
                $user_id,
                '0.00'
            )
        ) {
            $db->trans_rollback();
            return false;
        }

        $event_id = $this->CI->Balance_history_model->create_event(
            array(
                'user_id' => $user_id,
                'event_type' => 'balance_reset',
                'amount_delta' => $amount_delta,
                'target_balance' => '0.00',
                'effective_date' => date('Y-m-d'),
                'description' => 'Manual balance reset'
            )
        );

        if (
            $event_id === false ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }
}
