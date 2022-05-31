<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration;

use Codeception\PHPUnit\TestCase;
use craft\elements\Entry;
use FunctionalTester;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\FieldsFixture;
use PHPUnit\Framework\Assert;

class ElementTranslatableContentProviderCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ],
            'fields' => [
                'class' => FieldsFixture::class,
            ],
        ];
    }


    public function testProvide(FunctionalTester $I): void
    {
        $element = Entry::findOne(['authorId' => 1]);

        $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide($element);

        Assert::assertSame(
            $element->id,
            array_key_first($content)
        );

        $elementContent = $content[$element->id];

        Assert::assertSame(
            'Some example title',
            $elementContent['title']
        );

        Assert::assertSame(
            '<h1>Here is some header text</h1> Here is some content',
            $elementContent['body']
        );

        Assert::assertSame(
            [
                [
                    'fields' => [
                        'plainTextFirstBlock' => 'Some text'
                    ]
                ],
                [
                    'fields' => [
                        'plainTextSecondBlock' => 'Some text'
                    ]
                ],
            ],
            array_values($elementContent['matrixField'])
        );
    }
}
