<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use Craft;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

use function Arrayy\array_first;

class ColourSwatchesContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $i18NRecords = [];

        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }
        $fieldContent = $content[$fieldKey];

        $options = $command->getField()->options ?? [];
        foreach ($options as $option) {
            if (empty($option['label']) || empty($option['color'])) {
                continue;
            }

            $key = md5(sprintf('%s%s', $option['color'], $option['label']));

            if (empty($fieldContent['labels'][$key])) {
                continue;
            }

            $translation = [
                'target' => $fieldContent['labels'][$key],
                'source' => $option['label'],
                'sourceSiteId' => $command->getSourceSiteId(),
                'targetSiteId' => $command->getTargetSiteId(),
            ];

            $translation['hash'] = md5(json_encode($translation));
            $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
        }

        $value = $this->getOriginalFieldSerializedValue($command);

        $newValue = $command->getField()->normalizeValue(
            $value,
            $command->getElement()
        );

        $command->getElement()->setFieldValue($command->getField()->handle, $newValue);

        $this->forceSave($command);

        return ApplyContentResult::applied(
            $i18NRecords,
            $command->getElement()->getFieldValue(
                $command->getField()->handle
            )
        );
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::COLOUR_SWATCHES_FIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
