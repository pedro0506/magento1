<?php

/**
 * Class Rede_Adquirencia_Model_Session
 */
class Rede_Adquirencia_Model_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->init('rede_adquirencia');
    }

    /**
     * @return $this
     */
    public function incrementCheckoutAttempts()
    {
        $attempts = $this->getData('rede_adquirencia_checkout_attempts');
        if (empty($attempts)) {
            $attempts = 0;
        }
        $this->setData('rede_adquirencia_checkout_attempts', ($attempts + 1));

        return $this;
    }

    /**
     * @return int|mixed
     */
    public function getCheckoutAttempts()
    {
        $attempts = $this->getData('rede_adquirencia_checkout_attempts');
        if (empty($attempts)) {
            $attempts = 0;
        }

        return $attempts;
    }

    /**
     * @return $this
     */
    public function resetCheckoutAttempts()
    {
        $this->unsetData('rede_adquirencia_checkout_attempts');

        return $this;
    }
}
