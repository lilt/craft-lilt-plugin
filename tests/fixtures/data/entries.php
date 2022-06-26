<?php

declare(strict_types=1);

use lilthq\tests\fixtures\EntriesFixture;

$section = Craft::$app->sections->getSectionByHandle('blog');

return [
    [
        'authorId' => '1',
        'sectionId' => $section->id,
        'slug' => 'first-entry-user-1',
        'typeId' => $section->entryTypes[0]->id,
        'title' => 'Some example title',
        'field:redactor' => '<h1>Here is some header text</h1> Here is some content',
        'field:matrix' => EntriesFixture::getMatrixContent(),
        'field:supertable' => EntriesFixture::getSupertableContent(),
        'field:neo' => EntriesFixture::getNeoContent(),
        'field:colorSwatches' => new percipioglobal\colourswatches\models\ColourSwatches(
            json_encode([
                'label' => 'first label',
                'color' => '#CD5C5C',
                'class' => percipioglobal\colourswatches\models\ColourSwatches::class
            ], 4194304)
        ),
        'field:linkit' => [
            'type' => 'fruitstudios\\linkit\\models\\Email',
            'value' => 'test@lilt.com',
            'customText' => 'Test linkit text label',
            'target' => null,
        ],
    ]
];
