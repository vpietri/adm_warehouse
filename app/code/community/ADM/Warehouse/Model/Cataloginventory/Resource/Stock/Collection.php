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
        $this->_map['fields']['website']    = 'stock_table.website_id';
    }

    /**
     * Set first website flag
     *
     * @param bool $flag
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection
     */
    public function setFirstWebsiteFlag($flag = false)
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
                ->from(array('wss'=>$this->getTable('adm_warehouse/stock_website')))
                ->where('wss.stock_id IN (?)', $items);

                if ($result = $connection->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('stock_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('stock_id')] == 0) {
                            $websites = Mage::app()->getWebsites(false, true);
                            $websiteId = current($websites)->getId();
                            $websiteCode = key($websites);
                        } else {
                            $websiteId = $result[$item->getData('stock_id')];
                            $websiteCode = Mage::app()->getWebsite($websiteId)->getCode();
                        }
                        $item->setData('_first_website_id', $websiteId);
                        $item->setData('website_code', $websiteCode);
                    }
                }
            }

        }

        return parent::_afterLoad();
    }


    /**
     * Add filter by website
     *
     * @param int|Mage_Core_Model_Website $website
     * @param bool $withAdmin
     * @return ADM_Warehouse_Model_CatalogInventory_Resource_Stock_Collection
     */
    public function addWebsiteFilter($website, $withAdmin = true)
    {
        if (!$this->getFlag('website_filter_added')) {
            if ($website instanceof Mage_Core_Model_Website) {
                $website = array($website->getId());
            }

            if (!is_array($website)) {
                $website = array($website);
            }

            if ($withAdmin) {
                $website[] = 0;
            }

            $this->addFilter('website', array('in' => $website), 'public');
            $this->setFlag('website_filter_added', true);
        }
        return $this;
    }

    /**
     * Join website relation table if there is website filter
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('website')) {
            $this->getSelect()->join(
                    array('stock_table' => $this->getTable('adm_warehouse/stock_website')),
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