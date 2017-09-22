<?php

use Phinx\Migration\AbstractMigration;

class AddPrintUrlToQueue extends AbstractMigration
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
        $queue = $this->table('queue');
        $queue
            ->addColumn('front_print_url', 'string', array('limit' => 255, 'null' => true, 'default' => null))
            ->addColumn('back_print_url', 'string', array('limit' => 255, 'null' => true, 'default' => null))
            ->update();
    }
}
