<?php

/**
 * Class Rede_Adquirencia_Block_Adminhtml_Transacoes_Grid
 */
class Rede_Adquirencia_Block_Adminhtml_Transacoes_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {
        parent::_construct();

        $this->setDefaultSort('order_id');
        $this->setId('rede_adquirencia_transacoes_grid');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(false);
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('rede_adquirencia/transacoes_collection');
        Mage::getResourceModel('rede_adquirencia/transacoes')->appendRealOrderIdToTransactionCollection($collection);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('rede_adquirencia');
        $this->addColumn(
            'order_id',
            array(
                'header' => $helper->__('Order'),
                'align' => 'left',
                'width' => '100px',
                'index' => 'real_order_id',
                'filter_index' => 'o.increment_id'
            )
        )->addColumn(
            'tid',
            array(
                'header' => $helper->__('TID'),
                'align' => 'center',
                'width' => '100px',
                'index' => 'tid'
            )
        )->addColumn(
            'created_date',
            array(
                'header' => $helper->__('Date/Hour'),
                'align' => 'left',
                'width' => '100px',
                'type' => 'datetime',
                'index' => 'created_date'
            )
        )->addColumn(
            'transaction_status',
            array(
                'header' => $helper->__('Status'),
                'align' => 'left',
                'width' => '200px',
                'index' => 'transaction_status',
                'type' => 'options',
                'options' => Mage::getModel('rede_adquirencia/transacoes_status')->toArray(),
                'filter_index' => 'main_table.transaction_status'
            )
        )->addColumn(
            'return_message',
            array(
                'header' => $helper->__('Message'),
                'align' => 'left',
                'index' => 'return_message'
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * @param $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }
}