<?php

declare(strict_types=1);

namespace lilthq\tests\fixtures;

use benf\neo\elements\Block;
use benf\neo\elements\db\BlockQuery;
use craft\base\Element;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;

final class ExpectedElementContent
{
    public static function getExpectedI18N(string $prefix = ''): array
    {
        return [
            'firstCheckboxLabel' => $prefix . 'First checkbox label',
            'secondCheckboxLabel' => $prefix . 'Second checkbox label',
            'thirdCheckboxLabel' => $prefix . 'Third checkbox label',
            'onLabel' => $prefix . 'The label text to display beside the lightswitch’s enabled state',
            'offLabel' => $prefix . 'The label text to display beside the lightswitch’s disabled state.',
            'columnHeading1' => $prefix . 'Column Heading 1',
            'columnHeading2' => $prefix . 'Column Heading 2',
            'columnHeading3' => $prefix . 'Column Heading 3',
            'columnHeading4' => $prefix . 'Column Heading 4',
        ];
    }

    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedBody(Entry $element, string $prefix = '', string $i18nPrefix = ''): array
    {
        $matrixContent = self::getExpectedMatrixContent($element, $prefix);
        $neoContent = self::getExpectedNeoContent($element, $prefix, $i18nPrefix);

        $expected = [
            $element->getId() => [
                'title' => $prefix . 'Some example title',
                'redactor' => $prefix . '<h1>Here is some header text</h1> Here is some content',
                'matrix' => $matrixContent,
                'checkboxes' => [
                    //TODO: checkbox translation is only done with i18n records
                    'firstCheckboxLabel' => $i18nPrefix . 'First checkbox label',
                    'secondCheckboxLabel' => $i18nPrefix . 'Second checkbox label',
                    'thirdCheckboxLabel' => $i18nPrefix . 'Third checkbox label',
                ],
                'lightswitch' => [
                    //TODO: lightswitch translation is only done with i18n records
                    'onLabel' => $i18nPrefix . 'The label text to display beside the lightswitch’s enabled state',
                    'offLabel' => $i18nPrefix . 'The label text to display beside the lightswitch’s disabled state.',
                ],
                'table' => [
                    'columns' => [
                        //TODO: columns heading translation is only done with i18n records
                        'columnHeading1' => $i18nPrefix . 'Column Heading 1',
                        'columnHeading2' => $i18nPrefix . 'Column Heading 2',
                        'columnHeading3' => $i18nPrefix . 'Column Heading 3',
                        'columnHeading4' => $i18nPrefix . 'Column Heading 4',
                    ],
                    'content' => [
                        0 => [
                            'columnHeading1' => $prefix . 'First row first value',
                            'columnHeading2' => $prefix . 'First row second value',
                            'columnHeading3' => $prefix . 'First row third value',
                            'columnHeading4' => $prefix . 'First row fourth value',
                        ],
                        1 => [
                            'columnHeading1' => $prefix . 'Second row first value',
                            'columnHeading2' => $prefix . 'Second row second value',
                            'columnHeading3' => $prefix . 'Second row third value',
                            'columnHeading4' => $prefix . 'Second row fourth value',
                        ],
                    ],
                ],
                'neo' => $neoContent,
            ],
        ];

        if (TEST_SUPERTABLE_PLUGIN) {
            $supertableContent = self::getExpectedSupertableValue($element, $prefix);
            $expected[$element->getId()]['supertable'] = $supertableContent;
        }

        if (TEST_LINKIT_PLUGIN) {
            $linkitContent = self::getExpectedLinkitContent();
            $expected[$element->getId()]['linkit'] = $linkitContent;
        }

        if (TEST_COLOUR_SWATCHES_PLUGIN) {
            $colourSwatchesContent = self::getExpectedColourSwatchesContent();
            $expected[$element->getId()]['colorSwatches'] = $colourSwatchesContent;
        }

        return $expected;
    }


    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedColourSwatchesContent(): array
    {
        return [
            'labels' =>
                [
                    'a5e0af2bdf434712fd71358f5a2415b1' => 'first label',
                    'e7c9c88325b2a6a2476e2516094b6ba4' => 'second label',
                    'f13b85cdf5fdd245b03675f94d964946' => 'third label',
                ],
        ];
    }

    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedLinkitContent(): array
    {
        return [
            'defaultText' => 'Default link text',
            'customLabels' =>
                [
                    'fruitstudios\\linkit\\models\\Email' => 'Email address label',
                    'fruitstudios\\linkit\\models\\Phone' => 'Phone number label',
                    'fruitstudios\\linkit\\models\\Url' => 'Website url label',
                ],
            'value' =>
                [
                    'fruitstudios\\linkit\\models\\Email' =>
                        [
                            'value' => 'test@lilt.com',
                            'customText' => 'Test linkit text label',
                        ],
                ],
        ];
    }

    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedMatrixContent(Element $element, string $prefix = ''): array
    {
        /**
         * @var MatrixBlockQuery
         */
        $matrixFieldValue = $element->getFieldValue('matrix');

        $firstBlockId = $matrixFieldValue->type('firstBlock')->one()->id;
        $secondBlockId = $matrixFieldValue->type('secondblock')->one()->id;

        $content = [
            $firstBlockId => [
                'fields' => [
                    'plainTextFirstBlock' => sprintf('%s' . 'Plain text first block', $prefix)
                ]
            ],
            $secondBlockId => [
                'fields' => [
                    'plainTextSecondBlock' => sprintf('%s' . 'Plain text second block', $prefix)
                ]
            ],
        ];

        return $content;
    }

    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedSupertableValue(Entry $element, string $prefix = ''): array
    {
        $content = [];
        $field = $element->getFieldValue('supertable');
        $blocks = $field->all();

        foreach ($blocks as $block) {
            $content[$block->id] = [
                'fields' => [
                    'firstField' => $prefix . 'firstField - Supertable text',
                    'secondField' => $prefix . 'secondField - Supertable text',
                ],
            ];
        }

        return $content;
    }

