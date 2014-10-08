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
            }
        } else {
            $item->setQty(0);
        }

        $this->_afterLoad($item);
        return $this;
    }
}