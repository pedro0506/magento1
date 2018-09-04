<?php

/**
 * Class Rede_Adquirencia_Block_Checkout_Success
 */
class Rede_Adquirencia_Block_Checkout_Success extends Rede_Adquirencia_Block_Checkout_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/adquirencia/checkout/success.phtml');
    }
}
