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

        if(Mage::app()->getStore()->getCode()!='admin') {
            $stockIds = Mage::getModel('cataloginventory/stock')->getStockIds();
            if (!empty($stockIds)) {
                $select->where('stock_id', array('in'=>$stockIds));
            } else {
                $select->where('1=0');
            }
        }

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

        $this->_afterLoad($item);
        return $this;
    }
}