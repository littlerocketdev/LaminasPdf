<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\BinaryParser;

use LaminasPdf\Exception;

/**
 * Abstract utility class for parsing binary files.
 *
 * Provides a library of methods to quickly navigate and extract various data
 * types (signed and unsigned integers, floating- and fixed-point numbers,
 * strings, etc.) from the file.
 *
 * File access is managed via a {@link \LaminasPdf\BinaryParser\DataSource\AbstractDataSource}
 * object.
 * This allows the same parser code to work with many different data sources:
 * in-memory objects, filesystem files, etc.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\BinaryParser
 */
abstract class AbstractBinaryParser
{
    /**** Class Constants ****/

    /**
     * Little-endian byte order (0x04 0x03 0x02 0x01).
     */
    public const BYTE_ORDER_LITTLE_ENDIAN = 0;

    /**
     * Big-endian byte order (0x01 0x02 0x03 0x04).
     */
    public const BYTE_ORDER_BIG_ENDIAN = 1;


    /**** Instance Variables ****/


    /**
     * Flag indicating that the file has passed a cursory validation check.
     * @var boolean
     */
    protected $_isScreened = false;

    /**
     * Flag indicating that the file has been sucessfully parsed.
     * @var boolean
     */
    protected $_isParsed = false;

    /**
     * Object representing the data source to be parsed.
     * @var \LaminasPdf\BinaryParser\DataSource\AbstractDataSource
     */
    protected $_dataSource = null;


    /**** Public Interface ****/


    /* Abstract Methods */

    /**
     * Performs a cursory check to verify that the binary file is in the expected
     * format. Intended to quickly weed out obviously bogus files.
     *
     * Must set $this->_isScreened to true if successful.
     *
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    abstract public function screen();

    /**
     * Reads and parses the complete binary file.
     *
     * Must set $this->_isParsed to true if successful.
     *
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    abstract public function parse();


    /* Object Lifecycle */

    /**
     * Object constructor.
     *
     * Verifies that the data source has been properly initialized.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(DataSource\AbstractDataSource $dataSource)
    {
        if ($dataSource->getSize() == 0) {
            throw new Exception\BinaryParserException('The data source has not been properly initialized');
        }
        $this->_dataSource = $dataSource;
    }

    /**
     * Object destructor.
     *
     * Discards the data source object.
     */
    public function __destruct()
    {
        $this->_dataSource = null;
    }


    /* Accessors */

    /**
     * Returns true if the file has passed a cursory validation check.
     *
     * @return boolean
     */
    public function isScreened()
    {
        return $this->_isScreened;
    }

    /**
     * Returns true if the file has been successfully parsed.
     *
     * @return boolean
     */
    public function isParsed()
    {
        return $this->_isParsed;
    }

    /**
     * Returns the data source object representing the file being parsed.
     *
     * @return \LaminasPdf\BinaryParser\DataSource\AbstractDataSource
     */
    public function getDataSource()
    {
        return $this->_dataSource;
    }


    /* Primitive Methods */

    /**
     * Convenience wrapper for the data source object's moveToOffset() method.
     *
     * @param integer $offset Destination byte offset.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function moveToOffset($offset): void
    {
        $this->_dataSource->moveToOffset($offset);
    }

    public function getOffset()
    {
        return $this->_dataSource->getOffset();
    }

    public function getSize()
    {
        return $this->_dataSource->getSize();
    }

    /**
     * Convenience wrapper for the data source object's readBytes() method.
     *
     * @param integer $byteCount Number of bytes to read.
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readBytes($byteCount)
    {
        return $this->_dataSource->readBytes($byteCount);
    }

    /**
     * Convenience wrapper for the data source object's skipBytes() method.
     *
     * @param integer $byteCount Number of bytes to skip.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function skipBytes($byteCount): void
    {
        $this->_dataSource->skipBytes($byteCount);
    }


    /* Parser Methods */

