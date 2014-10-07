<?php

class ADM_Warehouse_Block_Adminhtml_Widget_Grid_Column_Renderer_Qty
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Number
{

    protected $_values;

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {

        $html = '<input type="text" ';
        $html .= 'name="' . $this->getColumn()->getId() . '[]" ';
        if($this->getColumn()->getNameKey()) {
            $value = (int) $row->getData($this->getColumn()->getNameKey());
            if(empty($value)) {
                $value = '';
            }
            $html .= $this->getColumn()->getNameKey() . '="' . $value .'" ';
        }

        $html .= 'original_value="' . $row->getData($this->getColumn()->getIndex()) . '"';
        $html .= 'value="' . $row->getData($this->getColumn()->getIndex()) . '"';
        $html .= 'class="input-text ' . $this->getColumn()->getInlineCss() . '"/>';

        return $html;
    }

}
