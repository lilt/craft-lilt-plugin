<?php

use craft\models\MatrixBlockType;

$section = Craft::$app->sections->getSectionByHandle('blog');

$matrixFieldData = [];
$matrixBlockData = [
    'secondblock' => [
        'plainTextSecondBlock' => 'Some text'
    ],
    'firstBlock' => [
        'plainTextFirstBlock' => 'Some text'
    ],
];

$matrix = Craft::$app->fields->getFieldByHandle('matrixField');

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

return [
    [
        'authorId' => '1',
        'sectionId' => $section->id,
        'slug' => 'first-entry-user-1',
        'typeId' => $section->entryTypes[0]->id,
        'title' => 'Some example title',
        'field:body' => '<h1>Here is some header text</h1> Here is some content',
        'field:matrixField' => $matrixFieldData,
    ]
];