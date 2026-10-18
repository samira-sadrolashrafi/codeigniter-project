<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recovery_model extends CI_Model
{
    public function find_for_user($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->get('user_recovery')->row();
    }

    public function save_for_user($user_id, $question_id, $answer)
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

    public function reset_password($user_id, $new_password)
    {
        $this->db->trans_begin();
        $user = $this->db->query(
            'SELECT id FROM users WHERE id = ? AND deleted_at IS NULL',
            array((int) $user_id)
        )->row();

        if (!$user) {
            $this->db->trans_rollback();
            return false;
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        if ($new_hash === false) {
            $this->db->trans_rollback();
            return false;
        }

        $ok = $this->db->where('id', (int) $user_id)
            ->where('deleted_at', null)
            ->update('users', array(
                'password' => $new_hash,
                'updated_at' => date('Y-m-d H:i:s'),
                'remember_token' => null,
                'remember_token_expires_at' => null
            ));

        if (!$ok || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return $this->db->trans_commit();
    }
}
