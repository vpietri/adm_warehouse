<?php

class ADM_Warehouse_Model_Cataloginventory_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    /**
     * Retrieve stock identifier
     *
     * @todo multi stock
     * @return int
     */
    public function getStockId()
    {
        if(!empty($this->_data['stock_id'])) {
            return $this->_data['stock_id'];
        } else {
            return parent::getStockId();
        }
    }
}