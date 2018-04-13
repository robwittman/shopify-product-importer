<?php

use Phinx\Migration\AbstractMigration;

class CreateTemplatesTable extends AbstractMigration
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
        $table = $this->table('templates');
        $table
            ->addColumn('handle', 'string', array('limit' => 45))
            ->addColumn('name', 'string', array('limit' => 45))
            ->addColumn('description', 'text', array('null' => true, 'default' => null))
            ->addColumn('vendor', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('product_type', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('enabled', 'boolean', array('default' => true))
            ->addColumn('sku_template', 'text', array('null' => true, 'default' => null))
            ->addcolumn('tags', 'string', array('limit' => 245, 'default' => null, 'null' => true))
            ->create();
    }
}
