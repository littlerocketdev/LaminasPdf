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
 * PDF file trailer.
 * Stores and provides access to the trailer parced from a PDF file
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class Parsed extends AbstractTrailer
{
    /**
     * Reference context
     */
    private \LaminasPdf\InternalType\IndirectObjectReference\Context $_context;

    /**
     * Previous trailer
     */
    private ?\LaminasPdf\Trailer\AbstractTrailer $_prev;


    /**
     * Object constructor
     *
     * @param \LaminasPdf\InternalType\DictionaryObject $dict
     * @param \LaminasPdf\InternalType\IndirectObjectReference\Context $context
     * @param \LaminasPdf\Trailer\AbstractTrailer $prev
     */
    public function __construct(
        InternalType\DictionaryObject $dict,
        InternalType\IndirectObjectReference\Context $context,
        AbstractTrailer $prev = null
    ) {
        parent::__construct($dict);

        $this->_context = $context;
        $this->_prev = $prev;
    }

    /**
     * Setter for $this->_prev
     *
     * @param \LaminasPdf\Trailer\Parsed $prev
     */
    public function setPrev(Parsed $prev): void
    {
        $this->_prev = $prev;
    }

    /**
     * Getter for $this->_prev
     *
     * @return \LaminasPdf\Trailer\AbstractTrailer
     */
    public function getPrev()
    {
        return $this->_prev;
    }

    /**
     * Get length of source PDF
     *
     * @return string
     */
    public function getPDFLength()
    {
        return $this->_context->getParser()->getLength();
    }

    /**
     * Get PDF String
     *
     * @return string
     */
    public function getPDFString()
    {
        return $this->_context->getParser()->getString();
    }

    /**
     * Get reference table, which corresponds to the trailer.
     * Proxy to the $_context member methad call
     *
     * @return \LaminasPdf\InternalType\IndirectObjectReference\Context
     */
    public function getRefTable()
    {
        return $this->_context->getRefTable();
    }

    /**
     * Get header of free objects list
     * Returns object number of last free object
     *
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function getLastFreeObject()
    {
        try {
            $this->_context->getRefTable()->getNextFree('0 65535 R');
        } catch (Exception\ExceptionInterface $e) {
            if ($e->getMessage() == 'Object not found.') {
                /**
                 * Here is work around for some wrong generated PDFs.
                 * We have not found reference to the header of free object list,
                 * thus we treat it as there are no free objects.
                 */
                return 0;
            }

            throw $e;
        }
    }
}
