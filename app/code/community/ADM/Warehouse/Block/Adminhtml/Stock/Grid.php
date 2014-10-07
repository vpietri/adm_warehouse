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
        $this->setDefaultSort('item_id');
        $this->setDefaultDir('ASC');
    }

    /**
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cataloginventory/stock_item')->getCollection();
        $collection->getSelect()->columns('sku', 'cp_table');
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
                'renderer'=> 'ADM_Warehouse_Block_Adminhtml_Widget_Grid_Column_Renderer_Integer',
                'align' => 'right',
                'index' => 'qty'));

        $this->addColumn('is_in_stock', array(
                'header' => $this->__('Is In Stock'),
                'type'  => 'checkbox',
                'align' => 'center',
                'index' => 'is_in_stock',
                'width' => '50px',
                'value' => '1'
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
        $url = $this->getUrl('*/*/edit', array('item_id' => $row->getStockId()));
        return $url;
    }
}
