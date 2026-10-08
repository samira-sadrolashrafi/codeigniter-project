<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    private $table = 'users';

    public function create($data)
    {
        if (!$this->db->insert($this->table, $data)) {
            return false;
        }
        return (int) $this->db->insert_id();
    }

    public function find_by_username($username)
    {
        return $this->db->where('username', $username)->get($this->table)->row();
    }

    public function find_by_email($email)
    {
        return $this->db->where('email', $email)->get($this->table)->row();
    }

    public function find_by_remember_token($hashed_token)
    {
        return $this->db->where('remember_token', $hashed_token)->where('deleted_at IS NULL', null, false)->get($this->table)->row();
    }

    public function lock_active_by_id($user_id)
    {
        return $this->db->query('SELECT id FROM users WHERE id = ? AND deleted_at IS NULL FOR UPDATE',array($user_id))->row();
    }

    public function update_remember_token($user_id, $hash, $expires_at)
    {
        return $this->db->where('id', $user_id)->where('deleted_at IS NULL', null, false)->update($this->table, array('remember_token' => $hash,'remember_token_expires_at' => $expires_at));
    }

    public function clear_remember_token($user_id)
    {
        return $this->db->where('id', $user_id)->update($this->table, array('remember_token' => null,'remember_token_expires_at' => null));
    }
}