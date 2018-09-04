<?php

/**
 * Class Rede_Adquirencia_Block_Checkout_Error
 */
class Rede_Adquirencia_Block_Checkout_Error extends Rede_Adquirencia_Block_Checkout_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/adquirencia/checkout/error.phtml');
    }
}
