<?php

/**
 * Class Rede_Adquirencia_Block_Adminhtml_Transacoes_View
 */
class Rede_Adquirencia_Block_Adminhtml_Transacoes_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $_transacao = null;

    protected $_order = null;

    protected $_helper = null;

    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'rede_adquirencia/adminhtml_transacoes';
        $this->_mode = 'view';

        parent::__construct();

        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('save');
        $this->setId('transacoes_view');
    }

    /**
     * @return mixed|null
     */
    public function getTransacao()
    {
        if (empty($this->_transacao)) {
            $this->_transacao = Mage::registry('current_transacao');
        }
        return $this->_transacao;
    }

    /**
     * @return Mage_Core_Model_Abstract|null
     */
    public function getOrder()
    {
        if (empty($this->_order) && $this->hasTransacao()) {
            $this->_order = Mage::getModel('sales/order')->load($this->getTransacao()->getOrderId());
        }
        return $this->_order;
    }

    /**
     * @return bool
     */
    public function hasTransacao()
    {
        $transacao = $this->getTransacao();
        return !empty($transacao);
    }

    /**
     * @return bool
     */
    public function hasOrder()
    {
        $order = $this->getOrder();
        return !empty($order);
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        if (!$this->hasTransacao()) {
            return '';
        }

        $header = $this->_getHelper()->__(
            'e.Rede Transaction # %s | %s', $this->getTransacao()->getId(), $this->formatDate(
                $this->getTransacao()->getCreatedDate(), 'medium', true
        )
        );
        return $this->escapeHtml($header);
    }

    /**
     * @return string
     */
    public function getInfoText()
    {
        if (!$this->hasTransacao()) {
            return "";
        }

        $info = $this->_getHelper()->__('e.Rede Transaction # %s | Informations', $this->getTransacao()->getId());
        return $this->escapeHtml($info);
    }

    /**
     * @return mixed
     */
    public function getStatusDescription()
    {
        $array = Mage::getModel('rede_adquirencia/transacoes_status')->toArray();
        return $array[$this->getTransacao()->getTransactionStatus()];
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
}