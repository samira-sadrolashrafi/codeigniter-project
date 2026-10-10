<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Balance_model extends CI_Model
{
    private $table = 'balances';

    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function find_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)->row();
    }

    // An atomic DECIMAL addition in MariaDB; no PHP float arithmetic.
    public function change_by($user_id, $signed_amount)
    {
        $querysuccess = $this->db->query(
            'UPDATE balances
             SET balance = balance + CAST(? AS DECIMAL(15,2)),
                 updated_at = ?
             WHERE user_id = ? AND deleted_at IS NULL
               AND balance + CAST(? AS DECIMAL(15,2))
                   BETWEEN -9999999999999.99 AND 9999999999999.99',
            array($signed_amount, date('Y-m-d H:i:s'), $user_id, $signed_amount)
        );
        return $querysuccess && $this->db->affected_rows() === 1;
    }

    public function update_balance($user_id, $new_balance)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, array(
                'balance' => $new_balance,
                'updated_at' => date('Y-m-d H:i:s')
            ));
    }
}