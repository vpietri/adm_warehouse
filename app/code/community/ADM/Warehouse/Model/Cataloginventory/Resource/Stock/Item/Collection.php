<?php
class ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Item_Collection extends Mage_CatalogInventory_Model_Resource_Stock_Item_Collection
{
    protected $_stock_filter_ids = array();

    /**
     * Add stock filter to collection
     *
     * @param mixed $stock
     * @return Mage_CatalogInventory_Model_Resource_Stock_Item_Collection
     */
    public function addStockFilter($stock)
    {
        if(is_array($stock)) {
            $this->setFlag('stock_filter', true);
            $this->_stock_filter_ids = $stock;
            $this->addFieldToFilter('main_table.stock_id', array('in'=>$stock));
        } else {
            parent::addStockFilter($stock);
        }

        return $this;
    }

    /**
     * Proces loaded collection data
     *
     * @return Varien_Data_Collection_Db
     */
    protected function _afterLoadData()
    {
        // Sort stock item by stock order
        if ($this->getFlag('stock_filter')) {
            $sortedData= array();
            foreach($this->_data as $itemData) {
                $key = array_search($itemData['stock_id'], $this->_stock_filter_ids, true);
                $sortedData[$key] = $itemData;
            }
            ksort($sortedData);
            $this->_data = $sortedData;
        }

        return $this;
    }

}