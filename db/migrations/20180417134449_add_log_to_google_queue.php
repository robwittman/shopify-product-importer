<?php

use Phinx\Migration\AbstractMigration;

class AddLogToGoogleQueue extends AbstractMigration
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
        $googleQueue = $this->table('google_queue');
        $googleQueue
            ->addColumn('print_type', 'string', array('limit' => 45))
            ->addColumn('product_fulfiller_code', 'string', array('limit' => 45))
            ->addColumn('shop_id', 'integer')
            ->addColumn('product_name', 'string', array('limit' => 145))
            ->addColumn('shopify_product_admin_url', 'string', array('limit' => 245))
            ->addColumn('front_print_file_url', 'string', array('limit' => 245))
            ->addColumn('back_print_file_url', 'string', array('limit' => 245, 'null' => true, 'default' => null))
            ->addColumn('garment_name', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('garment_color', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('product_sku', 'string', array('limit' => 145, 'null' => true, 'default' => null))
            ->addColumn('integration_status', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('date', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('error', 'text', array('null' => true, 'default' => null))
            ->addColumn('status', 'string', array('limit' => 45, 'default' => 'pending'));
        $googleQueue->create();
    }
}
