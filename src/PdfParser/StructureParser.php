<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\PdfParser;

use LaminasPdf as Pdf;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;
use LaminasPdf\InternalType\IndirectObjectReference;
use LaminasPdf\Trailer;

/**
 * PDF file parser
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class StructureParser
{
    /**
     * String parser
     */
    private ?\LaminasPdf\PdfParser\DataParser $_stringParser = null;

    /**
     * Last PDF file trailer
     *
     * @var \LaminasPdf\Trailer\Parsed
     */
    private $_trailer;

    /**
     * PDF version specified in the file header
     */
    private string $_pdfVersion;


    /**
     * Get length of source PDF
     *
     * @return integer
     */
    public function getPDFLength(): int
    {
        return strlen($this->_stringParser->data);
    }

    /**
     * Get PDF String
     *
     * @return string
     */
    public function getPDFString()
    {
        return $this->_stringParser->data;
    }

    /**
     * PDF version specified in the file header
     *
     * @return string
     */
    public function getPDFVersion()
    {
        return $this->_pdfVersion;
    }

    /**
     * Load XReference table and referenced objects
     *
     * @param integer $offset
     * @return \LaminasPdf\Trailer\Parsed
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private function _loadXRefTable($offset): \LaminasPdf\Trailer\Parsed
    {
        $this->_stringParser->offset = $offset;

        $refTable = new IndirectObjectReference\ReferenceTable();
        $context = new IndirectObjectReference\Context($this->_stringParser, $refTable);
        $this->_stringParser->setContext($context);

        $nextLexeme = $this->_stringParser->readLexeme();
        if ($nextLexeme == 'xref') {
            /**
             * Common cross-reference table
             */
            $this->_stringParser->skipWhiteSpace();
            while (($nextLexeme = $this->_stringParser->readLexeme()) != 'trailer') {
                if (!ctype_digit($nextLexeme)) {
                    throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X. Cross-reference table subheader values must contain only digits.', $this->_stringParser->offset - strlen($nextLexeme)));
                }
                $objNum = (int)$nextLexeme;

                $refCount = $this->_stringParser->readLexeme();
                if (!ctype_digit($refCount)) {
                    throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X. Cross-reference table subheader values must contain only digits.', $this->_stringParser->offset - strlen($refCount)));
                }

                $this->_stringParser->skipWhiteSpace();
                while ($refCount > 0) {
                    $objectOffset = substr($this->_stringParser->data, $this->_stringParser->offset, 10);
                    if (!ctype_digit($objectOffset)) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Offset must contain only digits.', $this->_stringParser->offset));
                    }
                    // Force $objectOffset to be treated as decimal instead of octal number
                    for ($numStart = 0; $numStart < strlen($objectOffset) - 1; $numStart++) {
                        if ($objectOffset[$numStart] != '0') {
                            break;
                        }
                    }
                    $objectOffset = substr($objectOffset, $numStart);
                    $this->_stringParser->offset += 10;

                    if (strpos("\x00\t\n\f\r ", $this->_stringParser->data[$this->_stringParser->offset]) === false) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_stringParser->offset));
                    }
                    $this->_stringParser->offset++;

                    $genNumber = substr($this->_stringParser->data, $this->_stringParser->offset, 5);
                    if (!ctype_digit($objectOffset)) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Offset must contain only digits.', $this->_stringParser->offset));
                    }
                    // Force $objectOffset to be treated as decimal instead of octal number
                    for ($numStart = 0; $numStart < strlen($genNumber) - 1; $numStart++) {
                        if ($genNumber[$numStart] != '0') {
                            break;
                        }
                    }
                    $genNumber = substr($genNumber, $numStart);
                    $this->_stringParser->offset += 5;

                    if (strpos("\x00\t\n\f\r ", $this->_stringParser->data[$this->_stringParser->offset]) === false) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_stringParser->offset));
                    }
                    $this->_stringParser->offset++;

                    $inUseKey = $this->_stringParser->data[$this->_stringParser->offset];
                    $this->_stringParser->offset++;

                    switch ($inUseKey) {
                        case 'f':
                            // free entry
                            unset($this->_refTable[$objNum . ' ' . $genNumber . ' R']);
                            $refTable->addReference(
                                $objNum . ' ' . $genNumber . ' R',
                                $objectOffset,
                                false
                            );
                            break;

                        case 'n':
                            // in-use entry

                            $refTable->addReference(
                                $objNum . ' ' . $genNumber . ' R',
                                $objectOffset,
                                true
                            );
                    }

                    if (!DataParser::isWhiteSpace(ord($this->_stringParser->data[$this->_stringParser->offset]))) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_stringParser->offset));
                    }
                    $this->_stringParser->offset++;
                    if (!DataParser::isWhiteSpace(ord($this->_stringParser->data[$this->_stringParser->offset]))) {
                        throw new Exception\CorruptedPdfException(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_stringParser->offset));
                    }
                    $this->_stringParser->offset++;

                    $refCount--;
                    $objNum++;
                }
            }

            $trailerDictOffset = $this->_stringParser->offset;
            $trailerDict = $this->_stringParser->readElement();
            if (!$trailerDict instanceof InternalType\DictionaryObject) {
                throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X.  Dictionary expected after \'trailer\' keyword.', $trailerDictOffset));
            }
        } else {
            $xrefStream = $this->_stringParser->getObject($offset, $context);

            if (!$xrefStream instanceof InternalType\StreamObject) {
                throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X.  Cross-reference stream expected.', $offset));
            }

            $trailerDict = $xrefStream->dictionary;
            if ($trailerDict->Type->value != 'XRef') {
                throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X.  Cross-reference stream object must have /Type property assigned to /XRef.', $offset));
            }
            if ($trailerDict->W === null || $trailerDict->W->getType() != InternalType\AbstractTypeObject::TYPE_ARRAY) {
                throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X. Cross reference stream dictionary doesn\'t have W entry or it\'s not an array.', $offset));
            }

            $entryField1Size = $trailerDict->W->items[0]->value;
            $entryField2Size = $trailerDict->W->items[1]->value;
            $entryField3Size = $trailerDict->W->items[2]->value;

            if ($entryField2Size == 0 || $entryField3Size == 0) {
                throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X. Wrong W dictionary entry. Only type field of stream entries has default value and could be zero length.', $offset));
            }

            $xrefStreamData = $xrefStream->value;

            if ($trailerDict->Index !== null) {
                if ($trailerDict->Index->getType() != InternalType\AbstractTypeObject::TYPE_ARRAY) {
                    throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Offset - 0x%X. Cross reference stream dictionary Index entry must be an array.', $offset));
                }
                $sections = (is_countable($trailerDict->Index->items) ? count($trailerDict->Index->items) : 0) / 2;
            } else {
                $sections = 1;
            }

            $streamOffset = 0;

            $size = $entryField1Size + $entryField2Size + $entryField3Size;
            $entries = strlen($xrefStreamData) / $size;

            for ($count = 0; $count < $sections; $count++) {
                if ($trailerDict->Index !== null) {
                    $objNum = $trailerDict->Index->items[$count * 2]->value;
                    $entries = $trailerDict->Index->items[$count * 2 + 1]->value;
                } else {
                    $objNum = 0;
                    $entries = $trailerDict->Size->value;
                }

                for ($count2 = 0; $count2 < $entries; $count2++) {
                    if ($entryField1Size == 0) {
                        $type = 1;
                    } elseif ($entryField1Size == 1) { // Optimyze one-byte field case
                        $type = ord($xrefStreamData[$streamOffset++]);
                    } else {
                        $type = DataParser::parseIntFromStream($xrefStreamData, $streamOffset, $entryField1Size);
                        $streamOffset += $entryField1Size;
                    }

                    if ($entryField2Size == 1) { // Optimyze one-byte field case
                        $field2 = ord($xrefStreamData[$streamOffset++]);
                    } else {
                        $field2 = DataParser::parseIntFromStream($xrefStreamData, $streamOffset, $entryField2Size);
                        $streamOffset += $entryField2Size;
                    }

                    if ($entryField3Size == 1) { // Optimyze one-byte field case
                        $field3 = ord($xrefStreamData[$streamOffset++]);
                    } else {
                        $field3 = DataParser::parseIntFromStream($xrefStreamData, $streamOffset, $entryField3Size);
                        $streamOffset += $entryField3Size;
                    }

                    switch ($type) {
                        case 0:
                            // Free object
                            $refTable->addReference($objNum . ' ' . $field3 . ' R', $field2, false);
                            // Debug output:
                            // echo "Free object - $objNum $field3 R, next free - $field2\n";
                            break;

                        case 1:
                            // In use object
                            $refTable->addReference($objNum . ' ' . $field3 . ' R', $field2, true);
                            // Debug output:
                            // echo "In-use object - $objNum $field3 R, offset - $field2\n";
                            break;

                        case 2:
                            // Object in an object stream
                            // Debug output:
                            // echo "Compressed object - $objNum 0 R, object stream - $field2 0 R, offset - $field3\n";
                            break;
                    }

                    $objNum++;
                }
            }

            // $streamOffset . ' ' . strlen($xrefStreamData) . "\n";
            // "$entries\n";
            throw new Exception\NotImplementedException('Cross-reference streams are not supported yet.');
        }


        $trailerObj = new Trailer\Parsed($trailerDict, $context);
        if (
            $trailerDict->Prev instanceof InternalType\NumericObject ||
            $trailerDict->Prev instanceof InternalType\IndirectObjectReference
        ) {
            $trailerObj->setPrev($this->_loadXRefTable($trailerDict->Prev->value));
            $context->getRefTable()->setParent($trailerObj->getPrev()->getRefTable());
        }

        /**
         * We set '/Prev' dictionary property to the current cross-reference section offset.
         * It doesn't correspond to the actual data, but is true when trailer will be used
         * as a trailer for next generated PDF section.
         */
        $trailerObj->Prev = new InternalType\NumericObject($offset);

        return $trailerObj;
    }


    /**
     * Get Trailer object
     *
     * @return \LaminasPdf\Trailer\Parsed
     */
    public function getTrailer()
    {
        return $this->_trailer;
    }

    /**
     * Object constructor
     *
     * Note: PHP duplicates string, which is sent by value, only of it's updated.
     * Thus we don't need to care about overhead
     *
     * @param mixed $source
     * @param \LaminasPdf\ObjectFactory $factory
     * @param boolean $load
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($source, Pdf\ObjectFactory $factory, $load)
    {
        if ($load) {
            if (($pdfFile = @fopen($source, 'rb')) === false) {
                throw new Exception\IOException("Can not open '$source' file for reading.");
            }

            $byteCount = filesize($source);

            $data = fread($pdfFile, $byteCount);
            $byteCount -= strlen($data);
            while ($byteCount > 0 && ($nextBlock = fread($pdfFile, $byteCount)) != false) {
                $data .= $nextBlock;
                $byteCount -= strlen($nextBlock);
            }
            fclose($pdfFile);

            $this->_stringParser = new DataParser($data, $factory);
        } else {
            $this->_stringParser = new DataParser($source, $factory);
        }

        $pdfVersionComment = $this->_stringParser->readComment();
        if (substr($pdfVersionComment, 0, 5) != '%PDF-') {
            throw new Exception\CorruptedPdfException('File is not a PDF.');
        }

        $pdfVersion = substr($pdfVersionComment, 5);
        if (
            version_compare($pdfVersion, '0.9', '<') ||
            version_compare($pdfVersion, '1.61', '>=')
        ) {
            /**
             * @todo
             * To support PDF versions 1.5 (Acrobat 6) and PDF version 1.7 (Acrobat 7)
             * Stream compression filter must be implemented (for compressed object streams).
             * Cross reference streams must be implemented
             */
            throw new Exception\NotImplementedException(sprintf('Unsupported PDF version. LaminasPdf supports PDF 1.0-1.4. Current version - \'%f\'', $pdfVersion));
        }
        $this->_pdfVersion = $pdfVersion;

        $this->_stringParser->offset = strrpos($this->_stringParser->data, '%%EOF');
        if (
            $this->_stringParser->offset === false ||
            strlen($this->_stringParser->data) - $this->_stringParser->offset > 7
        ) {
            throw new Exception\CorruptedPdfException('PDF file syntax error. End-of-fle marker expected at the end of file.');
        }

        $this->_stringParser->offset--;
        /**
         * Go to end of cross-reference table offset
         */
        while (
            DataParser::isWhiteSpace(ord($this->_stringParser->data[$this->_stringParser->offset])) &&
            ($this->_stringParser->offset > 0)
        ) {
            $this->_stringParser->offset--;
        }
        /**
         * Go to the start of cross-reference table offset
         */
        while (
            (!DataParser::isWhiteSpace(ord($this->_stringParser->data[$this->_stringParser->offset]))) &&
            ($this->_stringParser->offset > 0)
        ) {
            $this->_stringParser->offset--;
        }
        /**
         * Go to the end of 'startxref' keyword
         */
        while (
            DataParser::isWhiteSpace(ord($this->_stringParser->data[$this->_stringParser->offset])) &&
            ($this->_stringParser->offset > 0)
        ) {
            $this->_stringParser->offset--;
        }
        /**
         * Go to the white space (eol marker) before 'startxref' keyword
         */
        $this->_stringParser->offset -= 9;

        $nextLexeme = $this->_stringParser->readLexeme();
        if ($nextLexeme != 'startxref') {
            throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. \'startxref\' keyword expected. Offset - 0x%X.', $this->_stringParser->offset - strlen($nextLexeme)));
        }

        $startXref = $this->_stringParser->readLexeme();
        if (!ctype_digit($startXref)) {
            throw new Exception\CorruptedPdfException(sprintf('PDF file syntax error. Cross-reference table offset must contain only digits. Offset - 0x%X.', $this->_stringParser->offset - strlen($nextLexeme)));
        }

        $this->_trailer = $this->_loadXRefTable($startXref);
        $factory->setObjectCount($this->_trailer->Size->value);
    }


    /**
     * Object destructor
     */
    public function __destruct()
    {
        $this->_stringParser->cleanUp();
    }
}
