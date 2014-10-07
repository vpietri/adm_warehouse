<?php
class ADM_Warehouse_Block_Adminhtml_Warehouse_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('warehouse_grid');
        $this->setDefaultSort('sort_order');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    /**
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cataloginventory/stock')->getCollection();

        $this->setCollection($collection);
        $collection->setFirstStoreFlag(true);

        return parent::_prepareCollection();
    }

    /**
     * Prepares the columns for the grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('stock_name', array(
                'header' => $this->__('Stock Name'),
                'index' => 'stock_name'));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                    'header'        => $this->__('Store View'),
                    'index'         => 'store_id',
                    'type'          => 'store',
                    'store_all'     => true,
                    'store_view'    => true,
                    'sortable'      => false,
                    'filter_condition_callback'
                    => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('sort_order', array(
                'header' => $this->__('Sort'),
                'filter'  => false,
                'width'  => '10%',
                'align' => 'right',
                'index' => 'sort_order'));

        $this->addColumn('is_active', array(
                'header'    => $this->__('Status'),
                'index'     => 'is_active',
                'width'  => '10%',
                'type'      => 'options',
                'options'   => Mage::getSingleton('cataloginventory/stock')->getAvailableStatuses()
        ));

        return parent::_prepareColumns();
    }


    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
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
        $url = $this->getUrl('*/*/edit', array('stock_id' => $row->getStockId()));
        return $url;
    }
}
