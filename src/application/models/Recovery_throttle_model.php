<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recovery_throttle_model extends CI_Model
{
    // Account and IP buckets, independent of the optional login throttle.
    // The 15-minute window is anchored at the first request.
    private function hit($key, $maximum)
    {
        $ok = $this->db->query(
            'INSERT INTO recovery_throttles (bucket_key, attempts, window_started_at)
             VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE
               attempts = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                             1, attempts + 1),
               window_started_at = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                                      NOW(), window_started_at)',
            array($key)
        );
        if (!$ok) {
            return false;
        }
        $row = $this->db->select('attempts')->where('bucket_key', $key)
            ->get('recovery_throttles')->row();
        return $row && (int) $row->attempts <= $maximum;
    }

    public function allow($username, $ip, $purpose, $per_account, $per_ip)
    {
        $account_key = hash('sha256', 'recovery:' . $purpose . ':user:' . strtolower($username));
        $ip_key = hash('sha256', 'recovery:' . $purpose . ':ip:' . $ip);
        $this->db->trans_begin();
        $ok = $this->hit($account_key, $per_account)
            && $this->hit($ip_key, $per_ip);
        if (!$ok || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        return $this->db->trans_commit();
    }
}
