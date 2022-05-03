<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\providers;

use Craft;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;

class ExpandedContentProvider
{
    public function provide(ElementInterface $element): array
    {
        $elementType = get_class($element);
        $elementQuery = $elementType::find();

        $elementQuery->andWhere(['elements.id' => [$element->id]]);

        return $this->exporterProvider($element)->export(
            $elementQuery
        );
    }

    private function exporterProvider(ElementInterface $element): ElementExporterInterface
    {
        $exporter = [
            'type' => 'lilthq\craftliltplugin\elements\exporters\ExpandedTranslatable',
            'elementType' => get_class($element)
        ];

        return Craft::$app->getElements()->createExporter($exporter);
    }
}