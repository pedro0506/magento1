<?php

/**
 * Class Rede_Adquirencia_Model_Resource_Transacoes
 */
class Rede_Adquirencia_Model_Resource_Transacoes extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('rede_adquirencia/transacoes', 'id');
    }

    /**
     * @param Mage_Sales_Model_Resource_Order_Collection $collection
     *
     * @return $this
     */
    public function appendTransactionInfoToOrderCollection(Mage_Sales_Model_Resource_Order_Collection &$collection)
    {
        $collection->getSelect()
            ->joinLeft(
                array('transacoes' => $this->getMainTable()),
                'transacoes.order_id = main_table.entity_id',
                array(
                    'rede_tid' => 'tid',
                    'rede_environment' => 'environment'
                )
            );

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function appendTransactionInfoToOrder(Mage_Sales_Model_Order &$order)
    {
        if (!$order->getId()) {
            return $this;
        }

        $bind = array(
            ':order_id' => $order->getId()
        );

        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(
                $this->getMainTable(), array(
                    'rede_tid' => 'tid',
                'rede_environment' => 'environment'
                )
            )
            ->where('order_id = :order_id');

        $result = $read->fetchRow($select, $bind);

        if (!$result) {
            return $this;
        }

        $order->addData($result);

        return $this;
    }

    /**
     * @param Rede_Adquirencia_Model_Resource_Transacoes_Collection $collection
     *
     * @return $this
     */
    public function appendRealOrderIdToTransactionCollection(
        Rede_Adquirencia_Model_Resource_Transacoes_Collection &$collection
    ) {
        $collection->getSelect()
            ->join(
                array('o' => 'sales_flat_order'),
                'o.entity_id = main_table.order_id',
                array(
                    'real_order_id' => 'o.increment_id',
                )
            );

        return $this;
    }
}
