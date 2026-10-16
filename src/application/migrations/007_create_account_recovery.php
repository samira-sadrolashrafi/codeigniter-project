<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_account_recovery extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS user_recovery (
            user_id INT UNSIGNED NOT NULL,
            question_id TINYINT UNSIGNED NOT NULL,
            answer_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (user_id),
            CONSTRAINT fk_user_recovery_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->db->query('CREATE TABLE IF NOT EXISTS recovery_throttles (
            bucket_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            window_started_at DATETIME NOT NULL,
            PRIMARY KEY (bucket_key),
            INDEX idx_recovery_throttles_window (window_started_at)
        ) ENGINE=InnoDB');
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS recovery_throttles');
        $this->db->query('DROP TABLE IF EXISTS user_recovery');
    }
}
