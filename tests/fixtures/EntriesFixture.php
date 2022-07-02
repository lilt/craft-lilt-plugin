<?php

declare(strict_types=1);

namespace lilthq\tests\fixtures;

use benf\neo\helpers\Memoize;
use benf\neo\Plugin;
use Craft;
use craft\test\fixtures\elements\EntryFixture;
use verbb\supertable\SuperTable;

class EntriesFixture extends EntryFixture
{
    public $dataFile = __DIR__ . '/data/entries.php';

    /**
     * @inheritdoc
     */
    //public $depends = [
    //    FieldsFixture::class
    //];

    public static function getSupertableContent(): array
    {
        # SUPER TABLE PLUGIN FIELD
        $supertable = Craft::$app->fields->getFieldByHandle('supertable');

        /**
         * @var \verbb\supertable\services\SuperTableService $superTableService
         */
        $superTableService = SuperTable::$plugin->getService();
        $blockTypes = SuperTable::$plugin->getService()->getBlockTypesByFieldId((int)$supertable->id);
        $blockType = $blockTypes[0];

        return [
            [
                'type' => $blockType->id,
                'enabled' => true,
                'collapsed' => false,
                'fields' => [
                    'firstField' => 'firstField - Supertable text',
                    'secondField' => 'secondField - Supertable text',
                ]
            ]
        ];
    }

    public static function getNeoContent(): array
    {
        $blockTypes = Plugin::$plugin->blockTypes;
        $neoBlockTypes = $blockTypes->getAllBlockTypes();
        $field = Craft::$app->getFields()->getFieldByHandle(
            'neo'
        );

        //WE NEED THIS TO MAKE IT WORKING FOR SOME REASON?
        //TODO: find another approach
        Memoize::$blockTypesByFieldId[$field->id] = $neoBlockTypes;

        $neoData = [
            'firstBlockType' => [
                'redactor' => 'firstBlockType - redactor - Here is value of field',
                'matrix' => EntriesFixture::getMatrixContent('neo - firstBlockType - matrix - '),
            ],
            'secondBlockType' => [
                'plainText' => 'secondBlockType - plainText - Here is value of field',
                'table' => [
                    0 => [
                        'col1' => 'secondBlockType - table - First row first value',
                        'col2' => 'secondBlockType - table - First row second value',
                        'col3' => 'secondBlockType - table - First row third value',
                        'col4' => 'secondBlockType - table - First row fourth value',
                    ],
                    1 => [
                        'col1' => 'secondBlockType - table - Second row first value',
                        'col2' => 'secondBlockType - table - Second row second value',
                        'col3' => 'secondBlockType - table - Second row third value',
                        'col4' => 'secondBlockType - table - Second row fourth value',
                    ],
                ],
            ]
        ];
        $neoFieldData = [];

        foreach ($neoBlockTypes as $neoBlockType) {
            $neoFieldData[$neoBlockType->id] = [
                'modified' => 1,
                'type' => $neoBlockType->handle,
                'enabled' => true,
                'collapsed' => false,
                "level" => $neoBlockType->topLevel,
                'fields' => $neoData[$neoBlockType->handle]
            ];
        }

        return $neoFieldData;
    }

    public static function getMatrixContent(string $prefix = ''): array
    {
        $matrixFieldData = [];
        $matrixBlockData = [
            'firstBlock' => [
                'plainTextFirstBlock' =>  sprintf('%sPlain text first block', $prefix)
            ],
            'secondblock' => [
                'plainTextSecondBlock' => sprintf('%sPlain text second block', $prefix)
            ],
        ];

        $matrix = Craft::$app->fields->getFieldByHandle('matrix');

        /**
         * @var MatrixBlockType[] $matrixBlockTypes
         */

        $matrixBlockTypes = $matrix->getBlockTypes();

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $matrixFieldData[$matrixBlockType->id] = [
                'type' => $matrixBlockType->handle,
                'enabled' => true,
                'collapsed' => false,
                'fields' => $matrixBlockData[$matrixBlockType->handle]
            ];
        }

        return $matrixFieldData;
    }
}
