<?php

class ADM_Warehouse_Model_Resource_Sales_Item_Creditmemo extends ADM_Warehouse_Model_Resource_Sales_Item_Abstract
{
    /**
     * Model Initialization
     *
     */
    protected function _construct()
    {
        $this->_init('adm_warehouse/sales_item_creditmemo', 'entity_id');
    }

}