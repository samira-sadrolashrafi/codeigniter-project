<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends CI_Model
{
    private $table = 'categories';


    public function create($data)
    {
        if (!$this->db->insert($this->table, $data)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }


    public function find_category_by_id($user_id, $category_id)
    {
        return $this->db
            ->where('id', $category_id)
            ->where('user_id', $user_id)
            ->get($this->table)
            ->row();
    }


    public function find_categories_by_name($user_id, $name)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->get($this->table)
            ->result();
    }


    public function find_active_categories_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result();
    }


    /**
     * دریافت یک صفحه از دسته‌بندی‌های فعال با فیلتر و مرتب‌سازی.
     *
     * filters:
     * search = عبارت جست‌وجو در نام دسته‌بندی
     * type   = all | income | expense
     * sort   = newest | oldest
     */
    public function find_active_categories_by_user_id_paginated(
        $user_id,
        $limit,
        $offset,
        $filters = array()
    ) {
        $this->apply_active_category_filters($user_id, $filters);

        $sort = $filters['sort'] ?? 'newest';

        if ($sort === 'oldest') {
            $this->db
                ->order_by('created_at', 'ASC')
                ->order_by('id', 'ASC');
        } else {
            $this->db
                ->order_by('created_at', 'DESC')
                ->order_by('id', 'DESC');
        }

        return $this->db
            ->limit(max(1, (int) $limit), max(0, (int) $offset))
            ->get()
            ->result();
    }


    /**
     * شمارش دسته‌بندی‌های فعال مطابق فیلترهای اعمال‌شده.
     */
    public function count_active_categories_by_user_id(
        $user_id,
        $filters = array()
    ) {
        $this->apply_active_category_filters($user_id, $filters);

        return $this->db->count_all_results();
    }


    /**
     * اعمال فیلترهای دسته‌بندی‌های فعال.
     */
    private function apply_active_category_filters(
        $user_id,
        $filters = array()
    ) {
        $this->db
            ->from($this->table)
            ->where('user_id', $user_id)
            ->where('deleted_at', null);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $this->db->like('name', $search);
        }

        $type = $filters['type'] ?? 'all';

        if (in_array($type, array('income', 'expense'), true)) {
            $this->db->where('type', $type);
        }
    }


    public function find_deleted_categories_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->order_by('deleted_at', 'DESC')
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result();
    }


    /**
     * دریافت یک صفحه از دسته‌بندی‌های حذف‌شده.
     */
    public function find_deleted_categories_by_user_id_paginated(
        $user_id,
        $limit,
        $offset
    ) {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->order_by('deleted_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(max(1, (int) $limit), max(0, (int) $offset))
            ->get($this->table)
            ->result();
    }


    /**
     * شمارش دسته‌بندی‌های حذف‌شده.
     */
    public function count_deleted_categories_by_user_id($user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->count_all_results($this->table);
    }


    public function update_active_category_by_user(
        $user_id,
        $category_id,
        $data
    ) {
        return $this->db
            ->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, $data);
    }


    public function soft_delete_category_by_user(
        $user_id,
        $category_id,
        $now,
        $until
    ) {
        $querysuccess = $this->db
            ->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, array(
                'deleted_at' => $now,
                'restore_until' => $until,
                'updated_at' => $now
            ));

        return $querysuccess && $this->db->affected_rows() === 1;
    }


    public function restore_category_by_user($user_id, $category_id)
    {
        $querysuccess = $this->db
            ->where('id', $category_id)
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