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

    // Return a normalized DECIMAL(15,2) string, or false. 
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
        if (!$allow_negative && !preg_match('/[1-9]/', $result)) {
            return false;
        }
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
        if ($amount === false || !$this->CI->User_model->lock_active_by_id($user_id) ||
            !$this->CI->Balance_model->find_by_user_id($user_id)) {
            return false;
        }
        return $this->CI->Balance_model->change_by($user_id, $amount);
    }

    public function decrease($user_id, $amount)
    {
        $amount = $this->normalize_amount($amount);
        if ($amount === false || !$this->CI->User_model->lock_active_by_id($user_id) ||
            !$this->CI->Balance_model->find_by_user_id($user_id)) {
            return false;
        }
        return $this->CI->Balance_model->change_by($user_id, '-' . $amount);
    }

    public function get($user_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        return $this->CI->Balance_model->find_by_user_id($user_id);
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

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $balance = $this->CI->Balance_model->find_by_user_id($user_id);

        if (!$balance) {
            $db->trans_rollback();
            return false;
        }

        // Keep the manual adjustment as an exact signed DECIMAL change in SQL.
        // A zero adjustment is a valid no-op, not a failed UPDATE.
        if ($amount !== '0.00' && !$this->CI->Balance_model->change_by($user_id, $amount)) {
            $db->trans_rollback();
            return false;
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

        if (!$this->CI->User_model->lock_active_by_id($user_id) ||
            !$this->CI->Balance_model->find_by_user_id($user_id) ||
            !$this->CI->Balance_model->update_balance($user_id, '0.00') ||
            $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }
}
