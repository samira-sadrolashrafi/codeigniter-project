<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_throttle_model extends CI_Model
{
    private function use_bucket($bucket_key, $max_attempts)
    {
        $query = $this->db->query(
            'INSERT INTO login_throttles (bucket_key, attempts, window_started_at)
             VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
             attempts = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                           1, attempts + 1),
             window_started_at = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                                    NOW(), window_started_at)',
            array($bucket_key)
        );

        if (!$query) {
            return false;
        }

        $row = $this->db->select('attempts')
            ->where('bucket_key', $bucket_key)
            ->get('login_throttles')->row();

        return $row && (int) $row->attempts <= $max_attempts;
    }

    public function allow($username)
    {
        $account_key = hash(
            'sha256',
            'account:' . strtolower($username)
        );

        return $this->use_bucket($account_key, 10);
    }

    public function clear_account($username)
    {
        $key = hash('sha256', 'account:' . strtolower($username));
        return $this->db->where('bucket_key', $key)
            ->delete('login_throttles');
    }
}
