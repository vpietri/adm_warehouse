<?php

class ADM_Warehouse_Block_Adminhtml_Catalog_Product_Edit_Tab_Inventory extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Inventory
{

    protected $_item_by_warehouses;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('adm/warehouse/catalog/product/tab/inventory.phtml');
    }


    public function getWarehouses()
    {

        $this->getStockItem();

        return Mage::getModel('cataloginventory/stock')->getCollection();
    }

    /**
     * Initialize $this->_item_by_warehouses with quantities for a given product
     *
     * @return Mage_Cataloginventory_Model_Stock
     */
    public function getQtysByWarehouse()
    {
        if (!$this->_item_by_warehouses instanceof ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection)
        {
            $warehouses = Mage::getModel('cataloginventory/stock')->getCollection();
            $stockItem = $this->getStockItem();

            foreach ($warehouses as $warehouse)
            {
                if (!$stockItem)
                {
                    continue;
                }

                foreach ($stockItem->getStockDetails() as $stockDetail)
                {
                    if ($stockDetail['stock_id'] == $warehouse->getId())
                    {
                        $warehouse->setQty($stockDetail['qty']);
                        $warehouse->setItemId($stockDetail['item_id']);
                    }
                }
            }

            $this->_item_by_warehouses = $warehouses;
        }

        return $this->_item_by_warehouses;
    }

}
