<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_categories extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),

            'user_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE
            ),

            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100
            ),

            'type' => array(
                'type' => 'ENUM',
                'constraint' => array(
                    'income',
                    'expense'
                )
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
            )
        ));

        $this->dbforge->add_key('id', TRUE);

        $this->dbforge->add_key(
            array(
                'user_id',
                'name'
            )
        );

        $this->dbforge->create_table(
            'categories',
            TRUE,
            array(
                'ENGINE' => 'InnoDB'
            )
        );

        $this->db->query(
            'ALTER TABLE `categories`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );

        $this->db->query(
            'ALTER TABLE `categories`
             ADD CONSTRAINT `fk_categories_user`
             FOREIGN KEY (`user_id`)
             REFERENCES `users` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );
    }

    public function down()
    {
        $this->dbforge->drop_table(
            'categories',
            TRUE
        );
    }
}