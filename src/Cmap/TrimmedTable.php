<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Cmap;

use LaminasPdf\Exception;

/**
 * Implements the "trimmed table mapping" character map (type 6).
 *
 * This table type is preferred over the {@link \LaminasPdf\Cmap\SegmentToDelta}
 * table when the Unicode characters covered by the font fall into a single
 * contiguous range.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Font
 */
class TrimmedTable extends AbstractCmap
{
    /**** Instance Variables ****/


    /**
     * The starting character code covered by this table.
     * @var integer
     */
    protected $_startCode = 0;

    /**
     * The ending character code covered by this table.
     * @var integer
     */
    protected $_endCode = 0;

    /**
     * Glyph index array. Stores the actual glyph numbers.
     * @var array
     */
    protected $_glyphIndexArray = [];


    /**** Public Interface ****/


    /* Concrete Class Implementation */

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
    public function glyphNumbersForCharacters($characterCodes): array
    {
        $glyphNumbers = [];
        foreach ($characterCodes as $key => $characterCode) {
            if (($characterCode < $this->_startCode) || ($characterCode > $this->_endCode)) {
                $glyphNumbers[$key] = AbstractCmap::MISSING_CHARACTER_GLYPH;
                continue;
            }

            $glyphIndex = $characterCode - $this->_startCode;
            $glyphNumbers[$key] = $this->_glyphIndexArray[$glyphIndex];
        }
        return $glyphNumbers;
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
    public function glyphNumberForCharacter($characterCode)
    {
        if (($characterCode < $this->_startCode) || ($characterCode > $this->_endCode)) {
            return AbstractCmap::MISSING_CHARACTER_GLYPH;
        }
        $glyphIndex = $characterCode - $this->_startCode;
        return $this->_glyphIndexArray[$glyphIndex];
    }

    /**
     * Returns an array containing the Unicode characters that have entries in
     * this character map.
     *
     * @return array Unicode character codes.
     */
    public function getCoveredCharacters(): array
    {
        $characterCodes = [];
        for ($code = $this->_startCode; $code <= $this->_endCode; $code++) {
            $characterCodes[] = $code;
        }
        return $characterCodes;
    }


    /**
     * Returns an array containing the glyphs numbers that have entries in this character map.
     * Keys are Unicode character codes (integers)
     *
     * This functionality is partially covered by glyphNumbersForCharacters(getCoveredCharacters())
     * call, but this method do it in more effective way (prepare complete list instead of searching
     * glyph for each character code).
     *
     * @return array Array representing <Unicode character code> => <glyph number> pairs.
     * @internal
     */
    public function getCoveredCharactersGlyphs(): array
    {
        $glyphNumbers = [];
        for ($code = $this->_startCode; $code <= $this->_endCode; $code++) {
            $glyphNumbers[$code] = $this->_glyphIndexArray[$code - $this->_startCode];
        }

        return $glyphNumbers;
    }


    /* Object Lifecycle */

    /**
     * Object constructor
     *
     * Parses the raw binary table data. Throws an exception if the table is
     * malformed.
     *
     * @param string $cmapData Raw binary cmap table data.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($cmapData)
    {
        /* Sanity check: The table should be at least 9 bytes in size.
         */
        $actualLength = strlen($cmapData);
        if ($actualLength < 9) {
            throw new Exception\CorruptedFontException('Insufficient table data');
        }

        /* Sanity check: Make sure this is right data for this table type.
         */
        $type = $this->_extractUInt2($cmapData, 0);
        if ($type != AbstractCmap::TYPE_TRIMMED_TABLE) {
            throw new Exception\CorruptedFontException('Wrong cmap table type');
        }

        $length = $this->_extractUInt2($cmapData, 2);
        if ($length != $actualLength) {
            throw new Exception\CorruptedFontException("Table length ($length) does not match actual length ($actualLength)");
        }

        /* Mapping tables should be language-independent. The font may not work
         * as expected if they are not. Unfortunately, many font files in the
         * wild incorrectly record a language ID in this field, so we can't
         * call this a failure.
         */
        $language = $this->_extractUInt2($cmapData, 4);
        if ($language != 0) {
            // Record a warning here somehow?
        }

        $this->_startCode = $this->_extractUInt2($cmapData, 6);

        $entryCount = $this->_extractUInt2($cmapData, 8);
        $expectedCount = ($length - 10) >> 1;
        if ($entryCount != $expectedCount) {
            throw new Exception\CorruptedFontException("Entry count is wrong; expected: $expectedCount; actual: $entryCount");
        }

        $this->_endCode = $this->_startCode + $entryCount - 1;

        $offset = 10;
        for ($i = 0; $i < $entryCount; $i++, $offset += 2) {
            $this->_glyphIndexArray[] = $this->_extractUInt2($cmapData, $offset);
        }

        /* Sanity check: After reading all of the data, we should be at the end
         * of the table.
         */
        if ($offset != $length) {
            throw new Exception\CorruptedFontException("Ending offset ($offset) does not match length ($length)");
        }
    }
}
