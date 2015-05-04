<?php
class ADM_Warehouse_Model_Resource_Sales_Item_Creditmemo_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('adm_warehouse/sales_item_creditmemo');
    }

}