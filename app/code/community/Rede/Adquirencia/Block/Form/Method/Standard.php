<?php

/**
 * Class Rede_Adquirencia_Block_Form_Method_Standard
 */
class Rede_Adquirencia_Block_Form_Method_Standard extends Mage_Payment_Block_Form_Cc
{
    private $_helper = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rede/adquirencia/form/method/standard.phtml');
    }

    /**
     * @return mixed
     */
    public function getGrandTotal()
    {
        return Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();
    }

    /**
     * @return array
     */
    public function getInstallments()
    {
        $installments = array();
        $installmentsAmount = $this->_getHelper()->getConfigInstallmentsAmount();
        $installmentsMinOrderValue = $this->_getHelper()->getConfigInstallmentsMinOrderValue();
        $installmentsMinParcelValue = $this->_getHelper()->getConfigInstallmentsMinParcelValue();
        $grandTotal = $this->getGrandTotal();

        $installments[1] = $this->_getHelper()->quoteEscape($this->_getHelper()->__('R$ %0.2f in cash', $grandTotal));

        if ($installmentsMinOrderValue > $grandTotal) {
            return $installments;
        }

        for ($i = 2; $i <= $installmentsAmount; $i++) {
            $installmentValue = ceil(100 * $grandTotal / $i) / 100;

            if ($installmentsMinParcelValue > $installmentValue) {
                break;
            }

            $installments[$i] = $this->_getHelper()->quoteEscape(
                $this->_getHelper()->__(
                    '%dx of R$ %0.2f - Total R$ %0.2f',
                    $i, $installmentValue, $grandTotal
                )
            );
        }

        return $installments;
    }

    /**
     * @return bool
     */
    public function getIsAutomaticCapture()
    {
        return $this->_getHelper()->getTransactionType() === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * @return string
     */
    public function getCcBrandImagesBaseUrl()
    {
        return Mage::getDesign()->getSkinBaseUrl(
                array(
                    '_package' => 'base',
                '_theme' => 'default'
            )
        ) . 'rede/adquirencia/images/';
    }

    /**
     * @return string
     */
    public function getBrandsImage()
    {
        return $this->getCcBrandImagesBaseUrl() . 'rede.jpg';
            //$this->getCcBrandImagesBaseUrl() . ($this->getIsAutomaticCapture() ? 'rede.png' : 'rede2.png');
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
