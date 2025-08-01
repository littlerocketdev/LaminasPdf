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
 * PDF file 'reference' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class IndirectObjectReference extends AbstractTypeObject
{
    /**
     * Object value
     * The reference to the object
     *
     * @var mixed
     */
    private $_ref;

    /**
     * Object number within PDF file
     */
    private int $_objNum;

    /**
     * Generation number
     */
    private int $_genNum;

    /**
     * Reference context
     */
    private \LaminasPdf\InternalType\IndirectObjectReference\Context $_context;


    /**
     * Reference to the factory.
     *
     * It's the same as referenced object factory, but we save it here to avoid
     * unnecessary dereferencing, whech can produce cascade dereferencing and parsing.
     * The same for duplication of getFactory() function. It can be processed by __call()
     * method, but we catch it here.
     */
    private \LaminasPdf\ObjectFactory $_factory;

    /**
     * Object constructor:
     *
     * @param integer $objNum
     * @param integer $genNum
     * @param \LaminasPdf\InternalType\IndirectObjectReference\Context $context
     * @param \LaminasPdf\ObjectFactory $factory
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(
        $objNum,
        $genNum,
        IndirectObjectReference\Context $context,
        Pdf\ObjectFactory $factory
    ) {
        if (!(is_integer($objNum) && $objNum > 0)) {
            throw new Exception\RuntimeException('Object number must be positive integer');
        }
        if (!(is_integer($genNum) && $genNum >= 0)) {
            throw new Exception\RuntimeException('Generation number must be non-negative integer');
        }

        $this->_objNum = $objNum;
        $this->_genNum = $genNum;
        $this->_ref = null;
        $this->_context = $context;
        $this->_factory = $factory;
    }

    /**
     * Check, that object is generated by specified factory
     *
     * @return \LaminasPdf\ObjectFactory
     */
    public function getFactory()
    {
        return $this->_factory;
    }


    /**
     * Return type of the element.
     *
     * @return integer
     */
    public function getType()
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        return $this->_ref->getType();
    }


    /**
     * Return reference to the object
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null): string
    {
        if ($factory === null) {
            $shift = 0;
        } else {
            $shift = $factory->getEnumerationShift($this->_factory);
        }

        return $this->_objNum + $shift . ' ' . $this->_genNum . ' R';
    }


    /**
     * Dereference.
     * Take inderect object, take $value member of this object (must be \LaminasPdf\InternalType\AbstractTypeObject),
     * take reference to the $value member of this object and assign it to
     * $value member of current PDF Reference object
     * $obj can be null
     *
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private function _dereference(): void
    {
        if (($obj = $this->_factory->fetchObject($this->_objNum . ' ' . $this->_genNum)) === null) {
            $obj = $this->_context->getParser()->getObject(
                $this->_context->getRefTable()->getOffset($this->_objNum . ' ' . $this->_genNum . ' R'),
                $this->_context
            );
        }

        if ($obj === null) {
            $this->_ref = new NullObject();
            return;
        }

        if ($obj->toString() != $this->_objNum . ' ' . $this->_genNum . ' R') {
            throw new Exception\RuntimeException('Incorrect reference to the object');
        }

        $this->_ref = $obj;
    }

    /**
     * Detach PDF object from the factory (if applicable), clone it and attach to new factory.
     *
     * @param \LaminasPdf\ObjectFactory $factory The factory to attach
     * @param array &$processed List of already processed indirect objects, used to avoid objects duplication
     * @param integer $mode Cloning mode (defines filter for objects cloning)
     * @returns \LaminasPdf\InternalType\AbstractTypeObject
     */
    public function makeClone(Pdf\ObjectFactory $factory, array &$processed, $mode)
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        // This code duplicates code in \LaminasPdf\InternalType\IndirectObject class,
        // but allows to avoid unnecessary method call in most cases
        $id = spl_object_hash($this->_ref);
        if (isset($processed[$id])) {
            // Do nothing if object is already processed
            // return it
            return $processed[$id];
        }

        return $this->_ref->makeClone($factory, $processed, $mode);
    }

    /**
     * Mark object as modified, to include it into new PDF file segment.
     */
    public function touch(): void
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        $this->_ref->touch();
    }

    /**
     * Return object, which can be used to identify object and its references identity
     *
     * @return \LaminasPdf\InternalType\IndirectObject
     */
    public function getObject()
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        return $this->_ref;
    }

    /**
     * Get handler
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        return $this->_ref->$property;
    }

    /**
     * Set handler
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        $this->_ref->$property = $value;
    }

    /**
     * Call handler
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        return call_user_func_array([$this->_ref, $method], $args);
    }

    /**
     * Clean up resources
     */
    public function cleanUp(): void
    {
        $this->_ref = null;
    }

    /**
     * Convert PDF element to PHP type.
     *
     * @return mixed
     */
    public function toPhp()
    {
        if ($this->_ref === null) {
            $this->_dereference();
        }

        return $this->_ref->toPhp();
    }
}
