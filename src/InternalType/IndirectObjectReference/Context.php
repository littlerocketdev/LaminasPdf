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

use LaminasPdf\PdfParser;

/**
 * PDF reference object context
 * Reference context is defined by PDF parser and PDF Refernce table
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class Context
{
    /**
     * PDF parser object.
     */
    private \LaminasPdf\PdfParser\DataParser $_stringParser;

    /**
     * Reference table
     */
    private \LaminasPdf\InternalType\IndirectObjectReference\ReferenceTable $_refTable;

    /**
     * Object constructor
     *
     * @param \LaminasPdf\PdfParser\DataParser $parser
     * @param \LaminasPdf\InternalType\IndirectObjectReference\ReferenceTable $refTable
     */
    public function __construct(PdfParser\DataParser $parser, ReferenceTable $refTable)
    {
        $this->_stringParser = $parser;
        $this->_refTable = $refTable;
    }


    /**
     * Context parser
     *
     * @return \LaminasPdf\PdfParser\DataParser
     */
    public function getParser()
    {
        return $this->_stringParser;
    }


    /**
     * Context reference table
     *
     * @return \LaminasPdf\InternalType\IndirectObjectReference\ReferenceTable
     */
    public function getRefTable()
    {
        return $this->_refTable;
    }
}
