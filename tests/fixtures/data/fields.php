<?php

declare(strict_types=1);

use craft\fields\Matrix;
use craft\fields\PlainText;

return [
    [
        'type' => 'field_layout_with_matrix_and_normal_fields', // Required - can be set to whatever you want.
        'tabs' => [ // Required - Value can be set to an empty array[]
            [
                'name' => 'My First Tab', // Required
                'fields' => [ // Required - Value can be set to an empty array[]
                    [
                        'layout-link' => [ // Required
                            'required' => true // Required
                        ],
                        'field' => [
                            'name' => 'Test field', // Required
                            'handle' => 'myFieldFromTest', // Required
                            'fieldType' => PlainText::class, // Required
                        ]
                    ],
                    [
                        'layout-link' => [ // Required
                            'required' => true // Required
                        ],
                        'field' => [
                            'name' => 'Test redactor field', // Required
                            'handle' => 'myRedactorFieldFromTest', // Required
                            'fieldType' => craft\redactor\Field::class, // Required
                        ]
                    ],
                    // Matrix fields are supported in the following config:
                    [
                        'layout-link' => [
                            'required' => false
                        ],
                        'field' => [
                            'name' => 'Matrix Field',
                            'handle' => 'myMatrixField',
                            'fieldType' => Matrix::class,
                            'blockTypes' => [
                                'new1' => [
                                    'name' => 'A Block',
                                    'handle' => 'myMatrixBlock',
                                    'fields' => [
                                        'new1' => [
                                            'type' => PlainText::class,
                                            'name' => 'First Subfield',
                                            'handle' => 'myBlockField',
                                            'instructions' => '',
                                            'required' => false,
                                            'typesettings' => [
                                                'multiline' => ''
                                            ]
                                        ]
                                    ]
                                ],
                                'new2' => [
                                    'name' => 'Another Block',
                                    'handle' => 'myOtherMatrixBlock',
                                    'fields' => [
                                        'new1' => [
                                            'type' => PlainText::class,
                                            'name' => 'Another Subfield',
                                            'handle' => 'myOtherBlockField',
                                            'instructions' => '',
                                            'required' => false,
                                            'typesettings' => [
                                                'multiline' => ''
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ]
    ]
];
