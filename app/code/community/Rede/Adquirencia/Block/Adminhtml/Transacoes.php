<?php

/**
 * Class Rede_Adquirencia_Block_Adminhtml_Transacoes
 */
class Rede_Adquirencia_Block_Adminhtml_Transacoes extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function _construct()
    {
        $this->_blockGroup = 'rede_adquirencia';
        $this->_controller = 'adminhtml_transacoes';
        $this->_headerText = $this->escapeHtml(Mage::helper('rede_adquirencia')->__('Transactions'));

        parent::_construct();
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->_removeButton('add');
        return parent::_prepareLayout();
    }
}