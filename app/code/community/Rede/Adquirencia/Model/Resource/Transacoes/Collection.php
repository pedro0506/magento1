<?php

/**
 * Class Rede_Adquirencia_Model_Resource_Transacoes_Collection
 */
class Rede_Adquirencia_Model_Resource_Transacoes_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('rede_adquirencia/transacoes');
        parent::_construct();
    }

    /**
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);

        return $countSelect;
    }
}
