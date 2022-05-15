<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use craft\fields\RadioButtons;

class RadioButtonsContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $field = $command->getField();

        $i18NRecords = [];
        $fieldKey = $this->getFieldKey($field);
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }


        $options = $command->getField()->options;
        $optionsTranslated = $content[$field->handle];

        foreach ($options as $option) {
            $translation = [
                'target' => $optionsTranslated[$option['value']],
                'source' => $option['label'],
                'sourceSiteId' => $command->getSourceSiteId(),
                'targetSiteId' => $command->getTargetSiteId(),
            ];

            $translation['hash'] = md5(json_encode($translation));
            $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
        }

        return ApplyContentResult::applied($i18NRecords);
    }

    public function support(ApplyContentCommand $command): bool
    {
        return $command->getField() instanceof RadioButtons
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}