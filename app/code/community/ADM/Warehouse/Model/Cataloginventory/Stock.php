<?php

class ADM_Warehouse_Model_Cataloginventory_Stock extends Mage_CatalogInventory_Model_Stock
{
    /**
     * Stock's Statuses
     */
    const STATUS_ENABLED = 1;

    const STATUS_DISABLED = 0;

    protected $_stock_ids_by_website=array();


    /**
     * Retrieve stock identifier
     *
     * @return int
     */
    public function getId()
    {
        if ($this->getIdFieldName()) {
            return $this->_getData($this->getIdFieldName());
        } else {
            return parent::getId();
        }
    }


    public function getStockIds($websiteId=null, $groupId=null)
    {
        if (is_null($websiteId)) {
            $websiteId = Mage::app()->getWebsite()->getId();
        }

        if (!isset($this->_stock_ids_by_website[$websiteId])) {
            $collection = Mage::getResourceModel('cataloginventory/stock_collection')
                                ->addWebsiteFilter($websiteId)
                                ->setOrder('sort_order', 'ASC');

            $collection->addFilter('is_active', 1, 'public');

            $sortedStocks=array();
            foreach($collection as $stock) {
                $sortedStocks[] = $stock->getId();
            }
            $this->_stock_ids_by_website[$websiteId] = $sortedStocks;
        }

        return $this->_stock_ids_by_website[$websiteId];
    }


    /**
     * Retrieve items collection object with stock filter
     *
     * @return unknown
     */
    public function getItemCollection()
    {
        return Mage::getResourceModel('cataloginventory/stock_item_collection')
                    ->addStockFilter($this->getStockIds());
    }


    /**
     * Add stock item objects to products
     *
     * @param   collection $products
     * @return  Mage_CatalogInventory_Model_Stock
     */
    public function addItemsToProducts($productCollection)
    {
        $items = $this->getItemCollection()
            ->addProductsFilter($productCollection)
            ->joinStockStatus($productCollection->getStoreId())
            ->load();

        $qty = array();
        $stockDetails = array();
        foreach ($productCollection as $product) {
            $qty[$product->getId()] = 0;
            $stockDetails[$product->getId()] = array();
        }

        $stockItems = array();
        foreach ($items as $item) {
            if (!isset($stockItems[$item->getProductId()])) {
                $stockItems[$item->getProductId()] = $item;
            }

            $qty[$item->getProductId()] += $item->getQty();
            $stockDetails[$item->getProductId()][] = array('item_id'=>$item->getId(), 'qty'=>$item->getQty(), 'stock_id'=>$item->getStockId());

        }


        foreach ($productCollection as $product) {
            if (isset($stockItems[$product->getId()])) {
                $stockItems[$product->getId()]->setQty($qty[$product->getId()]);
                $stockItems[$product->getId()]->setStockDetails($stockDetails[$product->getId()]);
                $stockItems[$product->getId()]->assignProduct($product);
            } else {
            }
        }
        return $this;
    }

    /**
     * Subtract product qtys from stock.
     * Return array of items that require full save
     *
     * @param array $items
     * @return array
     */
    public function registerProductsSale($items)
    {
        $warehouses_qtys = $this->_prepareProductWarehousesAndQtysForRegister($items);

        $this->_getResource()->beginTransaction();
        $stockInfo = $this->_getResource()->getProductsStock($this, array_keys($warehouses_qtys['products']), true);

        /* @var $fullSaveItems will be affected to _itemsForReindex for reindex */
        $fullSaveItems = array();
        $item = Mage::getModel('cataloginventory/stock_item');
        foreach ($stockInfo as $itemInfo) {
            if(!empty($warehouses_qtys['warehouses'][$itemInfo['stock_id']][$item->getProductId()])) {
                $item->setData($itemInfo);
                $qtyByStock = $warehouses_qtys['warehouses'][$itemInfo['stock_id']][$item->getProductId()];

                if (!$item->checkQty($qtyByStock)) {
                    $this->_getResource()->commit();
                    Mage::throwException(Mage::helper('cataloginventory')->__('Not all products are available in the requested quantity'));
                }
                $item->subtractQty($qtyByStock);
                if (!$item->verifyStock() || $item->verifyNotification()) {
                    $fullSaveItems[] = clone $item;
                }
            }
        }

        $this->_getResource()->correctItemsByWarehouseQty($warehouses_qtys['warehouses'], '-', $warehouses_qtys['item_key']);
        $this->_getResource()->commit();

        return $fullSaveItems;
    }




