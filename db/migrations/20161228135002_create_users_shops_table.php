<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersShopsTable extends AbstractMigration
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
        $matrix = $this->table('users_shops');
        $matrix
            ->addColumn('user_id', 'integer', array('limit' => 11, 'null' => false))
            ->addColumn('shop_id', 'integer', array('limit' => 11, 'null' => false))
            ->addForeignKey('user_id', 'users', 'id', array("delete" => "CASCADE", "update" => "NO_ACTION"))
            ->addForeignKey('shop_id', 'shops', 'id', array("delete" => "CASCADE", "update" => "NO_ACTION"))
            ->create();
    }
}
