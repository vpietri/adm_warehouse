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

//         //TODO: Change product inventory tab
//         if(Mage::app()->getStore()->getCode()=='admin') {
//             $select->where('stock_id=?', Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID);
//         }

        //TODO: Set a filter to use only authorized warehouse
        $listStock = $this->_getReadAdapter()->fetchAll($select);
        if ($listStock) {
            $qty = 0;
            $stockDetails = array();
            foreach ($listStock as $data) {
                if($data['stock_id']==Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID) {
                    $item->setData($data);
                }
                if(!empty($data['qty'])) {
                    $qty+= $data['qty'];
                    //$stockDetails[$data['stock_id']] = $data['qty'];
                    $stockDetails[] = array('item_id'=>$data['item_id'], 'qty'=>$data['qty'], 'stock_id'=>$data['stock_id']);
                }
            }
            //die($qty);
            $item->setStockDetails($stockDetails);
            $item->setQty($qty);
        }

        $this->_afterLoad($item);
        return $this;
    }
}