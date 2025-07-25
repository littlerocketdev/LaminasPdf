<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType\StreamFilter\Compression;

use LaminasPdf as Pdf;
use LaminasPdf\Exception;

/**
 * Abstract compression stream filter
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
abstract class AbstractCompression implements Pdf\InternalType\StreamFilter\StreamFilterInterface
{
    /**
     * Paeth prediction function
     *
     * @param integer $a
     * @param integer $b
     * @param integer $c
     * @return integer
     */
    private static function _paeth($a, $b, $c)
    {
        // $a - left, $b - above, $c - upper left
        $p = $a + $b - $c;       // initial estimate
        $pa = abs($p - $a);       // distances to a, b, c
        $pb = abs($p - $b);
        $pc = abs($p - $c);

        // return nearest of a,b,c,
        // breaking ties in order a,b,c.
        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        } elseif ($pb <= $pc) {
            return $b;
        } else {
            return $c;
        }
    }


    /**
     * Get Predictor decode param value
     *
     * @param array $params
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private static function _getPredictorValue(&$params)
    {
        if (isset($params['Predictor'])) {
            $predictor = $params['Predictor'];

            if (
                $predictor != 1 && $predictor != 2 &&
                $predictor != 10 && $predictor != 11 && $predictor != 12 &&
                $predictor != 13 && $predictor != 14 && $predictor != 15
            ) {
                throw new Exception\CorruptedPdfException('Invalid value of \'Predictor\' decode param - ' . $predictor . '.');
            }
            return $predictor;
        } else {
            return 1;
        }
    }

    /**
     * Get Colors decode param value
     *
     * @param array $params
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private static function _getColorsValue(array &$params)
    {
        if (isset($params['Colors'])) {
            $colors = $params['Colors'];

            if ($colors != 1 && $colors != 2 && $colors != 3 && $colors != 4) {
                throw new Exception\CorruptedPdfException('Invalid value of \'Color\' decode param - ' . $colors . '.');
            }
            return $colors;
        } else {
            return 1;
        }
    }

    /**
     * Get BitsPerComponent decode param value
     *
     * @param array $params
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    private static function _getBitsPerComponentValue(array &$params)
    {
        if (isset($params['BitsPerComponent'])) {
            $bitsPerComponent = $params['BitsPerComponent'];

            if (
                $bitsPerComponent != 1 && $bitsPerComponent != 2 &&
                $bitsPerComponent != 4 && $bitsPerComponent != 8 &&
                $bitsPerComponent != 16
            ) {
                throw new Exception\CorruptedPdfException('Invalid value of \'BitsPerComponent\' decode param - ' . $bitsPerComponent . '.');
            }
            return $bitsPerComponent;
        } else {
            return 8;
        }
    }

    /**
     * Get Columns decode param value
     *
     * @param array $params
     * @return integer
     */
    private static function _getColumnsValue(array &$params)
    {
        if (isset($params['Columns'])) {
            return $params['Columns'];
        } else {
            return 1;
        }
    }


    /**
     * Convert stream data according to the filter params set before encoding.
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    protected static function _applyEncodeParams($data, $params)
    {
        $predictor = self::_getPredictorValue($params);
        $colors = self::_getColorsValue($params);
        $bitsPerComponent = self::_getBitsPerComponentValue($params);
        $columns = self::_getColumnsValue($params);

        /** None of prediction */
        if ($predictor == 1) {
            return $data;
        }

        /** TIFF Predictor 2 */
        if ($predictor == 2) {
            throw new Exception\NotImplementedException('TIFF compression perediction is not implemented yet');
        }

        /** Optimal PNG prediction */
        if ($predictor == 15) {
            /** Use Paeth prediction as optimal */
            $predictor = 14;
        }

        /** PNG prediction */
        if (
            $predictor == 10 || /** None of prediction */
            $predictor == 11 || /** Sub prediction     */
            $predictor == 12 || /** Up prediction      */
            $predictor == 13 || /** Average prediction */
            $predictor == 14/** Paeth prediction   */
        ) {
            $predictor -= 10;

            if ($bitsPerComponent == 16) {
                throw new Exception\CorruptedPdfException("PNG Prediction with bit depth greater than 8 not yet supported.");
            }

            $bitsPerSample = $bitsPerComponent * $colors;
            $bytesPerSample = (int)(($bitsPerSample + 7) / 8);           // (int)ceil(...) emulation
            $bytesPerRow = (int)(($bitsPerSample * $columns + 7) / 8);  // (int)ceil(...) emulation
            $rows = strlen($data) / $bytesPerRow;
            $output = '';
            $offset = 0;

            if (!is_integer($rows)) {
                throw new Exception\CorruptedPdfException('Wrong data length.');
            }

            switch ($predictor) {
                case 0: // None of prediction
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        $output .= substr($data, $offset, $bytesPerRow);
                        $offset += $bytesPerRow;
                    }
                    break;

                case 1: // Sub prediction
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);

                        $lastSample = array_fill(0, $bytesPerSample, 0);
                        for ($count2 = 0; $count2 < $bytesPerRow; $count2++) {
                            $newByte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($newByte - $lastSample[$count2 % $bytesPerSample]);
                            $lastSample[$count2 % $bytesPerSample] = $newByte;
                        }
                    }
                    break;

                case 2: // Up prediction
                    $lastRow = array_fill(0, $bytesPerRow, 0);
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);

                        for ($count2 = 0; $count2 < $bytesPerRow; $count2++) {
                            $newByte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($newByte - $lastRow[$count2]);
                            $lastRow[$count2] = $newByte;
                        }
                    }
                    break;

                case 3: // Average prediction
                    $lastRow = array_fill(0, $bytesPerRow, 0);
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);

                        $lastSample = array_fill(0, $bytesPerSample, 0);
                        for ($count2 = 0; $count2 < $bytesPerRow; $count2++) {
                            $newByte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($newByte - floor(($lastSample[$count2 % $bytesPerSample] + $lastRow[$count2]) / 2));
                            $lastSample[$count2 % $bytesPerSample] = $lastRow[$count2] = $newByte;
                        }
                    }
                    break;

                case 4: // Paeth prediction
                    $lastRow = array_fill(0, $bytesPerRow, 0);
                    $currentRow = [];
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);

                        $lastSample = array_fill(0, $bytesPerSample, 0);
                        for ($count2 = 0; $count2 < $bytesPerRow; $count2++) {
                            $newByte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($newByte - self::_paeth(
                                $lastSample[$count2 % $bytesPerSample],
                                $lastRow[$count2],
                                ($count2 - $bytesPerSample < 0) ?
                                0 : $lastRow[$count2 - $bytesPerSample]
                            ));
                            $lastSample[$count2 % $bytesPerSample] = $currentRow[$count2] = $newByte;
                        }
                        $lastRow = $currentRow;
                    }
                    break;
            }
            return $output;
        }

        throw new Exception\CorruptedPdfException('Unknown prediction algorithm - ' . $predictor . '.');
    }

    /**
     * Convert stream data according to the filter params set after decoding.
     *
     * @param string $data
     * @param array $params
     * @return string
     */
    protected static function _applyDecodeParams($data, $params)
    {
        $predictor = self::_getPredictorValue($params);
        $colors = self::_getColorsValue($params);
        $bitsPerComponent = self::_getBitsPerComponentValue($params);
        $columns = self::_getColumnsValue($params);

        /** None of prediction */
        if ($predictor == 1) {
            return $data;
        }

        /** TIFF Predictor 2 */
        if ($predictor == 2) {
            throw new Exception\NotImplementedException('TIFF compression perediction is not implemented yet');
        }

        /**
         * PNG prediction
         * Prediction code is duplicated on each row.
         * Thus all cases can be brought to one
         */
        if (
            $predictor == 10 || /** None of prediction */
            $predictor == 11 || /** Sub prediction     */
            $predictor == 12 || /** Up prediction      */
            $predictor == 13 || /** Average prediction */
            $predictor == 14 || /** Paeth prediction   */
            $predictor == 15/** Optimal prediction */
        ) {
            $bitsPerSample = $bitsPerComponent * $colors;
            $bytesPerSample = ceil($bitsPerSample / 8);
            $bytesPerRow = ceil($bitsPerSample * $columns / 8);
            $rows = ceil(strlen($data) / ($bytesPerRow + 1));
            $output = '';
            $offset = 0;

            $lastRow = array_fill(0, $bytesPerRow, 0);
            for ($count = 0; $count < $rows; $count++) {
                $lastSample = array_fill(0, $bytesPerSample, 0);
                switch (ord($data[$offset++])) {
                    case 0: // None of prediction
                        $output .= substr($data, $offset, $bytesPerRow);
                        for ($count2 = 0; $count2 < $bytesPerRow && $offset < strlen($data); $count2++) {
                            $lastSample[$count2 % $bytesPerSample] = $lastRow[$count2] = ord($data[$offset++]);
                        }
                        break;

                    case 1: // Sub prediction
                        for ($count2 = 0; $count2 < $bytesPerRow && $offset < strlen($data); $count2++) {
                            $decodedByte = (ord($data[$offset++]) + $lastSample[$count2 % $bytesPerSample]) & 0xFF;
                            $lastSample[$count2 % $bytesPerSample] = $lastRow[$count2] = $decodedByte;
                            $output .= chr($decodedByte);
                        }
                        break;

                    case 2: // Up prediction
                        for ($count2 = 0; $count2 < $bytesPerRow && $offset < strlen($data); $count2++) {
                            $decodedByte = (ord($data[$offset++]) + $lastRow[$count2]) & 0xFF;
                            $lastSample[$count2 % $bytesPerSample] = $lastRow[$count2] = $decodedByte;
                            $output .= chr($decodedByte);
                        }
                        break;

                    case 3: // Average prediction
                        for ($count2 = 0; $count2 < $bytesPerRow && $offset < strlen($data); $count2++) {
                            $decodedByte = (ord($data[$offset++]) +
                                    floor(($lastSample[$count2 % $bytesPerSample] + $lastRow[$count2]) / 2)
                                ) & 0xFF;
                            $lastSample[$count2 % $bytesPerSample] = $lastRow[$count2] = $decodedByte;
                            $output .= chr($decodedByte);
                        }
                        break;

                    case 4: // Paeth prediction
                        $currentRow = [];
                        for ($count2 = 0; $count2 < $bytesPerRow && $offset < strlen($data); $count2++) {
                            $decodedByte = (ord($data[$offset++]) +
                                    self::_paeth(
                                        $lastSample[$count2 % $bytesPerSample],
                                        $lastRow[$count2],
                                        ($count2 - $bytesPerSample < 0) ?
                                        0 : $lastRow[$count2 - $bytesPerSample]
                                    )
                                ) & 0xFF;
                            $lastSample[$count2 % $bytesPerSample] = $currentRow[$count2] = $decodedByte;
                            $output .= chr($decodedByte);
                        }
                        $lastRow = $currentRow;
                        break;

                    default:
                        throw new Exception\CorruptedPdfException('Unknown prediction tag.');
                }
            }
            return $output;
        }

        throw new Exception\CorruptedPdfException('Unknown prediction algorithm - ' . $predictor . '.');
    }
}
