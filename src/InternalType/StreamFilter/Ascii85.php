<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType\StreamFilter;

use LaminasPdf\Exception;

/**
 * ASCII85 stream filter
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class Ascii85 implements StreamFilterInterface
{
    /**
     * Encode data
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function encode($data, $params = null): string
    {
        $output = '';
        $dataLength = strlen($data);

        for ($i = 0; $i < $dataLength; $i += 4) {
            //convert the 4 characters into a 32-bit number
            $chunk = substr($data, $i, 4);

            if (strlen($chunk) < 4) {
                //partial chunk
                break;
            }

            $b = unpack("N", $chunk);
            $b = $b[1];

            //special char for all 4 bytes = 0
            if ($b == 0) {
                $output .= 'z';
                continue;
            }

            //encode into 5 bytes
            for ($j = 4; $j >= 0; $j--) {
                $foo = (int)(($b / 85 ** $j) + 33);
                $b %= 85 ** $j;
                $output .= chr($foo);
            }
        }

        //encode partial chunk
        if ($i < $dataLength) {
            $n = $dataLength - $i;
            $chunk = substr($data, -$n);

            //0 pad the rest
            for ($j = $n; $j < 4; $j++) {
                $chunk .= "\0";
            }

            $b = unpack("N", $chunk);
            $b = $b[1];

            //encode just $n + 1
            for ($j = 4; $j >= (4 - $n); $j--) {
                $foo = (int)(($b / 85 ** $j) + 33);
                $b %= 85 ** $j;
                $output .= chr($foo);
            }
        }

        //EOD
        $output .= '~>';

        //make sure lines are split
        $output = chunk_split($output, 76, "\n");

        //get rid of new line at the end
        $output = substr($output, 0, -1);
        return $output;
    }

    /**
     * Decode data
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function decode($data, $params = null): string
    {
        $output = '';

        //get rid of the whitespaces
        $whiteSpace = ["\x00", "\x09", "\x0A", "\x0C", "\x0D", "\x20"];
        $data = str_replace($whiteSpace, '', $data);

        if (substr($data, -2) != '~>') {
            throw new Exception\CorruptedPdfException('Invalid EOF marker');
            return '';
        }

        $data = substr($data, 0, (strlen($data) - 2));
        $dataLength = strlen($data);

        for ($i = 0; $i < $dataLength; $i += 5) {
            $b = 0;

            if (substr($data, $i, 1) == "z") {
                $i -= 4;
                $output .= pack("N", 0);
                continue;
            }

            $c = substr($data, $i, 5);

            if (strlen($c) < 5) {
                //partial chunk
                break;
            }

            $c = unpack('C5', $c);
            $value = 0;

            for ($j = 1; $j <= 5; $j++) {
                $value += (($c[$j] - 33) * 85 ** (5 - $j));
            }

            $output .= pack("N", $value);
        }

        //decode partial
        if ($i < $dataLength) {
            $value = 0;
            $chunk = substr($data, $i);
            $partialLength = strlen($chunk);

            //pad the rest of the chunk with u's
            //until the lenght of the chunk is 5
            for ($j = 0; $j < (5 - $partialLength); $j++) {
                $chunk .= 'u';
            }

            $c = unpack('C5', $chunk);

            for ($j = 1; $j <= 5; $j++) {
                $value += (($c[$j] - 33) * 85 ** (5 - $j));
            }

            $foo = pack("N", $value);
            $output .= substr($foo, 0, ($partialLength - 1));
        }

        return $output;
    }
}
