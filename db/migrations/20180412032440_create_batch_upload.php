<?php

use Phinx\Migration\AbstractMigration;

class CreateBatchUpload extends AbstractMigration
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
        $table = $this->table('batches');
        $table
            ->addColumn('title', 'string', array('limit' => 145))
            ->addColumn('product_type', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('vendor', 'string', array('limit' => 45, 'null' => true, 'default' => null))
            ->addColumn('file_path', 'string', array('limit' => 145))
            ->addColumn('tags', 'string', array('limit' => 145))
            ->addColumn('created_at', 'timestamp', array('default' => 'CURRENT_TIMESTAMP'))
            ->addColumn('file_name', 'string', array('limit' => 145))
            ->addColumn('status', 'string', array('limit' => 15, 'default' => 'pending'))
            ->addColumn('post', 'text')
            ->addColumn('shop_id', 'integer', array('limit' => 11))
            ->addColumn('template', 'string', array('limit' => 45))
            ->create();
    }
}
