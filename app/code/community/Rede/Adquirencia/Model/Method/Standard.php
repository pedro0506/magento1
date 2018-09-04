<?php

/**
 * Class Rede_Adquirencia_Model_Method_Standard
 */
class Rede_Adquirencia_Model_Method_Standard extends Mage_Payment_Model_Method_Cc
{
    const PAYMENT_INFO_INSTALLMENTS = 'cc_installments';
    const PAYMENT_INFO_DOCUMENT = 'cc_document';
    const PAYMENT_INFO_SESSION_ID = 'session_id';

    protected $_code = 'rede_adquirencia';
    protected $_formBlockType = 'rede_adquirencia/form_method_standard';
    protected $_infoBlockType = 'rede_adquirencia/info_method_standard';

    protected $_isGateway = false;
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;

    protected $_helper = null;

    /**
     * @param mixed $data
     *
     * @return Mage_Payment_Model_Info
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        $result = parent::assignData($data);
        $installments = self::PAYMENT_INFO_INSTALLMENTS;
        $document = self::PAYMENT_INFO_DOCUMENT;
        $sessionId = self::PAYMENT_INFO_SESSION_ID;

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation($installments, isset($data[$installments]) ? $data[$installments] : null);
            $this->getInfoInstance()->setAdditionalInformation($document, isset($data[$document]) ? $data[$document] : null);
            $this->getInfoInstance()->setAdditionalInformation($sessionId, isset($data[$sessionId]) ? $data[$sessionId] : null);
        } elseif ($data instanceof Varien_Object) {
            $this->getInfoInstance()->setAdditionalInformation($installments, $data->getData($installments));
            $this->getInfoInstance()->setAdditionalInformation($document, $data->getData($document));
            $this->getInfoInstance()->setAdditionalInformation($sessionId, $data->getData($sessionId));
        }

        return $result;
    }

    /**
     * @param string $field
     * @param null $storeId
     *
     * @return mixed|string
     */
    public function getConfigData($field, $storeId = null)
    {
        if ($field === 'order_status') {
            $transactionType = $this->getConfigData('transaction_type');
            if ($transactionType === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                return Mage_Sales_Model_Order::STATE_PROCESSING;
            } else {
                return Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            }
        } else {
            return parent::getConfigData($field, $storeId);
        }
    }

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Exception
     */
    public function order(Varien_Object $payment, $amount)
    {
        parent::order($payment, $amount);

        try {
            $transactionType = $this->_getHelper()->getTransactionType();

            if ($transactionType === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                $this->capture($payment, $amount);
            } elseif ($transactionType === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
                $this->authorize($payment, $amount);
            }

            $payment->setSkipOrderProcessing(true);
        } catch (\Exception $e) {
            $this->_getHelper()->getSession()->incrementCheckoutAttempts();
            if (!$this->_getHelper()->getIsCheckoutAttemptsExceeded()) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @param bool $capture
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount, $capture = false)
    {
        $processor = Mage::getModel('rede_adquirencia/processor_transacao');
        $processor->authorize($payment, $amount, false);

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $authorizationTransaction = $payment->getAuthorizationTransaction();
        $processor = Mage::getModel('rede_adquirencia/processor_transacao');

        if ($authorizationTransaction) {
            $processor->capture($payment, $amount);
        } else {
            $processor->authorize($payment, $amount, true);
        }

        return $this;
    }

    /**
     * @param Varien_Object $payment
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        parent::void($payment);

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        return $this;
    }

    /**
     * @param Varien_Object $payment
     *
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('adquirencia/checkout/verify');
    }

    /**
     * @return bool
     */
    public function getIsCapture()
    {
        $transactionType = $this->_getHelper()->getTransactionType();
        return $transactionType === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * @return array
     */
    public function getVerificationNumberRegEx()
    {
        $array = array_merge(
            parent::getVerificationRegEx(), array(
                'CZ' => '/^[0-9]{3}$/',
                'DC' => '/^[0-9]{3}$/',
                'HP' => '/^[0-9]{3}$/',
                'HPC' => '/^[0-9]{3}$/')
        );

        return $array;
    }

    /**
     * @return array
     */
    public function getCustomCcBrandsRegEx()
    {
        $array = array(
            'CZ' => '/^63(6[7-9][6-9][0-9]|70[0-3][0-2])[0-9]{10}$/',
            'DC' => '/^(30[0-5][0-9]{11})|(3(6|8)[0-9]{12})$/',
            'HP' => '/^(637(095|612|599|609))[0-9]{10}$/',
            'HPC' => '/^606282[0-9]{10}$/',
            'JCB' => '/^((35670[0468][0-9]{10})([0-9]{1,3})?)|((35((2[8-9]|3[0-2]|[4-6][0-9]|8[0-9])[0-9][0-9])[0-9]{10})([0-9]{1,3})?)$/',
            'VI' => '/^((4[0-9]{12})([0-9]{0,3})?)$/',
            'MC' => '/^5[1-5][0-9]{14}$/'
        );

        return $array;
    }

    /**
     * @return Mage_Payment_Model_Abstract|void
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        $info = $this->getInfoInstance();

        // validate credit card number against Luhn algorithm
        $info->setCcNumber(str_replace(' ', '', $info->getCcNumber()));
        if (!$this->validateCcNum($info->getCcNumber())) {
            Mage::throwException($this->_getHelper()->__('Invalid Credit Card Number'));
        }

        if (!preg_match('/^[0-9]{3,4}$/', $info->getCcCid())) {
            Mage::throwException($this->_getHelper()->__('Please enter a valid credit card verification number.'));
        }

        if (!preg_match('/(?=^[A-Z]+ [A-Z ]+$)/', $info->getCcOwner())) {
            Mage::throwException($this->_getHelper()->__('Please enter a valid cardholder first and last name.'));
        }

        if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            Mage::throwException($this->_getHelper()->__('Incorrect credit card expiration date.'));
        }

        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }
    }

    /**
     * @return Mage_Core_Helper_Abstract|Mage_Payment_Helper_Data|null
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('rede_adquirencia');
        }
        return $this->_helper;
    }
}
