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
 * \LaminasPdf\Destination\Fit explicit detination
 *
 * Destination array: [page /Fit]
 *
 * Display the page designated by page, with its contents magnified just enough
 * to fit the entire page within the window both horizontally and vertically. If
 * the required horizontal and vertical magnification factors are different, use
 * the smaller of the two, centering the page within the window in the other
 * dimension.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
class Fit extends AbstractExplicitDestination
{
    /**
     * Create destination object
     *
     * @param \LaminasPdf\Page|integer $page Page object or page number
     * @return \LaminasPdf\Destination\Fit
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function create($page): self
    {
        $destinationArray = new InternalType\ArrayObject();

        if ($page instanceof Pdf\Page) {
            $destinationArray->items[] = $page->getPageDictionary();
        } elseif (is_integer($page)) {
            $destinationArray->items[] = new InternalType\NumericObject($page);
        } else {
            throw new Exception\InvalidArgumentException('$page parametr must be a \LaminasPdf\Page object or a page number.');
        }

        $destinationArray->items[] = new InternalType\NameObject('Fit');

        return new self($destinationArray);
    }
}
