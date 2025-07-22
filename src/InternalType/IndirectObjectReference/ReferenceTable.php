<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType\IndirectObjectReference;

use LaminasPdf\Exception;

/**
 * PDF file reference table
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class ReferenceTable
{
    /**
     * Parent reference table
     */
    private ?\LaminasPdf\InternalType\IndirectObjectReference\ReferenceTable $_parent = null;

    /**
     * Free entries
     * 'reference' => next free object number
     */
    private array $_free;

    /**
     * Generation numbers for free objects.
     * Array: objNum => nextGeneration
     */
    private array $_generations;

    /**
     * In use entries
     * 'reference' => offset
     */
    private array $_inuse;

    /**
     * Generation numbers for free objects.
     * Array: objNum => objGeneration
     */
    private array $_usedObjects;


    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->_parent = null;
        $this->_free = [];
        $this->_generations = [];
        $this->_inuse = [];
        $this->_usedObjects = [];
    }


    /**
     * Add reference to the reference table
     *
     * @param string $ref
     * @param integer $offset
     * @param boolean $inuse
     */
    public function addReference($ref, $offset, $inuse = true): void
    {
        $refElements = explode(' ', $ref);
        if (!is_numeric($refElements[0]) || !is_numeric($refElements[1]) || $refElements[2] != 'R') {
            throw new Exception\InvalidArgumentException("Incorrect reference: '$ref'");
        }
        $objNum = (int)$refElements[0];
        $genNum = (int)$refElements[1];

        if ($inuse) {
            $this->_inuse[$ref] = $offset;
            $this->_usedObjects[$objNum] = $objNum;
        } else {
            $this->_free[$ref] = $offset;
            $this->_generations[$objNum] = $genNum;
        }
    }


    /**
     * Set parent reference table
     *
     * @param \LaminasPdf\InternalType\IndirectObjectReference\ReferenceTable $parent
     */
    public function setParent(self $parent): void
    {
        $this->_parent = $parent;
    }


    /**
     * Get object offset
     *
     * @param string $ref
     * @return integer
     */
    public function getOffset($ref)
    {
        if (isset($this->_inuse[$ref])) {
            return $this->_inuse[$ref];
        }

        if (isset($this->_free[$ref])) {
            return null;
        }

        if (isset($this->_parent)) {
            return $this->_parent->getOffset($ref);
        }

        return null;
    }


    /**
     * Get next object from a list of free objects.
     *
     * @param string $ref
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function getNextFree($ref)
    {
        if (isset($this->_inuse[$ref])) {
            throw new Exception\CorruptedPdfException('Object is not free');
        }

        if (isset($this->_free[$ref])) {
            return $this->_free[$ref];
        }

        if (isset($this->_parent)) {
            return $this->_parent->getNextFree($ref);
        }

        throw new Exception\CorruptedPdfException('Object not found.');
    }


    /**
     * Get next generation number for free object
     *
     * @param integer $objNum
     * @return unknown
     */
    public function getNewGeneration($objNum)
    {
        if (isset($this->_usedObjects[$objNum])) {
            throw new Exception\CorruptedPdfException('Object is not free');
        }

        if (isset($this->_generations[$objNum])) {
            return $this->_generations[$objNum];
        }

        if (isset($this->_parent)) {
            return $this->_parent->getNewGeneration($objNum);
        }

        throw new Exception\CorruptedPdfException('Object not found.');
    }
}
