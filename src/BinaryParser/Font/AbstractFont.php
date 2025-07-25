<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\BinaryParser\Font;

use LaminasPdf\BinaryParser;
use Laminas\Log;

/**
 * Abstract helper class for {@link \LaminasPdf\Font} that parses font files.
 *
 * Defines the public interface for concrete subclasses which are responsible
 * for parsing the raw binary data from the font file on disk. Also provides
 * a debug logging interface and a couple of shared utility methods.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Fonts
 */
abstract class AbstractFont extends BinaryParser\AbstractBinaryParser
{
    /**** Instance Variables ****/


    /**
     * Array of parsed font properties. Used with {@link __get()} and
     * {@link __set()}.
     * @var array
     */
    private $_fontProperties = [];

    /**
     * Flag indicating whether or not debug logging is active.
     */
    private bool $_debug = false;


    /**** Public Interface ****/


    /* Object Lifecycle */

    /**
     * Object constructor.
     *
     * Validates the data source and enables debug logging if so configured.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(BinaryParser\DataSource\AbstractDataSource $dataSource)
    {
        parent::__construct($dataSource);
        $this->fontType = \LaminasPdf\Font::TYPE_UNKNOWN;
    }


    /* Accessors */

    /**
     * Get handler
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        if (isset($this->_fontProperties[$property])) {
            return $this->_fontProperties[$property];
        } else {
            return null;
        }
    }

    /* NOTE: The set handler is defined below in the internal methods group. */


    /* Parser Methods */

    /**
     * Reads the Unicode UTF-16-encoded string from the binary file at the
     * current offset location. Overridden to fix return character set at UTF-16BE.
     *
     * @param integer $byteCount Number of bytes (characters * 2) to return.
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link \LaminasPdf\BinaryParser\AbstractBinaryParser}. If
     *   omitted, uses big-endian.
     * @param string $characterSet (optional) --Ignored--
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     * @todo Deal with to-dos in the parent method.
     *
     */
    public function readStringUTF16(
        $byteCount,
        $byteOrder = BinaryParser\AbstractBinaryParser::BYTE_ORDER_BIG_ENDIAN,
        $characterSet = ''
    ) {
        return parent::readStringUTF16($byteCount, $byteOrder, 'UTF-16BE');
    }

    /**
     * Reads the Mac Roman-encoded string from the binary file at the current
     * offset location. Overridden to fix return character set at UTF-16BE.
     *
     * @param integer $byteCount Number of bytes (characters) to return.
     * @param string $characterSet (optional) --Ignored--
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readStringMacRoman($byteCount, $characterSet = '')
    {
        return parent::readStringMacRoman($byteCount, 'UTF-16BE');
    }

    /**
     * Reads the Pascal string from the binary file at the current offset
     * location. Overridden to fix return character set at UTF-16BE.
     *
     * @param string $characterSet (optional) --Ignored--
     * @param integer $lengthBytes (optional) Number of bytes that make up the
     *   length. Default is 1.
     * @return string
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readStringPascal($characterSet = '', $lengthBytes = 1)
    {
        return parent::readStringPascal('UTF-16BE');
    }


    /* Utility Methods */

    /**
     * Writes the entire font properties array to STDOUT. Used only for debugging.
     */
    public function writeDebug(): void
    {
        print_r($this->_fontProperties);
    }



    /**** Internal Methods ****/


    /* Internal Accessors */

    /**
     * Set handler
     *
     * NOTE: This method is protected. Other classes may freely interrogate
     * the font properties, but only this and its subclasses may set them.
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        if ($value === null) {
            unset($this->_fontProperties[$property]);
        } else {
            $this->_fontProperties[$property] = $value;
        }
    }


    /* Internal Utility Methods */

    /**
     * If debug logging is enabled, writes the log message.
     *
     * The log message is a sprintf() style string and any number of arguments
     * may accompany it as additional parameters.
     *
     * @param string $message
     * @param mixed (optional, multiple) Additional arguments
     */
    protected function _debugLog($message)
    {
        if (!$this->_debug) {
            return;
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            $message = array_shift($args);
            $message = vsprintf($message, $args);
        }

        $logger = new Log\Logger();
        $logger->log($message, Log\Logger::DEBUG);
    }
}
