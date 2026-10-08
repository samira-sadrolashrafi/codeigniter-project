<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'services/Balance_service.php';

class Transaction_service
{
    protected $CI;
    private $balance_service;
    private $restore_days = 30;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('User_model');
        $this->CI->load->model('Category_model');
        $this->CI->load->model('Transaction_model');
        $this->balance_service = new Balance_service();
    }

    private function validate_data($data)
    {
        if (!is_array($data) || !isset($data['title'], $data['amount'], $data['category_id'], $data['transaction_date']) || !is_string($data['title']) || (!is_string($data['category_id']) && !is_int($data['category_id']))) {
            return false;
        }

        $title = trim($data['title']);

        if (preg_match('/^.{1,150}$/u', $title) !== 1 || !ctype_digit((string) $data['category_id']) || (int) $data['category_id'] <= 0) {
            return false;
        }

        $amount = $this->balance_service->normalize_amount($data['amount']);

        if ($amount === false || !is_string($data['transaction_date']) ||!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['transaction_date'])) {
            return false;
        }

        $date = DateTime::createFromFormat('!Y-m-d', $data['transaction_date']);

        if (!$date || $date->format('Y-m-d') !== $data['transaction_date']) {
            return false;
        }

        $description = $data['description'] ?? null;

        if ($description !== null && !is_string($description)) {
            return false;
        }

        return array(
            'title' => $title,
            'amount' => $amount,
            'category_id' => (int) $data['category_id'],
            'transaction_date' => $data['transaction_date'],
            'description' => $description === '' ? null : $description
        );
    }

    public function create($user_id, $data)
    {
        $values = $this->validate_data($data);
        if ($values === false) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }
        $category = $this->CI->Category_model->find_owned_for_update($user_id, $values['category_id']);
        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }
        $values['user_id'] = $user_id;
        $values['created_at'] = date('Y-m-d H:i:s');
        $id = $this->CI->Transaction_model->create($values);
        if (!$id || !$this->balance_service->apply_transaction_effect($user_id, $category->type, $values['amount']) ||$db->trans_status() === false) {$db->trans_rollback();
            return false;
        }
        return $db->trans_commit() ? $id : false;
    }

    public function update($user_id, $transaction_id, $data)
    {
        $values = $this->validate_data($data);
        if ($values === false) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();
        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $old = $this->CI->Transaction_model->find_owned_for_update($user_id, $transaction_id);
        if (!$old || $old->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $old_category = $this->CI->Category_model->find_owned_for_update($user_id, $old->category_id);
        $new_category = $this->CI->Category_model->find_owned_for_update($user_id, $values['category_id']);
        if (!$old_category || !$new_category || $new_category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $values['updated_at'] = date('Y-m-d H:i:s');
        if (!$this->balance_service->reverse_transaction_effect($user_id, $old_category->type, $old->amount) || !$this->CI->Transaction_model->update_owned_active($user_id, $transaction_id, $values) || !$this->balance_service->apply_transaction_effect($user_id, $new_category->type, $values['amount']) || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }

    public function delete($user_id, $transaction_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();
        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }
        $transaction = $this->CI->Transaction_model->find_owned_for_update($user_id, $transaction_id);
        if (!$transaction || $transaction->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }
        $category = $this->CI->Category_model->find_owned_for_update($user_id, $transaction->category_id);
        if (!$category) {
            $db->trans_rollback();
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', time() + $this->restore_days * 86400);
        if (!$this->CI->Transaction_model->soft_delete_owned($user_id, $transaction_id, $now, $until) ||
            !$this->balance_service->reverse_transaction_effect($user_id, $category->type, $transaction->amount) ||
            $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }

    public function restore($user_id, $transaction_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();
        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }
        $transaction = $this->CI->Transaction_model->find_owned_for_update($user_id, $transaction_id);
        if (!$transaction || !$transaction->deleted_at || !$transaction->restore_until || strtotime($transaction->restore_until) <= time()) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model->find_owned_for_update($user_id, $transaction->category_id);
        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }
        if (!$this->CI->Transaction_model->restore_owned($user_id, $transaction_id) || !$this->balance_service->apply_transaction_effect($user_id, $category->type, $transaction->amount) || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }
}