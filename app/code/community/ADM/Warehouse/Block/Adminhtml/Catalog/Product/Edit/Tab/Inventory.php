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


    public function getQtysByWarehouse()
    {
        if(is_null($this->_item_by_warehouses)) {
            $warehouses = Mage::getModel('cataloginventory/stock')->getCollection();
            $qtyByStock = $this->getStockItem()->getStockDetails();

            foreach($warehouses as $warehouse) {
                foreach($qtyByStock as $stockDetail) {
                    if($stockDetail['stock_id']==$warehouse->getId()) {
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
