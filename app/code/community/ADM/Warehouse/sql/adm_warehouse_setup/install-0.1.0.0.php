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
 * Create table 'adm_warehouse/stock_website'
 */
$table = $installer->getConnection()
->newTable($installer->getTable('adm_warehouse/stock_website'))
->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
        'primary'   => true,
), 'Stock ID')
->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
), 'Website ID')
->addIndex($installer->getIdxName('adm_warehouse/stock_website', array('website_id')),
        array('website_id'))
->addForeignKey($installer->getFkName('adm_warehouse/stock_website', 'stock_id', 'cataloginventory/stock', 'stock_id'),
        'stock_id', $installer->getTable('cataloginventory/stock'), 'stock_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->addForeignKey($installer->getFkName('adm_warehouse/stock_website', 'website_id', 'core/website', 'website_id'),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->setComment('Stock To Website Linkage Table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'adm_warehouse/stock_item_quote'
 */
$table = $installer->getConnection()
->newTable($installer->getTable('adm_warehouse/stock_item_quote'))
->addColumn('quote_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'nullable'  => false,
        'unsigned'  => true,
), 'Quote Item ID')
->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
), 'Stock ID')
->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'unsigned'  => true,
    'nullable'  => false,
    'default'   => '0',
    ), 'Product Id')
->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable'  => false,
        'default'   => '0.0000',
), 'Qty')
->addIndex($installer->getIdxName('adm_warehouse/stock_item_quote', array('quote_item_id', 'stock_id')),array('quote_item_id', 'stock_id'))
->addForeignKey($installer->getFkName('adm_warehouse/stock_item_quote', 'quote_item_id', 'sales/quote_item', 'item_id'),
        'quote_item_id', $installer->getTable('sales/quote_item'), 'item_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->setComment('Quote Item to Stock Movement');
$installer->getConnection()->createTable($table);

/**
 * Create table 'adm_warehouse/stock_item_creditmemo'
 */
$table = $installer->getConnection()
->newTable($installer->getTable('adm_warehouse/stock_item_creditmemo'))
->addColumn('creditmemo_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'nullable'  => false,
        'unsigned'  => true,
), 'Creditmemo Item ID')
->addColumn('stock_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
), 'Stock ID')
->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
), 'Product Id')
->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable'  => false,
        'default'   => '0.0000',
), 'Qty')
->addIndex($installer->getIdxName('adm_warehouse/stock_item_creditmemo', array('creditmemo_item_id', 'stock_id')),array('creditmemo_item_id', 'stock_id'))
->addForeignKey($installer->getFkName('adm_warehouse/stock_item_creditmemo', 'creditmemo_item_id', 'sales/creditmemo_item', 'entity_id'),
        'creditmemo_item_id', $installer->getTable('sales/creditmemo_item'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
->setComment('Creditmemo Item to Stock Movement');
$installer->getConnection()->createTable($table);