    /**
     * Prepare array('products'=>array($productId=>$qty), 'warehouses'=>array($stockId=>=>array($productId=>$qty)))
     *     based on array($productId => array('qty'=>$qty, 'item'=>$stockItem))
     *
     * @param array $warehouses_qtys
     */
    protected function _prepareProductWarehousesAndQtysForRegister($items)
    {
        $warehouses_qtys = array(
                'products' => array(),
                'warehouses' => array());
        $itemType = $this->_getItemTypeKey($items);
        $warehouses_qtys['item_key']=$itemType;

        foreach ($items as $productId => $item) {

            if (empty($item['item'])) {
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            } else {
                $stockItem = $item['item'];
            }
            $canSubtractQty = $stockItem->getId() && $stockItem->canSubtractQty();
            if ($canSubtractQty && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {

                /* @var $remainingQty : Total qty to substract*/
                $remainingQty = $item['qty'];
                $warehouses_qtys['products'][$productId] = $remainingQty;
                if(!$stockItem->getStockDetails()) {
                    throw new Exception('Stock detail is missing, cannot substract stock quantity.');
                }

                foreach ($stockItem->getStockDetails() as $stockDetail) {

                    $qty     = $stockDetail['qty'];
                    $stockId = $stockDetail['stock_id'];

                    /* @var $wQty : Qty to substract from warehouse stock*/
                    $wQty = $this->_getMaxAllowedQty($remainingQty,$qty);
                    if ($wQty>0) {
                        $warehouses_qtys['warehouses'][$stockId][$productId] = array(
                                'qty' => $wQty,
                                'item_id'=>$item[$itemType],
                        );
                        $remainingQty = $remainingQty - $wQty;
                    }

                    if (empty($remainingQty)) {
                        break;
                    }
                }

            }
        }

        return $warehouses_qtys;
    }


    /**
     *  TODO: check backorder
     *
     * @param unknown_type $remainingQty
     * @param unknown_type $qty
     */
    protected function _getMaxAllowedQty($remainingQty=0, $qty=0)
    {
        $wQty = 0;
        // Set qty by available stock
        if ($qty>0 and $remainingQty>0) {
            if ($remainingQty <= $qty) {
                $wQty = $remainingQty;
            } else {
                $wQty = $qty;
            }
        }

        return $wQty;
    }


    /**
     * Revert quote items inventory data (cover not success order place case)
     * and
     * Return creditmemo items qty to stock
     *
     * @param array $items
     */
    public function revertProductsSale($items)
    {
        $warehouses_qtys = $this->_prepareProductWarehousesAndQtysForRevert($items);

        $this->_getResource()->correctItemsByWarehouseQty($warehouses_qtys['warehouses'], '+', $warehouses_qtys['item_key']);
        return $this;
    }


    protected function _prepareProductWarehousesAndQtysForRevert($items)
    {

        $warehouses_qtys = array(
                                'products' => array(),
                                'warehouses' => array());

        $itemType = $this->_getItemTypeKey($items);
        $warehouses_qtys['item_key']=$itemType;

        foreach ($items as $productId => $item) {

            if (empty($item['item'])) {
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            } else {
                $stockItem = $item['item'];
            }
            $canSubtractQty = $stockItem->getId() && $stockItem->canSubtractQty();
            if ($canSubtractQty && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {
                $warehouses_qtys['products'][$productId] = $item['qty'];
                foreach ($item['warehouses'] as $stockId=>$qty) {
                    $warehouses_qtys['warehouses'][$stockId][$productId] = array(
                            'qty' => $qty,
                            'item_id'=>$item[$itemType],
                            );
                }

            }

        }

        return $warehouses_qtys;
    }


    /**
     *
     * @param array $items
     * @return string (quote_item_id or creditmemo_item_id)
     *
     * @throws Exception
     */
    protected function _getItemTypeKey($items)
    {
        $itemType = false;
        foreach ($items as $productId => $item) {
            if (!empty($item['quote_item_id'])) {
                $itemType = 'quote_item_id';
            } elseif (!empty($item['creditmemo_item_id'])) {
                $itemType = 'creditmemo_item_id';
            } else {
                throw new Exception('Cannot defined item type.');
            }

            break;
        }
        reset($items);
        return $itemType;
    }


    /**
     * Get back to stock (when order is canceled or whatever else)
     *
     * @param int $productId
     * @param numeric $qty
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return Mage_CatalogInventory_Model_Stock
     */
    public function backItemQtyToWarehouse($productId, $qty, Mage_Sales_Model_Order_Item $item)
    {
        $stockItemCollection = Mage::getResourceModel('cataloginventory/stock_item_collection')
                            ->addSalesOrderItemFilter($item);

        foreach ($stockItemCollection as $stockItem) {
            if (Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {

                $quoteQty = $stockItem->getQuoteQty();
                if($quoteQty<=$qty) {
                    $stockItem->addQty($quoteQty);
                } else {
                    $stockItem->addQty($qty);
                }

                if ($stockItem->getCanBackInStock() && $stockItem->getQty() > $stockItem->getMinQty()) {
                    $stockItem->setIsInStock(true)
                    ->setStockStatusChangedAutomaticallyFlag(true);
                }
                $stockItem->save();
            }
        }

        return $this;
    }



    /**
     * Prepare stock statuses.
     * Available event cataloginventory_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        $statuses = new Varien_Object(array(
                self::STATUS_ENABLED => Mage::helper('adm_warehouse')->__('Enabled'),
                self::STATUS_DISABLED => Mage::helper('adm_warehouse')->__('Disabled'),
        ));

        Mage::dispatchEvent('cataloginventory_get_available_statuses', array('statuses' => $statuses));

        return $statuses->getData();
    }




}