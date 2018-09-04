<?php

/**
 * Class Rede_Adquirencia_Adminhtml_Sales_OrderController
 */
class Rede_Adquirencia_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Controller_Action
{
    private $_helper = null;

    /**
     * @param null $orderId
     *
     * @return bool|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    protected function _initOrder($orderId = null)
    {
        if (empty($orderId)) {
            $orderId = $this->getRequest()->getParam('order_id');
        }

        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$order->getId()) {
            $this->_forward('noRoute');
            return false;
        }

        if (!Mage::registry('current_order')) {
            Mage::register('current_order', $order);
        }

        return $order;
    }

    public function updateOrderAction()
    {
        $order = $this->_initOrder();

        if (!$order->getId()) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('Order was not found.'));
            $this->_redirectReferer();
            return;
        }

        $transacao = Mage::getModel('rede_adquirencia/transacoes')->load($order->getId(), 'order_id');
        $tid = $transacao->getTid();

        if (empty($tid)) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('This order was not found in Rede Acquiring.'));
            $this->_redirectReferer();
            return;
        }

        try {
            $processor = Mage::getModel('rede_adquirencia/processor_transacao', $order);
            $processor->update($order->getPayment());
        } catch (\Exception $e) {
            Mage::logException($e);
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('An error has ocurred on order update.'));
            $this->_redirectReferer();
            return;
        }

        $this->_getHelper()->getAdminSession()->addSuccess($this->_getHelper()->__('Order updated successfully.'));
        $this->_redirect('*/*/view', array('order_id' => $order->getId()));
    }

    public function voidOrderAction()
    {
        $order = $this->_initOrder();

        if (!$order->getId()) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('Order was not found.'));
            $this->_redirectReferer();
            return;
        }

        $transacoes = Mage::getModel('rede_adquirencia/transacoes')->load($order->getId(), 'order_id');
        $tid = $transacoes->getTid();

        if (empty($tid)) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('The transaction of order was not found in E.Rede.'));
            $this->_redirectReferer();
            return;
        }

        try {
            $processor = Mage::getModel('rede_adquirencia/processor_transacao', $order);
            $processor->refundAndProcess($order->getPayment());
        } catch (\Exception $e) {
            Mage::logException($e);
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('An error has ocurred on order refund.'));
            $this->_redirectReferer();
            return;
        }

        $this->_getHelper()->getAdminSession()->addSuccess($this->_getHelper()->__('Order refunded successfully.'));
        $this->_redirect('*/*/view', array('order_id' => $order->getId()));
    }

    public function captureOrderAction()
    {
        $order = $this->_initOrder();

        if (!$order->getId()) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('Order was not found.'));
            $this->_redirectReferer();
            return;
        }

        $transacoes = Mage::getModel('rede_adquirencia/transacoes')->load($order->getId(), 'order_id');
        $tid = $transacoes->getTid();

        if (empty($tid)) {
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('The transaction of order was not found in E.Rede.'));
            $this->_redirectReferer();
            return;
        }

        try {
            $processor = Mage::getModel('rede_adquirencia/processor_transacao', $order);
            $processor->captureAndProcess($order->getPayment());
        } catch (\Exception $e) {
            Mage::logException($e);
            $this->_getHelper()->getAdminSession()->addError($this->_getHelper()->__('An error has ocurred on order capture.'));
            $this->_redirectReferer();
            return;
        }

        $this->_getHelper()->getAdminSession()->addSuccess($this->_getHelper()->__('Order captured successfully.'));
        $this->_redirect('*/*/view', array('order_id' => $order->getId()));
    }

    /**
     * @return Mage_Adminhtml_Helper_Data|Mage_Core_Helper_Abstract|null
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('rede_adquirencia');
        }
        return $this->_helper;
    }
}
