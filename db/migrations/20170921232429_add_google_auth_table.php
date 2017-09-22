<?php

use Phinx\Migration\AbstractMigration;

class AddGoogleAuthTable extends AbstractMigration
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
        $shops = $this->table('shops');
        $shops->addColumn('google_access_token', 'string', array('limit' => 255, 'default' => null, 'null' => true))
            ->addColumn('google_sheet_slug', 'string', array('limit' => 255, 'default' => null, 'null' => true))
            ->addColumn('google_expires_in', 'integer', array('null' => true, 'default' => null))
            ->addColumn('google_refresh_token', 'string', array('limit' => 255, 'null' => true, 'default' => null))
            ->addColumn('google_created_at', 'integer', array('null' => true, 'default' => null))
            ->update();
    }
}
