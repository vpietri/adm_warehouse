<?php

class ADM_Warehouse_Model_Cataloginventory_Stock extends Mage_CatalogInventory_Model_Stock
{
    /**
     * Stock's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

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


    public function getStockIds($storeId=null, $groupId=null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $collection = Mage::getResourceModel('cataloginventory/stock_collection')
                            ->addStoreFilter($storeId)
                            ->setOrder('sort_order', 'ASC');

        $collection->addFilter('is_active', 1, 'public');

        $sortedStocks=array();
        foreach($collection as $stock) {
            $sortedStocks[] = $stock->getId();
        }

        return $sortedStocks;
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

        $stockItems = array();
        $qty = array();
        $stockDetails = array();

        foreach ($productCollection as $product) {
            $qty[$product->getId()] = 0;
            $stockDetails[$product->getId()] = array();
        }

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
        $warehouses_qtys = $this->_prepareProductWarehousesAndQtys($items);

        $this->_getResource()->beginTransaction();
        $stockInfo = $this->_getResource()->getProductsStock($this, array_keys($warehouses_qtys['products']), true);

        $fullSaveItems = array();
        $item = Mage::getModel('cataloginventory/stock_item');
        foreach ($stockInfo as $itemInfo) {
            $item->setData($itemInfo);
            if (!$item->checkQty($warehouses_qtys['products'][$item->getProductId()])) {
                $this->_getResource()->commit();
                Mage::throwException(Mage::helper('cataloginventory')->__('Not all products are available in the requested quantity'));
            }
            $item->subtractQty($warehouses_qtys['products'][$item->getProductId()]);
            if (!$item->verifyStock() || $item->verifyNotification()) {
                $fullSaveItems[] = clone $item;
            }
        }

        $this->_getResource()->correctItemsByWarehouseQty($warehouses_qtys, '-');

        $this->_getResource()->commit();
        return $fullSaveItems;
    }

    /**
     *
     * @param unknown_type $items
     */
    public function revertProductsSale($items)
    {
        $warehouses_qtys = $this->_prepareProductWarehousesAndQtys($items);

        $this->_getResource()->correctItemsByWarehouseQty($warehouses_qtys, '+');
        return $this;
    }


    /**
     * Prepare array('products'=>array($productId=>$qty), 'warehouses'=>array($stockId=>=>array($productId=>$qty)))
     *     based on array($productId => array('qty'=>$qty, 'item'=>$stockItem))
     *
     * @param array $warehouses_qtys
     */
    protected function _prepareProductWarehousesAndQtys($items)
    {
        $warehouses_qtys = array('products'=>array(), 'warehouses'=>array());
        foreach ($items as $productId => $item) {
            if (empty($item['item'])) {
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            } else {
                $stockItem = $item['item'];
            }


            $canSubtractQty = $stockItem->getId() && $stockItem->canSubtractQty();
            if ($canSubtractQty && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {
                $remainingQty = $item['qty'];
                $warehouses_qtys['products'][$productId]=$remainingQty;
                foreach($stockItem->getStockDetails() as $stockDetail) {
                    $wQty=0;
                    $qty= $stockDetail['qty'];
                    $stockId= $stockDetail['stock_id'];

                    //TODO: check backorder
                    //Set qty by stock
                    if($qty<=0) {
                        continue;
                    } elseif ($remainingQty<=$qty) {
                        $wQty = $remainingQty;
                    } elseif ($remainingQty>$qty) {
                        $wQty = $qty;
                    }

                    $warehouses_qtys['warehouses'][$stockId][$productId] = $wQty;
                    $remainingQty-= $wQty;

                    if(empty($remainingQty)) {
                        break;
                    }
                }
            }
        }

        //Dispatch event in order to change the warehouse stock picking
        Mage::dispatchEvent('warehouses_cataloginventory_prepare_qtys', array(
                'warehouses'       => $warehouses_qtys,
                'items'    => $items
        ));

        return $warehouses_qtys;
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