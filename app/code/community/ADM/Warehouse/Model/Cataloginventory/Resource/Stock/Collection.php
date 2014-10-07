<?php
class ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Load data for preview flag
     *
     * @var bool
     */
    protected $_previewFlag;

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('cataloginventory/stock');
        $this->_map['fields']['stock_id'] = 'main_table.stock_id';
        $this->_map['fields']['store']    = 'stock_table.store_id';
    }

    /**
     * Set first store flag
     *
     * @param bool $flag
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection
     */
    public function setFirstStoreFlag($flag = false)
    {
        $this->_previewFlag = $flag;
        return $this;
    }

    /**
     * Perform operations after collection load
     *
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection
     */
    protected function _afterLoad()
    {
        if ($this->_previewFlag) {
            $items = $this->getColumnValues('stock_id');

            $connection = $this->getConnection();
            if (count($items)) {
                $select = $connection->select()
                ->from(array('wss'=>$this->getTable('adm_warehouse/stock_store')))
                ->where('wss.stock_id IN (?)', $items);

                if ($result = $connection->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('stock_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('stock_id')] == 0) {
                            $stores = Mage::app()->getStores(false, true);
                            $storeId = current($stores)->getId();
                            $storeCode = key($stores);
                        } else {
                            $storeId = $result[$item->getData('stock_id')];
                            $storeCode = Mage::app()->getStore($storeId)->getCode();
                        }
                        $item->setData('_first_store_id', $storeId);
                        $item->setData('store_code', $storeCode);
                    }
                }
            }

        }

        return parent::_afterLoad();
    }


    /**
     * Add filter by store
     *
     * @param int|Mage_Core_Model_Store $store
     * @param bool $withAdmin
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            if (!is_array($store)) {
                $store = array($store);
            }

            if ($withAdmin) {
                $store[] = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $this->addFilter('store', array('in' => $store), 'public');
            $this->setFlag('store_filter_added', true);
        }
        return $this;
    }

    /**
     * Join store relation table if there is store filter
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                    array('stock_table' => $this->getTable('adm_warehouse/stock_store')),
                    'main_table.stock_id = stock_table.stock_id',
                    array()
            )->group('main_table.stock_id');

            /*
             * Allow analytic functions usage because of one field grouping
            */
            $this->_useAnalyticFunction = true;
        }
        return parent::_renderFiltersBefore();
    }

    /**
     * Convert items array to hash for select options
     *
     * @return  array
     */
    public function toOptionHash()
    {
        return $this->_toOptionHash('stock_id', 'stock_name');
    }
}