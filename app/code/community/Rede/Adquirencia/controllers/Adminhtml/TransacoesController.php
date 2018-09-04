<?php

/**
 * Class Rede_Adquirencia_Adminhtml_TransacoesController
 */
class Rede_Adquirencia_Adminhtml_TransacoesController extends Mage_Adminhtml_Controller_Action
{
    protected $_helper = null;

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function viewAction()
    {
        $this->_initTransacao();

        $this->_initAction()
            ->renderLayout();
    }

    /**
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('rede_adquirencia/rede_adquirencia_transacoes')
            ->_title($this->_getHelper()->__('e.Rede'))->_title($this->_getHelper()->__('Transactions'))
            ->_addBreadcrumb($this->_getHelper()->__('e.Rede'), $this->_getHelper()->__('e.Rede'))
            ->_addBreadcrumb($this->_getHelper()->__('Transactions'), $this->_getHelper()->__('Transactions'));

        return $this;
    }

    protected function _initTransacao()
    {
        $helper = Mage::helper('rede_adquirencia');
        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->_forward('noRoute');
            return;
        }

        $log = Mage::getModel('rede_adquirencia/transacoes')->load($id);

        if (!$log->getId()) {
            $helper->getAdminSession()->addError($this->_getHelper()->__('This e.Rede transaction was not found.'));
            $this->_redirectReferer();
            return;
        }

        $order = Mage::getModel('sales/order')->load($log->getOrderId());

        if (!$order->getId()) {
            $helper->getAdminSession()->addError($this->_getHelper()->__('The related order was not found.'));
            $this->_redirectReferer();
            return;
        }

        Mage::register('current_transacao', $log);
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