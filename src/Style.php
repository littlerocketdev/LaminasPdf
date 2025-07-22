<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf;

/**
 * Style object.
 * Style object doesn't directly correspond to any PDF file object.
 * It's utility class, used as a container for style information.
 * It's used by \LaminasPdf\Page class for draw operations.
 *
 * @package    LaminasPdf
 */
class Style
{
    /**
     * Fill color.
     * Used to fill geometric shapes or text.
     *
     * @var \LaminasPdf\Color\ColorInterface|null
     */
    private $_fillColor = null;

    /**
     * Line color.
     * Current color, used for lines and font outlines.
     *
     * @var \LaminasPdf\Color\ColorInterface|null
     */

    private $_color;

    /**
     * Line width.
     *
     * @var \LaminasPdf\InternalType\NumericObject
     */
    private $_lineWidth;

    /**
     * Array which describes line dashing pattern.
     * It's array of numeric:
     * array($on_length, $off_length, $on_length, $off_length, ...)
     *
     * @var array
     */
    private $_lineDashingPattern;

    /**
     * Line dashing phase
     *
     * @var float
     */
    private $_lineDashingPhase;

    /**
     * Current font
     *
     * @var \LaminasPdf\Resource\Font\AbstractFont
     */
    private $_font;

    /**
     * Font size
     *
     * @var float
     */
    private $_fontSize;


    /**
     * Create style.
     *
     * @param \LaminasPdf\Style $anotherStyle
     */
    public function __construct($anotherStyle = null)
    {
        if ($anotherStyle !== null) {
            $this->_fillColor = $anotherStyle->_fillColor;
            $this->_color = $anotherStyle->_color;
            $this->_lineWidth = $anotherStyle->_lineWidth;
            $this->_lineDashingPattern = $anotherStyle->_lineDashingPattern;
            $this->_lineDashingPhase = $anotherStyle->_lineDashingPhase;
            $this->_font = $anotherStyle->_font;
            $this->_fontSize = $anotherStyle->_fontSize;
        }
    }


    /**
     * Set fill color.
     *
     * @param \LaminasPdf\Color\ColorInterface $color
     */
    public function setFillColor(Color\ColorInterface $color): void
    {
        $this->_fillColor = $color;
    }

    /**
     * Set line color.
     *
     * @param \LaminasPdf\Color\ColorInterface $color
     */
    public function setLineColor(Color\ColorInterface $color): void
    {
        $this->_color = $color;
    }

    /**
     * Set line width.
     *
     * @param float $width
     */
    public function setLineWidth($width): void
    {
        $this->_lineWidth = new InternalType\NumericObject($width);
    }


    /**
     * Set line dashing pattern
     *
     * @param array $pattern
     * @param float $phase
     */
    public function setLineDashingPattern($pattern, $phase = 0): void
    {
        if ($pattern === Page::LINE_DASHING_SOLID) {
            $pattern = [];
            $phase = 0;
        }

        $this->_lineDashingPattern = $pattern;
        $this->_lineDashingPhase = new InternalType\NumericObject($phase);
    }


    /**
     * Set current font.
     *
     * @param \LaminasPdf\Resource\Font\AbstractFont $font
     * @param float $fontSize
     */
    public function setFont(Resource\Font\AbstractFont $font, $fontSize): void
    {
        $this->_font = $font;
        $this->_fontSize = $fontSize;
    }

    /**
     * Modify current font size
     *
     * @param float $fontSize
     */
    public function setFontSize($fontSize): void
    {
        $this->_fontSize = $fontSize;
    }

    /**
     * Get fill color.
     *
     * @return \LaminasPdf\Color\ColorInterface|null
     */
    public function getFillColor()
    {
        return $this->_fillColor;
    }

    /**
     * Get line color.
     *
     * @return \LaminasPdf\Color\ColorInterface|null
     */
    public function getLineColor()
    {
        return $this->_color;
    }

    /**
     * Get line width.
     *
     * @return float
     */
    public function getLineWidth()
    {
        return $this->_lineWidth->value;
    }

    /**
     * Get line dashing pattern
     *
     * @return array
     */
    public function getLineDashingPattern()
    {
        return $this->_lineDashingPattern;
    }


    /**
     * Get current font.
     *
     * @return \LaminasPdf\Resource\Font\AbstractFont $font
     */
    public function getFont()
    {
        return $this->_font;
    }

    /**
     * Get current font size
     *
     * @return float $fontSize
     */
    public function getFontSize()
    {
        return $this->_fontSize;
    }

    /**
     * Get line dashing phase
     *
     * @return float
     */
    public function getLineDashingPhase()
    {
        return $this->_lineDashingPhase->value;
    }


    /**
     * Dump style to a string, which can be directly inserted into content stream
     *
     * @return string
     */
    public function instructions(): string
    {
        $instructions = '';

        if ($this->_fillColor !== null) {
            $instructions .= $this->_fillColor->instructions(false);
        }

        if ($this->_color !== null) {
            $instructions .= $this->_color->instructions(true);
        }

        if ($this->_lineWidth !== null) {
            $instructions .= $this->_lineWidth->toString() . " w\n";
        }

        if ($this->_lineDashingPattern !== null) {
            $dashPattern = new InternalType\ArrayObject();

            foreach ($this->_lineDashingPattern as $dashItem) {
                $dashElement = new InternalType\NumericObject($dashItem);
                $dashPattern->items[] = $dashElement;
            }

            $instructions .= $dashPattern->toString() . ' '
                . $this->_lineDashingPhase->toString() . " d\n";
        }

        return $instructions;
    }
}
