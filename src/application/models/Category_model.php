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

    public function find_category_by_id_with_lock($user_id, $category_id)
    {
        $query = $this->db->query(
            'SELECT * FROM categories WHERE id = ? AND user_id = ? FOR UPDATE',
            array($category_id, $user_id)
        );
        return $query ? $query->row() : false;
    }

    public function find_category_by_id($user_id, $category_id)
    {
        return $this->db->where('id', $category_id)
            ->where('user_id', $user_id)
            ->get($this->table)->row();
    }

    public function find_categories_by_name($user_id, $name)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->get($this->table)->result();
    }

    public function find_active_categories_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->order_by('id', 'DESC')->get($this->table)->result();
    }

    /**
     * Get one page of active categories for a user.
     */
    public function find_active_categories_by_user_id_paginated($user_id, $limit, $offset)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->order_by('id', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get($this->table)->result();
    }

    /**
     * Count all active categories belonging to a user.
     */
    public function count_active_categories_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->count_all_results($this->table);
    }

    public function find_deleted_categories_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->order_by('deleted_at', 'DESC')->get($this->table)->result();
    }

    /**
     * Get one page of deleted categories for a user.
     */
    public function find_deleted_categories_by_user_id_paginated($user_id, $limit, $offset)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->order_by('deleted_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get($this->table)->result();
    }

    /**
     * Count all deleted categories belonging to a user.
     */
    public function count_deleted_categories_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->count_all_results($this->table);
    }

    public function update_active_category_by_user($user_id, $category_id, $data)
    {
        return $this->db->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at', null)
            ->update($this->table, $data);
    }

    public function soft_delete_category_by_user($user_id, $category_id, $now, $until)
    {
        $querysuccess = $this->db->where('id', $category_id)
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
        $querysuccess = $this->db->where('id', $category_id)
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
