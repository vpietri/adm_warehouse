<?php

class ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Status extends Mage_CatalogInventory_Model_Resource_Stock_Status
{
    /**
     * Add stock status limitation to catalog product price index select object
     *
     * @param Varien_Db_Select $select
     * @param string|Zend_Db_Expr $entityField
     * @param string|Zend_Db_Expr $websiteField
     * @return Mage_CatalogInventory_Model_Resource_Stock_Status
     */
    public function prepareCatalogProductIndexSelect(Varien_Db_Select $select, $entityField, $websiteField)
    {

        $subquery = $this->getReadConnection()
        ->select()
        ->from(array('sub_ciss' => $this->getMainTable()),array('product_id', 'website_id'))
        ->columns(new Zend_Db_Expr("MAX(sub_ciss.stock_status) AS stock_status"))
        ->group(array('sub_ciss.product_id', 'sub_ciss.website_id'));

        $select->join(array('ciss'=>$subquery),"ciss.product_id = {$entityField} AND ciss.website_id = {$websiteField}",array());
        $select->where('ciss.stock_status = ?', Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK);

        return $this;
    }
}