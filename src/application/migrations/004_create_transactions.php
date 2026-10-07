<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_transactions extends CI_Migration
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

            'category_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE
            ),

            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => 150
            ),

            'amount' => array(
                'type' => 'DECIMAL',
                'constraint' => '15,2'
            ),

            'transaction_date' => array(
                'type' => 'DATE'
            ),

            'description' => array(
                'type' => 'TEXT',
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
            )
        ));

        $this->dbforge->add_key('id', TRUE);

        $this->dbforge->add_key('user_id');

        $this->dbforge->add_key('category_id');

        $this->dbforge->add_key('transaction_date');

        $this->dbforge->create_table(
            'transactions',
            TRUE,
            array(
                'ENGINE' => 'InnoDB'
            )
        );

        $this->db->query(
            'ALTER TABLE `transactions`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );

        $this->db->query(
            'ALTER TABLE `transactions`
             ADD CONSTRAINT `fk_transactions_user`
             FOREIGN KEY (`user_id`)
             REFERENCES `users` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );

        $this->db->query(
            'ALTER TABLE `transactions`
             ADD CONSTRAINT `fk_transactions_category`
             FOREIGN KEY (`category_id`)
             REFERENCES `categories` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );
    }

    public function down()
    {
        $this->dbforge->drop_table(
            'transactions',
            TRUE
        );
    }
}