<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $users = $this->table('users');
        $users
            ->addColumn('email', 'string', array('limit' => 145, 'null' => false))
            ->addColumn('password', 'string', array('limit' => 145, 'null' => false))
            ->addColumn('role', 'string', array('limit' => 45, 'null' => false))
            ->addIndex('email', array('unique' => true))
            ->create();
    }
}
