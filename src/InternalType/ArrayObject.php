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
 * PDF file 'array' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class ArrayObject extends AbstractTypeObject
{
    /**
     * Array element items
     *
     * Array of \LaminasPdf\InternalType\AbstractTypeObject objects
     *
     * @var array
     */
    public $items;


    /**
     * Object constructor
     *
     * @param array $val - array of \LaminasPdf\InternalType\AbstractTypeObject objects
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($val = null)
    {
        $this->items = new \ArrayObject();

        if ($val !== null && is_array($val)) {
            foreach ($val as $element) {
                if (!$element instanceof AbstractTypeObject) {
                    throw new Exception\RuntimeException('Array elements must be \LaminasPdf\InternalType\AbstractTypeObject objects');
                }
                $this->items[] = $element;
            }
        } elseif ($val !== null) {
            throw new Exception\RuntimeException('Argument must be an array');
        }
    }


    /**
     * Getter
     *
     * @param string $property
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __get(string $property)
    {
        throw new Exception\RuntimeException('Undefined property: \LaminasPdf\InternalType\ArrayObject::$' . $property);
    }


    /**
     * Setter
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __set(string $property, $value)
    {
        throw new Exception\RuntimeException('Undefined property: \LaminasPdf\InternalType\ArrayObject::$' . $property);
    }

    /**
     * Return type of the element.
     *
     * @return integer
     */
    public function getType(): int
    {
        return AbstractTypeObject::TYPE_ARRAY;
    }


    /**
     * Return object as string
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null): string
    {
        $outStr = '[';
        $lastNL = 0;

        foreach ($this->items as $element) {
            if (strlen($outStr) - $lastNL > 128) {
                $outStr .= "\n";
                $lastNL = strlen($outStr);
            }

            $outStr .= $element->toString($factory) . ' ';
        }
        $outStr .= ']';

        return $outStr;
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
        $newArray = new self();

        foreach ($this->items as $key => $value) {
            $newArray->items[$key] = $value->makeClone($factory, $processed, $mode);
        }

        return $newArray;
    }

    /**
     * Set top level parent indirect object.
     *
     * @param \LaminasPdf\InternalType\IndirectObject $parent
     */
    public function setParentObject(IndirectObject $parent): void
    {
        parent::setParentObject($parent);

        foreach ($this->items as $item) {
            $item->setParentObject($parent);
        }
    }

    /**
     * Convert PDF element to PHP type.
     *
     * Dictionary is returned as an associative array
     *
     * @return list
     */
    public function toPhp(): array
    {
        $phpArray = [];

        foreach ($this->items as $item) {
            $phpArray[] = $item->toPhp();
        }

        return $phpArray;
    }
}
