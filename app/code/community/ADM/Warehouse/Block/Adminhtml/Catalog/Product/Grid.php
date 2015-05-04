<?php

class ADM_Warehouse_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid
{

    /**
     * set collection object
     *
     * @param Varien_Data_Collection $collection
     */
    public function setCollection($collection)
    {

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $select = $collection->getSelect();
            $fromParts = $select->getPart(Zend_Db_Select::FROM);
            if (isset($fromParts['at_qty'])) {
                unset($fromParts['at_qty']);
                $select->reset(Zend_Db_Select::FROM);
                $select->setPart(Zend_Db_Select::FROM, $fromParts);


                $subquery = $collection->getConnection()
                           ->select()
                            ->from(array('at_sub_qty' => $collection->getResource()->getTable('cataloginventory/stock_item')),array('product_id'))
                            ->columns(new Zend_Db_Expr("SUM(at_sub_qty.qty) AS qty"))
                            ->group('at_sub_qty.product_id');

                $select->joinLeft(array('at_qty'=>$subquery),'at_qty.product_id=e.entity_id',array());
            }
        }

        parent::setCollection($collection);
    }
}
