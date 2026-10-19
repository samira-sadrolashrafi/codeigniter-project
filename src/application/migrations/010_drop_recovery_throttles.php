<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Drop_recovery_throttles extends CI_Migration
{
    public function up()
    {
        $this->db->query('DROP TABLE IF EXISTS recovery_throttles');
    }

    public function down()
    {
        // Recovery throttling has been removed; the table is intentionally not restored.
    }
}
