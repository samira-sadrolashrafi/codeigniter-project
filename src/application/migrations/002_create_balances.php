<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_balances extends CI_Migration
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

            'balance' => array(
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => '0.00'
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

        $this->dbforge->create_table(
            'balances',
            TRUE,
            array(
                'ENGINE' => 'InnoDB'
            )
        );

        $this->db->query(
            'ALTER TABLE `balances`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );

        $this->db->query(
            'ALTER TABLE `balances`
             ADD CONSTRAINT `uq_balances_user_id`
             UNIQUE (`user_id`)'
        );

        $this->db->query(
            'ALTER TABLE `balances`
             ADD CONSTRAINT `fk_balances_user`
             FOREIGN KEY (`user_id`)
             REFERENCES `users` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );
    }

    public function down()
    {
        $this->dbforge->drop_table(
            'balances',
            TRUE
        );
    }
}