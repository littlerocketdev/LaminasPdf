<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\BinaryParser\DataSource;

use LaminasPdf\Exception;
use Laminas\Stdlib\ErrorHandler;

/**
 * Concrete subclass of {@link \LaminasPdf\BinaryParser\DataSource\AbstractDataSource}
 * that provides an interface to filesystem objects.
 *
 * Note that this class cannot be used for other sources that may be supported
 * by {@link fopen()} (through URL wrappers). It may be used for local
 * filesystem objects only.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\BinaryParser
 */
class File extends AbstractDataSource
{
    /**** Instance Variables ****/


    /**
     * Fully-qualified path to the file.
     * @var String_
     */
    protected $_filePath = '';

    /**
     * File resource handle .
     * @var resource
     */
    protected $_fileResource = null;


    /**** Public Interface ****/


    /* Concrete Class Implementation */

    /**
     * Object constructor.
     *
     * Validates the path to the file, ensures that it is readable, then opens
     * it for reading.
     *
     * Throws an exception if the file is missing or cannot be opened.
     *
     * @param String_ $filePath Fully-qualified path to the file.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($filePath)
    {
        if (!(is_file($filePath) || is_link($filePath))) {
            throw new Exception\IOException('Invalid file path: ' . $filePath);
        }
        if (!is_readable($filePath)) {
            throw new Exception\IOException('File is not readable: ' . $filePath);
        }
        if (($this->_size = @filesize($filePath)) === false) {
            throw new Exception\IOException('Error while obtaining file size: ' . $filePath);
        }
        if (($this->_fileResource = @fopen($filePath, 'rb')) === false) {
            throw new Exception\IOException('Cannot open file for reading: ' . $filePath);
        }
        $this->_filePath = $filePath;
    }

    /**
     * Object destructor.
     *
     * Closes the file if it had been successfully opened.
     */
    public function __destruct()
    {
        if (is_resource($this->_fileResource)) {
            ErrorHandler::start(E_WARNING);
            fclose($this->_fileResource);
            ErrorHandler::stop();
        }
    }

    /**
     * Returns the specified number of raw bytes from the file at the byte
     * offset of the current read position.
     *
     * Advances the read position by the number of bytes read.
     *
     * Throws an exception if an error was encountered while reading the file or
     * if there is insufficient data to completely fulfill the request.
     *
     * @param integer $byteCount Number of bytes to read.
     * @return String_
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function readBytes($byteCount): string
    {
        $bytes = @fread($this->_fileResource, $byteCount);
        if ($bytes === false) {
            throw new Exception\IOException('Unexpected error while reading file');
        }
        if (strlen($bytes) != $byteCount) {
            throw new Exception\IOException('Insufficient data to read ' . $byteCount . ' bytes');
        }
        $this->_offset += $byteCount;
        return $bytes;
    }

    /**
     * Returns the entire contents of the file as a string.
     *
     * Preserves the current file seek position.
     *
     * @return String_
     */
    public function readAllBytes(): string|false
    {
        return file_get_contents($this->_filePath);
    }


    /* Object Magic Methods */

    /**
     * Returns the full filesystem path of the file.
     *
     * @return String_
     */
    public function __toString(): string
    {
        return $this->_filePath;
    }


    /* Primitive Methods */

    /**
     * Seeks the file read position to the specified byte offset.
     *
     * Throws an exception if the file pointer cannot be moved or if it is
     * moved beyond EOF (end of file).
     *
     * @param integer $offset Destination byte offset.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function moveToOffset($offset): void
    {
        if ($this->_offset == $offset) {
            return;    // Not moving; do nothing.
        }
        parent::moveToOffset($offset);
        $result = @fseek($this->_fileResource, $offset, SEEK_SET);
        if ($result !== 0) {
            throw new Exception\IOException('Error while setting new file position');
        }
        if (feof($this->_fileResource)) {
            throw new Exception\IOException('Moved beyond the end of the file');
        }
    }
}
