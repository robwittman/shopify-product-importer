<?php

use Phinx\Migration\AbstractMigration;

class UpdateSkuTemplateFields extends AbstractMigration
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
        $settings = $this->table('template_settings');
        $settings->changeColumn('sku_template', 'text', array('null' => true, 'default' => null))->update();
        $templates = $this->table('templates');
        $settings->changeColumn('sku_template', 'text', array('null' => true, 'default' => null))->update();
    }
}
