<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_ci_sessions extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'VARCHAR',
                'constraint' => 128
            ),

            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 45
            ),

            'timestamp' => array(
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => TRUE,
                'default' => 0
            ),

            'data' => array(
                'type' => 'BLOB'
            )
        ));

        $this->dbforge->add_key(
            'id',
            TRUE
        );

        $this->dbforge->add_key(
            'timestamp'
        );

        $this->dbforge->create_table(
            'ci_sessions',
            TRUE,
            array(
                'ENGINE' => 'InnoDB'
            )
        );

        $this->db->query(
            'ALTER TABLE `ci_sessions`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );
    }

    public function down()
    {
        $this->dbforge->drop_table(
            'ci_sessions',
            TRUE
        );
    }
}