<?php
class ADM_Warehouse_Block_Adminhtml_Warehouse_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('stock_form');
        $this->setTitle($this->__('Stock Information'));
    }


    protected function _prepareForm()
    {
        $model = Mage::registry('current_stock');

        $form = new Varien_Data_Form(
                array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post')
        );

        //$form->setHtmlIdPrefix('block_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>$this->__('General Information'), 'class' => 'fieldset-wide'));

        if ($model->getStockId()) {
            $fieldset->addField('stock_id', 'hidden', array(
                    'name' => 'stock_id',
            ));
        }

        $fieldset->addField('stock_name', 'text', array(
                'name'      => 'stock_name',
                'label'     => $this->__('Stock Name'),
                'title'     => $this->__('Stock Name'),
                'required'  => true,
        ));

        $fieldset->addField('stock_code', 'text', array(
                'name'      => 'stock_code',
                'label'     => $this->__('Stock Code'),
                'title'     => $this->__('Stock Code'),
        ));


        $fieldset->addField('is_active', 'select', array(
                'label'     => $this->__('Status'),
                'title'     => $this->__('Status'),
                'name'      => 'is_active',
                'required'  => true,
                'options'   => array(
                        '1' => $this->__('Enabled'),
                        '0' => $this->__('Disabled'),
                ),
        ));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $field =$fieldset->addField('store_id', 'multiselect', array(
                    'name'      => 'stores[]',
                    'label'     => $this->__('Store View'),
                    'title'     => $this->__('Store View'),
                    'required'  => true,
                    'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        }
        else {
            $fieldset->addField('store_id', 'hidden', array(
                    'name'      => 'stores[]',
                    'value'     => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
