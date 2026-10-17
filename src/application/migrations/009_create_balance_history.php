
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_balance_history extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field(array(
            // Unique identifier for each history event.
            'id' => array(
                'type' => 'BIGINT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),

            // Owner of this balance history event.
            'user_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE
            ),

            // Related transaction; NULL for non-transaction events.
            'transaction_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE
            ),

            // Type of event: income, expense, reset, etc.
            'event_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '40'
            ),

            // Signed change in balance.
            'amount_delta' => array(
                'type' => 'DECIMAL',
                'constraint' => '15,2'
            ),

            // Target balance for reset/snapshot events.
            // NULL for ordinary financial events.
            'target_balance' => array(
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => TRUE
            ),

            // Financial date on which this event takes effect.
            'effective_date' => array(
                'type' => 'DATE'
            ),

            // Optional explanation of the event.
            'description' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => TRUE
            ),

            // Actual time when the event was recorded.
            'created_at' => array(
                'type' => 'DATETIME'
            )
        ));

        // Primary key.
        $this->dbforge->add_key('id', TRUE);

        // Retrieve a user's financial history by effective date.
        $this->dbforge->add_key(
            array('user_id', 'effective_date', 'id')
        );

        // Retrieve history by actual recording time.
        $this->dbforge->add_key(
            array('user_id', 'created_at')
        );

        // Index for the transaction foreign key.
        $this->dbforge->add_key('transaction_id');

        // Create table using InnoDB.
        $this->dbforge->create_table(
            'balance_history',
            TRUE,
            array('ENGINE' => 'InnoDB')
        );

        // Match the application's character set and collation.
        $this->db->query(
            'ALTER TABLE `balance_history`
             CONVERT TO CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci'
        );

        // Associate every event with its owner.
        $this->db->query(
            'ALTER TABLE `balance_history`
             ADD CONSTRAINT `fk_balance_history_user`
             FOREIGN KEY (`user_id`)
             REFERENCES `users` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );

        // Link transaction-related events to their transaction.
        $this->db->query(
            'ALTER TABLE `balance_history`
             ADD CONSTRAINT `fk_balance_history_transaction`
             FOREIGN KEY (`transaction_id`)
             REFERENCES `transactions` (`id`)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );
    }

    public function down()
    {
        // WARNING: This permanently deletes the history table and its data.
        $this->dbforge->drop_table('balance_history', TRUE);
    }
}
