<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Destination;

use LaminasPdf as Pdf;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * \LaminasPdf\Destination\FitRectangle explicit detination
 *
 * Destination array: [page /FitR left bottom right top]
 *
 * Display the page designated by page, with its contents magnified just enough
 * to fit the rectangle specified by the coordinates left, bottom, right, and top
 * entirely within the window both horizontally and vertically. If the required
 * horizontal and vertical magnification factors are different, use the smaller of
 * the two, centering the rectangle within the window in the other dimension.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
class FitRectangle extends AbstractExplicitDestination
{
    /**
     * Create destination object
     *
     * @param \LaminasPdf\Page|integer $page Page object or page number
     * @param float $left Left edge of displayed page
     * @param float $bottom Bottom edge of displayed page
     * @param float $right Right edge of displayed page
     * @param float $top Top edge of displayed page
     * @return \LaminasPdf\Destination\FitRectangle
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function create($page, $left, $bottom, $right, $top): self
    {
        $destinationArray = new InternalType\ArrayObject();

        if ($page instanceof Pdf\Page) {
            $destinationArray->items[] = $page->getPageDictionary();
        } elseif (is_integer($page)) {
            $destinationArray->items[] = new InternalType\NumericObject($page);
        } else {
            throw new Exception\InvalidArgumentException('$page parametr must be a \LaminasPdf\Page object or a page number.');
        }

        $destinationArray->items[] = new InternalType\NameObject('FitR');
        $destinationArray->items[] = new InternalType\NumericObject($left);
        $destinationArray->items[] = new InternalType\NumericObject($bottom);
        $destinationArray->items[] = new InternalType\NumericObject($right);
        $destinationArray->items[] = new InternalType\NumericObject($top);

        return new self($destinationArray);
    }

    /**
     * Get left edge of the displayed page
     *
     * @return float
     */
    public function getLeftEdge()
    {
        return $this->_destinationArray->items[2]->value;
    }

    /**
     * Set left edge of the displayed page
     *
     * @param float $left
     * @return \LaminasPdf\Destination\FitRectangle
     */
    public function setLeftEdge($left): static
    {
        $this->_destinationArray->items[2] = new InternalType\NumericObject($left);
        return $this;
    }

    /**
     * Get bottom edge of the displayed page
     *
     * @return float
     */
    public function getBottomEdge()
    {
        return $this->_destinationArray->items[3]->value;
    }

    /**
     * Set bottom edge of the displayed page
     *
     * @param float $bottom
     * @return \LaminasPdf\Destination\FitRectangle
     */
    public function setBottomEdge($bottom): static
    {
        $this->_destinationArray->items[3] = new InternalType\NumericObject($bottom);
        return $this;
    }

    /**
     * Get right edge of the displayed page
     *
     * @return float
     */
    public function getRightEdge()
    {
        return $this->_destinationArray->items[4]->value;
    }

    /**
     * Set right edge of the displayed page
     *
     * @param float $right
     * @return \LaminasPdf\Destination\FitRectangle
     */
    public function setRightEdge($right): static
    {
        $this->_destinationArray->items[4] = new InternalType\NumericObject($right);
        return $this;
    }

    /**
     * Get top edge of the displayed page
     *
     * @return float
     */
    public function getTopEdge()
    {
        return $this->_destinationArray->items[5]->value;
    }

    /**
     * Set top edge of the displayed page
     *
     * @param float $top
     * @return \LaminasPdf\Destination\FitRectangle
     */
    public function setTopEdge($top): static
    {
        $this->_destinationArray->items[5] = new InternalType\NumericObject($top);
        return $this;
    }
}
