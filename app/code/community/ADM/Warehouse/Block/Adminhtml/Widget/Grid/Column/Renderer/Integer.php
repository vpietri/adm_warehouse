<?php

class ADM_Warehouse_Block_Adminhtml_Widget_Grid_Column_Renderer_Integer
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
        $html .= 'name="' . $this->getColumn()->getId() . '" ';
        $html .= 'value="' . $row->getData($this->getColumn()->getIndex()) . '"';
        $html .= 'class="input-text ' . $this->getColumn()->getInlineCss() . '"/>';
        return $html;
    }


    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
//     public function render(Varien_Object $row)
//     {
//         $input = '';
//         if($this->_getWidgetOption('readonly')) {
//             return $this->_getHtmlText($row);
//         }

//         switch ($this->_getWidgetOption('input')) {
//             case 'checkbox':
//                 $html = $this->_getHtmlMultiselect($row);
//             break;

//             case 'multiselect':
//             default:
//                 $html = $this->_getHtmlCheckbox($row);
//             break;
//         }

//         return $html;
//     }

    /**
     * Get value from optionnal widget_options key
     *
     * @param string $option
     *
     * @return mixed
     */
    protected function _getWidgetOption($option)
    {
        $widgetOptions = $this->getColumn()->getWidgetOptions();
        if(empty($widgetOptions)) {
            return false;
        } elseif (!is_array($widgetOptions)) {
            return $widgetOptions == $option;
        } else {
            return isset($widgetOptions[$option]) ? $widgetOptions[$option] : false;
        }
    }


    /**
     * @return string
     */
    protected function _getInputName()
    {
        return $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
    }

    /**
     * Get all options
     *
     * @return array
     */
    protected function _getOptions()
    {
        $options = $this->getColumn()->getOptions();
        return empty($options) ? array() : $options;
    }

    /**
     * Check if value is in availalble options
     *
     * @param Varien_Object $row
     * @param string|int $option
     *
     * @return bool
     */
    protected function _isOptionSet(Varien_Object $row, $option)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if(!empty($value)) {
            $optionsSetted = explode(',', $value);
        } else {
            return false;
        }

        return in_array($option, $optionsSetted);
    }

    /**
     * Get row as text
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    protected function _getHtmlText(Varien_Object $row)
    {
        $htmlValues=array();
        $value = $row->getData($this->getColumn()->getIndex());
        foreach ($this->_getOptions() as $val => $label) {
            if( $this->_isOptionSet($row, $val) ) {
                $htmlValues[] = $label;
            }
        }
        return implode('<span class="separator">&nbsp;|&nbsp;</span>', $htmlValues);
    }

    /**
     * Get row as html multiple select input
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    protected function _getHtmlMultiselect(Varien_Object $row)
    {
        $html = '<select multiple name="' . $this->escapeHtml($this->_getInputName()) . '" ' . $this->getColumn()->getValidateClass() . '>';
        $value = $row->getData($this->getColumn()->getIndex());
        foreach ($this->_getOptions() as $val => $label){
            $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
            $html .= '<option value="' .
                     $this->escapeHtml($val) . '"' .
                     ( $this->_isOptionSet($row, $val) ? ' selected="selected"' : '' ) .
                     '>' .
                     $this->escapeHtml($label) . '</option>';
        }
        $html.='</select>';
        return $html;
    }

    /**
     * Get row as html checboxes input
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    protected function _getHtmlCheckbox(Varien_Object $row)
    {
        $html = '';
        foreach ($this->_getOptions() as $val => $label){
            $html.= '<input type="checkbox" name="' .
                    $this->escapeHtml($this->_getInputName()) . '[]" ' .
                    $this->getColumn()->getValidateClass() .
                    'value="' . $this->escapeHtml($val) . '"' .
                    ( $this->_isOptionSet($row, $val) ? ' checked="checked"' : '' ) .
                    '>' .
                    $this->escapeHtml($label);
        }
        return $html;
    }

}
