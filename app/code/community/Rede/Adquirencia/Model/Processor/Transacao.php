<?php
require_once Mage::getBaseDir('lib') . '/erede-php/vendor/autoload.php';

use Rede\Environment;
use Rede\eRede;
use Rede\Store;
use Rede\Transaction;

/**
 * Class Rede_Adquirencia_Model_Processor_Transacao
 */
class Rede_Adquirencia_Model_Processor_Transacao
{
    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH = 'AUTH';
    const REQUEST_TYPE_CAPTURE = 'CAPTURE';
    const REQUEST_TYPE_VOID = 'VOID';

    protected $_helper = null;
    protected $_logger = null;

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @param bool $capture
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function authorize(Varien_Object $payment, $amount, $capture = false)
    {
        $payment->setAmount($amount);

        $environment = $this->_getHelper()->getEnvironment() ? Environment::production() : Environment::sandbox();
        $store = new Store(
            $this->_getHelper()->getConfigAffiliation(),
            $this->_getHelper()->getConfigToken(),
            $environment
        );

        $transaction = (new Transaction(
            $payment->getAmount(),
            $payment->getOrder()->getRealOrderId() + $this->_getHelper()->getConfigReference()
        ))->creditCard(
            $payment->getCcNumber(),
            $payment->getCcCid(),
            sprintf('%02d', $payment->getCcExpMonth()),
            $payment->getCcExpYear(),
            $payment->getCcOwner()
        )->capture($capture)
            ->setInstallments($payment->getAdditionalInformation('cc_installments'));

        $softDescriptor = $this->_getHelper()->getConfigSoftDescription();

        if (!empty($softDescriptor)) {
            $transaction->setSoftDescriptor($softDescriptor);
        }

        // TODO: Antifraude
        $antifraudEnabled = $this->_getHelper()->getConfigAntifraud();

        if ($antifraudEnabled == '1') {
            $fingerprint = $payment->getAdditionalInformation('session_id');

            $environment->setIp(filter_var(Mage::helper('core/http')->getRemoteAddr()))
                ->setSessionId(trim(sprintf('%s-%s', $softDescriptor, $fingerprint)));

            $shippingAddress = Mage::getModel('checkout/session')->getQuote()
                ->getShippingAddress()
                ->getData();

            $billingAddress = Mage::getModel('checkout/session')->getQuote()
                ->getBillingAddress()
                ->getData();

            $fullName = trim(sprintf(
                '%s %s %s',
                $billingAddress['firstname'],
                $billingAddress['middlename'],
                $billingAddress['lastname']
            ));

            if (empty($fullName)) {
                $fullName = trim(sprintf(
                    '%s %s %s',
                    $shippingAddress['firstname'],
                    $shippingAddress['middlename'],
                    $shippingAddress['lastname']
                ));
            }

            $telefone = preg_replace('/[^\d]/', '', $billingAddress['telephone']);
            $ddd = substr($telefone, 0, 3);

            if (substr($telefone, 0, 1) !== '0') {
                $ddd = substr($telefone, 0, 2);
            }

            $phone = str_replace($ddd, '', $telefone);

            //Dados do antifraude
            $antifraud = $transaction->antifraud($environment);
            $antifraud->consumer($fullName, $billingAddress['email'], $payment->getAdditionalInformation('cc_document'))
                ->setPhone(new \Rede\Phone($ddd, $phone)
                );

            $street = explode("\n", $billingAddress['street']);
            $antifraud->address(\Rede\Address::BILLING)
                ->setAddresseeName($fullName)
                ->setAddress($street[0])
                ->setNumber(preg_replace('/[^\d]/', '', $street[0]))
                ->setZipCode(preg_replace('/[^\d]/', '', $billingAddress['postcode']))
                ->setNeighbourhood($street[1])
                ->setCity($billingAddress['city'])
                ->setState($billingAddress['region']);

            $street = explode("\n", $shippingAddress['street']);
            $antifraud->address(\Rede\Address::SHIPPING)
                ->setAddresseeName($fullName)
                ->setAddress($street[0])
                ->setNumber(preg_replace('/[^\d]/', '', $street[0]))
                ->setZipCode(preg_replace('/[^\d]/', '', $shippingAddress['postcode']))
                ->setNeighbourhood($street[1])
                ->setCity($shippingAddress['city'])
                ->setState($shippingAddress['region']);

            $cart = Mage::getModel('checkout/cart')->getQuote();

            foreach ($cart->getAllItems() as $item) {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                $antifraud->addItem(
                    (new \Rede\Item($item->getProductId(), $item->getQty(), \Rede\Item::PHYSICAL))
                        ->setAmount(round($item->getPrice() * 100))
                        ->setDescription($product->getDescription())
                );
            }
        }

        $success = false;
        $errMessage = null;
        $transactionStatus = Rede_Adquirencia_Model_Transacoes_Status::DENIED;

        try {
            $transaction = (new eRede($store))->authorize($transaction);
            $success = $transaction->getReturnCode() == '00';
            $errMessage = $transaction->getReturnMessage();
            $transactionStatus = $transactionStatus = $transaction->getTid() ? Rede_Adquirencia_Model_Transacoes_Status::DENIED : Rede_Adquirencia_Model_Transacoes_Status::CANCELED;
        } catch (\Rede\Exception\RedeException $e) {
            $errMessage = $e->getMessage();
        }

        if ($success) {
            $transactionStatus = $capture ? Rede_Adquirencia_Model_Transacoes_Status::APPROVED : Rede_Adquirencia_Model_Transacoes_Status::PENDING;

            $this->_addTransaction(
                $payment,
                $transaction->getTid(),
                $capture ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE : Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
                array(
                    'is_transaction_closed' => 0,
                    'is_transaction_approved' => 1,
                    'anet_trans_type' => $capture ? self::REQUEST_TYPE_AUTH_CAPTURE : self::REQUEST_TYPE_AUTH
                ),
                $this->_getHelper()->__(
                    'Transacion %s by e.Rede.',
                    $this->_getHelper()->__($capture ? 'Authorized and Captured' : 'Authorized')
                )
            );
        }

        /**
         * @var Rede_Adquirencia_Model_Transacoes $transaction
         * @var int $status
         **/
        $redeTransaction = Mage::getModel('rede_adquirencia/transacoes')
            ->setOrderId($payment->getOrder()->getId())
            ->setTid($transaction->getTid())
            ->setTransactionStatus($transactionStatus)
            ->setCardType($payment->getCcType())
            ->setCardBin($transaction->getCardBin())
            ->setCardNumber($payment->getCcLast4())
            ->setCardholderName($payment->getCcOwner())
            ->setCardExpYear($payment->getCcExpYear())
            ->setCardExpMonth($payment->getCcExpMonth())
            ->setPaymentMethod($payment->getMethodInstance()->getConfigData('title'))
            ->setInstallments($payment->getAdditionalInformation('cc_installments'))
            ->setAmount($payment->getAmount())
            ->setNsu($transaction->getNsu())
            ->setAuthorizationNumber($transaction->getAuthorizationCode())
            ->setCreatedDate(date('Y-m-d H:i:s'))
            ->setReturnMessage($transaction->getReturnMessage())
            ->setEnvironment($this->_getHelper()->getEnvironment());

        if ($antifraudEnabled == '1') {
            $antifraud = $transaction->getAntifraud();

            $redeTransaction->setScore($antifraud->getScore())
                ->setRiskLevel($antifraud->getRiskLevel())
                ->setRecommendation($antifraud->getRecommendation());
        }

        $redeTransaction->save();

        if (!$success) {
            Mage::throwException(trim($this->_getHelper()->__('Dear customer, this transaction was not authorized. Please try again with a different card or select another payment method.') . "\n\n" . $errMessage));
        }

        return $redeTransaction;
    }

