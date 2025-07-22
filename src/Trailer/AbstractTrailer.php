<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Trailer;

use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * PDF file trailer
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
abstract class AbstractTrailer
{
    private static array $_allowedKeys = ['Size', 'Prev', 'Root', 'Encrypt', 'Info', 'ID', 'Index', 'W', 'XRefStm', 'DocChecksum'];

    /**
     * Trailer dictionary.
     */
    private \LaminasPdf\InternalType\DictionaryObject $_dict;

    /**
     * Check if key is correct
     *
     * @param string $key
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private function _checkDictKey($key): void
    {
        if (!in_array($key, self::$_allowedKeys)) {
            /** @todo Make warning (log entry) instead of an exception */
            throw new Exception\CorruptedPdfException("Unknown trailer dictionary key: '$key'.");
        }
    }


    /**
     * Object constructor
     *
     * @param \LaminasPdf\InternalType\DictionaryObject $dict
     */
    public function __construct(InternalType\DictionaryObject $dict)
    {
        $this->_dict = $dict;

        foreach ($this->_dict->getKeys() as $dictKey) {
            $this->_checkDictKey($dictKey);
        }
    }

    /**
     * Get handler
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->_dict->$property;
    }

    /**
     * Set handler
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        $this->_checkDictKey($property);
        $this->_dict->$property = $value;
    }

    /**
     * Return string trailer representation
     *
     * @return string
     */
    public function toString()
    {
        return "trailer\n" . $this->_dict->toString() . "\n";
    }


    /**
     * Get length of source PDF
     *
     * @return string
     */
    abstract public function getPDFLength();

    /**
     * Get PDF String
     *
     * @return string
     */
    abstract public function getPDFString();

    /**
     * Get header of free objects list
     * Returns object number of last free object
     *
     * @return integer
     */
    abstract public function getLastFreeObject();
}
