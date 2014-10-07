<?php


class ADM_Warehouse_Model_Cataloginventory_Observer extends Mage_CatalogInventory_Model_Observer
{

    /**
     * Saving product inventory data. Product qty calculated dynamically.
     *
     * @param   Varien_Event_Observer $observer
     * @return  Mage_CatalogInventory_Model_Observer
     */
    public function saveInventoryData($observer)
    {
        $product = $observer->getEvent()->getProduct();

        if (is_null($product->getStockData())) {
            if ($product->getIsChangedWebsites() || $product->dataHasChangedFor('status')) {
                Mage::getSingleton('cataloginventory/stock_status')
                ->updateStatus($product->getId());
            }
            return $this;
        }

        $item = $product->getStockItem();
        if (!$item) {
            $item = Mage::getModel('cataloginventory/stock_item');
        }

        $stockData = $product->getStockData();

        if(!empty($stockData['qty_by_warehouse']) and !empty($stockData['original_inventory_qty_by_warehouse'])) {
            $itemIds = isset($stockData['inventory_item']) ? $stockData['inventory_item'] : array();
            $originalQty = $stockData['original_inventory_qty_by_warehouse'];

            foreach ($stockData['qty_by_warehouse'] as $stockId=>$qty) {

                $stockDataChanged = $stockData;
                $stockDataChanged['qty'] = $qty;
                $stockDataChanged['original_inventory_qty'] = $originalQty[$stockId];
                $product->setStockData($stockDataChanged);

                $item->setStockId($stockId);

                $this->_prepareItemForSave($item, $product);
                if (!empty($itemIds[$stockId])) {
                    $item->setId($itemIds[$stockId]);
                } else {
                    $item->setId(null);
                }

                $item->save();
            }
        } else {
            //Obsolete should not be used
            $this->_prepareItemForSave($item, $product);
            $item->save();
        }

        return $this;
    }

}