<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Adds non-unique personal names without affecting existing users.
 * Nullable at DB level for existing accounts; required for new registrations
 * at the API layer. Username/email unique indexes remain unchanged.
 */
class Migration_Add_user_names extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();
        $this->dbforge->add_column('users', array(
            'first_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE
            ),
            'last_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE
            )
        ));
    }

    public function down()
    {
        $this->load->dbforge();
        $this->dbforge->drop_column('users', 'last_name');
        $this->dbforge->drop_column('users', 'first_name');
    }
}
