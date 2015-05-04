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
            $totalQty = 0;
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


    /**
     * Overwrite @method _addItemToQtyArray
     *
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
    protected function _addItemToQtyArray($quoteItem, &$items)
    {
        $productId = $quoteItem->getProductId();
        if (!$productId)
            return;
        if (isset($items[$productId])) {
            $items[$productId]['qty'] += $quoteItem->getTotalQty();
        } else {
            $stockItem = null;
            if ($quoteItem->getProduct()) {
                $stockItem = $quoteItem->getProduct()->getStockItem();
            }
            $items[$productId] = array(
                    'item' => $stockItem,
                    'qty'  => $quoteItem->getTotalQty(),
                    //Wee need to get quote item_id to manage stock history
                    'quote_item_id'=>$quoteItem->getId(),
                    'creditmemo_item_id'=>null,
            );
        }
    }

    /**
     * Return creditmemo items qty to stock
     *
     * @param Varien_Event_Observer $observer
     */
    public function refundOrderInventory($observer)
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $items = array();
        foreach ($creditmemo->getAllItems() as $item) {
            /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */
            $return = false;
            if ($item->hasBackToStock()) {
                if ($item->getBackToStock() && $item->getQty()) {
                    $return = true;
                }
            } elseif (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                $return = true;
            }
            if ($return) {
                $parentOrderId = $item->getOrderItem()->getParentItemId();
                /* @var $parentItem Mage_Sales_Model_Order_Creditmemo_Item */
                $parentItem = $parentOrderId ? $creditmemo->getItemByOrderId($parentOrderId) : false;
                $qty = $parentItem ? ($parentItem->getQty() * $item->getQty()) : $item->getQty();
                if (isset($items[$item->getProductId()])) {
                    $items[$item->getProductId()]['qty'] += $qty;
                } else {
                    $items[$item->getProductId()] = array(
                            'qty'  => $qty,
                            'item' => null,
                            'quote_item_id'=>null,
                            'creditmemo_item_id'=>$item->getId(),
                    );
                }
            }
        }

        $items = $this->_addWarehouseInfo($items, 'creditmemo');

        Mage::getSingleton('cataloginventory/stock')->revertProductsSale($items);
    }


    /**
     * Revert quote items inventory data (cover not success order place case)
     * @param $observer
     */
    public function revertQuoteInventory($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $items = $this->_getProductsQty($quote->getAllItems());

        $items = $this->_addWarehouseInfo($items, 'quote');

        Mage::getSingleton('cataloginventory/stock')->revertProductsSale($items);

        // Clear flag, so if order placement retried again with success - it will be processed
        $quote->setInventoryProcessed(false);
    }


    /**
     * In case order is passed we need to stock back in the correct warehouse
     *
     * @param array $items
     * @param unknown_type $type
     */
    protected function _addWarehouseInfo(array $items, $type)
    {
        $itemKey = $type . '_item_id';
        $itemIds = array();
        foreach ($items as $productId=>$item) {
            $itemIds[] = $item[$itemKey];
            $items[$productId]['warehouses']=array();
        }

        $linkedItems = Mage::getResourceModel('cataloginventory/stock')->getLinkedItemsWarehouseQtys($itemIds, $type);
        foreach ($linkedItems as $link) {
            if(isset($items[$link['product_id']])) {
                $items[$link['product_id']]['warehouses'][$link['stock_id']] = $link['qty'];
            }
        }

        return $items;
    }


    /**
     * Subtract quote items qtys from stock items related with quote items products.
     *
     * Used before order placing to make order save/place transaction smaller
     * Also called after every successful order placement to ensure subtraction of inventory
     *
     * @param Varien_Event_Observer $observer
     */
    public function subtractQuoteInventory(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        // Maybe we've already processed this quote in some event during order placement
        // e.g. call in event 'sales_model_service_quote_submit_before' and later in 'checkout_submit_all_after'
        if ($quote->getInventoryProcessed()) {
            return;
        }
        $items = $this->_getProductsQty($quote->getAllItems());

        //If we are in the case of an order modification
        if ($order->getData('relation_parent_id')) {
            $parentOrderId = $order->getData('relation_parent_id');
            $items = $this->_addWarehouseOrderHistory($items, $order->getData('relation_parent_id'));
        }

        /**
         * Remember items
         */
        $this->_itemsForReindex = Mage::getSingleton('cataloginventory/stock')->registerProductsSale($items);

        $quote->setInventoryProcessed(true);
        return $this;
    }


    /**
     *
     *
     * @param array $items
     *
     * @return array
     */
    protected function _addWarehouseOrderHistory(array $items, $orderId)
    {

        $linkedItems = Mage::getModel('adm_warehouse/sales_item_quote')->getCollection()
                                                                 ->addOrderIdFilter($orderId);

        foreach ($linkedItems as $link) {
            if(isset($items[$link->getProductId()]) and !empty($items[$link->getProductId()]['item'])) {
                $stockItem = $items[$link->getProductId()]['item'];
                $stockDetailsWithOrder = array();
                foreach ($stockItem->getStockDetails() as $stockDetail) {
                    //When we edit an order we already have stock warehouse information
                    if ($stockDetail['stock_id'] == $link->getStockId()) {
                        $stockDetail['qty'] += $link->getQty();
                        $stockDetail['qty_ordered'] = $link->getQty();
                    }
                    $stockDetailsWithOrder[] = $stockDetail;
                }
                $stockItem->setStockDetails($stockDetailsWithOrder);
            }
        }

        return $items;
    }





    /**
     * Cancel order item
     *
     * @param   Varien_Event_Observer $observer
     * @return  Mage_CatalogInventory_Model_Observer
     */
    public function cancelOrderItem($observer)
    {
        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

        $productId = $item->getProductId();
        if ($item->getId() && $productId && empty($children) && $qty) {
            Mage::getSingleton('cataloginventory/stock')->backItemQtyToWarehouse($productId, $qty, $item);
        }

        return $this;
    }

}