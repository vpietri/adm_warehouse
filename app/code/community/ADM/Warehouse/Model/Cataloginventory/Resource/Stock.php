<?php
class ADM_Warehouse_Model_CatalogInventory_Resource_Stock extends Mage_CatalogInventory_Model_Resource_Stock
{


    /**
     * Lock product items
     *
     * @param Mage_CatalogInventory_Model_Stock $stock
     * @param int|array $productIds
     * @return Mage_CatalogInventory_Model_Resource_Stock
     */
    public function lockProductItems($stock, $productIds)
    {
        $itemTable = $this->getTable('cataloginventory/stock_item');
        $select = $this->_getWriteAdapter()->select()
        ->from($itemTable)
        ->where('stock_id in IN(?)', $stock->getStockIds())
        ->where('product_id IN(?)', $productIds)
        ->forUpdate(true);
        /**
         * We use write adapter for resolving problems with replication
         */
        $this->_getWriteAdapter()->query($select);
        return $this;
    }

    /**
     * Get stock items data for requested products
     * called in ADM_Warehouse_Model_Cataloginventory_Stock::registerProductsSale
     *
     * @param Mage_CatalogInventory_Model_Stock $stock
     * @param array $productIds
     * @param bool $lockRows
     * @return array
     */
    public function getProductsStock($stock, $productIds, $lockRows = false)
    {
        if (empty($productIds)) {
            return array();
        }
        $itemTable = $this->getTable('cataloginventory/stock_item');
        $productTable = $this->getTable('catalog/product');
        $select = $this->_getWriteAdapter()->select()
        ->from(array('si' => $itemTable))
        ->join(array('p' => $productTable), 'p.entity_id=si.product_id', array('type_id'))
        //TODO: Set stock_id filter
        //->where('stock_id=?', $stock->getId())
        ->where('product_id IN(?)', $productIds)
        ->forUpdate($lockRows);
        $productStocks = $this->_getWriteAdapter()->fetchAll($select);

        $productStocksAgg=array();
        foreach($productStocks as $stokItem) {
            if(empty($productStocksAgg[$stokItem['product_id']])) {
                $productStocksAgg[$stokItem['product_id']] = $stokItem;
            } else {
                $productStocksAgg[$stokItem['product_id']]['qty'] += $stokItem['qty'];
            }
        }

        return $productStocksAgg;
    }


    /**
     * Correct particular stock products qty based on operator
     *
     * @param Mage_CatalogInventory_Model_Stock $stock
     * @param array $productQtys
     * @param string $operator +/-
     * @return Mage_CatalogInventory_Model_Resource_Stock
     */
    public function correctItemsByWarehouseQty($warehousesQtys, $operator = '-', $itemKey)
    {
        if (empty($warehousesQtys)) {
            return $this;
        }


        $adapter = $this->_getWriteAdapter();
        $conditions = array();
        $linkData=array();
        $productIds=array();
        foreach ($warehousesQtys as $stockId => $qtysByItems) {
            foreach ($qtysByItems as $itemQty) {

                $productId = $itemQty['product_id'];
                $qty = $itemQty['qty'];
                $productIds[$productId]=$productId;

                $case = $adapter->quoteInto('product_id=?', $productId) .
                        ' AND ' .
                        $adapter->quoteInto('stock_id=?', $stockId);
                $result = $adapter->quoteInto("qty{$operator}?", $qty);
                $conditions[$case] = $result;
                $linkData[] = array(
                                    $itemKey => $itemQty['item_id'],
                                    'stock_id' => $stockId,
                                    'product_id' => $productId,
                                    'qty' => $qty
                                    );
            }
        }

        //TAKE CARE: With more than 255 CASE WHEN we can have an issue
        $value = $adapter->getCaseSql('', $conditions, 'qty');
        $where = array(
                'product_id IN (?)' => $productIds,
                'stock_id IN (?)' => array_keys($warehousesQtys)
        );

        $adapter->beginTransaction();
        $adapter->update($this->getTable('cataloginventory/stock_item'), array('qty' => $value), $where);

        try {
            if (empty($itemKey)) {
                throw new Exception('Link key undefined.');
            }

            if ($operator=='-') {
                $tableItemLink = $this->getTable('adm_warehouse/stock_item_quote');
            } else {
                //TODO: Store data in this table is not mandatory can be set in configuration
                $tableItemLink = $this->getTable('adm_warehouse/stock_item_creditmemo');
            }

            if(!empty($tableItemLink)) {
                $bulkInsert = array();
                foreach($linkData as $updateLink) {
                    $bulkInsert[] = '(\'' . implode('\',\'', $updateLink) . '\')';
                }
                $insert = 'INSERT INTO '. $tableItemLink . ' (' . $itemKey . ', stock_id, product_id, qty) VALUES ' . implode(',',$bulkInsert);
                $adapter->query($insert);
            }

        } catch (Exception $e) {
           Mage::logException($e);
        }

        $adapter->commit();
        return $this;
    }

    /**
     * Get information on linked items
     *
     * @param array $itemIds
     * @param string $type
     * @throws Exception
     *
     * @return array
     */
    public function getLinkedItemsWarehouseQtys($itemIds, $type)
    {
        if (in_array($type, array('quote','creditmemo'))) {
            $select = $this->_getReadAdapter()->select()
            ->from(array('sfoi'=>$this->getTable('sales/order_item')), array())
            ->join(array('acsiq' => $this->getTable('adm_warehouse/stock_item_quote')), 'acsiq.quote_item_id= sfoi.quote_item_id', '*');

            if($type=='quote') {
                $select->where('sfoi.quote_item_id IN (?)', $itemIds);
            } else {
                $select->join(array('sfci' => $this->getTable('sales/creditmemo_item')), 'sfci.order_item_id=sfoi.item_id', array());
                $select->where('sfci.entity_id IN (?)', $itemIds);
            }

            return $this->_getWriteAdapter()->fetchAll($select);
        } else {
            throw new Exception('Unrecognized type for linked stock table.');
        }
    }

