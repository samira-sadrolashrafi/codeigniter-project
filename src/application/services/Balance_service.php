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
    }

    // Return a normalized DECIMAL(15,2) string, or false. Never use floats for money.
    public function normalize_amount($amount, $allow_negative = false)
    {
        if (!is_string($amount) && !is_int($amount)) {
            return false;
        }

        $value = trim((string) $amount);
        $pattern = $allow_negative
            ? '/^-?(?:0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/'
            : '/^(?:0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/';

        if (!preg_match($pattern, $value)) {
            return false;
        }
        if (!$allow_negative && !preg_match('/[1-9]/', $value)) {
            return false;
        }

        $negative = ($value[0] === '-');
        $unsigned = $negative ? substr($value, 1) : $value;
        $parts = explode('.', $unsigned, 2);
        $result = $parts[0] . '.' . str_pad($parts[1] ?? '', 2, '0');
        if ($negative && preg_match('/[1-9]/', $unsigned)) {
            return '-' . $result;
        }
        return $result;
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
        if ($amount === false) {
            return false;
        }
        return $this->CI->Balance_model->change_by($user_id, $amount);
    }

    public function decrease($user_id, $amount)
    {
        $amount = $this->normalize_amount($amount);
        if ($amount === false) {
            return false;
        }
        return $this->CI->Balance_model->change_by($user_id, '-' . $amount);
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

    public function update_manual_balance($user_id, $new_balance)
    {
        $new_balance = $this->normalize_amount($new_balance, true);
        if ($new_balance === false || (int) $user_id <= 0) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();
        if (!$this->CI->User_model->lock_active_by_id($user_id) || !$this->CI->Balance_model->find_by_user_id($user_id) || !$this->CI->Balance_model->update_balance($user_id, $new_balance) || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }
}