    /**
     * @param Varien_Object $payment
     * @param null $amount
     *
     * @return Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount = null)
    {
        if (!$amount) {
            $amount = $payment->getOrder()->getGrandTotal();
        }

        $payment->setAmount($amount);

        $redeTransacao = Mage::getModel('rede_adquirencia/transacoes')->load($payment->getOrder()->getId(), 'order_id');
        $tid = $redeTransacao->getTid();

        $environment = $this->_getHelper()->getEnvironment() ? Environment::production() : Environment::sandbox();
        $store = new Store(
            $this->_getHelper()->getConfigAffiliation(),
            $this->_getHelper()->getConfigToken(),
            $environment
        );

        $transaction = (new \Rede\eRede($store))->capture((new Transaction($amount))->setTid($tid));

        $success = $transaction->getReturnCode() == '00';

        if (!$success) {
            $errMessage = $this->_getHelper()->__(
                'An error occured while capturing: %s',
                $transaction->getReturnMessage()
            );

            Mage::throwException($errMessage);
        }

        $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
        $orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
        $payment->getOrder()->setStatus($orderStatus);
        $payment->getOrder()->setIsInProcess(true);

        $this->_addCaptureTransaction($payment);

        $redeTransacao->setTransactionStatus(Rede_Adquirencia_Model_Transacoes_Status::APPROVED)
            ->setCaptureAmount($payment->getAmount())
            ->setModifiedDate(date('Y-m-d H:i:s'));

        $redeTransacao->save();

        return $redeTransacao;
    }

    /**
     * @param Varien_Object $payment
     *
     * @throws Mage_Core_Exception
     */
    public function captureAndProcess(Varien_Object $payment)
    {
        $transacao = $this->capture($payment);
        $this->_processStatus($payment, $transacao);
    }

