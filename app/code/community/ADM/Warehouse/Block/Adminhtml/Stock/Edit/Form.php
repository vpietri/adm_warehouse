<?php
class ADM_Warehouse_Block_Adminhtml_Stock_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        $model = Mage::registry('current_stock_item');

        $form = new Varien_Data_Form(
                array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post')
        );

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>$this->__('General Information'), 'class' => 'fieldset-wide'));

        if ($model->getItemId()) {
            $fieldset->addField('item_id', 'hidden', array(
                    'name' => 'item_id',
            ));
        }

        $fieldset->addField('qty', 'text', array(
                'name'      => 'qty',
                'label'     => $this->__('Qty'),
                'title'     => $this->__('Qty'),
        ));


        $fieldset->addField('is_in_stock', 'select', array(
                'label'     => $this->__('Is in stock'),
                'title'     => $this->__('Status'),
                'name'      => 'is_active',
                'required'  => true,
                'options'   => array(
                        '1' => $this->__('Enabled'),
                        '0' => $this->__('Disabled'),
                ),
        ));
        if (!$model->getId()) {
            $model->setData('is_in_stock', '0');
        }

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
