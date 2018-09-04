<?php

/**
 * Class Rede_Adquirencia_Block_Adminhtml_System_Config_Fieldset_Group
 */
class Rede_Adquirencia_Block_Adminhtml_System_Config_Fieldset_Group extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        if (!$element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }

        $html = '<div class="comment">';
        $html .= '<p>' . Mage::helper('rede_adquirencia')->getLogoHtml() . '<p/>';
        $html .= '</div>';

        return $html;
    }
}