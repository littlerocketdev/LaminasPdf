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

use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * A markup annotation
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Annotation
 */
class Markup extends AbstractAnnotation
{
    /**
     * Annotation subtypes
     */
    public const SUBTYPE_HIGHLIGHT = 'Highlight';
    public const SUBTYPE_UNDERLINE = 'Underline';
    public const SUBTYPE_SQUIGGLY = 'Squiggly';
    public const SUBTYPE_STRIKEOUT = 'StrikeOut';

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
            !in_array(
                $annotationDictionary->Subtype->value,
                [self::SUBTYPE_HIGHLIGHT, self::SUBTYPE_UNDERLINE, self::SUBTYPE_SQUIGGLY, self::SUBTYPE_STRIKEOUT]
            )
        ) {
            throw new Exception\CorruptedPdfException('Subtype => Markup entry is omitted or has wrong value.');
        }

        parent::__construct($annotationDictionary);
    }

    /**
     * Create markup annotation object
     *
     * Text markup annotations appear as highlights, underlines, strikeouts or
     * jagged ("squiggly") underlines in the text of a document. When opened,
     * they display a pop-up window containing the text of the associated note.
     *
     * $subType parameter may contain
     *     \LaminasPdf\Annotation\Markup::SUBTYPE_HIGHLIGHT
     *     \LaminasPdf\Annotation\Markup::SUBTYPE_UNDERLINE
     *     \LaminasPdf\Annotation\Markup::SUBTYPE_SQUIGGLY
     *     \LaminasPdf\Annotation\Markup::SUBTYPE_STRIKEOUT
     * for for a highlight, underline, squiggly-underline, or strikeout annotation,
     * respectively.
     *
     * $quadPoints is an array of 8xN numbers specifying the coordinates of
     * N quadrilaterals default user space. Each quadrilateral encompasses a word or
     * group of contiguous words in the text underlying the annotation.
     * The coordinates for each quadrilateral are given in the order
     *     x1 y1 x2 y2 x3 y3 x4 y4
     * specifying the quadrilateral’s four vertices in counterclockwise order
     * starting from left bottom corner.
     * The text is oriented with respect to the edge connecting points
     * (x1, y1) and (x2, y2).
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param string $text
     * @param string $subType
     * @param array $quadPoints [x1 y1 x2 y2 x3 y3 x4 y4]
     * @return \LaminasPdf\Annotation\Markup
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function create($x1, $y1, $x2, $y2, $text, $subType, $quadPoints): self
    {
        $annotationDictionary = new InternalType\DictionaryObject();

        $annotationDictionary->Type = new InternalType\NameObject('Annot');
        $annotationDictionary->Subtype = new InternalType\NameObject($subType);

        $rectangle = new InternalType\ArrayObject();
        $rectangle->items[] = new InternalType\NumericObject($x1);
        $rectangle->items[] = new InternalType\NumericObject($y1);
        $rectangle->items[] = new InternalType\NumericObject($x2);
        $rectangle->items[] = new InternalType\NumericObject($y2);
        $annotationDictionary->Rect = $rectangle;

        $annotationDictionary->Contents = new InternalType\StringObject($text);

        if (!is_array($quadPoints) || count($quadPoints) == 0 || count($quadPoints) % 8 != 0) {
            throw new Exception\InvalidArgumentException('$quadPoints parameter must be an array of 8xN numbers');
        }
        $points = new InternalType\ArrayObject();
        foreach ($quadPoints as $quadPoint) {
            $points->items[] = new InternalType\NumericObject($quadPoint);
        }
        $annotationDictionary->QuadPoints = $points;

        return new self($annotationDictionary);
    }
}
