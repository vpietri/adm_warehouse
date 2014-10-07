<?php
class ADM_Warehouse_Model_Observer extends Varien_Event_Observer
{
    /**
     * @param  Varien_Event_Observer $observer the observer
     */
    public function alterAdminhtmlProductInventoryTab(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if($block->getNameInLayout()=='product_tabs') {
            if (Mage::helper('core')->isModuleEnabled('Mage_CatalogInventory')) {

                $body = $block->getLayout()
                                ->createBlock('adm_warehouse/adminhtml_catalog_product_edit_tab_inventory')->toHtml();
                Mage::getSingleton('core/translate_inline')->processResponseBody($body);

                $block->addTab('inventory', array(
                        'label'     => Mage::helper('catalog')->__('Inventory'),
                        'content'   => $body,
                ));
            }
        }

    }
}