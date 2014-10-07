<?php

class ADM_Warehouse_Block_Adminhtml_Stock extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Class Constructor
     *
     */
    public function __construct()
    {
        $this->_blockGroup = 'adm_warehouse';
        $this->_controller = 'adminhtml_stock';
        $this->_headerText = $this->__('Manage Stocks');
        parent::__construct();

        $this->removeButton('add');
    }
}
