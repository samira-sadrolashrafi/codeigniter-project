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
        if (
            !is_array($data) ||
            !isset($data['name'], $data['type']) ||
            !$this->valid_name($data['name']) ||
            !in_array($data['type'], array('income', 'expense'), true)
        ) {
            return false;
        }

        $name = trim($data['name']);
        $db = $this->CI->db;

        $db->trans_begin();

        if (
            !$this->CI->User_model->find_active_by_id($user_id) ||
            $this->check_duplicate_name($user_id, $name)
        ) {
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

    public function get($user_id, $category_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id($user_id, $category_id);

        return ($category && $category->deleted_at === null)
            ? $category
            : false;
    }

    public function get_all($user_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        return $this->CI->Category_model
            ->find_active_categories_by_user_id($user_id);
    }

    public function get_deleted($user_id)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        return $this->CI->Category_model
            ->find_deleted_categories_by_user_id($user_id);
    }

    /**
     * دریافت دسته‌بندی‌های فعال به‌صورت صفحه‌بندی‌شده،
     * همراه با فیلتر جست‌وجو، نوع و مرتب‌سازی.
     */
    public function get_all_paginated(
        $user_id,
        $page,
        $per_page,
        $filters = array()
    ) {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        // اعتبارسنجی و اصلاح شماره صفحه و تعداد نتایج هر صفحه
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);

        $offset = ($page - 1) * $per_page;

        // اطمینان از اینکه فیلترها یک آرایه هستند
        $filters = is_array($filters) ? $filters : array();

        // فیلتر جست‌وجوی نام دسته‌بندی
        $search = isset($filters['search']) &&
            is_scalar($filters['search'])
                ? trim((string) $filters['search'])
                : '';

        // فیلتر نوع دسته‌بندی
        $type = isset($filters['type']) &&
            in_array(
                $filters['type'],
                array('all', 'income', 'expense'),
                true
            )
                ? $filters['type']
                : 'all';

        // مرتب‌سازی پیش‌فرض: جدیدترین دسته‌بندی‌ها
        $sort = isset($filters['sort']) &&
            in_array(
                $filters['sort'],
                array('newest', 'oldest'),
                true
            )
                ? $filters['sort']
                : 'newest';

        // فقط مقادیر معتبر به مدل ارسال می‌شوند
        $normalized_filters = array(
            'search' => $search,
            'type' => $type,
            'sort' => $sort
        );

        // دریافت دسته‌بندی‌های مطابق فیلترها و صفحه درخواستی
        $categories = $this->CI->Category_model
            ->find_active_categories_by_user_id_paginated(
                $user_id,
                $per_page,
                $offset,
                $normalized_filters
            );

        // شمارش کل نتایج مطابق همان فیلترها
        $total = $this->CI->Category_model
            ->count_active_categories_by_user_id(
                $user_id,
                $normalized_filters
            );

        return array(
            'categories' => $categories,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => (int) ceil($total / $per_page)
            )
        );
    }

    /**
     * دریافت دسته‌بندی‌های حذف‌شده به‌صورت صفحه‌بندی‌شده.
     */
    public function get_deleted_paginated($user_id, $page, $per_page)
    {
        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            return false;
        }

        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);

        $offset = ($page - 1) * $per_page;

        $categories = $this->CI->Category_model
            ->find_deleted_categories_by_user_id_paginated(
                $user_id,
                $per_page,
                $offset
            );

        $total = $this->CI->Category_model
            ->count_deleted_categories_by_user_id($user_id);

        return array(
            'categories' => $categories,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => (int) ceil($total / $per_page)
            )
        );
    }

    public function update($user_id, $category_id, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        if (
            !array_key_exists('name', $data) &&
            !array_key_exists('type', $data)
        ) {
            return false;
        }

        $db = $this->CI->db;

        $db->trans_begin();

        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id($user_id, $category_id);

        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $changes = array();

        if (array_key_exists('name', $data)) {

            // Validate the raw input before trim()
            // to avoid a TypeError for non-string values.
            if (!$this->valid_name($data['name'])) {
                $db->trans_rollback();
                return false;
            }

            $new_name = trim($data['name']);

            if (
                $this->check_duplicate_name(
                    $user_id,
                    $new_name,
                    $category_id
                )
            ) {
                $db->trans_rollback();
                return false;
            }

            $changes['name'] = $new_name;
        }

        if (array_key_exists('type', $data)) {

            $new_type = $data['type'];

            if (!in_array($new_type, array('income', 'expense'), true)) {
                $db->trans_rollback();
                return false;
            }

            // اگر تراکنشی به دسته‌بندی مرتبط باشد،
            // تغییر نوع دسته‌بندی مجاز نیست.
            if (
                $new_type !== $category->type &&
                $this->CI->Transaction_model
                    ->has_transactions_by_category($user_id, $category_id)
            ) {
                $db->trans_rollback();
                return false;
            }

            $changes['type'] = $new_type;
        }

        // زمان آخرین تغییر
        $changes['updated_at'] = date('Y-m-d H:i:s');

        // ذخیره تغییرات
        $updated = $this->CI->Category_model
            ->update_active_category_by_user(
                $user_id,
                $category_id,
                $changes
            );

        if (!$updated || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    public function delete($user_id, $category_id)
    {
        $db = $this->CI->db;

        $db->trans_begin();

        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id($user_id, $category_id);

        if (!$category || $category->deleted_at !== null) {
            $db->trans_rollback();
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $until = date(
            'Y-m-d H:i:s',
            time() + $this->restore_days * 86400
        );

        $transactions = $this->CI->Transaction_model
            ->find_active_transactions_by_category(
                $user_id,
                $category_id
            );

        foreach ($transactions as $transaction) {
            if (
                !$this->CI->Transaction_model
                    ->soft_delete_transaction_by_user(
                        $user_id,
                        $transaction->id,
                        $now,
                        $until
                    ) ||
                !$this->balance_service->reverse_transaction_effect(
                    $user_id,
                    $category->type,
                    $transaction->amount
                )
            ) {
                $db->trans_rollback();
                return false;
            }
        }

        if (
            !$this->CI->Category_model->soft_delete_category_by_user(
                $user_id,
                $category_id,
                $now,
                $until
            ) ||
            $db->trans_status() === false
        ) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    public function restore($user_id, $category_id)
    {
        $db = $this->CI->db;

        $db->trans_begin();

        if (!$this->CI->User_model->find_active_by_id($user_id)) {
            $db->trans_rollback();
            return false;
        }

        $category = $this->CI->Category_model
            ->find_category_by_id($user_id, $category_id);

        if (
            !$category ||
            !$category->deleted_at ||
            !$category->restore_until ||
            strtotime($category->restore_until) <= time() ||
            $this->check_duplicate_name(
                $user_id,
                $category->name,
                $category_id
            )
        ) {
            $db->trans_rollback();
            return false;
        }

        $querysuccess = $this->CI->Category_model
            ->restore_category_by_user($user_id, $category_id);

        if (!$querysuccess || $db->trans_status() === false) {
            $db->trans_rollback();
            return false;
        }

        return $db->trans_commit();
    }

    private function valid_name($name)
    {
        return is_string($name) &&
            preg_match('/^.{1,100}$/u', trim($name)) === 1;
    }

    private function check_duplicate_name(
        $user_id,
        $name,
        $ignore_id = null
    ) {
        $categories = $this->CI->Category_model
            ->find_categories_by_name($user_id, $name);

        foreach ($categories as $category) {
            if (
                $ignore_id !== null &&
                (int) $category->id === (int) $ignore_id
            ) {
                continue;
            }

            if (
                $category->deleted_at === null ||
                (
                    $category->restore_until &&
                    strtotime($category->restore_until) > time()
                )
            ) {
                return true;
            }
        }

        return false;
    }
}