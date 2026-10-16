<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_login_throttles extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS login_throttles (
                bucket_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                window_started_at DATETIME NOT NULL,
                PRIMARY KEY (bucket_key),
                INDEX idx_login_throttles_window (window_started_at)
            ) ENGINE=InnoDB"
        );
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS login_throttles');
    }
}
