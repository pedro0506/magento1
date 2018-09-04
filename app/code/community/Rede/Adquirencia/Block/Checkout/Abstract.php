<?php

/**
 * Class Rede_Adquirencia_Block_Checkout_Abstract
 */
abstract class Rede_Adquirencia_Block_Checkout_Abstract extends Mage_Core_Block_Template
{
    /**
     * @return mixed
     */
    public function getOrder()
    {
        if (!$this->hasData('order')) {
            $realOrderId = Mage::helper('rede_adquirencia')->getCheckoutSession()->getLastRealOrderId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
            $this->setData('order', $order);
        }

        return $this->getData('order');
    }

    /**
     * @return string
     */
    public function getPrintOrderUrl()
    {
        return $this->getUrl('sales/order/print', array('order_id' => $this->getOrder()->getId()));
    }

    /**
     * @return bool
     */
    public function canReorder()
    {
        if ($this->getOrder()->getCustomerIsGuest()) {
            return true;
        }

        return $this->getOrder()->canReorderIgnoreSalable();
    }

    /**
     * @return string
     */
    public function getReorderUrl()
    {
        return $this->getUrl('*/*/reorder', array('order_id' => $this->getOrder()->getId()));
    }

}
