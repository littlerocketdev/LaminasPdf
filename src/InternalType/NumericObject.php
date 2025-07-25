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
use LaminasPdf\Exception;

/**
 * PDF file 'numeric' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class NumericObject extends AbstractTypeObject
{
    /**
     * Object value
     *
     * @var numeric
     */
    public $value;


    /**
     * Object constructor
     *
     * @param numeric $val
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($val)
    {
        if (!is_numeric($val)) {
            throw new Exception\RuntimeException('Argument must be numeric');
        }

        $this->value = $val;
    }


    /**
     * Return type of the element.
     *
     * @return integer
     */
    public function getType(): int
    {
        return AbstractTypeObject::TYPE_NUMERIC;
    }


    /**
     * Return object as string
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null): string
    {
        if (is_integer($this->value)) {
            return (string)$this->value;
        }

        /**
         * PDF doesn't support exponental format.
         * Fixed point format must be used instead
         */
        $prec = 0;
        $v = $this->value;
        while (abs(floor($v) - $v) > 1e-10) {
            $prec++;
            $v *= 10;
        }
        return sprintf("%.{$prec}F", $this->value);
    }
}