    /**
     * Reads the signed integer value from the binary file at the current byte
     * offset.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $size Size of integer in bytes: 1-4
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link \LaminasPdf\BinaryParser\AbstractBinaryParser}.
     *   If omitted, uses big-endian.
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readInt($size, $byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        if (($size < 1) || ($size > 4)) {
            throw new Exception\BinaryParserException("Invalid signed integer size: $size");
        }
        $bytes = $this->_dataSource->readBytes($size);
        /* unpack() will not work for this method because it always works in
         * the host byte order for signed integers. It also does not allow for
         * variable integer sizes.
         */
        if ($byteOrder == self::BYTE_ORDER_BIG_ENDIAN) {
            $number = ord($bytes[0]);
            if (($number & 0x80) == 0x80) {
                /* This number is negative. Extract the positive equivalent.
                 */
                $number = (~$number) & 0xff;
                for ($i = 1; $i < $size; $i++) {
                    $number = ($number << 8) | ((~ord($bytes[$i])) & 0xff);
                }
                /* Now turn this back into a negative number by taking the
                 * two's complement (we didn't add one above so won't
                 * subtract it below). This works reliably on both 32- and
                 * 64-bit systems.
                 */
                $number = ~$number;
            } else {
                for ($i = 1; $i < $size; $i++) {
                    $number = ($number << 8) | ord($bytes[$i]);
                }
            }
        } elseif ($byteOrder == self::BYTE_ORDER_LITTLE_ENDIAN) {
            $number = ord($bytes[$size - 1]);
            if (($number & 0x80) == 0x80) {
                /* Negative number. See discussion above.
                 */
                $number = 0;
                for ($i = --$size; $i >= 0; $i--) {
                    $number |= ((~ord($bytes[$i])) & 0xff) << ($i * 8);
                }
                $number = ~$number;
            } else {
                $number = 0;
                for ($i = --$size; $i >= 0; $i--) {
                    $number |= ord($bytes[$i]) << ($i * 8);
                }
            }
        } else {
            throw new Exception\BinaryParserException("Invalid byte order: $byteOrder");
        }
        return $number;
    }

    /**
     * Reads the unsigned integer value from the binary file at the current byte
     * offset.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * NOTE: If you ask for a 4-byte unsigned integer on a 32-bit machine, the
     * resulting value WILL BE SIGNED because PHP uses signed integers internally
     * for everything. To guarantee portability, be sure to use bitwise operators
     * operators on large unsigned integers!
     *
     * @param integer $size Size of integer in bytes: 1-4
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link \LaminasPdf\BinaryParser\AbstractBinaryParser}.
     *   If omitted, uses big-endian.
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readUInt($size, $byteOrder = self::BYTE_ORDER_BIG_ENDIAN)
    {
        if (($size < 1) || ($size > 4)) {
            throw new Exception\BinaryParserException("Invalid unsigned integer size: $size");
        }
        $bytes = $this->_dataSource->readBytes($size);
        /* unpack() is a bit heavyweight for this simple conversion. Just
         * work the bytes directly.
         */
        if ($byteOrder == self::BYTE_ORDER_BIG_ENDIAN) {
            $number = ord($bytes[0]);
            for ($i = 1; $i < $size; $i++) {
                $number = ($number << 8) | ord($bytes[$i]);
            }
        } elseif ($byteOrder == self::BYTE_ORDER_LITTLE_ENDIAN) {
            $number = 0;
            for ($i = --$size; $i >= 0; $i--) {
                $number |= ord($bytes[$i]) << ($i * 8);
            }
        } else {
            throw new Exception\BinaryParserException("Invalid byte order: $byteOrder");
        }
        return $number;
    }

    /**
     * Returns true if the specified bit is set in the integer bitfield.
     *
     * @param integer $bit Bit number to test (i.e. - 0-31)
     * @param integer $bitField
     * @return boolean
     */
    public function isBitSet($bit, $bitField)
    {
        $bitMask = 1 << $bit;
        $isSet = (($bitField & $bitMask) == $bitMask);
        return $isSet;
    }

    /**
     * Reads the signed fixed-point number from the binary file at the current
     * byte offset.
     *
     * Common fixed-point sizes are 2.14 and 16.16.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $mantissaBits Number of bits in the mantissa
     * @param integer $fractionBits Number of bits in the fraction
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link \LaminasPdf\BinaryParser\AbstractBinaryParser}.
     *   If omitted, uses big-endian.
     * @return float
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readFixed(
        $mantissaBits,
        $fractionBits,
        $byteOrder = self::BYTE_ORDER_BIG_ENDIAN
    ) {
        $bitsToRead = $mantissaBits + $fractionBits;
        if (($bitsToRead % 8) !== 0) {
            throw new Exception\BinaryParserException('Fixed-point numbers are whole bytes');
        }
        $number = $this->readInt(($bitsToRead >> 3), $byteOrder) / (1 << $fractionBits);
        return $number;
    }

    /**
     * Reads the Unicode UTF-16-encoded string from the binary file at the
     * current byte offset.
     *
     * The byte order of the UTF-16 string must be specified. You must also
     * supply the desired resulting character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $byteCount Number of bytes (characters * 2) to return.
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link \LaminasPdf\BinaryParser\AbstractBinaryParser}.
     *   If omitted, uses big-endian.
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     * @todo Make $byteOrder optional if there is a byte-order mark (BOM) in the
     *   string being extracted.
     *
     * @todo Consider changing $byteCount to a character count. They are not
     *   always equivalent (in the case of surrogates).
     */
    public function readStringUTF16(
        $byteCount,
        $byteOrder = self::BYTE_ORDER_BIG_ENDIAN,
        $characterSet = ''
    ) {
        if ($byteCount == 0) {
            return '';
        }
        $bytes = $this->_dataSource->readBytes($byteCount);
        if ($byteOrder == self::BYTE_ORDER_BIG_ENDIAN) {
            if ($characterSet == 'UTF-16BE') {
                return $bytes;
            }
            return iconv('UTF-16BE', $characterSet, $bytes);
        } elseif ($byteOrder == self::BYTE_ORDER_LITTLE_ENDIAN) {
            if ($characterSet == 'UTF-16LE') {
                return $bytes;
            }
            return iconv('UTF-16LE', $characterSet, $bytes);
        } else {
            throw new Exception\BinaryParserException("Invalid byte order: $byteOrder");
        }
    }

    /**
     * Reads the Mac Roman-encoded string from the binary file at the current
     * byte offset.
     *
     * You must supply the desired resulting character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $byteCount Number of bytes (characters) to return.
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readStringMacRoman($byteCount, $characterSet = '')
    {
        if ($byteCount == 0) {
            return '';
        }
        $bytes = $this->_dataSource->readBytes($byteCount);
        if ($characterSet == 'MacRoman') {
            return $bytes;
        }
        return iconv('macintosh', $characterSet, $bytes);
    }

    /**
     * Reads the Pascal string from the binary file at the current byte offset.
     *
     * The length of the Pascal string is determined by reading the length bytes
     * which preceed the character data. You must supply the desired resulting
     * character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @param integer $lengthBytes (optional) Number of bytes that make up the
     *   length. Default is 1.
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readStringPascal($characterSet = '', $lengthBytes = 1)
    {
        $byteCount = $this->readUInt($lengthBytes);
        if ($byteCount == 0) {
            return '';
        }
        $bytes = $this->_dataSource->readBytes($byteCount);
        if ($characterSet == 'ASCII') {
            return $bytes;
        }
        return iconv('ASCII', $characterSet, $bytes);
    }
}
