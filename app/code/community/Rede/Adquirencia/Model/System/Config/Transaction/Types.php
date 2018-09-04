<?php

/**
 * Class Rede_Adquirencia_Model_System_Config_Transaction_Types
 */
class Rede_Adquirencia_Model_System_Config_Transaction_Types
{
    protected $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (empty($this->_options)) {
            $helper = Mage::helper('rede_adquirencia');
            $this->_options[] = array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                'label' => $helper->__('With automatic capture')
            );
            $this->_options[] = array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'label' => $helper->__('With subsequent capture')
            );
        }

        return $this->_options;
    }
}