    /**
     * @param Varien_Object $payment
     *
     * @throws Mage_Core_Exception
     */
    public function update(Varien_Object $payment)
    {
        $redeTransacao = Mage::getModel('rede_adquirencia/transacoes')->load($payment->getOrder()->getId(), 'order_id');
        $tid = $redeTransacao->getTid();

        $environment = $this->_getHelper()->getEnvironment() ? Environment::production() : Environment::sandbox();
        $store = new Store(
            $this->_getHelper()->getConfigAffiliation(),
            $this->_getHelper()->getConfigToken(),
            $environment
        );

        $transaction = (new eRede($store))->get($tid);
        $success = $transaction && !$transaction->getReturnCode();

        if (!$success) {
            $errMessage = $this->_getHelper()->__('An error occured while updating: %s',
                $transaction->getReturnMessage());
            Mage::throwException($errMessage);
        }

        $status = $this->_mapTransactionStatus($transaction->getAuthorization()->getStatus());

        if ($redeTransacao->getTransactionStatus() !== $status) {
            $redeTransacao->setTransactionStatus($status)
                ->setModifiedDate(date('Y-m-d H:i:s'));

            if ($status === Rede_Adquirencia_Model_Transacoes_Status::APPROVED) {
                $amount = $transaction->getCapture()->getAmount();

                $redeTransacao->setCaptureAmount(floatval(intval($amount) / 100))
                    ->setModifiedDate((new DateTime($transaction->getCapture()->getDateTime()))->format('Y-m-d H:i:s'));

            } elseif ($status === Rede_Adquirencia_Model_Transacoes_Status::CANCELED) {
                $refunds = $transaction->getRefunds();

                if ($refunds && is_array($refunds)) {
                    foreach ($refunds as $refund) {
                        $redeTransacao->setModifiedDate((new DateTime($refund->getRefundDateTime()))->format('Y-m-d H:i:s'));
                        break;
                    }
                }
            }

            $this->_processStatus($payment, $redeTransacao, true);
        }
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment)
    {
        $amount = $payment->getOrder()->getGrandTotal();
        $redeTransacao = Mage::getModel('rede_adquirencia/transacoes')->load($payment->getOrder()->getId(), 'order_id');
        $tid = $redeTransacao->getTid();

        $environment = $this->_getHelper()->getEnvironment() ? Environment::production() : Environment::sandbox();
        $store = new Store(
            $this->_getHelper()->getConfigAffiliation(),
            $this->_getHelper()->getConfigToken(),
            $environment
        );

        $transaction = (new \Rede\eRede($store))->cancel((new Transaction($amount))->setTid($tid));
        $success = $transaction && in_array($transaction->getReturnCode(), array('359', '360'));

        if (!$success) {
            $errMessage = $this->_getHelper()->__(
                'An error occured while canceling: %s',
                $transaction->getReturnMessage()
            );

            Mage::throwException($errMessage);
        }

        $orderState = Mage_Sales_Model_Order::STATE_CLOSED;
        $statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
        $orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
        $payment->getOrder()->setStatus($orderStatus);

        $this->_addVoidTransaction($payment);

        $redeTransacao->setTransactionStatus(Rede_Adquirencia_Model_Transacoes_Status::CANCELED)
            ->setModifiedDate(date('Y-m-d H:i:s'))
            ->setRefundId($transaction->getRefundId())
            ->setCancelId($transaction->getCancelId());

        return $redeTransacao;
    }

    /**
     * @param Varien_Object $payment
     *
     * @throws Mage_Core_Exception
     */
    public function refundAndProcess(Varien_Object $payment)
    {
        $transacao = $this->refund($payment);
        $this->_processStatus($payment, $transacao);
    }

