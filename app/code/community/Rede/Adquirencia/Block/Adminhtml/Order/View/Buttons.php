<?php

/**
 * Class Rede_Adquirencia_Block_Adminhtml_Order_View_Buttons
 */
class Rede_Adquirencia_Block_Adminhtml_Order_View_Buttons extends Mage_Core_Block_Template
{
    /**
     * @return mixed
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * @return bool
     */
    public function canCapture()
    {
        return $this->_getIsERedePayment()
            && $this->_getIsOrderInThisStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
    }

    /**
     * @return bool
     */
    public function canUpdate()
    {
        return $this->_getIsERedePayment()
            && !$this->_getIsOrderTerminated();
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
        return $this->_getIsERedePayment()
            && !$this->_getIsOrderTerminated()
            && ($this->getOrder()->canVoidPayment() || $this->getOrder()->canCreditmemo());
    }

    /**
     * @return bool
     */
    public function canAct()
    {
        return $this->canCapture() || $this->canUpdate() || $this->canVoid();
    }

    /**
     * @return mixed
     */
    public function getCaptureUrl()
    {
        return Mage::helper('adminhtml')->getUrl('*/*/captureOrder', array('order_id' => $this->getOrder()->getId()));
    }

    /**
     * @return mixed
     */
    public function getUpdateUrl()
    {
        return Mage::helper('adminhtml')->getUrl('*/*/updateOrder', array('order_id' => $this->getOrder()->getId()));
    }

    /**
     * @return mixed
     */
    public function getVoidUrl()
    {
        return Mage::helper('adminhtml')->getUrl('*/*/voidOrder', array('order_id' => $this->getOrder()->getId()));
    }

    /**
     * @return mixed
     */
    public function getVoidConfirmationMessage()
    {
        $helper = Mage::helper('rede_adquirencia');
        return $helper->jsQuoteEscape($helper->__('Are you sure you want to cancel the payment?'));
    }

    /**
     * @return bool
     */
    protected function _getIsERedePayment()
    {
        return $this->getOrder()->getPayment()->getMethodInstance() instanceof Rede_Adquirencia_Model_Method_Standard;
    }

    /**
     * @param $status
     *
     * @return bool
     */
    protected function _getIsOrderInThisStatus($status)
    {
        return $this->getOrder()->getStatus() === $status;
    }

    /**
     * @return bool
     */
    protected function _getIsOrderTerminated()
    {
        return $this->_getIsOrderInThisStatus(Mage_Sales_Model_Order::STATE_COMPLETE)
            || $this->_getIsOrderInThisStatus(Mage_Sales_Model_Order::STATE_CLOSED)
            || $this->_getIsOrderInThisStatus(Mage_Sales_Model_Order::STATE_CANCELED);
    }
}