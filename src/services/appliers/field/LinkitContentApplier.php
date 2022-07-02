<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

use function Arrayy\array_first;

class LinkitContentApplier extends AbstractContentApplier implements ApplierInterface
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

        $value = [];
        if (!empty($fieldContent['value'])) {
            $valueType = array_key_first($fieldContent['value']);
            $value = array_first($fieldContent['value']);
            $value['type'] = $valueType;
        }

        $value = array_merge(
            $this->getOriginalFieldSerializedValue($command),
            $value
        );

        $newValue = $command->getField()->normalizeValue(
            $value,
            $command->getElement()
        );

        $command->getElement()->setFieldValue($command->getField()->handle, $newValue);

        $this->forceSave($command);

        # Custom labels
        foreach ($fieldContent['customLabels'] as $key => $customLabel) {
            $translation = [
                'target' => $customLabel,
                'source' => $command->getField()->types[$key]['customLabel'],
                'sourceSiteId' => $command->getSourceSiteId(),
                'targetSiteId' => $command->getTargetSiteId(),
            ];

            $translation['hash'] = md5(json_encode($translation));
            $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
        }

        if (!empty($fieldContent['defaultText'])) {
            # Default text
            $translation = [
                'target' => $fieldContent['defaultText'],
                'source' => $command->getField()->defaultText,
                'sourceSiteId' => $command->getSourceSiteId(),
                'targetSiteId' => $command->getTargetSiteId(),
            ];
            $translation['hash'] = md5(json_encode($translation));
            $i18NRecords[$translation['hash']] = $this->createI18NRecord($translation);
        }

        return ApplyContentResult::applied(
            $i18NRecords,
            $command->getElement()->getFieldValue(
                $command->getField()->handle
            )
        );
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::LINKIT_FIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
