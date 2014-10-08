<?php
class ADM_Warehouse_Model_CatalogInventory_Resource_Stock extends Mage_CatalogInventory_Model_Resource_Stock
{
    /**
     * Correct particular stock products qty based on operator
     *
     * @param Mage_CatalogInventory_Model_Stock $stock
     * @param array $productQtys
     * @param string $operator +/-
     * @return Mage_CatalogInventory_Model_Resource_Stock
     */
    public function correctItemsByWarehouseQty($warehousesQtys, $operator = '-')
    {
        if (empty($warehousesQtys) or empty($warehousesQtys['products']) or empty($warehousesQtys['warehouses'])) {
            return $this;
        }

        $adapter = $this->_getWriteAdapter();
        $conditions = array();
        foreach ($warehousesQtys['warehouses'] as $stockId => $productIds) {
            foreach ($productIds as $productId=>$qty) {
                $case = $adapter->quoteInto('product_id=?', $productId) .
                        ' AND ' .
                        $adapter->quoteInto('stock_id=?', $stockId);
                $result = $adapter->quoteInto("qty{$operator}?", $qty);
                $conditions[$case] = $result;
            }
        }

        //With more than 255 CASE WHEN there can have a issue
        $value = $adapter->getCaseSql('', $conditions, 'qty');
        $where = array(
                'product_id IN (?)' => array_keys($warehousesQtys['products']),
                'stock_id IN (?)' => array_keys($warehousesQtys['warehouses'])
        );

        $adapter->beginTransaction();
        $adapter->update($this->getTable('cataloginventory/stock_item'), array('qty' => $value), $where);
        $adapter->commit();

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
}