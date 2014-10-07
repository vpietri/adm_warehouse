<?php

class ADM_Warehouse_Adminhtml_StockController extends Mage_Adminhtml_Controller_Action
{
    /**
     * _initAction()
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_title($this->__('Catalog'))
        ->_title($this->__('Warehouse'))
        ->_title($this->__('Manage Stock'));

        $this->loadLayout();

        $this->_setActiveMenu('catalog/warehouse');

        return $this;
    }


    /**
     * display default layout
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }


    public function updateAction()
    {
        $qtyByItemId= $this->getRequest()->getParam('qty');
        $originalQtyByItemId= $this->getRequest()->getParam('original_qty');

        if(!empty($qtyByItemId) and !empty($originalQtyByItemId)) {
            $item = Mage::getModel('cataloginventory/stock_item');

            foreach($qtyByItemId as $itemId=>$qty) {
                if(!isset($originalQtyByItemId[$itemId])) {
                    continue;
                }
                $item->load($itemId);
                $item->setQtyCorrection($qty-$originalQtyByItemId[$itemId]);
                $item->save();
            }
        }
    }

}
