<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** تمام عملیات دیتابیس مخصوص پروفایل در این مدل انجام می‌شود. */
class Profile_model extends CI_Model
{
    public function find_active_user($user_id)
    {
        return $this->db->where('id', (int) $user_id)
            ->where('deleted_at', null)->get('users')->row();
    }

    public function find_recovery_question($user_id)
    {
        return $this->db->select('question_id')
            ->where('user_id', (int) $user_id)
            ->get('user_recovery')->row();
    }

    public function username_taken($username, $user_id)
    {
        return $this->db->where('username', $username)
            ->where('id !=', (int) $user_id)->count_all_results('users') > 0;
    }

    public function email_taken($email, $user_id)
    {
        return $this->db->where('email', $email)
            ->where('id !=', (int) $user_id)->count_all_results('users') > 0;
    }

    public function lock_active_user($user_id)
    {
        $result = $this->db->query(
            'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL FOR UPDATE',
            array((int) $user_id)
        );
        return $result ? $result->row() : false;
    }

    public function update_active_user($user_id, $changes)
    {
        return $this->db->where('id', (int) $user_id)
            ->where('deleted_at', null)->update('users', $changes);
    }

    public function save_recovery_question($user_id, $question_id, $answer)
    {
        $hash = password_hash(trim($answer), PASSWORD_DEFAULT);
        if ($hash === false) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->query(
            'INSERT INTO user_recovery (user_id, question_id, answer_hash, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE question_id = VALUES(question_id),
               answer_hash = VALUES(answer_hash), updated_at = VALUES(updated_at)',
            array((int) $user_id, (int) $question_id, $hash, $now, $now)
        );
    }
}