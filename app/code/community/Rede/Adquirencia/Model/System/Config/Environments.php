<?php

/**
 * Class Rede_Adquirencia_Model_System_Config_Environments
 */
class Rede_Adquirencia_Model_System_Config_Environments
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
            $this->_options[] = array('value' => 0, 'label' => $helper->__('Test'));
            $this->_options[] = array('value' => 1, 'label' => $helper->__('Production'));
        }

        return $this->_options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (empty($this->_array)) {
            $helper = Mage::helper('rede_adquirencia');
            $this->_array[0] = $helper->__('Test');
            $this->_array[1] = $helper->__('Production');
        }

        return $this->_array;
    }
}