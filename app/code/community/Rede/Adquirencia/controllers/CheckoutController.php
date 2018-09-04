<?php

/**
 * Class Rede_Adquirencia_CheckoutController
 */
class Rede_Adquirencia_CheckoutController extends Rede_Adquirencia_Controller_Front_Action
{
    public function successAction()
    {
        $this->_initLayout();

        if (!$lastOrderId = $this->_getLastRealOrderId()) {
            $this->_redirectCart();
            return;
        }

        $this->_renderLayout();
        $this->_clearSessions();
    }

    public function errorAction()
    {
        $this->_initLayout();

        if (!$lastOrderId = $this->_getLastRealOrderId()) {
            $this->_redirectCart();
            return;
        }

        $this->_renderLayout();
        $this->_clearSessions();
    }

    public function verifyAction()
    {
        if (!$lastOrderId = $this->_getLastRealOrderId()) {
            $this->_redirectCart();
            return;
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($lastOrderId);
        if ($order->getStatus() === Mage_Sales_Model_Order::STATE_CANCELED) {
            $this->_redirect('adquirencia/checkout/error');
        } else {
            $this->_redirect('adquirencia/checkout/success');
        }
    }
}
