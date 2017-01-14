<?php

use Phinx\Migration\AbstractMigration;

class CreateProductQueue extends AbstractMigration
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
        $table = $this->table('queue');
        $table
            ->addColumn('created_at', 'timestamp', array('null' => false, 'default' => "CURRENT_TIMESTAMP"))
            ->addColumn('data', 'text', array('null' => false))
            ->addColumn('started_at', 'timestamp', array('null' => true, 'default' => null))
            ->addColumn('finished_at', 'timestamp', array('null' => true, 'default' => null))
            ->addColumn('product_id', 'string', array('null' => true, 'default' => null))
            ->create();
    }
}
