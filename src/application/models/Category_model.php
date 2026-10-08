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

    public function find_owned_for_update($user_id, $category_id)
    {
        return $this->db->query(
            'SELECT * FROM categories WHERE id = ? AND user_id = ? FOR UPDATE',
            array($category_id, $user_id)
        )->row();
    }

    public function find_all_by_name($user_id, $name)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('name', $name)
            ->get($this->table)->result();
    }

    public function find_active_by_user_id($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('id', 'DESC')->get($this->table)->result();
    }

    public function update_owned_active($user_id, $category_id, $data)
    {
        return $this->db->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }

    public function soft_delete_owned($user_id, $category_id, $now, $until)
    {
        $query_success = $this->db->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, array(
                'deleted_at' => $now,
                'restore_until' => $until,
                'updated_at' => $now
            ));
        return $query_success && $this->db->affected_rows() === 1;
    }

    public function restore_owned($user_id, $category_id)
    {
        $query_success = $this->db->where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update($this->table, array(
                'deleted_at' => null,
                'restore_until' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        return $query_success && $this->db->affected_rows() === 1;
    }
}