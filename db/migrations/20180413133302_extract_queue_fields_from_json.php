<?php

use Phinx\Migration\AbstractMigration;

class ExtractQueueFieldsFromJson extends AbstractMigration
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
            ->addColumn('vendor', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('product_type', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('title', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('file', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('tags', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('showcase_color', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('showcase_product', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('print_type', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->update();
    }
}
