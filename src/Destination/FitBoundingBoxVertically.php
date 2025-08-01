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
 * \LaminasPdf\Destination\FitBoundingBoxVertically explicit detination
 *
 * Destination array: [page /FitBV left]
 *
 * (PDF 1.1) Display the page designated by page, with the horizontal coordinate
 * left positioned at the left edge of the window and the contents of the page
 * magnified just enough to fit the entire height of its bounding box within the
 * window.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
class FitBoundingBoxVertically extends AbstractExplicitDestination
{
    /**
     * Create destination object
     *
     * @param \LaminasPdf\Page|integer $page Page object or page number
     * @param float $left Left edge of displayed page
     * @return \LaminasPdf\Destination\FitBoundingBoxVertically
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function create($page, $left): self
    {
        $destinationArray = new InternalType\ArrayObject();

        if ($page instanceof Pdf\Page) {
            $destinationArray->items[] = $page->getPageDictionary();
        } elseif (is_integer($page)) {
            $destinationArray->items[] = new InternalType\NumericObject($page);
        } else {
            throw new Exception\InvalidArgumentException('$page parametr must be a \LaminasPdf\Page object or a page number.');
        }

        $destinationArray->items[] = new InternalType\NameObject('FitBV');
        $destinationArray->items[] = new InternalType\NumericObject($left);

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
     * @return FitBoundingBoxVertically
     */
    public function setLeftEdge($left): static
    {
        $this->_destinationArray->items[2] = new InternalType\NumericObject($left);
        return $this;
    }
}
