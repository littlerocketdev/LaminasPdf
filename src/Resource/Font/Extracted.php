<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Resource\Font;

use LaminasPdf as Pdf;
use LaminasPdf\Exception;

/**
 * Extracted fonts implementation
 *
 * Thes class allows to extract fonts already mentioned within PDF document and use them
 * for text drawing.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Fonts
 */
class Extracted extends AbstractFont
{
    /**
     * Messages
     */
    public const TYPE_NOT_SUPPORTED = 'Unsupported font type.';
    public const ENCODING_NOT_SUPPORTED = 'Font encoding is not supported';
    public const OPERATION_NOT_SUPPORTED = 'Operation is not supported for extracted fonts';

    /**
     * Extracted font encoding
     *
     * Only 'Identity-H' and 'WinAnsiEncoding' encodings are supported now
     *
     * @var string
     */
    protected $_encoding = null;

    /**
     * Object constructor
     *
     * $fontDictionary is a \LaminasPdf\InternalType\IndirectObjectReference or
     * \LaminasPdf\InternalType\IndirectObject object
     *
     * @param mixed $fontDictionary
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($fontDictionary)
    {
        // Extract object factory and resource object from font dirctionary object
        $this->_objectFactory = $fontDictionary->getFactory();
        $this->_resource = $fontDictionary;

        if ($fontDictionary->Encoding !== null) {
            $this->_encoding = $fontDictionary->Encoding->value;
        }

        switch ($fontDictionary->Subtype->value) {
            case 'Type0':
                // Composite type 0 font
                if ((is_countable($fontDictionary->DescendantFonts->items) ? count($fontDictionary->DescendantFonts->items) : 0) != 1) {
                    // Multiple descendant fonts are not supported
                    throw new Exception\NotImplementedException(self::TYPE_NOT_SUPPORTED);
                }

                $fontDictionaryIterator = $fontDictionary->DescendantFonts->items->getIterator();
                $fontDictionaryIterator->rewind();
                $descendantFont = $fontDictionaryIterator->current();
                $fontDescriptor = $descendantFont->FontDescriptor;
                break;

            case 'Type1':
                if ($fontDictionary->FontDescriptor === null) {
                    // That's one of the standard fonts
                    $standardFont = Pdf\Font::fontWithName($fontDictionary->BaseFont->value);

                    $this->_fontNames = $standardFont->getFontNames();
                    $this->_isBold = $standardFont->isBold();
                    $this->_isItalic = $standardFont->isItalic();
                    $this->_isMonospace = $standardFont->isMonospace();
                    $this->_underlinePosition = $standardFont->getUnderlinePosition();
                    $this->_underlineThickness = $standardFont->getUnderlineThickness();
                    $this->_strikePosition = $standardFont->getStrikePosition();
                    $this->_strikeThickness = $standardFont->getStrikeThickness();
                    $this->_unitsPerEm = $standardFont->getUnitsPerEm();
                    $this->_ascent = $standardFont->getAscent();
                    $this->_descent = $standardFont->getDescent();
                    $this->_lineGap = $standardFont->getLineGap();

                    return;
                }

                $fontDescriptor = $fontDictionary->FontDescriptor;
                break;

            case 'TrueType':
                $fontDescriptor = $fontDictionary->FontDescriptor;
                break;

            default:
                throw new Exception\NotImplementedException(self::TYPE_NOT_SUPPORTED);
        }

        $this->_fontNames[Pdf\Font::NAME_POSTSCRIPT]['en'] = iconv('UTF-8', 'UTF-16BE', $fontDictionary->BaseFont->value);

        $this->_isBold = false; // this property is actually not used anywhere
        $this->_isItalic = (($fontDescriptor->Flags->value & (1 << 6)) != 0); // Bit-7 is set
        $this->_isMonospace = (($fontDescriptor->Flags->value & (1 << 0)) != 0); // Bit-1 is set
        $this->_underlinePosition = null; // Can't be extracted
        $this->_underlineThickness = null; // Can't be extracted
        $this->_strikePosition = null; // Can't be extracted
        $this->_strikeThickness = null; // Can't be extracted
        $this->_unitsPerEm = null; // Can't be extracted
        $this->_ascent = $fontDescriptor->Ascent->value;
        $this->_descent = $fontDescriptor->Descent->value;
        $this->_lineGap = null; // Can't be extracted
    }

    /**
     * Returns an array of glyph numbers corresponding to the Unicode characters.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumberForCharacter()}.
     *
     * @param array $characterCodes Array of Unicode character codes (code points).
     * @return array Array of glyph numbers.
     */
    public function glyphNumbersForCharacters($characterCodes): never
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the glyph number corresponding to the Unicode character.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumbersForCharacters()} which is optimized for bulk
     * operations.
     *
     * @param integer $characterCode Unicode character code (code point).
     * @return integer Glyph number.
     */
    public function glyphNumberForCharacter($characterCode): never
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns a number between 0 and 1 inclusive that indicates the percentage
     * of characters in the string which are covered by glyphs in this font.
     *
     * Since no one font will contain glyphs for the entire Unicode character
     * range, this method can be used to help locate a suitable font when the
     * actual contents of the string are not known.
     *
     * Note that some fonts lie about the characters they support. Additionally,
     * fonts don't usually contain glyphs for control characters such as tabs
     * and line breaks, so it is rare that you will get back a full 1.0 score.
     * The resulting value should be considered informational only.
     *
     * @param string $string
     * @param string $charEncoding (optional) Character encoding of source text.
     *   If omitted, uses 'current locale'.
     * @return float
     */
    public function getCoveredPercentage($string, $charEncoding = ''): never
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the widths of the glyphs.
     *
     * The widths are expressed in the font's glyph space. You are responsible
     * for converting to user space as necessary. See {@link unitsPerEm()}.
     *
     * See also {@link widthForGlyph()}.
     *
     * @param array $glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function widthsForGlyphs($glyphNumbers): never
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Returns the width of the glyph.
     *
     * Like {@link widthsForGlyphs()} but used for one glyph at a time.
     *
     * @param integer $glyphNumber
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function widthForGlyph($glyphNumber): never
    {
        throw new Exception\NotImplementedException(self::OPERATION_NOT_SUPPORTED);
    }

    /**
     * Convert string to the font encoding.
     *
     * The method is used to prepare string for text drawing operators
     *
     * @param string $string
     * @param string $charEncoding Character encoding of source text.
     * @return string
     */
    public function encodeString($string, $charEncoding): string|false
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv($charEncoding, 'UTF-16BE', $string);
        }

        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv($charEncoding, 'CP1252//IGNORE', $string);
        }

        throw new Exception\CorruptedPdfException(self::ENCODING_NOT_SUPPORTED);
    }

    /**
     * Convert string from the font encoding.
     *
     * The method is used to convert strings retrieved from existing content streams
     *
     * @param string $string
     * @param string $charEncoding Character encoding of resulting text.
     * @return string
     */
    public function decodeString($string, $charEncoding): string|false
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv('UTF-16BE', $charEncoding, $string);
        }

        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv('CP1252', $charEncoding, $string);
        }

        throw new Exception\CorruptedPdfException(self::ENCODING_NOT_SUPPORTED);
    }
}
