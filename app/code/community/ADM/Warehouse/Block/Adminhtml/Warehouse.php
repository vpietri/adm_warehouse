<?php

class ADM_Warehouse_Block_Adminhtml_Warehouse extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Class Constructor
     *
     */
    public function __construct()
    {
        $this->_blockGroup = 'adm_warehouse';
        $this->_controller = 'adminhtml_warehouse';
        $this->_headerText = $this->__('Manage Warehouses');
        parent::__construct();
    }
}
