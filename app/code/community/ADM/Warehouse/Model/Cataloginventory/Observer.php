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

//             echo '<pre>';
//             var_dump($itemIds, $originalQty, $stockData['qty_by_warehouse']);
//             exit;

            foreach ($stockData['qty_by_warehouse'] as $stockId=>$qty) {

                $stockDataChanged = $stockData;
                $stockDataChanged['qty'] = $qty;
                $stockDataChanged['original_inventory_qty'] = $originalQty[$stockId];
                $product->setStockData($stockDataChanged);

//                 $product->setData('stock_data/original_inventory_qty', $originalQty[$stockId]);
//                 $product->setData('stock_data/qty', $qty);
                $item->setStockId($stockId);

                $this->_prepareItemForSave($item, $product);
                if (isset($itemIds[$stockId])) {
                    $item->setId($itemIds[$stockId]);
                }
                $item->save();
            }
        } else {
            //Obsolete should not be used
            $this->_prepareItemForSave($item, $product);
            $item->save();
        }
//         exit;


        return $this;
    }



    /**
     * Adds stock item qty to $items (creates new entry or increments existing one)
     * $items is array with following structure:
     * array(
     *  $productId  => array(
     *      'qty'   => $qty,
     *      'item'  => $stockItems|null
     *  )
     * )
     *
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @param array &$items
     */
//     protected function _addItemToQtyArray($quoteItem, &$items)
//     {
//         $productId = $quoteItem->getProductId();
//         if (!$productId)
//             return;

//         //In case that warehouses are specified from cart
//         $options = $quoteItem->getBuyRequest()->getOptions();
//         if(!empty($options['warehouses'])) {
//             $items[$productId]['warehouses'] = $options['warehouses'];
//         } else {

//         }


//         if (isset($items[$productId])) {
//             $items[$productId]['qty'] += $quoteItem->getTotalQty();
//         } else {
//             $stockItem = null;
//             if ($quoteItem->getProduct()) {
//                 $stockItem = $quoteItem->getProduct()->getStockItem();
//             }
//             $items[$productId] = array(
//                     'item' => $stockItem,
//                     'warehouses' => array(),
//                     'qty'  => $quoteItem->getTotalQty()
//             );

//         }
//     }
}