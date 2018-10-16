<?php
require_once Mage::getBaseDir('lib') . '/autoload.php';

class Rede_Adquirencia_Model_Logger implements Psr\Log\LoggerInterface
{

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function emergency($message, array $context = array())
    {
        Mage::log($message, 0);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function alert($message, array $context = array())
    {
        Mage::log($message, 1);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function critical($message, array $context = array())
    {
        Mage::log($message, 2);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function error($message, array $context = array())
    {
        Mage::log($message, 3);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function warning($message, array $context = array())
    {
        Mage::log($message, 4);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function notice($message, array $context = array())
    {
        Mage::log($message, 5);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function info($message, array $context = array())
    {
        Mage::log($message, 6);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function debug($message, array $context = array())
    {
        Mage::log($message, 7);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null|void
     */
    public function log($level, $message, array $context = array())
    {
        Mage::log($message, $level);
    }
}