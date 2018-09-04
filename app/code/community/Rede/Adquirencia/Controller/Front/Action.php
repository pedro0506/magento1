<?php

/**
 * Class Rede_Adquirencia_Controller_Front_Action
 */
class Rede_Adquirencia_Controller_Front_Action extends Mage_Sales_Controller_Abstract
{
    private $_helper = null;

    /**
     * @param null $handles
     * @param bool $generateBlocks
     * @param bool $generateXml
     *
     * @return $this
     */
    protected function _initLayout($handles = null, $generateBlocks = true, $generateXml = true)
    {
        $this->loadLayout($handles, $generateBlocks, $generateXml);
        $this->_title('E.Rede');

        return $this;
    }

    /**
     * @param string $output
     *
     * @return $this
     */
    protected function _renderLayout($output = '')
    {
        $this->renderLayout($output);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _redirectCart()
    {
        $this->_redirect('checkout/cart');
        return $this;
    }

    /**
     * @return mixed
     */
    protected function _getLastRealOrderId()
    {
        return $this->_getHelper()->getCheckoutSession()->getLastRealOrderId();
    }

    /**
     * @return $this
     */
    protected function _clearSessions()
    {
        $this->_getHelper()->clearCheckoutAttempts();
        $this->_getHelper()->getCheckoutSession()->clear();

        return $this;
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
}
