<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_users extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),

            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => 100
            ),

            'email' => array(
                'type' => 'VARCHAR',
                'constraint' => 191
            ),

            'password' => array(
                'type' => 'VARCHAR',
                'constraint' => 255
            ),

            'avatar' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE
            ),

            'created_at' => array(
                'type' => 'DATETIME'
            ),

            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),

            'deleted_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),

            'restore_until' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),

            'remember_token' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE
            ),

            'remember_token_expires_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            )
        ));

        $this->dbforge->add_key('id', TRUE);

        $this->dbforge->create_table(
            'users',
            TRUE,
            array(
                'ENGINE' => 'InnoDB'
            )
        );

        $this->db->query(
            'ALTER TABLE `users`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );

        $this->db->query(
            'ALTER TABLE `users`
             ADD CONSTRAINT `uq_users_username`
             UNIQUE (`username`)'
        );

        $this->db->query(
            'ALTER TABLE `users`
             ADD CONSTRAINT `uq_users_email`
             UNIQUE (`email`)'
        );

        $this->db->query(
            'CREATE INDEX `idx_users_remember_token`
             ON `users` (`remember_token`)'
        );
    }

    public function down()
    {
        $this->dbforge->drop_table(
            'users',
            TRUE
        );
    }
}