    /**
     * Perform operations after object load
     *
     * @param Mage_Core_Model_Abstract $object
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $websites = $this->lookupWebsiteIds($object->getId());

            $object->setData('website_id', $websites);

        }

        return parent::_afterLoad($object);
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupWebsiteIds($stockId)
    {
        $adapter = $this->_getReadAdapter();

        $select  = $adapter->select()
        ->from($this->getTable('adm_warehouse/stock_website'), 'website_id')
        ->where('stock_id = ?',(int)$stockId);

        return $adapter->fetchCol($select);
    }

    /**
     * Perform operations after object save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $oldWebsites = $this->lookupWebsiteIds($object->getId());
        $newWebsites = (array)$object->getWebsites();

        if(in_array(0, $newWebsites)) {
            $newWebsites = array(0);
        }

        $table  = $this->getTable('adm_warehouse/stock_website');
        $insert = array_diff($newWebsites, $oldWebsites);
        $delete = array_diff($oldWebsites, $newWebsites);

        if ($delete) {
            $where = array(
                    'stock_id = ?'     => (int) $object->getId(),
                    'website_id IN (?)' => $delete
            );

            $this->_getWriteAdapter()->delete($table, $where);
        }

        if ($insert) {
            $data = array();

            foreach ($insert as $websiteId) {
                $data[] = array(
                        'stock_id'  => (int) $object->getId(),
                        'website_id' => (int) $websiteId
                );
            }

            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }

        return parent::_afterSave($object);
    }

    /**
     * Set items out of stock basing on their quantities and config settings
     *
     */
    public function updateSetOutOfStock()
    {
        $this->_initConfig();
        $adapter = $this->_getWriteAdapter();
        $values  = array(
            'is_in_stock'                  => 0,
            'stock_status_changed_auto'    => 1
        );

        $select = $adapter->select()
            ->from($this->getTable('catalog/product'), 'entity_id')
            ->where('type_id IN(?)', $this->_configTypeIds);

        $where = sprintf('is_in_stock = 1'
            . ' AND ((use_config_manage_stock = 1 AND 1 = %2$d) OR (use_config_manage_stock = 0 AND manage_stock = 1))'
            . ' AND ((use_config_backorders = 1 AND %3$d = %4$d) OR (use_config_backorders = 0 AND backorders = %3$d))'
            . ' AND ((use_config_min_qty = 1 AND qty <= %5$d) OR (use_config_min_qty = 0 AND qty <= min_qty))'
            . ' AND product_id IN (%6$s)',
            'DUMMY_VALUE_NOT_USED',
            $this->_isConfigManageStock,
            Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
            $this->_isConfigBackorders,
            $this->_configMinQty,
            $select->assemble()
        );

        $adapter->update($this->getTable('cataloginventory/stock_item'), $values, $where);
    }

    /**
     * Set items in stock basing on their quantities and config settings
     *
     */
    public function updateSetInStock()
    {
        $this->_initConfig();
        $adapter = $this->_getWriteAdapter();
        $values  = array(
                'is_in_stock'   => 1,
        );

        $select = $adapter->select()
        ->from($this->getTable('catalog/product'), 'entity_id')
        ->where('type_id IN(?)', $this->_configTypeIds);

        $where = sprintf('is_in_stock = 0'
                . ' AND stock_status_changed_auto = 1'
                . ' AND ((use_config_manage_stock = 1 AND 1 = %2$d) OR (use_config_manage_stock = 0 AND manage_stock = 1))'
                . ' AND ((use_config_min_qty = 1 AND qty > %3$d) OR (use_config_min_qty = 0 AND qty > min_qty))'
                . ' AND product_id IN (%4$s)',
                'DUMMY_VALUE_NOT_USED',
                $this->_isConfigManageStock,
                $this->_configMinQty,
                $select->assemble()
        );

        $adapter->update($this->getTable('cataloginventory/stock_item'), $values, $where);
    }

    /**
     * Update items low stock date basing on their quantities and config settings
     *
     */
    public function updateLowStockDate()
    {
        $this->_initConfig();

        $adapter = $this->_getWriteAdapter();
        $condition = $adapter->quoteInto('(use_config_notify_stock_qty = 1 AND qty < ?)',
                $this->_configNotifyStockQty) . ' OR (use_config_notify_stock_qty = 0 AND qty < notify_stock_qty)';
        $currentDbTime = $adapter->quoteInto('?', $this->formatDate(true));
        $conditionalDate = $adapter->getCheckSql($condition, $currentDbTime, 'NULL');

        $value  = array(
                'low_stock_date' => new Zend_Db_Expr($conditionalDate),
        );

        $select = $adapter->select()
        ->from($this->getTable('catalog/product'), 'entity_id')
        ->where('type_id IN(?)', $this->_configTypeIds);

        $where = sprintf('((use_config_manage_stock = 1 AND 1 = %2$d) OR (use_config_manage_stock = 0 AND manage_stock = 1))'
                . ' AND product_id IN (%3$s)',
                'DUMMY_VALUE_NOT_USED',
                $this->_isConfigManageStock,
                $select->assemble()
        );

        $adapter->update($this->getTable('cataloginventory/stock_item'), $value, $where);
    }
}