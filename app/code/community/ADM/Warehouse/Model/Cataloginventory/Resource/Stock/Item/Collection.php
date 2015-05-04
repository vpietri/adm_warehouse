<?php
class ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Item_Collection extends Mage_CatalogInventory_Model_Resource_Stock_Item_Collection
{
    /**
     * Add stock filter to collection
     *
     * @param mixed $stock
     * @return Mage_CatalogInventory_Model_Resource_Stock_Item_Collection
     */
    public function addStockFilter($stock)
    {
        if(is_array($stock)) {
            if(!empty($stock)) {
                $this->addFieldToFilter('main_table.stock_id', array('in'=>$stock));
            } else {
                $this->addFieldToFilter('1', '0');
            }
        } else {
            parent::addStockFilter($stock);
        }

        return $this;
    }

    /**
     *
     * @param Mage_Sales_Model_Order_Item $item
     */
    public function addSalesOrderItemFilter(Mage_Sales_Model_Order_Item $item)
    {
        $this->getSelect()->join(array('stqt'=>$this->getTable('adm_warehouse/sales_item_quote')), 'main_table.stock_id=stqt.stock_id AND main_table.product_id=stqt.product_id', array('quote_qty'=>'stqt.qty'))
            ->where('quote_item_id=?', $item->getQuoteItemId());

        return $this;
    }

}