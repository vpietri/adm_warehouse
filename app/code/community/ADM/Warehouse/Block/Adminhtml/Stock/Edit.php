<?php

class ADM_Warehouse_Block_Adminhtml_Stock_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    public function __construct()
    {
        $this->_blockGroup  = 'adm_warehouse';
        $this->_objectId   = 'item_id';
        $this->_controller = 'adminhtml_stock';

        parent::__construct();

        if ($this->_isAllowedAction('save')) {
            $this->_updateButton('save', 'label', $this->__('Save Stock Item'));
        } else {
            $this->_removeButton('save');
        }

        if ($this->_isAllowedAction('delete')) {
            $this->_updateButton('delete', 'label', $this->__('Delete Stock Item'));
        } else {
            $this->_removeButton('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('current_stock_item')->getId()) {
            return $this->__("Edit Stock '%s'", $this->escapeHtml(Mage::registry('current_stock_item')->getSku()));
        }
        else {
            return $this->__('New Stock Item');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        //return Mage::getSingleton('admin/session')->isAllowed('cms/page/' . $action);
        return true;
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }
}
