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


    /**
     * Check if item should be in stock or out of stock based on $qty param of existing item qty
     *
     * @param float|null $qty
     * @return bool true - item in stock | false - item out of stock
     */
    public function verifyStock($qty = null)
    {
        if ($qty === null) {
            $qty = ($this->hasTotalQty()) ? $this->getTotalQty() : $this->getQty();
        }
        if ($this->getBackorders() == Mage_CatalogInventory_Model_Stock::BACKORDERS_NO && $qty <= $this->getMinQty()) {
            return false;
        }
        return true;
    }
}