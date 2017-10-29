<?php

use Phinx\Migration\AbstractMigration;

class CreateCatalogColorsTable extends AbstractMigration
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
        $colors = $this->table('catalog_colors');
        $colors
            ->addColumn('catalog_id', 'string', array('null' => false))
            ->addColumn('name', 'string', array('limit' => 45, 'null' => false))
            ->addColumn('alias', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->create();
    }
}
