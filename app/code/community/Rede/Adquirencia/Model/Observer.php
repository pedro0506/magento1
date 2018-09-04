<?php

/**
 * Class Rede_Adquirencia_Model_Observer
 */
class Rede_Adquirencia_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function addTransacaoInfoToOrderCollection(Varien_Event_Observer $observer)
    {
        $collection = $observer->getData('order_collection');

        if (!$collection) {
            $collection = $observer->getData('order_grid_collection');
        }

        if (!$collection || !($collection instanceof Mage_Sales_Model_Resource_Order_Collection)) {
            return;
        }

        Mage::getResourceModel('rede_adquirencia/transacoes')->appendTransactionInfoToOrderCollection($collection);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addTransacaoColumnsToOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('rede_adquirencia');
        $block = $observer->getData('block');

        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid)) {
            return;
        }

        $block->addColumnAfter(
            'rede_tid', array(
            'header' => $helper->__('TID'),
            'index' => 'rede_tid',
            'type' => 'text',
            'width' => '150px',
            'filter' => false,
            'sortable' => false,
            ), 'real_order_id'
        )
            ->addColumnAfter(
                'rede_environment', array(
                'header' => $helper->__('Environment'),
                'index' => 'rede_environment',
                'type' => 'options',
                'width' => '100px',
                'options' => Mage::getModel('rede_adquirencia/System_Config_Environments')->toArray(),
                'filter' => false,
                'sortable' => false,
                ), 'rede_tid'
            );

        $block->sortColumnsByOrder();
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addApiActionButtons(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block->getType() === 'adminhtml/sales_order_view_info') {
            $order = Mage::registry('current_order');
            if ($order && $order->getPayment()->getMethodInstance() instanceof Rede_Adquirencia_Model_Method_Standard) {
                $transport = $observer->getTransport();
                if ($transport) {
                    $child = $block->getChild('rede_adquirencia_order_view_buttons');
                    $html = $transport->getHtml();
                    $html .= $child->toHtml();
                    $transport->setHtml($html);
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function verifyCheckout(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $helper = Mage::helper('rede_adquirencia');

        if (!($paymentMethod instanceof Rede_Adquirencia_Model_Method_Standard)) {
            return;
        }

        if ($order->canCancel() && $helper->getIsCheckoutAttemptsExceeded()) {
            $order->cancel()->save();
            $helper->clearCheckoutAttempts();
            return;
        }

        if ($order->canInvoice()
            && $paymentMethod->getConfigData('transaction_type') == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
        ) {
            $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
            $statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
            $orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
            $order->setStatus($orderStatus);
            $order->setIsInProcess(true);

            $qtys = array();
            foreach ($order->getAllItems() as $item) {
                $qtys[$item->getId()] = $item->getQtyOrdered();
            }

            $invoice = $order->prepareInvoice($qtys);

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE)
                ->setTransactionId($order->getPayment()->getTransactionId())
                ->addComment($helper->__('Order invoiced using the E.Rede API.'))
                ->register()
                ->sendEmail(true);

            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($order)
                ->save();
        }
    }
}
