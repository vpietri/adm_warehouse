<?php
class ADM_Warehouse_Model_Resource_Sales_Item_Quote_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('adm_warehouse/sales_item_quote');
    }

    public function addOrderIdFilter($order_id)
    {

        $this->getSelect()->join(array('sfqi'=>$this->getTable('sales/quote_item')), 'main_table.quote_item_id=sfqi.item_id', array())
                          ->join(array('sfo'=>$this->getTable('sales/order')), 'sfo.quote_id=sfqi.quote_id', array())
                          ->where('sfo.entity_id=?',$order_id);

        return $this;
    }

}