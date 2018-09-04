<?php

/**
 * Class Rede_Adquirencia_Model_System_Config_Installments
 */
class Rede_Adquirencia_Model_System_Config_Installments
{
    protected $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (empty($this->_options)) {
            for ($y = 1; $y <= 12; $y++) {
                $this->_options[] = array(
                    'value' => $y,
                    'label' => $y . 'x',
                );
            }
        }

        return $this->_options;
    }
}