<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\services\providers;

use craft\elements\Entry;
use FunctionalTester;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use lilthq\tests\fixtures\EntriesFixture;
use lilthq\tests\fixtures\ExpectedElementContent;
use lilthq\tests\fixtures\FieldsFixture;
use PHPUnit\Framework\Assert;

use function Arrayy\array_first;

class ElementTranslatableContentProviderCest extends AbstractIntegrationCest
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class,
            ],
            //'fields' => [
            //    'class' => FieldsFixture::class,
            //],
        ];
    }


    /**
     * @throws \craft\errors\InvalidFieldException
     */
    public function testProvide(FunctionalTester $I): void
    {
        $element = Entry::findOne(['authorId' => 1]);

        $content = Craftliltplugin::getInstance()->elementTranslatableContentProvider->provide($element);

        Assert::assertSame(
            $element->id,
            array_key_first($content)
        );

        $actualElementContent = $content[$element->id];

        $expectedElementContent = array_first(
            ExpectedElementContent::getExpectedBody($element)
        );

        Assert::assertSame(
            $expectedElementContent,
            $actualElementContent
        );
    }
}
