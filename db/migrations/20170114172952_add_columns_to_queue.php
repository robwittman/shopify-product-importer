<?php

use Phinx\Migration\AbstractMigration;

class AddColumnsToQueue extends AbstractMigration
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
     *    addIndexs
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $queue = $this->table('queue');
        $queue
            ->addColumn('status', 'string', array('limit' => 45, 'default' => "PENDING", 'null' => false))
            // ->addColumn('results', 'text', array('default' => null))
            ->update();
    }
}
