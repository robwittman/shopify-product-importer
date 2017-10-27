<?php

use Phinx\Migration\AbstractMigration;

class AddFileAndTemplateToQueue extends AbstractMigration
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
            ->addColumn('file_name', 'string', array('limit' => 245, 'null' => true, 'default' => null))
            ->addColumn('template', 'string', array('limit' => 245, 'null' => true, 'default' => null))
            ->addColumn('log_to_google', 'integer', array('default' => 0))
            ->update();
    }
}
