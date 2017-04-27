<?php

class ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Item extends Mage_CatalogInventory_Model_Resource_Stock_Item
{
    /**
     * Loading stock item data by product
     *
     * @param Mage_CatalogInventory_Model_Stock_Item $item
     * @param int $productId
     * @return Mage_CatalogInventory_Model_Resource_Stock_Item
     */
    public function loadByProductId(Mage_CatalogInventory_Model_Stock_Item $item, $productId)
    {
        $select = $this->_getLoadSelect('product_id', $productId, $item);

        $isAdmin = Mage::app()->getStore()->isAdmin();

        if(!$isAdmin) {
            $stockIds = Mage::getModel('cataloginventory/stock')->getStockIds();
            if (!empty($stockIds)) {
                $select->where('stock_id', array('in'=>$stockIds));
            }
        }

        if (!empty($stockIds) or $isAdmin) {
            $listStock = $this->_getReadAdapter()->fetchAll($select);
            if ($listStock) {
                $qty = 0;
                $stockDetails = array();
                //Init with first line found
                $item->setData(current($listStock));
                foreach ($listStock as $data) {
                    if(!empty($data['qty'])) {
                        $qty+= $data['qty'];
                        $stockDetails[] = array('item_id'=>$data['item_id'], 'qty'=>$data['qty'], 'stock_id'=>$data['stock_id']);
                    }
                }
                $item->setStockDetails($stockDetails);
                $item->setQty($qty);
                $item->setTotalQty($qty);
            }
        } else {
            $item->setQty(0);
        }

        $this->_afterLoad($item);
        return $this;
    }


    /**
     * Add join for catalog in stock field to product collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @return Mage_CatalogInventory_Model_Resource_Stock_Item
     */
    public function addCatalogInventoryToProductCollection($productCollection)
    {
        $adapter = $this->_getReadAdapter();
        $isManageStock = (int)Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
        $stockExpr = $adapter->getCheckSql('sub_cisi.use_config_manage_stock = 1', $isManageStock, 'sub_cisi.manage_stock');
        $stockExpr = $adapter->getCheckSql("({$stockExpr} = 1)", 'sub_cisi.is_in_stock', '1');

        $subSelect = $adapter->select()->from(array('sub_cisi' => $productCollection->getTable('cataloginventory/stock_item')),
                array(
                'product_id',
                'is_saleable' => new Zend_Db_Expr('MAX('.$stockExpr.')'),
                'inventory_in_stock' => new Zend_Db_Expr('MAX(is_in_stock)')
            ))->group('product_id');

        $subquery = new Zend_Db_Expr('(' . $subSelect->__toString() .')');
        $productCollection->joinTable(array('cisi' => $subquery),
                'product_id=entity_id',
                array('is_saleable','inventory_in_stock'),
                    null,
                    'left'
                );

        return $this;
    }
}