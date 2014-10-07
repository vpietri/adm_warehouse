<?php
class ADM_Warehouse_Block_Adminhtml_Stock_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('stock_grid');
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');

    }



    public function getMainButtonsHtml()
    {
        $html = $this->getChildHtml('update_stock');
        $html.= parent::getMainButtonsHtml();

        return $html;
    }


    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setChild('update_stock',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                        'label'     => Mage::helper('adminhtml')->__('Update'),
                        'onclick'   => 'javascript:updateStockQty(\'' .  Mage::helper('adminhtml')->getUrl('*/*/update') .'\')',
                        'class'   => 'task'
                ))
        );

        return $this;
    }

    public function getAdditionalJavaScript()
    {

        $js = <<<EndJS
updateStockQty = function(updateQtyUrl) {
    var qtyParameters={};
    $$('input.editable-qty').each(function(elem){
        if (elem.readAttribute('original_value') != elem.value) {
            var itemId = elem.readAttribute('item_id');
            qtyParameters['qty['+itemId+']']=elem.value;
            qtyParameters['original_qty['+itemId+']']=elem.readAttribute('original_value');
        }
    });

    if(Object.keys(qtyParameters).length>1) {
        new Ajax.Request(updateQtyUrl, {
            method:'post',
            parameters: qtyParameters,
            onComplete:  function(transport){
            }.bind(this),
            onSuccess:  function(){},
            onFailure: function(){}
        });
    }
}
EndJS;
        return $js;
    }

    /**
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cataloginventory/stock_item')->getCollection();
        $collection->getSelect()->columns('sku', 'cp_table');

        $compositeTypeIds     = Mage::getSingleton('catalog/product_type')->getCompositeTypes();
        $collection->getSelect()->where('cp_table.type_id not in (?)', $compositeTypeIds);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepares the columns for the grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
                'header' => $this->__('Product ID'),
                'type'  => 'number',
                'align' => 'left',
                'index' => 'product_id',
                'width' => '50px'));

        $this->addColumn('sku', array(
                'header' => $this->__('SKU'),
                'align' => 'left',
                'index' => 'sku'));

        $this->addColumn('qty', array(
                'header' => $this->__('Qty'),
                'type'  => 'number',
                'name_key'=> 'item_id',
                'inline_css'=> 'editable-qty',
                'renderer'=> 'ADM_Warehouse_Block_Adminhtml_Widget_Grid_Column_Renderer_Qty',
                'align' => 'right',
                'index' => 'qty'));

        $this->addColumn('is_in_stock', array(
                'header' => $this->__('Is In Stock'),
                'type'  => 'options',
                'align' => 'center',
                'index' => 'is_in_stock',
                'width' => '50px',
                'options' =>  Mage::getModel('adminhtml/system_config_source_yesno')->toArray(),
                ));


        $this->addColumn('stock_id', array(
                'header' => $this->__('Stock'),
                'align' => 'right',
                'index' => 'stock_id',
                'type' => 'options',
                'options' =>  Mage::getModel('cataloginventory/stock')->getCollection()->toOptionHash()
                ));

        return parent::_prepareColumns();
    }

    /**
     * Returns the row url
     *
     * @param $row Object
     *            row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        $url = $this->getUrl('adminhtml/catalog_product/edit/', array('id' => $row->getProductId()));
        return $url;
    }
}
