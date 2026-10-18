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



    public function find_transaction_by_id($user_id, $transaction_id)
    {
        return $this->db
            ->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->get($this->table)
            ->row();
    }


    public function find_active_transactions_by_category($user_id, $category_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('category_id', $category_id)
            ->where('deleted_at', null)
            ->get($this->table)->result();
    }


    public function has_transactions_by_category($user_id, $category_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('category_id', $category_id)
            ->count_all_results($this->table) > 0;
    }


    /**
     * دریافت تراکنش‌های فعال کاربر.
     *
     * $limit:
     * تعداد رکوردهای هر صفحه.
     *
     * $offset:
     * تعداد رکوردهایی که باید رد شوند.
     *
     * اگر limit برابر null باشد، تمام نتایج مطابق رفتار قبلی برگردانده می‌شوند.
     */
    public function find_active_transactions_by_user_id(
        $user_id,
        $limit = null,
        $offset = 0
    ) {
        $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->order_by('transaction_date', 'DESC')
            ->order_by('id', 'DESC');

        if ($limit !== null) {
            $limit = max(1, (int) $limit);
            $offset = max(0, (int) $offset);

            $this->db->limit($limit, $offset);
        }

        return $this->db
            ->get($this->table)
            ->result();
    }


    /**
     * شمارش کل تراکنش‌های فعال کاربر.
     *
     * برای محاسبه تعداد صفحات استفاده می‌شود.
     */
    public function count_active_transactions_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->count_all_results($this->table);
    }



    public function find_deleted_transactions_by_user_id(
        $user_id,
        $limit = null,
        $offset = 0
    ) {
        $this->db
            ->select(
                'transactions.*,
                 categories.name AS category_name,
                 categories.deleted_at AS category_deleted_at'
            )
            ->from($this->table)
            ->join(
                'categories',
                'categories.id = transactions.category_id',
                'left'
            )
            ->where('transactions.user_id', $user_id)
            ->where(
                'transactions.deleted_at IS NOT NULL',
                null,
                false
            )
            ->order_by('transactions.deleted_at', 'DESC')
            ->order_by('transactions.id', 'DESC');

        if ($limit !== null) {
            $limit = max(1, (int) $limit);
            $offset = max(0, (int) $offset);

            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result();
    }



    public function count_deleted_transactions_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where(
                'deleted_at IS NOT NULL',
                null,
                false
            )
            ->count_all_results($this->table);
    }


    public function update_active_transaction_by_user(
        $user_id,
        $transaction_id,
        $data
    ) {
        return $this->db
            ->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, $data);
    }


    public function soft_delete_transaction_by_user(
        $user_id,
        $transaction_id,
        $deleted_at,
        $restore_until
    ) {
        $is_updated = $this->db
            ->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, array(
                'deleted_at' => $deleted_at,
                'restore_until' => $restore_until,
                'updated_at' => $deleted_at
            ));

        return $is_updated && $this->db->affected_rows() === 1;
    }


    public function restore_transaction_by_user($user_id, $transaction_id)
    {
        $is_updated = $this->db
            ->where('id', $transaction_id)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update($this->table, array(
                'deleted_at' => null,
                'restore_until' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ));

        return $is_updated && $this->db->affected_rows() === 1;
    }
}
