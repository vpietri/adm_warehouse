<?php

class ADM_Warehouse_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    /**
     * Create order based on checkout type. Create customer if necessary.
     *
     * @see http://magento.stackexchange.com/questions/4682/magento-multiple-order-on-one-checkout-or-order-splitting
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function saveOrder()
    {
        $quote = $this->getQuote();

        //TODO: Set config option
        $splitOnMuliWarehouse = true;

        // First build an array with the items split by vendor
        $sortedItems = array();
        foreach ($quote->getAllItems() as $item) {
            $buyRequest = $item->getBuyRequest()->getOptions();
            if(isset($buyRequest['warehouses'])) {
                $warehouses = $buyRequest['warehouses'];
            } else {
                $warehouses = array(Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID=>$item->getQty());
            }

            foreach ($warehouses as $stockId=>$stockQty) {
                $sortedItems[$stockId][] = array('item'=>$item, 'qty'=>$stockQty);
            }
        }

        //var_dump(array_keys($sortedItems));

        $quote->setWarehouseIds(implode(',',array_keys($sortedItems)));
        if(count($sortedItems)>1 and $splitOnMuliWarehouse) {
//             Mage::register('has_several_warehouses', true);
            $orders=array();
            foreach ($sortedItems as $stockId => $stockItems) {

                $itemCollection = $quote->getItemsCollection();
                // Empty quote
                foreach ($quote->getAllItems() as $item) {
                    $itemCollection->removeItemByKey($item->getId());
                }

                foreach ($stockItems as $stockItem) {
                    $item = $stockItem['item'];
                    //Unset id to add item on quote with addItem
                    //$item->setId(null);

                    if($item->getProductType()=='simple') {
                         $item->setQty($stockItem['qty']);
                         $item->setWarehouseId($stockId);
                    }

                    //Unset id to add item on quote with addItem
                    //$quote->addItem($item);
                    $itemCollection->addItem($item);
                }

                // Update totals for vendor
                $quote->setTotalsCollectedFlag(false)->collectTotals();

                // Delegate to parent method to place an order for each vendor
                parent::saveOrder();

                $checkoutSession = $this->_checkoutSession;
                $orders[$checkoutSession->getLastOrderId()] = array('url'=>$checkoutSession->getRedirectUrl(),
                                                                     'incrementid'=>$checkoutSession->getLastRealOrderId(),
                                                                     'profileids'=>$checkoutSession->setLastRecurringProfileIds()
                        );
            }
            return $this;
        } else {
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            return parent::saveOrder();
        }

    }
}
