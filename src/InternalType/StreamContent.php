<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType;

use LaminasPdf as Pdf;

/**
 * PDF file 'stream' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class StreamContent extends AbstractTypeObject
{
    /**
     * Object value
     */
    public $value;

    /**
     * Object constructor
     *
     * @param string $val
     */
    public function __construct($val)
    {
        $this->value = $val;
    }

    /**
     * Return type of the element.
     *
     * @return integer
     */
    public function getType(): int
    {
        return AbstractTypeObject::TYPE_STREAM;
    }

    /**
     * Stream length.
     * (Method is used to avoid string copying, which may occurs in some cases)
     *
     * @return integer
     */
    public function length(): int
    {
        return strlen($this->value);
    }

    /**
     * Clear stream
     *
     */
    public function clear(): void
    {
        $this->value = null;
    }

    /**
     * Append value to a stream
     *
     * @param mixed $val
     */
    public function append($val): void
    {
        $this->value .= (string)$val;
    }

    /**
     * Detach PDF object from the factory (if applicable), clone it and attach to new factory.
     *
     * @param \LaminasPdf\ObjectFactory $factory The factory to attach
     * @param array &$processed List of already processed indirect objects, used to avoid objects duplication
     * @param integer $mode Cloning mode (defines filter for objects cloning)
     * @returns \LaminasPdf\InternalType\AbstractTypeObject
     */
    public function makeClone(Pdf\ObjectFactory $factory, array &$processed, $mode): self
    {
        return new self($this->value);
    }

    /**
     * Return object as string
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null): string
    {
        return "stream\n" . $this->value . "\nendstream";
    }
}
