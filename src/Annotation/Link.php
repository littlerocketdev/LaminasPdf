<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Annotation;

use LaminasPdf as Pdf;
use LaminasPdf\Destination;
use LaminasPdf\Exception;
use LaminasPdf\InternalStructure;
use LaminasPdf\InternalType;

/**
 * A link annotation represents either a hypertext link to a destination elsewhere in
 * the document or an action to be performed.
 *
 * Only destinations are used now since only GoTo action can be created by user
 * in current implementation.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Annotation
 */
class Link extends AbstractAnnotation
{
    /**
     * Annotation object constructor
     *
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $annotationDictionary)
    {
        if ($annotationDictionary->getType() != InternalType\AbstractTypeObject::TYPE_DICTIONARY) {
            throw new Exception\CorruptedPdfException('Annotation dictionary resource has to be a dictionary.');
        }

        if (
            $annotationDictionary->Subtype === null ||
            $annotationDictionary->Subtype->getType() != InternalType\AbstractTypeObject::TYPE_NAME ||
            $annotationDictionary->Subtype->value != 'Link'
        ) {
            throw new Exception\CorruptedPdfException('Subtype => Link entry is requires');
        }

        parent::__construct($annotationDictionary);
    }

    /**
     * Create link annotation object
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param \LaminasPdf\InternalStructure\NavigationTarget|string $target
     * @return \LaminasPdf\Annotation\Link
     */
    public static function create($x1, $y1, $x2, $y2, $target): self
    {
        if (is_string($target)) {
            $destination = Destination\Named::create($target);
        }
        if (!$target instanceof InternalStructure\NavigationTarget) {
            throw new Exception\InvalidArgumentException('$target parameter must be a \LaminasPdf\InternalStructure\NavigationTarget object or a string.');
        }

        $annotationDictionary = new InternalType\DictionaryObject();

        $annotationDictionary->Type = new InternalType\NameObject('Annot');
        $annotationDictionary->Subtype = new InternalType\NameObject('Link');

        $rectangle = new InternalType\ArrayObject();
        $rectangle->items[] = new InternalType\NumericObject($x1);
        $rectangle->items[] = new InternalType\NumericObject($y1);
        $rectangle->items[] = new InternalType\NumericObject($x2);
        $rectangle->items[] = new InternalType\NumericObject($y2);
        $annotationDictionary->Rect = $rectangle;

        if ($target instanceof Destination\AbstractDestination) {
            $annotationDictionary->Dest = $target->getResource();
        } else {
            $annotationDictionary->A = $target->getResource();
        }

        return new self($annotationDictionary);
    }

    /**
     * Set link annotation destination
     *
     * @param \LaminasPdf\InternalStructure\NavigationTarget|string $target
     * @return \LaminasPdf\Annotation\Link
     */
    public function setDestination($target): static
    {
        $destination = null;
        if (is_string($target)) {
            $destination = Destination\Named::create($target);
        }
        if (!$target instanceof InternalStructure\NavigationTarget) {
            throw new Exception\InvalidArgumentException('$target parameter must be a \LaminasPdf\InternalStructure\NavigationTarget object or a string.');
        }

        $this->_annotationDictionary->touch();
        $this->_annotationDictionary->Dest = $destination->getResource();
        if ($target instanceof Destination\AbstractDestination) {
            $this->_annotationDictionary->Dest = $target->getResource();
            $this->_annotationDictionary->A = null;
        } else {
            $this->_annotationDictionary->Dest = null;
            $this->_annotationDictionary->A = $target->getResource();
        }

        return $this;
    }

    /**
     * Get link annotation destination
     *
     * @return \LaminasPdf\InternalStructure\NavigationTarget|null
     */
    public function getDestination()
    {
        if (
            $this->_annotationDictionary->Dest === null &&
            $this->_annotationDictionary->A === null
        ) {
            return null;
        }

        if ($this->_annotationDictionary->Dest !== null) {
            return Destination\AbstractDestination::load($this->_annotationDictionary->Dest);
        } else {
            return Pdf\Action\AbstractAction::load($this->_annotationDictionary->A);
        }
    }
}
