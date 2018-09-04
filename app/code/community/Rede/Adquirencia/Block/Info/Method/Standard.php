<?php

/**
 * Class Rede_Adquirencia_Block_Info_Method_Standard
 */
class Rede_Adquirencia_Block_Info_Method_Standard extends Mage_Payment_Block_Info
{
    protected $_transacao = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/adquirencia/info/method/standard.phtml');
    }

    /**
     * @return null
     */
    public function getTransacao()
    {
        if ($this->_transacao == null) {
            $this->_initByOrder();
        }
        return $this->_transacao;
    }

    /**
     * @return string
     */
    public function getExpirationDate()
    {
        return Mage::helper('rede_adquirencia')->__(
            '%02d/%04d', $this->getTransacao()->getCardExpMonth(),
            $this->getTransacao()->getCardExpYear()
        );
    }

    /**
     * @param Mage_Sales_Model_Order|null $order
     *
     * @return $this
     */
    protected function _initByOrder(Mage_Sales_Model_Order $order = null)
    {
        if (empty($order)) {
            $order = $this->getInfo()->getOrder();
        }

        if ($this->_transacao) {
            return $this;
        }

        $this->_transacao = Mage::getModel('rede_adquirencia/transacoes');
        $this->_transacao->load($order->getId(), 'order_id');

        return $this;
    }

}