    /**
     * @param Varien_Object $payment
     */
    protected function _addCaptureTransaction(Varien_Object $payment)
    {
        $authTransaction = $this->_getLastTransaction($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $authTransaction->setIsClosed(true);
        $authTransaction->save();

        $tid = $authTransaction->getTxnId();
        $this->_addTransaction(
            $payment,
            $tid . '-capture',
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
            array(
                'is_transaction_closed' => 0,
                'parent_transaction_id' => $tid,
                'should_close_parent_transaction' => 1,
                'is_transaction_approved' => 1,
                'anet_trans_type' => self::REQUEST_TYPE_CAPTURE
            ),
            $this->_getHelper()->__('Transaction Captured by E.Rede.')
        );

        $payment->setSkipTransactionCreation(true);
    }

    /**
     * @param Varien_Object $payment
     */
    protected function _addVoidTransaction(Varien_Object $payment)
    {
        $transaction = $this->_getLastTransaction($payment);
        $transaction->setIsClosed(true)
            ->save();

        $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND;
        if ($transaction === Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID;
        }

        $this->_addTransaction(
            $payment,
            $transaction->getTxnId() . '-void',
            $type,
            array(
                'is_transaction_closed' => 1,
                'should_close_parent_transaction' => 1,
                'parent_transaction_id' => $transaction->getTxnId(),
                'anet_trans_type' => self::REQUEST_TYPE_VOID
            ),
            $this->_getHelper()->__('Transaction Voided by E.Rede.')
        );

        $payment->setSkipTransactionCreation(true);
    }

    /**
     * @param $payment
     * @param $transacao
     * @param bool $addTransaction
     *
     * @throws Mage_Core_Exception
     */
    protected function _processStatus($payment, $transacao, $addTransaction = false)
    {
        $order = $payment->getOrder();

        switch ($transacao->getTransactionStatus()) {
            case Rede_Adquirencia_Model_Transacoes_Status::APPROVED:
                if (!$order->canInvoice()) {
                    return;
                }

                $transactionSave = Mage::getModel('core/resource_transaction');
                $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
                $orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
                $order->setStatus($orderStatus);
                $order->setIsInProcess(true);

                if ($addTransaction) {
                    $this->_addCaptureTransaction($payment);
                }

                $invoice = $this->_initInvoice($order);
                if ($invoice) {
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE)
                        ->setTransactionId($payment->getTransactionId())
                        ->addComment($this->_getHelper()->__('Order invoiced using the E.Rede API.'))
                        ->register()
                        ->sendEmail(true);
                }

                $transactionSave->addObject($invoice)
                    ->addObject($order)
                    ->addObject($payment)
                    ->addObject($transacao)
                    ->save();

                break;
            case Rede_Adquirencia_Model_Transacoes_Status::CANCELED:
                $transactionSave = Mage::getModel('core/resource_transaction');

                $orderState = Mage_Sales_Model_Order::STATE_CLOSED;
                $statusModel = Mage::getModel('sales/order_status')->loadDefaultByState($orderState);
                $orderStatus = $statusModel->getStatus() ? $statusModel->getStatus() : $orderState;
                $order->setStatus($orderStatus);

                if ($addTransaction) {
                    $this->_addVoidTransaction($payment);
                }

                if ($order->canCreditmemo()) {
                    $invoiceCollection = $order->getInvoiceCollection();
                    $service = Mage::getModel('sales/service_order', $order);
                    $message = $this->_getHelper()->__('Order Voided By E.Rede.');

                    if ($invoiceCollection && count($invoiceCollection) > 0) {
                        foreach ($invoiceCollection as $invoice) {
                            $invoice->addComment($message);

                            $creditmemo = $service->prepareInvoiceCreditmemo($invoice)
                                ->setOfflineRequested(false)
                                ->setTransactionId($invoice->getTransactionId())
                                ->addComment($message)
                                ->register();

                            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                                $orderItem = $creditmemoItem->getOrderItem();

                                if (!$orderItem->getParentItemId()) {
                                    $creditmemoItem->setBackToStock(true);
                                }
                            }

                            $transactionSave->addObject($invoice);
                            $transactionSave->addObject($creditmemo);
                        }
                    } else {
                        $creditmemo = $service->prepareCreditmemo()
                            ->setOfflineRequested(false)
                            ->addComment($message)
                            ->register();

                        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                            $orderItem = $creditmemoItem->getOrderItem();

                            if (!$orderItem->getParentItemId()) {
                                $creditmemoItem->setBackToStock(true);
                            }
                        }

                        $transactionSave->addObject($creditmemo);
                    }
                }

