<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Resource\Font;

use LaminasPdf as Pdf;
use LaminasPdf\BinaryParser\Font\OpenType as OpenTypeFontParser;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * FontDescriptor implementation
 *
 * A font descriptor specifies metrics and other attributes of a simple font or a
 * CIDFont as a whole, as distinct from the metrics of individual glyphs. These font
 * metrics provide information that enables a viewer application to synthesize a
 * substitute font or select a similar font when the font program is unavailable. The
 * font descriptor may also be used to embed the font program in the PDF file.
 *
 * @subpackage LaminasPdf\Fonts
 * @subpackage Fonts
 */
class FontDescriptor
{
    /**
     * Object constructor
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct()
    {
        throw new Exception\RuntimeException('\LaminasPdf\Resource\Font\FontDescriptor is not intended to be instantiated');
    }

    /**
     * Object constructor
     *
     * The $embeddingOptions parameter allows you to set certain flags related
     * to font embedding. You may combine options by OR-ing them together. See
     * the EMBED_ constants defined in {@link \LaminasPdf\Font} for the list of
     * available options and their descriptions.
     *
     * Note that it is not requried that fonts be embedded within the PDF file
     * to use them. If the recipient of the PDF has the font installed on their
     * computer, they will see the correct fonts in the document. If they don't,
     * the PDF viewer will substitute or synthesize a replacement.
     *
     *
     * @param \LaminasPdf\Resource\Font\AbstractFont $font Font
     * @param \LaminasPdf\BinaryParser\Font\OpenType\AbstractOpenType $fontParser Font parser object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @return \LaminasPdf\InternalType\DictionaryObject
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function factory(
        AbstractFont $font,
        OpenTypeFontParser\AbstractOpenType $fontParser,
        $embeddingOptions
    ): \LaminasPdf\InternalType\DictionaryObject {
        /* The font descriptor object contains the rest of the font metrics and
         * the information about the embedded font program (if applicible).
         */
        $fontDescriptor = new InternalType\DictionaryObject();

        $fontDescriptor->Type = new InternalType\NameObject('FontDescriptor');
        $fontDescriptor->FontName = new InternalType\NameObject($font->getResource()->BaseFont->value);

        /* The font flags value is a bitfield that describes the stylistic
         * attributes of the font. We will set as many of the bits as can be
         * determined from the font parser.
         */
        $flags = 0;
        if ($fontParser->isMonospace) {    // bit 1: FixedPitch
            $flags |= 1 << 0;
        }
        if ($fontParser->isSerifFont) {    // bit 2: Serif
            $flags |= 1 << 1;
        }
        if (!$fontParser->isAdobeLatinSubset) {    // bit 3: Symbolic
            $flags |= 1 << 2;
        }
        if ($fontParser->isScriptFont) {    // bit 4: Script
            $flags |= 1 << 3;
        }
        if ($fontParser->isAdobeLatinSubset) {    // bit 6: Nonsymbolic
            $flags |= 1 << 5;
        }
        if ($fontParser->isItalic) {    // bit 7: Italic
            $flags |= 1 << 6;
        }
        // bits 17-19: AllCap, SmallCap, ForceBold; not available
        $fontDescriptor->Flags = new InternalType\NumericObject($flags);

        $fontBBox = [new InternalType\NumericObject($font->toEmSpace($fontParser->xMin)), new InternalType\NumericObject($font->toEmSpace($fontParser->yMin)), new InternalType\NumericObject($font->toEmSpace($fontParser->xMax)), new InternalType\NumericObject($font->toEmSpace($fontParser->yMax))];
        $fontDescriptor->FontBBox = new InternalType\ArrayObject($fontBBox);

        $fontDescriptor->ItalicAngle = new InternalType\NumericObject($fontParser->italicAngle);

        $fontDescriptor->Ascent = new InternalType\NumericObject($font->toEmSpace($fontParser->ascent));
        $fontDescriptor->Descent = new InternalType\NumericObject($font->toEmSpace($fontParser->descent));

        $fontDescriptor->CapHeight = new InternalType\NumericObject($fontParser->capitalHeight);
        /**
         * The vertical stem width is not yet extracted from the OpenType font
         * file. For now, record zero which is interpreted as 'unknown'.
         * @todo Calculate value for StemV.
         */
        $fontDescriptor->StemV = new InternalType\NumericObject(0);

        $fontDescriptor->MissingWidth = new InternalType\NumericObject($fontParser->glyphWidths[0]);

        /* Set up font embedding. This is where the actual font program itself
         * is embedded within the PDF document.
         *
         * Note that it is not requried that fonts be embedded within the PDF
         * document to use them. If the recipient of the PDF has the font
         * installed on their computer, they will see the correct fonts in the
         * document. If they don't, the PDF viewer will substitute or synthesize
         * a replacement.
         *
         * There are several guidelines for font embedding:
         *
         * First, the developer might specifically request not to embed the font.
         */
        if (!($embeddingOptions & Pdf\Font::EMBED_DONT_EMBED)) {
            /* Second, the font author may have set copyright bits that prohibit
             * the font program from being embedded. Yes this is controversial,
             * but it's the rules:
             *   http://partners.adobe.com/public/developer/en/acrobat/sdk/FontPolicies.pdf
             *
             * To keep the developer in the loop, and to prevent surprising bug
             * reports of "your PDF doesn't have the right fonts," throw an
             * exception if the font cannot be embedded.
             */
            if (!$fontParser->isEmbeddable) {
                /* This exception may be suppressed if the developer decides that
                 * it's not a big deal that the font program can't be embedded.
                 */
                if (!($embeddingOptions & Pdf\Font::EMBED_SUPPRESS_EMBED_EXCEPTION)) {
                    $message = 'This font cannot be embedded in the PDF document. If you would like to use '
                        . 'it anyway, you must pass \LaminasPdf\Font::EMBED_SUPPRESS_EMBED_EXCEPTION '
                        . 'in the $options parameter of the font constructor.';
                    throw new Exception\DomainException($message);
                }
            } else {
                /* Otherwise, the default behavior is to embed all custom fonts.
                 */
                /* This section will change soon to a stream object data
                 * provider model so that we don't have to keep a copy of the
                 * entire font in memory.
                 *
                 * We also cannot build font subsetting until the data provider
                 * model is in place.
                 */
                $fontFile = $fontParser->getDataSource()->readAllBytes();
                $fontFileObject = $font->getFactory()->newStreamObject($fontFile);
                $fontFileObject->dictionary->Length1 = new InternalType\NumericObject(strlen($fontFile));
                if (!($embeddingOptions & Pdf\Font::EMBED_DONT_COMPRESS)) {
                    /* Compress the font file using Flate. This generally cuts file
                     * sizes by about half!
                     */
                    $fontFileObject->dictionary->Filter = new InternalType\NameObject('FlateDecode');
                }
                // Type1 fonts are not implemented now
                // if ($fontParser instanceof OpenTypeFontParser\Type1) {
                //     $fontDescriptor->FontFile  = $fontFileObject;
                // } else
                if ($fontParser instanceof OpenTypeFontParser\TrueType) {
                    $fontDescriptor->FontFile2 = $fontFileObject;
                } else {
                    $fontDescriptor->FontFile3 = $fontFileObject;
                }
            }
        }

        return $fontDescriptor;
    }
}
