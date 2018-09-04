<?php

/**
 * Class Rede_Adquirencia_Block_Logo
 */
class Rede_Adquirencia_Block_Logo extends Mage_Core_Block_Template
{
    const DEFAULT_LOGO_WIDTH = 180;

    protected $_helper = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/adquirencia/logo.phtml');
    }

    /**
     * @return Mage_Core_Helper_Abstract|null
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('rede_adquirencia');
        }
        return $this->_helper;
    }

    /**
     * @return string
     */
    public function getSrc()
    {
        return $this->getSkinUrl('rede/adquirencia/images/e_rede_transparente.png');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_getHelper()->__('e.Rede');
    }

    /**
     * @return string
     */
    public function getAlt()
    {
        return $this->_getHelper()->__('e.Rede');
    }

    /**
     * @return int|mixed
     */
    public function getWidth()
    {
        if (!$this->getData('width')) {
            return self::DEFAULT_LOGO_WIDTH;
        }

        return $this->getData('width');
    }
}
