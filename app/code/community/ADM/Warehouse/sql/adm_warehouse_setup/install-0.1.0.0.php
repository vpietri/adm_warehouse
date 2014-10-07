<?php
$installer = $this;
/* @var $installer Mage_Eav_Model_Entity_Setup */

$installer->startSetup();

$installer->getConnection()
->addColumn(
        $installer->getTable('cataloginventory/stock'),
        'stock_code',
        array(
                'TYPE' => Varien_Db_Ddl_Table::TYPE_TEXT,
                'LENGTH' => 255,
                'NULLABLE' => false,
                'DEFAULT' => '',
                'COMMENT' => 'Stock Code'
        )
);

$installer->getConnection()
->addColumn(
        $installer->getTable('cataloginventory/stock'),
        'sort_order',
        array(
                'TYPE' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'LENGTH' => 5,
                'UNSIGNED' => true,
                'NULLABLE' => false,
                'DEFAULT' => 0,
                'COMMENT' => 'Sort order'
        )
);

$installer->getConnection()
->addColumn(
    $installer->getTable('cataloginventory/stock'),
    'is_active',
    array(
            'TYPE' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'LENGTH' => 5,
            'UNSIGNED' => true,
            'NULLABLE' => false,
            'DEFAULT' => 0,
            'COMMENT' => 'Status'
    )
);

/**
 * Create table 'adm_warehouse/stock_store'
 */
$table = $installer->getConnection()
->newTable($installer->getTable('adm_warehouse/stock_store'))
->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
        'primary'   => true,
), 'Stock ID')
->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
), 'Store ID')
->addIndex($installer->getIdxName('adm_warehouse/stock_store', array('store_id')),
        array('store_id'))
->addForeignKey($installer->getFkName('adm_warehouse/stock_store', 'stock_id', 'cataloginventory/stock', 'stock_id'),
        'stock_id', $installer->getTable('cataloginventory/stock'), 'stock_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->addForeignKey($installer->getFkName('adm_warehouse/stock_store', 'store_id', 'core/store', 'store_id'),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->setComment('Stock To Store Linkage Table');
$installer->getConnection()->createTable($table);