                $transactionSave->addObject($order)
                    ->addObject($payment)
                    ->addObject($transacao)
                    ->save();

                break;
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $tid
     * @param $transactionType
     * @param array $transactionDetails
     * @param bool $message
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    protected function _addTransaction(
        Mage_Sales_Model_Order_Payment $payment,
        $tid,
        $transactionType,
        array $transactionDetails = array(),
        $message = false
    ) {
        $payment->setTransactionId($tid);

        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }

        $transaction = $payment->addTransaction($transactionType, null, false, $message);

        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }

        $payment->unsLastTransId();

        if ($transaction) {
            $transaction->setMessage($message);
        }

        return $transaction;
    }

    /**
     * @param $order
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _initInvoice($order)
    {
        if (!$order->getId()) {
            return false;
        }

        if (!$order->canInvoice()) {
            return false;
        }

        $qtys = array();

        foreach ($order->getAllItems() as $item) {
            $qtys[$item->getId()] = $item->getQtyOrdered();
        }

        $invoice = $order->prepareInvoice($qtys);

        if (!$invoice->getTotalQty()) {
            Mage::throwException($this->_helper()->__('Cannot create an invoice without products.'));
        }

        return $invoice;
    }

    /**
     * @param $order
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _getCustomer($order)
    {
        return Mage::getModel('customer/customer')->load($order->getCustomerId());
    }

    /**
     * @param $order
     *
     * @return mixed
     */
    protected function _getAddress($order)
    {
        if ($order->getIsVirtual()) {
            return $order->getBillingAddress();
        }

        return $order->getShippingAddress();
    }

    /**
     * @param $ccType
     *
     * @return string
     */
    protected function _getCardBrand($ccType)
    {
        $cardBrand = '';

        switch ($ccType) {
            case 'AMX':
                $cardBrand = 'Amex';
                break;
            case 'BNC':
                $cardBrand = 'Banescard';
                break;
            case 'CB':
                $cardBrand = 'Cabal';
                break;
            case 'CS':
                $cardBrand = 'CredSystem';
                break;
            case 'CZ':
                $cardBrand = 'Credz';
                break;
            case 'DC':
                $cardBrand = 'Diners Club';
                break;
            case 'ELO':
                $cardBrand = 'Elo';
                break;
            case 'HP':
                $cardBrand = 'Hiper';
                break;
            case 'HPC':
                $cardBrand = 'Hipercard';
                break;
            case 'JCB':
                $cardBrand = 'JCB';
                break;
            case 'MC':
                $cardBrand = 'MasterCard';
                break;
            case 'SC':
                $cardBrand = 'Sorocred';
                break;
            case 'VI':
                $cardBrand = 'Visa';
                break;
        }

        return $cardBrand;
    }

    /**
     * @param $payment
     * @param bool $type
     *
     * @return bool
     */
    protected function _getLastTransaction($payment, $type = false)
    {
        if ($payment->getId()) {
            $collection = Mage::getModel('sales/order_payment_transaction')->getCollection();
            if ($type) {
                $collection->addTxnTypeFilter($type);
            }
            $collection->setOrderFilter($payment->getOrder())
                ->addPaymentIdFilter($payment->getId())
                ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC)
                ->setOrder('transaction_id', Varien_Data_Collection::SORT_ORDER_DESC);
            foreach ($collection as $txn) {
                $txn->setOrderPaymentObject($payment);
                return $txn;
            }
        }

        return false;
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
     * @param $transactionStatus
     *
     * @return int|null
     */
    protected function _mapTransactionStatus($transactionStatus)
    {
        $status = null;
        switch ($transactionStatus) {
            case 'Approved':
                $status = Rede_Adquirencia_Model_Transacoes_Status::APPROVED;
                break;
            case 'Denied':
                $status = Rede_Adquirencia_Model_Transacoes_Status::DENIED;
                break;
            case 'Canceled':
                $status = Rede_Adquirencia_Model_Transacoes_Status::CANCELED;
                break;
            case 'Pending':
                $status = Rede_Adquirencia_Model_Transacoes_Status::PENDING;
                break;
        }
        return $status;
    }
}
