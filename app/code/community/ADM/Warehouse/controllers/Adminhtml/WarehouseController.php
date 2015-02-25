<?php
class ADM_Warehouse_Adminhtml_WarehouseController extends Mage_Adminhtml_Controller_Action
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
        ->_title($this->__('Manage Warehouses'));

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
     * Create new warehouse
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * display grid
     */
    public function editAction()
    {
        $this->_initAction();

        // Get id if available
        $id    = $this->getRequest()->getParam('stock_id');
        $model = Mage::getModel('cataloginventory/stock');

        if ($id) {
            // Load record
            $model->load($id);

            // Check if record is loaded
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This stock no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        Mage::register('current_stock', $model);

        $this->_title($model->getId() ? $model->getName() : $this->__('New Stock'));

        $this->_initAction()->_addBreadcrumb(
            $id ? $this->__('Edit Stock') : $this->__('New Stock'),
            $id ? $this->__('Edit Stock') : $this->__('New Stock')
        )->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {

            $id = $this->getRequest()->getParam('stock_id');
            $model = Mage::getModel('cataloginventory/stock')->load($id);
            if (!$model->getId() && $id) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This stock no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }

            // init model and set data
            $model->setData($data);

            // try to save it
            try {
                // save the data
                $model->save();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The stock has been saved.'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                // go to grid
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // save data in session
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('stock_id' => $this->getRequest()->getParam('stock_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        // check if we know what should be deleted
        if ($id = $this->getRequest()->getParam('stock_id')) {
            try {
                // init model and delete
                $model = Mage::getModel('cataloginventory/stock');
                $model->load($id);
                $model->delete();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__('The stock has been deleted.'));
                // go to grid
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('*/*/edit', array('stock_id' => $id));
                return;
            }
        }
        // display error message
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('Unable to find a stock to delete.'));
        // go to grid
        $this->_redirect('*/*/');
    }
}
