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

use LaminasPdf as Pdf;
use LaminasPdf\InternalType;

/**
 * PDF file trailer generator (used for just created PDF)
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class Generated extends AbstractTrailer
{
    /**
     * Object constructor
     *
     * @param \LaminasPdf\InternalType\DictionaryObject $dict
     */
    public function __construct(InternalType\DictionaryObject $dict)
    {
        parent::__construct($dict);
    }

    /**
     * Get length of source PDF
     *
     * @return string
     */
    public function getPDFLength(): int
    {
        return strlen(Pdf\PdfDocument::PDF_HEADER);
    }

    /**
     * Get PDF String
     *
     * @return string
     */
    public function getPDFString(): string
    {
        return Pdf\PdfDocument::PDF_HEADER;
    }

    /**
     * Get header of free objects list
     * Returns object number of last free object
     *
     * @return integer
     */
    public function getLastFreeObject(): int
    {
        return 0;
    }
}
