<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use Craft;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class LightswitchContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $i18NRecords = [];

        $field = $command->getField();
        $fieldKey = $this->getFieldKey($field);
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        foreach ($content[$fieldKey] as $attribute => $translation) {
            $translation = [
                'target' => $translation,
                'source' => $field->$attribute,
                'sourceSiteId' => $command->getSourceSiteId(),
                'targetSiteId' => $command->getTargetSiteId(),
            ];

            $translation['hash'] = md5(json_encode($translation));
            $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
        }

        $originalElement = Craft::$app->elements->getElementById(
            $command->getElement()->getCanonicalId(),
            null,
            $command->getSourceSiteId()
        );

        return ApplyContentResult::applied($i18NRecords, $originalElement->getFieldValue($field->handle));
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_LIGHTSWITCH
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
