<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction_model extends CI_Model
{
    private $table = 'transactions';

    public function create($data)
    {
        if (!$this->db->insert($this->table, $data)) {
            return false;
        }
        return (int) $this->db->insert_id();
    }

    public function find_owned_for_update($user_id, $transaction_id)
    {
        $query = $this->db->query(
            'SELECT * FROM transactions WHERE id = ? AND user_id = ? FOR UPDATE',
            array($transaction_id, $user_id)
        );
        return $query ? $query->row() : false;
    }

    public function find_active_by_category_for_update($user_id, $category_id)
    {
        $query = $this->db->query(
            'SELECT * FROM transactions
             WHERE user_id = ? AND category_id = ? AND deleted_at IS NULL
             FOR UPDATE',
            array($user_id, $category_id)
        );
        return $query ? $query->result() : false;
    }

    // Includes deleted transactions: the category's type may not change historically.
    public function has_any_by_category($user_id, $category_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('category_id', $category_id)
            ->count_all_results($this->table) > 0;
    }

    public function find_active_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->order_by('transaction_date', 'DESC')
            ->get($this->table)->result();
    }

    public function update_owned_active($user_id, $transaction_id, $data)
    {
        return $this->db->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, $data);
    }

    public function soft_delete_owned($user_id, $transaction_id, $now, $until)
    {
        $querysuccess = $this->db->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, array(
                'deleted_at' => $now,
                'restore_until' => $until,
                'updated_at' => $now
            ));
        return $querysuccess && $this->db->affected_rows() === 1;
    }

    public function restore_owned($user_id, $transaction_id)
    {
        $querysuccess = $this->db->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update($this->table, array(
                'deleted_at' => null,
                'restore_until' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        return $querysuccess && $this->db->affected_rows() === 1;
    }
}