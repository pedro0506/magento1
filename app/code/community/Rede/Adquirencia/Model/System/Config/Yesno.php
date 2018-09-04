<?php

/**
 * Class Rede_Adquirencia_Model_System_Config_Yesno
 */
class Rede_Adquirencia_Model_System_Config_Yesno
{
    protected $_options = array();

    protected $_array = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (empty($this->_options)) {
            $helper = Mage::helper('rede_adquirencia');
            $this->_options[] = array('value' => 1, 'label' => $helper->__('Yes'));
            $this->_options[] = array('value' => 0, 'label' => $helper->__('No'));
        }

        return $this->_options;
    }
}