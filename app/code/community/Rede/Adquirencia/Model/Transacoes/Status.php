<?php

/**
 * Class Rede_Adquirencia_Model_Transacoes_Status
 */
class Rede_Adquirencia_Model_Transacoes_Status
{
    const APPROVED = 1;
    const DENIED = 2;
    const CANCELED = 3;
    const PENDING = 4;

    protected $_array = array();

    /**
     * @return array
     */
    public function toArray()
    {
        if (empty($this->_array)) {
            $helper = Mage::helper('rede_adquirencia');
            $this->_array[self::APPROVED] = $helper->__('Approved');
            $this->_array[self::DENIED] = $helper->__('Denied');
            $this->_array[self::CANCELED] = $helper->__('Canceled');
            $this->_array[self::PENDING] = $helper->__('Pending');
        }

        return $this->_array;
    }
}