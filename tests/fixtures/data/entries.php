<?php

$section = Craft::$app->sections->getSectionByHandle('blog');

return [
    [
        // Standard `craft\elements\Entry` fields.
        'authorId' => '1',
        'sectionId' => $section->id,
        'typeId' => $section->entryTypes[0]->id,
        'title' => 'Theories of matrix',

        // Set a field layout
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',


        // Set custom field values
        'field:myTestField' => 'value of text field',

        'field:myTestRedactorField' => 'here is the body',

        // Set custom Matrix field values
        'field:myMatrixField' => [
            'myMatrixBlock' => [
                'fields:myBlockField' => 'Some text'
            ],
            'myOtherMatrixBlock' => [
                'fields:myOtherBlockField' => 'Some text'
            ],
        ],
    ]
];