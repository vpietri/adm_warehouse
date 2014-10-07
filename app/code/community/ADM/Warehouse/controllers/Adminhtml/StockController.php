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

    /**
     * Action for creating new stock entries.
     *
     * @return void
     */
    public function newAction()
    {
        $this->_initAction();

        // Get Next AutoInc id
        $model = Mage::getModel('cataloginventory/stock');

        $model->setData('stock_id', $id);
        $this->_title($this->__('New Stock'));

        $this->_initAction()->_addBreadcrumb(
            $this->__('New Stock'),
            $this->__('New Stock')
        )->renderLayout();
    }
    /**
     * Edit stock entry controller
     *
     * @return void
     */
    public function editAction()
    {
        $this->_initAction();

        // Get id if available
        $id    = $this->getRequest()->getParam('item_id');
        $model = Mage::getModel('cataloginventory/stock_item');

        if ($id) {
            // Load record
            $model->load($id);

            // Check if record is loaded
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This stock item no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
            Mage::register('current_stock_item', $model);
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Stock'));

        $this->_initAction()->_addBreadcrumb(
            $id ? $this->__('Edit Stock') : $this->__('New Stock'),
            $id ? $this->__('Edit Stock') : $this->__('New Stock')
        )->renderLayout();
    }

    /**
     * saveAction
     *
     * @return void
     */
    public function saveAction()
    {

        $stockId      = $this->getRequest()->getParam('stock_id');
        $redirectBack = $this->getRequest()->getParam('back', false);
        if ($postData = $this->getRequest()->getPost()) {
            /** @var $stock cataloginventory_Model_Stock */
            $stock = Mage::getModel('cataloginventory/stock');
            $stock->setData($postData);
            //we are in editing mode.
            if ($stockId) {
                $stock->setId($stockId);
            }
            $stock->save();
            try {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The stock has been saved.'));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::logException($e);
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->__('An error occurred while saving this stock.')
                );
                Mage::logException($e);
            }
            if ($redirectBack) {
                $this->_redirect(
                    '*/*/edit', array('stock_id' => $stockId, '_current' => true)
                );
            } else {
                $this->_redirect('*/*/');
            }
        }
    }

    /**
     * deleteAction
     *
     * @return void
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('stock_id')
        ) {
            $stock = Mage::getModel('cataloginventory/stock')->load($id);
            try {
                $stock->delete();
                $this->_getSession()->addSuccess($this->__('The stock has been deleted.'));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

}
