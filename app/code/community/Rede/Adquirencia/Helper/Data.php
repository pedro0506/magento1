<?php

/**
 * Class Rede_Adquirencia_Helper_Data
 */
class Rede_Adquirencia_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * @param $field
     *
     * @return mixed
     */
    public function getPaymentConfig($field)
    {
        return Mage::getStoreConfig(implode('/', array('payment', 'rede_adquirencia', $field)));
    }

    /**
     * @return mixed
     */
    public function getConfigReference()
    {
        $reference = (int)$this->getPaymentConfig('reference');

        if (!is_numeric($reference) || $reference < 0) {
            $reference = 0;
        }

        return $reference;
    }

    /**
     * @return mixed
     */
    public function getConfigAffiliation()
    {
        return $this->getPaymentConfig('affiliation');
    }

    /**
     * @return mixed
     */
    public function getConfigToken()
    {
        return $this->getPaymentConfig('password');
    }

    /**
     * @return mixed
     */
    public function getConfigTitle()
    {
        return $this->getPaymentConfig('title');
    }

    /**
     * @return mixed
     */
    public function getTransactionType()
    {
        return $this->getPaymentConfig('transaction_type');
    }

    /**
     * @return int
     */
    public function getEnvironment()
    {
        return (int)$this->getPaymentConfig('environment');
    }

    /**
     * @return int
     */
    public function getConfigInstallmentsAmount()
    {
        return (int)$this->getPaymentConfig('installments_amount');
    }

    /**
     * @return int
     */
    public function getConfigInstallmentsMinOrderValue()
    {
        return (int)$this->getPaymentConfig('installments_min_order_value');
    }

    /**
     * @return int
     */
    public function getConfigInstallmentsMinParcelValue()
    {
        return (int)$this->getPaymentConfig('installments_min_parcel_value');
    }

    /**
     * @return string
     */
    public function getConfigModule()
    {
        return $this->getPaymentConfig('module');
    }

    /**
     * @return string
     */
    public function getConfigGateway()
    {
        return $this->getPaymentConfig('gateway');
    }

    /**
     * @return mixed
     */
    public function getConfigSoftDescription()
    {
        return $this->getPaymentConfig('soft_description');
    }

    /**
     * @return mixed
     */
    public function getConfigAntifraud()
    {
        return $this->getPaymentConfig('antifraud');
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function getSession()
    {
        return Mage::getSingleton('rede_adquirencia/session');
    }

    /**
     * @return bool
     */
    public function getIsCheckoutAttemptsExceeded()
    {
        $attempts = $this->getSession()->getCheckoutAttempts();

        return $attempts >= 2;
    }

    public function clearCheckoutAttempts()
    {
        $this->getSession()->clear();
        $this->getSession()->resetCheckoutAttempts();
    }

    /**
     * @param null $width
     *
     * @return mixed
     */
    public function getLogoBlock($width = null)
    {
        return Mage::app()->getLayout()->createBlock('rede_adquirencia/logo')->setWidth($width);
    }

    /**
     * @param null $width
     *
     * @return mixed
     */
    public function getLogoHtml($width = null)
    {
        return $this->getLogoBlock($width)->toHtml();
    }
}