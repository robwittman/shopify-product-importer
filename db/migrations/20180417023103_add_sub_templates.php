<?php

use Phinx\Migration\AbstractMigration;

class AddSubTemplates extends AbstractMigration
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
        $subTemplates = $this->table('sub_templates');
        $subTemplates
            ->addColumn('name', 'string', array('limit' => 145))
            ->addColumn('value', 'string', array('limit' => 145))
            ->addColumn('enabled', 'boolean', array('default' => true))
            ->addColumn('template_id', 'integer')
            ->addColumn('default', 'integer', array('default' => 0))
            ->addForeignKey('template_id', 'templates', 'id', array('delete' => 'CASCADE', 'update' => 'NO_ACTION'))
            ->addIndex(array('value', 'template_id'), array('unique' => true))
            ->create();
    }
}
