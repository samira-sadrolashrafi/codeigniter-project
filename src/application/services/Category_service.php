<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'services/Balance_service.php';

class Category_service
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

    public function create($user_id, $data)
    {
        if (!is_array($data) || !isset($data['name'], $data['type']) ||
            !$this->valid_name($data['name']) ||
            !in_array($data['type'], array('income', 'expense'), true)) {
            return false;
        }

        $name = trim($data['name']);
        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id) ||$this->check_duplicate_name($user_id, $name)) {
            $db->trans_rollback();
            return false;
        }

        $id = $this->CI->Category_model->create(array(
            'user_id' => $user_id,
            'name' => $name,
            'type' => $data['type'],
            'created_at' => date('Y-m-d H:i:s')
        ));
        if (!$id || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit() ? $id : false;
    }

    public function update($user_id, $category_id, $data)
    {
        if (!is_array($data) || (!array_key_exists('name', $data) && !array_key_exists('type', $data))) {
            return false;
        }

        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }
        $category = $this->CI->Category_model->find_owned_for_update($user_id, $category_id);
        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $changes = array();

        if (array_key_exists('name', $data)) {
            if (!$this->valid_name($data['name'])) {
                $db->trans_rollback();
                return false;
            }
            $name = trim($data['name']);
            if ($this->check_duplicate_name($user_id, $name, $category_id)) {
                $db->trans_rollback();
                return false;
            }
            $changes['name'] = $name;
        }
        if (array_key_exists('type', $data)) {
            if (!in_array($data['type'], array('income', 'expense'), true)) {
                $db->trans_rollback();
                return false;
            }

            if ($data['type'] !== $category->type && $this->CI->Transaction_model->has_any_by_category($user_id, $category_id)) {
                $db->trans_rollback();
                return false;
            }
            $changes['type'] = $data['type'];
        }

        $changes['updated_at'] = date('Y-m-d H:i:s');
        $ok = $this->CI->Category_model->update_owned_active($user_id, $category_id, $changes);
        if (!$ok || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }

    public function delete($user_id, $category_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();

        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model->find_owned_for_update($user_id, $category_id);
        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', time() + $this->restore_days * 86400);
        $transactions = $this->CI->Transaction_model->find_active_by_category_for_update($user_id, $category_id);

        foreach ($transactions as $transaction) {
            if (!$this->CI->Transaction_model->soft_delete_owned($user_id, $transaction->id, $now, $until) ||!$this->balance_service->reverse_transaction_effect(
                    $user_id, $category->type, $transaction->amount
                )) {
                $db->trans_rollback();
                return false;
            }
        }
        if (!$this->CI->Category_model->soft_delete_owned($user_id, $category_id, $now, $until) ||
            $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }

    public function restore($user_id, $category_id)
    {
        $db = $this->CI->db;
        $db->trans_begin();
        if (!$this->CI->User_model->lock_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }
        $category = $this->CI->Category_model->find_owned_for_update($user_id, $category_id);
        if (!$category || !$category->deleted_at || !$category->restore_until || strtotime($category->restore_until) <= time() || $this->check_duplicate_name($user_id, $category->name, $category_id)) {
            $db->trans_rollback();
            return false;
        }

        $ok = $this->CI->Category_model->restore_owned($user_id, $category_id);
        if (!$ok || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }
        return $db->trans_commit();
    }

    private function valid_name($name)
    {
        return is_string($name) && preg_match('/^.{1,100}$/u', trim($name)) === 1;
    }

    private function check_duplicate_name($user_id, $name, $ignore_id = null)
    {
        $categories = $this->CI->Category_model->find_all_by_name($user_id, $name);

        foreach ($categories as $category) {
            if ($ignore_id !== null && (int) $category->id === (int) $ignore_id) {
                continue;
            }
            if ($category->deleted_at === null || ($category->restore_until && strtotime($category->restore_until) > time())) {
                return true;
            }
        }
        return false;
    }
}