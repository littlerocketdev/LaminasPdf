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

/**
 * PDF file 'binary string' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class BinaryStringObject extends StringObject
{
    /**
     * Object value
     *
     * @var string
     */
    public $value;


    /**
     * Escape string according to the PDF rules
     *
     * @param string $inStr
     * @return string
     */
    public static function escape($inStr): string
    {
        return strtoupper(bin2hex($inStr));
    }


    /**
     * Unescape string according to the PDF rules
     *
     * @param string $inStr
     * @return string
     */
    public static function unescape($inStr): string
    {
        $chunks = [];
        $offset = 0;
        $length = 0;
        while ($offset < strlen($inStr)) {
            // Collect hexadecimal characters
            $start = $offset;
            $offset += strspn($inStr, "0123456789abcdefABCDEF", $offset);
            $chunks[] = substr($inStr, $start, $offset - $start);
            $length += strlen(end($chunks));

            // Skip non-hexadecimal characters
            $offset += strcspn($inStr, "0123456789abcdefABCDEF", $offset);
        }
        if ($length % 2 != 0) {
            // We have odd number of digits.
            // Final digit is assumed to be '0'
            $chunks[] = '0';
        }

        return pack('H*', implode($chunks));
    }


    /**
     * Return object as string
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null): string
    {
        return '<' . self::escape((string)$this->value) . '>';
    }
}