    /**
     * @throws InvalidFieldException
     */
    public static function getExpectedNeoContent(Entry $element, string $prefix = '', string $i18nPrefix = ''): array
    {
        /**
         * @var BlockQuery
         */
        $neoFieldValue = $element->getFieldValue('neo');

        /**
         * @var Block $firstBlock
         */
        $firstBlock = $neoFieldValue->type('firstBlockType')->one();
        $firstBlockId = $neoFieldValue->type('firstBlockType')->one()->id;
        $secondBlockId = $neoFieldValue->type('secondBlockType')->one()->id;

        $content = [
            $firstBlockId => [
                'fields' => [
                    'redactor' => $prefix . 'firstBlockType - redactor - Here is value of field',
                    'lightswitch' => [
                        //TODO: lightswitch translation is only done with i18n records
                        'onLabel' => $i18nPrefix . 'The label text to display beside the lightswitch’s enabled state',
                        'offLabel' => $i18nPrefix . 'The label text to display beside the lightswitch’s disabled state.',
                    ],
                    'matrix' => self::getExpectedMatrixContent($firstBlock, 'neo - firstBlockType - matrix - '),
                ],
            ],
            $secondBlockId => [
                'fields' => [
                    'plainText' => $prefix . 'secondBlockType - plainText - Here is value of field',
                    'table' => [
                        'content' => [
                            0 => [
                                'columnHeading1' => $prefix . 'secondBlockType - table - First row first value',
                                'columnHeading2' => $prefix . 'secondBlockType - table - First row second value',
                                'columnHeading3' => $prefix . 'secondBlockType - table - First row third value',
                                'columnHeading4' => $prefix . 'secondBlockType - table - First row fourth value',
                            ],
                            1 => [
                                'columnHeading1' => $prefix . 'secondBlockType - table - Second row first value',
                                'columnHeading2' => $prefix . 'secondBlockType - table - Second row second value',
                                'columnHeading3' => $prefix . 'secondBlockType - table - Second row third value',
                                'columnHeading4' => $prefix . 'secondBlockType - table - Second row fourth value',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (TEST_SUPERTABLE_PLUGIN) {
            $content[$secondBlockId]['fields']['supertable'] = [];
        }

        return $content;
    }
}
